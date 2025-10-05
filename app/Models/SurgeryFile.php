<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurgeryFile extends Model
{
    protected $fillable = ['medical_record_id', 'file_path', 'description'];

    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class);
    }
}
