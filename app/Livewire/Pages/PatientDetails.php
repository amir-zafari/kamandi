<?php

namespace App\Livewire\Pages;

use Livewire\Component;
use App\Models\Patient;

class PatientDetails extends Component
{
    public $patient;

    public function mount(Patient $patient)
    {


        $this->medicalRecords = $patient->medicalRecords;

    }

    public function render()
    {
        return view('livewire.pages.patient-details', [
            'patient' => $this->patient,
        ]);
    }
}
