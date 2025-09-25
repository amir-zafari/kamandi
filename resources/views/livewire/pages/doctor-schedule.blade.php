<div class="p-6 space-y-4">
    <div class="flex justify-between mb-4">
        <h2 class="text-xl font-bold">حضور پزشک در روزهای هفته</h2>
        <button wire:click="save"
                class="bg-blue-600 text-white px-4 py-2 rounded">
            ذخیره تغییرات
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach($days as $day => $data)
            <div class="border rounded-lg p-4 bg-white shadow"
                 x-data="{ open: @entangle('days.'.$day.'.active') }">

                <!-- تیک روز -->
                <label class="flex items-center mb-2 gap-2 cursor-pointer">
                    <input type="checkbox" wire:model="days.{{ $day }}.active" class="mr-2">
                    <span class="font-bold">{{ $day }}</span>
                </label>

                <!-- بخش slotها -->
                <div x-show="open" x-transition class="space-y-2">
                    @foreach($data['slots'] as $index => $slot)
                        <div class="border rounded p-2 mb-2 space-y-2">
                            <div class="flex gap-2">
                                <input type="time" wire:model="days.{{ $day }}.slots.{{ $index }}.start"
                                       class="border rounded px-2 py-1 flex-1">
                                <input type="time" wire:model="days.{{ $day }}.slots.{{ $index }}.end"
                                       class="border rounded px-2 py-1 flex-1">
                            </div>

                            <div>
                                <label class="text-sm">مدت ویزیت</label>
                                <select wire:model="days.{{ $day }}.slots.{{ $index }}.duration"
                                        class="border rounded px-2 py-1 w-full">
                                    @foreach($durations as $d)
                                        <option value="{{ $d }}">{{ $d }} دقیقه</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex gap-2 mt-1">
                                <button type="button"
                                        wire:click="removeSlot('{{ $day }}', {{ $index }})"
                                        class="text-red-600 text-sm">
                                    🗑 حذف
                                </button>

                                <button type="button"
                                        wire:click="addSlot('{{ $day }}')"
                                        class="bg-green-500 text-white text-sm px-3 py-1 rounded">
                                    + ساعت
                                </button>
                            </div>

                            {{-- محاسبه تعداد نوبت‌ها --}}
                            @php
                                $appointmentCount = $this->countAppointments(
                                    $slot['start'] ?? null,
                                    $slot['end'] ?? null,
                                    $slot['duration'] ?? null
                                );
                            @endphp

                            @if($appointmentCount > 0)
                                <div class="text-sm text-gray-700 bg-blue-50 border border-blue-200 rounded-lg px-3 py-1 mt-2">
                                    📊 تعداد نوبت‌ها: <span class="font-bold">{{ $appointmentCount }}</span>
                                </div>
                            @endif
                        </div>
                    @endforeach

                    <!-- اگر روز فعال شد ولی هیچ slot ندارد، یک slot اولیه اضافه کن -->
                    @if(count($data['slots']) === 0)
                        <button type="button"
                                wire:click="addSlot('{{ $day }}')"
                                class="bg-green-500 text-white text-sm px-3 py-1 rounded mb-2">
                            + ساعت
                        </button>
                    @endif

                </div>
            </div>
        @endforeach
    </div>

    <!-- پیام ذخیره موفق -->
    @if (session()->has('message'))
        <div class="mt-4 text-green-600">{{ session('message') }}</div>
    @endif
</div>
