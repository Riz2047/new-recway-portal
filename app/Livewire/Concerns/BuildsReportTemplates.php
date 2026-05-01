<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Illuminate\Support\Str;

trait BuildsReportTemplates
{
    public function addLegacySection(string $lang, string $preset): void
    {
        if (! $this->canEditLanguage($lang)) {
            return;
        }

        $section = match ($preset) {
            'introduction' => $this->legacyIntroductionSection($lang),
            'background' => $this->legacyBackgroundSection($lang),
            'information' => $this->legacyInformationSection($lang),
            'summary' => $this->legacySummarySection($lang),
            'profile' => $this->legacyProfileSection($lang),
            'economy' => $this->legacyEconomySection($lang),
            'income' => $this->legacyIncomeSection($lang),
            'legal' => $this->legacyLegalSection($lang),
            default => null,
        };

        if (! is_array($section)) {
            return;
        }

        $this->templates[$lang]['sections'][] = $section;
    }

    public function addTextSection(string $lang): void
    {
        if (! $this->canEditLanguage($lang)) {
            return;
        }

        $this->templates[$lang]['sections'][] = [
            'id' => (string) Str::uuid(),
            'type' => 'text',
            'heading' => '',
            'content' => '',
            'align' => 'left',
            'status_id' => null,
        ];
    }

    public function addTableSection(string $lang): void
    {
        if (! $this->canEditLanguage($lang)) {
            return;
        }

        $this->templates[$lang]['sections'][] = [
            'id' => (string) Str::uuid(),
            'type' => 'table',
            'caption' => '',
            'columns' => 3,
            'headers' => ['', '', ''],
            'rows' => [
                ['c1' => '', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''],
            ],
        ];
    }

    public function addPageBreakSection(string $lang): void
    {
        if (! $this->canEditLanguage($lang)) {
            return;
        }

        $this->templates[$lang]['sections'][] = [
            'id' => (string) Str::uuid(),
            'type' => 'page_break',
        ];
    }

    public function removeSection(string $lang, int $sectionIndex): void
    {
        if (! $this->canEditLanguage($lang) || ! isset($this->templates[$lang]['sections'][$sectionIndex])) {
            return;
        }

        unset($this->templates[$lang]['sections'][$sectionIndex]);
        $this->templates[$lang]['sections'] = array_values($this->templates[$lang]['sections']);
    }

    public function addTableRow(string $lang, int $sectionIndex): void
    {
        if (! $this->canEditLanguage($lang)) {
            return;
        }

        if (($this->templates[$lang]['sections'][$sectionIndex]['type'] ?? null) !== 'table') {
            return;
        }

        $this->templates[$lang]['sections'][$sectionIndex]['rows'][] = ['c1' => '', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''];
    }

    public function removeTableRow(string $lang, int $sectionIndex, int $rowIndex): void
    {
        if (! $this->canEditLanguage($lang)) {
            return;
        }

        if (($this->templates[$lang]['sections'][$sectionIndex]['type'] ?? null) !== 'table') {
            return;
        }

        if (! isset($this->templates[$lang]['sections'][$sectionIndex]['rows'][$rowIndex])) {
            return;
        }

        unset($this->templates[$lang]['sections'][$sectionIndex]['rows'][$rowIndex]);
        $this->templates[$lang]['sections'][$sectionIndex]['rows'] = array_values($this->templates[$lang]['sections'][$sectionIndex]['rows']);

        if ($this->templates[$lang]['sections'][$sectionIndex]['rows'] === []) {
            $this->templates[$lang]['sections'][$sectionIndex]['rows'][] = ['c1' => '', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''];
        }
    }

    private function canEditLanguage(string $lang): bool
    {
        return in_array($lang, ['sv', 'en'], true) && isset($this->templates[$lang]['sections']);
    }

    /** @return array{id:string,type:string,heading:string,content:string,align:string,status_id:null} */
    private function legacyIntroductionSection(string $lang): array
    {
        $content = $lang === 'sv'
            ? 'Vi pa Recway AB ar glada over att ha fatt i uppdrag av {cus_company} att genomfora ett noggrant {serviceTitle}. Detta ar en viktig process for att sakerstalla att den potentiella kandidaten ar lamplig och palitlig for tjansten i fraga.'
            : 'We at Recway AB are pleased to have been assigned by {cus_company} to conduct a thorough {serviceTitle}. This is an important process to ensure that the potential candidate is suitable and reliable for the position in question.';

        return [
            'id' => (string) Str::uuid(),
            'type' => 'text',
            'heading' => $lang === 'sv' ? 'Introduktion' : 'Introduction',
            'content' => $content,
            'align' => 'justify',
            'status_id' => null,
        ];
    }

    /** @return array{id:string,type:string,heading:string,content:string,align:string,status_id:null} */
    private function legacyBackgroundSection(string $lang): array
    {
        return [
            'id' => (string) Str::uuid(),
            'type' => 'text',
            'heading' => $lang === 'sv' ? 'Bakgrund' : 'Background',
            'content' => '',
            'align' => 'left',
            'status_id' => null,
        ];
    }

