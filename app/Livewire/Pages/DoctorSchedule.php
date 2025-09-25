<?php

namespace App\Livewire\Pages;

use App\Models\DoctorShift;
use Livewire\Component;

class DoctorSchedule extends Component
{
    public $doctorId;
    public $days = [];
    public $durations = [10, 15, 20, 30];

    public function mount($doctorId)
    {
        $this->doctorId = $doctorId;

        // مقداردهی اولیه برای هر روز
        $weekDays = ['شنبه','یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنجشنبه','جمعه'];
        foreach ($weekDays as $day) {
            $this->days[$day] = [
                'active' => false,
                'slots' => [
                    ['start' => '', 'end' => '', 'duration' => 15, 'in_person' => true, 'online' => false]
                ]
            ];
        }
    }

    public function addSlot($day)
    {
        $this->days[$day]['slots'][] = ['start' => '', 'end' => '', 'duration' => 15];
    }

    public function removeSlot($day, $index)
    {
        unset($this->days[$day]['slots'][$index]);
        $this->days[$day]['slots'] = array_values($this->days[$day]['slots']);
    }

    public function save()
    {
        foreach ($this->days as $day => $data) {
            if ($data['active']) {
                foreach ($data['slots'] as $slot) {
                    DoctorShift::updateOrCreate([
                        'doctor_id' => $this->doctorId,
                        'day' => $day,
                        'start_time' => $slot['start'],
                        'end_time' => $slot['end'],
                    ], [
                        'duration' => $slot['duration'],
                    ]);
                }
            }
        }

        session()->flash('message', 'برنامه کاری ذخیره شد ✅');
    }
    public function render()
    {
        return view('livewire.pages.doctor-schedule');
    }
}
