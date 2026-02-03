<?php

include_once('../../includes/functions.php');

// Create a DateTime object for Sweden's timezone

$swedenTimezone = new DateTimeZone('Europe/Stockholm');



// Create a DateTime object for the current time in Sweden

$swedenTime = new DateTime('now', $swedenTimezone);

$currentTime = $swedenTime->format('H:i:s');

$dayOfWeek = date('N');



//matching day b/w Monday to friday and time between 8am to 5pm

if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {

    $emails = findAllByQuery('SELECT * FROM emails WHERE email_delay = 1');

    $currentTimeInSweden = $swedenTime->format('Y-m-d H:i:s');

    if (!empty($emails)) {

        foreach ($emails as $email) {

            if ($email->order_id != 'N/A') {

                $candidate = findAllByQuery("SELECT * FROM candidates WHERE order_id = '$email->order_id'")[0];

                $customer = findAllByQuery("SELECT * FROM customers WHERE id = $candidate->cus_id")[0];

                $status = getStatusById($candidate->status);

                if (isEmailAllowed($candidate->cus_id, $status->id)) {

                    $directory = "../../security-report-uploads/";

                    $filename = $candidate->interview_report;

                    if (($status->variable == "approved" || $status->variable == "denied") && !empty($filename) && file_exists($directory . $filename) && $customer->send_security_report == 1) {

                        sendMail($email->text, $email->email, $email->user_name, $email->subject, $directory . $filename);

                    } else {

                        sendMail($email->text, $email->email, $email->user_name, $email->subject);

                    }

                }



                if ($status->variable == "canceledbycustomer") {

                    $emailMsg =  sendMail($email->text, $email->email, $email->user_name, $email->subject);

                }



                update('emails', ['delay_email_sent_at' => $currentTimeInSweden, 'email_delay' => 0], 'id', $email->id);

            } else {

                sendMail($email->text, $email->email, $email->user_name, $email->subject);

                update('emails', ['delay_email_sent_at' => $currentTimeInSweden, 'email_delay' => 0], 'id', $email->id);

            }

        }

    }

}

