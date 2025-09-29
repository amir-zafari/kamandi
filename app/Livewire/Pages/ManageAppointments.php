<?php

namespace App\Livewire\Pages;

use App\Models\DoctorShift;
use Livewire\Component;
use App\Models\Appointment;
use Carbon\Carbon;

class ManageAppointments extends Component
{
    public $search = '';
    public $selectedDate;
    public $patientName, $patientPhone, $patientNationalId;
    public $appointments; // نگه داشتن نتایج آخرین سرچ

    public function mount()
    {
        $this->selectedDate = Carbon::today()->format('Y-m-d');
        $this->appointments = collect([]);
        $this->searchAppointments();
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

    public function markPresent($id)
    {
        $appointment = Appointment::find($id);
        if ($appointment) {
            $appointment->update(['attended' => true]);
            $this->searchAppointments(); // بروز رسانی جدول
        }
    }

    public function delete($id)
    {
        Appointment::destroy($id);
        $this->searchAppointments(); // بروز رسانی جدول
    }

    public function addAppointment()
    {
        $date = $this->selectedDate ?: Carbon::today()->format('Y-m-d');
        $dayName = Carbon::parse($date)->locale('fa')->dayName;

        $shiftExists = DoctorShift::where('day', $dayName)->exists();

        if (!$shiftExists) {
            session()->flash('error', "❌ برای روز {$dayName} هیچ شیفتی ثبت نشده است.");
            return;
        }

        $lastQueue = Appointment::whereDate('day', $date)->max('queue_number');

        Appointment::create([
            'doctor_id' => 0,
            'patient_name' => $this->patientName,
            'patient_phone' => $this->patientPhone,
            'patient_national_id' => $this->patientNationalId,
            'day' => $date,
            'start_time' => null,
            'end_time' => null,
            'queue_number' => $lastQueue ? $lastQueue + 1 : 1,
            'attended' => false,
        ]);

        $this->reset(['patientName', 'patientPhone', 'patientNationalId']);
        $this->searchAppointments();

        session()->flash('success', "✅ نوبت با موفقیت برای تاریخ {$date} ثبت شد.");
    }


    public function render()
    {
        return view('livewire.pages.manage-appointments', [
            'appointments' => $this->appointments
        ]);
    }
}
