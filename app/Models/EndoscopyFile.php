<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EndoscopyFile extends Model
{
    protected $fillable = ['medical_record_id', 'file_path', 'type'];

    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class);
    }
}
