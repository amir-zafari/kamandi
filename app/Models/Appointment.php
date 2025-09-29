<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'doctor_id',
        'patient_name',
        'patient_phone',
        'patient_national_id',
        'day',
        'start_time',
        'end_time',
        'queue_number',
        'attended'
    ];
}
