{{-- Mobile overlay --}}
<div x-show="sidebarOpen" @click="sidebarOpen = false"
    class="fixed inset-0 z-20 bg-gray-900/50 lg:hidden" x-cloak></div>

{{-- Sidebar --}}
<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    class="fixed inset-y-0 left-0 z-30 flex w-64 flex-col bg-white shadow-lg transition-transform duration-300 dark:bg-gray-800 lg:static lg:z-auto lg:shadow-none"
>
    {{-- Logo + close button --}}
    <div class="flex h-16 shrink-0 items-center justify-between border-b border-gray-200 bg-white px-5 dark:border-gray-700 dark:bg-gray-800">
        <a href="{{ route('customer.dashboard') }}" class="flex items-center">
            <img src="{{ asset('images/logo/logo-dark.png') }}"
                 alt="{{ config('app.name') }}"
                 class="h-9 dark:hidden">
            <img src="{{ asset('images/logo/logo-dark.png') }}"
                 alt="{{ config('app.name') }}"
                 class="hidden h-9 dark:block"
                 style="filter:brightness(0) invert(1)">
        </a>
        <button @click="sidebarOpen = false" class="rounded p-1 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 lg:hidden">
            <iconify-icon icon="lucide:x" width="20"></iconify-icon>
        </button>
    </div>

    {{-- Customer info card (matches old portal's user-wrap in header) --}}
    @php $sidebarCustomer = auth()->user()->customer; @endphp
    <div class="border-b border-gray-200 bg-gray-50 px-5 py-4 dark:border-gray-700 dark:bg-gray-800/60">
        <div class="flex items-center gap-3">
            {{-- Avatar initial --}}
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-700 text-sm font-bold text-white shadow-sm">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-gray-800 dark:text-white">
                    {{ auth()->user()->name }}
                </p>
                @if($sidebarCustomer?->company)
                <p class="truncate text-xs text-brand-600 dark:text-brand-400">
                    {{ $sidebarCustomer->company }}
                </p>
                @else
                <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('Customer') }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4">
        <ul class="space-y-1">

            @php
                $navItems = [
                    ['route' => 'customer.dashboard',    'icon' => 'lucide:layout-dashboard', 'label' => __('Dashboard')],
                    ['route' => 'customer.orders.create','icon' => 'lucide:plus-circle',     'label' => __('Create Order')],
                    ['route' => 'customer.orders.index', 'icon' => 'lucide:list',            'label' => __('Orders')],
                    ['route' => 'customer.statistics',  'icon' => 'lucide:bar-chart-2',      'label' => __('Statistics')],
                    ['route' => 'customer.history',     'icon' => 'lucide:clock',            'label' => __('Archived Orders')],
                    ['route' => 'customer.account',           'icon' => 'lucide:settings',     'label' => __('Account')],
                    ['route' => 'customer.reviewers.index',   'icon' => 'lucide:user-check',   'label' => __('Reviewers')],
                    ['route' => 'customer.notifications',     'icon' => 'lucide:bell',          'label' => __('Notifications')],
                ];

                // Company Users — only for managers with can_view_report
                $sidebarCustomerId = \App\Models\Customer::where('user_id', auth()->id())->value('id');
                $isCompanyManager  = $sidebarCustomerId && \App\Models\CompanyManager::where('cus_id', $sidebarCustomerId)
                    ->where('can_view_report', 1)->exists();
            @endphp

            @foreach($navItems as $item)
                @if(Route::has($item['route']))
                @php
                    // Orders list: active on show/cancel but NOT create
                    if ($item['route'] === 'customer.orders.index') {
                        $isActive = request()->routeIs('customer.orders.*')
                                 && !request()->routeIs('customer.orders.create');
                    } else {
                        $isActive = request()->routeIs($item['route']);
                    }
                @endphp
                <li>
                    <a href="{{ route($item['route']) }}"
                        style="{{ $isActive ? 'background:rgba(139,43,45,.08);color:#8b2b2d;border-left:3px solid #8b2b2d;' : 'border-left:3px solid transparent;' }}"
                        class="flex items-center gap-3 rounded-r-lg px-3 py-2.5 text-sm font-medium transition-all
                            {{ $isActive
                                ? 'font-semibold'
                                : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-white' }}">
                        <iconify-icon icon="{{ $item['icon'] }}" width="18" class="shrink-0"
                            style="{{ $isActive ? 'color:#8b2b2d' : '' }}"></iconify-icon>
                        {{ $item['label'] }}
                    </a>
                </li>
                @endif
            @endforeach

            {{-- Company Users — shown only for company managers --}}
            @if($isCompanyManager)
            <li>
                @php $companyActive = request()->routeIs('customer.company-users*'); @endphp
                <a href="{{ route('customer.company-users') }}"
                    style="{{ $companyActive ? 'background:rgba(139,43,45,.08);color:#8b2b2d;border-left:3px solid #8b2b2d;' : 'border-left:3px solid transparent;' }}"
                    class="flex items-center gap-3 rounded-r-lg px-3 py-2.5 text-sm font-medium transition-all
                        {{ $companyActive ? 'font-semibold' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/60 dark:hover:text-white' }}">
                    <iconify-icon icon="lucide:users" width="18" class="shrink-0"
                        style="{{ $companyActive ? 'color:#8b2b2d' : '' }}"></iconify-icon>
                    {{ __('Company Staff') }}
                </a>
            </li>
            @endif

        </ul>
    </nav>

    {{-- Logout --}}
    <div class="border-t border-gray-200 p-3 dark:border-gray-700">
        <form action="{{ route('customer.logout') }}" method="POST">
            @csrf
            <button type="submit"
                class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 transition-colors hover:bg-red-50 hover:text-red-600 dark:text-gray-400 dark:hover:bg-red-900/20 dark:hover:text-red-400">
                <iconify-icon icon="lucide:log-out" width="18" class="shrink-0"></iconify-icon>
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</aside>
