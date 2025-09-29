{{-- سایدبار عمودی --}}
<aside class="w-2/12 bg-gray-800 text-gray-100 flex-shrink-0 hidden md:flex flex-col z-10">
    <div class="shrink-0 flex items-center">
        <a href="{{ route('dashboard') }}" wire:navigate>
            <x-application-logo  />
        </a>
    </div>
    <nav class="mt-6 flex-1">
        <a href="{{ route('dashboard') }}" class="block px-6 py-2 hover:bg-gray-700 {{ request()->routeIs('dashboard') ? 'bg-gray-700' : '' }}">
            داشبورد
        </a>

        <a href="{{ route('dashboard.DoctorShift') }}" class="block px-6 py-2 hover:bg-gray-700 {{ request()->routeIs('dashboard.DoctorShift') ? 'bg-gray-700' : '' }}">شیت حضور دکتر</a>
        <a href="{{ route('dashboard.manage-appointments') }}" class="block px-6 py-2 hover:bg-gray-700 {{ request()->routeIs('dashboard.manage-appointments') ? 'bg-gray-700' : '' }}">مدیریت نوبت‌ها</a>
        <a href="{{ route('dashboard.today-appointments') }}" class="block px-6 py-2 hover:bg-gray-700 {{ request()->routeIs('dashboard.today-appointments') ? 'bg-gray-700' : '' }}">صف نوبت</a>
    </nav>
</aside>
