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
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
