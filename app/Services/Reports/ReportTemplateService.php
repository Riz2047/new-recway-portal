<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\ServiceType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ReportTemplateService
{
    public function getBackgroundServices(): Collection
    {
        return ServiceType::query()
            ->where('service_category_id', 3)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getCustomerBackgroundServices(int $customerId): Collection
    {
        return ServiceType::query()
            ->select('service_types.id', 'service_types.name')
            ->join('service_type_user', 'service_type_user.service_type_id', '=', 'service_types.id')
            ->where('service_type_user.cus_id', $customerId)
            ->where('service_types.service_category_id', 3)
            ->orderBy('service_types.name')
            ->get();
    }

    public function getStatusesForService(int $serviceId): Collection
    {
        if (! Schema::hasTable('statuses') || ! Schema::hasTable('status_services')) {
            return collect([]);
        }

        return DB::table('status_services as ss')
            ->join('statuses as s', 's.id', '=', 'ss.status_id')
            ->where('ss.service_id', $serviceId)
            ->select('s.id', 's.status', 's.status_sv')
            ->distinct()
            ->orderBy('s.status')
            ->get();
    }

    /** @return array{version:int,sections:array<int,array<string,mixed>>} */
    public function loadTemplate(int $customerId, int $serviceId, string $lang): array
    {
        if (! Schema::hasTable('customer_reports_html')) {
            return $this->defaultPayload($lang);
        }

        $row = DB::table('customer_reports_html')
            ->where('cus_id', $customerId)
            ->where('interview_id', $serviceId)
            ->where('lang', $lang)
            ->first(['report_data']);

        return $this->normalizePayload($row->report_data ?? null, $lang);
    }

    public function templateExists(int $customerId, int $serviceId, string $lang): bool
    {
        if (! Schema::hasTable('customer_reports_html')) {
            return false;
        }

        return DB::table('customer_reports_html')
            ->where('cus_id', $customerId)
            ->where('interview_id', $serviceId)
            ->where('lang', $lang)
            ->exists();
    }

    /** @param array{version?:int,sections?:array<int,array<string,mixed>>} $payload */
    public function saveTemplate(int $customerId, int $serviceId, string $lang, array $payload): void
    {
        if (! Schema::hasTable('customer_reports_html')) {
            return;
        }

        $normalized = $this->normalizePayload($payload);

        DB::table('customer_reports_html')->updateOrInsert(
            [
                'cus_id' => $customerId,
                'interview_id' => $serviceId,
                'lang' => $lang,
            ],
            [
                'report_data' => json_encode($normalized, JSON_UNESCAPED_UNICODE),
                'meta_info' => json_encode([
                    'created_by' => auth()->id(),
                    'created_on' => now()->toDateTimeString(),
                    'user' => auth()->user()?->name ?? 'System',
                    'format' => 'json_builder_v1',
                ], JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function deleteTemplate(int $customerId, int $serviceId, string $lang): void
    {
        if (! Schema::hasTable('customer_reports_html')) {
            return;
        }

        DB::table('customer_reports_html')
            ->where('cus_id', $customerId)
            ->where('interview_id', $serviceId)
            ->where('lang', $lang)
            ->delete();
    }

    /** @param mixed $payload */
    public function normalizePayload(mixed $payload, string $lang = 'sv'): array
    {
        if ($payload === null || $payload === '') {
            return $this->defaultPayload($lang);
        }

        $decoded = is_array($payload) ? $payload : json_decode((string) $payload, true);

        if (! is_array($decoded)) {
            return [
                'version' => 1,
                'sections' => [[
                    'id' => (string) Str::uuid(),
                    'type' => 'text',
                    'heading' => '',
                    'content' => (string) $payload,
                    'align' => 'left',
                    'status_id' => null,
                ]],
            ];
        }

        $sections = is_array($decoded['sections'] ?? null) ? $decoded['sections'] : [];
        if ($sections === []) {
            return $this->defaultPayload($lang);
        }

        return [
            'version' => 1,
            'sections' => array_values(array_map(fn ($section) => $this->normalizeSection($section), $sections)),
        ];
    }

    /** @return array{version:int,sections:array<int,array<string,mixed>>} */
    public function defaultPayload(string $lang = 'sv'): array
    {
        $sv = $lang === 'sv';
        $h3 = $this->defaultHeaders($lang, 3);
        $h5 = $this->defaultHeaders($lang, 5);

        return [
            'version' => 1,
            'sections' => [
                // Result
                ['id' => (string) Str::uuid(), 'type' => 'text', 'heading' => 'Result', 'content' => '', 'align' => 'left', 'status_id' => null],
                // Introduction
                ['id' => (string) Str::uuid(), 'type' => 'text', 'heading' => $sv ? 'Introduktion' : 'Introduction', 'content' => $this->defaultIntroduction($lang), 'align' => 'justify', 'status_id' => null],
                // Background
                ['id' => (string) Str::uuid(), 'type' => 'text', 'heading' => $sv ? 'Bakgrund' : 'Background', 'content' => '', 'align' => 'left', 'status_id' => null],
                // Information & Facts
                ['id' => (string) Str::uuid(), 'type' => 'text', 'heading' => $sv ? 'INFORMATION OCH FAKTA' : 'INFORMATION & FACTS', 'content' => $this->defaultInformationFactsText($lang), 'align' => 'left', 'status_id' => null],
                // Summary
                ['id' => (string) Str::uuid(), 'type' => 'text', 'heading' => $sv ? 'SUMMERING' : 'SUMMARY', 'content' => '', 'align' => 'left', 'status_id' => null],
                // Page break
                ['id' => (string) Str::uuid(), 'type' => 'page_break'],
                // Profile table
                ['id' => (string) Str::uuid(), 'type' => 'table', 'caption' => 'Profile', 'columns' => 3, 'headers' => $h3, 'rows' => $this->defaultProfileRows($lang)],
                // Economy table
                ['id' => (string) Str::uuid(), 'type' => 'table', 'caption' => $sv ? 'Ekonomi' : 'Economy', 'columns' => 3, 'headers' => $h3, 'rows' => $this->defaultEconomyRows($lang)],
                // Income table
                ['id' => (string) Str::uuid(), 'type' => 'table', 'caption' => $sv ? 'Inkomstuppgifter' : 'Income Information', 'columns' => 5, 'headers' => $h5, 'rows' => $this->defaultIncomeRows($lang)],
                // Legal table
                ['id' => (string) Str::uuid(), 'type' => 'table', 'caption' => $sv ? 'Juridik' : 'Legal', 'columns' => 5, 'headers' => $h5, 'rows' => $this->defaultLegalRows($lang)],
                // Bolagsengagemang
                ['id' => (string) Str::uuid(), 'type' => 'table', 'caption' => $sv ? 'Bolagsengagemang' : 'Company Involvement', 'columns' => 3, 'headers' => $h3, 'rows' => $sv ? [['c1' => 'Styrelseledamot & Ordforande', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''], ['c1' => 'Historiska bolagsengagemang', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => '']] : [['c1' => 'Board Member & Chairman', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''], ['c1' => 'Historical Company Involvement', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => '']]],
                // Historiska bolagsengagemang
                ['id' => (string) Str::uuid(), 'type' => 'table', 'caption' => $sv ? 'Historiska bolagsengagemang' : 'Historical Company Involvement', 'columns' => 3, 'headers' => $h3, 'rows' => [['c1' => '', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => '']]],
                // Korkort
                ['id' => (string) Str::uuid(), 'type' => 'table', 'caption' => $sv ? 'Korkort' : 'Driving Licence', 'columns' => 3, 'headers' => $h3, 'rows' => $sv ? [['c1' => 'Korkortsbehorighe', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''], ['c1' => 'Aterkallelse av korkort', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''], ['c1' => 'Antal fordon', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => '']] : [['c1' => 'Licence Category', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''], ['c1' => 'Licence Revocation', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''], ['c1' => 'Number of Vehicles', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => '']]],
                // Fordonskontroll
                ['id' => (string) Str::uuid(), 'type' => 'table', 'caption' => $sv ? 'Fordonskontroll' : 'Vehicle Check', 'columns' => 3, 'headers' => $h3, 'rows' => [['c1' => '', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => '']]],
                // Fastighetsinnehav
                ['id' => (string) Str::uuid(), 'type' => 'table', 'caption' => $sv ? 'Fastighetsinnehav' : 'Property Holdings', 'columns' => 3, 'headers' => $h3, 'rows' => [['c1' => '', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => '']]],
                // PEP/Sanktion
                ['id' => (string) Str::uuid(), 'type' => 'table', 'caption' => $sv ? 'PEP/Sanktion' : 'PEP/Sanction', 'columns' => 3, 'headers' => $h3, 'rows' => [['c1' => $sv ? 'PEP/Sanktion' : 'PEP/Sanction', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => '']]],
                // CV-kontroll arbetsgivare
                ['id' => (string) Str::uuid(), 'type' => 'table', 'caption' => $sv ? 'CV-kontroll arbetsgivare' : 'CV Check - Employer', 'columns' => 3, 'headers' => $h3, 'rows' => $sv ? [['c1' => 'Arbetsgivare 1', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''], ['c1' => 'Befattning', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''], ['c1' => 'Anstallningstid', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''], ['c1' => 'Refrensperson', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => '']] : [['c1' => 'Employer 1', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''], ['c1' => 'Position', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''], ['c1' => 'Employment Period', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''], ['c1' => 'Reference Person', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => '']]],
                // CV-kontroll utbildning
                ['id' => (string) Str::uuid(), 'type' => 'table', 'caption' => $sv ? 'CV-kontroll hogre utbildning' : 'CV Check - Higher Education', 'columns' => 3, 'headers' => $h3, 'rows' => $sv ? [['c1' => 'Institut 1', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''], ['c1' => 'Utbildning', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''], ['c1' => 'Examen', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => '']] : [['c1' => 'Institution 1', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''], ['c1' => 'Education', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''], ['c1' => 'Degree', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => '']]],
                // Sociala medier
                ['id' => (string) Str::uuid(), 'type' => 'text', 'heading' => $sv ? 'SOCIALA MEDIER' : 'SOCIAL MEDIA', 'content' => '', 'align' => 'left', 'status_id' => null],
                // Källor
                ['id' => (string) Str::uuid(), 'type' => 'text', 'heading' => $sv ? 'KALLOR' : 'SOURCES', 'content' => $this->defaultKallorText($lang), 'align' => 'left', 'status_id' => null],
                // Ansvar
                ['id' => (string) Str::uuid(), 'type' => 'text', 'heading' => $sv ? 'ANSVAR' : 'LIABILITY', 'content' => $this->defaultAnsvarText($lang), 'align' => 'left', 'status_id' => null],
                // Metod
                ['id' => (string) Str::uuid(), 'type' => 'text', 'heading' => $sv ? 'METOD' : 'METHOD', 'content' => $this->defaultMetodText($lang), 'align' => 'justify', 'status_id' => null],
            ],
        ];
    }

    private function defaultIntroduction(string $lang): string
    {
        if ($lang === 'sv') {
            return 'Vi pa Recway AB ar glada over att ha fatt i uppdrag av {cus_company} att genomfora ett noggrant {serviceTitle}. '
                .'Detta ar en viktig process for att sakerstalla att den potentiella kandidaten ar lamplig och palitlig for tjansten i fraga. '
                .'Genom att undersoka individens kriminella historia, utbildning, anstallningshistoria och ekonomiska status kan vi identifiera eventuella varningstecken '
                .'och minska risken for tjanstefel. Vi ar ett foretag som lagger stor vikt vid integritet och sakerhet och vi kommer att genomfora denna kritiska process med '
                .'storsta omsorg och professionalism.';
        }

        return 'We at Recway AB are pleased to have been assigned by {cus_company} to conduct a thorough {serviceTitle}. '
            .'This is an important process to ensure that the potential candidate is suitable and reliable for the position in question. '
            .'By examining the individual\'s criminal history, education, employment history, and financial status, we can identify warning signs and reduce the risk of misconduct. '
            .'We place great emphasis on integrity and security and carry out this process with the highest level of care and professionalism.';
    }

    private function defaultInformationFactsText(string $lang): string
    {
        if ($lang === 'sv') {
            return 'Recway ar specialiserat pa att samla in information fran olika kallor, inklusive internet, databaser, register och offentliga register '
                .'som innehas av myndigheter. Vart arbetssatt innebar att verifiera information genom flera oberoende kallor for att sakerstalla riktighet i rapporteringen.';
        }

        return 'Recway specializes in collecting information from multiple sources, including internet resources, databases, registers, and official records. '
            .'Our method is to verify information through independent sources to ensure accuracy and reliable reporting.';
    }

    /** @return array<int, array{c1:string,c2:string,c3:string}> */
    private function defaultProfileRows(string $lang): array
    {
        if ($lang === 'sv') {
            return [
                ['c1' => 'Namn', 'c2' => '{can_name}', 'c3' => ''],
                ['c1' => 'Tjanst', 'c2' => '{serviceTitle}', 'c3' => ''],
                ['c1' => 'Kund', 'c2' => '{cus_company}', 'c3' => ''],
            ];
        }

        return [
            ['c1' => 'Name', 'c2' => '{can_name}', 'c3' => ''],
            ['c1' => 'Service', 'c2' => '{serviceTitle}', 'c3' => ''],
            ['c1' => 'Customer', 'c2' => '{cus_company}', 'c3' => ''],
        ];
    }

    /** @return array<int, string> */
    private function defaultHeaders(string $lang, int $columns): array
    {
        if ($columns === 3) {
            return $lang === 'sv'
                ? ['Rubrik', 'Varde', 'Status']
                : ['Head', 'Value', 'Status'];
        }

        return $lang === 'sv'
            ? ['Kolumn 1', 'Kolumn 2', 'Kolumn 3', 'Kolumn 4', 'Status']
            : ['Column 1', 'Column 2', 'Column 3', 'Column 4', 'Status'];
    }

    /** @return array<int, array{c1:string,c2:string,c3:string}> */
    private function defaultEconomyRows(string $lang): array
    {
        if ($lang === 'sv') {
            return [
                ['c1' => 'Betalningsanmarkningar', 'c2' => '', 'c3' => ''],
                ['c1' => 'Skuldsaldo hos Kronofogden', 'c2' => '', 'c3' => ''],
            ];
        }

        return [
            ['c1' => 'Payment Remarks', 'c2' => '', 'c3' => ''],
            ['c1' => 'Debt Balance', 'c2' => '', 'c3' => ''],
        ];
    }

    /** @return array<int, array{c1:string,c2:string,c3:string,c4:string,c5:string}> */
    private function defaultIncomeRows(string $lang): array
    {
        if ($lang === 'sv') {
            return [
                ['c1' => 'Ar', 'c2' => 'Inkomst av tjanst', 'c3' => 'Inkomst av kapital', 'c4' => 'Totalt', 'c5' => ''],
            ];
        }

        return [
            ['c1' => 'Year', 'c2' => 'Income from Service', 'c3' => 'Income from Capital', 'c4' => 'Total', 'c5' => ''],
        ];
    }

    /** @return array<int, array{c1:string,c2:string,c3:string,c4:string,c5:string}> */
    private function defaultLegalRows(string $lang): array
    {
        if ($lang === 'sv') {
            return [
                ['c1' => 'Kategori', 'c2' => 'Fynd', 'c3' => 'Datum', 'c4' => 'Kalla', 'c5' => ''],
            ];
        }

        return [
            ['c1' => 'Category', 'c2' => 'Finding', 'c3' => 'Date', 'c4' => 'Source', 'c5' => ''],
        ];
    }

    private function defaultKallorText(string $lang): string
    {
        if ($lang === 'sv') {
            return 'Recway utfor bakgrundskontroller dar antalet kallor som kontrolleras varierar beroende pa omfattningen av kontrollen. Recway hamtar offentliga uppgifter fran flera myndigheter och institutioner i Sverige, inklusive Skatteverket, Kronofogdemyndigheten, Transportstyrelsen, Hogsta domstolen, Arbetsdomstolen samt samtliga Sveriges hovrattar, tings- och forvaltningsrattar. For att sakerstalla en omfattande kontroll inkluderar de ocksa information fran oppna kallor pa internet samt ett urval av de mest populara sociala medie-plattformarna.';
        }

        return 'Recway conducts background checks where the number of sources varies depending on the scope of the check. Recway retrieves public information from multiple authorities and institutions in Sweden, including the Swedish Tax Agency, the Enforcement Authority, the Transport Agency, and all Swedish courts. To ensure a comprehensive check, open internet sources and selected social media platforms are also included.';
    }

    private function defaultAnsvarText(string $lang): string
    {
        if ($lang === 'sv') {
            return 'Rapporten far anvandas av endast Bestallaren och far ej spridas till annan. Recway ansvarar inte gentemot annan an Bestallaren for innehallet i rapporten eller for annan anvandning av rapporten an i samband med en bakgrundskontroll. Recway ansvarar ej for eventuella fel i de kallor vi hamtar uppgifter fran.';
        }

        return 'The report may only be used by the Client and may not be distributed to others. Recway is not responsible to anyone other than the Client for the content of the report. Recway is not responsible for any errors in the sources from which we retrieve information.';
    }

    private function defaultMetodText(string $lang): string
    {
        if ($lang === 'sv') {
            return 'Recway genomfor bakgrundskontroller genom en strukturerad och dokumenterad process. Informationsinhamnting sker, i den utstrackning det ar lagligen tillatet och relevant for uppdraget, fran offentliga register och myndighetskallor, kommersiella databaser samt oppna kallor.'."\n\n".'Kontroller genomfors i enlighet med vald kontrollniva och uppdragets riskprofil. Efter genomford informationsinhamnting analyseras samtliga uppgifter manuellt av behorig sakerhetshantlaggare. Bedomningen sker utifran relevans i forhallande till uppdraget, aktualitet, identifierade riskindikatorer samt proportionalitet.';
        }

        return 'Recway conducts background checks through a structured and documented process. Information is gathered from public registers, official sources, commercial databases, and open sources, to the extent legally permitted.'."\n\n".'After information gathering, all data is manually analyzed by an authorized security officer. The assessment is based on relevance, timeliness, identified risk indicators, and proportionality.';
    }

    /** @param mixed $section */
    private function normalizeSection(mixed $section): array
    {
        if (! is_array($section)) {
            return [
                'id' => (string) Str::uuid(),
                'type' => 'text',
                'heading' => '',
                'content' => '',
                'align' => 'left',
                'status_id' => null,
            ];
        }

        $type = (string) ($section['type'] ?? 'text');

        if ($type === 'table') {
            $rows = is_array($section['rows'] ?? null) ? $section['rows'] : [];
            $columns = max(3, min(5, (int) ($section['columns'] ?? 3)));
            $headers = is_array($section['headers'] ?? null) ? array_values($section['headers']) : [];
            $headers = array_pad(array_slice(array_map(fn ($h) => (string) $h, $headers), 0, $columns), $columns, '');
            $rows = array_values(array_map(function ($row) {
                if (! is_array($row)) {
                    return ['c1' => '', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''];
                }

                return [
                    'c1' => (string) ($row['c1'] ?? ''),
                    'c2' => (string) ($row['c2'] ?? ''),
                    'c3' => (string) ($row['c3'] ?? ''),
                    'c4' => (string) ($row['c4'] ?? ''),
                    'c5' => (string) ($row['c5'] ?? ''),
                ];
            }, $rows));

            return [
                'id' => (string) ($section['id'] ?? Str::uuid()),
                'type' => 'table',
                'caption' => (string) ($section['caption'] ?? ''),
                'columns' => $columns,
                'headers' => $headers,
                'rows' => $rows === [] ? [['c1' => '', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => '']] : $rows,
            ];
        }

        if ($type === 'page_break') {
            return [
                'id' => (string) ($section['id'] ?? Str::uuid()),
                'type' => 'page_break',
            ];
        }

        return [
            'id' => (string) ($section['id'] ?? Str::uuid()),
            'type' => 'text',
            'heading' => (string) ($section['heading'] ?? ''),
            'content' => (string) ($section['content'] ?? ''),
            'align' => in_array(($section['align'] ?? 'left'), ['left', 'right', 'justify'], true)
                ? (string) $section['align']
                : 'left',
            'status_id' => isset($section['status_id']) && $section['status_id'] !== ''
                ? (int) $section['status_id']
                : null,
        ];
    }
}
