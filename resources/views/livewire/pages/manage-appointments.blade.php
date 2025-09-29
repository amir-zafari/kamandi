<div class="p-6">
    <h2 class="text-xl font-bold mb-4">مدیریت نوبت‌ها</h2>

    <div class="flex items-center space-x-4 mb-4">
        <input type="date" wire:model="selectedDate" class="border rounded p-2">
        <input type="text" wire:model="search" class="border rounded p-2 flex-1" placeholder="جستجو بر اساس نام یا کد ملی...">
        <button wire:click="searchAppointments" class="bg-blue-600 text-white px-4 py-2 rounded">
            جستجو
        </button>
    </div>

    <table class="min-w-full bg-white border rounded-lg shadow">
        <thead class="bg-gray-100">
        <tr>
            <th class="px-4 py-2">#</th>
            <th class="px-4 py-2">نام بیمار</th>
            <th class="px-4 py-2">کد ملی</th>
            <th class="px-4 py-2">شماره تماس</th>
            <th class="px-4 py-2">وضعیت حضور</th>
            <th class="px-4 py-2">عملیات</th>
        </tr>
        </thead>
        <tbody>
        @forelse($appointments as $appointment)
            <tr class="border-t">
                <td class="px-4 py-2">{{ $appointment->queue_number }}</td>
                <td class="px-4 py-2">{{ $appointment->patient_name }}</td>
                <td class="px-4 py-2">{{ $appointment->patient_national_id }}</td>
                <td class="px-4 py-2">{{ $appointment->patient_phone }}</td>
                <td class="px-4 py-2">
                    @if($appointment->attended)
                        <span class="text-green-600">حاضر</span>
                    @else
                        <span class="text-red-600">غایب</span>
                    @endif
                </td>
                <td class="px-4 py-2 space-x-2">
                    <button wire:click="markPresent({{ $appointment->id }})" class="bg-green-500 text-white px-2 py-1 rounded">ثبت حضور</button>
                    <button wire:click="delete({{ $appointment->id }})" class="bg-red-500 text-white px-2 py-1 rounded">حذف</button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-2 text-center text-gray-500">
                    برای این تاریخ نوبتی ثبت نشده
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="mt-6 border-t pt-4">
        <h3 class="text-lg font-semibold mb-2">افزودن نوبت دستی</h3>
        <div class="grid grid-cols-3 gap-4">
            <input type="text" wire:model="patientName" class="border p-2 rounded" placeholder="نام بیمار">
            <input type="text" wire:model="patientNationalId" class="border p-2 rounded" placeholder="کد ملی">
            <input type="text" wire:model="patientPhone" class="border p-2 rounded" placeholder="شماره تماس">
        </div>
        <button wire:click="addAppointment" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded">
            افزودن نوبت
        </button>
    </div>
    {{-- پیام موفقیت / خطا --}}
    @if(session()->has('success'))
        <div class="mt-4 text-green-600 text-center">{{ session('success') }}</div>
    @endif
    @if(session()->has('error'))
        <div class="mt-4 text-red-600 text-center">{{ session('error') }}</div>
    @endif
</div>
