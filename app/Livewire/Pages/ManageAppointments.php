<?php

namespace App\Livewire\Pages;

use App\Models\DoctorShift;
use App\Models\Patient;
use Livewire\Component;
use App\Models\Appointment;
use Carbon\Carbon;

class ManageAppointments extends Component
{
    public $doctorId = 0; // اگر لازم داری انتخاب دکتر بذاری میتونی این رو تغییر بدی
    public $search = '';
    public $selectedDate;
    public $patientName = '';
    public $patientPhone = '';
    public $patientNationalId = '';
    public $appointments; // نتایج آخرین سرچ
    public $slots = []; // اسلات‌های ساخته شده برای selectedDate
    public $selectedSlot = null; // "{shiftId}|{HH:MM}|{HH:MM}"

    public function mount()
    {
        $this->selectedDate = Carbon::today()->format('Y-m-d');
        $this->appointments = collect([]);
        $this->searchAppointments();
        $this->generateSlotsForDate($this->selectedDate);
    }

    public function updatedSelectedDate($value)
    {
        // هنگام تغییر تاریخ: رفرش جدول و اسلات‌ها
        $this->searchAppointments();
        $this->selectedSlot = null;
        $this->generateSlotsForDate($value);
    }

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
     * - استفاده از شیفت‌های دکتر (DoctorShift) براساس روز هفته (فارسی/لاتین بسته به ذخیره شما)
     * - نشون دادن booked و expired
     */
    public function generateSlotsForDate($date)
    {
        $this->slots = [];

        if (empty($date)) return;

        try {
            $carbonDate = Carbon::parse($date, config('app.timezone'));
        } catch (\Exception $e) {
            return;
        }

        $now = Carbon::now(config('app.timezone'));

        // گرفتن نام روز (فارسی و انگلیسی)
        $dayNameFa = $carbonDate->locale('fa')->dayName ?? null;
        $dayNameEn = $carbonDate->format('l');

        // پیدا کردن شیفت‌ها بر اساس روز
        $shifts = DoctorShift::where('doctor_id', $this->doctorId)
            ->where(function($q) use ($dayNameFa, $dayNameEn) {
                if ($dayNameFa) $q->orWhere('day', $dayNameFa);
                $q->orWhere('day', $dayNameEn);
            })
            ->get();

        if ($shifts->isEmpty()) return;

        // گرفتن اپوینت‌منت‌های روز
        $appointments = Appointment::where('doctor_id', $this->doctorId)
            ->whereDate('day', $date)
            ->get();

        foreach ($shifts as $shift) {
            $duration = (int)$shift->duration;
            if ($duration <= 0) continue;

            $shiftStart = $this->parseTimeToCarbon($shift->start_time);
            $shiftEnd   = $this->parseTimeToCarbon($shift->end_time);
            if (!$shiftStart || !$shiftEnd) continue;

            // وصل کردن زمان به تاریخ انتخاب‌شده با timezone یکسان
            $shiftStart = Carbon::createFromFormat(
                'Y-m-d H:i',
                $carbonDate->format('Y-m-d') . ' ' . $shiftStart->format('H:i'),
                config('app.timezone')
            );
            $shiftEnd = Carbon::createFromFormat(
                'Y-m-d H:i',
                $carbonDate->format('Y-m-d') . ' ' . $shiftEnd->format('H:i'),
                config('app.timezone')
            );

            $cursor = $shiftStart->copy();
            $counter = 1;

            while ($cursor->lt($shiftEnd)) {
                $slotEnd = $cursor->copy()->addMinutes($duration);
                if ($slotEnd->gt($shiftEnd)) $slotEnd = $shiftEnd->copy();

                // بررسی رزرو
                $booked = $appointments->contains(function($a) use ($cursor, $slotEnd, $carbonDate) {
                    if (empty($a->start_time) || empty($a->end_time)) return false;

                    $aStart = Carbon::createFromFormat(
                        'Y-m-d H:i',
                        $carbonDate->format('Y-m-d') . ' ' . Carbon::parse($a->start_time)->format('H:i'),
                        config('app.timezone')
                    );
                    $aEnd = Carbon::createFromFormat(
                        'Y-m-d H:i',
                        $carbonDate->format('Y-m-d') . ' ' . Carbon::parse($a->end_time)->format('H:i'),
                        config('app.timezone')
                    );

                    return $cursor->lt($aEnd) && $slotEnd->gt($aStart);
                });

                // بررسی گذشته بودن اسلات
                $expired = $slotEnd->lte($now);

                $this->slots[] = [
                    'shift_id' => $shift->id,
                    'start' => $cursor->format('H:i'),
                    'end' => $slotEnd->format('H:i'),
                    'value' => sprintf('%d|%s|%s', $shift->id, $cursor->format('H:i'), $slotEnd->format('H:i')),
                    'booked' => $booked,
                    'expired' => $expired,
                    'queue_index' => $counter,
                    'shift_start' => $shiftStart->format('H:i'),
                    'shift_end' => $shiftEnd->format('H:i'),
                    'duration' => $duration,
                ];

                $cursor->addMinutes($duration);
                $counter++;

                // محافظت در برابر لوپ بی‌نهایت
                if ($cursor->equalTo($shiftStart)) break;
            }
        }

    }

