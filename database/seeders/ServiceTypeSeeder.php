<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds service_types from the old `interviews` table.
 *
 * Column mapping:  id             → id
 *                  service_cat_id → service_category_id
 *                  title          → name
 *                  desc           → description
 *                  place (0/1)    → place (null / 'Physical')
 *                  country (int)  → country (null when 0)
 *                  cost (int)     → price (0.00 when ≤ 0)
 *                  delivery_days  → delivery_days
 *
 * Skipped: id=33 (service_cat_id=7 does not exist in service_categories).
 */
class ServiceTypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('service_types')->truncate();

        $rows = $this->rows();
        foreach (array_chunk($rows, 50) as $chunk) {
            DB::table('service_types')->insert($chunk);
        }

        // OLD system AUTO_INCREMENT was 98.
        DB::statement('ALTER TABLE service_types AUTO_INCREMENT = 98');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /** @return array<int, array<string, mixed>> */
    private function rows(): array
    {
        // [id, service_category_id, name, description, place(0/1), country(0=null), cost, delivery_days]
        $raw = [
            [1,  1,  ' Grundutredning + Säkerhetsprövningsintervju - Video',                          '',                                                    0, 0, 0,    null],
            [2,  1,  ' Grundutredning + Säkerhetsprövningsintervju - Fysiskt',                        '',                                                    1, 0, 0,    null],
            [3,  1,  'SPI - klass 2 - Video',                                                         '',                                                    0, 0, 0,    null],
            [4,  1,  'SPI - klass 2 - Fysikt',                                                        '',                                                    1, 0, 0,    null],
            [10, 3,  'Bakgrundskontroll nivå 1',                                                      "● Verifiering av personnummer.\r\n● Nuvarande och tidigare folkbokföringsadress, samt eventuella in- och utvandringar.\r\n● De två (3) sista deklarerade årsinkomsterna.\r\n● Betalningsförelägganden och skulder hos Kronofogdemyndigheten.\r\n● Kontroll hos Sveriges samtliga tingsrätter, hovrätter och förvaltningsrätter.\r\n● Sociala medier", 0, 0, 0, 2],
            [12, 3,  'Bakgrundskontroll - nivå 2',                                                    "● Verifiering av personnummer.\r\n● Nuvarande och tidigare folkbokföringsadress.\r\n● De tre (3) sista deklarerade årsinkomsterna.\r\n● Betalningsförelägganden och skulder hos Kronofogdemyndigheten.\r\n● CV­ kontroll\r\n● Sociala medier", 0, 0, 0, 5],
            [13, 3,  'Bakgrundskontroll - nivå 3',                                                    "● Verifiering av personnummer.\r\n● Nuvarande och tidigare folkbokföringsadress.\r\n● De tre (3) sista deklarerade årsinkomsterna.\r\n● CV­ kontroll\r\n● Sociala medier", 0, 0, 3, 7],
            [17, 3,  'Sefina - Nivå 1',                                                               "● Verifiering av personnummer.\r\n● Nuvarande och tidigare folkbokföringsadress, samt eventuella in- och utvandringar.\r\n● De två (2) sista deklarerade årsinkomsterna.\r\n● Betalningsförelägganden och skulder hos Kronofogdemyndigheten.\r\n● Kontroll hos Sveriges samtliga tingsrätter, hovrätter och förvaltningsrätter.\r\n", 0, 0, 0, null],
            [18, 3,  'Background check - Screening',                                                  'test',                                                0, 0, 0,    null],
            [19, 3,  'Dekra - Bakgrundskontroll nivå 1',                                              "Verifiering av personnummer.\r\n● Nuvarande och tidigare folkbokföringsadress, samt eventuella in- och utvandringar.\r\n● De två (2) sista deklarerade årsinkomsterna.\r\n● Betalningsförelägganden och skulder hos Kronofogdemyndigheten.\r\n● Kontroll hos Sveriges samtliga tingsrätter, hovrätter och förvaltningsrätter.\r\n", 0, 0, 0, null],
            [20, 3,  'ISS - Bakgrundskontroll nivå 1',                                                '',                                                    0, 0, 0,    2],
            [21, 3,  'Sefina - Nivå 2',                                                               "● Verifiering av personnummer.\r\n● Nuvarande och tidigare folkbokföringsadress, samt eventuella in- och utvandringar.\r\n● De två (2) sista deklarerade årsinkomsterna.\r\n● Betalningsförelägganden och skulder hos Kronofogdemyndigheten.\r\n● Kontroll hos Sveriges samtliga tingsrätter, hovrätter och förvaltningsrätter.\r\n● CV­ kontroll\r\n● Sociala medier", 0, 0, 0, null],
            [22, 3,  'Sefina - Nivå 3',                                                               "● Verifiering av personnummer.\r\n● Nuvarande och tidigare folkbokföringsadress, samt eventuella in- och utvandringar.\r\n● De två (2) sista deklareradeårsinkomsterna.\r\n● Betalningsförelägganden och skulder hos Kronofogdemyndigheten.\r\n● Kontroll hos Sveriges samtliga tingsrätter, hovrätter och förvaltningsrätter.\r\n● CV­ kontroll\r\n● Sociala medier\r\n● Kontroll av make/makas bolagsengagemang\r\n", 0, 0, 0, null],
            [23, 3,  'Sefina - Personutredning',                                                      '',                                                    0, 0, 0,    null],
            [25, 1,  'Scania - SPI - Video',                                                          '',                                                    0, 0, 0,    null],
            [26, 1,  'Scania - SPI - Fysiskt',                                                        '',                                                    1, 0, 0,    null],
            [27, 3,  'Scania - Nivå 1',                                                               "Verifiering av personnummer.\r\n● Nuvarande och tidigare folkbokföringsadress, samt eventuella in- och utvandringar.\r\n● De två (2) sista deklarerade årsinkomsterna.\r\n● Betalningsförelägganden och skulder hos Kronofogdemyndigheten.\r\n● Kontroll hos Sveriges samtliga tingsrätter, hovrätter och förvaltningsrätter.\r\n", 0, 0, 0, null],
            [28, 3,  'Scania - Nivå 2',                                                               "Verifiering av personnummer.\r\n● Nuvarande och tidigare folkbokföringsadress, samt eventuella in- och utvandringar.\r\n● De två (2) sista deklarerade årsinkomsterna.\r\n● Betalningsförelägganden och skulder hos Kronofogdemyndigheten.\r\n● Kontroll hos Sveriges samtliga tingsrätter, hovrätter och förvaltningsrätter.\r\n", 0, 0, 0, null],
            [29, 3,  'Scania - Nivå 3',                                                               "Verifiering av personnummer.\r\n● Nuvarande och tidigare folkbokföringsadress, samt eventuella in- och utvandringar.\r\n● De två (2) sista deklarerade årsinkomsterna.\r\n● Betalningsförelägganden och skulder hos Kronofogdemyndigheten.\r\n● Kontroll hos Sveriges samtliga tingsrätter, hovrätter och förvaltningsrätter.\r\n", 0, 0, 0, null],
            [30, 3,  'Northvolt Level 1',                                                             '',                                                    0, 0, 0,    null],
            [31, 3,  'Northvolt Level 2',                                                             '',                                                    0, 0, 0,    null],
            // id=33 intentionally skipped: service_cat_id=7 has no matching service_category
            [35, 1,  'SK - SPI - Fysiskt klass 3',                                                   '',                                                    0, 0, 0,    null],
            [36, 1,  'SK - SPI - Fysiskt klass 2',                                                   '',                                                    0, 0, 0,    null],
            [37, 1,  'SK - SPI - Digital klass 3',                                                   '',                                                    0, 0, 0,    null],
            [38, 1,  'SK - SPI - Digital klass 2',                                                   '',                                                    0, 0, 0,    null],
            [42, 9,  'Uppföljningssamtal',                                                            '',                                                    0, 0, 0,    null],
            [43, 3,  'Bakgrundskontroll nivå 1 - Nuvia',                                             "● Verifiering av personnummer.\r\n● Nuvarande och tidigare folkbokföringsadress, samt eventuella in- och utvandringar.\r\n● De två (2) sista deklarerade årsinkomsterna.\r\n● Betalningsförelägganden och skulder hos Kronofogdemyndigheten.\r\n● Kontroll hos Sveriges samtliga tingsrätter, hovrätter och förvaltningsrätter.\r\n", 0, 0, 0, 2],
            [44, 3,  'Bakgrundskontroll nivå 2 - Nuvia',                                             "● Verifiering av personnummer.\r\n● Nuvarande och tidigare folkbokföringsadress, samt eventuella in- och utvandringar.\r\n● De två (2) sista deklarerade årsinkomsterna.\r\n● Betalningsförelägganden och skulder hos Kronofogdemyndigheten.\r\n● Kontroll hos Sveriges samtliga tingsrätter, hovrätter och förvaltningsrätter.\r\n● CV­ kontroll\r\n● Sociala medier", 0, 0, 0, 8],
            [45, 3,  'Bakgrundskontroll BAS - Landskrona Kommun',                                    "● Verifiering av personnummer.\r\n● Nuvarande och tidigare folkbokföringsadress, samt eventuella in- och utvandringar.\r\n● De två (3) sista deklarerade årsinkomsterna.\r\n● Betalningsförelägganden och skulder hos Kronofogdemyndigheten.\r\n● Kontroll hos Sveriges samtliga tingsrätter, hovrätter och förvaltningsrätter.\r\n", 0, 0, 0, null],
            [46, 3,  'Bakgrundskontroll Utökad - Landskrona Kommun',                                 "● Verifiering av personnummer.\r\n● Nuvarande och tidigare folkbokföringsadress, samt eventuella in- och utvandringar.\r\n● De två (3) sista deklarerade årsinkomsterna.\r\n● Betalningsförelägganden och skulder hos Kronofogdemyndigheten.\r\n● Kontroll hos Sveriges samtliga tingsrätter, hovrätter och förvaltningsrätter.\r\n● CV­ kontroll\r\n● Sociala medier", 0, 0, 0, null],
            [47, 9,  'Uppföljningssamtal - Landskrona stad',                                         'Årliga uppföljningssamtal enligt säkerhetsskyddslagen i Sverige är en del av arbetet med att säkerställa att anställda och andra personer med tillgång till säkerhetsklassad information eller verksamhet fortfarande är pålitliga ur säkerhetssynpunkt.',  1, 0, 0, null],
            [48, 1,  ' Grundutredning + Säkerhetsprövningsintervju - Fysiskt - Landskrona stad',     '<p>När kunden lägger sin beställning, anger de nödvändiga uppgifter såsom namn och kontaktinformation för kandidaten. Recway skickar därefter en bekräftelse på mottagandet av beställningen och informerar om nästa steg i processen. Kandidaten får ett informationsmejl från Recway med detaljer om intervjun, vad som förväntas och vilka dokument de ska ta med sig. Beställaren får en statusuppdatering som innehåller detaljer om intervjutiden och platsen.</p>', 1, 0, 0, null],
            [49, 1,  ' Grundutredning + Säkerhetsprövningsintervju - Video - Landskrona stad',       '',                                                    0, 0, 0,    null],
            [50, 9,  'Uppföljningssamtal - Sundsvall Kommun',                                        'Årliga uppföljningssamtal enligt säkerhetsskyddslagen i Sverige.',  0, 0, 0, null],
            [51, 3,  'Bakgrundskontroll nivå 1 - Meritmind',                                         "● Verifiering av personnummer.</br>\r\n● De tre (3) sista deklarerade årsinkomsterna.</br>\r\n● Betalningsförelägganden och skulder hos Kronofogdemyndigheten.</br>\r\n● Kontroll hos Sveriges samtliga tingsrätter, hovrätter och förvaltningsrätter.\r\n", 0, 0, 0, 1],
            [52, 3,  'Bakgrundskontroll nivå 2 - Meritmind',                                         "● Verifiering av personnummer.</br>\r\n● Nuvarande och tidigare folkbokföringsadress, samt eventuella in- och utvandringar.</br>\r\n● De tre (3) sista deklarerade årsinkomsterna.</br>\r\n● Betalningsförelägganden och skulder hos Kronofogdemyndigheten.</br>\r\n● Kontroll hos Sveriges samtliga tingsrätter, hovrätter och förvaltningsrätter.</br>\r\n● CV­ kontroll</br>\r\n● Sociala medier</br>", 0, 0, 0, 5],
            [53, 9,  'Uppföljningssamtal - Konecranes AB',                                           'Årliga uppföljningssamtal enligt säkerhetsskyddslagen i Sverige.',  0, 0, 0, null],
            [54, 1,  ' Grundutredning + Säkerhetsprövningsintervju - Fysisk - Konecranes AB',        '<p>När kunden lägger sin beställning, anger de nödvändiga uppgifter såsom namn och kontaktinformation för kandidaten. Recway skickar därefter en bekräftelse på mottagandet av beställningen och informerar om nästa steg i processen. Kandidaten får ett informationsmejl från Recway med detaljer om intervjun, vad som förväntas och vilka dokument de ska ta med sig. Beställaren får en statusuppdatering som innehåller detaljer om intervjutiden och platsen.</p>', 1, 0, 0, null],
            [55, 1,  'Volvo - Security clearance interview - Video',                                  '',                                                    0, 0, 0,    null],
            [56, 1,  'On-site - Security clearance interview',                                        '',                                                    1, 0, 0,    null],
            [57, 3,  'Bakgrundskontroll nivå 1 - Sundsvalls Kommun',                                 "● Verifiering av personnummer.</br>\r\n● De tre (3) sista deklarerade årsinkomsterna.</br>\r\n● Betalningsförelägganden och skulder hos Kronofogdemyndigheten.</br>\r\n● Kontroll hos Sveriges samtliga tingsrätter, hovrätter och förvaltningsrätter.\r\n", 0, 0, 0, 2],
            [58, 3,  'Bakgrundskontroll nivå 2 - Sundsvalls Kommun',                                 "● Verifiering av personnummer.</br>\r\n● Nuvarande och tidigare folkbokföringsadress, samt eventuella in- och utvandringar.</br>\r\n● De tre (3) sista deklareradeårsinkomsterna.</br>\r\n● Betalningsförelägganden och skulder hos Kronofogdemyndigheten.</br>\r\n● Kontroll hos Sveriges samtliga tingsrätter, hovrätter och förvaltningsrätter.</br>\r\n● CV­ kontroll</br>\r\n● Sociala medier</br>", 0, 0, 0, 8],
            [59, 3,  'Personal investigation',                                                        'Syftet är att bedöma personens pålitlighet, integritet och eventuella säkerhetsrisker.',  0, 0, 0, null],
            [62, 10, 'Avslutande säkerhetssamtal',                                                   '',                                                    0, 0, 0,    null],
            [63, 1,  'Swedish - Video - Compatibility Interview',                                     '',                                                    0, 0, 0,    null],
            [64, 1,  'English - Video - Compatibility Interview',                                     '',                                                    0, 0, 0,    null],
            [65, 9,  'Annual follow-up - Video - Volvo',                                             'Årliga uppföljningssamtal enligt säkerhetsskyddslagen i Sverige.',  0, 0, 0, null],
            [66, 3,  'Bakgrundskontroll - Spendrups',                                                "• Personalia \r\n• Ekonomi \r\n• Juridiska histroia \r\n• Körkortskontroll \r\n• Bolagesengagemang \r\n• Sociala Medier", 0, 0, 0, 5],
            [67, 3,  'Bakgrundskontroll Nivå 1 - Solna Stad',                                       "ID-verifiering\r\nPersonalia (adress, medborgarskap, arbetstillstånd)\r\nInkomstuppgifter (3 år)\r\nSkulder & ekonomisk status (Kronofogden)\r\nCV-kontroll (anställningar och utbildningar, 10 år)\r\nCV-analys\r\nRättslig kontroll (tingsrätt, förvaltningsrätt, hovrätter)\r\nUtökad personalia (andra på adressen, civilstånd, barn)\r\nSociala medier och medial närvaro\r\nBolagsengagemang (10 år)\r\nKonkurser", 0, 0, 2250, 10],
            [68, 3,  'Bakgrundskontroll Nivå 2 - Solna stad',                                       "ID-verifiering\r\nPersonalia (adress, medborgarskap, arbetstillstånd)\r\nInkomstuppgifter (3 år)\r\nKörkortskontroll\r\nFöreningsengemang\r\nBolagskontroll\r\nSanktionslistor\r\nFordonsinnehav\r\nFastighetsinnehav", 0, 0, 3450, 10],
            [69, 3,  'Säkerhetsprövning enligt Klass 2 - Solna stad',                               "ID-verifiering\r\nPersonalia (adress, medborgarskap, arbetstillstånd)\r\nInkomstuppgifter (3 år)\r\nSanktionslistor\r\nFordonsinnehav\r\nFastighetsinnehav", 0, 0, 3450, 10],
            [70, 3,  'Bakgrundskontroll Bas - Solna stad',                                          "Personalia: namn, personnummer, adress och medborgarskap\r\nArbetstillstånd: kontroll vid behov\r\nEkonomisk bakgrund: aktuella och historiska ärenden hos Kronofogden\r\nRättslig kontroll\r\nGranskning av sociala medier", 0, 0, 950, 10],
            [71, 3,  'Säkerhetsprövning enligt Klass 3 - Solna stad',                               "ID-verifiering\r\nPersonalia (adress, medborgarskap, arbetstillstånd)\r\nInkomstuppgifter (3 år)\r\nFordonsinnehav\r\nFastighetsinnehav", 0, 0, 3450, 10],
            [72, 1,  'Säkerhetsprövningsintervju',                                                   '',                                                    0, 0, 0,    null],
            [73, 9,  'Annual follow-up - Onsite - Volvo',                                            'Årliga uppföljningssamtal enligt säkerhetsskyddslagen i Sverige.',  1, 0, 0, null],
            [74, 1,  'Uniper - BK+säkerhetsprövningsintervju - Fysiskt',                             'Bakgrundskontroll + säkerhetsprövningsintervju gällande Uniper - ISS',  1, 0, 0, null],
            [75, 3,  'ISS - Uniper - Bakgrundskontroll nivå 1',                                      "● Verifiering av personnummer.</br>\r\n● De tre (3) sista deklarerade årsinkomsterna.</br>\r\n● Betalningsförelägganden och skulder hos Kronofogdemyndigheten.</br>\r\n● Kontroll hos Sveriges samtliga tingsrätter, hovrätter och förvaltningsrätter.\r\n", 0, 0, 0, 2],
            [76, 1,  'Säkerhetsprövningsintervju - Video - Alfa Laval',                              '',                                                    0, 0, 0,    null],
            [80, 3,  'Grundutredning Alfa Laval',                                                    "● Verifiering av personnummer.</br>\r\n● De tre (3) sista deklarerade årsinkomsterna.</br>\r\n● Betalningsförelägganden och skulder hos Kronofogdemyndigheten.</br>\r\n● Kontroll hos Sveriges samtliga tingsrätter, hovrätter och förvaltningsrätter.\r\n", 0, 0, 0, null],
            [81, 3,  'Grundutredning - SPV - Fysiskt',                                               '',                                                    0, 0, 0,    null],
            [82, 3,  'Grundutredning - enligt säkerhetsskyddslagen',                                 'Detta är endast grundutredning - enlig säkerhetsskyddslagen',  0, 0, 0, null],
            [83, 3,  'Bolagskontroll',                                                               '',                                                    0, 0, 0,    null],
            [84, 3,  'Bakgrundskontroll nivå 1 - Sodexo',                                            'Personilia, ekonomi och socialmedier och internetexponering',  0, 0, 0, 2],
            [85, 3,  'Bakgrundskontroll nivå 2 - Sodexo',                                            'Peronalia, Inkomsthistorik (senaste 3 åren), Betalningsanmärkningar och skuldsituation, Rättsliga uppgifter (civilrättsliga), Körkortskontroll, Öppna källor och internetexponering', 0, 0, 0, 4],
            [86, 3,  'Bakgrundskontroll nivå 3 - Sodexo',                                            'Peronalia, ekonomi, juridik, körkort och socialamedier, cv-kontroll, fordonkontroll, fastighetesinnehav',  0, 0, 0, 6],
            [87, 3,  'Bakgrundskontroll nivå 4 - Sodexo',                                            'Peronalia, ekonomi, juridik, körkort och socialamedier, cv-kontroll, fordonkontroll, fastighetesinnehav, bolagesengagem, verkliga huvudman, internet exponering och nyhetsmedia.',  0, 0, 0, 7],
            [88, 3,  'Bakgrundskontroll nivå 2 - Sodexo AZ',                                         'Peronalia, ekonomi, juridik, körkort och socialmedier, viktigt kontrolla djuraktivitetvist',  0, 0, 0, 4],
            [89, 3,  'Grundutredning enligt säkerhetsskyddslagen - Sodexo',                          'Peronalia, ekonomi, juridik, körkort och socialmedier.',  0, 0, 0, 4],
            [92, 1,  'Grundutredning + säkerhetsprövningsintervju - Digital - Sodeox',               '',                                                    0, 0, 0,    null],
            [93, 1,  'Grundutredning + säkerhetsprövningsintervju - Fysisk - Sodeox',                '',                                                    1, 0, 0,    null],
            [94, 3,  'Bakgrundskontroll nivå 1 – Bas',                                               "ID-verifiering\r\nBetalningsförseelser\r\nRättslig förekomst\r\nMedborgarskap\r\nIn- och utvandring från Sverige\r\nAnalys och sammanfattning\r\n", 0, 0, 0, 2],
            [95, 3,  'Bakgrundskontroll nivå 2 - Säkerhetsbedömning',                               "ID-verifiering\r\nBetalningsförseelser\r\nRättslig förekomst\r\nMedborgarskap\r\nIn- och utvandring från Sverige\r\nInkomst 5 år\r\nInternetexponering\r\nNyhetsmedia\r\nSociala medier\r\nNamnbyten\r\nArbetslivserfarenhet 10 år\r\nUtbildning (samtliga)\r\nBolagsengagemang\r\nAdresshistorik 10 år\r\n", 0, 0, 0, 4],
            [96, 3,  'Bakgrundskontroll nivå 3 i samband med säkerhetsprövning',                    "ID-verifiering\r\nBetalningsförseelser\r\nRättslig förekomst\r\nMedborgarskap\r\nIn- och utvandring från Sverige\r\nInkomst 5 år\r\nInternetexponering\r\nNyhetsmedia\r\nSociala medier\r\nNamnbyten\r\nArbetslivserfarenhet 10 år\r\nUtbildning (samtliga)\r\nBolagsengagemang\r\nAdresshistorik 10 år\r\nCivilstånd\r\nFolkbokförda på samma adress\r\nFastighetsinnehav\r\nFordonsregister\r\n", 0, 0, 0, 5],
            [97, 3,  'Bakgrundskontroll nivå 4',                                                     "ID-verifiering\r\nBetalningsförseelser\r\nRättslig förekomst\r\nMedborgarskap\r\nIn- och utvandring från Sverige\r\nInkomst 5 år\r\nInternetexponering\r\nNyhetsmedia\r\nSociala medier\r\nNamnbyten\r\nArbetslivserfarenhet 10 år\r\nUtbildning (samtliga)\r\nBolagsengagemang\r\nAdresshistorik 10 år\r\nCivilstånd\r\nFolkbokförda på samma adress\r\nFastighetsinnehav\r\nFordonsregister\r\nNätverksanalys\r\nRiskanalys \r\nBolagsengagemang inkl. historik och analys\r\n", 0, 0, 0, 7],
        ];

        $now = now();
        return array_map(fn ($r) => [
            'id' => $r[0],
            'service_category_id' => $r[1],
            'name' => trim((string) $r[2]),
            'description' => $r[3] !== '' ? $r[3] : null,
            'place' => $r[4] === 1 ? 'Physical' : null,
            'country' => $r[5] !== 0 ? (string) $r[5] : null,
            'price' => max(0.0, (float) $r[6]),
            'delivery_days' => $r[7],
            'created_at' => $now,
            'updated_at' => $now,
        ], $raw);
    }
}
