<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// ini_set('display_errors', true);
// ini_set('display_startup_errors', true);
// error_reporting(E_ALL);
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
include('connection.php');
include('config.php');
global $pdo;
$conn = $pdo->open();
$message = '';
// Also present in expire.php and ajax.php
$statuses = ["Pending", "Booked", "Approved", "Interview Interrupted", "Under investigation with SPO", "Denied", "Did not show up", "Canceled", "Candidate doesn't answer"];
$statusesDetail = ["Pending", "Interview has been booked", "Candidate has been approved", "Interview Interrupted", "Candidate is under investigation with SPO", "Candidate has been denied after meeting with SPO", "Candidate did not show up", "Interview has been canceled", "Candidate doesn't answer"];
$statusIcons = ["bi-hourglass-split", "bi-journal-check", "bi-clipboard-check", "bi-x-square", "bi-search", "bi-x-circle", "bi-emoji-dizzy", "bi-trash", "bi-envelope-slash"];
$statusSort = [0 => 0, 8 => 1, 7 => 2, 1 => 3, 6 => 4, 3 => 5, 4 => 6, 2 => 7, 5 => 8];
const INTERVIEW_ID = 1;
const BACKGROUND_ID = 3;
require __DIR__ . '/../PHPMailer/vendor/autoload.php';
include_once(__DIR__ . "/../mail-config.php");
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
                if (isset($_SESSION['previous_page']) && !empty($_SESSION['previous_page'])) {
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
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                return $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
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
            'request_time' => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME'])
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

                if (!empty($otpDB)) {
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
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                return $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
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
            'request_time' => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME'])
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
                if (!empty($otpDB)) {
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
                if (!empty($otpDB)) {
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
        if (!empty($customer)) {
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
    if ($stmt->execute())
        return true;
    else
        return false;
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
function sendMail($body, $to, $name, $subject, $attachment = null, $delay = false)
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
        // $mail->addCC(USERNAME, $email_from);
        //        $mail->addCustomHeader("BCC: ".USERNAME);
        //Content
        $mail->isHTML(true);                                  //Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body = $body;
        //    $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
        if (!empty($attachment)) {
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
        "subject" => $subject
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
    if (!empty($customer)) {
        $text = str_replace('{customer}', $customer, $text);
    }
    if (!empty($candidate)) {
        $text = str_replace('{candidate}', $candidate, $text);
    }
    if (!empty($company)) {
        $text = str_replace('{company}', $company, $text);
    }
    if (!empty($interview)) {
        $text = str_replace('{interview}', $interview, $text);
    }
    if (!empty($staff)) {
        $text = str_replace('{staff}', $staff, $text);
    }
    if (!empty($email)) {
        $text = str_replace('{email}', $email, $text);
    }
    if (!empty($password)) {
        $text = str_replace('{password}', $password, $text);
    }
    if (!empty($status)) {
        $text = str_replace('{status}', $status, $text);
    }
    if (!empty($date)) {
        $text = str_replace('{date}', $date, $text);
    }
    if (!empty($orderID)) {
        $text = str_replace('{orderid}', $orderID, $text);
    }
    if (!empty($interviewDate)) {
        $text = str_replace('{interview_date}', $interviewDate, $text);
    }
    if (!empty($staff_email)) {
        $text = str_replace('{staff_email}', $staff_email, $text);
    }
    if (!empty($comment)) {
        $text = str_replace('{comment}', $comment, $text);
    }
    if (!empty($vascId)) {
        $text = str_replace('{vasc_id}', $vascId, $text);
    }
    if (!empty($service)) {
        $text = str_replace('{service}', $service, $text);
    }
    if (!empty($place)) {
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
    $dates = array();
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
    if (!strpos($redirect_url, "logout")) {
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
    if (!empty($service_id)) {
        $query .= " WHERE i.service_cat_id = ? GROUP BY ss.status_id ORDER BY i.service_cat_id";
        $stmt = $conn->prepare($query);
        $stmt->execute([$service_id]);
    } else {
        if (!empty($catIds)) {
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
    if (!empty($status_service)) {
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
    return !empty($message) ? $message : false;
}
function getStatusServiceCat($status_id)
{
    global $conn;
    $query = "SELECT * FROM statuses s INNER JOIN status_services ss on s.id = ss.status_id INNER JOIN interviews i ON i.id = ss.service_id WHERE s.id = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute([$status_id]);
    $service = $stmt->fetch();
    return !empty($service) ? $service : false;
}
function getCustomerServiceCat($customerID)
{
    global $conn;
    $query = "SELECT * FROM customer_services WHERE cus_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$customerID]);
    $services = $stmt->fetchAll();
    $serviceCats = array();
    if (!empty($services)) {
        foreach ($services as $service) {
            $service = findById("interviews", $service->service_id);
            if (!in_array($service->service_cat_id, $serviceCats)) {
                array_push($serviceCats, $service->service_cat_id);
            }
        }
    }
    return $serviceCats;
}
function getMsgColsByService($service_id)
{
    global $conn;
    $query = "SELECT * FROM `status_services` ss INNER JOIN interviews i ON i.id = ss.service_id WHERE i.id = {$service_id} GROUP BY msg_col ORDER BY status_id ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $msgCols = $stmt->fetchAll();
    return !empty($msgCols) ? $msgCols : false;
}
//Cron Job Functions
function expiredOTP()
{
    global $conn;
    $query = "DELETE FROM otp_verification WHERE date_time < DATE_SUB(NOW(), INTERVAL 24 HOUR);";
    $stmt = $conn->prepare($query);
    $stmt->execute();
}
function isEmailAllowed($cus_id, $status_id)
{
    $res = findByQuery("SELECT * FROM allowed_emails WHERE cus_id = {$cus_id} AND status_id = {$status_id}");
    if ($res) {
        return !($res->allowed == "0");
    }
}
function rand_string($length)
{
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
    return substr(str_shuffle($chars), 0, $length);
}
function flash($name = "", $message = "", $class = "successMsg")
{
    if (!empty($name)) {
        if (!empty($message) && empty($_SESSION[$name])) {
            $_SESSION[$name] = $message;
            $_SESSION["class"] = $class;
        }
        $icons = ["successMsg" => "<i style='color: #00D26A' class='bi bi-check-circle-fill'></i>", "errorMsg" => "<i style='color: #ff4d40' class='bi bi-x-circle-fill'></i>"];
        if (empty($message) && !empty($_SESSION[$name])) {
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
function filter_candidate($place = null, $candidate = null, $customer = null, $order_created_from = null, $order_created_to = null, $interview_date_from = null, $interview_date_to = null, $status = null, $company = null, $extra_where_condition = null, $service_category = null)
{
    global $conn;
    $query = "SELECT candidates.*,statuses.status as status_name,statuses.color as status_color, staff.name as staff_name,customers.id as customer_id,customers.name as customer_name,customers.company as customer_company,places.name as place_name , interviews.title as interview_title,interviews.service_cat_id as service_category FROM candidates LEFT JOIN statuses ON candidates.status = statuses.id LEFT JOIN staff ON candidates.staff_id = staff.id LEFT JOIN customers ON candidates.cus_id = customers.id LEFT JOIN places ON candidates.place = places.id LEFT JOIN interviews ON candidates.interview_id = interviews.id WHERE candidates.expired = 0 AND candidates.invoice_sent = 0";  // Always true condition to simplify query building
    if (!empty($place)) {
        $query .= " AND candidates.place = :place";
    }
    if (!empty($candidate)) {
        $query .= " AND (candidates.name LIKE :candidate OR candidates.surname LIKE :candidate)";
    }
    if (!empty($customer)) {
        $query .= " AND candidates.cus_id = :customer";
    }
    if (!empty($order_created_from)) {
        $query .= " AND candidates.created >= :order_created_from";
    }
    if (!empty($order_created_to)) {
        $query .= " AND candidates.created <= :order_created_to";
    }
    if (!empty($interview_date_from)) {
        $query .= " AND candidates.booked >= :interview_date_from";
    }
    if (!empty($interview_date_to)) {
        $query .= " AND candidates.booked <= :interview_date_to";
    }
    if (!empty($status)) {
        $query .= " AND candidates.status = :can_status";
    }
    if (!empty($company)) {
        $query .= " AND customers.company LIKE :company";
    }
    if (!empty($service_category)) {
        $query .= " AND interviews.service_cat_id = :service_category";
    }
    if (!empty($extra_where_condition)) {
        $query .= $extra_where_condition;
    }
    $query .= "  ORDER BY CASE
        WHEN booked IS NULL OR booked = '' THEN 1  -- Places empty interview dates at the end
        ELSE 0
    END, booked ASC";
    $stmt = $conn->prepare($query);
    if (!empty($place)) {
        $stmt->bindParam(':place', $place);
    }
    if (!empty($candidate)) {
        $stmt->bindParam(':candidate', $candidate);
    }
    if (!empty($customer)) {
        $stmt->bindParam(':customer', $customer);
    }
    if (!empty($status)) {
        $stmt->bindValue(':can_status', $status);
    }
    if (!empty($company)) {
        $company = trim($company);
        $companyParam = '%' . $company . '%';
        $stmt->bindValue(':company', $companyParam);
    }
    if (!empty($service_category)) {
        $stmt->bindParam(':service_category', $service_category);
    }
    if (!empty($order_created_from)) {
        $stmt->bindValue(':order_created_from', date('Y-m-d', strtotime($order_created_from)));
    }
    if (!empty($order_created_to)) {
        $stmt->bindValue(':order_created_to', date('Y-m-d', strtotime($order_created_to)));
    }
    if (!empty($interview_date_from)) {
        $stmt->bindValue(':interview_date_from', date('Y-m-d', strtotime($interview_date_from)));
    }
    if (!empty($interview_date_to)) {
        $stmt->bindValue(':interview_date_to', date('Y-m-d', strtotime($interview_date_to)));
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
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }
    $logEntry = '[' . date('Y-m-d H:i:s') . '] ' . strtoupper($level) . ': ' . $message . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}
function cuslogMessage($message, $level = 'INFO') {
    if (is_array($message)) {
        $message = print_r($message, true);
    }
    $logFile = __DIR__ . '/../assets/logs/cusLog_' . date('Y-m-d') . '.log';
    $logDir = dirname($logFile);
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true); 
    }
    $logEntry = '[' . date('Y-m-d H:i:s') . '] ' . strtoupper($level) . ': ' . $message . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}