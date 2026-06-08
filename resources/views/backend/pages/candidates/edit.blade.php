<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">
    @php
        $prefix = request()->routeIs('staff.*') ? 'staff' : 'admin';
    @endphp

    <form
        method="POST"
        action="{{ route($prefix . '.candidates.update', $candidate->id) }}"
        enctype="multipart/form-data"
        class="space-y-6"
    >
        @csrf
        @method('PUT')

        {{-- ORDER SUMMARY BANNER --}}
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-wrap items-center gap-4 text-sm">
                <span class="font-mono font-semibold text-gray-800 dark:text-gray-100">
                    {{ __('Order') }}: <span class="text-indigo-600 dark:text-indigo-400">{{ $candidate->order_id }}</span>
                </span>
                <span class="text-gray-500 dark:text-gray-400">
                    {{ __('Customer') }}: <strong class="text-gray-800 dark:text-gray-100">{{ $candidate->customer?->user?->name ?? '-' }}</strong>
                </span>
                <span class="text-gray-500 dark:text-gray-400">
                    {{ __('Created') }}: <strong class="text-gray-800 dark:text-gray-100">{{ $candidate->created_at?->format('d M Y') ?? $candidate->created }}</strong>
                </span>
                @if ($candidate->statusRelation)
                    <span
                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium text-white"
                        style="background-color: {{ $candidate->statusRelation->color ?: '#6b7280' }}"
                    >
                        {{ $candidate->statusRelation->status }}
                    </span>
                @endif
            </div>
            <div class="flex items-center gap-3">
                <a
                    href="{{ route($prefix . '.candidates.bk-report.edit', $candidate->id) }}"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-4 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-red-700"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 16 16" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 2h6l4 4v8a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1Z"/>
                        <path stroke-linecap="round" d="M9 2v4h4M6 9h4M6 12h4"/>
                    </svg>
                    {{ __('BK Report') }}
                </a>
                <a
                    href="{{ route($prefix . '.candidates.index') }}"
                    class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    &larr; {{ __('Back to Candidates') }}
                </a>
            </div>
        </div>

        {{-- SECTION: PERSONAL INFO --}}
        <x-card>
            <x-slot name="header">{{ __('Personal Information') }}</x-slot>

            <div class="grid gap-4 md:grid-cols-2">

                <div>
                    <label for="name" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('First Name') }} <span class="text-red-500">*</span>
                    </label>
                    <input id="name" name="name" type="text" required
                        value="{{ old('name', $candidate->name) }}"
                        class="form-control" />
                    @error('name')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="surname" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Surname') }} <span class="text-red-500">*</span>
                    </label>
                    <input id="surname" name="surname" type="text" required
                        value="{{ old('surname', $candidate->surname) }}"
                        class="form-control" />
                    @error('surname')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Email') }} <span class="text-red-500">*</span>
                    </label>
                    <input id="email" name="email" type="email" required
                        value="{{ old('email', $candidate->email) }}"
                        class="form-control" />
                    @error('email')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="phone" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Phone') }} <span class="text-red-500">*</span>
                    </label>
                    <input id="phone" name="phone" type="text" required
                        value="{{ old('phone', $candidate->phone) }}"
                        class="form-control" />
                    @error('phone')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="security" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Security / Date of Birth') }} <span class="text-red-500">*</span>
                    </label>
                    <input id="security" name="security" type="text" required
                        value="{{ old('security', $candidate->security) }}"
                        class="form-control" placeholder="YYMMDD-XXXX" />
                    @error('security')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="vasc_id" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('VASC ID') }}
                    </label>
                    <input id="vasc_id" name="vasc_id" type="text"
                        value="{{ old('vasc_id', $candidate->vasc_id) }}"
                        class="form-control" />
                    @error('vasc_id')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

            </div>
        </x-card>

        {{-- SECTION: SERVICE & ASSIGNMENT --}}
        <x-card>
            <x-slot name="header">{{ __('Service & Assignment') }}</x-slot>

            <div class="grid gap-4 md:grid-cols-2">

                <div>
                    <label for="interview_id" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Service Type') }} <span class="text-red-500">*</span>
                    </label>
                    <select id="interview_id" name="interview_id" class="form-control" required>
                        <option value="">{{ __('Select service type') }}</option>
                        @foreach ($serviceTypes as $serviceType)
                            <option value="{{ $serviceType->id }}"
                                @selected(old('interview_id', $candidate->interview_id) == $serviceType->id)>
                                {{ $serviceType->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('interview_id')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="staff_id" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Assigned Staff') }}
                    </label>
                    <select id="staff_id" name="staff_id" class="form-control">
                        <option value="">{{ __('Not assigned') }}</option>
                        @foreach ($staff as $staffMember)
                            <option value="{{ $staffMember->id }}"
                                @selected(old('staff_id', $candidate->staff_id) == $staffMember->id)>
                                {{ $staffMember->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('staff_id')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="status" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Status') }}
                    </label>
                    <select id="status" name="status" class="form-control">
                        <option value="">{{ __('Select status') }}</option>
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption->id }}"
                                @selected(old('status', $candidate->status) == $statusOption->id)>
                                {{ $statusOption->status }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="place" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Place') }}
                    </label>
                    <select id="place" name="place" class="form-control">
                        <option value="">{{ __('Select place') }}</option>
                        @foreach ($places as $place)
                            <option value="{{ $place->id }}"
                                @selected(old('place', $candidate->place) == $place->id)>
                                {{ $place->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('place')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="country" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Country') }}
                    </label>
                    <input id="country" name="country" type="text"
                        value="{{ old('country', $candidate->country) }}"
                        class="form-control" />
                    @error('country')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="booked" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Interview Date') }}
                    </label>
                    <x-inputs.date-picker id="booked" name="booked"
                        :value="$candidate->booked?->format('Y-m-d')" />
                    @error('booked')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="background_check_date" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Background Check Date') }}
                    </label>
                    <x-inputs.date-picker id="background_check_date" name="background_check_date"
                        :value="$candidate->background_check_date?->format('Y-m-d')" />
                    @error('background_check_date')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="delivery_date" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Delivery Date') }}
                    </label>
                    <x-inputs.date-picker id="delivery_date" name="delivery_date"
                        :value="$candidate->delivery_date?->format('Y-m-d')" />
                    @error('delivery_date')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ── Combine Interview (BK → Security) ── --}}
                @php
                    $customerCombineBkIds = array_filter(
                        array_map('trim', explode(',', $candidate->customer?->combine_bk_and_security ?? ''))
                    );
                    $showCombinePicker = ! empty($customerCombineBkIds)
                        && in_array((string) $candidate->interview_id, $customerCombineBkIds, true);
                @endphp
                @if ($showCombinePicker)
                    <div class="md:col-span-2">
                        <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-800 dark:bg-indigo-900/20">
                            <div class="mb-2 flex items-center gap-2">
                                <svg class="h-4 w-4 shrink-0 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                </svg>
                                <span class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">
                                    {{ __('Combine Interview Target') }}
                                </span>
                            </div>
                            <p class="mb-3 text-xs text-indigo-600 dark:text-indigo-400">
                                {{ __('This candidate is on a Background Check service. When a combine-trigger status is set, the candidate will automatically transfer to the security-interview service selected below.') }}
                            </p>
                            <div>
                                <label for="combine_interview_id" class="mb-1 block text-xs font-medium text-indigo-700 dark:text-indigo-300">
                                    {{ __('Target Security Interview Service') }}
                                    @if ($candidate->customer?->combine_interview_id || $candidate->customer?->combine_interview_service)
                                        <span class="ml-1 text-indigo-400">({{ __('customer default: ') }}
                                            {{ \App\Models\ServiceType::find($candidate->customer?->combine_interview_id ?? $candidate->customer?->combine_interview_service)?->name ?? __('not set') }})
                                        </span>
                                    @endif
                                </label>
                                <select id="combine_interview_id" name="combine_interview_id" class="form-control text-sm">
                                    <option value="">{{ __('— Use customer default —') }}</option>
                                    @foreach ($serviceTypes->whereNotIn('id', $customerCombineBkIds) as $st)
                                        <option value="{{ $st->id }}"
                                            @selected(old('combine_interview_id', $candidate->combine_interview_id) == $st->id)>
                                            {{ $st->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('combine_interview_id')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </x-card>

        {{-- SECTION: BACKGROUND CHECKS --}}
        <x-card>
            <x-slot name="header">{{ __('Background Check Results') }}</x-slot>

            <div class="grid gap-6 md:grid-cols-3">

                @php
                    $bgFields = [
                        ['key' => 'economy',        'label' => __('Economy')],
                        ['key' => 'criminal_record', 'label' => __('Criminal Record')],
                        ['key' => 'social',          'label' => __('Social')],
                    ];
                @endphp

                @foreach ($bgFields as $field)
                    @php $current = old($field['key'], $candidate->{$field['key']}); @endphp
                    <div>
                        <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">{{ $field['label'] }}</p>
                        <div class="flex flex-wrap gap-4">
                            <label class="inline-flex cursor-pointer items-center gap-2 text-sm">
                                <input type="radio" name="{{ $field['key'] }}" value="-1"
                                    class="text-yellow-500 focus:ring-yellow-400"
                                    @checked((string)$current === '-1' || $current === null) />
                                <span class="text-yellow-600 dark:text-yellow-400">{{ __('Pending') }}</span>
                            </label>
                            <label class="inline-flex cursor-pointer items-center gap-2 text-sm">
                                <input type="radio" name="{{ $field['key'] }}" value="0"
                                    class="text-green-500 focus:ring-green-400"
                                    @checked((string)$current === '0') />
                                <span class="text-green-600 dark:text-green-400">{{ __('Clear') }}</span>
                            </label>
                            <label class="inline-flex cursor-pointer items-center gap-2 text-sm">
                                <input type="radio" name="{{ $field['key'] }}" value="1"
                                    class="text-red-500 focus:ring-red-400"
                                    @checked((string)$current === '1') />
                                <span class="text-red-600 dark:text-red-400">{{ __('Found') }}</span>
                            </label>
                        </div>
                        @error($field['key'])
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach

            </div>
        </x-card>

        {{-- SECTION: INVOICE --}}
        <x-card>
            <x-slot name="header">{{ __('Invoice') }}</x-slot>

            <div class="grid gap-4 md:grid-cols-3">

                <div class="flex items-center gap-3">
                    <input id="invoice_sent" name="invoice_sent" type="checkbox" value="1"
                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        @checked(old('invoice_sent', $candidate->invoice_sent)) />
                    <label for="invoice_sent" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Invoice Sent') }}
                    </label>
                </div>

                <div class="flex items-center gap-3">
                    <input id="invoice_genrated" name="invoice_genrated" type="checkbox" value="1"
                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        @checked(old('invoice_genrated', $candidate->invoice_genrated)) />
                    <label for="invoice_genrated" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Invoice Generated') }}
                    </label>
                </div>

                <div>
                    <label for="invoice_date" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Invoice Date') }}
                    </label>
                    <x-inputs.date-picker id="invoice_date" name="invoice_date"
                        :value="$candidate->invoice_date?->format('Y-m-d')" />
                    @error('invoice_date')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Costs (used for auto-invoice amount calculation) --}}
                <div>
                    <label for="service_cost" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Service Cost (kr)') }}
                    </label>
                    <input id="service_cost" name="service_cost" type="number" step="0.01" min="0"
                        value="{{ old('service_cost', $candidate->service_cost) }}"
                        class="form-control" placeholder="0.00" />
                    @error('service_cost')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="travel_cost" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Travel Cost (kr)') }}
                    </label>
                    <input id="travel_cost" name="travel_cost" type="number" step="0.01" min="0"
                        value="{{ old('travel_cost', $candidate->travel_cost) }}"
                        class="form-control" placeholder="0.00" />
                    @error('travel_cost')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

            </div>
        </x-card>

        {{-- SECTION: BILLING NOTES --}}
        <x-card>
            <x-slot name="header">{{ __('Billing & Notes') }}</x-slot>

            @if (!empty($billingDisplayFields))
                {{-- Form-builder billing section: show labels as configured per customer/service --}}
                <div class="space-y-4">
                    @foreach ($billingDisplayFields as [$blabel, $bvalue])
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $blabel }}</p>
                            <p class="mt-0.5 text-gray-800 dark:text-gray-200">{{ $bvalue ?: '—' }}</p>
                        </div>
                    @endforeach
                </div>
                {{-- Keep the editable fields hidden (submitted on save with current values) --}}
                <input type="hidden" name="referensperson" value="{{ $candidate->referensperson }}">
                <input type="hidden" name="reference"      value="{{ $candidate->reference }}">
                <input type="hidden" name="comment"        value="{{ $candidate->comment }}">
                <input type="hidden" name="note"           value="{{ $candidate->note }}">
            @else
                {{-- Default billing section (no form builder configured) --}}
                <div class="grid gap-4 md:grid-cols-2">

                    <div>
                        <label for="referensperson" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Reference (Invoice Recipient)') }}
                        </label>
                        <input id="referensperson" name="referensperson" type="text"
                            value="{{ old('referensperson', $candidate->referensperson) }}"
                            class="form-control" />
                        @error('referensperson')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="reference" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Reference') }}
                        </label>
                        <input id="reference" name="reference" type="text"
                            value="{{ old('reference', $candidate->reference) }}"
                            class="form-control" />
                        @error('reference')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="comment" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Invoice Comment') }}
                        </label>
                        <textarea id="comment" name="comment" rows="3"
                            class="form-control">{{ old('comment', $candidate->comment) }}</textarea>
                        @error('comment')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="note" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Internal Note') }}
                        </label>
                        <textarea id="note" name="note" rows="3"
                            class="form-control">{{ old('note', $candidate->note) }}</textarea>
                        @error('note')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
            @endif
        </x-card>

        {{-- SECTION: DOCUMENTS --}}
        <x-card>
            <x-slot name="header">{{ __('Documents') }}</x-slot>

            {{-- Existing files --}}
            @if (count($existingFiles) > 0)
                <div class="mb-4">
                    <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Existing Documents') }}</p>
                    <ul class="divide-y divide-gray-100 rounded-lg border border-gray-200 dark:divide-gray-700 dark:border-gray-700">
                        @foreach ($existingFiles as $file)
                            <li class="flex items-center justify-between px-4 py-2 text-sm">
                                <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                    <svg class="h-4 w-4 shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M4 4a2 2 0 012-2h4l6 6v8a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/>
                                    </svg>
                                    <a
                                        href="{{ Storage::url('candidates/' . $file) }}"
                                        target="_blank"
                                        class="hover:underline"
                                    >{{ $file }}</a>
                                </div>
                                <label class="inline-flex cursor-pointer items-center gap-1.5 text-xs text-red-600 hover:text-red-800 dark:text-red-400">
                                    <input type="checkbox" name="remove_files[]" value="{{ $file }}"
                                        class="h-3.5 w-3.5 rounded border-gray-300 text-red-600" />
                                    {{ __('Remove') }}
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Upload new files --}}
            <div>
                <label for="files" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('Upload New Documents') }}
                </label>
                <input id="files" name="files[]" type="file"
                    class="form-control"
                    accept="application/pdf"
                    multiple />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('PDF files only. You can select multiple files.') }}
                </p>
                @error('files.*')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </x-card>

        {{-- SECTION: SECURITY / INTERVIEW REPORTS --}}
        @php
            $reportService = app(\App\Services\Candidate\InterviewReportService::class);
            $interviewReports   = $reportService->getReports($candidate);
            $enabledReportTypes = $candidate->customer
                ? $reportService->enabledTypesForCustomer($candidate->customer)
                : [];
            $anyEnabled = count(array_filter($enabledReportTypes)) > 0;
        @endphp
        @if ($anyEnabled)
        {{-- ── GENERATE INTERVIEW TEMPLATES ── --}}
        @php
            $editServiceCatId  = $candidate->serviceType?->service_category_id;
            $editStatusVar     = $candidate->statusRelation?->variable ?? '';
            $editTplEmpty      = empty($candidate->interview_template);

            // Outer gate: service must belong to Interview(1), Follow-up(9), or Exit Interview(10)
            $editIsInterview   = in_array($editServiceCatId, [1, 9, 10]);

            // SPI: outer gate + booked status (IDs 3 / 35 / 51)
            $showEditSpi       = $editIsInterview && $editTplEmpty
                                 && in_array($editStatusVar, ['booked', 'booked_msg_follow']);

            // Ellevio: outer gate + customer flag (no status restriction)
            $showEditEllevio   = $editIsInterview && $editTplEmpty
                                 && ($candidate->customer?->ellevio_report ?? false);

            // Timrå: outer gate + customer flag (no status restriction)
            $showEditTimra     = $editIsInterview && $editTplEmpty
                                 && ($candidate->customer?->timra_report ?? false);
        @endphp
        @if ($showEditSpi || $showEditEllevio || $showEditTimra)
        <x-card>
            <x-slot name="header">{{ __('Generate Interview Templates') }}</x-slot>
            <div x-data="{
                     templateDataUrl: '{{ route($prefix . '.candidates.template-data', $candidate->id) }}',
                     templateHistoryUrl: '{{ route($prefix . '.candidates.template-history', $candidate->id) }}',
                     cusCompany: '{{ e($candidate->customer?->company ?? '') }}',
                     serviceCatId: {{ (int) $editServiceCatId }},
                     csrfToken: '{{ csrf_token() }}',
                     generating: '',
                     showConfirm: false,
                     pendingType: '',

                     openConfirm(type) {
                         this.pendingType = type;
                         this.showConfirm = true;
                     },

                     async confirmGenerate() {
                         this.showConfirm = false;
                         const type = this.pendingType;
                         this.pendingType = '';
                         this.generating = type;
                         try {
                             await window.RecwayTemplateGenerator.generate(
                                 type, this.templateDataUrl, this.templateHistoryUrl,
                                 this.cusCompany, this.serviceCatId, this.csrfToken
                             );
                         } catch(e) {
                             alert('{{ __('Failed to generate template: ') }}' + e.message);
                         } finally {
                             this.generating = '';
                         }
                     }
                 }">

                <div class="flex flex-wrap gap-3">
                    @if ($showEditSpi)
                        <button type="button" @click="openConfirm('spi')" :disabled="generating !== ''"
                            class="inline-flex items-center gap-2 rounded-lg border border-cyan-300 bg-cyan-50 px-4 py-2 text-sm font-medium text-cyan-700 hover:bg-cyan-100 disabled:opacity-50 dark:border-cyan-700 dark:bg-cyan-900/20 dark:text-cyan-300">
                            <iconify-icon icon="lucide:file-down" width="15" x-show="generating !== 'spi'"></iconify-icon>
                            <iconify-icon icon="lucide:loader-circle" width="15" class="animate-spin" x-show="generating === 'spi'"></iconify-icon>
                            {{ __('Generate SPI Template') }}
                        </button>
                    @endif
                    @if ($showEditEllevio)
                        <button type="button" @click="openConfirm('ellevio')" :disabled="generating !== ''"
                            class="inline-flex items-center gap-2 rounded-lg border border-cyan-300 bg-cyan-50 px-4 py-2 text-sm font-medium text-cyan-700 hover:bg-cyan-100 disabled:opacity-50 dark:border-cyan-700 dark:bg-cyan-900/20 dark:text-cyan-300">
                            <iconify-icon icon="lucide:file-down" width="15" x-show="generating !== 'ellevio'"></iconify-icon>
                            <iconify-icon icon="lucide:loader-circle" width="15" class="animate-spin" x-show="generating === 'ellevio'"></iconify-icon>
                            {{ __('Generate Ellevio Template') }}
                        </button>
                    @endif
                    @if ($showEditTimra)
                        <button type="button" @click="openConfirm('timra')" :disabled="generating !== ''"
                            class="inline-flex items-center gap-2 rounded-lg border border-cyan-300 bg-cyan-50 px-4 py-2 text-sm font-medium text-cyan-700 hover:bg-cyan-100 disabled:opacity-50 dark:border-cyan-700 dark:bg-cyan-900/20 dark:text-cyan-300">
                            <iconify-icon icon="lucide:file-down" width="15" x-show="generating !== 'timra'"></iconify-icon>
                            <iconify-icon icon="lucide:loader-circle" width="15" class="animate-spin" x-show="generating === 'timra'"></iconify-icon>
                            {{ __('Generate Timrå Referens') }}
                        </button>
                    @endif
                </div>

                {{-- Confirmation modal --}}
                <div x-show="showConfirm"
                     x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm"
                     @keydown.escape.window="showConfirm = false; pendingType = ''">

                    <div x-show="showConfirm"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="w-full max-w-sm rounded-2xl bg-white px-8 py-8 text-center shadow-2xl dark:bg-gray-800">

                        {{-- Question mark icon --}}
                        <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full border-2 border-blue-200 dark:border-blue-700">
                            <span class="text-3xl font-light text-blue-300 dark:text-blue-400">?</span>
                        </div>

                        {{-- Title --}}
                        <h3 class="mb-4 text-xl font-semibold text-gray-800 dark:text-white">
                            Bekräftelse
                        </h3>

                        {{-- Body --}}
                        <p class="mb-3 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                            Har du läst och tagit del av instruktionen för denna kund innan du genererar rapporten?
                        </p>
                        <p class="mb-3 text-sm text-gray-600 dark:text-gray-300">
                            Instruktionen finns tillgänglig i Pulshub.
                        </p>
                        <p class="mb-7 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                            Rapporten kan endast genereras efter att du har bekräftat att instruktionen är genomläst.
                        </p>

                        {{-- Buttons --}}
                        <div class="flex items-center justify-center gap-3">
                            <button type="button"
                                @click="showConfirm = false; pendingType = ''"
                                class="min-w-[80px] rounded-lg bg-red-500 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2">
                                Nej
                            </button>
                            <button type="button"
                                @click="confirmGenerate()"
                                class="min-w-[80px] rounded-lg bg-blue-500 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">
                                Ja
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </x-card>
        @endif

        <x-card>
            <x-slot name="header">{{ __('Security / Interview Reports') }}</x-slot>

            @if (session('success') && str_contains(session('success'), 'Report'))
                <div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Notice when interview upload is disabled for this customer --}}
            @if ($editIsInterview && empty($enabledReportTypes[\App\Services\Candidate\InterviewReportService::TYPE_SPI]))
                <div class="mb-4 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800 dark:bg-amber-900/20">
                    <iconify-icon icon="lucide:alert-triangle" width="16" class="mt-0.5 shrink-0 text-amber-600 dark:text-amber-400"></iconify-icon>
                    <p class="text-sm text-amber-700 dark:text-amber-300">
                        {{ __('Interview report upload is not enabled for this customer.') }}
                        <a href="{{ route($prefix . '.customers.edit', $candidate->cus_id) }}"
                            class="ml-1 font-semibold underline hover:text-amber-900 dark:hover:text-amber-100">
                            {{ __('Enable "Interview Upload Report" on the customer settings') }}
                        </a>
                        {{ __('to allow uploading SPI reports.') }}
                    </p>
                </div>
            @endif

            <div class="space-y-4">
                @php
                    $reportDefs = [
                        \App\Services\Candidate\InterviewReportService::TYPE_SPI     => __('Upload Interview Report'),
                        \App\Services\Candidate\InterviewReportService::TYPE_ELLEVIO => __('Ellevio Report'),
                        \App\Services\Candidate\InterviewReportService::TYPE_TIMRA   => __('Timrå Reference Report'),
                    ];
                @endphp

                @foreach ($reportDefs as $rType => $rLabel)
                    @if (!empty($enabledReportTypes[$rType]))
                        @php $rFile = $interviewReports[$rType] ?? null; @endphp
                        <div class="rounded-lg border {{ $rFile ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/10' : 'border-gray-200 dark:border-gray-700' }} p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="inline-block h-2.5 w-2.5 rounded-full {{ $rFile ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}"></span>
                                    <span class="text-sm font-semibold {{ $rFile ? 'text-green-700 dark:text-green-400' : 'text-gray-600 dark:text-gray-400' }}">
                                        {{ $rLabel }}
                                    </span>
                                    @if ($rFile)
                                        <span class="text-xs text-green-600 dark:text-green-400">{{ __('Uploaded') }}</span>
                                    @else
                                        <span class="text-xs text-gray-400">{{ __('Not uploaded') }}</span>
                                    @endif
                                </div>

                                @if ($rFile)
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route($prefix . '.candidates.report.download', [$candidate->id, $rType]) }}"
                                            target="_blank"
                                            class="inline-flex items-center rounded border border-green-300 bg-white px-3 py-1.5 text-xs font-medium text-green-700 hover:bg-green-50 dark:border-green-700 dark:bg-transparent dark:text-green-400">
                                            <svg class="mr-1 h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            {{ __('Download') }}
                                        </a>
                                        <form method="POST" action="{{ route($prefix . '.candidates.report.destroy', [$candidate->id, $rType]) }}"
                                            onsubmit="return confirm('{{ __('Delete this report?') }}')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center rounded border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 dark:border-red-800 dark:text-red-400">
                                                {{ __('Delete') }}
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>

                            {{-- Livewire-free upload via the main form is not possible for these (private storage).
                                 Redirect to upload via the candidate popup panel instead. --}}
                            <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                                {{ __('To upload this report, open the candidate panel → Attachments tab.') }}
                            </p>
                        </div>
                    @endif
                @endforeach

                @if ($candidate->customer?->send_security_report)
                    <p class="text-xs text-blue-600 dark:text-blue-400">
                        <svg class="mr-1 inline h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ __('The SPI report is automatically attached to the customer email when status changes to Approved or Denied.') }}
                    </p>
                @endif
            </div>
        </x-card>
        @endif

        {{-- FORM ACTIONS --}}
        <div class="flex items-center justify-between gap-3">
            <button
                type="button"
                onclick="if(confirm('{{ __('Delete this candidate? This cannot be undone.') }}')) { document.getElementById('delete-form').submit(); }"
                class="inline-flex items-center rounded-md border border-red-300 px-4 py-2 text-sm font-medium text-red-700 transition hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20"
            >
                {{ __('Delete Candidate') }}
            </button>

            <div class="flex gap-3">
                <a href="{{ route($prefix . '.candidates.index') }}"
                    class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                    {{ __('Cancel') }}
                </a>
                <button type="submit"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                    {{ __('Save Changes') }}
                </button>
            </div>
        </div>
    </form>

    {{-- Hidden delete form --}}
    <form id="delete-form" method="POST"
        action="{{ route($prefix . '.candidates.destroy', $candidate->id) }}"
        class="hidden">
        @csrf
        @method('DELETE')
    </form>

</x-layouts.backend-layout>