    /**
     * وقتی منشی یک اسلات رو انتخاب می‌کنه، selectedSlot مقداردهی می‌شه
     */
    public function selectSlot($value)
    {
        $this->selectedSlot = $value;
    }

    /**
     * ثبت نوبت از اسلات انتخاب‌شده
     */
    public function addAppointmentFromSlot()
    {
        if (!$this->selectedSlot) {
            session()->flash('error', 'اسلاتی انتخاب نشده است.');
            return;
        }

        // استخراج مقادیر
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

        // اعتبارسنجی اطلاعات بیمار (می‌تونید اینها رو شل‌تر کنید اگر خواستی)
        if (!is_string($this->patientName) || strlen(trim($this->patientName)) < 2) {
            session()->flash('error', 'نام بیمار معتبر نیست.');
            return;
        }
        if (!preg_match('/^\d{10,12}$/', $this->patientNationalId)) {
            session()->flash('error', 'کد ملی معتبر نیست.');
            return;
        }
        if (!preg_match('/^09\d{9}$/', $this->patientPhone)) {
            session()->flash('error', 'شماره موبایل معتبر نیست و باید با 09 شروع شود.');
            return;
        }

        // ساخت Carbon ها برای شیفت و اسلات
        $date = $this->selectedDate ?: Carbon::today()->format('Y-m-d');
        $carbonDate = Carbon::parse($date);

        $shiftStart = $this->parseTimeToCarbon($shift->start_time);
        $shiftEnd   = $this->parseTimeToCarbon($shift->end_time);
        if (!$shiftStart || !$shiftEnd) {
            session()->flash('error', 'اطلاعات شیفت ناقص است.');
            return;
        }
        // وصل کردن به تاریخ
        $shiftStart = Carbon::createFromFormat('Y-m-d H:i', $carbonDate->format('Y-m-d') . ' ' . $shiftStart->format('H:i'));
        $shiftEnd   = Carbon::createFromFormat('Y-m-d H:i', $carbonDate->format('Y-m-d') . ' ' . $shiftEnd->format('H:i'));

        // نرمال‌سازی انتخاب‌شده
        $selStartNorm = $this->normalizeTime($selStart);
        $selEndNorm   = $this->normalizeTime($selEnd);
        if (!$selStartNorm || !$selEndNorm) {
            session()->flash('error', 'فرمت ساعت انتخابی نامعتبر است.');
            return;
        }

        $selStartCarbon = Carbon::createFromFormat('Y-m-d H:i', $carbonDate->format('Y-m-d') . ' ' . $selStartNorm);
        $selEndCarbon   = Carbon::createFromFormat('Y-m-d H:i', $carbonDate->format('Y-m-d') . ' ' . $selEndNorm);

        // اگر اسلات منقضی شده یا قبلاً رزرو شده نباید ثبت شود
        if ($selEndCarbon->lte(Carbon::now())) {
            session()->flash('error', 'نمی‌توان برای اسلاتی که زمانش گذشته نوبت ثبت کرد.');
            return;
        }

        // بررسی تداخل با اپوینت‌منت‌های موجود
        $appointments = Appointment::where('doctor_id', $this->doctorId)
            ->whereDate('day', $date)
            ->get();

        foreach ($appointments as $a) {
            if (empty($a->start_time) || empty($a->end_time)) continue;
            $aStart = Carbon::createFromFormat('Y-m-d H:i', $carbonDate->format('Y-m-d') . ' ' . $this->normalizeTime($a->start_time));
            $aEnd   = Carbon::createFromFormat('Y-m-d H:i', $carbonDate->format('Y-m-d') . ' ' . $this->normalizeTime($a->end_time));
            if ($selStartCarbon->lt($aEnd) && $selEndCarbon->gt($aStart)) {
                session()->flash('error', 'این اسلات قبلاً رزرو شده یا با یک نوبت دیگر تداخل دارد.');
                return;
            }
        }

        // محاسبه شماره نوبت (بر اساس جایگاه در شیفت یا به عنوان next queue)
        $duration = (int) $shift->duration;
        $queueNumber = null;
        $counter = 1;
        $cursor = $shiftStart->copy();

        while ($cursor->lt($shiftEnd)) {
            $slotEnd = $cursor->copy()->addMinutes($duration);
            if ($slotEnd->gt($shiftEnd)) $slotEnd = $shiftEnd->copy();

            if ($cursor->format('H:i') === $selStartNorm && $slotEnd->format('H:i') === $selEndNorm) {
                $queueNumber = $counter;
                break;
            }

            $cursor->addMinutes($duration);
            $counter++;

            if ($cursor->equalTo($shiftStart)) break;
        }

        // اگر نتونستیم موقعیت رو پیدا کنیم، شماره صف بعدی برای آن روز رو قرار می‌دهیم
        if (!$queueNumber) {
            $lastQueue = Appointment::whereDate('day', $date)->max('queue_number');
            $queueNumber = $lastQueue ? $lastQueue + 1 : 1;
        } else {
            // اگر در آن شماره صف قبلاً کسی هست شماره صف را به آخرین+1 تغییر بده
            $existsSameQueue = Appointment::whereDate('day', $date)
                ->where('queue_number', $queueNumber)
                ->exists();
            if ($existsSameQueue) {
                $lastQueue = Appointment::whereDate('day', $date)->max('queue_number');
                $queueNumber = $lastQueue ? $lastQueue + 1 : $queueNumber;
            }
        }
        $patient = Patient::firstOrCreate(
            ['national_id' => $this->patientNationalId],
            [
                'first_name' => $this->patientName,   // یا جدا first_name و last_name بذاری
                'phone' => $this->patientPhone,
            ]
        );

        Appointment::create([
            'doctor_id' => $this->doctorId,
            'patient_id' => $patient->id,
            'day' => $date,
            'start_time' => $selStartNorm,
            'end_time' => $selEndNorm,
            'queue_number' => $queueNumber,
            'attended' => false,
        ]);

        // رفرش
        $this->reset(['patientName', 'patientPhone', 'patientNationalId', 'selectedSlot']);
        $this->searchAppointments();
        $this->generateSlotsForDate($date);

        session()->flash('success', "✅ نوبت با موفقیت ثبت شد. شماره نوبت: {$queueNumber}");
    }


public function addAppointment()
{
    $date = $this->selectedDate ?: Carbon::today()->format('Y-m-d');

    // تولید اسلات‌های روز انتخاب‌شده
    $slots = $this->generateSlotsForDate($date);

    // بررسی وجود اسلات آزاد و آینده
    $hasFreeFutureSlot = collect($slots)->contains(function ($slot) {
        return !$slot['reserved'] && !$slot['past'];
    });

    if ($hasFreeFutureSlot) {
        session()->flash('error', '❌ هنوز ساعت خالی برای این روز وجود دارد، لطفاً از لیست استفاده کنید.');
        return;
    }

    // اعتبارسنجی ورودی‌ها
    if (!is_string($this->patientName) || strlen(trim($this->patientName)) < 2) {
        session()->flash('error', 'نام بیمار معتبر نیست.');
        return;
    }
    if (!preg_match('/^\d{10,12}$/', $this->patientNationalId)) {
        session()->flash('error', 'کد ملی معتبر نیست.');
        return;
    }
    if (!preg_match('/^09\d{9}$/', $this->patientPhone)) {
        session()->flash('error', 'شماره موبایل معتبر نیست و باید با 09 شروع شود.');
        return;
    }

    // گرفتن آخرین شماره صف برای روز
    $lastQueue = Appointment::whereDate('day', $date)->max('queue_number');

    // ایجاد نوبت
    Appointment::create([
        'doctor_id' => $this->doctorId,
        'patient_name' => $this->patientName,
        'patient_phone' => $this->patientPhone,
        'patient_national_id' => $this->patientNationalId,
        'day' => $date,
        'start_time' => null,
        'end_time' => null,
        'queue_number' => $lastQueue ? $lastQueue + 1 : 1,
        'attended' => false,
    ]);

    // ریست کردن فرم و آپدیت جدول
    $this->reset(['patientName', 'patientPhone', 'patientNationalId']);
    $this->searchAppointments();
    $this->generateSlotsForDate($date);

    session()->flash('success', "✅ نوبت با موفقیت برای تاریخ {$date} ثبت شد.");
}


    public function markPresent($id)
    {
        $appointment = Appointment::find($id);
        if ($appointment) {
            $appointment->update(['attended' => true]);
            $this->searchAppointments(); // بروز رسانی جدول
            $this->generateSlotsForDate($this->selectedDate);
        }
    }

    public function delete($id)
    {
        Appointment::destroy($id);
        $this->searchAppointments(); // بروز رسانی جدول
        $this->generateSlotsForDate($this->selectedDate);
    }

    /**
     * تبدیل رشته زمان به Carbon (بازگشت به Carbon با زمان ساعت:دقیقه)
     */
    private function parseTimeToCarbon($time)
    {
        if (empty($time)) return null;

        if (preg_match('/^(\d{1,2}):(\d{2})/', trim($time), $m)) {
            $hour = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $minute = $m[2];

            try {
                return Carbon::createFromFormat('H:i', "$hour:$minute");
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

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
