<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    @include('backend.layouts.partials.theme-colors')

    @viteReactRefresh
    @vite(['resources/js/app.js', 'resources/css/app.css'])

    {{-- ── Customer portal: override brand to Recway burgundy #8b2b2d ── --}}
    <style>
        :root {
            --color-brand-50:  #fdf2f2;
            --color-brand-100: #fce4e4;
            --color-brand-200: #f8bcbc;
            --color-brand-300: #f28585;
            --color-brand-400: #e94e50;
            --color-brand-500: #8b2b2d;
            --color-brand-600: #7a2527;
            --color-brand-700: #6b1f21;
            --color-brand-800: #551819;
            --color-brand-900: #3d1112;
        }
    </style>

    <style>
        [x-cloak] { display: none !important; }

        /* ── Customer data table ────────────────────────────────────────── */
        .dt-th { cursor:pointer; user-select:none; }
        .dt-th:hover { background-color: rgba(139,43,45,.06); }
        .dt-sort-icon { display:inline-flex; flex-direction:column; gap:1px;
                        margin-left:6px; opacity:.4; vertical-align:middle; }
        .dt-sort-icon svg { width:8px; height:8px; }
        .dt-sort-asc  .sort-up,
        .dt-sort-desc .sort-down { opacity:1; color:#8b2b2d; }
        .dt-sort-asc  .sort-down,
        .dt-sort-desc .sort-up   { opacity:.25; }
        .dark .dt-sort-asc  .sort-up,
        .dark .dt-sort-desc .sort-down { color:#c47375; }

        /* Pagination */
        .dt-page-btn {
            display:inline-flex; align-items:center; justify-content:center;
            min-width:34px; height:34px; padding:0 10px; border-radius:8px;
            border:1px solid #e5e7eb; background:#fff; font-size:13px;
            font-weight:500; color:#374151; transition:all .15s; cursor:pointer;
        }
        .dt-page-btn:hover:not(:disabled):not(.active) {
            border-color:#8b2b2d; color:#8b2b2d; background:#fdf6f6;
        }
        .dt-page-btn.active {
            background:#8b2b2d; border-color:#8b2b2d; color:#fff;
        }
        .dt-page-btn:disabled { opacity:.4; cursor:not-allowed; }
        .dark .dt-page-btn { background:#1f2937; border-color:#374151; color:#d1d5db; }
        .dark .dt-page-btn:hover:not(:disabled):not(.active) {
            border-color:#c47375; color:#c47375; background:#2d1617;
        }
        .dark .dt-page-btn.active { background:#8b2b2d; border-color:#8b2b2d; color:#fff; }
    </style>
    @stack('styles')
</head>

<body
    x-data="{
        darkMode: false,
        sidebarOpen: false,
    }"
    x-init="
        darkMode = JSON.parse(localStorage.getItem('darkMode')) ?? false;
        $watch('darkMode', v => localStorage.setItem('darkMode', JSON.stringify(v)));
    "
    :class="{ 'dark bg-gray-900': darkMode }"
    class="font-sans antialiased"
>

<div class="flex h-screen overflow-hidden bg-gray-100 dark:bg-gray-900">

    {{-- ── Sidebar ── --}}
    @include('customer.layouts.partials.sidebar')

    {{-- ── Main content area ── --}}
    <div class="flex flex-col flex-1 overflow-hidden">

        {{-- Navbar --}}
        @include('customer.layouts.partials.navbar')

        {{-- Page content --}}
        <main class="flex-1 overflow-y-auto p-4 md:p-6">
            {{-- Flash messages --}}
            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                    class="mb-4 flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
                    <iconify-icon icon="lucide:check-circle" class="shrink-0"></iconify-icon>
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                    class="mb-4 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                    <iconify-icon icon="lucide:alert-circle" class="shrink-0"></iconify-icon>
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script>
/**
 * Reusable Alpine.js data table — sorting + pagination.
 *
 * Usage:  x-data="customerTable('tbody-id', { sort:'name', dir:'asc', perPage:25 })"
 *
 * Each <tr> must have:
 *   data-sort-name="John Smith"
 *   data-sort-date="2026-05-01"
 *   data-sort-status="Active"
 *   (one data-sort-* per sortable column, value is plain string for comparison)
 *
 * Each sortable <th> should have:
 *   @click="sortBy('name')"   :class="thClass('name')"
 *   And inside: the column label + <span x-html="sortIcon('name')"></span>
 */
function customerTable(tbodyId, opts = {}) {
    return {
        tbodyId,
        sortKey:  opts.sort    || '',
        sortDir:  opts.dir     || 'asc',
        perPage:  parseInt(opts.perPage || 25),
        page:     1,
        rows:     [],   // { el, vals }
        order:    [],   // sorted indices into rows[]

        // ── Computed ──────────────────────────────────────────────────────
        get total()     { return this.rows.length; },
        get pageCount() { return Math.max(1, Math.ceil(this.total / this.perPage)); },
        get from()      { return this.total ? (this.page - 1) * this.perPage + 1 : 0; },
        get to()        { return Math.min(this.page * this.perPage, this.total); },

        pageNumbers() {
            const p = this.pageCount, cur = this.page, nums = [];
            if (p <= 7) { for (let i = 1; i <= p; i++) nums.push(i); return nums; }
            nums.push(1);
            if (cur > 3) nums.push('…');
            for (let i = Math.max(2, cur - 1); i <= Math.min(p - 1, cur + 1); i++) nums.push(i);
            if (cur < p - 2) nums.push('…');
            nums.push(p);
            return nums;
        },

        // ── Init ─────────────────────────────────────────────────────────
        init() {
            const tbody = document.getElementById(this.tbodyId);
            if (!tbody) return;
            tbody.querySelectorAll('tr[data-row]').forEach(el => {
                const vals = {};
                Object.keys(el.dataset).forEach(k => {
                    if (k.startsWith('sort')) {
                        vals[k.slice(4).toLowerCase()] = (el.dataset[k] || '').toLowerCase();
                    }
                });
                this.rows.push({ el, vals });
            });
            this._applySort();
        },

        // ── Actions ───────────────────────────────────────────────────────
        sortBy(key) {
            this.sortDir = (this.sortKey === key && this.sortDir === 'asc') ? 'desc' : 'asc';
            this.sortKey = key;
            this.page = 1;
            this._applySort();
        },

        goTo(p) {
            if (p === '…' || p < 1 || p > this.pageCount) return;
            this.page = p;
            this._renderPage();
        },

        changePerPage(n) {
            this.perPage = parseInt(n);
            this.page = 1;
            this._renderPage();
        },

        // ── Helpers ───────────────────────────────────────────────────────
        thClass(key) {
            const base = 'dt-th';
            if (this.sortKey !== key) return base;
            return base + (this.sortDir === 'asc' ? ' dt-sort-asc' : ' dt-sort-desc');
        },

        sortIcon(key) {
            const active = this.sortKey === key;
            const asc    = this.sortDir === 'asc';
            return `<span class="dt-sort-icon">
                <svg class="sort-up"  viewBox="0 0 10 6" fill="currentColor"><path d="M5 0l5 6H0z"/></svg>
                <svg class="sort-down" viewBox="0 0 10 6" fill="currentColor"><path d="M5 6L0 0h10z"/></svg>
            </span>`;
        },

        _applySort() {
            this.order = this.rows.map((_, i) => i);
            if (this.sortKey) {
                const key = this.sortKey, dir = this.sortDir === 'asc' ? 1 : -1;
                this.order.sort((a, b) => {
                    const av = this.rows[a].vals[key] || '';
                    const bv = this.rows[b].vals[key] || '';
                    return av.localeCompare(bv, undefined, { numeric: true, sensitivity: 'base' }) * dir;
                });
            }
            this._renderPage();
        },

        _renderPage() {
            const tbody = document.getElementById(this.tbodyId);
            if (!tbody) return;
            const start = (this.page - 1) * this.perPage;
            const end   = start + this.perPage;
            this.order.forEach((rowIdx, displayIdx) => {
                const row = this.rows[rowIdx].el;
                row.style.display = (displayIdx >= start && displayIdx < end) ? '' : 'none';
                // Re-order in DOM for correct visual sort
                tbody.appendChild(row);
            });
            // Renumber serial column (#) if present
            let visible = 0;
            this.order.slice(start, end).forEach(rowIdx => {
                const numCell = this.rows[rowIdx].el.querySelector('.dt-num');
                if (numCell) numCell.textContent = ++visible + start;
            });
        },
    };
}
</script>

@stack('scripts')
</body>
</html>
