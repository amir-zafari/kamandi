<?php

namespace App\Livewire\Pages;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\DoctorShift;
use App\Models\Appointment;
use Hekmatinasser\Verta\Verta;

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

    public function mount()
    {
        $today = Carbon::today();
        $endDate = $today->copy()->addWeeks(3);

        $days = [];

        for ($date = $today->copy(); $date <= $endDate; $date->addDay()) {
            $dayName = Verta::instance($date)->formatWord('l'); // روز هفته شمسی

            $slots = DoctorShift::where('doctor_id', $this->doctorId)
                ->where('day', $dayName)
                ->get();

            if ($slots->isEmpty()) continue;

            $days[$date->format('Y-m-d')] = [
                'label' => $dayName,
                'date' => Verta::instance($date)->format('%d %B %Y'),
                'slots' => $slots,
                'disabled' => $date->lt($today),
            ];
        }

        $this->days = $days;
    }

    /**
     * وقتی روز انتخاب شد، اسلات‌ها رو می‌سازیم — این نسخه نسبت به ورودی‌های ناقص ایمن است
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

            // مدت زمان نامعتبر
            if ($duration <= 0) continue;

            // تبدیل زمان‌ها به Carbon (با هندل کردن فرمت‌های مختلف مثل H:i یا H:i:s یا 8:00)
            $shiftStart = $this->parseTimeToCarbon($shift->start_time);
            $shiftEnd   = $this->parseTimeToCarbon($shift->end_time);

            if (!$shiftStart || !$shiftEnd) continue; // داده ناقص

            // کپی کردن برای استفاده در حلقه تا شیفت اصلی تغییر نکند
            $cursor = $shiftStart->copy();

            while ($cursor->lt($shiftEnd)) {
                $slotEnd = $cursor->copy()->addMinutes($duration);
                if ($slotEnd->gt($shiftEnd)) {
                    $slotEnd = $shiftEnd->copy();
                }

                // بررسی تداخل با اپوینت‌منت‌ها (ایمن نسبت به داده‌های ناقص اپوینت‌منت)
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
                    // مقدار value که در فرانت احتمالاً برای انتخاب ارسال می‌شود
                    'value' => sprintf('%d|%s|%s', $shift->id, $cursor->format('H:i'), $slotEnd->format('H:i')),
                    'booked' => $booked,
                ];

                $cursor->addMinutes($duration);

                // محافظت در برابر لوپ بی‌نهایت (در صورت خطای داده‌ای)
                // اگر به هر دلیلی cursor تغییری نکرده بود، شکستن
                if ($cursor->equalTo($shiftStart)) break;
            }
        }
    }

    /**
     * ثبت نوبت
     */
    public function book()
    {
        if (!$this->selectedDay || !$this->selectedSlot) {
            session()->flash('error', 'لطفا روز و ساعت را انتخاب کنید');
            return;
        }

        if (!is_string($this->patientName) || strlen(trim($this->patientName)) < 2) {
            session()->flash('error', 'نام بیمار معتبر نیست');
            return;
        }

        if (!preg_match('/^\d{10,12}$/', $this->patientNationalId)) {
            session()->flash('error', 'کد ملی معتبر نیست');
            return;
        }

        if (!preg_match('/^09\d{9}$/', $this->patientPhone)) {
            session()->flash('error', 'شماره موبایل معتبر نیست و باید با 09 شروع شود');
            return;
        }

        // selectedSlot باید فرمت: "{shiftId}|{HH:MM}|{HH:MM}" داشته باشد
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

        $duration = (int) $shift->duration;
        if ($duration <= 0) {
            session()->flash('error', 'طول هر نوبت نامعتبر است');
            return;
        }

        $shiftStart = $this->parseTimeToCarbon($shift->start_time);
        $shiftEnd   = $this->parseTimeToCarbon($shift->end_time);

        if (!$shiftStart || !$shiftEnd) {
            session()->flash('error', 'اطلاعات شیفت ناقص است');
            return;
        }

        // نرمال‌سازی مقادیر انتخاب‌شده
        $selStartNorm = $this->normalizeTime($selStart);
        $selEndNorm   = $this->normalizeTime($selEnd);

        if (!$selStartNorm || !$selEndNorm) {
            session()->flash('error', 'فرمت ساعت انتخابی نامعتبر است');
            return;
        }

        // محاسبه شماره نوبت (صف)
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

            if ($cursor->equalTo($shiftStart)) break; // محافظت اضافی
        }

        if (!$queueNumber) {
            session()->flash('error', 'اسلات انتخابی معتبر نیست.');
            return;
        }

        // بررسی اینکه کد ملی قبلاً نوبت نگرفته
        $existing = Appointment::where('doctor_id', $this->doctorId)
            ->where('day', $this->selectedDay)
            ->where('patient_national_id', $this->patientNationalId)
            ->first();

        if ($existing) {
            session()->flash('error', 'این کد ملی قبلاً برای این روز نوبت گرفته است.');
            return;
        }

        Appointment::create([
            'doctor_id' => $this->doctorId,
            'patient_name' => $this->patientName,
            'patient_phone' => $this->patientPhone,
            'patient_national_id' => $this->patientNationalId,
            'day' => $this->selectedDay,
            'start_time' => $selStartNorm,
            'end_time' => $selEndNorm,
            'queue_number' => $queueNumber,
            'attended' => false,
        ]);

        session()->flash('success', "نوبت شما با موفقیت ثبت شد ✅\nلطفاً در تاریخ {$this->days[$this->selectedDay]['date']}، ساعت {$selStartNorm} الی {$selEndNorm}، در مطب حضور پیدا کنید.\nشماره نوبت شما برای این روز: {$queueNumber} است.\nلطفاً حداقل ۵ دقیقه قبل از نوبت حاضر شوید و کارت ملی همراه داشته باشید.");

        $this->reset(['selectedDay', 'selectedSlot', 'patientName', 'patientPhone', 'patientNationalId']);
    }

    /**
     * تبدیل رشته زمان به Carbon (زمان فقط ساعت:دقیقه را در نظر می‌گیریم)
     * پشتیبانی از فرمت‌های "H:i", "H:i:s" و "H:i" بدون صفر پیش‌رو مانند "8:05"
     */
    private function parseTimeToCarbon($time)
    {
        if (empty($time)) return null;

        // تلاش برای گرفتن الگوی ساعت:دقیقه از ابتدای رشته
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
     * نرمال‌سازی رشته زمان و برگرداندن فرمت "HH:MM" یا null
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
