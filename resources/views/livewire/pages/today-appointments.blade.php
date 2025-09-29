<div class="p-6 bg-gradient-to-br from-blue-100 to-green-100 min-h-screen text-gray-800">
    <div class="text-center mb-12">
        <h1 class="text-6xl font-extrabold text-blue-700">{{ $doctorName }}</h1>
        <p id="live-clock" class="text-2xl text-gray-600 mt-2"></p>
    </div>

    <script>
        function updateClock() {
            const now = new Date();
            const options = {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            };
            document.getElementById('live-clock').textContent = now.toLocaleString('fa-IR', options);
        }

        // آپدیت ساعت هر ثانیه
        setInterval(updateClock, 1000);

        // اجرای اولیه برای نمایش بدون تأخیر
        updateClock();
    </script>


    <div class="grid grid-cols-3 gap-8">
        <!-- لیست نوبت‌ها -->
        <div class="col-span-1 bg-white p-6 rounded-2xl shadow-lg flex flex-col" style="height: 600px;">
            <h2 class="font-bold text-2xl mb-6 text-center text-blue-600">لیست انتظار</h2>
            <div class="overflow-y-auto flex-1">
                @forelse($appointments as $appointment)
                    <div class="border-b py-4 flex justify-between items-center text-lg {{ $appointment->id === optional($currentAppointment)->id ? 'bg-yellow-300 font-extrabold rounded px-2' : '' }}">
                        <span class="text-blue-700">#{{ $appointment->queue_number }}</span>
                        <span>{{ $appointment->patient_name }}</span>
                    </div>
                @empty
                    <p class="text-center text-gray-400">نوبتی وجود ندارد</p>
                @endforelse
            </div>
        </div>

        <!-- نوبت جاری -->
        <div class="col-span-2 bg-white p-10 rounded-2xl shadow-xl flex flex-col justify-center items-center" style="height: 600px;">
            <h2 class="text-3xl font-bold mb-6 text-red-600">در حال پذیرش</h2>
            @if($currentAppointment)
                <p class="text-7xl font-extrabold text-blue-800">{{ $currentAppointment->patient_name }}</p>
                <p class="text-3xl mt-4 text-gray-700">نوبت شماره #{{ $currentAppointment->queue_number }}</p>
            @else
                <p class="text-3xl text-gray-400">نوبتی وجود ندارد</p>
            @endif
        </div>
    </div>

</div>
