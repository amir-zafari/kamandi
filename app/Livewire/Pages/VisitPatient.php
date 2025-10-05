<?php

namespace App\Livewire\Pages;

use Livewire\Component;
use App\Models\Appointment;
use Carbon\Carbon;

class VisitPatient extends Component
{
    public $appointments;   // لیست نوبت‌های امروز
    public $currentAppointment; // نوبت فعلی (اول لیست)

    public function mount()
    {
        // تاریخ امروز
        $today = Carbon::today()->toDateString();

        // گرفتن نوبت‌های امروز به همراه اطلاعات بیمار
        $this->appointments = Appointment::with('patient')
            ->whereDate('day', $today)
            ->orderBy('queue_number', 'asc')
            ->get();

        // اولین نوبت (کسی که الان باید بیاد)
        $this->currentAppointment = $this->appointments->first();
    }

    public function render()
    {
        return view('livewire.pages.visit-patient', [
            'appointments' => $this->appointments,
            'currentAppointment' => $this->currentAppointment,
        ]);
    }
}
