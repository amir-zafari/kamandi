<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    protected $fillable = [
        'patient_id', 'doctor_id',
        'summary', 'diagnosis', 'chemotherapy_plan', 'visit_notes'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id'); // فرض کردیم دکترها همون User باشن
    }

    public function imagingFiles()
    {
        return $this->hasMany(ImagingFile::class);
    }

    public function pathologyFiles()
    {
        return $this->hasMany(PathologyFile::class);
    }

    public function surgeryFiles()
    {
        return $this->hasMany(SurgeryFile::class);
    }

    public function labFiles()
    {
        return $this->hasMany(LabFile::class);
    }

    public function endoscopyFiles()
    {
        return $this->hasMany(EndoscopyFile::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }
}
