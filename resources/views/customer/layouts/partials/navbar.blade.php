<header class="flex h-16 shrink-0 items-center border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
    style="padding: 0;">

    {{-- ── Left: mobile sidebar toggle ── --}}
    <button @click="sidebarOpen = !sidebarOpen"
        class="flex h-16 w-14 shrink-0 items-center justify-center border-r border-gray-200 text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700/50 lg:hidden">
        <iconify-icon icon="lucide:menu" width="20"></iconify-icon>
    </button>

    {{-- ── Centre: contact info (matches old portal header-left) ── --}}
    <div class="flex flex-1 items-center gap-6 overflow-hidden px-4 md:px-6">
        <div class="flex items-center gap-5">
            {{-- Email --}}
            <a href="mailto:info@recway.se"
                class="flex items-center gap-2 text-sm text-gray-600 transition-colors hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400">
                <iconify-icon icon="lucide:mail" width="18" class="shrink-0"></iconify-icon>
                <span class="hidden font-medium sm:inline">info@recway.se</span>
            </a>

            {{-- Divider --}}
            <span class="hidden h-4 w-px bg-gray-200 dark:bg-gray-600 sm:block"></span>

            {{-- Phone --}}
            <a href="tel:+4685510639"
                class="flex items-center gap-2 text-sm text-gray-600 transition-colors hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400">
                <iconify-icon icon="lucide:phone" width="18" class="shrink-0"></iconify-icon>
                <span class="hidden font-medium sm:inline">+46 8 551 063 97</span>
            </a>
        </div>

        {{-- Help text — hidden on small screens --}}
        <p class="hidden truncate text-xs text-gray-400 dark:text-gray-500 xl:block">
            {{ __('If you have any questions, feel free to email us or call us!') }}
        </p>
    </div>

    {{-- ── Right: actions ── --}}
    <div class="flex shrink-0 items-center gap-1 pr-3">

        @php
            $latestUpdate  = \Illuminate\Support\Facades\DB::table('updates')->where('visible',1)->max('created_at');
            $navCusId      = \App\Models\Customer::where('user_id', auth()->id())->value('id');
            $navLastSeen   = $navCusId
                ? \Illuminate\Support\Facades\DB::table('customer_update_reads')->where('customer_id',$navCusId)->value('last_seen_at')
                : null;
            $hasUnread = $latestUpdate && (!$navLastSeen || strtotime($latestUpdate) > strtotime($navLastSeen));
        @endphp

        {{-- Notification bell --}}
        <a href="{{ route('customer.notifications') }}"
            class="relative flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700">
            <iconify-icon icon="lucide:bell" width="18"></iconify-icon>
            @if($hasUnread)
            <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-red-500 ring-2 ring-white dark:ring-gray-800"></span>
            @endif
        </a>

        {{-- Language switcher --}}
        @include('backend.layouts.partials.locale-switcher')

        {{-- Dark mode --}}
        <button @click="darkMode = !darkMode"
            class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700">
            <iconify-icon x-show="!darkMode" icon="lucide:moon" width="18"></iconify-icon>
            <iconify-icon x-show="darkMode"  icon="lucide:sun"  width="18" x-cloak></iconify-icon>
        </button>

        {{-- User dropdown --}}
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" @click.outside="open = false"
                class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700 dark:bg-brand-900/30 dark:text-brand-400">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="hidden text-left md:block">
                    <p class="text-xs font-semibold text-gray-800 dark:text-white leading-tight">{{ auth()->user()->name }}</p>
                    @php $navCompany = \App\Models\Customer::where('user_id', auth()->id())->value('company'); @endphp
                    @if($navCompany)
                    <p class="text-xs text-gray-400 leading-tight">{{ $navCompany }}</p>
                    @endif
                </div>
                <iconify-icon icon="lucide:chevron-down" width="13" class="text-gray-400"></iconify-icon>
            </button>

            <div x-show="open" x-transition x-cloak
                class="absolute right-0 top-full z-50 mt-1 w-48 rounded-xl border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                <a href="{{ route('customer.account') }}"
                    class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700">
                    <iconify-icon icon="lucide:settings" width="14"></iconify-icon>
                    {{ __('Account Settings') }}
                </a>
                <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>
                <form action="{{ route('customer.logout') }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="flex w-full items-center gap-2 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20">
                        <iconify-icon icon="lucide:log-out" width="14"></iconify-icon>
                        {{ __('Log Out') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
