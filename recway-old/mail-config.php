<?php

$query = 'SELECT * FROM messages LIMIT 1';

$stmt = $conn->prepare($query);

$stmt->execute();

$messages = $stmt->fetch();

$query = "SELECT * FROM settings";

$stmt = $conn->prepare($query);

$stmt->execute();

$settings = $stmt->fetchAll();

foreach ($settings as $setting) {

    $var = $setting->name;

    $$var = $setting->value;

}

const SMTP_HOST = 'smtp.office365.com';

const SMTP_PORT = 587;

const USERNAME = 'info@recway.se';

const PASSWORD = 'kmqfwjjnhfmktgnp';

$email_from = $emailFrom;
