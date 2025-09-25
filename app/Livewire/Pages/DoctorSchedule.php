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

        $weekDays = ['شنبه','یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنجشنبه','جمعه'];

        // مقداردهی اولیه
        foreach ($weekDays as $day) {
            $this->days[$day] = [
                'active' => false,
                'slots' => []
            ];
        }

        // بارگذاری شیفت‌های موجود از دیتابیس
        $shifts = DoctorShift::where('doctor_id', $doctorId)->get();

        foreach ($shifts as $shift) {
            $this->days[$shift->day]['active'] = true;
            $this->days[$shift->day]['slots'][] = [
                'id' => $shift->id, // مهم برای آپدیت رکورد موجود
                'start' => $shift->start_time,
                'end' => $shift->end_time,
                'duration' => $shift->duration,
                'in_person' => true,
                'online' => false,
            ];
        }

        // اگر روزی هیچ slotی نداشت، یک slot خالی اضافه نشود مگر کاربر + ساعت بزند
    }

    public function addSlot($day)
    {
        $this->days[$day]['slots'][] = [
            'id' => null, // برای slot جدید
            'start' => '',
            'end' => '',
            'duration' => 15,
        ];
    }

    public function removeSlot($day, $index)
    {
        $slot = $this->days[$day]['slots'][$index];

        // اگر slot موجود در دیتابیس بود، آن را حذف کن
        if (!empty($slot['id'])) {
            DoctorShift::where('id', $slot['id'])->delete();
        }

        unset($this->days[$day]['slots'][$index]);
        $this->days[$day]['slots'] = array_values($this->days[$day]['slots']);
    }

    public function save()
    {
        foreach ($this->days as $day => $data) {
            if ($data['active']) {
                foreach ($data['slots'] as $slot) {
                    // فقط اگر start و end خالی نبودند
                    if ($slot['start'] && $slot['end']) {
                        if (!empty($slot['id'])) {
                            // آپدیت رکورد موجود
                            DoctorShift::where('id', $slot['id'])->update([
                                'start_time' => $slot['start'],
                                'end_time' => $slot['end'],
                                'duration' => $slot['duration'],
                            ]);
                        } else {
                            // ایجاد رکورد جدید فقط برای slotهای جدید (+ ساعت)
                            $newShift = DoctorShift::create([
                                'doctor_id' => $this->doctorId,
                                'day' => $day,
                                'start_time' => $slot['start'],
                                'end_time' => $slot['end'],
                                'duration' => $slot['duration'],
                            ]);
                            $slot['id'] = $newShift->id; // id جدید به آرایه اضافه شود
                        }
                    }
                }
            } else {
                // اگر روز غیر فعال شد، همه شیفت‌های آن روز پاک شوند
                DoctorShift::where('doctor_id', $this->doctorId)->where('day', $day)->delete();
            }
        }

        session()->flash('message', 'برنامه کاری ذخیره شد ✅');
    }

    public function render()
    {
        return view('livewire.pages.doctor-schedule');
    }
}