    /** @return array{id:string,type:string,heading:string,content:string,align:string,status_id:null} */
    private function legacyInformationSection(string $lang): array
    {
        $content = $lang === 'sv'
            ? 'Recway ar specialiserat pa att samla in information fran olika kallor, inklusive internet, databaser, register och offentliga register.'
            : 'Recway specializes in collecting information from multiple sources, including internet resources, databases, and public records.';

        return [
            'id' => (string) Str::uuid(),
            'type' => 'text',
            'heading' => $lang === 'sv' ? 'INFORMATION OCH FAKTA' : 'INFORMATION & FACTS',
            'content' => $content,
            'align' => 'left',
            'status_id' => null,
        ];
    }

    /** @return array{id:string,type:string,heading:string,content:string,align:string,status_id:null} */
    private function legacySummarySection(string $lang): array
    {
        return [
            'id' => (string) Str::uuid(),
            'type' => 'text',
            'heading' => $lang === 'sv' ? 'SUMMERING' : 'SUMMARY',
            'content' => '',
            'align' => 'left',
            'status_id' => null,
        ];
    }

    /** @return array{id:string,type:string,caption:string,columns:int,headers:array<int,string>,rows:array<int,array<string,string>>} */
    private function legacyProfileSection(string $lang): array
    {
        return $this->legacyTableSection(
            $lang === 'sv' ? 'Profile' : 'Profile',
            3,
            $lang === 'sv' ? ['Rubrik', 'Varde', 'Status'] : ['Head', 'Value', 'Status'],
            $lang === 'sv'
                ? [
                    ['c1' => 'Namn', 'c2' => '{can_name}', 'c3' => '', 'c4' => '', 'c5' => ''],
                    ['c1' => 'Tjanst', 'c2' => '{serviceTitle}', 'c3' => '', 'c4' => '', 'c5' => ''],
                    ['c1' => 'Kund', 'c2' => '{cus_company}', 'c3' => '', 'c4' => '', 'c5' => ''],
                ]
                : [
                    ['c1' => 'Name', 'c2' => '{can_name}', 'c3' => '', 'c4' => '', 'c5' => ''],
                    ['c1' => 'Service', 'c2' => '{serviceTitle}', 'c3' => '', 'c4' => '', 'c5' => ''],
                    ['c1' => 'Customer', 'c2' => '{cus_company}', 'c3' => '', 'c4' => '', 'c5' => ''],
                ],
        );
    }

    /** @return array{id:string,type:string,caption:string,columns:int,headers:array<int,string>,rows:array<int,array<string,string>>} */
    private function legacyEconomySection(string $lang): array
    {
        return $this->legacyTableSection(
            $lang === 'sv' ? 'Ekonomi' : 'Economy',
            3,
            $lang === 'sv' ? ['Rubrik', 'Varde', 'Status'] : ['Head', 'Value', 'Status'],
            $lang === 'sv'
                ? [
                    ['c1' => 'Betalningsanmarkningar', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''],
                    ['c1' => 'Skulder hos kronofogdemyndigheten', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''],
                ]
                : [
                    ['c1' => 'Payment Remarks', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''],
                    ['c1' => 'Debt with Enforcement Authority', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''],
                ],
        );
    }

    /** @return array{id:string,type:string,caption:string,columns:int,headers:array<int,string>,rows:array<int,array<string,string>>} */
    private function legacyIncomeSection(string $lang): array
    {
        return $this->legacyTableSection(
            $lang === 'sv' ? 'Inkomstuppgifter' : 'Income Information',
            5,
            $lang === 'sv'
                ? ['Kolumn 1', 'Kolumn 2', 'Kolumn 3', 'Kolumn 4', 'Status']
                : ['Column 1', 'Column 2', 'Column 3', 'Column 4', 'Status'],
            [[
                'c1' => $lang === 'sv' ? 'Ar' : 'Year',
                'c2' => $lang === 'sv' ? 'Inkomst av tjanst' : 'Income from Service',
                'c3' => $lang === 'sv' ? 'Inkomst av kapital' : 'Income from Capital',
                'c4' => $lang === 'sv' ? 'Totalt' : 'Total',
                'c5' => '',
            ]],
        );
    }

    /** @return array{id:string,type:string,caption:string,columns:int,headers:array<int,string>,rows:array<int,array<string,string>>} */
    private function legacyLegalSection(string $lang): array
    {
        return $this->legacyTableSection(
            $lang === 'sv' ? 'Juridik' : 'Legal',
            5,
            $lang === 'sv'
                ? ['Kolumn 1', 'Kolumn 2', 'Kolumn 3', 'Kolumn 4', 'Status']
                : ['Column 1', 'Column 2', 'Column 3', 'Column 4', 'Status'],
            [[
                'c1' => $lang === 'sv' ? 'Kategori' : 'Category',
                'c2' => $lang === 'sv' ? 'Fynd' : 'Finding',
                'c3' => $lang === 'sv' ? 'Datum' : 'Date',
                'c4' => $lang === 'sv' ? 'Kalla' : 'Source',
                'c5' => '',
            ]],
        );
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<string, string>>  $rows
     * @return array{id:string,type:string,caption:string,columns:int,headers:array<int,string>,rows:array<int,array<string,string>>}
     */
    private function legacyTableSection(string $caption, int $columns, array $headers, array $rows): array
    {
        return [
            'id' => (string) Str::uuid(),
            'type' => 'table',
            'caption' => $caption,
            'columns' => $columns,
            'headers' => $headers,
            'rows' => $rows,
        ];
    }
}
