<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    <link rel="icon" href="{{ config('settings.site_favicon') ?? asset('favicon.ico') }}" type="image/x-icon">

    @include('backend.layouts.partials.theme-colors')
    @yield('before_vite_build')

    @livewireStyles
    @viteReactRefresh
    @vite(['resources/js/app.js', 'resources/css/app.css'], 'build')

    <!-- Select2 CSS for enhanced multi-selects (used with .js-multiselect) -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    @stack('styles')
    @yield('before_head')

    @if (!empty(config('settings.global_custom_css')))
    <style>
        {!! config('settings.global_custom_css') !!}
    </style>
    @endif

    @include('backend.layouts.partials.integration-scripts')

    {!! Hook::applyFilters(AdminFilterHook::ADMIN_HEAD, '') !!}
</head>

<body x-data="{
    page: 'ecommerce',
    darkMode: false,
    stickyMenu: false,
    sidebarToggle: $persist(false),
    scrollTop: false
}"
x-init="
    darkMode = JSON.parse(localStorage.getItem('darkMode')) ?? false;
    $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)));
    $watch('sidebarToggle', value => localStorage.setItem('sidebarToggle', JSON.stringify(value)));
    
    // Add loaded class for smooth fade-in
    $nextTick(() => {
        document.querySelector('.app-container').classList.add('loaded');
    });
