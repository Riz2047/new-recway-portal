<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">
    @php $prefix = request()->routeIs('staff.*') ? 'staff' : 'admin'; @endphp

    <div
        x-data="{ panelOpen: false }"
        @open-candidate-panel.window="panelOpen = true"
        @keydown.escape.window="panelOpen = false"
    >
        {{-- Datatable --}}
        @livewire('datatable.candidate-datatable', [
            'lazy'        => true,
            'panelPrefix' => $prefix,
        ], key('candidate-datatable'))

        {{-- ============================================================
             HOVER HISTORY TOOLTIP
             - Populated by JavaScript on mouseenter of [data-candidate-id]
             ============================================================ --}}
        <div id="history-tooltip"
            class="pointer-events-none fixed z-[60] hidden w-72 rounded-xl border border-gray-200 bg-white p-4 shadow-2xl dark:border-gray-700 dark:bg-gray-800"
        >
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {{ __('Order History') }}
            </p>
            <div id="history-tooltip-inner">
                <div class="flex items-center gap-2 text-xs text-gray-400">
                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                    {{ __('Loading...') }}
                </div>
            </div>
        </div>

        {{-- ============================================================
             SLIDE-OVER PANEL  (wider: max-w-7xl)
             ============================================================ --}}

        {{-- Backdrop --}}
        <div
            x-show="panelOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="panelOpen = false"
            class="fixed inset-0 z-40 bg-gray-900/40 backdrop-blur-sm"
            style="display:none"
        ></div>

        {{-- Panel --}}
        <div
            x-show="panelOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            @click.stop
            class="fixed inset-y-0 right-0 z-50 flex w-full flex-col bg-white shadow-2xl dark:bg-gray-800 sm:max-w-5xl lg:max-w-6xl xl:max-w-7xl"
            style="display:none"
        >
            {{-- Panel header --}}
            <div class="flex shrink-0 items-center justify-between border-b border-gray-200 px-5 py-3 dark:border-gray-700">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Candidate Details') }}</h2>
                <button @click="panelOpen = false"
                    class="rounded-md p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                    aria-label="{{ __('Close') }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Panel body --}}
            <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                @livewire('candidate.candidate-panel', [], key('candidate-panel-singleton'))
            </div>
        </div>

    </div>

    @push('scripts')
    <script>
    (function () {
        const tooltip   = document.getElementById('history-tooltip');
        const inner     = document.getElementById('history-tooltip-inner');
        const baseUrl   = '{{ url($prefix . '/candidates') }}';
        let hideTimer   = null;
        let fetchController = null;
        let currentId   = null;

        function showTooltip(el, id) {
            clearTimeout(hideTimer);
            if (currentId === id && !tooltip.classList.contains('hidden')) return;
            currentId = id;

            // Position near the cursor / element
            const rect = el.getBoundingClientRect();
            const top  = Math.min(rect.bottom + 6, window.innerHeight - 280);
            const left = Math.min(rect.left, window.innerWidth - 300);
            tooltip.style.top  = top  + 'px';
            tooltip.style.left = left + 'px';

            // Reset content to loader
            inner.innerHTML = '<div class="flex items-center gap-2 text-xs text-gray-400"><svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg> {{ __('Loading...') }}</div>';
            tooltip.classList.remove('hidden');

            // Abort previous fetch if any
            if (fetchController) fetchController.abort();
            fetchController = new AbortController();

            fetch(`${baseUrl}/${id}/history-preview`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                signal: fetchController.signal,
            })
            .then(r => r.json())
            .then(data => {
                if (currentId != id) return;
                renderHistory(data.items || []);
            })
            .catch(() => {});
        }

        function renderHistory(items) {
            if (!items.length) {
                inner.innerHTML = '<p class="text-xs text-gray-400">{{ __('No history yet.') }}</p>';
                return;
            }
            let html = '<ol class="relative border-l border-gray-200 dark:border-gray-600 space-y-2">';
            items.forEach(item => {
                html += `<li class="ml-4">
                    <div class="absolute -left-1.5 mt-1.5 h-2.5 w-2.5 rounded-full bg-indigo-400 border border-white dark:border-gray-800"></div>
                    <time class="block text-xs text-gray-400">${escHtml(item.date_time || '')}</time>
                    <p class="text-xs font-medium text-gray-700 dark:text-gray-300 leading-snug">${escHtml(item.desc || '')}</p>
                    ${item.comment ? `<p class="text-xs italic text-gray-500 mt-0.5">${escHtml(item.comment)}</p>` : ''}
                </li>`;
            });
            html += '</ol>';
            inner.innerHTML = html;
        }

        function hideTooltip() {
            hideTimer = setTimeout(() => {
                tooltip.classList.add('hidden');
                currentId = null;
            }, 200);
        }

        function escHtml(str) {
            return String(str)
                .replace(/&/g,'&amp;').replace(/</g,'&lt;')
                .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        // Keep tooltip alive when mouse moves into it
        tooltip.addEventListener('mouseenter', () => clearTimeout(hideTimer));
        tooltip.addEventListener('mouseleave', hideTooltip);

        // Delegate hover to all [data-candidate-id] buttons
        document.addEventListener('mouseover', function (e) {
            const btn = e.target.closest('[data-candidate-id]');
            if (btn) showTooltip(btn, btn.dataset.candidateId);
        });
        document.addEventListener('mouseout', function (e) {
            const btn = e.target.closest('[data-candidate-id]');
            if (btn) hideTooltip();
        });
    })();
    </script>
    @endpush

</x-layouts.backend-layout>
