<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorShift extends Model
{
    protected $fillable = [
        'doctor_id',
        'day',
        'start_time',
        'end_time',
        'duration',
    ];
}
