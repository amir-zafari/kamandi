<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabFile extends Model
{
    protected $fillable = ['medical_record_id', 'file_path', 'test_type'];

    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class);
    }
}
