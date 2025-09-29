<?php
namespace App\Livewire\Pages;

use Livewire\Component;
use App\Models\Appointment;
use Carbon\Carbon;

class TodayAppointments extends Component
{
    public $appointments;
    public $currentAppointment;
    public $doctorName = 'دکتر کمندی';

    public function mount()
    {
        $this->loadAppointments();
    }

    public function loadAppointments()
    {
        $today = Carbon::today()->format('Y-m-d');

        $this->appointments = Appointment::whereDate('day', $today)
            ->where('attended', true)
            ->orderBy('queue_number')
            ->get();

        $this->currentAppointment = $this->appointments->first();
    }

    public function render()
    {
        return view('livewire.pages.today-appointments', [
            'appointments' => $this->appointments,
            'currentAppointment' => $this->currentAppointment,
            'doctorName' => $this->doctorName,
        ]);
    }
}
