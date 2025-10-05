<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImagingFile extends Model
{
    protected $fillable = ['medical_record_id', 'file_path', 'highlight'];

    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class);
    }
}

