<?php

$query = 'SELECT * FROM candidates';

$stmt = $conn->prepare($query);

$stmt->execute();

$candidates = $stmt->fetchAll();



$statuses = getStatuses();



if (!empty($candidates)) {

    foreach ($candidates as $candidate) {

        //        if($candidate->status == 2 || $candidate->status == 7) {

        $query = 'SELECT * FROM customers WHERE id = ?';

        $stmt = $conn->prepare($query);

        $stmt->execute([$candidate->cus_id]);

        $customer = $stmt->fetch();

        $status2 = getStatusById($candidate->status);



        $query = 'SELECT * FROM history WHERE order_id = ? AND `desc` = ? ORDER BY id DESC LIMIT 1';

        $stmt = $conn->prepare($query);

        $stmt->execute([$candidate->id, $status2->status_detail]);

        $history = $stmt->fetch();



        if ($history) {

            $status = $status2->id;

            $status_date = $history->date_time;

        } else {

            $status = 1;

            $status_date = $candidate->created;

        }

        $days = 14;

        if (!empty($customer->report_delete_duration)) {

            $days = $customer->report_delete_duration;

        }

        $expired = date("Y-m-d", strtotime($candidate->created . " + " . $days . " days "));

        if ($candidate->invoice_sent == 1 && $candidate->expired == 0) {

            if ($candidate->status == 4 || $candidate->status == 7 || $candidate->status == 21 || $candidate->status == 22 || $candidate->status == 9 || $candidate->status == 37 || $candidate->status == 40 || $candidate->status == 42) {
                $query = 'SELECT * FROM history WHERE `order_id` = ? ORDER BY `id` DESC LIMIT 1';
                $stmt = $conn->prepare($query);
                $stmt->execute([$candidate->id]);
                $history = $stmt->fetch();
                $daysToArchive = "N/A";
                if ($history) {
                    // Extract the date from the `date_time` column
                    $recordDate = new DateTime($history->date_time);
                    $currentDate = new DateTime(); // Current date and time

                    // Calculate the difference in days
                    $interval = $recordDate->diff($currentDate);
                    $daysElapsed = $interval->days; // Number of days passed

                    // Subtract elapsed days from 28
                    $daysRemaining = 28 - $daysElapsed;

                    if ($daysRemaining > 0) {
                        $daysToArchive = $daysRemaining;
                    } else {
                        $daysToArchive = "already_archived";
                    }
                }
            } else {
                $daysToArchive = "N/A";
            }


            if ($daysToArchive == "already_archived") {
                $query = 'INSERT INTO order_history (company, cus_id, staff_id, order_id, interview_id, created, status, status_date, invoice_date) VALUES (?,?,?,?,?,?,?,?,?)';

                $stmt = $conn->prepare($query);

                $res = $stmt->execute([

                    $customer->company,
                    $candidate->cus_id,
                    $candidate->staff_id,
                    $candidate->order_id,
                    $candidate->interview_id,

                    $candidate->created,
                    $status,
                    $status_date,
                    $candidate->invoice_date

                ]);



                $query = 'UPDATE candidates SET expired = 1 WHERE id = ?';

                $stmt = $conn->prepare($query);

                $stmt->execute([$candidate->id]);
            }

        }

    }

}

// Create a DateTime object for Sweden's timezone

$swedenTimezone = new DateTimeZone('Europe/Stockholm');



// Create a DateTime object for the current time in Sweden

$swedenTime = new DateTime('now', $swedenTimezone);

$currentTime = $swedenTime->format('H:i:s');

$dayOfWeek = date('N');



$query = "SELECT ca.order_id,ca.name,ca.surname,ca.booked,ca.vasc_id,cu.name as cus_name,cu.company,cu.email,cu.cost_place,cu.phone,cu.remainder_email_template,interviews.title as interview_title,places.name as place_name,statuses.status as status_title FROM candidates as ca INNER JOIN customers as cu ON ca.cus_id = cu.id LEFT JOIN interviews ON ca.interview_id=interviews.id LEFT JOIN places ON ca.place=places.id LEFT JOIN statuses ON ca.status=statuses.id  WHERE ca.expired = '1' AND ca.status IN ('4', '37') AND interviews.service_cat_id IN ('1', '9')  AND ca.booked < DATE_SUB(NOW(), INTERVAL 11 MONTH) AND cu.remainder_email = '1'";

