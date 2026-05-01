<?php

$sessionLifetime = 86400; // 8 hours default
if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/admin2/') !== false) {
    $sessionLifetime = 86400; // 24 hours for admin2
}
ini_set('session.gc_maxlifetime', $sessionLifetime);
session_set_cookie_params($sessionLifetime);
session_start();
ini_set('display_errors', true);
ini_set('display_startup_errors', true);
error_reporting(E_ALL);
//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

include('connection.php');
include('config.php');
global $pdo;
$conn = $pdo->open();
$message = '';
//function statusColors() {
//    return ['#ffa200', '#008B8B', '#00FF00', '#964B00', '#FFFF00', '#FF0000', '#FF00FF', '#7B94A8', '#416c96'];
//}
// Also present in expire.php and ajax.php
$statuses = ["Pending", "Booked", "Approved", "Interview Interrupted", "Under investigation with SPO", "Denied", "Did not show up", "Canceled", "Candidate doesn't answer"];
//$statuses = ["Pending", "Candidate don't answer", "Canceled", "Booked", "Did not show up", "Interview Interrupted", "Under investigation with SPO", "Approved", "Denied"];
$statusesDetail = ["Pending", "Interview has been booked", "Candidate has been approved", "Interview Interrupted", "Candidate is under investigation with SPO", "Candidate has been denied after meeting with SPO", "Candidate did not show up", "Interview has been canceled", "Candidate doesn't answer"];
$statusIcons = ["bi-hourglass-split", "bi-journal-check", "bi-clipboard-check", "bi-x-square", "bi-search", "bi-x-circle", "bi-emoji-dizzy", "bi-trash", "bi-envelope-slash"];
$statusSort = [0 => 0, 8 => 1, 7 => 2, 1 => 3, 6 => 4, 3 => 5, 4 => 6, 2 => 7, 5 => 8];
const INTERVIEW_ID = 1;
const BACKGROUND_ID = 3;
/**
 * Get allowed permissions (by title) for the currently logged-in staff user.
 */
function getStaffAllowedPermissions(): array
{
    global $conn;
    if (! isset($_SESSION['staff']) || empty($_SESSION['staff']->id)) {
        return [];
    }
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $query = 'SELECT category FROM staff WHERE id = ?';
    $stmt = $conn->prepare($query);
    $stmt->execute([$_SESSION['staff']->id]);
    $login_user = $stmt->fetch();
    if (empty($login_user) || empty($login_user->category)) {
        $cache = [];
        return $cache;
    }
    if (! isset($_SESSION['user_category']) || $_SESSION['user_category'] != $login_user->category) {
        $_SESSION['user_category'] = $login_user->category;
    }
    $query = 'SELECT * FROM user_category WHERE id = ?';
    $stmt = $conn->prepare($query);
    $stmt->execute([$login_user->category]);
    $category_permissions = $stmt->fetchAll();
    if (empty($category_permissions) || empty($category_permissions[0]->permissions_id)) {
        $cache = [];
        return $cache;
    }
    $staff_permissions_ids = explode(',', $category_permissions[0]->permissions_id);
    $allowed_staff_permission = [];
    foreach ($staff_permissions_ids as $staff_permission_id) {
        $staff_permission_id = (int)trim($staff_permission_id);
        if ($staff_permission_id <= 0) {
            continue;
        }
        $query = 'SELECT title FROM user_permissions WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$staff_permission_id]);
        $result = $stmt->fetch();
        if (! empty($result) && ! empty($result->title)) {
            $allowed_staff_permission[$result->title] = 1;
        }
    }
    $cache = $allowed_staff_permission;
    return $cache;
}
/**
 * Check if current staff user has a specific permission title.
 *
 * Example:
 *   if (staffHasPermission('view_customer')) { ... }
 */
