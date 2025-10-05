<?php

namespace App\Livewire\Pages;

use App\Models\Patient;
use App\Models\DoctorShift;
use App\Models\Appointment;
use Carbon\Carbon;
use Hekmatinasser\Verta\Verta;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class BookAppointment extends Component
{
    public $doctorId = 0;
    public $selectedDay;
    public $selectedSlot;
    public $patientName;
    public $patientPhone;
    public $patientNationalId;
    public $days = [];
    public $slots = [];

    /**
     * =========================
     * مقداردهی اولیه و ساخت روزها و شیفت‌ها
     * =========================
     */
    public function mount()
    {
        $today = Carbon::today();
        $endDate = $today->copy()->addWeeks(3);
        $days = [];

        for ($date = $today->copy(); $date <= $endDate; $date->addDay()) {
            // فیلتر کردن روز امروز
            if ($date->isToday()) continue;

            $dayName = Verta::instance($date)->formatWord('l');

            $shifts = DoctorShift::where('doctor_id', $this->doctorId)
                ->where('day', $dayName)
                ->get();

            if ($shifts->isEmpty()) continue;

            $days[$date->format('Y-m-d')] = [
                'label' => $dayName,
                'date' => Verta::instance($date)->format('%d %B %Y'),
                'slots' => $shifts,
                'disabled' => $date->lt($today),
            ];
        }

        $this->days = $days;
    }


    /**
     * =========================
     * وقتی روز انتخاب شد، اسلات‌ها رو می‌سازیم
     * =========================
     */
    public function updatedSelectedDay($day)
    {
        $this->selectedSlot = null;
        $this->slots = [];

        if (!isset($this->days[$day])) return;

        $appointments = Appointment::where('doctor_id', $this->doctorId)
            ->where('day', $day)
            ->get();

        foreach ($this->days[$day]['slots'] as $shift) {
            $duration = (int) $shift->duration;
            if ($duration <= 0) continue;

            $shiftStart = $this->parseTimeToCarbon($shift->start_time);
            $shiftEnd   = $this->parseTimeToCarbon($shift->end_time);

            if (!$shiftStart || !$shiftEnd) continue;

            $cursor = $shiftStart->copy();
            $maxIterations = 1000; // جلوگیری از حلقه بی‌نهایت
            $iteration = 0;

            while ($cursor->lt($shiftEnd) && $iteration < $maxIterations) {
                $slotEnd = $cursor->copy()->addMinutes($duration);
                if ($slotEnd->gt($shiftEnd)) $slotEnd = $shiftEnd->copy();

                $booked = false;
                foreach ($appointments as $a) {
                    if (empty($a->start_time) || empty($a->end_time)) continue;

                    $aStart = $this->parseTimeToCarbon($a->start_time);
                    $aEnd   = $this->parseTimeToCarbon($a->end_time);

                    if (!$aStart || !$aEnd) continue;

                    if ($cursor->lt($aEnd) && $slotEnd->gt($aStart)) {
                        $booked = true;
                        break;
                    }
                }

                $this->slots[] = [
                    'shift_id' => $shift->id,
                    'start' => $cursor->format('H:i'),
                    'end' => $slotEnd->format('H:i'),
                    'value' => sprintf('%d|%s|%s', $shift->id, $cursor->format('H:i'), $slotEnd->format('H:i')),
                    'booked' => $booked,
                ];

                $cursor->addMinutes($duration);
                $iteration++;
            }
        }
    }

    /**
     * =========================
     * ثبت نوبت
     * =========================
     */
    public function book()
    {
        // -------------------------
        // ولیدیشن داده‌ها
        // -------------------------
        $this->validate([
            'patientName' => 'required|string|min:2',
            'patientPhone' => ['required', 'regex:/^09\d{9}$/'],
            'patientNationalId' => ['required', 'digits_between:10,12'],
            'selectedDay' => 'required',
            'selectedSlot' => 'required',
        ]);

        // -------------------------
        // بررسی فرمت اسلات انتخابی
        // -------------------------
        $parts = explode('|', $this->selectedSlot);
        if (count($parts) !== 3) {
            session()->flash('error', 'اسلات انتخابی نامعتبر است');
            return;
        }
        [$shiftId, $selStart, $selEnd] = $parts;
        $shift = DoctorShift::find($shiftId);

        if (!$shift) {
            session()->flash('error', 'ساعت انتخاب شده معتبر نیست');
            return;
        }

        $shiftStart = $this->parseTimeToCarbon($shift->start_time);
        $shiftEnd   = $this->parseTimeToCarbon($shift->end_time);
        if (!$shiftStart || !$shiftEnd) {
            session()->flash('error', 'اطلاعات شیفت ناقص است');
            return;
        }

        $selStartNorm = $this->normalizeTime($selStart);
        $selEndNorm   = $this->normalizeTime($selEnd);
        if (!$selStartNorm || !$selEndNorm) {
            session()->flash('error', 'فرمت ساعت انتخابی نامعتبر است');
            return;
        }

        // -------------------------
        // تراکنش برای جلوگیری از رزرو همزمان
        // -------------------------
        DB::transaction(function () use ($shift, $selStartNorm, $selEndNorm) {
            // ایجاد یا پیدا کردن بیمار
            $patient = Patient::firstOrCreate(
                ['national_id' => $this->patientNationalId],
                [
                    'first_name' => $this->patientName,
                    'phone' => $this->patientPhone,
                ]
            );

            // جلوگیری از رزرو چندباره توسط همین بیمار در همان روز
            $existing = Appointment::where('doctor_id', $this->doctorId)
                ->where('day', $this->selectedDay)
                ->where('patient_id', $patient->id)
                ->first();

            if ($existing) {
                session()->flash('error', 'این کد ملی قبلاً برای این روز نوبت گرفته است.');
                return;
            }

            // بررسی رزرو همزمان روی همین اسلات
            $exists = Appointment::where('doctor_id', $this->doctorId)
                ->where('day', $this->selectedDay)
                ->where('start_time', $selStartNorm)
                ->lockForUpdate()
                ->first();

            if ($exists) {
                session()->flash('error', 'این اسلات قبلاً رزرو شده است.');
                return;
            }

            // محاسبه شماره نوبت
            $shiftStart = $this->parseTimeToCarbon($shift->start_time);
            $shiftEnd   = $this->parseTimeToCarbon($shift->end_time);
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
            }

            if (!$queueNumber) {
                session()->flash('error', 'اسلات انتخابی معتبر نیست.');
                return;
            }

            // ایجاد نوبت
            Appointment::create([
                'doctor_id' => $this->doctorId,
                'patient_id' => $patient->id,
                'day' => $this->selectedDay,
                'start_time' => $selStartNorm,
                'end_time' => $selEndNorm,
                'queue_number' => $queueNumber,
                'attended' => false,
            ]);

            session()->flash('success', "نوبت شما با موفقیت ثبت شد ✅
            لطفاً در تاریخ {$this->days[$this->selectedDay]['date']}، ساعت {$selStartNorm} الی {$selEndNorm}، در مطب حضور پیدا کنید.
            شماره نوبت شما برای این روز: {$queueNumber} است.
            لطفاً حداقل ۵ دقیقه قبل از نوبت حاضر شوید و کارت ملی همراه داشته باشید.");
        });

        // ریست کردن فرم
        $this->reset(['selectedDay', 'selectedSlot', 'patientName', 'patientPhone', 'patientNationalId']);
    }

    /**
     * =========================
     * تبدیل رشته زمان به Carbon
     * =========================
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

    /**
     * =========================
     * نرمال‌سازی زمان به فرمت HH:MM
     * =========================
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
        return view('livewire.pages.book-appointment');
    }
}
