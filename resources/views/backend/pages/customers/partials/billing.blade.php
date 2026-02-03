<div class="mt-4">
    <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
        <iconify-icon icon="lucide:file-text" class="w-5 h-5"></iconify-icon>
        {{ __('Standard Billing Details') }}
    </h3>
    <div class="mt-0 bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="space-y-6">
            <div>
                <label for="pref" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('Reference') }}
                    <span class="block text-xs font-normal text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('(Invoice Recipient)') }}
                    </span>
                </label>
                <input
                    type="text"
                    name="pref"
                    id="pref"
                    value="{{ old('pref', $billingDetails->referenceperson ?? '') }}"
                    class="w-full form-control border-2 border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-800 transition-colors"
                    placeholder="{{ __('Enter reference person') }}"
                >
                @error('pref')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="ref" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('Reference') }}
                </label>
                <input
                    type="text"
                    name="ref"
                    id="ref"
                    value="{{ old('ref', $billingDetails->reference ?? '') }}"
                    class="w-full form-control border-2 border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-800 transition-colors"
                    placeholder="{{ __('Enter reference') }}"
                >
                @error('ref')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="comment" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('Invoice Comment') }}
                </label>
                <textarea
                    name="comment"
                    id="comment"
                    rows="3"
                    class="w-full form-control border-2 border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-800 transition-colors resize-none"
                    placeholder="{{ __('Enter invoice comment') }}"
                >{{ old('comment', $billingDetails->comment ?? '') }}</textarea>
                @error('comment')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>
</div>



