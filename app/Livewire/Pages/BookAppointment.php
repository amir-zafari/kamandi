<?php

namespace App\Livewire\Pages;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\DoctorShift;
use App\Models\Appointment;
use Hekmatinasser\Verta\Verta;

class BookAppointment extends Component
{
    public $doctorId;
    public $selectedDay;
    public $selectedSlot;
    public $patientName;
    public $patientPhone;
    public $patientNationalId;
    public $days = [];
    public $slots = [];

    public function mount($doctorId)
    {
        $this->doctorId = $doctorId;

        $today = Carbon::today();
        $endDate = $today->copy()->addWeeks(3);

        $days = [];

        for ($date = $today->copy(); $date <= $endDate; $date->addDay()) {
            $dayName = Verta::instance($date)->formatWord('l'); // روز هفته شمسی

            // گرفتن شیفت‌های این روز
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

    public function updatedSelectedDay($day)
    {
        $this->selectedSlot = null;
        $this->slots = [];

        if (!isset($this->days[$day])) return;

        // دریافت نوبت‌های رزرو شده برای این روز
        $appointments = Appointment::where('doctor_id', $this->doctorId)
            ->where('day', $day)
            ->get();

        foreach ($this->days[$day]['slots'] as $shift) {
            $start = substr($shift->start_time, 0, 5);
            $end   = substr($shift->end_time, 0, 5);
            $duration = (int) $shift->duration;

            $startTime = Carbon::createFromFormat('H:i', $start);
            $endTime   = Carbon::createFromFormat('H:i', $end);

            while ($startTime->lt($endTime)) {
                $slotEnd = $startTime->copy()->addMinutes($duration);
                if ($slotEnd->gt($endTime)) $slotEnd = $endTime->copy();

                // بررسی تداخل با نوبت‌های رزرو شده
                $booked = false;
                foreach ($appointments as $a) {
                    $aStart = Carbon::createFromFormat('H:i', substr($a->start_time, 0, 5));
                    $aEnd   = Carbon::createFromFormat('H:i', substr($a->end_time, 0, 5));

                    if ($startTime < $aEnd && $slotEnd > $aStart) {
                        $booked = true;
                        break;
                    }
                }

                $this->slots[] = [
                    'shift_id' => $shift->id,
                    'start' => $startTime->format('H:i'),
                    'end' => $slotEnd->format('H:i'),
                    'booked' => $booked,
                ];

                $startTime->addMinutes($duration);
            }
        }
    }






    public function book()
    {
        if (!$this->selectedDay || !$this->selectedSlot) {
            session()->flash('error', 'لطفا روز و ساعت را انتخاب کنید');
            return;
        }

        // اعتبارسنجی
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

        [$shiftId, $startTime, $endTime] = explode('|', $this->selectedSlot);
        $shift = DoctorShift::find($shiftId);
dd($shift);
        if (!$shift) {
            session()->flash('error', 'ساعت انتخاب شده معتبر نیست');
            return;
        }

        if ($shift->day !== Verta::instance($this->selectedDay)->formatWord('l')) {
            session()->flash('error', 'روز انتخاب شده با شیفت مطابقت ندارد');
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
            'start_time' => $startTime,
            'end_time' => $endTime,
            'queue_number' => $queueNumber,
            'attended' => false,
        ]);

        session()->flash('success', "نوبت شما با موفقیت ثبت شد ✅
لطفاً در تاریخ {$this->days[$this->selectedDay]['date']}، ساعت {$startTime} الی {$endTime}، در مطب حضور پیدا کنید.
شماره نوبت شما برای این روز: {$queueNumber} است.
لطفاً حداقل ۵ دقیقه قبل از نوبت حاضر شوید و کارت ملی همراه داشته باشید.");

        $this->reset(['selectedDay', 'selectedSlot', 'patientName', 'patientPhone', 'patientNationalId']);
    }


    public function render()
    {
        return view('livewire.pages.book-appointment');
    }
}
