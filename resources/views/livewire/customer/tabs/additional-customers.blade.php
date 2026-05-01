<div class="py-6">
    <div class="mb-6 flex items-center justify-between">
        <h3 class="flex items-center gap-2.5 text-lg font-medium text-gray-900">
            <span class="inline-block h-2 w-2 rounded-full bg-indigo-600"></span>
            Additional Customers
        </h3>
        <button
            type="button"
            wire:click="showAddForm"
            class="inline-flex items-center gap-2 rounded-md border border-indigo-200 px-5 py-1.5 text-xs font-semibold uppercase tracking-wide text-indigo-700 shadow-sm transition hover:bg-indigo-50"
        >
            Add
        </button>
    </div>

    @if ($showForm)
        <form wire:submit="saveAdditionalCustomer" class="mb-6 rounded-xl border border-gray-200 bg-white p-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-gray-500">Name</label>
                    <input
                        type="text"
                        wire:model.defer="name"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                    />
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-gray-500">Email</label>
                    <input
                        type="email"
                        wire:model.defer="email"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                    />
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-4 flex items-center justify-end gap-2">
                <button
                    type="button"
                    wire:click="cancelForm"
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-600 transition hover:bg-gray-50"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700"
                >
                    {{ $isEditing ? 'Update' : 'Save' }}
                </button>
            </div>
        </form>
    @endif

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <table class="w-full border-collapse">
            <thead class="bg-gray-50">
                <tr>
                    <th class="border-b border-gray-200 px-4 py-3 text-left text-sm font-medium text-gray-600">Name</th>
                    <th class="border-b border-gray-200 px-4 py-3 text-left text-sm font-medium text-gray-600">Email</th>
                    <th class="w-14 border-b border-gray-200 px-2 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($additionalCustomers as $additionalCustomer)
                    <tr wire:key="additional-customer-{{ $additionalCustomer['id'] }}" class="hover:bg-gray-50/60">
                        <td class="border-b border-gray-200 px-4 py-3 text-sm text-gray-800">{{ $additionalCustomer['name'] }}</td>
                        <td class="border-b border-gray-200 px-4 py-3 text-sm text-gray-700">{{ $additionalCustomer['email'] }}</td>
                        <td class="border-b border-gray-200 px-2 py-3 text-right">
                            <div x-data="{ open: false }" class="relative inline-block">
                                <button
                                    type="button"
                                    @click="open = !open"
                                    class="rounded-full p-1.5 text-gray-500 transition hover:bg-gray-100 hover:text-gray-800"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M11.94 2.5a1.93 1.93 0 0 0-3.88 0v.55a6.93 6.93 0 0 0-2 .83l-.39-.39a1.93 1.93 0 0 0-2.73 2.73l.39.39c-.36.63-.64 1.3-.83 2h-.55a1.93 1.93 0 1 0 0 3.88h.55c.19.7.47 1.37.83 2l-.39.39a1.93 1.93 0 1 0 2.73 2.73l.39-.39c.63.36 1.3.64 2 .83v.55a1.93 1.93 0 1 0 3.88 0v-.55c.7-.19 1.37-.47 2-.83l.39.39a1.93 1.93 0 1 0 2.73-2.73l-.39-.39c.36-.63.64-1.3.83-2h.55a1.93 1.93 0 1 0 0-3.88h-.55a6.94 6.94 0 0 0-.83-2l.39-.39a1.93 1.93 0 0 0-2.73-2.73l-.39.39a6.93 6.93 0 0 0-2-.83V2.5Z"/>
                                        <circle cx="10" cy="10" r="2.6"/>
                                    </svg>
                                </button>

                                <div
                                    x-show="open"
                                    @click.outside="open = false"
                                    x-cloak
                                    class="absolute right-0 z-10 mt-1 w-28 rounded-lg border border-gray-200 bg-white py-1 shadow-lg"
                                >
                                    <button
                                        type="button"
                                        @click="open = false"
                                        wire:click="editAdditionalCustomer({{ $additionalCustomer['id'] }})"
                                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs text-gray-700 transition hover:bg-gray-50"
                                    >
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path d="M11 2.5a1.5 1.5 0 0 1 2.5 1.5L5 13l-3 1 1-3 8.5-8.5Z"/>
                                        </svg>
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        @click="open = false"
                                        wire:click="deleteAdditionalCustomer({{ $additionalCustomer['id'] }})"
                                        wire:confirm="Are you sure you want to delete this additional customer?"
                                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs text-red-600 transition hover:bg-red-50"
                                    >
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path d="M3 4h10M6.5 4V2.8A.8.8 0 0 1 7.3 2h1.4a.8.8 0 0 1 .8.8V4M5.2 4v8.2c0 .44.36.8.8.8h4c.44 0 .8-.36.8-.8V4"/>
                                            <path d="M7 6.5v4m2-4v4"/>
                                        </svg>
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500">
                            No additional customers found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>