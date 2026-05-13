{{--
  Data table footer: "Showing X to Y of Z" + per-page selector + pagination.
  Requires: x-data="customerTable(...)" on a parent element.
  param $colspan — number of columns (for empty-state row)
--}}
<div class="flex flex-wrap items-center justify-between gap-4 border-t border-gray-200 px-5 py-4 dark:border-gray-700">

    {{-- Left: showing info + per-page --}}
    <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
        <span x-text="`Showing ${from}–${to} of ${total} entries`"></span>
        <select @change="changePerPage($event.target.value)"
            class="rounded-lg border border-gray-200 bg-gray-50 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
            <option value="10"  :selected="perPage===10">10</option>
            <option value="25"  :selected="perPage===25">25</option>
            <option value="50"  :selected="perPage===50">50</option>
            <option value="100" :selected="perPage===100">100</option>
        </select>
        <span class="text-xs">{{ __('per page') }}</span>
    </div>

    {{-- Right: page buttons --}}
    <div class="flex items-center gap-1" x-show="pageCount > 1">
        {{-- Previous --}}
        <button type="button" class="dt-page-btn" @click="goTo(page - 1)" :disabled="page === 1">
            <iconify-icon icon="lucide:chevron-left" width="14"></iconify-icon>
        </button>

        {{-- Page numbers --}}
        <template x-for="p in pageNumbers()" :key="p">
            <button type="button"
                class="dt-page-btn"
                :class="p === page ? 'active' : (p === '…' ? 'cursor-default hover:bg-transparent border-transparent' : '')"
                @click="goTo(p)"
                :disabled="p === '…'"
                x-text="p">
            </button>
        </template>

        {{-- Next --}}
        <button type="button" class="dt-page-btn" @click="goTo(page + 1)" :disabled="page === pageCount">
            <iconify-icon icon="lucide:chevron-right" width="14"></iconify-icon>
        </button>
    </div>
</div>
