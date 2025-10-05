<div class="p-6">
    <h2 class="text-xl font-bold mb-4">لیست نوبت‌های امروز</h2>


    {{-- جدول نوبت‌های امروز --}}
    <table class="min-w-full bg-white border rounded-lg shadow">
        <thead class="bg-gray-100">
        <tr>
            <th class="px-4 py-2">شماره نوبت</th>
            <th class="px-4 py-2">نام بیمار</th>
            <th class="px-4 py-2">کد ملی</th>
            <th class="px-4 py-2">شماره تماس</th>
            <th class="px-4 py-2">عملیات</th>
        </tr>
        </thead>
        <tbody>
        @foreach($appointments as $a)
            <tr class="{{ $a->id === $currentAppointment?->id ? 'bg-green-50' : '' }}">
                <td class="px-4 py-2">{{ $a->queue_number }}</td>
                <td class="px-4 py-2">{{ $a->patient->first_name }} {{ $a->patient->last_name }}</td>
                <td class="px-4 py-2">{{ $a->patient->national_id }}</td>
                <td class="px-4 py-2">{{ $a->patient->phone }}</td>
                <td class="px-4 py-2">
                    <a href="{{ route('patient.details', $a->patient_id) }}"
                       class="bg-blue-600 text-white px-3 py-1 rounded">
                        ویزیت بیمار
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
