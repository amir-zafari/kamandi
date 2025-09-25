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
            <div class="border rounded-lg p-4 bg-white shadow">
                <label class="flex items-center mb-2">
                    <input type="checkbox" wire:model="days.{{ $day }}.active" class="mr-2">
                    <span class="font-bold">{{ $day }}</span>
                </label>

                @if($data['active'])
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


                            <button type="button"
                                    wire:click="removeSlot('{{ $day }}', {{ $index }})"
                                    class="text-red-600 text-sm">🗑 حذف
                            </button>
                        </div>
                    @endforeach

                    <button type="button"
                            wire:click="addSlot('{{ $day }}')"
                            class="bg-green-500 text-white text-sm px-3 py-1 rounded">
                        + ساعت
                    </button>
                @endif
            </div>
        @endforeach
    </div>

    @if (session()->has('message'))
        <div class="mt-4 text-green-600">{{ session('message') }}</div>
    @endif
</div>
