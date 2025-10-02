<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    // جدول مرتبط
    protected $table = 'patients';

    // فیلدهایی که قابل پر شدن هستند
    protected $fillable = [
        'first_name',
        'last_name',
        'national_id',
        'phone',
        'birth_date',
        'gender',
        'address',
        'notes',
    ];

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

}
