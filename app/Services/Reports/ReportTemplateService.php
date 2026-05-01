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
            ->where('service_category_id', 2)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function getCustomerBackgroundServices(int $customerId): Collection
    {
        return ServiceType::query()
            ->select('service_types.id', 'service_types.name')
            ->join('service_type_user', 'service_type_user.service_type_id', '=', 'service_types.id')
            ->where('service_type_user.cus_id', $customerId)
            ->where('service_types.service_category_id', 2)
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
        $isSwedish = $lang === 'sv';

        return [
            'version' => 1,
            'sections' => [
                [
                    'id' => (string) Str::uuid(),
                    'type' => 'text',
                    'heading' => 'Result',
                    'content' => '',
                    'align' => 'left',
                    'status_id' => null,
                ],
                [
                    'id' => (string) Str::uuid(),
                    'type' => 'text',
                    'heading' => $isSwedish ? 'Introduktion' : 'Introduction',
                    'content' => $this->defaultIntroduction($lang),
                    'align' => 'justify',
                    'status_id' => null,
                ],
                [
                    'id' => (string) Str::uuid(),
                    'type' => 'text',
                    'heading' => $isSwedish ? 'Bakgrund' : 'Background Heading',
                    'content' => '',
                    'align' => 'left',
                    'status_id' => null,
                ],
                [
                    'id' => (string) Str::uuid(),
                    'type' => 'text',
                    'heading' => $isSwedish ? 'INFORMATION OCH FAKTA' : 'INFORMATION & FACTS',
                    'content' => $this->defaultInformationFactsText($lang),
                    'align' => 'left',
                    'status_id' => null,
                ],
                [
                    'id' => (string) Str::uuid(),
                    'type' => 'table',
                    'caption' => 'Profile',
                    'columns' => 3,
                    'headers' => $this->defaultHeaders($lang, 3),
                    'rows' => $this->defaultProfileRows($lang),
                ],
                [
                    'id' => (string) Str::uuid(),
                    'type' => 'table',
                    'caption' => $isSwedish ? 'Ekonomi' : 'Economy',
                    'columns' => 3,
                    'headers' => $this->defaultHeaders($lang, 3),
                    'rows' => $this->defaultEconomyRows($lang),
                ],
                [
                    'id' => (string) Str::uuid(),
                    'type' => 'table',
                    'caption' => $isSwedish ? 'Inkomstuppgifter' : 'Income Information',
                    'columns' => 5,
                    'headers' => $this->defaultHeaders($lang, 5),
                    'rows' => $this->defaultIncomeRows($lang),
                ],
                [
                    'id' => (string) Str::uuid(),
                    'type' => 'table',
                    'caption' => $isSwedish ? 'Juridik' : 'Legal',
                    'columns' => 5,
                    'headers' => $this->defaultHeaders($lang, 5),
                    'rows' => $this->defaultLegalRows($lang),
                ],
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
