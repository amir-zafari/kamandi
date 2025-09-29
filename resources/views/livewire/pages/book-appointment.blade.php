<div class="flex flex-col items-center justify-center min-h-screen bg-gray-100 py-6">
    <div class="bg-white shadow-lg rounded-xl p-6 w-full max-w-xl">
        <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">رزرو نوبت</h2>

        {{-- اطلاعات بیمار --}}
        <div class="mb-6 space-y-3">
            <input type="text" wire:model="patientName" placeholder="نام بیمار"
                   class="border border-gray-300 rounded-lg px-3 py-2 w-full" required>
            <input type="text" wire:model="patientPhone" placeholder="شماره تلفن"
                   class="border border-gray-300 rounded-lg px-3 py-2 w-full" required>
            <input type="text" wire:model="patientNationalId" placeholder="کد ملی"
                   class="border border-gray-300 rounded-lg px-3 py-2 w-full" required>
        </div>

        {{-- انتخاب روز --}}
        <div class="mb-6">
            <h3 class="text-gray-700 font-medium mb-3 text-center">انتخاب روز</h3>
            <div class="flex overflow-x-auto px-2 py-1 scrollbar-hide gap-4">
                @foreach($days as $date => $data)
                    <button wire:click="$set('selectedDay', '{{ $date }}')"
                            @disabled($data['disabled'])
                            class="flex-shrink-0 flex flex-col items-center border rounded-lg px-4 py-2
                        {{ $selectedDay == $date ? 'bg-blue-600 text-white border-blue-600' : ($data['disabled'] ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-gray-100 text-gray-700 hover:bg-gray-200') }}">
                        <span class="font-semibold">{{ $data['label']}}</span>
                        <span class="text-sm">{{ $data['date'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- انتخاب ساعت --}}
        @if($selectedDay)
            @if(count($slots))
                <div class="mb-6">
                    <h3 class="text-gray-700 font-medium mb-3 text-center">انتخاب ساعت</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach($slots as $s)
                            <button
                                wire:click="$set('selectedSlot', '{{ $s['shift_id'] }}|{{ $s['start'] }}|{{ $s['end'] }}')"
                                @if($s['booked']) disabled @endif
                                class="border rounded-lg px-3 py-2 text-center
                        {{ $s['booked'] ? 'bg-gray-400 text-white cursor-not-allowed' : ($selectedSlot == ($s['shift_id'].'|'.$s['start'].'|'.$s['end']) ? 'bg-green-600 text-white border-green-600' : 'bg-gray-100 text-gray-700 hover:bg-gray-200') }}">
                                {{ $s['start'] }} - {{ $s['end'] }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="text-center text-gray-500 mb-6">شیفتی برای این روز وجود ندارد</div>
            @endif
        @endif

        {{-- دکمه ثبت --}}
        <div class="text-center">
            <button wire:click="book"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-lg shadow">
                ثبت نوبت
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
</div>
