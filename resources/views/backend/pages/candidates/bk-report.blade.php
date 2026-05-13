<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">

    <div class="mb-4 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ __('BK Report Editor') }}</h2>
            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                {{ __('Edit sections, then Preview in a modal or Download/Submit the PDF.') }}
            </p>
        </div>
        <a href="{{ route($prefix . '.candidates.edit', $candidate->id) }}"
            class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            &larr; {{ __('Back to Candidate') }}
        </a>
    </div>

    <livewire:backend.candidates.bk-report :candidateId="$candidate->id" />

    {{-- Fullscreen preview modal --}}
    <div id="bkPreviewModal"
         class="hidden fixed inset-0 z-50 flex flex-col bg-black/80"
         style="backdrop-filter:blur(2px)">
        <div class="flex items-center justify-between bg-gray-900 px-5 py-3">
            <span class="text-sm font-semibold text-white">{{ __('Report Preview') }}</span>
            <button onclick="document.getElementById('bkPreviewModal').classList.add('hidden');document.getElementById('bkReportFrame').src='';"
                class="rounded px-3 py-1 text-xs font-semibold text-gray-300 hover:bg-gray-700 hover:text-white">
                ✕ {{ __('Close') }}
            </button>
        </div>
        <iframe id="bkReportFrame" src="" class="flex-1 w-full border-0"></iframe>
    </div>

    {{-- Upload status message --}}
    <p id="bkUploadMsg" class="mt-3 text-center text-sm font-medium text-gray-600 dark:text-gray-400"></p>

    {{-- jsPDF scripts — placed inline (not via @push) so they load regardless of component slot context --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <script src="{{ asset('assets/bk-report-generator.js') }}"></script>
    <script>
        window.bkCandidate = {
            id:           {{ $candidate->id }},
            orderId:      @json($candidate->order_id ?? ''),
            name:         @json(trim(($candidate->name ?? '') . ' ' . ($candidate->surname ?? ''))),
            email:        @json($candidate->email ?? ''),
            phone:        @json($candidate->phone ?? ''),
            customerName: @json($candidate->customer?->user?->name ?? $candidate->customer?->company ?? ''),
            company:      @json($candidate->customer?->company ?? ''),
            serviceName:  @json($candidate->serviceType?->name ?? ''),
            ssn:          @json($candidate->vasc_id ?? ''),
            staffName:    @json($candidate->staff?->name ?? ''),
        };

        window.bkUploadUrl = @json(route($prefix . '.candidates.bk-report.upload', $candidate->id));
        window.bkCsrfToken = @json(csrf_token());

        // Pre-load background images as base64 for jsPDF embedding
        (function waitForGenerator() {
            if (window.BkReportGenerator) {
                BkReportGenerator.preloadImages(
                    '{{ asset('assets/reportbg2.webp') }}',
                    '{{ asset('assets/reportbg3.webp') }}'
                );
            } else {
                setTimeout(waitForGenerator, 50);
            }
        })();

        // Called from Alpine @click — reads live Livewire template state then generates PDF
        window.bkGenerate = async function (lang, action) {
            if (!window.__bkWire) {
                alert('Report editor not ready yet. Please wait a moment and try again.');
                return;
            }
            const templates = await window.__bkWire.get('templates');
            if (!templates || !templates[lang]) {
                alert('No template data found for language: ' + lang);
                return;
            }
            BkReportGenerator.generate(
                templates[lang],
                window.bkCandidate,
                action,
                window.bkUploadUrl,
                window.bkCsrfToken
            );
        };
    </script>

</x-layouts.backend-layout>
