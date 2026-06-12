<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">
    <x-slot name="breadcrumbsData">
        <x-breadcrumbs :breadcrumbs="$breadcrumbs">
        </x-breadcrumbs>
    </x-slot>
    <div class="mb-6 hidden">
        <!-- Blue gradient header bar -->
        <div x-data="{ dropdownOpen: false }" class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg px-6 py-4 flex items-center justify-between mb-4 relative">
            <h2 class="text-xl font-semibold text-white">
                {{ __('Services') }}
            </h2>
            <div class="flex items-center gap-2">
                @can('service-category.create')
                    <a href="{{ route('admin.service-category.create') }}" class="bg-white text-blue-600 hover:bg-gray-100 rounded-md p-2 flex items-center justify-center transition-colors" title="{{ __('New Service') }}">
                        <iconify-icon icon="lucide:plus" class="w-5 h-5"></iconify-icon>
                    </a>
                @endcan
            </div>
        </div>
    </div>

    @livewire('datatable.service-category-datatable', ['lazy' => true], key('service-category-datatable'))

    <!-- Service Types Modal -->
    <div x-data="serviceTypesModal()"
         @open-service-types-modal.window="openModal($event.detail)"
         @edit-service-type.window="editServiceType($event.detail)"
         @delete-service-type.window="deleteServiceType($event.detail)"
         class="relative z-50 overflow-hidden">
        
        <div x-show="open" 
             class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity flex items-center justify-center p-4"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             style="display: none;">
            
            <div @click.away="open = false" 
                 class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl w-full max-w-7xl max-h-[95vh] overflow-hidden flex flex-col"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-800">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <iconify-icon icon="lucide:layers" class="text-blue-600"></iconify-icon>
                        <span>{{ __('Service Types for') }}: <span x-text="categoryName" class="text-blue-600"></span></span>
                    </h3>
                    <button @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                        <iconify-icon icon="lucide:x" class="w-6 h-6"></iconify-icon>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="overflow-y-auto flex-1 border-b border-gray-200 dark:border-gray-700">
                    <div class="p-6">
                        <!-- Form Section -->
                        <div class="bg-blue-50/50 dark:bg-blue-900/10 p-4 rounded-lg border border-blue-100 dark:border-blue-800 mb-8">
                            <h4 class="text-md font-semibold mb-4 text-blue-800 dark:text-blue-300" x-text="isEditing ? '{{ __('Edit Service Type') }}' : '{{ __('Add New Service Type') }}'"></h4>
                            <form id="addServiceTypeForm" @submit.prevent="submitForm" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Service Name') }} <span class="text-red-500">*</span></label>
                                    <input type="text" x-model="formData.name" required class="form-control w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Price') }} <span class="text-red-500">*</span></label>
                                    <div class="relative rounded-md shadow-sm">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                            <span class="text-gray-500 sm:text-sm">$</span>
                                        </div>
                                        <input type="number" step="0.01" x-model="formData.price" required class="form-control w-full rounded-md border-gray-300 pl-7 focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="0.00">
                                    </div>
                                </div>
                                <div class="space-y-1 md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Description') }}</label>
                                    <textarea x-model="formData.description" class="form-control w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" rows="2"></textarea>
                                </div>
                                <div class="md:col-span-2 flex items-center gap-6">
                                    <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <input type="checkbox" x-model="formData.place" class="form-checkbox rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                        {{ __('Place') }}
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <input type="checkbox" x-model="formData.country" class="form-checkbox rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                        {{ __('Country') }}
                                    </label>
                                </div>
                                <div class="space-y-1" x-show="categoryId == 3">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Delivery Days') }}</label>
                                    <input type="number" min="0" step="1" x-model="formData.delivery_days" class="form-control w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>
                                <div class="space-y-1 md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Customers') }}</label>
                                    <select id="customerSelect" class="js-multiselect w-full" multiple="multiple"></select>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('Search and select customers for this service type.') }}</p>
                                </div>
                                <div class="md:col-span-2 flex justify-end gap-2 mt-2">
                                    <button type="button" @click="resetForm" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">{{ __('Reset') }}</button>
                                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 flex items-center gap-2" :disabled="loading">
                                        <iconify-icon x-show="loading" icon="lucide:loader-2" class="animate-spin"></iconify-icon>
                                        <span x-text="loading ? '{{ __("Saving...") }}' : (isEditing ? '{{ __("Update Service Type") }}' : '{{ __("Save Service Type") }}')"></span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Datatable Section -->
                        <div class="mt-8">
                            <livewire:datatable.service-type-datatable :lazy="true" wire:key="service-type-datatable" />
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex justify-end gap-2">
                    <button @click="open = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function serviceTypesModal() {
            return {
                open: false,
                loading: false,
                isEditing: false,
                editingId: null,
                categoryId: null,
                categoryName: '',
                formData: {
                    name: '',
                    price: '',
                    description: '',
                    place: false,
                    country: false,
                    delivery_days: '',
                    customers: []
                },

                openModal(detail) {
                    this.categoryId = detail.id;
                    this.categoryName = detail.name;
                    this.open = true;
                    this.loadCustomers();
                    this.resetForm();
                    
                    // Update Livewire component with category ID using event dispatch
                    Livewire.dispatch('refreshServiceTypeDatatable', { categoryId: this.categoryId });
                },

                loadCustomers() {
                    fetch(`{{ route('admin.service-types.customers') }}`)
                        .then(response => response.json())
                        .then(res => {
                            if (res.success) {
                                let select = $('#customerSelect');
                                select.empty();
                                res.data.forEach(customer => {
                                    select.append(new Option(customer.text, customer.id, false, false));
                                });
                                select.select2({
                                    width: '100%',
                                    placeholder: '{{ __("Select Customers") }}'
                                });
                            }
                        });
                },

                resetForm() {
                    this.isEditing = false;
                    this.editingId = null;
                    this.formData = {
                        name: '',
                        price: '',
                        description: '',
                        place: false,
                        country: false,
                        delivery_days: '',
                        customers: []
                    };
                    if ($('#customerSelect').length) {
                        $('#customerSelect').val([]).trigger('change');
                    }
                },

                editServiceType(type) {
                    this.isEditing = true;
                    this.editingId = type.id;
                    this.formData = {
                        name: type.name,
                        price: type.price,
                        description: type.description,
                        place: !! type.place,
                        country: !! type.country,
                        delivery_days: type.delivery_days ?? '',
                        customers: type.customers ? type.customers.map(c => c.id) : []
                    };
                    if ($('#customerSelect').length) {
                        $('#customerSelect').val(this.formData.customers).trigger('change');
                    }
                    // Scroll to form
                    document.getElementById('addServiceTypeForm').scrollIntoView({ behavior: 'smooth' });
                },

                submitForm() {
                    this.loading = true;
                    this.formData.service_category_id = this.categoryId;
                    this.formData.customers = $('#customerSelect').val() || [];

                    const url = this.isEditing 
                        ? `{{ url('admin/service-types') }}/${this.editingId}`
                        : '{{ route("admin.service-types.store") }}';
                    
                    const method = this.isEditing ? 'PUT' : 'POST';

                    fetch(url, {
                            method: method,
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify(this.formData)
                        })
                        .then(response => response.json())
                        .then(res => {
                            this.loading = false;
                            if (res.success) {
                                window.dispatchEvent(new CustomEvent('toast-notify', {
                                    detail: {
                                        message: res.message,
                                        type: 'success'
                                    }
                                }));
                                this.resetForm();
                                // Dispatch event to refresh Livewire datatable
                                Livewire.dispatch('refreshServiceTypeDatatable', { categoryId: this.categoryId });
                            } else {
                                alert(res.message);
                            }
                        })
                        .catch(err => {
                            this.loading = false;
                            alert('{{ __("An error occurred. Please try again.") }}');
                        });
                },

                deleteServiceType(id) {
                    if (!confirm('{{ __("Are you sure you want to delete this service type?") }}')) return;

                    this.loading = true;
                    fetch(`{{ url('admin/service-types') }}/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        })
                        .then(response => response.json())
                        .then(res => {
                            this.loading = false;
                            if (res.success) {
                                window.dispatchEvent(new CustomEvent('toast-notify', {
                                    detail: {
                                        message: res.message,
                                        type: 'success'
                                    }
                                }));
                                // Dispatch event to refresh Livewire datatable
                                Livewire.dispatch('refreshServiceTypeDatatable', { categoryId: this.categoryId });
                            } else {
                                alert(res.message);
                            }
                        });
                }
            };
        }

        // Global event listener for datatable buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('.open-service-types-modal')) {
                const btn = e.target.closest('.open-service-types-modal');
                window.dispatchEvent(new CustomEvent('open-service-types-modal', {
                    detail: {
                        id: btn.dataset.id,
                        name: btn.dataset.name
                    }
                }));
            }
            
            // Edit service type from datatable
            if (e.target.closest('.edit-service-type')) {
                const btn = e.target.closest('.edit-service-type');
                try {
                    const serviceTypeData = btn.getAttribute('data-service-type');
                    console.log('Raw data:', serviceTypeData);
                    const serviceType = JSON.parse(serviceTypeData);
                    console.log('Parsed:', serviceType);
                    window.dispatchEvent(new CustomEvent('edit-service-type', {
                        detail: serviceType
                    }));
                } catch(err) {
                    console.error('Failed to parse service type:', err);
                }
            }
            
            // Delete service type from datatable
            if (e.target.closest('.delete-service-type')) {
                const btn = e.target.closest('.delete-service-type');
                const serviceTypeId = parseInt(btn.getAttribute('data-service-type-id'));
                window.dispatchEvent(new CustomEvent('delete-service-type', {
                    detail: serviceTypeId
                }));
            }
        });
    </script>
    @endpush
</x-layouts.backend-layout>

