<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">
    @php
        $prefix = request()->routeIs('staff.*') ? 'staff' : 'admin';
    @endphp

    <x-card>
        <x-slot name="header">
            {{ __('Add Candidate') }}
        </x-slot>

        <form
            method="POST"
            action="{{ route($prefix . '.candidates.store') }}"
            enctype="multipart/form-data"
            class="space-y-6"
            id="candidate-create-form"
            data-old-input='@json(session()->getOldInput())'
            data-field-errors='@json($errors->toArray())'
        >
            @csrf

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="cus_id" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Customer') }}</label>
                    <select id="cus_id" name="cus_id" class="form-control" required>
                        <option value="">{{ __('Select customer') }}</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected(old('cus_id') == $customer->id)>
                                {{ $customer->user?->name ?? ('#' . $customer->id) }}
                            </option>
                        @endforeach
                    </select>
                    @error('cus_id')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="interview_id" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Service Type') }}</label>
                    <select id="interview_id" name="interview_id" class="form-control" required>
                        <option value="">{{ __('Select service type') }}</option>
                    </select>
                    @error('interview_id')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="staff_id" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Staff') }}</label>
                    <select id="staff_id" name="staff_id" class="form-control">
                        <option value="">{{ __('Select staff') }}</option>
                        @foreach ($staff as $staffMember)
                            <option value="{{ $staffMember->id }}" @selected(old('staff_id') == $staffMember->id)>
                                {{ $staffMember->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('staff_id')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div id="place-wrapper" class="hidden">
                    <label for="place" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Place') }}</label>
                    <select id="place" name="place" class="form-control">
                        <option value="">{{ __('Select place') }}</option>
                        @foreach ($places as $place)
                            <option value="{{ $place->id }}" @selected(old('place') == $place->id)>{{ $place->name }}</option>
                        @endforeach
                    </select>
                    @error('place')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div id="country-wrapper" class="hidden">
                    <label for="country" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Country') }}</label>
                    <input id="country" name="country" type="text" class="form-control" value="{{ old('country') }}" />
                    @error('country')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2 grid gap-4 md:grid-cols-2">
                    <div>
                        <p class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Send Mail - Customer') }}</p>
                        <label class="mr-4 inline-flex items-center gap-2 text-sm">
                            <input type="radio" name="send_mail_customer" value="yes" class="text-indigo-600" checked />
                            <span>{{ __('Yes') }}</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="radio" name="send_mail_customer" value="no" class="text-indigo-600" />
                            <span>{{ __('No') }}</span>
                        </label>
                    </div>
                    <div>
                        <p class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Send Mail - Candidate') }}</p>
                        <label class="mr-4 inline-flex items-center gap-2 text-sm">
                            <input type="radio" name="send_mail_candidate" value="yes" class="text-indigo-600" checked />
                            <span>{{ __('Yes') }}</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="radio" name="send_mail_candidate" value="no" class="text-indigo-600" />
                            <span>{{ __('No') }}</span>
                        </label>
                    </div>
                </div>

                <div id="documents-wrapper" class="hidden md:col-span-2">
                    <label for="candidate_documents" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Documents') }}</label>
                    <input id="candidate_documents" type="file" name="files[]" class="form-control" accept="application/pdf" multiple />
                    <p class="mt-1 text-sm text-gray-500">{{ __('Here you can upload several documents (Interview Templates, Documents or CV)') }}</p>
                </div>
            </div>

            <div id="candidate-dynamic-fields" class="space-y-6">
                <div class="grid gap-4 md:grid-cols-2" id="personal-fields"></div>
                <div class="grid gap-4 md:grid-cols-2" id="billing-fields"></div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route($prefix . '.candidates.index') }}" class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    {{ __('Cancel') }}
                </a>
                <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                    {{ __('Save') }}
                </button>
            </div>
        </form>
    </x-card>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const customerSelect = document.getElementById('cus_id');
            const serviceSelect = document.getElementById('interview_id');
            const personalFieldsContainer = document.getElementById('personal-fields');
            const billingFieldsContainer = document.getElementById('billing-fields');
            const placeWrapper = document.getElementById('place-wrapper');
            const countryWrapper = document.getElementById('country-wrapper');
            const placeInput = document.getElementById('place');
            const countryInput = document.getElementById('country');
            const documentsWrapper = document.getElementById('documents-wrapper');
            const formElement = document.getElementById('candidate-create-form');
            const oldInput = JSON.parse(formElement.dataset.oldInput || '{}');
            const fieldErrors = JSON.parse(formElement.dataset.fieldErrors || '{}');

            let hasPersonalId = Boolean(Number(oldInput.hasPersonalId ?? 0));
            let hasFormBuilder = false;

            const firstCustomerOption = Array.from(customerSelect.options).find((option) => option.value !== '');
            if (!customerSelect.value && firstCustomerOption) {
                customerSelect.value = firstCustomerOption.value;
            }

            customerSelect.addEventListener('change', () => {
                loadServices(customerSelect.value);
            });

            serviceSelect.addEventListener('change', () => {
                updateServiceLocationFields();
                loadForm(customerSelect.value, serviceSelect.value);
            });

            loadServices(customerSelect.value, oldInput.interview_id ?? null);

            formElement.addEventListener('submit', (event) => {
                if (!validateSecurityField()) {
                    event.preventDefault();
                }
            });

            async function loadServices(customerId, preferredServiceId = null) {
                resetServiceOptions();
                clearDynamicFields();

                if (!customerId) {
                    return;
                }

                try {
                    const response = await fetch(`{{ route($prefix . '.candidates.services') }}?cus_id=${encodeURIComponent(customerId)}`);
                    if (!response.ok) {
                        return;
                    }

                    const payload = await response.json();
                    const services = payload.services ?? [];

                    renderServiceOptions(services);
                    const selectedServiceId = resolveSelectedServiceId(services, preferredServiceId ?? payload.selected_service_id);

                    if (selectedServiceId) {
                        serviceSelect.value = String(selectedServiceId);
                        updateServiceLocationFields();
                        await loadForm(customerId, selectedServiceId);
                    } else {
                        updateServiceLocationFields();
                    }
                } catch (_error) {
                    // Keep form usable if dynamic payload fails.
                }
            }

            async function loadForm(customerId, serviceId) {
                clearDynamicFields();

                if (!customerId || !serviceId) {
                    return;
                }

                try {
                    const query = new URLSearchParams({
                        cus_id: customerId,
                        interview_id: serviceId,
                    });

                    const response = await fetch(`{{ route($prefix . '.candidates.form') }}?${query.toString()}`);
                    if (!response.ok) {
                        return;
                    }

                    const payload = await response.json();
                    if (payload.selected_service_id) {
                        serviceSelect.value = String(payload.selected_service_id);
                    }

                    hasFormBuilder = Boolean(payload.has_form_builder);
                    updateServiceLocationFields();
                    renderDynamicFields(payload.form_fields ?? []);
                } catch (_error) {
                    // Keep form usable if dynamic payload fails.
                }
            }

            function resetServiceOptions() {
                serviceSelect.innerHTML = `<option value="">{{ __('Select service type') }}</option>`;
                hasFormBuilder = false;
                updateServiceLocationFields();
            }

            function renderServiceOptions(services) {
                const options = [`<option value="">{{ __('Select service type') }}</option>`];

                services.forEach((service) => {
                    const showPlace = Number(service.place) === 1 ? '1' : '0';
                    const showCountry = Number(service.country) === 1 ? '1' : '0';
                    options.push(`<option value="${escapeHtml(service.id)}" data-place="${showPlace}" data-country="${showCountry}">${escapeHtml(service.name)}</option>`);
                });

                serviceSelect.innerHTML = options.join('');
            }

            function updateServiceLocationFields() {
                const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
                const hasSelectedService = Boolean(selectedOption && selectedOption.value !== '');
                const showPlaceByService = selectedOption && selectedOption.dataset.place === '1';
                const showCountry = selectedOption && selectedOption.dataset.country === '1';
                const showPlace = Boolean(hasSelectedService && (showPlaceByService || !hasFormBuilder));
                const showDocuments = Boolean(hasSelectedService && !hasFormBuilder);

                placeWrapper.classList.toggle('hidden', !showPlace);
                countryWrapper.classList.toggle('hidden', !showCountry);
                documentsWrapper.classList.toggle('hidden', !showDocuments);

                if (!showPlace) {
                    placeInput.value = '';
                }

                if (!showCountry) {
                    countryInput.value = '';
                }
            }

            function resolveSelectedServiceId(services, preferredServiceId) {
                const preferred = preferredServiceId ? Number(preferredServiceId) : null;
                if (preferred && services.some((service) => Number(service.id) === preferred)) {
                    return preferred;
                }

                return services.length ? Number(services[0].id) : null;
            }

            function clearDynamicFields() {
                personalFieldsContainer.innerHTML = '';
                billingFieldsContainer.innerHTML = '';
            }

            function renderDynamicFields(fields) {
                clearDynamicFields();
                let securityControlRendered = false;

                fields.forEach((field) => {
                    const sectionContainer = field.section === 'billing' ? billingFieldsContainer : personalFieldsContainer;
                    if (!sectionContainer) {
                        return;
                    }

                    if (field.name === 'security' && !securityControlRendered) {
                        sectionContainer.appendChild(renderSecurityToggle());
                        securityControlRendered = true;
                    }

                    sectionContainer.appendChild(renderField(field));
                });

                bindSecurityBehavior();
            }

            function renderSecurityToggle() {
                const wrapper = document.createElement('div');
                wrapper.className = 'md:col-span-2';
                wrapper.innerHTML = `
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                        <input type="checkbox" id="hasPersonalId" name="hasPersonalId" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" ${hasPersonalId ? 'checked' : ''} />
                        <span>{{ __('Has Personal Identification Number') }}</span>
                    </label>
                `;

                const checkbox = wrapper.querySelector('#hasPersonalId');
                checkbox.addEventListener('change', (event) => {
                    hasPersonalId = Boolean(event.target.checked);
                    updateSecurityFieldMode();
                    validateSecurityField();
                });

                return wrapper;
            }

            function renderField(field) {
                const wrapper = document.createElement('div');
                wrapper.className = field.type === 'textarea' ? 'md:col-span-2' : '';

                const name = String(field.name || '');
                const type = String(field.type || 'text');
                const label = String(field.label || name);
                const placeholder = String(field.placeholder || '');
                const required = Boolean(field.required);

                // Known direct DB columns — these always submit with their column name.
                const directColumns = [
                    'security', 'name', 'surname', 'email', 'phone', 'vasc_id',
                    'referensperson', 'reference', 'comment', 'note', 'place', 'country',
                ];

                // Strip trailing required-marker (*) from label to get a clean storage key.
                const cleanLabel = label.replace(/\s*\*\s*$/, '').trim();

                let inputName;
                if (field.section === 'billing') {
                    // ── Billing section ───────────────────────────────────────────────
                    // Known billing fields use their canonical column name (matched by
                    // name after PHP remap OR by label keyword).
                    const ll = label.toLowerCase();
                    if (name === 'referensperson' || name === 'pref' ||
                        ll.includes('invoice recipient') || ll.includes('ansvarig chef') || ll.includes('hiring manager')) {
                        inputName = 'referensperson';
                    } else if (name === 'reference' || name === 'ref' ||
                               (ll.includes('do') && ll.includes('siffror'))) {
                        inputName = 'reference';
                    } else if (name === 'comment' || name === 'note') {
                        inputName = name;
                    } else {
                        // Custom billing field → form_builder[cleanLabel] → meta_data
                        inputName = `form_builder[${cleanLabel}]`;
                    }
                } else {
                    // ── Personal section ──────────────────────────────────────────────
                    // Direct DB columns submit with their column name; everything else
                    // (custom questions like "Apply for the position", "Have you informed…")
                    // submits as form_builder[cleanLabel] so it lands in meta_data.
                    if (directColumns.includes(name)) {
                        inputName = name;
                    } else {
                        inputName = `form_builder[${cleanLabel}]`;
                    }
                }

                // Restore old value after validation failure
                const oldValue = (() => {
                    if (inputName.startsWith('form_builder[')) {
                        const key = inputName.slice(13, -1); // strip "form_builder[" and "]"
                        return (oldInput.form_builder ?? {})[key] ?? '';
                    }
                    return oldInput[inputName] ?? '';
                })();

                const fieldId = name === 'security' ? 'security' : `field_${name}`;
                const labelId = name === 'security' ? 'security-label' : '';
                let html = `<label ${labelId ? `id="${labelId}"` : ''} for="${escapeHtml(fieldId)}" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">${escapeHtml(label)}</label>`;

                if (type === 'textarea') {
                    html += `<textarea id="${escapeHtml(fieldId)}" name="${escapeHtml(inputName)}" rows="4" class="form-control" ${required ? 'required' : ''} placeholder="${escapeHtml(placeholder)}">${escapeHtml(oldValue)}</textarea>`;
                } else if (type === 'select') {
                    html += `<select id="${escapeHtml(fieldId)}" name="${escapeHtml(inputName)}" class="form-control" ${required ? 'required' : ''}>`;

                    if (placeholder) {
                        html += `<option value="">${escapeHtml(placeholder)}</option>`;
                    }

                    (field.options || []).forEach((option) => {
                        const selected = String(oldValue) === String(option) ? 'selected' : '';
                        html += `<option value="${escapeHtml(option)}" ${selected}>${escapeHtml(option)}</option>`;
                    });

                    html += `</select>`;
                } else {
                    // The security field is always rendered as text — when it represents a
                    // date of birth, flatpickr (a calendar widget) is attached to it instead
                    // of relying on the native browser date input.
                    const inputType = name === 'security'
                        ? 'text'
                        : normalizeInputType(type);

                    html += `<input id="${escapeHtml(fieldId)}" name="${escapeHtml(inputName)}" type="${escapeHtml(inputType)}" class="form-control" value="${escapeHtml(oldValue)}" ${required ? 'required' : ''} placeholder="${escapeHtml(placeholder)}" />`;
                }

                if (name === 'security') {
                    html += `<p id="pnr-help" class="mt-1 text-sm"></p>`;
                }

                const fieldError = fieldErrors[name]?.[0];
                if (fieldError) {
                    html += `<p class="mt-1 text-sm text-red-600 dark:text-red-400">${escapeHtml(fieldError)}</p>`;
                }

                wrapper.innerHTML = html;
                return wrapper;
            }

            function normalizeInputType(type) {
                if (['text', 'email', 'date', 'number', 'tel', 'password'].includes(type)) {
                    return type;
                }

                return 'text';
            }

            // Attach/detach the flatpickr calendar on the security field depending on
            // whether it currently represents a date of birth.
            function setSecurityDatePicker(input, enable) {
                if (enable) {
                    if (!input._flatpickr) {
                        flatpickr(input, {
                            dateFormat: 'Y-m-d',
                            allowInput: true,
                            disableMobile: true,
                            static: true,
                            locale: { firstDayOfWeek: 1 },
                        });
                    }
                } else if (input._flatpickr) {
                    input._flatpickr.destroy();
                }
            }

            function bindSecurityBehavior() {
                const checkbox = document.getElementById('hasPersonalId');
                const securityInput = document.getElementById('security');

                if (!securityInput) {
                    return;
                }

                if (checkbox) {
                    checkbox.checked = hasPersonalId;
                }

                updateSecurityFieldMode();
                if (!securityInput.dataset.validationBound) {
                    securityInput.addEventListener('input', validateSecurityField);
                    securityInput.addEventListener('blur', validateSecurityField);
                    securityInput.dataset.validationBound = '1';
                }
            }

            function updateSecurityFieldMode() {
                const securityInput = document.getElementById('security');
                const securityLabel = document.getElementById('security-label');
                const pnrHelp = document.getElementById('pnr-help');

                if (!securityInput) {
                    return;
                }

                if (!hasPersonalId) {
                    securityInput.removeAttribute('inputmode');
                    securityInput.placeholder = '{{ __('Select date of birth') }}';
                    setSecurityDatePicker(securityInput, true);
                    if (securityLabel) {
                        securityLabel.innerHTML = 'Date of Birth <span class="text-red-500">*</span>';
                    }
                    if (pnrHelp) {
                        pnrHelp.textContent = '';
                    }
                    return;
                }

                setSecurityDatePicker(securityInput, false);
                securityInput.setAttribute('inputmode', 'numeric');
                securityInput.placeholder = 'YYMMDD-XXXX';
                if (securityLabel) {
                    securityLabel.innerHTML = 'Personal identification number <span class="text-red-500">*</span>';
                }
            }

            function validateSecurityField() {
                const securityInput = document.getElementById('security');
                const pnrHelp = document.getElementById('pnr-help');

                if (!securityInput) {
                    return true;
                }

                securityInput.classList.remove('border-red-500');
                if (pnrHelp) {
                    pnrHelp.classList.remove('text-red-600', 'text-green-600');
                }

                if (!hasPersonalId) {
                    if (securityInput.value.trim() === '') {
                        if (pnrHelp) {
                            pnrHelp.textContent = 'Date of birth is required';
                            pnrHelp.classList.add('text-red-600');
                        }
                        securityInput.classList.add('border-red-500');
                        return false;
                    }

                    if (pnrHelp) {
                        pnrHelp.textContent = '';
                    }
                    return true;
                }

                const validation = validatePNR(securityInput.value);
                if (!validation.isValid) {
                    if (pnrHelp) {
                        pnrHelp.textContent = validation.message;
                        pnrHelp.classList.add('text-red-600');
                    }
                    securityInput.classList.add('border-red-500');
                    return false;
                }

                if (pnrHelp) {
                    pnrHelp.textContent = validation.message;
                    pnrHelp.classList.add('text-green-600');
                }

                return true;
            }

            function validatePNR(pnr) {
                if (!pnr.trim()) {
                    return { isValid: false, message: 'Personal identification number is required' };
                }

                const pnrPattern = /^(\d{6})-?(\d{4})$/;
                const match = pnr.match(pnrPattern);
                if (!match) {
                    return { isValid: false, message: 'Required format is YYMMDD-XXXX or YYMMDDXXXX' };
                }

                const cleanPNR = match[1] + match[2];
                const year = Number.parseInt(cleanPNR.substring(0, 2), 10);
                const month = Number.parseInt(cleanPNR.substring(2, 4), 10);
                const day = Number.parseInt(cleanPNR.substring(4, 6), 10);

                if (year < 0 || year > 99) {
                    return { isValid: false, message: 'Invalid year in Personal identification number' };
                }
                if (month < 1 || month > 12) {
                    return { isValid: false, message: 'Invalid month in Personal identification number (01-12)' };
                }
                if (day < 1 || day > 31) {
                    return { isValid: false, message: 'Invalid day in Personal identification number (01-31)' };
                }

                return { isValid: true, message: 'Personal identification number is valid' };
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }
        });
    </script>
</x-layouts.backend-layout>
