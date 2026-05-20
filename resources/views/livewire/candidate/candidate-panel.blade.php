<div wire:loading.class="opacity-60 pointer-events-none" class="flex h-full flex-col transition-opacity">

@if ($candidate)

{{-- =========================================================
     HEADER
     ========================================================= --}}
<div class="flex shrink-0 items-center gap-3 border-b border-gray-200 bg-white px-5 py-4 dark:border-gray-700 dark:bg-gray-800">
    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-sm font-bold uppercase text-white select-none">
        {{ mb_substr($candidate->name ?? '', 0, 1) }}{{ mb_substr($candidate->surname ?? '', 0, 1) }}
    </div>
    <div class="min-w-0 flex-1">
        <p class="truncate font-semibold text-gray-900 dark:text-white">
            {{ $candidate->name }} {{ $candidate->surname }}
        </p>
        <div class="mt-0.5 flex flex-wrap items-center gap-2">
            <span class="font-mono text-xs text-gray-400">#{{ $candidate->order_id }}</span>
            @if ($candidate->statusRelation)
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium text-white"
                    style="background-color:{{ $candidate->statusRelation->color ?: '#6b7280' }}">
                    {{ $candidate->statusRelation->status }}
                </span>
            @endif
            @if ($candidate->customer?->user)
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $candidate->customer->user->name }}
                    @if($candidate->customer->company) — {{ $candidate->customer->company }} @endif
                </span>
            @endif
        </div>
    </div>
    @php $prefix = request()->routeIs('staff.*') ? 'staff' : 'admin'; @endphp
    <a href="{{ route($prefix . '.candidates.edit', $candidate->id) }}" target="_blank"
        class="shrink-0 rounded border border-gray-300 px-3 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
        {{ __('Full Edit') }} ↗
    </a>
</div>

{{-- =========================================================
     MIDDLE: Info tabs (left) + History (right)
     ========================================================= --}}