"
:class="{ 'dark bg-gray-900': darkMode === true }">

    <!-- Page Wrapper with smooth fade-in -->
    <div class="app-container flex h-screen overflow-hidden">
        @include('backend.layouts.partials.sidebar.logo')

        <!-- Content Area -->
        <div class="relative flex flex-col flex-1 overflow-x-hidden overflow-y-auto bg-body dark:bg-gray-900">
            <!-- Small Device Overlay -->
            <div @click="sidebarToggle = false" :class="sidebarToggle ? 'block lg:hidden' : 'hidden'"
                class="fixed w-full h-screen z-9 bg-gray-900/50"></div>
            <!-- End Small Device Overlay -->

            @include('backend.layouts.partials.header.index')

            <!-- Main Content -->
            <main>
                @hasSection('admin-content')
                    @yield('admin-content')
                @else
                    @isset($slot) {{ $slot }} @endisset
                @endif
            </main>
            <!-- End Main Content -->
        </div>
    </div>

    <x-toast-notifications />

    {!! Hook::applyFilters(AdminFilterHook::ADMIN_FOOTER_BEFORE, '') !!}

    <!-- jQuery + Select2 (used globally for .js-multiselect fields) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
            crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Initialize Select2 multi-selects when present -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.jQuery && $.fn.select2) {
                $('.js-multiselect').select2({
                    width: '100%',
                    closeOnSelect: false,
                    placeholder: '{{ __("Select options") }}'
                });
            }
        });
    </script>

    {{-- ── Interview template generation libraries (lazy-loaded from CDN) ── --}}
    <script src="https://unpkg.com/pizzip@3.1.4/dist/pizzip.js"></script>
    <script src="https://unpkg.com/pizzip@3.1.4/dist/pizzip-utils.js"></script>
    <script src="https://unpkg.com/docxtemplater@3.44.0/build/docxtemplater.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/file-saver@2.0.5/dist/FileSaver.min.js"></script>
    <script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
    <script>
    /**
     * RecwayTemplateGenerator — shared client-side DOCX/PDF generator.
     * Mirrors pdf_gene / pdf_gene_ellevio / pdf_gene_timra from admin2/pages/invoice.php
     */
    window.RecwayTemplateGenerator = (function () {

        function fmtCheck(val) {
            if (val == 1) return "Ja ☒\tNej ☐";
            if (val == 0) return "Ja ☐\tNej ☒";
            return "Ja ☐\tNej ☐";
        }

        function nowDate() {
            const d = new Date();
            const dd = String(d.getDate()).padStart(2,'0');
            const mm = String(d.getMonth()+1).padStart(2,'0');
            return d.getFullYear() + '-' + mm + '-' + dd;
        }

        function nowTime() {
            const d = new Date();
            return String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0') + ':' + String(d.getSeconds()).padStart(2,'0');
        }

        function initials(name, surname) {
            return (name.trim().charAt(0) + surname.trim().charAt(0)).toUpperCase();
        }

        function getMeta(metaData, ...keys) {
            if (!metaData) return 'N/A';
            let data = metaData;
            if (typeof data === 'string') { try { data = JSON.parse(data); } catch(e) { return 'N/A'; } }
            for (const key of keys) {
                const found = Object.keys(data).find(k => k.trim() === key.trim());
                if (found && data[found] && data[found] !== '-' && data[found] !== 'NA') return data[found];
            }
            return 'N/A';
        }

        async function loadFile(url) {
            return new Promise((resolve, reject) => {
                PizZipUtils.getBinaryContent(url, (err, content) => {
                    if (err) reject(err); else resolve(content);
                });
            });
        }

        function buildDocxPayload(r, currentDate) {
            const ini = initials(r.name, r.surname);
            return {
                place_inter_date: r.booked || '',
                social_security_number: r.security || '',
                staff: r.staff || 'N/A',
                time: nowTime(),
                vasc_id: r.vasc_id || 'N/A',
                name_ini: ini,
                ord_id: r.order_id || 'N/A',
                inv_ref: r.referensperson || 'N/A',
                company: r.cus_company || '',
                bk_date: r.background_check_date || '',
                customer_name: r.cus_name || 'N/A',
                eco_check: fmtCheck(r.economy),
                soc_check: fmtCheck(r.social),
                cri_check: fmtCheck(r.criminal_record),
                apply_position: getMeta(r.meta_data, 'Is currently applying for the position of and If this is a consultant transition please specify'),
                e_or_c: getMeta(r.meta_data, 'Employee or consultant?'),
                srs: getMeta(r.meta_data, 'This interview is suggested in the SRS portal?'),
                rapport_id: getMeta(r.meta_data, 'Report-ID for the background check from SRS'),
                current_date: currentDate,
                inter_date: currentDate,
                place: r.place_name || 'N/A',
            };
        }

        function spiTemplatePath(r, cusCompany, serviceCatId) {
            const base = '/templates/';
            let temp = '';
            if (r.status == 35) {
                temp = base + 'Follow_up_template.docx';
            } else if (r.status == 51) {
                temp = base + 'Exit_Interview.docx';
            } else {
                if (cusCompany == 'Scania') {
                    temp = base + 'Scania_interview_template.docx';
                } else if (
                    (cusCompany == 'Volvo Cars' || cusCompany == 'Volvodemo') &&
                    (r.interview_id == 63 || r.interview_id == 64)
                ) {
                    temp = base + 'Kompatibilitet_interview_template.docx';
                } else {
                    temp = base + 'default_interview_template.docx';
                }
            }
            return temp;
        }

        async function generateDocx(templateUrl, payload, filename) {
            const content = await loadFile(templateUrl);
            const zip = new PizZip(content);
            const doc = new docxtemplater(zip, { paragraphLoop: true, linebreaks: true });
            doc.render(payload);
            const blob = doc.getZip().generate({ type: 'blob', mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', compression: 'DEFLATE' });
            saveAs(blob, filename);
        }

        async function generateEllevioPdf(r) {
            const existingPdfBytes = await fetch('/templates/Eliviio.pdf').then(res => res.arrayBuffer());
            const pdfDoc = await PDFLib.PDFDocument.load(existingPdfBytes);
            const form = pdfDoc.getForm();
            const fields = form.getFields();
            const safe = v => (v == null || v === undefined) ? '' : String(v);

            // Helper to fill by name if it exists
            function fill(name, value) {
                try {
                    const f = form.getTextField(name);
                    f.setText(safe(value));
                } catch(e) { /* field may not exist */ }
            }

            fill('order_id', r.order_id);
            fill('vasc_id', r.vasc_id);
            fill('ssn', r.security);
            fill('name', r.name + ' ' + r.surname);
            fill('company', r.cus_company);
            fill('staff', r.staff);
            fill('interview_date', r.booked);
            fill('customer_name', r.cus_name);
            fill('date', nowDate());

            const pdfBytes = await pdfDoc.save();
            const blob = new Blob([pdfBytes], { type: 'application/pdf' });
            const ini = initials(r.name, r.surname);
            const vasc = r.vasc_id ? r.vasc_id + '_' : '';
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `Ellevio_${r.order_id}_${vasc}${ini}_${r.booked || nowDate()}.pdf`;
            link.click();
        }

        async function postHistory(url, type, csrf) {
            await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ type })
            });
        }

        return {
            async generate(type, dataUrl, historyUrl, cusCompany, serviceCatId, csrf) {
                const resp = await fetch(dataUrl, { headers: { 'Accept': 'application/json' } });
                if (!resp.ok) throw new Error('Failed to fetch candidate data');
                const r = await resp.json();
                const currentDate = nowDate();
                const ini = initials(r.name, r.surname);
                const vasc = r.vasc_id ? r.vasc_id + '_' : '';

                await postHistory(historyUrl, type, csrf);

                if (type === 'spi') {
                    const tmpl = spiTemplatePath(r, cusCompany, serviceCatId);
                    const payload = buildDocxPayload(r, currentDate);
                    const filename = `${r.order_id}_${vasc}${ini}_${r.booked || currentDate}.docx`;
                    await generateDocx(tmpl, payload, filename);
                } else if (type === 'ellevio') {
                    await generateEllevioPdf(r);
                } else if (type === 'timra') {
                    const tmpl = '/templates/Timrå-Referenstagning-grundutredning.docx';
                    const payload = buildDocxPayload(r, currentDate);
                    const filename = `Timra_${r.order_id}_${vasc}${ini}_${currentDate}.docx`;
                    await generateDocx(tmpl, payload, filename);
                }
            }
        };
    })();
    </script>

    @stack('scripts')

    @if (!empty(config('settings.global_custom_js')))
    <script>
        {!! config('settings.global_custom_js') !!}
    </script>
    @endif

    @livewireScriptConfig

    {!! Hook::applyFilters(AdminFilterHook::ADMIN_FOOTER_AFTER, '') !!}
</body>
</html>
