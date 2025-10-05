<div class="p-6">
    {{-- عنوان صفحه --}}
    <h2 class="text-xl font-bold mb-4">مدیریت نوبت‌ها</h2>

    {{-- بخش فیلتر و جستجو --}}
    <div class="flex flex-col sm:flex-row items-center gap-4 mb-6">
        <input type="date" wire:model="selectedDate"
               class="border rounded p-2"
               wire:loading.attr="disabled">
        <input type="text" wire:model="search"
               class="border rounded p-2 flex-1"
               placeholder="جستجو بر اساس نام یا کد ملی..."
               wire:loading.attr="disabled">
        <button wire:click="searchAppointments"
                class="bg-blue-600 text-white px-4 py-2 rounded"
                wire:loading.attr="disabled">
            جستجو
        </button>
    </div>

    {{-- جدول نوبت‌ها --}}
    <div class="overflow-x-auto mb-6">
        <table class="min-w-full bg-white border rounded-lg shadow">
            <thead class="bg-gray-100">
            <tr>
                <th class="px-4 py-2 text-right">#</th>
                <th class="px-4 py-2 text-right">نام بیمار</th>
                <th class="px-4 py-2 text-right">کد ملی</th>
                <th class="px-4 py-2 text-right">شماره تماس</th>
                <th class="px-4 py-2 text-right">وضعیت حضور</th>
                <th class="px-4 py-2 text-right">عملیات</th>
            </tr>
            </thead>
            <tbody>
            @forelse($appointments as $appointment)
                <tr class="border-t">
                    <td class="px-4 py-2">{{ $appointment->queue_number }}</td>
                    <td class="px-4 py-2">{{ $appointment->patient->first_name }}</td>
                    <td class="px-4 py-2">{{ $appointment->patient->national_id }}</td>
                    <td class="px-4 py-2">{{ $appointment->patient->phone }}</td>
                    <td class="px-4 py-2">
                        @if($appointment->attended)
                            <span class="text-green-600">حاضر</span>
                        @else
                            <span class="text-red-600">غایب</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 space-x-2">
                        <button wire:click="markPresent({{ $appointment->id }})"
                                class="bg-green-500 text-white px-2 py-1 rounded"
                                wire:loading.attr="disabled">
                            ثبت حضور
                        </button>
                        <button wire:click="delete({{ $appointment->id }})"
                                class="bg-red-500 text-white px-2 py-1 rounded"
                                wire:loading.attr="disabled">
                            حذف
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-2 text-center text-gray-500">برای این تاریخ نوبتی ثبت نشده</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- شمارش اسلات‌ها با collect --}}
    @php
        $slotsCollection = collect($slots ?? []);
        $total = $slotsCollection->count();
        $free = $slotsCollection->where('booked', false)->where('expired', false)->count();
        $bookedCount = $slotsCollection->where('booked', true)->count();
        $expiredCount = $slotsCollection->where('expired', true)->count();
        $hasFreeFutureSlot = $free > 0;
    @endphp

    {{-- کارت‌های اسلات و ثبت نوبت --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- کارت اسلات‌ها --}}
        <div class="bg-white border rounded-xl shadow p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold text-gray-800">اسلات‌های {{ $selectedDate }}</h3>
                <div class="text-sm text-gray-600">
                    کل: {{ $total }} · آزاد: {{ $free }} · رزرو: {{ $bookedCount }} · گذشته: {{ $expiredCount }}
                </div>
            </div>

            {{-- لید ـ راهنما --}}
            <div class="flex items-center gap-3 text-xs mb-4 flex-wrap">
                <div class="flex items-center gap-2"><span class="w-4 h-4 rounded border bg-white"></span> قابل رزرو</div>
                <div class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-green-600"></span> انتخاب‌شده</div>
                <div class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-gray-400"></span> رزرو شده</div>
                <div class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-gray-900"></span> گذشته (غیرفعال)</div>
            </div>

            {{-- نمایش اسلات‌ها --}}
            @if($total)
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @foreach($slotsCollection as $s)
                        @php
                            $isDisabled = $s['booked'] || $s['expired'];
                            $bgClass = $s['expired'] ? 'bg-gray-900 text-white' :
                                      ($s['booked'] ? 'bg-gray-400 text-white' :
                                      ($selectedSlot == $s['value'] ? 'bg-green-600 text-white' : 'bg-white text-gray-800 hover:bg-gray-50'));
                            $cursorClass = $isDisabled ? 'cursor-not-allowed opacity-80' : 'cursor-pointer';
                        @endphp
                        <button wire:click="selectSlot('{{ $s['value'] }}')"
                                :key="$s['value']"
                                @if($isDisabled) disabled @endif
                                class="border rounded-lg px-3 py-3 text-center text-sm {{ $bgClass }} {{ $cursorClass }}"
                                wire:loading.attr="disabled">
                            <div class="font-medium">{{ $s['start'] }} - {{ $s['end'] }}</div>
                            <div class="text-xs mt-1">
                                @if($s['booked'])
                                    رزرو شده
                                @elseif($s['expired'])
                                    گذشته
                                @else
                                    قابل رزرو
                                @endif
                            </div>
                        </button>
                    @endforeach
                </div>
            @else
                <div class="text-gray-500">هیچ شیفتی برای این روز ثبت نشده است.</div>
            @endif
        </div>

        {{-- کارت ثبت نوبت --}}
        <div class="bg-white border rounded-xl shadow p-5">
            <h3 class="text-lg font-semibold mb-3">ثبت نوبت جدید</h3>

            <div class="text-sm text-gray-600 mb-3">
                <p>برای ثبت از اسلات‌ها استفاده کنید. اگر تمام اسلات‌ها رزرو یا گذشته باشند، می‌توانید <strong>نوبت دستی</strong> اضافه کنید.</p>
            </div>

            {{-- فرم اطلاعات بیمار --}}
            <div class="grid grid-cols-1 gap-3 mb-4">
                <input type="text" wire:model="patientName" placeholder="نام بیمار" class="border rounded p-2">
                @error('patientName') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror

                <input type="text" wire:model="patientNationalId" placeholder="کد ملی" class="border rounded p-2">
                @error('patientNationalId') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror

                <input type="text" wire:model="patientPhone" placeholder="شماره تماس" class="border rounded p-2">
                @error('patientPhone') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>

            {{-- خلاصه اسلات انتخاب‌شده --}}
            @if($selectedSlot)
                @php [$sid,$sst,$end] = explode('|', $selectedSlot); @endphp
                <div class="mb-4 p-3 bg-gray-50 border rounded text-sm">
                    <div>اسلات انتخاب‌شده: <strong>{{ $sst }} - {{ $end }}</strong></div>
                </div>
            @endif

            {{-- دکمه‌های ثبت --}}
            <div class="flex flex-col sm:flex-row gap-3">
                <button wire:click="addAppointmentFromSlot"
                        class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold px-4 py-2 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                        @if(!$selectedSlot) disabled @endif
                        wire:loading.attr="disabled">
                    ثبت نوبت از اسلات انتخاب‌شده
                </button>

                <button wire:click="addAppointment"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                        @if($hasFreeFutureSlot) disabled title="هنوز اسلات‌های آزاد و آینده وجود دارد — ابتدا از اسلات‌ها استفاده کنید" @endif
                        wire:loading.attr="disabled">
                    افزودن نوبت (دستی)
                </button>
            </div>

            @if($hasFreeFutureSlot)
                <div class="mt-3 text-sm text-orange-600">
                    توجه: هنوز اسلات آزاد و آینده برای این روز وجود دارد. لطفاً ابتدا از لیست اسلات‌ها نوبت ثبت کنید.
                </div>
            @endif

            {{-- پیام موفقیت / خطا --}}
            @if(session()->has('success'))
                <div class="mt-4 text-green-600 text-center">{{ session('success') }}</div>
            @endif
            @if(session()->has('error'))
                <div class="mt-4 text-red-600 text-center">{{ session('error') }}</div>
            @endif
        </div>

    </div>

</div>