<div class="flex min-h-0 flex-1 overflow-hidden">

    {{-- LEFT: Info tabs --}}
    <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
        {{-- Tab bar --}}
        <div class="flex shrink-0 overflow-x-auto border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50">
            @php
                $historyCount  = $history instanceof \Illuminate\Support\Collection ? $history->count() : count($history);
                $commentsCount = $comments instanceof \Illuminate\Support\Collection ? $comments->count() : count($comments);
                $infoTabs = [
                    ['id' => 'profile',     'label' => __('Profile')],
                    ['id' => 'billing',     'label' => __('Billing')],
                    ['id' => 'attachments', 'label' => __('Attachments')],
                    ['id' => 'cus_notes',   'label' => __('Additional Customer Notes')],
                    ['id' => 'history',     'label' => __('History') . ($historyCount  > 0 ? " ({$historyCount})"  : '')],
                    ['id' => 'comments',    'label' => __('Comments') . ($commentsCount > 0 ? " ({$commentsCount})" : '')],
                ];
            @endphp
            @foreach ($infoTabs as $tab)
                <button wire:click="$set('activeTab', '{{ $tab['id'] }}')"
                    class="shrink-0 whitespace-nowrap border-b-2 px-4 py-2.5 text-xs font-medium transition
                        {{ $activeTab === $tab['id']
                            ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                            : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </div>

        {{-- Tab content --}}
        <div class="flex-1 overflow-y-auto p-4 text-sm">

            {{-- PROFILE --}}
            @if ($activeTab === 'profile')
                <div class="space-y-2">
                    @php
                        $rows = [
                            [__('SSN / DoB'),              $candidate->security],
                            [__('VASC ID'),                $candidate->vasc_id],
                            [__('Service Type'),           $candidate->serviceType?->name],
                            [__('Interview Date'),         $candidate->booked?->format('d M Y H:i')],
                            [__('Delivery Date'),          $candidate->delivery_date?->format('d M Y')],
                            [__('Background Check Date'),  $candidate->background_check_date?->format('d M Y')],
                            [__('Email'),                  $candidate->email],
                            [__('Phone'),                  $candidate->phone],
                            [__('Place'),                  $candidate->placeRelation?->name],
                            [__('Country'),                $candidate->country],
                            [__('Assigned Staff'),         $candidate->staff?->name],
                        ];
                    @endphp
                    @foreach ($rows as [$label, $value])
                        @if (!empty($value))
                            <div class="flex gap-2">
                                <span class="w-44 shrink-0 text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</span>
                                <span class="text-gray-900 dark:text-gray-100">{{ $value }}</span>
                            </div>
                        @endif
                    @endforeach

                    @if (!empty($candidate->meta_data))
                        @php $meta = json_decode((string)$candidate->meta_data, true); @endphp
                        @if (is_array($meta))
                            @foreach ($meta as $mKey => $mVal)
                                <div class="flex gap-2">
                                    <span class="w-44 shrink-0 text-xs font-medium text-gray-500 dark:text-gray-400">{{ $mKey }}</span>
                                    <span class="text-gray-900 dark:text-gray-100">{{ $mVal }}</span>
                                </div>
                            @endforeach
                        @endif
                    @endif

                    @if (!empty($candidate->note))
                        <div class="mt-3 rounded-md bg-amber-50 px-3 py-2 dark:bg-amber-900/20">
                            <p class="mb-1 text-xs font-medium text-amber-700 dark:text-amber-400">{{ __('Internal Note') }}</p>
                            <p class="text-gray-800 dark:text-gray-200">{{ $candidate->note }}</p>
                        </div>
                    @endif

                    {{-- ── Report status badges (shown when any report type is enabled) ── --}}
                    @php
                        $profilePrefix   = request()->routeIs('staff.*') ? 'staff' : 'admin';
                        $reportBadgeDefs = [
                            \App\Services\Candidate\InterviewReportService::TYPE_SPI     => __('SPI'),
                            \App\Services\Candidate\InterviewReportService::TYPE_ELLEVIO => __('Ellevio'),
                            \App\Services\Candidate\InterviewReportService::TYPE_TIMRA   => __('Timrå'),
                        ];
                        $showBadges = count(array_filter($enabledReportTypes)) > 0;
                    @endphp
                    @if ($showBadges)
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Reports') }}:</span>
                            @foreach ($reportBadgeDefs as $rType => $rLabel)
                                @if (!empty($enabledReportTypes[$rType]))
                                    @php $rUploaded = !empty($interviewReports[$rType]); @endphp
                                    @if ($rUploaded)
                                        <a href="{{ route($profilePrefix . '.candidates.report.download', [$candidate->id, $rType]) }}"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 hover:bg-green-200 dark:bg-green-900/40 dark:text-green-300">
                                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 00-1.414 0L8 12.586 4.707 9.293a1 1 0 00-1.414 1.414l4 4a1 1 0 001.414 0l8-8a1 1 0 000-1.414z" clip-rule="evenodd"/>
                                            </svg>
                                            {{ $rLabel }}
                                        </a>
                                    @else
                                        <button wire:click="$set('activeTab', 'attachments')"
                                            class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-400"
                                            title="{{ __('Click to upload') }}">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            {{ $rLabel }}
                                        </button>
                                    @endif
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>

            {{-- BILLING --}}
            @elseif ($activeTab === 'billing')
                <div class="space-y-4">
                    @if (!empty($billingDisplayFields))
                        {{-- Form-builder billing fields: use labels configured per customer/service --}}
                        @foreach ($billingDisplayFields as [$label, $value])
                            <div>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
                                <p class="mt-0.5 text-gray-900 dark:text-gray-100">{{ $value ?: '—' }}</p>
                            </div>
                        @endforeach
                    @else
                        {{-- Default billing fields (no form builder configured) --}}
                        @foreach ([
                            [__('Invoice Recipient'), $candidate->referensperson],
                            [__('Invoice Reference'), $candidate->reference],
                            [__('Invoice Comment'),   $candidate->comment],
                        ] as [$label, $value])
                            <div>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
                                <p class="mt-0.5 text-gray-900 dark:text-gray-100">{{ $value ?: '—' }}</p>
                            </div>
                        @endforeach
                    @endif
                </div>

            {{-- ATTACHMENTS --}}
            @elseif ($activeTab === 'attachments')
                {{-- CV / Documents --}}
                <div class="mb-5">
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('CV / Documents') }}</h3>
                    @if (count($existingCvFiles) > 0)
                        <ul class="mb-3 divide-y divide-gray-100 rounded-lg border border-gray-200 dark:divide-gray-700 dark:border-gray-700">
                            @foreach ($existingCvFiles as $file)
                                <li class="flex items-center justify-between px-3 py-2 text-xs">
                                    <a href="{{ Storage::url('candidates/' . $file) }}" target="_blank"
                                        class="max-w-xs truncate text-indigo-600 hover:underline dark:text-indigo-400">{{ $file }}</a>
                                    <button wire:click="deleteCvFile('{{ e($file) }}')"
                                        wire:confirm="{{ __('Remove this document?') }}"
                                        class="ml-2 shrink-0 text-red-400 hover:text-red-600">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mb-3 text-xs text-gray-400">{{ __('No documents uploaded.') }}</p>
                    @endif
                    <div class="flex items-center gap-2">
                        <input wire:model="cvFiles" type="file" multiple accept="application/pdf"
                            class="text-xs text-gray-600 dark:text-gray-400 file:mr-2 file:rounded file:border-0 file:bg-indigo-50 file:px-2 file:py-1 file:text-xs file:font-medium file:text-indigo-700 dark:file:bg-indigo-900/30 dark:file:text-indigo-300" />
                        <button wire:click="uploadCvFiles"
                            class="shrink-0 rounded bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">{{ __('Upload') }}</button>
                    </div>
                    @error('cvFiles.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Interview Template --}}
                <div>
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Interview Template') }}</h3>
                    @if (!empty($candidate->interview_template))
                        <a href="{{ Storage::url('candidates/templates/' . $candidate->interview_template) }}" target="_blank"
                            class="block truncate text-xs text-indigo-600 hover:underline dark:text-indigo-400">{{ $candidate->interview_template }}</a>
                    @else
                        <p class="mb-2 text-xs text-gray-400">{{ __('No template uploaded.') }}</p>
                    @endif
                    <div class="mt-2 flex items-center gap-2">
                        <input wire:model="interviewTemplateFile" type="file" accept="application/pdf"
                            class="text-xs text-gray-600 dark:text-gray-400 file:mr-2 file:rounded file:border-0 file:bg-indigo-50 file:px-2 file:py-1 file:text-xs file:font-medium file:text-indigo-700" />
                        <button wire:click="uploadInterviewTemplate"
                            class="shrink-0 rounded bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">{{ __('Upload') }}</button>
                    </div>
                    @error('interviewTemplateFile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- ── GENERATE INTERVIEW TEMPLATES (SPI / Ellevio / Timrå) ── --}}
                @php
                    $genPrefix       = request()->routeIs('staff.*') ? 'staff' : 'admin';
                    $serviceCatId    = $candidate->serviceType?->service_category_id;
                    $statusVariable  = $candidate->statusRelation?->variable ?? '';
                    $templateEmpty   = empty($candidate->interview_template);

                    // Outer gate: service must belong to Interview(1), Follow-up(9), or Exit Interview(10)
                    $isInterviewCat  = in_array($serviceCatId, [1, 9, 10]);

                    // SPI: outer gate + booked status (IDs 3 / 35 / 51 map to these variables)
                    $showSpiGenerate   = $isInterviewCat && $templateEmpty
                                        && in_array($statusVariable, ['booked', 'booked_msg_follow']);

                    // Ellevio: outer gate + customer flag (no status restriction)
                    $showEllevioGen    = $isInterviewCat && $templateEmpty
                                        && ($candidate->customer?->ellevio_report ?? false);

                    // Timrå: outer gate + customer flag (no status restriction)
                    $showTimraGen      = $isInterviewCat && $templateEmpty
                                        && ($candidate->customer?->timra_report ?? false);

                    $anyGenerateButton  = $showSpiGenerate || $showEllevioGen || $showTimraGen;
                    $templateDataUrl    = route($genPrefix . '.candidates.template-data', $candidate->id);
                    $templateHistoryUrl = route($genPrefix . '.candidates.template-history', $candidate->id);
                    $cus_company        = $candidate->customer?->company ?? '';
                @endphp

                @if ($anyGenerateButton)
                    <div class="mt-5 border-t border-gray-200 pt-4 dark:border-gray-700"
                         x-data="{
                             templateDataUrl: '{{ $templateDataUrl }}',
                             templateHistoryUrl: '{{ $templateHistoryUrl }}',
                             cusCompany: '{{ e($cus_company) }}',
                             serviceCatId: {{ (int) $serviceCatId }},
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
                                         type,
                                         this.templateDataUrl,
                                         this.templateHistoryUrl,
                                         this.cusCompany,
                                         this.serviceCatId,
                                         this.csrfToken
                                     );
                                 } catch(e) {
                                     alert('{{ __('Failed to generate template: ') }}' + e.message);
                                 } finally {
                                     this.generating = '';
                                 }
                             }
                         }">

                        <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ __('Generate Interview Templates') }}
                        </h3>

                        <div class="flex flex-wrap gap-2">
                            @if ($showSpiGenerate)
                                <button type="button"
                                    @click="openConfirm('spi')"
                                    :disabled="generating !== ''"
                                    class="inline-flex items-center gap-1.5 rounded border border-cyan-300 bg-cyan-50 px-3 py-1.5 text-xs font-medium text-cyan-700 hover:bg-cyan-100 disabled:opacity-50 dark:border-cyan-700 dark:bg-cyan-900/20 dark:text-cyan-300">
                                    <iconify-icon icon="lucide:file-down" width="13" x-show="generating !== 'spi'"></iconify-icon>
                                    <iconify-icon icon="lucide:loader-circle" width="13" class="animate-spin" x-show="generating === 'spi'"></iconify-icon>
                                    {{ __('Generate SPI Template') }}
                                </button>
                            @endif

                            @if ($showEllevioGen)
                                <button type="button"
                                    @click="openConfirm('ellevio')"
                                    :disabled="generating !== ''"
                                    class="inline-flex items-center gap-1.5 rounded border border-cyan-300 bg-cyan-50 px-3 py-1.5 text-xs font-medium text-cyan-700 hover:bg-cyan-100 disabled:opacity-50 dark:border-cyan-700 dark:bg-cyan-900/20 dark:text-cyan-300">
                                    <iconify-icon icon="lucide:file-down" width="13" x-show="generating !== 'ellevio'"></iconify-icon>
                                    <iconify-icon icon="lucide:loader-circle" width="13" class="animate-spin" x-show="generating === 'ellevio'"></iconify-icon>
                                    {{ __('Generate Ellevio Template') }}
                                </button>
                            @endif

                            @if ($showTimraGen)
                                <button type="button"
                                    @click="openConfirm('timra')"
                                    :disabled="generating !== ''"
                                    class="inline-flex items-center gap-1.5 rounded border border-cyan-300 bg-cyan-50 px-3 py-1.5 text-xs font-medium text-cyan-700 hover:bg-cyan-100 disabled:opacity-50 dark:border-cyan-700 dark:bg-cyan-900/20 dark:text-cyan-300">
                                    <iconify-icon icon="lucide:file-down" width="13" x-show="generating !== 'timra'"></iconify-icon>
                                    <iconify-icon icon="lucide:loader-circle" width="13" class="animate-spin" x-show="generating === 'timra'"></iconify-icon>
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
                @endif

                {{-- ── SECURITY / INTERVIEW REPORTS ── --}}
                @php
                    $prefix = request()->routeIs('staff.*') ? 'staff' : 'admin';
                    $reportDefs = [
                        \App\Services\Candidate\InterviewReportService::TYPE_SPI     => ['label' => __('Upload Interview Report'),    'prop' => 'spiReportFile',     'method' => 'uploadSpiReport'],
                        \App\Services\Candidate\InterviewReportService::TYPE_ELLEVIO => ['label' => __('Ellevio Report'),         'prop' => 'ellevioReportFile', 'method' => 'uploadEllevioReport'],
                        \App\Services\Candidate\InterviewReportService::TYPE_TIMRA   => ['label' => __('Timrå Reference Report'), 'prop' => 'timraReportFile',   'method' => 'uploadTimraReport'],
                    ];
                    $anyReportEnabled = count(array_filter($enabledReportTypes));
                @endphp

                {{-- Notice when interview upload is disabled for this customer --}}
                @if ($isInterviewCat && empty($enabledReportTypes[\App\Services\Candidate\InterviewReportService::TYPE_SPI]))
                    @php $noticeEditUrl = route(($genPrefix ?? (request()->routeIs('staff.*') ? 'staff' : 'admin')) . '.customers.edit', $candidate->cus_id); @endphp
                    <div class="mt-5 border-t border-gray-200 pt-4 dark:border-gray-700">
                        <div class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 dark:border-amber-800 dark:bg-amber-900/20">
                            <iconify-icon icon="lucide:alert-triangle" width="14" class="mt-0.5 shrink-0 text-amber-600 dark:text-amber-400"></iconify-icon>
                            <p class="text-xs text-amber-700 dark:text-amber-300">
                                {{ __('Interview report upload is not enabled for this customer.') }}
                                <a href="{{ $noticeEditUrl }}" target="_blank"
                                    class="ml-1 font-semibold underline hover:text-amber-900 dark:hover:text-amber-100">
                                    {{ __('Enable "Interview Upload Report" on the customer settings') }}
                                </a>
                                {{ __('to allow uploading SPI reports.') }}
                            </p>
                        </div>
                    </div>
                @endif

                @if ($anyReportEnabled)
                    <div class="mt-5 border-t border-gray-200 pt-4 dark:border-gray-700">
                        <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ __('Security / Interview Reports') }}
                            @if ($candidate->customer?->send_security_report)
                                <span class="ml-1.5 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                    {{ __('Sent with email on approved/denied') }}
                                </span>
                            @endif
                        </h3>

                        <div class="space-y-3">
                            @foreach ($reportDefs as $type => $def)
                                @if (!empty($enabledReportTypes[$type]))
                                    @php $uploaded = $interviewReports[$type] ?? null; @endphp
                                    <div class="rounded-lg border {{ $uploaded ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/10' : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800' }} p-3">
                                        <div class="mb-2 flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                {{-- Status dot --}}
                                                <span class="inline-block h-2 w-2 rounded-full {{ $uploaded ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}"></span>
                                                <span class="text-xs font-semibold {{ $uploaded ? 'text-green-700 dark:text-green-400' : 'text-gray-600 dark:text-gray-400' }}">
                                                    {{ $def['label'] }}
                                                    @if ($uploaded)
                                                        <span class="ml-1 font-normal opacity-70">✓ {{ __('Uploaded') }}</span>
                                                    @endif
                                                </span>
                                            </div>

                                            @if ($uploaded)
                                                <div class="flex items-center gap-2">
                                                    {{-- Download --}}
                                                    <a href="{{ route($prefix . '.candidates.report.download', [$candidate->id, $type]) }}"
                                                        class="rounded border border-green-300 bg-white px-2.5 py-0.5 text-xs font-medium text-green-700 hover:bg-green-50 dark:border-green-700 dark:bg-transparent dark:text-green-400"
                                                        target="_blank">
                                                        {{ __('Download') }}
                                                    </a>
                                                    {{-- Delete --}}
                                                    <button wire:click="deleteInterviewReport('{{ $type }}')"
                                                        wire:confirm="{{ __('Delete this report?') }}"
                                                        class="rounded border border-red-200 px-2.5 py-0.5 text-xs text-red-600 hover:bg-red-50 dark:border-red-800 dark:text-red-400">
                                                        {{ __('Delete') }}
                                                    </button>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Upload input --}}
                                        <div class="flex items-center gap-2">
                                            <input wire:model="{{ $def['prop'] }}" type="file"
                                                accept=".pdf,.doc,.docx"
                                                class="min-w-0 text-xs text-gray-600 dark:text-gray-400
                                                    file:mr-2 file:rounded file:border-0 file:bg-indigo-50 file:px-2 file:py-1
                                                    file:text-xs file:font-medium file:text-indigo-700
                                                    dark:file:bg-indigo-900/30 dark:file:text-indigo-300" />
                                            <button wire:click="{{ $def['method'] }}"
                                                class="shrink-0 rounded bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">
                                                {{ $uploaded ? __('Replace') : __('Upload') }}
                                            </button>
                                        </div>
                                        @error($def['prop']) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

            {{-- ADDITIONAL CUSTOMER NOTES --}}
            @elseif ($activeTab === 'cus_notes')
                @if ($additionalCustomers->isNotEmpty())
                    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-xs">
                            <thead class="bg-gray-50 dark:bg-gray-700/40">
                                <tr>
                                    <th class="px-4 py-2.5 text-left font-medium text-gray-500 dark:text-gray-400">{{ __('Name') }}</th>
                                    <th class="px-4 py-2.5 text-left font-medium text-gray-500 dark:text-gray-400">{{ __('Email') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($additionalCustomers as $ac)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="px-4 py-2.5 font-medium text-gray-800 dark:text-gray-200">{{ $ac->name }}</td>
                                        <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400">
                                            @if ($ac->email)
                                                <a href="mailto:{{ $ac->email }}" class="hover:text-indigo-600 hover:underline">{{ $ac->email }}</a>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-2 text-xs text-gray-400">
                        {{ __('These are additional contacts registered under the customer account.') }}
                    </p>
                @else
                    <div class="flex flex-col items-center justify-center py-10 text-center">
                        <svg class="mb-2 h-8 w-8 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <p class="text-sm text-gray-400">{{ __('No additional customers registered for this account.') }}</p>
                    </div>
                @endif

            {{-- HISTORY --}}
            @elseif ($activeTab === 'history')
                {{-- Header row with "Open full page" link --}}
                <div class="mb-3 flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ __('Audit Trail') }}
                        @if ($historyCount > 0)
                            <span class="ml-1 rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                {{ $historyCount }}
                            </span>
                        @endif
                    </p>
                    @php $prefix = request()->routeIs('staff.*') ? 'staff' : 'admin'; @endphp
                    <a href="{{ route($prefix . '.candidates.history', $candidate->id) }}"
                        target="_blank"
                        class="text-xs text-indigo-600 hover:underline dark:text-indigo-400">
                        {{ __('Full history page') }} ↗
                    </a>
                </div>

                {{-- Timeline --}}
                @if ($history->isNotEmpty())
                    <ol class="relative mb-5 border-l border-gray-200 dark:border-gray-700">
                        @foreach ($history as $h)
                            <li class="group mb-4 ml-5" wire:key="hist-{{ $h->id }}">
                                {{-- dot --}}
                                <div class="absolute -left-2.5 mt-0.5 h-4 w-4 rounded-full border-2 border-white bg-indigo-400 dark:border-gray-800 dark:bg-indigo-500"></div>

                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <time class="mb-0.5 block text-xs text-gray-400 dark:text-gray-500">
                                            {{ $h->date_time?->format('d M Y — H:i') }}
                                        </time>
                                        <p class="text-xs font-semibold leading-snug text-gray-800 dark:text-gray-200">
                                            {{ $h->desc }}
                                        </p>
                                        @if (!empty($h->comment))
                                            <p class="mt-0.5 text-xs italic text-gray-500 dark:text-gray-400">
                                                {!! nl2br(e($h->comment)) !!}
                                            </p>
                                        @endif
                                    </div>

                                    {{-- Delete (admin only) --}}
                                    @if ($isAdmin)
                                        <button
                                            wire:click="deleteHistoryEntry({{ $h->id }})"
                                            wire:confirm="{{ __('Delete this history entry? This cannot be undone.') }}"
                                            class="mt-0.5 shrink-0 opacity-0 group-hover:opacity-100 text-red-400 transition hover:text-red-600"
                                            title="{{ __('Delete entry') }}"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <div class="mb-5 flex flex-col items-center justify-center py-6 text-center">
                        <svg class="mb-2 h-8 w-8 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm text-gray-400">{{ __('No history recorded yet.') }}</p>
                    </div>
                @endif

                {{-- Manual entry form (admin only) --}}
                @if ($isAdmin)
                    <div class="rounded-lg border border-dashed border-gray-300 p-4 dark:border-gray-600">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ __('Add Manual Entry') }}
                        </p>
                        <div class="space-y-2">
                            <div>
                                <input
                                    wire:model="manualHistoryDesc"
                                    type="text"
                                    placeholder="{{ __('Description *') }}"
                                    class="form-control text-sm w-full"
                                />
                                @error('manualHistoryDesc')
                                    <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <textarea
                                    wire:model="manualHistoryComment"
                                    rows="2"
                                    placeholder="{{ __('Comment (optional)') }}"
                                    class="form-control text-sm w-full"
                                ></textarea>
                            </div>
                            <div class="flex justify-end">
                                <button
                                    wire:click="addManualHistory"
                                    class="rounded bg-indigo-600 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-700"
                                >
                                    {{ __('Add Entry') }}
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

            {{-- COMMENTS --}}
            @elseif ($activeTab === 'comments')
                <div class="space-y-2">
                    @forelse ($comments as $comment)
                        <div class="rounded-md bg-gray-50 p-3 dark:bg-gray-700/40">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                    {{ $comment->author?->name ?? ucfirst($comment->author_type) }}
                                </span>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-400">{{ $comment->created_at?->format('d M Y H:i') }}</span>
                                    <button wire:click="deleteComment({{ $comment->id }})"
                                        wire:confirm="{{ __('Delete this comment?') }}"
                                        class="text-red-400 hover:text-red-600">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <p class="mt-2 text-sm text-gray-800 dark:text-gray-200">{{ $comment->comment }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400">{{ __('No comments yet.') }}</p>
                    @endforelse

                    <div class="mt-4 border-t border-gray-200 pt-3 dark:border-gray-700">
                        <textarea wire:model="newComment" rows="3"
                            placeholder="{{ __('Write an internal comment...') }}"
                            class="form-control w-full text-sm"></textarea>
                        @error('newComment') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        <div class="mt-2 flex justify-end">
                            <button wire:click="addComment"
                                class="rounded bg-indigo-600 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">
                                {{ __('Add Comment') }}
                            </button>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- RIGHT: Order History (quick-view — latest 8 entries) --}}
    <div class="hidden w-60 shrink-0 overflow-y-auto border-l border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/40 lg:block">
        <div class="p-4">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ __('Recent History') }}
                </h3>
                {{-- Jump to full History tab --}}
                <button
                    wire:click="$set('activeTab', 'history')"
                    class="text-xs text-indigo-600 hover:underline dark:text-indigo-400"
                >{{ __('View all') }}</button>
            </div>

            @php $recentHistory = $history->take(8); @endphp

            @if ($recentHistory->isNotEmpty())
                <ol class="relative border-l border-gray-200 dark:border-gray-700">
                    @foreach ($recentHistory as $h)
                        <li class="mb-3 ml-4">
                            <div class="absolute -left-1.5 mt-1 h-2.5 w-2.5 rounded-full border border-white bg-indigo-400 dark:border-gray-800 dark:bg-indigo-500"></div>
                            <time class="block text-xs text-gray-400 dark:text-gray-500">
                                {{ $h->date_time?->format('d M Y H:i') }}
                            </time>
                            <p class="mt-0.5 text-xs font-medium leading-snug text-gray-700 dark:text-gray-300">
                                {{ $h->desc }}
                            </p>
                            @if (!empty($h->comment))
                                <p class="mt-0.5 text-xs italic text-gray-500 dark:text-gray-400 line-clamp-2">
                                    {{ $h->comment }}
                                </p>
                            @endif
                        </li>
                    @endforeach
                </ol>

                @if ($historyCount > 8)
                    <button
                        wire:click="$set('activeTab', 'history')"
                        class="mt-2 w-full rounded border border-gray-200 py-1 text-center text-xs text-gray-500 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700"
                    >
                        {{ __('+ :n more entries', ['n' => $historyCount - 8]) }}
                    </button>
                @endif
            @else
                <p class="text-xs text-gray-400">{{ __('No history yet.') }}</p>
            @endif
        </div>
    </div>