function staffHasPermission(string $permissionTitle): bool
{
    $perms = getStaffAllowedPermissions();
    return isset($perms[$permissionTitle]) && ! empty($perms[$permissionTitle]);
}
require __DIR__ . '/../PHPMailer/vendor/autoload.php';
include_once __DIR__ . '/../mail-config.php';
//if(strpos($_SERVER['REQUEST_URI'], 'admin') || strpos($_SERVER['REQUEST_URI'], 'staff') || strpos($_SERVER['REQUEST_URI'], 'customer') || strpos($_SERVER['REQUEST_URI'], 'reviewer')) {
//    //Load Composer's autoloader
//    require '../PHPMailer/vendor/autoload.php';
//
//    include_once("../mail-config.php");
//}else {
//    //Load Composer's autoloader
//    require 'PHPMailer/vendor/autoload.php';
//
//    include_once("mail-config.php");
//
////    require '../PHPMailer/vendor/autoload.php';
////
////    include_once("../mail-config.php");
//}
function redirect($path)
{
    echo '<script>window.location.href = "' . $path . '"</script>';
}
function login($userTable)
{
    global $conn;
    global $message;
    if (isset($_POST['login'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        $query = 'SELECT * FROM ' . $userTable . ' WHERE email = ? LIMIT 1';
        $stmt = $conn->prepare($query);
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        //Check if username is correct
        if ($user) {
            // Check if password is correct
            if (password_verify($password, $user->password)) {
                //Storing user in session
                if ($userTable === 'admin') {
                    $_SESSION['admin'] = $user;
                } elseif ($userTable === 'customers') {
                    $_SESSION['customer'] = $user;
                } elseif ($userTable === 'staff') {
                    $_SESSION['staff'] = $user;
                }
                $message = '<p class="text-success">Logged in successfully!</p>';
                if (isset($_SESSION['previous_page']) && ! empty($_SESSION['previous_page'])) {
                    redirect($_SESSION['previous_page']);
                }
                redirect('index.php');
            } else {
                $message = '<p class="text-danger">Email or password is wrong!</p>';
            }
        } else {
            $message = '<p class="text-danger">Email or password is wrong!</p>';
        }
        return $message;
    }
}
function loginAdmin2($userTable)
{
    global $conn;
    global $message;
    if (isset($_POST['login'])) {
        function getUserIP()
        {
            if (! empty($_SERVER['HTTP_CLIENT_IP'])) {
                return $_SERVER['HTTP_CLIENT_IP'];
            } elseif (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                return $_SERVER['REMOTE_ADDR'];
            }
        }
        $log_data = [
            'ip_address' => getUserIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'request_uri' => $_SERVER['REQUEST_URI'],
            'http_referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
            'request_time' => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
        ];
        $sql = "INSERT INTO request_logs (ip_address, user_agent, request_method, request_uri, http_referer, request_time) VALUES (:ip_address, :user_agent, :request_method, :request_uri, :http_referer, :request_time)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':ip_address', $log_data['ip_address']);
        $stmt->bindParam(':user_agent', $log_data['user_agent']);
        $stmt->bindParam(':request_method', $log_data['request_method']);
        $stmt->bindParam(':request_uri', $log_data['request_uri']);
        $stmt->bindParam(':http_referer', $log_data['http_referer']);
        $stmt->bindParam(':request_time', $log_data['request_time']);
        $stmt->execute();
        $email = $_POST['email'];
        $password = $_POST['password'];
        $query = 'SELECT * FROM ' . $userTable . ' WHERE email = ? LIMIT 1';
        $stmt = $conn->prepare($query);
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        //Check if username is correct
        if ($user) {
            // Check if password is correct
            if (password_verify($password, $user->password)) {
                $_SESSION['email'] = $email;
                $_SESSION['userTable'] = $userTable;
                $otpDB = findByQuery("SELECT * FROM otp_verification WHERE email = '{$_SESSION['email']}' AND date_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
                if (! empty($otpDB)) {
                    $otp = $otpDB->otp;
                } else {
                    $otp = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
                    insert("otp_verification", ['email' => $email, 'otp' => $otp]);
                }
                $body = '<div>
                              <div style="background-color: #AC0206; color: #ffffff; text-align:center; padding: 4px">
                                <h2>Your verification code</h2>
                              </div>
                              <div>
                                <p>Use the following verification code to login</p>
                                <h1 style="background-color: #dddddd; padding: 5px 10px; border-radius: 10px; width: fit-content">' . $otp . '</h1>
                                <small>This code is valid for 24 hours</small>
                                <div style="font-weight: bold; background-color: #DDDDDD; text-align:center; padding: 4px">
                                    <p style="text-align: center">Regards</p>
                                    <p style="text-align: center">Recway AB</p>
                                </div>
                              </div>
                            </div>';
                if ($userTable === 'admin') {
                    sendMail($body, $email, $userTable == "admin" ? "Admin" : $user->name, "Code Verification");
                } elseif ($userTable === 'customers') {
                    sendMail($body, $email, $userTable == "customer" ? "Customer" : $user->name, "Code Verification");
                } elseif ($userTable === 'staff') {
                    sendMail($body, $email, $userTable == "staff" ? "Staff" : $user->name, "Code Verification");
                }
                redirect('verify.php');
            } else {
                $message = '<p class="text-danger">Email or password is wrong!</p>';
            }
        } else {
            $message = '<p class="text-danger">Email or password is wrong!</p>';
        }
        return $message;
    }
}
function userLogin($userTable)
{
    global $conn;
    global $message;
    if (isset($_POST['login'])) {
        function getUserIP()
        {
            if (! empty($_SERVER['HTTP_CLIENT_IP'])) {
                return $_SERVER['HTTP_CLIENT_IP'];
            } elseif (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                return $_SERVER['REMOTE_ADDR'];
            }
        }
        $log_data = [
            'ip_address' => getUserIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'request_uri' => $_SERVER['REQUEST_URI'],
            'http_referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
            'request_time' => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
        ];
        $sql = "INSERT INTO request_logs (ip_address, user_agent, request_method, request_uri, http_referer, request_time) VALUES (:ip_address, :user_agent, :request_method, :request_uri, :http_referer, :request_time)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':ip_address', $log_data['ip_address']);
        $stmt->bindParam(':user_agent', $log_data['user_agent']);
        $stmt->bindParam(':request_method', $log_data['request_method']);
        $stmt->bindParam(':request_uri', $log_data['request_uri']);
        $stmt->bindParam(':http_referer', $log_data['http_referer']);
        $stmt->bindParam(':request_time', $log_data['request_time']);
        $stmt->execute();
        $email = $_POST['email'];
        $password = $_POST['password'];
        $query = 'SELECT * FROM ' . $userTable . ' WHERE email = ? LIMIT 1';
        $stmt = $conn->prepare($query);
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        //Check if username is correct
        if ($user) {
            // Check if password is correct
            if (password_verify($password, $user->password)) {
                $_SESSION['email'] = $email;
                $otpDB = findByQuery("SELECT * FROM otp_verification WHERE email = '{$_SESSION['email']}' AND date_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
                if (! empty($otpDB)) {
                    $otp = $otpDB->otp;
                } else {
                    $otp = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
                    insert("otp_verification", ['email' => $email, 'otp' => $otp]);
                }
                $body = '<div>
                              <div style="background-color: #AC0206; color: #ffffff; text-align:center; padding: 4px">
                                <h2>Your verification code</h2>
                              </div>
                              <div>
                                <p>Use the following verification code to login</p>
                                <h1 style="background-color: #dddddd; padding: 5px 10px; border-radius: 10px; width: fit-content">' . $otp . '</h1>
                                <small>This code is valid for 24 hours</small>
                                <div style="font-weight: bold; background-color: #DDDDDD; text-align:center; padding: 4px">
                                    <p style="text-align: center">Regards</p>
                                    <p style="text-align: center">Recway AB</p>
                                </div>
                              </div>
                            </div>';
                sendMail($body, $email, $userTable == "reviewers" ? "Reviewer" : $user->name, "Code Verification");
                redirect('verify.php');
            } else {
                $message = '<p class="text-danger">Email or password is wrong!</p>';
            }
        } else {
            $message = '<p class="text-danger">Email or password is wrong!</p>';
        }
        return $message;
    }
}
function departmentuserLogin($userTable)
{
    global $conn;
    global $message;
    if (isset($_POST['login'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        $query = 'SELECT * FROM ' . $userTable . ' WHERE dep_user_email = ? LIMIT 1';
        $stmt = $conn->prepare($query);
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        //Check if username is correct
        if ($user) {
            // Check if password is correct
            if (password_verify($password, $user->dep_user_password)) {
                $_SESSION['email'] = $email;
                $otpDB = findByQuery("SELECT * FROM otp_verification WHERE email = '{$_SESSION['email']}' AND date_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
                if (! empty($otpDB)) {
                    $otp = $otpDB->otp;
                } else {
                    $otp = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
                    insert("otp_verification", ['email' => $email, 'otp' => $otp]);
                }
                $body = '<div>
                              <div style="background-color: #AC0206; color: #ffffff; text-align:center; padding: 4px">
                                <h2>Your verification code</h2>
                              </div>
                              <div>
                                <p>Use the following verification code to login</p>
                                <h1 style="background-color: #dddddd; padding: 5px 10px; border-radius: 10px; width: fit-content">' . $otp . '</h1>
                                <small>This code is valid for 24 hours</small>
                                <div style="font-weight: bold; background-color: #DDDDDD; text-align:center; padding: 4px">
                                    <p style="text-align: center">Regards</p>
                                    <p style="text-align: center">Recway AB</p>
                                </div>
                              </div>
                            </div>';
                sendMail($body, $email, $userTable == "department_users" ? "Dept User" : $user->name, "Code Verification");
                redirect('verify.php');
            } else {
                $message = '<p class="text-danger">Email or password is wrong!</p>';
            }
        } else {
            $message = '<p class="text-danger">Email or password is wrong!</p>';
        }
        return $message;
    }
}
function signup()
{
    global $conn;
    global $message;
    if (isset($_POST['signup'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $password = $_POST['password'];
        // PASSWORD TO HASHED PASSWORD
        $password = password_hash($password, PASSWORD_BCRYPT);
        $query = "SELECT * FROM customers WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$email]);
        $customer = $stmt->fetch();
        if (! empty($customer)) {
            $message = '<p class="text-danger">Email already exists!</p>';
        } else {
            // SAVE DATA INTO DATABASE
            $query = 'INSERT INTO customers(name, email, phone, password) VALUES (?,?,?,?)';
            $stmt = $conn->prepare($query);
            $res = $stmt->execute([$name, $email, $phone, $password]);
            if ($res) {
                $message = '<p class="text-success">Signed up successfully!</p>';
            } else {
                $message = "Sorry! Registration not successful.";
            }
        }
    }
}
// Database
function findById($table, $id)
{
    global $conn;
    $query = "SELECT * FROM {$table} WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute([$id]);
    return $stmt->fetch();
}
function findByQuery($query)
{
    global $conn;
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetch();
}
function findAllByQuery($query)
{
    global $conn;
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll();
}
function findAll($table)
{
    global $conn;
    $query = "SELECT * FROM {$table}";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll();
}
function valueParams($length)
{
    $params = "";
    for ($i = 0; $i < $length; $i++) {
        $params .= "?";
        $params .= $i !== $length - 1 ? "," : "";
    }
    return $params;
}
function insert($table, $data)
{
    global $conn;
    $query = "INSERT INTO {$table} (" . implode(',', array_keys($data)) . ") VALUES (" . valueParams(count($data)) . ")";
    $stmt = $conn->prepare($query);
    $stmt->execute(array_values($data));
    return $conn->lastInsertId();
}
function update($table, $data, $idCol, $idColVal)
{
    global $conn;
    $properties_pairs = [];
    $properties_values = [];
    foreach ($data as $key => $value) {
        $properties_pairs[] = "{$key}=?";
        $properties_values[] = $value;
    }
    $sql = "UPDATE  " . $table . "  SET ";
    $sql .= implode(", ", $properties_pairs);
    $sql .= " WHERE " . $idCol . " = '" . $idColVal . "'";
    $stmt = $conn->prepare($sql);
    if ($stmt->execute($properties_values)) {
        return true;
    } else {
        return false;
    }
}
function delete($table, $idCol, $idVal)
{
    global $conn;
    $query = "DELETE FROM {$table} WHERE {$idCol} = '{$idVal}'";
    $stmt = $conn->prepare($query);
    if ($stmt->execute()) {
        return true;
    } else {
        return false;
    }
}
function findUserByEmail($email)
{
    return findByQuery("SELECT * FROM users WHERE email = '{$email}' LIMIT 1");
}
function findAllUsers()
{
    return findAllByQuery("SELECT * FROM users");
}
function getMessages($cus_id = 0, $service_id = 0)
{
    global $conn;
    $query = 'SELECT * FROM messages WHERE cus_id = ? AND interview_id = ? LIMIT 1';
    $stmt = $conn->prepare($query);
    $stmt->execute([$cus_id, $service_id]);
    $messages = $stmt->fetch();
    return $messages;
}
function getDefaultMessages()
{
    global $conn;
    $query = 'SELECT * FROM messages LIMIT 1';
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $messages = $stmt->fetch();
}
function insertMessages($cus_id, $services2 = null)
{
    global $conn;
    $messages = (array) getDefaultMessages();
    unset($messages['id']);
    unset($messages['cus_id']);
    unset($messages['interview_id']);
    $num_messages = count($messages);
    $placeholders = implode(',', array_fill(0, $num_messages + 2, '?'));
    foreach ($services2 as $service) {
        $query = "INSERT INTO messages (cus_id,interview_id," . implode(",", array_keys($messages)) . ") VALUES ({$placeholders})";
        $stmt = $conn->prepare($query);
        $a = explode("***", implode("***", array_values($messages)));
        $stmt->execute(array_merge([$cus_id, $service], $a));
    }
}
function sendMail($body, $to, $name, $subject, $attachment = null, $delay = false, $cc = null)
{
    global $conn;
    global $email_from;
    //Create an instance; passing `true` enables exceptions
    $mail = new PHPMailer(true);
    $status = null;
    $errorMessage = null;
    try {
        $mail->CharSet = "UTF-8";
        $mail->Encoding = 'base64';
        //Server settings
        //         $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
        $mail->isSMTP();                                            //Send using SMTP
        $mail->Host = SMTP_HOST;                     //Set the SMTP server to send through
        $mail->SMTPAuth = true;                                   //Enable SMTP authentication
        $mail->Username = USERNAME;                     //SMTP username
        $mail->Password = PASSWORD;                               //SMTP password
        //        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
        //Recipients
        $mail->setFrom(USERNAME, $email_from);
        $mail->addAddress($to, $name);     //Add a recipient
        $mail->addReplyTo(USERNAME, $email_from);
        $mail->addBCC(USERNAME, $email_from);
        if (! empty($cc)) {
            $mail->addCC($cc);
        }
        // $mail->addCC(USERNAME, $email_from);
        //        $mail->addCustomHeader("BCC: ".USERNAME);
        //Content
        $mail->isHTML(true);                                  //Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body = $body;
        //    $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
        if (! empty($attachment)) {
            $mail->addAttachment($attachment);
        }
        $mail->send();
        $status = "success";
        $errorMessage = null;
        $mailMsg = 'Email sent successfully!';
    } catch (Exception $e) {
        $status = "failed";
        $errorMessage = $mail->ErrorInfo;
        $mailMsg = "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
    $meta = json_encode([
        "recipient_email" => $to,
        "recipient_name" => $name,
        "subject" => $subject,
    ]);
    $query = "INSERT INTO email_logs (meta, status, error_message) VALUES (:meta, :status, :errorMessage)";
    $stmt = $conn->prepare($query);
    // Bind parameters
    $stmt->bindParam(':meta', $meta);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':errorMessage', $errorMessage);
    // Execute the query
    $stmt->execute();
    if (isset($mailMsg)) {
        return $mailMsg;
    }
    return '';
}
function replace($text, $customer, $candidate, $company, $interview, $staff, $email, $password, $status, $date, $orderID = null, $interviewDate = null, $staff_email = null, $comment = null, $vascId = null, $service = null, $place = null)
{
    if (! empty($customer)) {
        $text = str_replace('{customer}', $customer, $text);
    }
    if (! empty($candidate)) {
        $text = str_replace('{candidate}', $candidate, $text);
    }
    if (! empty($company)) {
        $text = str_replace('{company}', $company, $text);
    }
    if (! empty($interview)) {
        $text = str_replace('{interview}', $interview, $text);
    }
    if (! empty($staff)) {
        $text = str_replace('{staff}', $staff, $text);
    }
    if (! empty($email)) {
        $text = str_replace('{email}', $email, $text);
    }
    if (! empty($password)) {
        $text = str_replace('{password}', $password, $text);
    }
    if (! empty($status)) {
        $text = str_replace('{status}', $status, $text);
    }
    if (! empty($date)) {
        $text = str_replace('{date}', $date, $text);
    }
    if (! empty($orderID)) {
        $text = str_replace('{orderid}', $orderID, $text);
    }
    if (! empty($interviewDate)) {
        $text = str_replace('{interview_date}', $interviewDate, $text);
    }
    if (! empty($staff_email)) {
        $text = str_replace('{staff_email}', $staff_email, $text);
    }
    if (! empty($comment)) {
        $text = str_replace('{comment}', $comment, $text);
    }
    if (! empty($vascId)) {
        $text = str_replace('{vasc_id}', $vascId, $text);
    }
    if (! empty($service)) {
        $text = str_replace('{service}', $service, $text);
    }
    if (! empty($place)) {
        $text = str_replace('{place}', $place, $text);
    }
    return $text;
}
function statusColors()
{
    return ['#ffa200', '#008B8B', '#00FF00', '#964B00', '#FFFF00', '#FF0000', '#FF00FF', '#7B94A8', '#416c96'];
}
function saveEmail($userType, $userName, $orderID, $msgType, $text, $email, $subject, $email_delay = null)
{
    global $conn;
    $query = 'INSERT INTO emails(user_type, user_name, order_id, msg_type, text, email, subject,email_delay) VALUES (?,?,?,?,?,?,?,?)';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$userType, $userName, $orderID, $msgType, $text, $email, $subject, $email_delay]);
}
function date_range($first, $last, $step = '+1 day', $output_format = 'd/m/Y')
{
    $dates = [];
    $current = strtotime($first);
    $last = strtotime($last);
    while ($current <= $last) {
        $dates[] = date($output_format, $current);
        $current = strtotime($step, $current);
    }
    return $dates;
}
function cssVars()
{
    global $conn;
    $query = "SELECT * FROM settings";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $settings = $stmt->fetchAll();
    foreach ($settings as $setting) {
        $var = $setting->name;
        $$var = $setting->value;
    }
    return ["primaryColor" => $primaryColor, "secondaryColor" => $secondaryColor];
}
function setCurrentURL()
{
    // Store the current URL in a session variable
    $domain = $_SERVER['HTTP_HOST'];
    $request_uri = $_SERVER['REQUEST_URI'];
    $redirect_url = "https://" . $domain . $request_uri;
    if (! strpos($redirect_url, "logout")) {
        $_SESSION['previous_page'] = $redirect_url;
    }
}
function getStatuses()
{
    global $conn;
    $query = "SELECT * FROM statuses";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $statuses = $stmt->fetchAll();
    return $statuses;
}
function getStatusesByService($service_id = null, $catIds = null)
{
    global $conn;
    $query = "SELECT *, statuses.id AS sID FROM statuses 
                  INNER JOIN status_services ss ON statuses.id = ss.status_id 
                  INNER JOIN interviews i ON ss.service_id = i.id 
                  INNER JOIN service_categories sc ON i.service_cat_id = sc.id";
    if (! empty($service_id)) {
        $query .= " WHERE i.service_cat_id = ? GROUP BY ss.status_id ORDER BY i.service_cat_id";
        $stmt = $conn->prepare($query);
        $stmt->execute([$service_id]);
    } else {
        if (! empty($catIds)) {
            $placeholders = implode(',', array_fill(0, count($catIds), '?'));
            $query .= " WHERE i.service_cat_id IN ($placeholders) GROUP BY ss.status_id ORDER BY i.service_cat_id";
            $stmt = $conn->prepare($query);
            $stmt->execute($catIds);
        } else {
            $query .= " GROUP BY ss.status_id ORDER BY i.service_cat_id";
            $stmt = $conn->prepare($query);
            $stmt->execute();
        }
    }
    return $stmt->fetchAll();
}
// Also present in ajax.php
function getStatusById($id)
{
    global $conn;
    $query = "SELECT * FROM statuses WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute([$id]);
    $status = $stmt->fetch();
    return $status;
}
function getStatusByDesc($desc)
{
    global $conn;
    $query = "SELECT * FROM statuses WHERE status_detail = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute([$desc]);
    $status = $stmt->fetch();
    return $status;
}
function getStatusMessage($status_id, $service_id, $customer_id)
{
    global $conn;
    $query = "SELECT * FROM status_services WHERE status_id = ? AND service_id = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute([$status_id, $service_id]);
    $status_service = $stmt->fetch();
    if (! empty($status_service)) {
        $query = 'SELECT * FROM interviews WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$service_id]);
        $interview = $stmt->fetch();
        $query = "SELECT {$status_service->msg_col} as col FROM messages WHERE cus_id = ? AND interview_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$customer_id, $interview->id]);
        $message = $stmt->fetch();
    }
    //    var_dump($message);
    return ! empty($message) ? $message : false;
}
function getStatusServiceCat($status_id)
{
    global $conn;
    $query = "SELECT * FROM statuses s INNER JOIN status_services ss on s.id = ss.status_id INNER JOIN interviews i ON i.id = ss.service_id WHERE s.id = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute([$status_id]);
    $service = $stmt->fetch();
    return ! empty($service) ? $service : false;
}
function getCustomerServiceCat($customerID)
{
    global $conn;
    $query = "SELECT * FROM customer_services WHERE cus_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$customerID]);
    $services = $stmt->fetchAll();
    $serviceCats = [];
    if (! empty($services)) {
        foreach ($services as $service) {
            $service = findById("interviews", $service->service_id);
            if (! in_array($service->service_cat_id, $serviceCats)) {
                array_push($serviceCats, $service->service_cat_id);
            }
        }
    }
    return $serviceCats;
}
function getMsgColsByService($service_id)
{
    if (empty($service_id) || ! is_numeric($service_id)) {
        return false; // no service id, return false
    }
    global $conn;
    $query = "SELECT * FROM `status_services` ss INNER JOIN interviews i ON i.id = ss.service_id WHERE i.id = {$service_id} GROUP BY msg_col ORDER BY status_id ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $msgCols = $stmt->fetchAll();
    return ! empty($msgCols) ? $msgCols : false;
}
//Cron Job Functions
function expiredOTP()
{
    global $conn;
    $query = "DELETE FROM otp_verification WHERE date_time < DATE_SUB(NOW(), INTERVAL 24 HOUR);";
    $stmt = $conn->prepare($query);
    $stmt->execute();
}
function sendInvestigationReminderEmails()
{
    global $conn;
    try {
        // Find status id for "Under investigation" (fallback to 6 if not found)
        $statusRow = findByQuery("SELECT id FROM statuses WHERE status = 'Under investigation' LIMIT 1");
        $statusId = ! empty($statusRow) && isset($statusRow->id) ? (int)$statusRow->id : 6;
        // Get all candidates (orders) that are under investigation and have an interview report uploaded
        $query = "SELECT c.*, cu.company AS customer_company, cu.name AS customer_name
                  FROM candidates c
                  INNER JOIN customers cu ON cu.id = c.cus_id
                  WHERE c.status = :status_id
                    AND c.interview_report IS NOT NULL
                    AND c.interview_report <> ''
										AND c.expired = 0";
        $stmt = $conn->prepare($query);
        $stmt->execute([':status_id' => $statusId]);
        $candidates = $stmt->fetchAll(PDO::FETCH_OBJ);
        if (empty($candidates)) {
            return;
        }
        // Current Sweden time (used for calculations)
        $swedenTimezone = new DateTimeZone('Europe/Stockholm');
        $nowSweden = new DateTime('now', $swedenTimezone);
        foreach ($candidates as $candidate) {
            $orderId = $candidate->id;
            $company = trim($candidate->customer_company);
            if (empty($company)) {
                continue;
            }
            // Find the first time an interview report was uploaded for this order
            $stmtReport = $conn->prepare("SELECT date_time FROM history WHERE order_id = ? AND `desc` LIKE 'Interview%Report Uploaded%' ORDER BY date_time ASC LIMIT 1");
            $stmtReport->execute([$orderId]);
            $firstReport = $stmtReport->fetch(PDO::FETCH_OBJ);
            if (empty($firstReport) || empty($firstReport->date_time)) {
                continue; // No upload history -> skip
            }
            // Find the last reminder date for this order
            $stmtReminder = $conn->prepare("SELECT date_time FROM history WHERE order_id = ? AND `desc` LIKE 'Reminder email send to Active status manager%' ORDER BY date_time DESC LIMIT 1");
            $stmtReminder->execute([$orderId]);
            $lastReminder = $stmtReminder->fetch(PDO::FETCH_OBJ);
            $fromDate = $lastReminder && ! empty($lastReminder->date_time) ? $lastReminder->date_time : $firstReport->date_time;
            $workingDaysPassed = getWorkingDaysBetween($fromDate, $nowSweden->format('Y-m-d'));
            if ($workingDaysPassed < 5) {
                continue; // Not yet 5 working days
            }
            // Fetch all active managers (can_view_report = 1) for this company
            $stmtManagers = $conn->prepare("SELECT cm.*, cu.email, cu.name FROM company_manager cm INNER JOIN customers cu ON cm.cus_id = cu.id WHERE cm.company = :company AND cm.can_view_report = 1");
            $stmtManagers->execute([':company' => $company]);
            $managers = $stmtManagers->fetchAll(PDO::FETCH_ASSOC);
            if (empty($managers)) {
                continue;
            }
            // Load related data for email templates
            $statusStmt = $conn->prepare("SELECT * FROM statuses WHERE id = :status_id");
            $statusStmt->execute([':status_id' => $candidate->status]);
            $status = $statusStmt->fetch(PDO::FETCH_OBJ);
            $place = null;
            if (! empty($candidate->place)) {
                $placeStmt = $conn->prepare("SELECT * FROM places WHERE id = :place_id");
                $placeStmt->execute([':place_id' => $candidate->place]);
                $place = $placeStmt->fetch(PDO::FETCH_OBJ);
            }
            $staff = null;
            if (! empty($candidate->staff_id)) {
                $staffStmt = $conn->prepare("SELECT * FROM staff WHERE id = :staff_id");
                $staffStmt->execute([':staff_id' => $candidate->staff_id]);
                $staff = $staffStmt->fetch(PDO::FETCH_OBJ);
            }
            $service = null;
            if (! empty($candidate->interview_id)) {
                $serviceStmt = $conn->prepare("SELECT * FROM interviews WHERE id = :interview");
                $serviceStmt->execute([':interview' => $candidate->interview_id]);
                $service = $serviceStmt->fetch(PDO::FETCH_OBJ);
            }
            $statusName = ! empty($status) && isset($status->status) ? $status->status : 'Under investigation with SPO';
            $serviceTitle = ! empty($service) && isset($service->title) ? $service->title : '';
            $placeName = ! empty($place) && isset($place->name) ? $place->name : '';
            $staffName = ! empty($staff) && isset($staff->name) ? $staff->name : '';
            $currentDateTime = $nowSweden->format('Y-m-d H:i:s');
            $isWorkingHours = isSwedenWorkingHours();
            // Fetch email template from custom_email_template table
            // Template name: "Active manager reminder order still under investigation" with id 3
            $templateStmt = $conn->prepare("SELECT message FROM custom_email_template WHERE id = 3 OR name = 'Active manager reminder order still under investigation' LIMIT 1");
            $templateStmt->execute();
            $templateRow = $templateStmt->fetch(PDO::FETCH_OBJ);
            $emailTemplate = ! empty($templateRow) && isset($templateRow->message) ? trim($templateRow->message) : '';

            // Fallback to default template if custom template not found
            if (empty($emailTemplate)) {
                $emailTemplate = "Dear {customer},<br><br>This is a reminder that order {orderid} for candidate {candidate} is still in status \"{status}\" and the interview report is available.<br><br>Best regards,<br>Recway AB";
            }

            // Send reminder to each active manager
            foreach ($managers as $manager) {
                if (empty($manager['email'])) {
                    continue;
                }
                $body = replace(
                    $emailTemplate,
                    $manager['name'],
                    $candidate->name . " " . $candidate->surname,
                    $manager['company'],
                    $serviceTitle,
                    $staffName,
                    '',
                    '',
                    $statusName,
                    $currentDateTime,
                    $candidate->order_id,
                    '',
                    '',
                    '',
                    $candidate->vasc_id,
                    $serviceTitle,
                    $placeName
                );
                $subject = "Reminder: Order {$candidate->order_id} under investigation";
                if ($isWorkingHours) {
                    saveEmail("Customer", $manager['name'], $candidate->order_id, 'Investigation Reminder', $body, $manager['email'], $subject);
                    sendMail($body, $manager['email'], $manager['name'], $subject, null, false, 'info@recway.se');
                } else {
                    saveEmail("Customer", $manager['name'], $candidate->order_id, 'Investigation Reminder', $body, $manager['email'], $subject, "1");
                }
            }
            // Add history entry for this reminder
            $historyDate = $isWorkingHours ? $nowSweden->format('Y-m-d H:i:s') : getNextWorkingHour()->format('Y-m-d H:i:s');
            $historyComment = 'By System (Investigation Reminder)';
            $historyStmt = $conn->prepare("INSERT INTO history (order_id, `desc`, date_time, comment) VALUES (?,?,?,?)");
            $historyStmt->execute([$orderId, 'Reminder email send to Active status manager', $historyDate, $historyComment]);
        }
    } catch (Exception $e) {
        // Log any unexpected error to file
        logMessage('Error in sendInvestigationReminderEmails: ' . $e->getMessage(), 'ERROR');
    }
}
function sendStaffReminderEmails()
{
    global $conn;
    try {
        // Status IDs that require staff reminders
        $statusIds = [2, 14, 34, 5, 41, 11, 83, 10, 65, 23, 43, 82, 24, 81];
        $statusIdsStr = implode(',', array_map('intval', $statusIds));

        // Get all candidates (orders) that are in the specified statuses
        $query = "SELECT c.*, cu.company AS customer_company, cu.name AS customer_name
                  FROM candidates c
                  INNER JOIN customers cu ON cu.id = c.cus_id
                  WHERE c.status IN ({$statusIdsStr})
                    AND c.staff_id IS NOT NULL
                    AND c.staff_id <> 0
                    AND c.expired = 0";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $candidates = $stmt->fetchAll(PDO::FETCH_OBJ);

        if (empty($candidates)) {
            return;
        }

        // Get status details for matching with history
        $statusQuery = "SELECT id, status_detail FROM statuses WHERE id IN ({$statusIdsStr})";
        $statusStmt = $conn->prepare($statusQuery);
        $statusStmt->execute();
        $statusDetails = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
        $statusDetailMap = [];
        foreach ($statusDetails as $statusDetail) {
            $statusDetailMap[$statusDetail['id']] = $statusDetail['status_detail'];
        }

        // Current Sweden time (used for calculations)
        $swedenTimezone = new DateTimeZone('Europe/Stockholm');
        $nowSweden = new DateTime('now', $swedenTimezone);

        foreach ($candidates as $candidate) {
            $orderId = $candidate->id;
            $currentStatusId = (int)$candidate->status;

            // Get the status detail for this status
            $statusDetail = isset($statusDetailMap[$currentStatusId]) ? $statusDetailMap[$currentStatusId] : null;
            if (empty($statusDetail)) {
                continue; // Status detail not found, skip
            }

            // Find the first time this order entered this status
            $stmtStatusEntry = $conn->prepare("SELECT date_time FROM history WHERE order_id = ? AND `desc` = ? ORDER BY date_time ASC LIMIT 1");
            $stmtStatusEntry->execute([$orderId, $statusDetail]);
            $firstStatusEntry = $stmtStatusEntry->fetch(PDO::FETCH_OBJ);

            if (empty($firstStatusEntry) || empty($firstStatusEntry->date_time)) {
                continue; // No status entry history found, skip
            }

            try {
                $stmtReminder = $conn->prepare("SELECT created FROM comments WHERE order_id = ? AND author_id = 13 AND author_type = 'admin' AND comment LIKE 'Reminder email send to assigned staff%' ORDER BY id DESC LIMIT 1");
                $stmtReminder->execute([$orderId]);
                $lastReminder = $stmtReminder->fetch(PDO::FETCH_OBJ);

                // Get the date from the comment (try different field names)
                $reminderDate = null;
                if ($lastReminder) {
                    if (! empty($lastReminder->created)) {
                        $reminderDate = $lastReminder->created;
                    }
                }
            } catch (Exception $e) {
                // If query fails due to missing fields, try simpler query
                $stmtReminder = $conn->prepare("SELECT * FROM comments WHERE order_id = ? AND author_id = 13 AND author_type = 'admin' AND comment LIKE 'Reminder email send to assigned staff%' ORDER BY id DESC LIMIT 1");
                $stmtReminder->execute([$orderId]);
                $lastReminder = $stmtReminder->fetch(PDO::FETCH_OBJ);
                $reminderDate = null;
                if ($lastReminder) {
                    $reminderDate = isset($lastReminder->created) ? $lastReminder->created : null;
                }
            }

            // Use reminder date if available, otherwise use first status entry date
            $fromDate = $reminderDate ? $reminderDate : $firstStatusEntry->date_time;
            $workingDaysPassed = getWorkingDaysBetween($fromDate, $nowSweden->format('Y-m-d'));

            if ($workingDaysPassed < 5) {
                continue; // Not yet 5 working days
            }

            // Get the assigned staff member
            $staffStmt = $conn->prepare("SELECT * FROM staff WHERE id = :staff_id");
            $staffStmt->execute([':staff_id' => $candidate->staff_id]);
            $staff = $staffStmt->fetch(PDO::FETCH_OBJ);

            if (empty($staff) || empty($staff->email)) {
                continue; // No staff assigned or no email
            }

            // Load related data for email templates
            $statusStmt = $conn->prepare("SELECT * FROM statuses WHERE id = :status_id");
            $statusStmt->execute([':status_id' => $currentStatusId]);
            $status = $statusStmt->fetch(PDO::FETCH_OBJ);

            $place = null;
            if (! empty($candidate->place)) {
                $placeStmt = $conn->prepare("SELECT * FROM places WHERE id = :place_id");
                $placeStmt->execute([':place_id' => $candidate->place]);
                $place = $placeStmt->fetch(PDO::FETCH_OBJ);
            }

            $service = null;
            if (! empty($candidate->interview_id)) {
                $serviceStmt = $conn->prepare("SELECT * FROM interviews WHERE id = :interview");
                $serviceStmt->execute([':interview' => $candidate->interview_id]);
                $service = $serviceStmt->fetch(PDO::FETCH_OBJ);
            }

            $statusName = ! empty($status) && isset($status->status) ? $status->status : '';
            $serviceTitle = ! empty($service) && isset($service->title) ? $service->title : '';
            $placeName = ! empty($place) && isset($place->name) ? $place->name : '';
            $staffName = ! empty($staff) && isset($staff->name) ? $staff->name : '';
            $currentDateTime = $nowSweden->format('Y-m-d H:i:s');
            $isWorkingHours = isSwedenWorkingHours();

            // Get email template from custom_email_template table (id = 4 and name = 'Staff Reminder Email for Status')
            $templateStmt = $conn->prepare("SELECT message FROM custom_email_template WHERE id = 4 AND name = 'Staff Reminder Email for Status' LIMIT 1");
            $templateStmt->execute();
            $templateRow = $templateStmt->fetch(PDO::FETCH_OBJ);
            $emailTemplate = ! empty($templateRow) && isset($templateRow->message) ? trim($templateRow->message) : '';

            if (empty($emailTemplate)) {
                // Fallback to default template
                $body = "Dear {$staffName},<br><br>This is a reminder that order {$candidate->order_id} for candidate {$candidate->name} {$candidate->surname} is still in status \"{$statusName}\".<br><br>Best regards,<br>Recway AB";
            } else {
                $body = replace(
                    $emailTemplate,
                    $candidate->customer_name,
                    $candidate->name . " " . $candidate->surname,
                    $candidate->customer_company,
                    $serviceTitle,
                    $staffName,
                    '',
                    '',
                    '',
                    '',
                    $candidate->order_id,
                    '',
                    '',
                    '',
                    $candidate->vasc_id,
                    $serviceTitle,
                    $placeName
                );
            }

            $subject = "Reminder: Order {$candidate->order_id} in status \"{$statusName}\"";

            if ($isWorkingHours) {
                saveEmail("Staff", $staffName, $candidate->order_id, 'Staff Reminder', $body, $staff->email, $subject);
                sendMail($body, $staff->email, $staffName, $subject);
            } else {
                saveEmail("Staff", $staffName, $candidate->order_id, 'Staff Reminder', $body, $staff->email, $subject, "1");
            }

            // Add comment entry for this reminder
            $commentDate = $isWorkingHours ? $nowSweden->format('Y-m-d H:i:s') : getNextWorkingHour()->format('Y-m-d H:i:s');
            $reminderComment = 'Reminder email send to assigned staff ' . $staffName;

            // Insert into comments table with author_id=13, author_type='admin'
            // Try to insert with created if the field exists, otherwise without it
            $commentStmt = $conn->prepare("INSERT INTO comments (order_id, author_id, author_type, comment, created) VALUES (?, 13, 'admin', ?, ?)");
            try {
                $commentStmt->execute([$orderId, $reminderComment, $commentDate]);
            } catch (Exception $e) {
                // If created doesn't exist, try without it
                $commentStmt = $conn->prepare("INSERT INTO comments (order_id, author_id, author_type, comment) VALUES (?, 13, 'admin', ?)");
                $commentStmt->execute([$orderId, $reminderComment]);
            }
        }
    } catch (Exception $e) {
        // Log any unexpected error to file
        logMessage('Error in sendStaffReminderEmails: ' . $e->getMessage(), 'ERROR');
    }
}
function sendTaskInvoiceEmails()
{
    global $conn;
    try {
        // Get status details for Booked, Approved, Under Investigation, Interview Interrupted, and Did not show up
        $bookedStatus = findByQuery("SELECT id, status_detail FROM statuses WHERE status_detail LIKE '%Booked%' OR variable = 'booked' LIMIT 1");
        $approvedStatus = findByQuery("SELECT id, status_detail FROM statuses WHERE status = 'Approved' OR status_detail LIKE '%Approved%' LIMIT 1");
        $underInvestigationStatus = findByQuery("SELECT id, status_detail FROM statuses WHERE status = 'Under investigation' OR status_detail LIKE '%Under investigation%' LIMIT 1");
        $interviewInterruptedStatus = findByQuery("SELECT id, status_detail FROM statuses WHERE status_detail LIKE '%Interview Interrupted%' OR status_detail LIKE '%Interview%Interrupted%' LIMIT 1");
        $didNotShowUpStatus = findByQuery("SELECT id, status_detail FROM statuses WHERE status_detail LIKE '%Did not show up%' OR status_detail LIKE '%did not show up%' OR status_detail LIKE '%not show up%' LIMIT 1");

        if (empty($bookedStatus)) {
            logMessage('Booked status not found for Task Invoice Emails', 'ERROR');
            return;
        }

        // Check if at least one target status exists
        $hasTargetStatus = ! empty($approvedStatus) || ! empty($underInvestigationStatus) ||
                          ! empty($interviewInterruptedStatus) || ! empty($didNotShowUpStatus);

        if (! $hasTargetStatus) {
            logMessage('No target statuses found for Task Invoice Emails (Approved, Under Investigation, Interview Interrupted, or Did not show up)', 'ERROR');
            return;
        }

        $bookedStatusDetail = $bookedStatus->status_detail;
        $approvedStatusDetail = ! empty($approvedStatus) ? $approvedStatus->status_detail : null;
        $underInvestigationStatusDetail = ! empty($underInvestigationStatus) ? $underInvestigationStatus->status_detail : null;
        $interviewInterruptedStatusDetail = ! empty($interviewInterruptedStatus) ? $interviewInterruptedStatus->status_detail : null;
        $didNotShowUpStatusDetail = ! empty($didNotShowUpStatus) ? $didNotShowUpStatus->status_detail : null;

        // Get all managers with statistic role (staff category 4)
        $managersQuery = "SELECT * FROM staff WHERE category = 4 AND email IS NOT NULL AND email <> ''";
        $managersStmt = $conn->prepare($managersQuery);
        $managersStmt->execute();
        $managers = $managersStmt->fetchAll(PDO::FETCH_OBJ);

        if (empty($managers)) {
            logMessage('No managers with statistic role (category 4) found', 'INFO');
            return;
        }

        // Find orders that transitioned from Booked to Approved, Under Investigation, Interview Interrupted, or Did not show up
        // Check for consecutive history entries where previous is Booked and next is one of the target statuses
        $statusConditions = [];
        $params = [':booked_status' => $bookedStatusDetail];

        if (! empty($approvedStatusDetail)) {
            $statusConditions[] = "h2.desc = :approved_status";
            $params[':approved_status'] = $approvedStatusDetail;
        }
        if (! empty($underInvestigationStatusDetail)) {
            $statusConditions[] = "h2.desc = :under_investigation_status";
            $params[':under_investigation_status'] = $underInvestigationStatusDetail;
        }
        if (! empty($interviewInterruptedStatusDetail)) {
            $statusConditions[] = "h2.desc = :interview_interrupted_status";
            $params[':interview_interrupted_status'] = $interviewInterruptedStatusDetail;
        }
        if (! empty($didNotShowUpStatusDetail)) {
            $statusConditions[] = "h2.desc = :did_not_show_up_status";
            $params[':did_not_show_up_status'] = $didNotShowUpStatusDetail;
        }

        if (empty($statusConditions)) {
            return;
        }

        $statusCondition = implode(' OR ', $statusConditions);

        $ordersQuery = "SELECT DISTINCT c.id, c.order_id, c.cus_id, c.name, c.surname, c.status,
                               cu.name AS customer_name, cu.company AS customer_company, cu.invoice_period
                        FROM candidates c
                        INNER JOIN customers cu ON cu.id = c.cus_id
                        INNER JOIN history h1 ON h1.order_id = c.id AND h1.desc = :booked_status
                        INNER JOIN history h2 ON h2.order_id = c.id 
                            AND h2.id = (
                                SELECT MIN(h3.id) 
                                FROM history h3 
                                WHERE h3.order_id = c.id 
                                AND h3.id > h1.id
                            )
                            AND ({$statusCondition})
                        WHERE c.expired = 0
                          AND NOT EXISTS (
                              SELECT 1 FROM invoice_staff_email 
                              WHERE order_id = c.id 
                              AND invoice_email = 1
                          )";

        $ordersStmt = $conn->prepare($ordersQuery);
        $ordersStmt->execute($params);
        $orders = $ordersStmt->fetchAll(PDO::FETCH_OBJ);

        if (empty($orders)) {
            return;
        }

        // Current Sweden time
        $swedenTimezone = new DateTimeZone('Europe/Stockholm');
        $nowSweden = new DateTime('now', $swedenTimezone);
        $currentDateTime = $nowSweden->format('Y-m-d H:i:s');
        $isWorkingHours = isSwedenWorkingHours();

        // Get email template from custom_email_template table
        $templateStmt = $conn->prepare("SELECT message FROM custom_email_template WHERE id = 5 OR name = 'Task Invoice Email for Manager' LIMIT 1");
        $templateStmt->execute();
        $templateRow = $templateStmt->fetch(PDO::FETCH_OBJ);
        $emailTemplate = ! empty($templateRow) && isset($templateRow->message) ? trim($templateRow->message) : '';

        foreach ($orders as $order) {
            // Get order details
            $orderId = $order->id;
            $customer = null;
            if (! empty($order->cus_id)) {
                $customerStmt = $conn->prepare("SELECT * FROM customers WHERE id = :cus_id");
                $customerStmt->execute([':cus_id' => $order->cus_id]);
                $customer = $customerStmt->fetch(PDO::FETCH_OBJ);
            }

            $service = null;
            $candidateStmt = $conn->prepare("SELECT interview_id FROM candidates WHERE id = :order_id");
            $candidateStmt->execute([':order_id' => $orderId]);
            $candidate = $candidateStmt->fetch(PDO::FETCH_OBJ);
            if (! empty($candidate) && ! empty($candidate->interview_id)) {
                $serviceStmt = $conn->prepare("SELECT * FROM interviews WHERE id = :interview_id");
                $serviceStmt->execute([':interview_id' => $candidate->interview_id]);
                $service = $serviceStmt->fetch(PDO::FETCH_OBJ);
            }

            $serviceTitle = ! empty($service) && isset($service->title) ? $service->title : '';
            $customerName = ! empty($customer) && isset($customer->name) ? $customer->name : ($order->customer_name ?? '');
            $customerCompany = ! empty($customer) && isset($customer->company) ? $customer->company : ($order->customer_company ?? '');
            $invoicePeriod = ! empty($customer) && isset($customer->invoice_period) ? $customer->invoice_period : ($order->invoice_period ?? '');

            // Format invoice period text
            $invoicePeriodText = '';
            if (! empty($invoicePeriod)) {
                switch (strtolower($invoicePeriod)) {
                    case 'day':
                        $invoicePeriodText = 'daily';
                        break;
                    case 'week':
                        $invoicePeriodText = 'weekly';
                        break;
                    case 'month':
                        $invoicePeriodText = 'monthly';
                        break;
                    default:
                        $invoicePeriodText = $invoicePeriod;
                }
            }

            // Build email body
            if (empty($emailTemplate)) {
                // Fallback template
                $body = "Dear Manager,<br><br>";
                $body .= "This is a task invoice notification for order {$order->order_id} for candidate {$order->name} {$order->surname}.<br><br>";
                if (! empty($invoicePeriodText)) {
                    $body .= "This customer has {$invoicePeriodText} invoice period. Please send invoice to customer.<br><br>";
                }
                $body .= "Best regards,<br>Recway AB";
            } else {
                $body = replace(
                    $emailTemplate,
                    $customerName,
                    $order->name . " " . $order->surname,
                    $customerCompany,
                    $serviceTitle,
                    '',
                    '',
                    '',
                    '',
                    '',
                    $currentDateTime,
                    $order->order_id,
                    '',
                    '',
                    '',
                    '',
                    $serviceTitle,
                    ''
                );
                // Replace invoice_period placeholder if it exists in template
                if (! empty($invoicePeriodText)) {
                    $body = str_replace('{invoice_period}', $invoicePeriodText, $body);
                }
            }

            $subject = "Task Invoice: Order {$order->order_id} - Send Invoice to Customer";

            // Send email to each manager with statistic role
            $emailDate = $isWorkingHours ? $nowSweden->format('Y-m-d H:i:s') : getNextWorkingHour()->format('Y-m-d H:i:s');

            foreach ($managers as $manager) {
                if (empty($manager->email)) {
                    continue;
                }

                // Check if email already sent to this manager for this order
                $checkStmt = $conn->prepare("SELECT id FROM invoice_staff_email WHERE order_id = ? AND staff_id = ? AND invoice_email = 1");
                $checkStmt->execute([$orderId, $manager->id]);
                $existing = $checkStmt->fetch(PDO::FETCH_OBJ);

                if ($existing) {
                    continue; // Already sent to this manager
                }

                if ($isWorkingHours) {
                    saveEmail("Staff", $manager->name, $order->order_id, 'Task Invoice Email', $body, $manager->email, $subject);
                    sendMail($body, $manager->email, $manager->name, $subject);
                } else {
                    saveEmail("Staff", $manager->name, $order->order_id, 'Task Invoice Email', $body, $manager->email, $subject, "1");
                }

                // Insert into invoice_staff_email table to track sent email
                try {
                    $invoiceEmailStmt = $conn->prepare("INSERT INTO invoice_staff_email (order_id, staff_id, invoice_email, created_at) VALUES (?, ?, 1, ?)");
                    $invoiceEmailStmt->execute([$orderId, $manager->id, $emailDate]);
                } catch (Exception $e) {
                    // If created_at doesn't exist, try without it
                    $invoiceEmailStmt = $conn->prepare("INSERT INTO invoice_staff_email (order_id, staff_id, invoice_email) VALUES (?, ?, 1)");
                    $invoiceEmailStmt->execute([$orderId, $manager->id]);
                }
            }
        }
    } catch (Exception $e) {
        // Log any unexpected error to file
        logMessage('Error in sendTaskInvoiceEmails: ' . $e->getMessage(), 'ERROR');
    }
}
function isEmailAllowed($cus_id, $status_id)
{
    $res = findByQuery("SELECT * FROM allowed_emails WHERE cus_id = {$cus_id} AND status_id = {$status_id}");
    if ($res) {
        return ! ($res->allowed == "0");
    }
}
function rand_string($length)
{
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
    return substr(str_shuffle($chars), 0, $length);
}
function flash($name = "", $message = "", $class = "successMsg")
{
    if (! empty($name)) {
        if (! empty($message) && empty($_SESSION[$name])) {
            $_SESSION[$name] = $message;
            $_SESSION["class"] = $class;
        }
        $icons = ["successMsg" => "<i style='color: #00D26A' class='bi bi-check-circle-fill'></i>", "errorMsg" => "<i style='color: #ff4d40' class='bi bi-x-circle-fill'></i>"];
        if (empty($message) && ! empty($_SESSION[$name])) {
            $class2 = $_SESSION["class"];
            echo "<div class='$class2' id='msg-flash'>$icons[$class2] &nbsp $_SESSION[$name]</div>";
            unset($_SESSION[$name]);
            unset($_SESSION["class"]);
        }
    }
}
function getTableSettings($condition)
{
    global $conn;
    $query = "SELECT * FROM tables_settings WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$condition]);
    $table_settings = $stmt->fetch();
    return $table_settings;
}
function insertTableSettings($table, $meta_data)
{
    global $conn;
    $query = 'INSERT INTO tables_settings(name, meta_data) VALUES (?,?)';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$table, $meta_data]);
    return $res;
}
function updateTableSettings($table, $meta_data)
{
    global $conn;
    $query = "UPDATE tables_settings SET meta_data = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$meta_data, $table]);
    return $res;
}
function filter_candidate($place = null, $candidate = null, $customer = null, $order_created_from = null, $order_created_to = null, $interview_date_from = null, $interview_date_to = null, $status = null, $company = null, $extra_where_condition = null, $service_category = null, $delivery_date_from = null, $delivery_date_to = null)
{
    global $conn;
    $query = "SELECT candidates.*,statuses.status as status_name,statuses.color as status_color, staff.name as staff_name,customers.id as customer_id,customers.name as customer_name,customers.company as customer_company,places.name as place_name , interviews.title as interview_title,interviews.service_cat_id as service_category FROM candidates LEFT JOIN statuses ON candidates.status = statuses.id LEFT JOIN staff ON candidates.staff_id = staff.id LEFT JOIN customers ON candidates.cus_id = customers.id LEFT JOIN places ON candidates.place = places.id LEFT JOIN interviews ON candidates.interview_id = interviews.id WHERE candidates.expired = 0 AND candidates.invoice_sent = 0";  // Always true condition to simplify query building
    if (! empty($place)) {
        $query .= " AND candidates.place = :place";
    }
    if (! empty($candidate)) {
        $query .= " AND (candidates.name LIKE :candidate OR candidates.surname LIKE :candidate)";
    }
    if (! empty($customer)) {
        $query .= " AND candidates.cus_id = :customer";
    }
    if (! empty($order_created_from)) {
        $query .= " AND candidates.created >= :order_created_from";
    }
    if (! empty($order_created_to)) {
        $query .= " AND candidates.created <= :order_created_to";
    }
    if (! empty($interview_date_from)) {
        $query .= " AND candidates.booked >= :interview_date_from";
    }
    if (! empty($interview_date_to)) {
        $query .= " AND candidates.booked <= :interview_date_to";
    }
    if (! empty($delivery_date_from)) {
        $query .= " AND candidates.delivery_date >= :delivery_date_from";
    }
    if (! empty($delivery_date_to)) {
        $query .= " AND candidates.delivery_date <= :delivery_date_to";
    }
    if (! empty($status)) {
        $query .= " AND candidates.status = :can_status";
    }
    if (! empty($company)) {
        $query .= " AND customers.company LIKE :company";
    }
    if (! empty($service_category)) {
        $query .= " AND interviews.service_cat_id = :service_category";
    }
    if (! empty($extra_where_condition)) {
        $query .= $extra_where_condition;
    }
    $query .= "  ORDER BY CASE
        WHEN booked IS NULL OR booked = '' THEN 1  -- Places empty interview dates at the end
        ELSE 0
    END, booked ASC";
    $stmt = $conn->prepare($query);
    if (! empty($place)) {
        $stmt->bindParam(':place', $place);
    }
    if (! empty($candidate)) {
        $stmt->bindParam(':candidate', $candidate);
    }
    if (! empty($customer)) {
        $stmt->bindParam(':customer', $customer);
    }
    if (! empty($status)) {
        $stmt->bindValue(':can_status', $status);
    }
    if (! empty($company)) {
        $company = trim($company);
        $companyParam = '%' . $company . '%';
        $stmt->bindValue(':company', $companyParam);
    }
    if (! empty($service_category)) {
        $stmt->bindParam(':service_category', $service_category);
    }
    if (! empty($order_created_from)) {
        $stmt->bindValue(':order_created_from', date('Y-m-d', strtotime($order_created_from)));
    }
    if (! empty($order_created_to)) {
        $stmt->bindValue(':order_created_to', date('Y-m-d', strtotime($order_created_to)));
    }
    if (! empty($interview_date_from)) {
        $stmt->bindValue(':interview_date_from', date('Y-m-d', strtotime($interview_date_from)));
    }
    if (! empty($interview_date_to)) {
        $stmt->bindValue(':interview_date_to', date('Y-m-d', strtotime($interview_date_to)));
    }
    if (! empty($delivery_date_from)) {
        $stmt->bindValue(':delivery_date_from', date('Y-m-d', strtotime($delivery_date_from)));
    }
    if (! empty($delivery_date_to)) {
        $stmt->bindValue(':delivery_date_to', date('Y-m-d', strtotime($delivery_date_to)));
    }
    $res = $stmt->execute();
    if ($res) {
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    } else {
        $errorInfo = $stmt->errorInfo();
        // Handle the error, log it, or return false
        return false;
    }
}
function getDateAfterDays($days)
{
    // Start from the current date
    $date = new DateTime();
    // Loop through the number of days
    while ($days > 0) {
        // Add 1 day to the current date
        $date->modify('+1 day');
        // Check if the current day is a weekday (Monday to Friday)
        if ($date->format('N') < 6) {
            $days--; // Decrease the number of days to go
        }
    }
    // Return the date after the number of days
    return $date->format('Y-m-d');
}
function getWorkingDaysBetween($startDate, $endDate)
{
    // Calculate working days (Mon–Fri) between two dates in Sweden's timezone
    $tz = new DateTimeZone('Europe/Stockholm');
    $start = new DateTime($startDate, $tz);
    $end = new DateTime($endDate, $tz);
    // Normalize times to midnight
    $start->setTime(0, 0, 0);
    $end->setTime(0, 0, 0);
    if ($start > $end) {
        return 0;
    }
    $workingDays = 0;
    while ($start < $end) {
        $dayOfWeek = (int)$start->format('N'); // 1 (Mon) to 7 (Sun)
        if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
            $workingDays++;
        }
        $start->modify('+1 day');
    }
    return $workingDays;
}
function isSwedenWorkingHours()
{
    // Define Sweden's timezone
    $swedenTimezone = new DateTimeZone('Europe/Stockholm');
    // Get the current time in Sweden
    $swedenTime = new DateTime('now', $swedenTimezone);
    $currentTime = $swedenTime->format('H:i:s');
    // Get the day of the week (1 = Monday, 7 = Sunday)
    $dayOfWeek = $swedenTime->format('N');
    // Check if it is a weekday (Monday to Friday) and within working hours
    if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
        return 1;
    } else {
        return 0;
    }
}
function getNextWorkingHour()
{
    // Define Sweden's timezone
    $swedenTimezone = new DateTimeZone('Europe/Stockholm');
    // Get the current date and time in Sweden
    $currentDateTime = new DateTime('now', $swedenTimezone);
    // Get the current day and time
    $dayOfWeek = $currentDateTime->format('N'); // 1 = Monday, 7 = Sunday
    $currentTime = $currentDateTime->format('H:i:s');
    // Working hours
    $startOfWork = '08:00:00';
    $endOfWork = '18:00:00';
    if ($dayOfWeek >= 1 && $dayOfWeek <= 5) { // Weekdays (Monday to Friday)
        if ($currentTime < $startOfWork) {
            // Before working hours, return today at 08:00
            $currentDateTime->setTime(8, 0, 0);
        } elseif ($currentTime >= $endOfWork) {
            // After working hours, check if today is Friday
            if ($dayOfWeek == 5) {
                // Move to next Monday at 08:00
                $currentDateTime->modify('+3 days')->setTime(8, 0, 0);
            } else {
                // Move to the next day at 08:00
                $currentDateTime->modify('+1 day')->setTime(8, 0, 0);
            }
        } else {
            // During working hours, return current time
            return $currentDateTime;
        }
    } else {
        // Weekend: Move to next Monday at 08:00
        $daysToAdd = ($dayOfWeek == 6) ? 2 : 1; // If Saturday, add 2 days; if Sunday, add 1 day
        $currentDateTime->modify("+{$daysToAdd} days")->setTime(8, 0, 0);
    }
    return $currentDateTime;
}
function logMessage($message, $level = 'INFO')
{
    // Convert array to string if needed
    if (is_array($message)) {
        $message = print_r($message, true); // true returns output instead of printing
    }
    $logFile = __DIR__ . '/../assets/logs/Log_' . date('Y-m-d') . '.log';
    $logDir = dirname($logFile);
    if (! file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }
    $logEntry = '[' . date('Y-m-d H:i:s') . '] ' . strtoupper($level) . ': ' . $message . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}
function cuslogMessage($message, $level = 'INFO')
{
    if (is_array($message)) {
        $message = print_r($message, true);
    }
    $logFile = __DIR__ . '/../assets/logs/cusLog_' . date('Y-m-d') . '.log';
    $logDir = dirname($logFile);
    if (! file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }
    $logEntry = '[' . date('Y-m-d H:i:s') . '] ' . strtoupper($level) . ': ' . $message . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}
