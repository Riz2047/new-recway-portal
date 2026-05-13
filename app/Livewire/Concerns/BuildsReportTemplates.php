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
            'bolagsengagemang' => $this->legacyBolagsengagemangSection($lang),
            'historiska_bolagsengagemang' => $this->legacyHistoriskaBolagsengagemangSection($lang),
            'korkort' => $this->legacyKorkortSection($lang),
            'fordonskontroll' => $this->legacyFordonskontrollSection($lang),
            'fastighetsinnehav' => $this->legacyFastighetsinnehavSection($lang),
            'pep_sanktion' => $this->legacyPepSanktionSection($lang),
            'cv_arbetsgivare' => $this->legacyCvArbetsgivareSection($lang),
            'cv_utbildning' => $this->legacyCvUtbildningSection($lang),
            'sociala_medier' => $this->legacySocialaMedierSection($lang),
            'kallor' => $this->legacyKallorSection($lang),
            'ansvar' => $this->legacyAnsvarSection($lang),
            'metod' => $this->legacyMetodSection($lang),
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

    // -------------------------------------------------------------------------
    // BK Level presets (load full section set, replacing current content)
    // -------------------------------------------------------------------------

    public function loadBkPreset(string $lang, int $level): void
    {
        if (! $this->canEditLanguage($lang)) {
            return;
        }

        $sections = [
            ['type' => 'text', 'id' => (string) Str::uuid(), 'heading' => 'Result', 'content' => '', 'align' => 'left', 'status_id' => null],
            $this->legacyIntroductionSection($lang),
            $this->legacyBackgroundSection($lang),
            $this->legacyInformationSection($lang),
            $this->legacySummarySection($lang),
            ['id' => (string) Str::uuid(), 'type' => 'page_break'],
            $this->legacyProfileSection($lang),
            $this->legacyEconomySection($lang),
            $this->legacyIncomeSection($lang),
            $this->legacyLegalSection($lang),
            $this->legacyBolagsengagemangSection($lang),
            $this->legacyHistoriskaBolagsengagemangSection($lang),
            $this->legacyKorkortSection($lang),
            $this->legacyFordonskontrollSection($lang),
            $this->legacyFastighetsinnehavSection($lang),
            $this->legacyPepSanktionSection($lang),
        ];

        if ($level >= 2) {
            $sections[] = $this->legacyCvArbetsgivareSection($lang);
            $sections[] = $this->legacyCvUtbildningSection($lang);
            $sections[] = $this->legacySocialaMedierSection($lang);
            $sections[] = $this->legacyKallorSection($lang);
            $sections[] = $this->legacyAnsvarSection($lang);
            $sections[] = $this->legacyMetodSection($lang);
        }

        $this->templates[$lang]['sections'] = array_values($sections);
    }

    // -------------------------------------------------------------------------
    // New section builders
    // -------------------------------------------------------------------------

    private function legacyBolagsengagemangSection(string $lang): array
    {
        return $this->legacyTableSection(
            $lang === 'sv' ? 'Bolagsengagemang' : 'Company Involvement',
            3,
            $lang === 'sv' ? ['Rubrik', 'Varde', 'Status'] : ['Head', 'Value', 'Status'],
            $lang === 'sv'
                ? [
                    ['c1' => 'Styrelseledamot & Ordforande', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''],
                    ['c1' => 'Historiska bolagsengagemang', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''],
                ]
                : [
                    ['c1' => 'Board Member & Chairman', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''],
                    ['c1' => 'Historical Company Involvement', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''],
                ],
        );
    }

    private function legacyHistoriskaBolagsengagemangSection(string $lang): array
    {
        return $this->legacyTableSection(
            $lang === 'sv' ? 'Historiska bolagsengagemang' : 'Historical Company Involvement',
            3,
            $lang === 'sv' ? ['Rubrik', 'Varde', 'Status'] : ['Head', 'Value', 'Status'],
            [['c1' => '', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => '']],
        );
    }

    private function legacyKorkortSection(string $lang): array
    {
        return $this->legacyTableSection(
            $lang === 'sv' ? 'Korkort' : 'Driving Licence',
            3,
            $lang === 'sv' ? ['Rubrik', 'Varde', 'Status'] : ['Head', 'Value', 'Status'],
            $lang === 'sv'
                ? [
                    ['c1' => 'Korkortsbehorighe', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''],
                    ['c1' => 'Aterkallelse av korkort', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''],
                    ['c1' => 'Antal fordon', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''],
                ]
                : [
                    ['c1' => 'Driving Licence Category', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''],
                    ['c1' => 'Licence Revocation', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''],
                    ['c1' => 'Number of Vehicles', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''],
                ],
        );
    }

    private function legacyFordonskontrollSection(string $lang): array
    {
        return $this->legacyTableSection(
            $lang === 'sv' ? 'Fordonskontroll' : 'Vehicle Check',
            3,
            $lang === 'sv' ? ['Rubrik', 'Varde', 'Status'] : ['Head', 'Value', 'Status'],
            [['c1' => '', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => '']],
        );
    }

    private function legacyFastighetsinnehavSection(string $lang): array
    {
        return $this->legacyTableSection(
            $lang === 'sv' ? 'Fastighetsinnehav' : 'Property Holdings',
            3,
            $lang === 'sv' ? ['Rubrik', 'Varde', 'Status'] : ['Head', 'Value', 'Status'],
            [['c1' => '', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => '']],
        );
    }

    private function legacyPepSanktionSection(string $lang): array
    {
        return $this->legacyTableSection(
            $lang === 'sv' ? 'PEP/Sanktion' : 'PEP/Sanction',
            3,
            $lang === 'sv' ? ['Rubrik', 'Varde', 'Status'] : ['Head', 'Value', 'Status'],
            [['c1' => $lang === 'sv' ? 'PEP/Sanktion' : 'PEP/Sanction', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => '']],
        );
    }

    private function legacyCvArbetsgivareSection(string $lang): array
    {
        return $this->legacyTableSection(
            $lang === 'sv' ? 'CV-kontroll arbetsgivare' : 'CV Check - Employer',
            3,
            $lang === 'sv' ? ['Rubrik', 'Varde', 'Status'] : ['Head', 'Value', 'Status'],
            $lang === 'sv'
                ? [
                    ['c1' => 'Arbetsgivare 1', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''],
                    ['c1' => 'Befattning', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''],
                    ['c1' => 'Anstallningstid', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''],
                    ['c1' => 'Refrensperson', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''],
                ]
                : [
                    ['c1' => 'Employer 1', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''],
                    ['c1' => 'Position', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''],
                    ['c1' => 'Employment Period', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''],
                    ['c1' => 'Reference Person', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''],
                ],
        );
    }

    private function legacyCvUtbildningSection(string $lang): array
    {
        return $this->legacyTableSection(
            $lang === 'sv' ? 'CV-kontroll hogre eftergymnasial utbildning' : 'CV Check - Higher Education',
            3,
            $lang === 'sv' ? ['Rubrik', 'Varde', 'Status'] : ['Head', 'Value', 'Status'],
            $lang === 'sv'
                ? [
                    ['c1' => 'Institut 1', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''],
                    ['c1' => 'Utbildning', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''],
                    ['c1' => 'Examen', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''],
                ]
                : [
                    ['c1' => 'Institution 1', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''],
                    ['c1' => 'Education', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''],
                    ['c1' => 'Degree', 'c2' => '', 'c3' => '', 'c4' => '', 'c5' => ''],
                ],
        );
    }

    private function legacySocialaMedierSection(string $lang): array
    {
        $content = $lang === 'sv'
            ? 'Facebook: Kandidaten har ett konto pa Facebook. Under den granskade perioden hittades inga avvikelser eller olagliga aktiviteter.'."\n".'Instagram: Kandidaten har ett konto pa Instagram. Under den granskade perioden hittades inga avvikelser.'."\n".'Linkedin: Kandidaten har ett konto pa Linkedin med professionell narvaro.'."\n".'Avvikelser: Inga avvikelser eller oregelbundenheter har hittats pa kandidatens sociala mediekonton.'
            : 'Facebook: The candidate has a Facebook account. No deviations or illegal activities were found during the reviewed period.'."\n".'Instagram: The candidate has an Instagram account. No deviations were found.'."\n".'LinkedIn: The candidate has a LinkedIn account with professional presence.'."\n".'Deviations: No deviations or irregularities were found on the candidate\'s social media accounts.';

        return [
            'id' => (string) Str::uuid(),
            'type' => 'text',
            'heading' => $lang === 'sv' ? 'SOCIALA MEDIER' : 'SOCIAL MEDIA',
            'content' => $content,
            'align' => 'left',
            'status_id' => null,
        ];
    }

    private function legacyKallorSection(string $lang): array
    {
        $content = $lang === 'sv'
            ? 'Recway utfor bakgrundskontroller dar antalet kallor som kontrolleras varierar beroende pa omfattningen av kontrollen. Recway hamtar offentliga uppgifter fran flera myndigheter och institutioner i Sverige, inklusive Skatteverket, Kronofogdemyndigheten, Centrala studiestodsnamnden, aktuella larosaten, Transportstyrelsen, Hogsta domstolen, Arbetsdomstolen samt samtliga Sveriges hovrattar, tings- och forvaltningsrattar. For att sakerstalla en omfattande kontroll inkluderar de ocksa information fran oppna kallor pa internet samt ett urval av de mest populara sociala medie-plattformarna. Genom att kombinera dessa kallor ger Recway en grundlig och palitlig bakgrundskontroll.'
            : 'Recway conducts background checks where the number of sources varies depending on the scope of the check. Recway retrieves public information from multiple authorities and institutions in Sweden. To ensure a comprehensive check, open internet sources and selected social media platforms are also included. By combining these sources, Recway provides a thorough and reliable background check.';

        return [
            'id' => (string) Str::uuid(),
            'type' => 'text',
            'heading' => $lang === 'sv' ? 'KALLOR' : 'SOURCES',
            'content' => $content,
            'align' => 'left',
            'status_id' => null,
        ];
    }

    private function legacyAnsvarSection(string $lang): array
    {
        $content = $lang === 'sv'
            ? 'Rapporten far anvandas av endast Bestallaren och far ej spridas till annan. Recway ansvarar inte gentemot annan an Bestallaren for innehallet i rapporten eller for annan anvandning av rapporten an i samband med en bakgrundskontroll. Recway ansvarar ej for eventuella fel i de kallor vi hamtar uppgifter fran.'
            : 'The report may only be used by the Client and may not be distributed to others. Recway is not responsible to anyone other than the Client for the content of the report or for any use of the report other than in connection with a background check. Recway is not responsible for any errors in the sources from which we retrieve information.';

        return [
            'id' => (string) Str::uuid(),
            'type' => 'text',
            'heading' => $lang === 'sv' ? 'ANSVAR' : 'LIABILITY',
            'content' => $content,
            'align' => 'left',
            'status_id' => null,
        ];
    }

    private function legacyMetodSection(string $lang): array
    {
        $content = $lang === 'sv'
            ? 'Recway genomfor bakgrundskontroller genom en strukturerad och dokumenterad process. Informationsinhamnting sker, i den utstrackning det ar lagligen tillatet och relevant for uppdraget, fran offentliga register och myndighetskallor, kommersiella databaser samt oppna kallor.'."\n\n".'Kontroller genomfors i enlighet med vald kontrollniva och uppdragets riskprofil. Verifiering av utbildning eller tidigare anstallning sker nar dettaingar i betalld kontroll.'."\n\n".'Efter genomford informationsinhamnting analyseras samtliga uppgifter manuellt av behorig sakerhetshantlaggare. Bedomningen sker utifran relevans i forhallande till uppdraget, aktualitet, identifierade riskindikatorer samt proportionalitet.'
            : 'Recway conducts background checks through a structured and documented process. Information is gathered, to the extent legally permitted and relevant to the assignment, from public registers, official sources, commercial databases, and open sources.'."\n\n".'Checks are conducted in accordance with the selected control level and the risk profile of the assignment.'."\n\n".'After information gathering, all data is manually analyzed by an authorized security officer. The assessment is based on relevance to the assignment, timeliness, identified risk indicators, and proportionality.';

        return [
            'id' => (string) Str::uuid(),
            'type' => 'text',
            'heading' => $lang === 'sv' ? 'METOD' : 'METHOD',
            'content' => $content,
            'align' => 'justify',
            'status_id' => null,
        ];
    }
}