</div>

{{-- =========================================================
     BOTTOM: Action tabs
     ========================================================= --}}
<div class="shrink-0 border-t border-gray-200 dark:border-gray-700">

    {{-- Action tab bar --}}
    <div class="flex overflow-x-auto border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50">
        @php
            $actionTabs = [
                ['id' => 'records',          'label' => __('Records')],
                ['id' => 'status',           'label' => __('Update Status')],
                ['id' => 'edit_candidate',   'label' => __('Edit Candidate')],
                ['id' => 'bk',               'label' => __('Background Check')],
                ['id' => 'emails',           'label' => __('Emails')],
            ];
            if ($canChangeStaff) {
                array_splice($actionTabs, 1, 0, [['id' => 'staff', 'label' => __('Assign Staff')]]);
            }
        @endphp
        @foreach ($actionTabs as $tab)
            <button wire:click="$set('activeActionTab', '{{ $tab['id'] }}')"
                class="shrink-0 whitespace-nowrap border-b-2 px-4 py-2.5 text-xs font-medium transition
                    {{ $activeActionTab === $tab['id']
                        ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                {{ $tab['label'] }}
            </button>
        @endforeach
    </div>

    {{-- Action content --}}
    <div class="max-h-80 overflow-y-auto p-4">

        {{-- RECORDS --}}
        @if ($activeActionTab === 'records')
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="flex items-center gap-2">
                    <input id="invoice-sent-{{ $candidate->id }}" type="checkbox" @checked($invoiceSent)
                        wire:click="updateInvoiceSent({{ $invoiceSent ? 'false' : 'true' }})"
                        class="h-4 w-4 cursor-pointer rounded border-gray-300 text-indigo-600" />
                    <label for="invoice-sent-{{ $candidate->id }}" class="cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Invoice Sent') }}
                        @if ($invoiceSent && $invoiceDate)
                            <span class="ml-1 text-xs text-gray-400">({{ $invoiceDate }})</span>
                        @endif
                    </label>
                </div>
                <div class="flex items-center gap-2">
                    <input id="reported-{{ $candidate->id }}" type="checkbox" @checked($reportedToSm)
                        wire:click="updateReported({{ $reportedToSm ? 'false' : 'true' }})"
                        class="h-4 w-4 cursor-pointer rounded border-gray-300 text-indigo-600" />
                    <label for="reported-{{ $candidate->id }}" class="cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Reported to SM') }}
                    </label>
                </div>

                @php
                    $bkFields = [
                        ['dbKey' => 'economy',        'prop' => $economy,       'label' => __('Economy')],
                        ['dbKey' => 'criminal_record', 'prop' => $criminalRecord,'label' => __('Criminal Record')],
                        ['dbKey' => 'social',          'prop' => $social,        'label' => __('Social')],
                    ];
                    $bkColors = [
                        '-1' => ['label' => __('Pending'), 'color' => 'text-yellow-600 dark:text-yellow-400'],
                        '0'  => ['label' => __('Clear'),   'color' => 'text-green-600  dark:text-green-400'],
                        '1'  => ['label' => __('Found'),   'color' => 'text-red-600    dark:text-red-400'],
                    ];
                @endphp
                @foreach ($bkFields as $bkField)
                    <div>
                        <p class="mb-1.5 text-xs font-medium text-gray-600 dark:text-gray-400">{{ $bkField['label'] }}</p>
                        <div class="flex flex-wrap gap-3">
                            @foreach ($bkColors as $val => $meta)
                                <label class="inline-flex cursor-pointer items-center gap-1.5 text-xs {{ $meta['color'] }}">
                                    <input type="radio" wire:click="updateBkField('{{ $bkField['dbKey'] }}', '{{ $val }}')"
                                        @checked($bkField['prop'] === $val) class="cursor-pointer" />
                                    {{ $meta['label'] }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

        {{-- ASSIGN STAFF --}}
        @elseif ($activeActionTab === 'staff' && $canChangeStaff)
            <div class="max-w-md space-y-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Staff Member') }}</label>
                    <select wire:model="newStaffId" class="form-control text-sm">
                        <option value="">{{ __('— Not assigned —') }}</option>
                        @foreach ($allStaff as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Comment') }}</label>
                    <textarea wire:model="staffComment" rows="2" class="form-control text-sm"></textarea>
                </div>
                <div class="flex justify-end">
                    <button wire:click="assignStaff"
                        class="rounded bg-indigo-600 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">
                        {{ __('Save Assignment') }}
                    </button>
                </div>
            </div>

        {{-- UPDATE STATUS --}}
        @elseif ($activeActionTab === 'status')
            <div class="max-w-lg space-y-3">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('New Status') }} <span class="text-red-500">*</span></label>
                        <select wire:model.live="newStatusId" class="form-control text-sm">
                            <option value="">{{ __('Select status') }}</option>
                            @foreach ($statuses as $s)
                                <option value="{{ $s->id }}">{{ $s->status }}</option>
                            @endforeach
                        </select>
                        @error('newStatusId') <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Date') }} <span class="text-red-500">*</span></label>
                        <input wire:model="statusDate" type="date" class="form-control text-sm" />
                        @error('statusDate') <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- ── Combine Interview Panel ──────────────────────────────── --}}
                @if ($combineWouldTrigger)
                    <div class="rounded-lg border {{ $combineTargetMissing ? 'border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-900/20' : 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/10' }} p-3">
                        <div class="flex items-start gap-2">
                            @if ($combineTargetMissing)
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                </svg>
                                <div class="flex-1">
                                    <p class="text-xs font-semibold text-amber-700 dark:text-amber-400">
                                        {{ __('Combine Transfer Required') }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-amber-600 dark:text-amber-400">
                                        {{ __('This status triggers a BK → Security interview transfer. Select the target security-interview service below.') }}
                                    </p>
                                </div>
                            @else
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                </svg>
                                <div class="flex-1">
                                    <p class="text-xs font-semibold text-green-700 dark:text-green-400">
                                        {{ __('Combine Transfer Will Trigger') }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-green-600 dark:text-green-400">
                                        {{ __('Candidate will be transferred to:') }}
                                        <strong>{{ $combineTargetService?->name }}</strong>
                                        {{ __('with a fresh Pending status.') }}
                                    </p>
                                </div>
                            @endif
                        </div>

                        {{-- Target service picker (shown when missing OR to allow override) --}}
                        @if ($combineTargetMissing || $combineTargetService)
                            <div class="mt-2">
                                <label class="mb-1 block text-xs font-medium text-amber-700 dark:text-amber-400">
                                    {{ $combineTargetMissing ? __('Target Security Service Type *') : __('Override Target (optional)') }}
                                </label>
                                <select wire:model="combineInterviewId" class="form-control text-xs">
                                    <option value="">{{ $combineTargetMissing ? __('— Select target —') : __('— Use default: ' . ($combineTargetService?->name ?? '') . ' —') }}</option>
                                    @foreach ($combineServiceTypes as $cst)
                                        <option value="{{ $cst->id }}">{{ $cst->name }}</option>
                                    @endforeach
                                </select>
                                @error('combineInterviewId') <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    </div>
                @endif

                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Comment') }}</label>
                    <textarea wire:model="statusComment" rows="2" class="form-control text-sm"></textarea>
                </div>
                <div class="flex items-center justify-between">
                    @if ($combineWouldTrigger)
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            {{ __('After saving, candidate will be moved to the security interview workflow.') }}
                        </p>
                    @else
                        <div></div>
                    @endif
                    <button wire:click="updateStatus"
                        @if($combineTargetMissing && !$combineInterviewId) disabled @endif
                        class="rounded bg-indigo-600 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                        {{ __('Update Status') }}
                    </button>
                </div>
            </div>

        {{-- EDIT CANDIDATE --}}
        @elseif ($activeActionTab === 'edit_candidate')
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('First Name') }} <span class="text-red-500">*</span></label>
                    <input wire:model="editName" type="text" class="form-control text-sm" />
                    @error('editName') <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Surname') }} <span class="text-red-500">*</span></label>
                    <input wire:model="editSurname" type="text" class="form-control text-sm" />
                    @error('editSurname') <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Email') }} <span class="text-red-500">*</span></label>
                    <input wire:model="editEmail" type="email" class="form-control text-sm" />
                    @error('editEmail') <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Phone') }} <span class="text-red-500">*</span></label>
                    <input wire:model="editPhone" type="text" class="form-control text-sm" />
                    @error('editPhone') <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('SSN / Date of Birth') }} <span class="text-red-500">*</span></label>
                    <input wire:model="editSecurity" type="text" placeholder="YYMMDD-XXXX" class="form-control text-sm" />
                    @error('editSecurity') <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('VASC ID') }}</label>
                    <input wire:model="editVascId" type="text" class="form-control text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Service Type') }}</label>
                    <select wire:model="editInterviewId" class="form-control text-sm">
                        <option value="">{{ __('Select') }}</option>
                        @foreach ($serviceTypes as $st)
                            <option value="{{ $st->id }}">{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Staff') }}</label>
                    <select wire:model="editStaffId" class="form-control text-sm">
                        <option value="">{{ __('Not assigned') }}</option>
                        @foreach ($allStaff as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Place') }}</label>
                    <select wire:model="editPlace" class="form-control text-sm">
                        <option value="">{{ __('Select') }}</option>
                        @foreach ($places as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Country') }}</label>
                    <input wire:model="editCountry" type="text" class="form-control text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Interview Date') }}</label>
                    <input wire:model="editBookedDate" type="date" class="form-control text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Background Check Date') }}</label>
                    <input wire:model="editBackgroundCheckDate" type="date" class="form-control text-sm" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Delivery Date') }}</label>
                    <input wire:model="editDeliveryDate" type="date" class="form-control text-sm" />
                </div>
                {{-- Costs --}}
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Service Cost (kr)') }}</label>
                    <input wire:model="editServiceCost" type="number" step="0.01" min="0"
                        class="form-control text-sm" placeholder="0.00" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Travel Cost (kr)') }}</label>
                    <input wire:model="editTravelCost" type="number" step="0.01" min="0"
                        class="form-control text-sm" placeholder="0.00" />
                </div>

                <div class="sm:col-span-2 lg:col-span-3">
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Note') }}</label>
                    <textarea wire:model="editNote" rows="2" class="form-control text-sm"
                        placeholder="{{ __('Internal note about this candidate...') }}"></textarea>
                </div>

                {{-- ── Combine Interview Target ── --}}
                @php
                    $panelCombineBkIds = array_filter(
                        array_map('trim', explode(',', $candidate->customer?->combine_bk_and_security ?? ''))
                    );
                    $showPanelCombinePicker = ! empty($panelCombineBkIds)
                        && in_array((string) ($editInterviewId ?? $candidate->interview_id), $panelCombineBkIds, true);
                @endphp
                @if ($showPanelCombinePicker)
                    <div class="sm:col-span-2 lg:col-span-3">
                        <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-3 dark:border-indigo-800 dark:bg-indigo-900/20">
                            <p class="mb-1.5 text-xs font-semibold text-indigo-700 dark:text-indigo-300">
                                {{ __('Combine Interview Target') }}
                            </p>
                            <p class="mb-2 text-xs text-indigo-600 dark:text-indigo-400">
                                {{ __('When a combine-trigger status is applied, the candidate transfers to this security interview service.') }}
                            </p>
                            <select wire:model="editCombineInterviewId" class="form-control text-xs">
                                <option value="">{{ __('— Use customer default') }}
                                    @if ($combineTargetService)
                                        ({{ $combineTargetService->name }})
                                    @endif
                                    —
                                </option>
                                @foreach ($combineServiceTypes as $cst)
                                    <option value="{{ $cst->id }}">{{ $cst->name }}</option>
                                @endforeach
                            </select>
                            @error('editCombineInterviewId')
                                <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                @endif

                <div class="sm:col-span-2 lg:col-span-3 flex justify-end">
                    <button wire:click="updateCandidate"
                        class="rounded bg-indigo-600 px-5 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">
                        {{ __('Save Changes') }}
                    </button>
                </div>
            </div>

        {{-- BACKGROUND CHECK DOCS --}}
        @elseif ($activeActionTab === 'bk')
            <div class="max-w-lg space-y-4">
                <div class="grid gap-3 sm:grid-cols-3 items-end">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Type') }}</label>
                        <select wire:model="bkFileFor" class="form-control text-sm">
                            <option value="1">{{ __('Economy') }}</option>
                            <option value="2">{{ __('Criminal Record') }}</option>
                            <option value="3">{{ __('Social Media') }}</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('PDF File') }}</label>
                        <div class="flex items-center gap-2">
                            <input wire:model="bkFile" type="file" accept="application/pdf"
                                class="min-w-0 text-xs text-gray-600 file:mr-2 file:rounded file:border-0 file:bg-indigo-50 file:px-2 file:py-1 file:text-xs file:font-medium file:text-indigo-700" />
                            <button wire:click="uploadBkFile"
                                class="shrink-0 rounded bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">{{ __('Upload') }}</button>
                        </div>
                        @error('bkFile') <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                @if ($bkFiles->isNotEmpty())
                    <table class="w-full border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-gray-200 text-left dark:border-gray-700">
                                <th class="pb-1 pr-3 text-gray-500">{{ __('Type') }}</th>
                                <th class="pb-1 pr-3 text-gray-500">{{ __('File') }}</th>
                                <th class="pb-1 text-gray-500"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($bkFiles as $bkDoc)
                                <tr>
                                    <td class="py-1.5 pr-3 text-gray-700 dark:text-gray-300">{{ $bkDoc->for_label }}</td>
                                    <td class="py-1.5 pr-3">
                                        <a href="{{ Storage::url('candidates/bk/' . $bkDoc->file_name) }}" target="_blank"
                                            class="truncate text-indigo-600 hover:underline dark:text-indigo-400">{{ $bkDoc->file_name }}</a>
                                    </td>
                                    <td class="py-1.5 text-right">
                                        <button wire:click="deleteBkFile({{ $bkDoc->id }})"
                                            wire:confirm="{{ __('Delete this file?') }}"
                                            class="text-red-400 hover:text-red-600">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-xs text-gray-400">{{ __('No BK documents uploaded.') }}</p>
                @endif
            </div>

        {{-- EMAILS --}}
        @elseif ($activeActionTab === 'emails')
            {{-- Header row --}}
            <div class="mb-3 flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ __('Email Log') }}
                    @if ($emails->isNotEmpty())
                        <span class="ml-1 rounded-full bg-gray-200 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            {{ $emails->count() }}
                        </span>
                    @endif
                </p>
                @php $prefix = request()->routeIs('staff.*') ? 'staff' : 'admin'; @endphp
                <a href="{{ route($prefix . '.candidates.emails', $candidate->id) }}"
                    target="_blank"
                    class="text-xs text-indigo-600 hover:underline dark:text-indigo-400">
                    {{ __('Full email page') }} ↗
                </a>
            </div>

            @if ($emails->isNotEmpty())
                <div class="space-y-2">
                    @foreach ($emails as $email)
                        @php
                            $isResending = $resendEmailId === $email->id;
                            $isPreviewing = $previewEmailId === $email->id;
                            $isResent = str_ends_with($email->msg_type ?? '', '(Resent)');
                        @endphp

                        <div class="rounded-lg border {{ $isResending ? 'border-indigo-300 dark:border-indigo-600' : 'border-gray-200 dark:border-gray-700' }} bg-white dark:bg-gray-800"
                            wire:key="email-{{ $email->id }}">

                            {{-- Email row header --}}
                            <div class="flex items-center gap-2 px-3 py-2">
                                {{-- Type badge --}}
                                @php
                                    $badgeColor = match($email->user_type) {
                                        'Customer'  => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                        'Candidate' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
                                        'Staff'     => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                                        default     => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                    };
                                @endphp
                                <span class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $badgeColor }}">
                                    {{ $email->user_type }}
                                    @if ($isResent)
                                        <span class="ml-1 opacity-60">↩</span>
                                    @endif
                                </span>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-medium text-gray-800 dark:text-gray-200">
                                        {{ $email->subject ?: $email->msg_type }}
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        {{ $email->email }}
                                        <span class="mx-1">·</span>
                                        {{ $email->created_at?->format('d M Y H:i') }}
                                    </p>
                                </div>

                                {{-- Action buttons --}}
                                <div class="flex shrink-0 items-center gap-1">
                                    {{-- Preview toggle --}}
                                    <button
                                        wire:click="toggleEmailPreview({{ $email->id }})"
                                        title="{{ __('Preview body') }}"
                                        class="rounded p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-200
                                            {{ $isPreviewing ? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200' : '' }}"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>

                                    {{-- Resend toggle --}}
                                    <button
                                        wire:click="prepareResend({{ $email->id }})"
                                        title="{{ __('Resend email') }}"
                                        class="rounded p-1 text-gray-400 transition hover:bg-indigo-50 hover:text-indigo-600 dark:hover:bg-indigo-900/30 dark:hover:text-indigo-400
                                            {{ $isResending ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400' : '' }}"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Preview panel (inline HTML) --}}
                            @if ($isPreviewing && !empty($email->text))
                                <div class="border-t border-gray-100 px-3 py-2 dark:border-gray-700">
                                    <p class="mb-1 text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('Email Body') }}</p>
                                    <div class="max-h-56 overflow-y-auto rounded border border-gray-100 bg-gray-50 p-3 text-xs dark:border-gray-700 dark:bg-gray-900">
                                        {!! $email->text !!}
                                    </div>
                                </div>
                            @endif

                            {{-- Resend form (inline edit before send) --}}
                            @if ($isResending)
                                <div class="border-t border-indigo-100 px-3 py-3 dark:border-indigo-900/40">
                                    <p class="mb-2 text-xs font-semibold text-indigo-700 dark:text-indigo-400">
                                        {{ __('Edit & Resend to') }}: <span class="font-normal">{{ $email->email }}</span>
                                    </p>
                                    <div class="space-y-2">
                                        <div>
                                            <label class="mb-0.5 block text-xs text-gray-500">{{ __('Subject') }}</label>
                                            <input wire:model="resendSubject" type="text"
                                                class="form-control text-xs w-full" />
                                            @error('resendSubject') <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="mb-0.5 block text-xs text-gray-500">{{ __('Body') }}</label>
                                            <textarea wire:model="resendBody" rows="6"
                                                class="form-control text-xs w-full font-mono"></textarea>
                                            @error('resendBody') <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <p class="text-xs text-gray-400">
                                                {{ __('A copy will be saved in the email log.') }}
                                            </p>
                                            <div class="flex gap-2">
                                                <button wire:click="cancelResend"
                                                    class="rounded border border-gray-300 px-3 py-1 text-xs text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400">
                                                    {{ __('Cancel') }}
                                                </button>
                                                <button wire:click="executeResend"
                                                    class="rounded bg-indigo-600 px-3 py-1 text-xs font-medium text-white hover:bg-indigo-700">
                                                    <span wire:loading.remove wire:target="executeResend">{{ __('Send') }}</span>
                                                    <span wire:loading wire:target="executeResend">{{ __('Sending…') }}</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-10 text-center">
                    <svg class="mb-2 h-8 w-8 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-sm text-gray-400">{{ __('No emails logged for this candidate.') }}</p>
                </div>
            @endif
        @endif

    </div>
</div>

@else
    <div class="flex flex-1 items-center justify-center">
        <div class="text-center">
            <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <p class="mt-3 text-sm text-gray-400">{{ __('Click a candidate name to view details.') }}</p>
        </div>
    </div>
@endif

</div>
