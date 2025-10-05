<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    protected $fillable = ['medical_record_id', 'medicine_name', 'dose','duration','notes'];

    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class);
    }
}
