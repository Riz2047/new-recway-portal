<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds a single "master default" row in the messages table.
 *
 *   cus_id       = 0  (no real customer — sentinel for "global default")
 *   interview_id = 0  (no real service   — sentinel for "global default")
 *
 * This row holds the default HTML body for every known msg_col key.
 * Admins can copy from it to any real customer + service combination
 * via the "Copy Templates" feature in the admin panel.
 *
 * Safe to re-run — uses updateOrInsert so it only ever creates/overwrites
 * the one sentinel row.
 */
class DefaultMessageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Decode HTML entities (e.g. &auml; → ä, &ouml; → ö, &nbsp; → space)
        // so templates are stored as clean UTF-8 HTML, not entity-encoded text.
        $templates = array_map(
            fn (string $body): string => str_replace(
                "\xc2\xa0",   // non-breaking space (U+00A0) left by &nbsp; → regular space
                ' ',
                html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8')
            ),
            $this->templates()
        );

        DB::table('messages')->updateOrInsert(
            ['cus_id' => 0, 'interview_id' => 0],
            ['templates' => json_encode($templates, JSON_UNESCAPED_UNICODE)]
        );

        $this->command->info('DefaultMessageTemplateSeeder: default templates stored (cus_id=0, interview_id=0).');
    }

    /** @return array<string, string> */
    private function templates(): array
    {
        return [
            'cus_msg' => '<p>Hej <strong>{customer}</strong>,</p><p>Tack för din beställning och din ordernummer är: <strong>{orderid}</strong></p><p>Information har skickats till  <strong>{candidate}</strong>  gällande din beställningen av säkerhetsprövningsintervjun.</p><p>Vi kommer inom kort kontakta  <strong>{candidate} </strong>för att boka  <strong>{interview}</strong>.</p><p>Du kan via portal följa förloppet av din beställning.</p><p>Med vänliga hälsningar</p><p>Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se/">https://www.recway.se</a></p><p><em>This e-mail is confidential and may contain legally privileged information. If you have received this email in error, please immediately notify us and delete the message from your system.</em></p>',

            'admin_msg' => '<p>Hej Admin </p><p>Ordernummer: <strong>{orderid}</strong></p><p>Du har fått beställning ifrån <strong>{company}</strong>, där {customer} har gjort beställningen- gäller för en  <strong>{interview} </strong>kandidaten heter <strong>{candidate}. </strong></p>',

            'staff_msg' => '<p>Hej <strong>{staff}!</strong></p><p>Du har blivit tilldelat en ny kandidat. </p><p>Kandidaten heter: <strong>{candidate}</strong> och det gäller en  <strong>{interview}</strong></p><p>Du kan via portalen se samtliga nödvändiga information kring beställningen. <br /><br />Logga in portal via länken här : <a href="https://orderspi.se/staff" target="_blank">Logga in </a><br /><br /></p><p>Med vänliga hälsningar<br />Recway – Vägen till en säkrare rekrytering</p><p>https://www.recway.se</p>',

            'can_msg' => '<p>Hello <strong>{candidate}</strong>,</p><p>We have received an order from the company <strong>{company}</strong>, with the order placed by <strong>{customer}</strong>. The interview will be conducted online via video.</p><p>You can log in using the following link to schedule a suitable time:<br /><a href="https://calendly.com/d/3sc-wsv-w7v" target="_blank">Schedule a time for the security interview</a></p><p><strong>Important!</strong><br />During the booking process, you will need to provide this order number, which is: <strong>{orderid}</strong></p><p>The interview will focus on you as an individual, and the questions may be more personal and private compared to a typical recruitment interview.</p><p>If you have any questions regarding the order, please contact <strong>{customer}</strong> at <strong>{company}.</strong></p><p><strong>Best regards,</strong><br />Recway – vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se" target="_blank">https://www.recway.se</a></p>',

            'can_msg_2' => '<p>Hej , <strong>{candidate}</strong></p><p>Vi har fått in en beställning från företaget <strong>{company} </strong> där  <strong>{customer}</strong> har gjort beställningen.</p><p>Intervjun kommer sker på plats. </p><p>Du kommer inom kort bli kontaktad utav oss. </p><p>Vid frågor kring beställningen kontakta gärna <strong>{customer} </strong>på <strong>{company}. </strong></p><p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se" target="_blank">https://www.recway.se</a></p>',

            'pending_msg' => '<p>Hej, <strong>{customer}, </strong></p><p>Uppdatering av status för beställningen: <strong>{orderid}</strong></p><p><strong>{candidate}</strong> har fortfarande inte bokat in sig men efter samtal med <strong>{candidate}  </strong>uppger denne att hen ska göra det. <strong><br /></strong></p><p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se" target="_blank">https://www.recway.se</a></p>',

            'booked_msg' => '<p>Hej, <strong> {customer}, </strong></p><p>Uppdatering av status för beställningen: <strong>{orderid}</strong></p><p><strong>{candidate}</strong> är nu inbokat för en <strong>{interview}</strong> den  <strong>{interview_date}.  </strong></p><p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p><p>https://www.recway.se</p>',

            'approved_msg' => '<p>Hej, <strong>{customer}, </strong></p><p>Uppdatering av status för beställningen: <strong>{orderid}</strong></p><p>Säkerhetsprövningsintervju har genomfört och <strong>{candidate} </strong>nu <span style="color: #11bd00;"><strong>GODKÄND</strong></span>. <br />Intervjun genomfördes av <strong>{staff} </strong>som har  e-post: <strong>{staff_email}</strong> den <strong>{interview_date}. <br /></strong><br />Datum: <strong>{date}<br /></strong></p><p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se" target="_blank">https://www.recway.se</a></p>',

            'approved_msg_2' => '<p>Hej, <strong>{customer}, </strong></p><p>Uppdatering av status för beställningen: <strong>{orderid}</strong></p><p>Säkerhetsprövningsintervju har genomfört och <strong>{candidate} </strong>nu <span style="color: #11bd00;"><strong>GODKÄND</strong></span>. <br />Intervjun genomfördes av <strong>{staff} </strong>som har  e-post: <strong>{staff_email}</strong> den <strong>{interview_date}. <br /></strong><br />Datum: <strong>{date}<br /></strong></p><p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se" target="_blank">https://www.recway.se</a></p>',

            'invest_msg' => '<p>Hej, <strong>{customer}, </strong></p><p>Uppdatering av status för beställningen: <strong>{orderid}</strong></p><p>Intervjun med  <strong>{candidate}</strong>  som var inbokat  <strong>{interview_date} </strong>blev avbruten.  Logga in i portalen för mer information. </p><p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p><p>https://www.recway.se</p>',

            'spo_msg' => '<p>Hej, <strong>{customer}, </strong></p><p>Uppdatering av status för beställningen:  <strong>{orderid}</strong></p><p>Intervjun med  <strong>{candidate} </strong> som var bokat  <strong>{date}  </strong>är under utredning hos SPO. Vi återkommer så snart vi har fått ett svar från SPO:n. </p><p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p><p>https://www.recway.se</p>',

            'denied_msg' => '<p>Hej, <strong>{customer}, </strong></p><p>Uppdatering av status för beställningen: <strong>{orderid}</strong></p><p>Efter samtal med SPO gällande  <strong>{candidate} </strong>har vi valt att <strong><span style="color: #ff0000;">inte godkänna</span></strong> kandidaten dvs <strong><span style="color: #ff0000;">ej godkänd</span></strong>. Intervjun genomfördes av  <strong>{staff} </strong> som har e-post: <strong>{staff_email}</strong>  den  <strong>{interview_date}. </strong></p><p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p><p>https://www.recway.se</p>',

            'denied_msg_2' => '<p>Hej, <strong>{customer}, </strong></p><p>Uppdatering av status för beställningen: <strong>{orderid}</strong></p><p>Efter samtal med SPO gällande  <strong>{candidate} </strong>har vi valt att <strong><span style="color: #ff0000;">inte godkänna</span></strong> kandidaten dvs <strong><span style="color: #ff0000;">ej godkänd</span></strong>. Intervjun genomfördes av  <strong>{staff} </strong> som har e-post: <strong>{staff_email}</strong>  den  <strong>{interview_date}. </strong></p><p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p><p>https://www.recway.se</p>',

            'notshow_msg' => '<p>Hej, <strong>{customer}, </strong></p><p>Uppdatering av status för beställningen: <strong>{orderid}</strong></p><p><strong>{candidate} </strong>dök inte upp på den avtalade och bestämde tiden. Intervjun var inbokat den : <strong>{interview_date}. <br /><br /></strong>Vi kommer prova igen att kontakta kandidaten för att boka ny tid. </p><p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p><p>https://www.recway.se</p>',

            'canceled_msg' => '<p>Hej <strong>{customer}</strong>,<br /><br />Uppdatering av status gällande ordernummer:  <strong>{orderid}</strong></p><p>Vi har valt att avbryta  <strong>{interview}  </strong>med <strong>{candidate} </strong>som var bokat <strong>{interview_date}.  </strong></p><p><strong>Med vänliga hälsningar</strong></p><p>Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se/">https://www.recway.se</a></p>',

            'noans_msg' => '<p>Hej, <strong>{customer}, </strong></p><p>Uppdatering av status för beställningen: <strong>{orderid}</strong></p><p>Vi har försök  nå <strong>{candidate} </strong>vid flera tillfällen utan resultat. Vi vill gärna att ni kontaktar  <strong>{candidate} </strong>för att följa upp bokningen. </p><p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p><p>https://www.recway.se</p>',

            'can_cancel' => '<p>Hej, <strong>{candidate},&nbsp;</strong><br /><br />Din säkerhetsprövningsintervju har blivit avbruten av beställaren&nbsp; <strong>{company}.</strong></p><p>Har du frågor eller funderingar kring beställningen var god kontakta <strong>{customer}</strong> på&nbsp; <strong>{company}</strong></p><p>Vi kommer därför radera all information vi samlat in om er och varken du eller beställaren kommer kunna ta del av beställningen</p><p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p><p>https://www.recway.se</p>',

            'staff_cancel' => '<p>Hej, <strong>{staff}, </strong><br /><br />Beställningen för <strong>{candidate}</strong> som har <strong>{orderid} </strong>har blivit avbruten av kunden. </p><p>Med vänliga hälsningar<br />Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se">https://www.recway.se</a></p>',

            'REbook_interviews' => '<p>Hej, <strong>{customer},&nbsp;</strong></p><p>Uppdatering av status för beställningen: <strong>{orderid}</strong></p><p><strong>{candidate} </strong>blivit ombokat/bokat om sin säkerhetsprövningsintervju.&nbsp;<br /><br />Datum: <strong>{date}<br /></strong></p><p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se" target="_blank">https://www.recway.se</a></p>',

            'still_not_booked_msg' => '<p>Hej, <strong>{customer},&nbsp;</strong></p><p>Status uppdatering gällande ordernummer: <strong>{orderid} </strong></p><p><strong>{candidate} </strong>har fortfarande inte bokat in sig för en intervju. Vi har tidigare mejlat kandidaten med information om beställningen.</p><p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se" target="_blank">https://www.recway.se</a></p>',

            'not_available_msg' => '<p>Hej, <strong>{customer},&nbsp;</strong></p><p>Status uppdatering gällande ordernummer: <strong>{orderid}.&nbsp;</strong></p><p>Senaste kommunikation med <strong>{candidate}</strong> informerade kandidaten om att de föreslagna tiderna för de kommande veckorna inte passar då <strong>{candidate}</strong> är inte tillgängligt.&nbsp;<br /><br />Se kommenterar:&nbsp; <strong>{comment}</strong></p><p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se" target="_blank">https://www.recway.se</a></p>',

            'Contact_established' => '<p>Hej <strong>{customer}</strong>,</p><p>Vi vill informera dig om att vi nu har etablerat kontakt med <strong>{candidate}</strong> gällande din beställning av <strong>{service}</strong>. Vi återkommer med ytterligare information så snart vi har en uppdatering.</p><p>Du kan via portal följa förloppet av din beställning.</p><p>Med vänliga hälsningar<br />Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se/">https://www.recway.se</a></p>',

            'withoutdeviation' => '<p>Hello, <strong>{customer},&nbsp;</strong></p><p>Status update for the order: <strong>{orderid}</strong></p><p>The interview with the candidate booked on <strong>{date}</strong> has been completed without deviations.</p><p><strong>Best regards </strong></p><p>Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se" target="_blank" rel="noopener">https://www.recway.se</a></p>',

            'Candidate_Cancel' => '<p>Hello, <strong>{customer},&nbsp;</strong></p><p>Status update for the order: <strong>{orderid}</strong></p><p><strong>{candidate}</strong> has canceled their appointment which was on <strong>{interview_date}.&nbsp; </strong><br />We will get back to you as soon as the candidate has booked a new appointment.</p><p><strong>Best regards&nbsp;</strong></p><p>Recway – Vägen till en säkrare rekrytering</p><p>https://www.recway.se</p>',

            // Background check templates
            'cus_msg_background' => '<p>Hej <strong>{customer}</strong>,</p><p>Tack för din beställning och din ordernummer är: <strong>{orderid}</strong></p><p>Information har skickats till  <strong>{candidate}</strong>  gällande din beställningen av <strong>{service}</strong>.</p><p>Vi kommer inom kort skicka ett samtyckte till <strong>{candidate}</strong>  .</p><p>Du kan via portal följa förloppet av din beställning.</p><p>Med vänliga hälsningar</p><p>Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se/">https://www.recway.se</a></p>',

            'can_msg_background' => '<p><strong>Hej , {candidate}</strong></p><p>Vi har fått in en beställning från företaget gällande bakgrundskontroll.&nbsp;</p><p>Inom kort kommer du att få ett samtyckte ifrån oss.&nbsp;</p><p>Vid frågor kring beställningen kontakta gärna {customer} på {company}.&nbsp;</p><p>Med vänliga hälsningar<br />Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se">https://www.recway.se</a></p>',

            'pending_background' => '<p><strong>Hej, {customer},&nbsp;</strong></p><p>Uppdatering av status för beställningen: <strong>{orderid}</strong></p><p><strong>{candidate}</strong> har fortfarande inte skickat in sitt samtyckte men efter samtal med <strong>{candidate}</strong>&nbsp;&nbsp;uppger denne att hen ska göra det.&nbsp;</p><p>Med vänliga hälsningar<br />Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se">https://www.recway.se</a></p>',

            'consent_msg' => '<p><strong>Hej, {customer},&nbsp;</strong></p><p>Uppdatering av status för beställningen: <strong>{orderid}</strong></p><p>Ett samtyckte idag <strong>{date}</strong> inskickat till <strong>{candidate}</strong></p><p>Med vänliga hälsningar<br />Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se">https://www.recway.se</a></p>',

            'approval_received_msg' => '<p>Hej, <strong>{customer},&nbsp;</strong></p><p>Uppdatering av status för beställningen: <strong>{orderid}</strong></p><p>Ett godkännande samtyckte idag <strong>{date}</strong> inkommit till oss från <strong>{candidate}</strong></p><p>Med vänliga hälsningar<br />Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se">https://www.recway.se</a></p>',

            'research_started_msg' => '<p>Hej, <strong>{customer},&nbsp;</strong></p><p>Uppdatering av status för beställningen: <strong>{orderid}</strong></p><p>Vi har idag <strong>{date}</strong> påbörjat bakgrundskontrollen för <strong>{candidate}</strong>.&nbsp;</p><p>Inom 1-3 arbetsdagar inkommer vi med återkoppling.&nbsp;</p><p>Med vänliga hälsningar<br />Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se">https://www.recway.se</a></p>',

            'results_received_msg' => '<p>Hej, <strong>{customer},&nbsp;</strong></p><p>Uppdatering av status för beställningen: <strong>{orderid}</strong></p><p>Vi har inkommit med återkoppoing gällande <strong>{service}</strong></p><p>Logga in på portalen för att se resultatet.&nbsp;</p><p>Med vänliga hälsningar<br />Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se">https://www.recway.se</a></p>',

            'deviation' => '<p>Hej, <strong>{reviewer},&nbsp;</strong></p><p>Uppdatering av status för beställningen: <strong>{orderid}</strong></p><p>Vi har inkommit med återkoppoing gällande <strong>{service}&nbsp;</strong>för <strong>{candidate}.&nbsp;</strong></p><p>Det finns avvikelse i rapporten. Du har möjlighet att godkänna eller avslå. När du har gjort det kommer resultat skickas till beställaren <strong>{customer}</strong></p><p>Logga in på portalen för att se resultatet.&nbsp;</p><p>Med vänliga hälsningar<br />Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se">https://www.recway.se</a></p>',

            'approved_msg_bc' => '<p>Hej, <strong>{customer},</strong></p><p>Uppdatering av status för beställningen: <strong>{orderid}</strong></p><p><strong>{service}</strong> har genomfört och <strong>{candidate}</strong> är nu <strong><span style="color: #339966;">GODKÄND</span></strong>. </p><p>Du kan se rapporten i portalen. </p><p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering<br /><a href="https://www.recway.se">https://www.recway.se</a></p>',

            'bk_can_cancel' => '<p>Hej, <strong>{candidate},&nbsp;</strong><br /><br />Din säkerhetsprövningsbakgrundskontroll har blivit avbruten av beställaren&nbsp; <strong>{company}.</strong></p><p>Har du frågor eller funderingar kring beställningen var god kontakta <strong>{customer}</strong> på&nbsp; <strong>{company}</strong></p><p>Vi kommer därför radera all information vi samlat in om er och varken du eller beställaren kommer kunna ta del av beställningen</p><p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p><p>https://www.recway.se</p>',

            // Follow-up templates
            'candidate_msg' => '<p>Hej <strong>{customer}</strong>,</p><p>Tack för din beställning och din ordernummer är: <strong>{orderid}</strong></p><p>Information har skickats till&nbsp; <strong>{candidate}</strong>&nbsp; gällande din beställningen av <strong>{service}</strong></p><p>Vi kommer inom kort kontakta&nbsp; <strong>{candidate} </strong>för att boka&nbsp; <strong>{service}</strong>.</p><p>Du kan via portal följa förloppet av din beställning.</p><p>Med vänliga hälsningar</p><p>Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se/">https://www.recway.se</a></p>',

            'Pending' => '<p>Hej, <strong>{customer},&nbsp;</strong></p><p>Uppdatering av status för beställningen: <strong>{orderid}</strong></p><p><strong>{candidate}</strong> har fortfarande inte bokat in sig men efter samtal med <strong>{candidate}&nbsp;&nbsp;</strong>uppger denne att hen ska göra det.&nbsp;</p><p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se" target="_blank">https://www.recway.se</a></p>',

            'Booked' => '<p>Hej, <strong> {customer},&nbsp;</strong></p><p>Uppdatering av status för beställningen: <strong>{orderid}</strong></p><p><strong>{candidate}</strong> är nu inbokat för en <strong>{service}</strong> den <strong>{interview_date}.&nbsp; </strong></p><p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p><p>https://www.recway.se</p>',

            'Candidatedidntshowup' => '<p>Hej, <strong>{customer},&nbsp;</strong></p><p>Status uppdatering gällande ordernummer: <strong>{orderid}.&nbsp;</strong></p><p><strong>{candidate}</strong> dök inte upp på den avtalade och bestämde tiden. Intervjun var inbokat den : <strong>{interview_date} .</strong> Vi försökte nå kandiditen både via sms och samtal utan resultat. <br /><br />Följ gärna upp med kandidaten.&nbsp;&nbsp;</p><p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p><p>https://www.recway.se</p>',

            'Approved_followup' => '<p>Hej, <strong>{customer},&nbsp;</strong></p><p>Uppdatering av status för beställningen: <strong>{orderid}</strong></p><p>Uppföljningssamtalen har genomfört med <strong>{candidate}</strong>.&nbsp;</p><p>Datum: <strong>{date}<br /></strong></p><p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se" target="_blank">https://www.recway.se</a></p>',

            'follow_up_under_investigation' => '<p>Hej, <strong>{customer},&nbsp;</strong></p><p>Uppdatering av status för beställningen: <strong>{orderid}</strong></p><p>Intervjun med <strong>{candidate} </strong> som var bokat <strong>{date} </strong>är under utredning vi har lämnat ärendet till säkerhetsavdelningen hos er på <strong>{company}</strong>. Vi återkommer så snart vi har fått ett svar från säkerhetsavdelningen på <strong>{company}.&nbsp;</strong></p><p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p><p>https://www.recway.se</p>',

            'Candidate_interup_followup' => '<p>Hej, <strong>{customer}, </strong></p><p>Uppdatering av status för beställningen: <strong>{orderid}</strong></p><p>Intervjun med  <strong>{candidate}</strong>  som var inbokat  <strong>{interview_date} </strong>blev avbruten.</p><p>Anledning: <strong>{comment}</strong></p><p>Logga in i portalen för mer information. </p><p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p><p>https://www.recway.se</p>',

            'denid_followup' => '<p>Hej, <strong>{customer}, </strong></p><p>Uppdatering av status för beställningen: <strong>{orderid}</strong></p><p>Efter samtal med SPO gällande  <strong>{candidate} </strong>har vi valt att <strong><span style="color: #ff0000;">inte godkänna</span></strong> kandidaten dvs <strong><span style="color: #ff0000;">ej godkänd</span></strong>. Intervjun genomfördes av  <strong>{staff} </strong> som har e-post: <strong>{staff_email}</strong>  den  <strong>{interview_date}. </strong></p><p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p><p>https://www.recway.se</p>',

            'still_not_booked_followup' => '<p>Hej, <strong>{customer},&nbsp;</strong></p><p>Status uppdatering gällande ordernummer: <strong>{orderid}&nbsp;</strong></p><p><strong>{candidate}</strong> har fortfarande inte bokat in sig på <strong>{service}</strong>.&nbsp; Vi har tidigare mejlat kandidaten med information om beställningen. Vänligen följ upp med kandidaten.&nbsp;</p><p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se" target="_blank">https://www.recway.se</a></p>',

            'no_avaible_followup' => '<p>Hej, <strong>{customer}, </strong></p><p>Status uppdatering gällande ordernummer: <strong>{orderid}. </strong></p><p>Senaste kommunikation med <strong>{candidate}</strong> informerade kandidaten om att de föreslagna tiderna för de kommande veckorna inte passar då <strong>{candidate}</strong> är inte tillgängligt. <br /><br />Se kommenterar:  <strong>{comment}</strong></p><p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se" target="_blank">https://www.recway.se</a></p>',

            'Customer_flow' => '<p>Hej <strong>{customer}</strong>,</p><p>Vi vill informera dig om att vi nu har etablerat kontakt med <strong>{candidate}</strong> gällande din beställning av <strong>{service}</strong>. Vi återkommer med ytterligare information så snart vi har en uppdatering.</p><p>Du kan via portal följa förloppet av din beställning.</p><p>Med vänliga hälsningar<br />Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se/">https://www.recway.se</a></p>',

            // Exit interview
            'Cus_msg_exit' => '<p>Hej <strong>{customer}</strong>,</p><p>Tack för din beställning och din ordernummer är: <strong>{orderid}</strong></p><p>Information har skickats till  <strong>{candidate}</strong>  gällande din beställningen av {service}.</p><p>Vi kommer inom kort kontakta  <strong>{candidate} </strong>för att boka  <strong>{service}</strong>.</p><p>Du kan via portal följa förloppet av din beställning.</p><p>Med vänliga hälsningar</p><p>Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se/">https://www.recway.se</a></p>',

            // Additional templates from status_services
            'person_startedf' => 'Started',

            'Reference_check' => '<p><strong>Hej {customer},</strong></p><p>Tack för din beställning. Ordernummer för denna beställning är: <strong>{orderid}</strong>.</p><p>Vi vill informera om att referenstagningen nu är genomförd. Inom högst två arbetsdagar kommer vi att kontakta kandidaten för att erbjuda alternativa tider för intervjun.</p><p><strong>Med vänliga hälsningar</strong></p><p>Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se/">https://www.recway.se</a></p>',

            'Concentdone1' => '<p><strong>Hej {customer},</strong></p><p>Uppdatering av status för beställningen: <strong>{orderid}</strong></p><p>Vi vill informera om att kandidaten <strong>{candidate}</strong> idag har lämnat in samtycke och referenser. Vi påbörjar nu referenstagningen genom att kontakta de angivna referenserna. När detta är klart uppdateras status i portalen till "Referenser klara" och därefter inleds bokningen av intervjun.</p><p>Med vänliga hälsningar<br />Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se">https://www.recway.se</a></p>',

            'Candidate_Cancel_Followup' => '<p>Hej, <strong>{customer}, </strong></p><p>Status uppdatering gällande ordernummer: <strong>{orderid}. </strong></p><p>Vi vill informera om att <strong>{candidate}</strong> har avbokat sin planerade tid. Vi ber er att följa upp med kandidaten för att se om en ny bokning är aktuell.</p><p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se/">https://www.recway.se</a></p>',

            'customer_cancel_Order' => '<p>Hej, <strong>{customer}, </strong></p><p>Uppdatering av status för beställningen: <strong>{orderid}</strong></p><p>Beställningen har blivit avbruten av kunden.</p><p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se/">https://www.recway.se</a></p>',

            'referens_grundutrening_klar' => '<p>Hej, <strong>{customer}, </strong></p><p>Uppdatering av status för beställningen: <strong>{orderid}</strong></p><p>Referenser och grundutredning är nu klar för <strong>{candidate}</strong>.</p><p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se/">https://www.recway.se</a></p>',

            'Concentdone2' => '<p><strong>Hej {customer},</strong></p><p>Uppdatering av status för beställningen: <strong>{orderid}</strong></p><p>Samtycket har inkommit från <strong>{candidate}</strong>.</p><p>Med vänliga hälsningar<br />Recway – Vägen till en säkrare rekrytering</p><p><a href="https://www.recway.se">https://www.recway.se</a></p>',
        ];
    }
}