$stmt = $conn->prepare($query);

$stmt->execute();

$interviews_candidates = $stmt->fetchAll();



foreach ($interviews_candidates as $row) {

    $cus_name = $row->cus_name;

    $can_name = $row->name . ' ' . $row->surname;

    $company = $row->company;

    $interview = $row->interview_title;

    $staff = $row->status_title;

    $status = '';

    $date = '';

    $orderID = $row->order_id;

    $staff_email = '';

    $comment = '';

    $vasc_id = $row->vasc_id;

    $service_title = '';

    $place = $row->place_name;

    $query = 'SELECT * FROM emails WHERE order_id = ? AND `msg_type` = ?';

    $stmt = $conn->prepare($query);

    $stmt->execute([$orderID, "Customer Remainder Message"]);

    $history = $stmt->fetchAll();

    if (empty($history)) {

        $body = replace(!empty($row->remainder_email_template) ? $row->remainder_email_template : '', $cus_name, $can_name, $company, $interview, $staff, '', '', $status, $date, $orderID, $date, '', $comment, $vasc_id, $interview, $place);

        if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {

            saveEmail("Customer", $cus_name, $orderID, 'Customer Remainder Message', $body, $row->email, 'Påminnelse om årliga uppföljningssamtal');

            sendMail($body, $row->email, $cus_name, 'Påminnelse om årliga uppföljningssamtal');

        } else {

            saveEmail("Customer", $cus_name, $orderID, 'Customer Remainder Message', $body, $row->email, 'Påminnelse om årliga uppföljningssamtal', '1');

        }

    }

}


$query = "SELECT ca.order_id,ca.name,ca.surname,ca.booked,ca.vasc_id,cu.name as cus_name,cu.company,cu.email,cu.cost_place,cu.phone,cu.bk_remainder_email_template,interviews.title as interview_title,places.name as place_name,statuses.status as status_title FROM candidates as ca INNER JOIN customers as cu ON ca.cus_id = cu.id LEFT JOIN interviews ON ca.interview_id=interviews.id LEFT JOIN places ON ca.place=places.id LEFT JOIN statuses ON ca.status=statuses.id  WHERE ca.expired = '1' AND ca.status IN ('18', '21', '22') AND interviews.service_cat_id IN ('3') AND ca.delivery_date < DATE_SUB(NOW(), INTERVAL 11 MONTH) AND cu.bk_remainder_email = '1'";
$stmt = $conn->prepare($query);
$stmt->execute();
$bk_candidates = $stmt->fetchAll();

foreach ($bk_candidates as $row) {
    $cus_name = $row->cus_name;
    $can_name = $row->name . ' ' . $row->surname;
    $company = $row->company;
    $interview = $row->interview_title;
    $staff = $row->status_title;
    $status = '';
    $date = '';
    $orderID = $row->order_id;
    $staff_email = '';
    $comment = '';
    $vasc_id = $row->vasc_id;
    $service_title = '';
    $place = $row->place_name;
    $query = 'SELECT * FROM emails WHERE order_id = ? AND `msg_type` = ?';
    $stmt = $conn->prepare($query);
    $stmt->execute([$orderID, "Customer Remainder Message"]);
    $history = $stmt->fetchAll();
    if (empty($history)) {
        $body = replace(!empty($row->bk_remainder_email_template) ? $row->bk_remainder_email_template : '', $cus_name, $can_name, $company, $interview, $staff, '', '', $status, $date, $orderID, $date, '', $comment, $vasc_id, $interview, $place);
        if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
            saveEmail("Customer", $cus_name, $orderID, 'Customer Remainder Message', $body, $row->email, 'Påminnelse om årliga uppföljningssamtal');
            sendMail($body, $row->email, $cus_name, 'Påminnelse om årliga uppföljningssamtal');
        } else {
            saveEmail("Customer", $cus_name, $orderID, 'Customer Remainder Message', $body, $row->email, 'Påminnelse om årliga uppföljningssamtal', '1');
        }
    }
}
