<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'doctor_id',
        'patient_id',
        'day',
        'start_time',
        'end_time',
        'queue_number',
        'attended'
    ];

    // هر نوبت متعلق به یک بیمار است
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    // هر نوبت می‌تواند چند پرونده پزشکی داشته باشد
    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class, 'appointment_id');
    }
}
