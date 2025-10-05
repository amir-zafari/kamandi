<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PathologyFile extends Model
{
    protected $fillable = ['medical_record_id', 'file_path', 'notes'];

    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class);
    }
}
