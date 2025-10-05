<?php

namespace App\Livewire\Pages;

use App\Models\DoctorShift;
use App\Models\Patient;
use App\Models\Appointment;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ManageAppointments extends Component
{
    public $doctorId = 0; // در صورت نیاز به انتخاب دکتر می‌توان این متغیر را داینامیک کرد
    public $search = '';
    public $selectedDate;
    public $patientName = '';
    public $patientPhone = '';
    public $patientNationalId = '';
    public $appointments; // نتایج آخرین جستجو
    public $slots = []; // اسلات‌های روز انتخاب شده
    public $selectedSlot = null; // "{shiftId}|{HH:MM}|{HH:MM}"


    public function mount()
    {
        $this->selectedDate = Carbon::today()->format('Y-m-d');
        $this->appointments = collect([]);
        $this->searchAppointments();
        $this->generateSlotsForDate($this->selectedDate);
    }

    /**
     * بروزرسانی جدول و اسلات‌ها هنگام تغییر تاریخ
     */
    public function updatedSelectedDate($value)
    {
        $this->selectedSlot = null;
        $this->searchAppointments();
        $this->generateSlotsForDate($value);
    }

    /**
     * جستجو و نمایش نوبت‌ها با فیلتر تاریخ و نام یا کد ملی بیمار
     */
    public function searchAppointments()
    {
        $query = Appointment::query();

        if ($this->selectedDate) {
            $query->whereDate('day', $this->selectedDate);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('patient_name', 'like', '%' . $this->search . '%')
                    ->orWhere('patient_national_id', 'like', '%' . $this->search . '%');
            });
        }

        $this->appointments = $query->orderBy('queue_number')->get();
    }

    /**
     * ساخت اسلات‌ها برای تاریخ انتخاب‌شده
     */
    public function generateSlotsForDate($date)
    {
        $this->slots = [];
        if (empty($date)) return;

        try {
            $carbonDate = Carbon::parse($date);
        } catch (\Exception $e) {
            return;
        }

        $now = Carbon::now();

        // نام روز برای فیلتر شیفت‌ها
        $dayNameEn = $carbonDate->format('l');
        $dayNameFa = $carbonDate->locale('fa')->dayName ?? null;

        // دریافت شیفت‌های دکتر
        $shifts = DoctorShift::where('doctor_id', $this->doctorId)
            ->where(function ($q) use ($dayNameEn, $dayNameFa) {
                $q->orWhere('day', $dayNameEn);
                if ($dayNameFa) $q->orWhere('day', $dayNameFa);
            })->get();

        if ($shifts->isEmpty()) return;

        // اپوینت‌منت‌های روز
        $appointments = Appointment::where('doctor_id', $this->doctorId)
            ->whereDate('day', $date)
            ->get();

        foreach ($shifts as $shift) {
            $duration = (int)$shift->duration;
            if ($duration <= 0) continue;

            $shiftStart = $this->parseTimeToCarbon($shift->start_time);
            $shiftEnd = $this->parseTimeToCarbon($shift->end_time);
            if (!$shiftStart || !$shiftEnd) continue;

            // متصل کردن زمان به تاریخ
            $shiftStart = Carbon::createFromFormat('Y-m-d H:i', $carbonDate->format('Y-m-d').' '.$shiftStart->format('H:i'));
            $shiftEnd   = Carbon::createFromFormat('Y-m-d H:i', $carbonDate->format('Y-m-d').' '.$shiftEnd->format('H:i'));

            $cursor = $shiftStart->copy();
            $counter = 1;

            while ($cursor->lt($shiftEnd)) {
                $slotEnd = $cursor->copy()->addMinutes($duration);
                if ($slotEnd->gt($shiftEnd)) $slotEnd = $shiftEnd->copy();

                // بررسی رزرو شدن اسلات
                $booked = $appointments->contains(function ($a) use ($cursor, $slotEnd, $carbonDate) {
                    if (empty($a->start_time) || empty($a->end_time)) return false;

                    $aStart = Carbon::createFromFormat('Y-m-d H:i', $carbonDate->format('Y-m-d').' '.$this->normalizeTime($a->start_time));
                    $aEnd   = Carbon::createFromFormat('Y-m-d H:i', $carbonDate->format('Y-m-d').' '.$this->normalizeTime($a->end_time));

                    return $cursor->lt($aEnd) && $slotEnd->gt($aStart);
                });

                $expired = $slotEnd->lte($now); // زمان گذشته

                $this->slots[] = [
                    'shift_id' => $shift->id,
                    'start' => $cursor->format('H:i'),
                    'end' => $slotEnd->format('H:i'),
                    'value' => sprintf('%d|%s|%s', $shift->id, $cursor->format('H:i'), $slotEnd->format('H:i')),
                    'booked' => $booked,
                    'expired' => $expired,
                    'queue_index' => $counter,
                ];

                $cursor->addMinutes($duration);
                $counter++;

                if ($cursor->equalTo($shiftStart)) break; // محافظت از لوپ بی‌نهایت
            }
        }
    }

    /**
     * انتخاب اسلات توسط منشی
     */
    public function selectSlot($value)
    {
        $this->selectedSlot = $value;
    }

    /**
     * ثبت نوبت جدید از اسلات انتخاب‌شده
     */
    public function addAppointmentFromSlot()
    {
        if (!$this->selectedSlot) {
            session()->flash('error', 'اسلاتی انتخاب نشده است.');
            return;
        }

        $parts = explode('|', $this->selectedSlot);
        if (count($parts) !== 3) {
            session()->flash('error', 'اسلات انتخابی نامعتبر است.');
            return;
        }

        [$shiftId, $selStart, $selEnd] = $parts;

        $shift = DoctorShift::find($shiftId);
        if (!$shift) {
            session()->flash('error', 'شیفت مرتبط پیدا نشد.');
            return;
        }

        // ولیدیشن اطلاعات بیمار
        if (!is_string($this->patientName) || strlen(trim($this->patientName)) < 2) {
            session()->flash('error', 'نام بیمار معتبر نیست.');
            return;
        }
        if (!preg_match('/^\d{10,12}$/', $this->patientNationalId)) {
            session()->flash('error', 'کد ملی معتبر نیست.');
            return;
        }
        if (!preg_match('/^09\d{9}$/', $this->patientPhone)) {
            session()->flash('error', 'شماره موبایل معتبر نیست.');
            return;
        }

        $date = $this->selectedDate ?: Carbon::today()->format('Y-m-d');
        $carbonDate = Carbon::parse($date);

        $shiftStart = Carbon::createFromFormat('Y-m-d H:i', $carbonDate->format('Y-m-d').' '.$this->normalizeTime($shift->start_time));
        $shiftEnd   = Carbon::createFromFormat('Y-m-d H:i', $carbonDate->format('Y-m-d').' '.$this->normalizeTime($shift->end_time));

        $selStartNorm = $this->normalizeTime($selStart);
        $selEndNorm   = $this->normalizeTime($selEnd);
        $selStartCarbon = Carbon::createFromFormat('Y-m-d H:i', $carbonDate->format('Y-m-d').' '.$selStartNorm);
        $selEndCarbon   = Carbon::createFromFormat('Y-m-d H:i', $carbonDate->format('Y-m-d').' '.$selEndNorm);

        // بررسی گذشته بودن یا رزرو شدن اسلات
        if ($selEndCarbon->lte(Carbon::now())) {
            session()->flash('error', 'نمی‌توان برای اسلاتی که زمانش گذشته نوبت ثبت کرد.');
            return;
        }

        $appointments = Appointment::where('doctor_id', $this->doctorId)
            ->whereDate('day', $date)
            ->get();

        foreach ($appointments as $a) {
            if (empty($a->start_time) || empty($a->end_time)) continue;
            $aStart = Carbon::createFromFormat('Y-m-d H:i', $carbonDate->format('Y-m-d').' '.$this->normalizeTime($a->start_time));
            $aEnd   = Carbon::createFromFormat('Y-m-d H:i', $carbonDate->format('Y-m-d').' '.$this->normalizeTime($a->end_time));
            if ($selStartCarbon->lt($aEnd) && $selEndCarbon->gt($aStart)) {
                session()->flash('error', 'این اسلات قبلاً رزرو شده یا با یک نوبت دیگر تداخل دارد.');
                return;
            }
        }

        // ایجاد یا دریافت بیمار
        $patient = Patient::firstOrCreate(
            ['national_id' => $this->patientNationalId],
            [
                'first_name' => $this->patientName,
                'phone' => $this->patientPhone,
            ]
        );

        // ایجاد نوبت در تراکنش
        DB::transaction(function () use ($patient, $date, $shift, $selStartNorm, $selEndNorm) {
            // شماره نوبت
            $lastQueue = Appointment::whereDate('day', $date)->max('queue_number');
            $queueNumber = $lastQueue ? $lastQueue + 1 : 1;

            Appointment::create([
                'doctor_id' => $this->doctorId,
                'patient_id' => $patient->id,
                'day' => $date,
                'start_time' => $selStartNorm,
                'end_time' => $selEndNorm,
                'queue_number' => $queueNumber,
                'attended' => false,
            ]);
        });

        // ریست فرم و بروزرسانی جدول و اسلات‌ها
        $this->reset(['patientName', 'patientPhone', 'patientNationalId', 'selectedSlot']);
        $this->searchAppointments();
        $this->generateSlotsForDate($date);

        session()->flash('success', '✅ نوبت با موفقیت ثبت شد.');
    }

    /**
     * علامت‌گذاری حضور بیمار
     */
    public function markPresent($id)
    {
        $appointment = Appointment::find($id);
        if ($appointment) {
            $appointment->update(['attended' => true]);
            $this->searchAppointments();
            $this->generateSlotsForDate($this->selectedDate);
        }
    }

    /**
     * حذف نوبت
     */
    public function delete($id)
    {
        Appointment::destroy($id);
        $this->searchAppointments();
        $this->generateSlotsForDate($this->selectedDate);
    }

    /**
     * تبدیل رشته زمان به Carbon
     */
    private function parseTimeToCarbon($time)
    {
        if (empty($time)) return null;

        if (preg_match('/^(\d{1,2}):(\d{2})/', trim($time), $m)) {
            $hour = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $minute = $m[2];
            return Carbon::createFromFormat('H:i', "$hour:$minute");
        }

        return null;
    }

    /**
     * نرمال‌سازی ساعت به HH:MM
     */
    private function normalizeTime($time)
    {
        if (empty($time)) return null;

        if (preg_match('/^(\d{1,2}):(\d{2})/', trim($time), $m)) {
            $hour = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $minute = $m[2];
            return "$hour:$minute";
        }

        return null;
    }

    public function render()
    {
        return view('livewire.pages.manage-appointments', [
            'appointments' => $this->appointments,
            'slots' => $this->slots,
        ]);
    }
}
