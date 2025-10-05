<div class="p-6">
    <h2 class="text-xl font-bold mb-4">پرونده بیمار</h2>

    <div class="bg-white border rounded-lg p-4 shadow mb-6">
        <h3 class="font-semibold text-lg mb-2">اطلاعات بیمار</h3>
        <p>نام: {{ $patient->first_name }} {{ $patient->last_name }}</p>
        <p>کد ملی: {{ $patient->national_id }}</p>
        <p>شماره تماس: {{ $patient->phone }}</p>
        <p>تاریخ تولد: {{ $patient->birth_date }}</p>
        <p>جنسیت: {{ $patient->gender }}</p>
        <p>آدرس: {{ $patient->address }}</p>
    </div>

    <div class="bg-white border rounded-lg p-4 shadow">
        <h3 class="font-semibold text-lg mb-2">پرونده‌های پزشکی</h3>
        @forelse($patient->medicalRecords as $record)
            <div class="border-b py-2">
                <p><strong>تشخیص:</strong> {{ $record->diagnosis ?? '-' }}</p>
                <p><strong>خلاصه:</strong> {{ $record->summary ?? '-' }}</p>
                <p><strong>یادداشت‌ها:</strong> {{ $record->notes ?? '-' }}</p>
            </div>
        @empty
            <p class="text-gray-500">پرونده‌ای ثبت نشده است.</p>
        @endforelse
    </div>
</div>
0
