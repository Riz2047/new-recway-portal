<?php

$activeLink = "start-order";

include_once "includes/header.php";

if (!isset($_GET['i'])) {
    redirect('index.php');
}

$query = "SELECT * FROM customer_services WHERE cus_id={$_SESSION['customer']->id} AND service_id = {$_GET['i']}";
$stmt = $conn->prepare($query);
$stmt->execute();
$customer_services = $stmt->fetchAll();
if (empty($customer_services)) {
    redirect('index.php');
}

$query = 'SELECT * FROM customers WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['customer']->id]);
$customer = $stmt->fetch();

$query = 'SELECT * FROM interviews WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['i']]);
$interview = $stmt->fetch();

if (empty($interview)) {
    redirect('index.php');
}

if (isset($_POST['order'])) {
    $vasc_id = $_POST['vasc_id'];
    $security = $_POST['security'];
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $referensperson = $_POST['pref'];
    $reference = $_POST['ref'];
    $cus_id = $_SESSION['customer']->id;
    $interview_id = $interview->id;
    $comment = $_POST['comment'];
    $note = $_POST['note'];
    $place = isset($_POST['place']) ? $_POST['place'] : null;
    $sendMail = $_POST['sendMail'];

    $query = "SELECT * FROM candidates";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $candidates = $stmt->fetchAll();

    $order_ids = [];
    if (!empty($candidates)) {
        foreach ($candidates as $candidate) {
            array_push($order_ids, $candidate->order_id);
        }
    }

    $permitted_chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $uid = substr(str_shuffle($permitted_chars), 0, 6);
    while (in_array($uid, $order_ids)) {
        $uid = substr(str_shuffle($permitted_chars), 0, 6);
    }

    $query = 'SELECT * FROM interviews WHERE id = ?';
    $stmt = $conn->prepare($query);
    $stmt->execute([$interview_id]);
    $interview = $stmt->fetch();

    $statusID = $interview->service_cat_id == 1 ? 1 : 13;

    $query = "INSERT INTO candidates (order_id, vasc_id ,security, name, surname, email, phone, place, cv, referensperson, reference, comment, note, cus_id, interview_id, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $stmt = $conn->prepare($query);
    if (!empty($_FILES['files']['name'][0])) {
        $totalFiles = count($_FILES['files']['name']);

        $files = null;
        for ($i = 0; $i < $totalFiles; $i++) {
            $fileName = time() . '-' . $_FILES['files']['name'][$i];
            $files .= $fileName . ',';
            // $cv = !empty($_FILES['cv']['name']) ? $fileName : null;
            move_uploaded_file($_FILES['files']['tmp_name'][$i], '../uploads/' . $fileName);
        }
    }
    $res = $stmt->execute([$uid, $vasc_id, $security, $name, $surname, $email, $phone, $place, isset($files) ? $files : null, $referensperson, $reference, $comment, $note, $cus_id, $interview_id, $statusID]);

    if ($res) {
        $lastInsertId = $conn->lastInsertId();

        $query = 'SELECT * FROM candidates WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$lastInsertId]);
        $candidate = $stmt->fetch();

        $query = 'SELECT * FROM interviews WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$candidate->interview_id]);
        $interview = $stmt->fetch();

        $query = 'SELECT * FROM places WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$candidate->place]);
        $place = $stmt->fetch();

        $query = 'SELECT * FROM service_categories WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$interview->service_cat_id]);
        $serviceCat = $stmt->fetch();

        $query = "INSERT INTO history (order_id, `desc`) VALUES (?,?)";
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$lastInsertId, 'Order Created']);
        // Create a DateTime object for Sweden's timezone
        $swedenTimezone = new DateTimeZone('Europe/Stockholm');
        $swedenTime = new DateTime('now', $swedenTimezone);
        $currentTime = $swedenTime->format('H:i:s');
        $dayOfWeek = date('N');
        if ($sendMail == 'yes') {
            $messages = getMessages($cus_id, $interview->id);

            $cus_msg = $interview->service_cat_id == 1 ? $messages->cus_msg : $messages->cus_msg_background;

            $cusBody = replace($cus_msg, $customer->name, $name . " " . $surname, $customer->company, $interview->title, '', '', '', '', '', $candidate->order_id, '', '', '', $candidate->vasc_id, $interview->title, !empty($place) ? $place->name : '');
            if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
                saveEmail("Customer", $customer->name, $candidate->order_id, 'Customer Message', $cusBody, $customer->email, $serviceCat->name);
                $mailMsg = sendMail($cusBody, $customer->email, $customer->name, $serviceCat->name);
            } else {
                saveEmail("Customer", $customer->name, $candidate->order_id, 'Customer Message', $cusBody, $customer->email, $serviceCat->name, '1');
            }
            $statusID = $interview->service_cat_id == 1 ? 1 : 13;
            $msg = getStatusMessage($statusID, $interview_id, $cus_id);
            if ($msg) {
                $msg = $msg->col;
            }

            $canBody = replace($msg, $customer->name, $name . " " . $surname, $customer->company, $interview->title, '', '', '', '', '', $candidate->order_id, '', '', '', $candidate->vasc_id, $interview->title, !empty($place) ? $place->name : '');

            //            if($interview->id == 1 || $interview->id == 3) {
            //                $canBody = replace($messages->can_msg, $customer->name, $name. " " .$surname, $customer->company, $interview->title,'','','', '', '', $candidate->order_id,'','','', $candidate->vasc_id, $interview->title, !empty($place) ? $place->name : '');
            //            }else{
            //                $canBody = replace($messages->can_msg_2, $customer->name, $name. " " .$surname, $customer->company, $interview->title,'','','', '', '', $candidate->order_id,'','','', $candidate->vasc_id, $interview->title, !empty($place) ? $place->name : '');
            //            }
            if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
                saveEmail("Candidate", $name, $candidate->order_id, 'Candidate Message', $canBody, $email, $serviceCat->name);
                $mailMsg = sendMail($canBody, $_POST['email'], $_POST['name'], $serviceCat->name);
            } else {
                saveEmail("Candidate", $name, $candidate->order_id, 'Candidate Message', $canBody, $email, $serviceCat->name, '1');
            }
            $adminBody = replace($messages->admin_msg, $customer->name, $name . " " . $surname, $customer->company, $interview->title, '', '', '', '', '', $candidate->order_id, '', '', '', $candidate->vasc_id, $interview->title, !empty($place) ? $place->name : '');

            $query = 'SELECT * FROM admin LIMIT 1';
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $admin = $stmt->fetch();
            if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
                saveEmail("Admin", $admin->name, $candidate->order_id, 'Admin Message', $adminBody, $admin->email, 'Order Created');
                $mailMsg = sendMail($adminBody, $admin->email, $admin->name, "Order Created");
            } else {
                saveEmail("Admin", $admin->name, $candidate->order_id, 'Admin Message', $adminBody, $admin->email, 'Order Created', '1');
            }
            $mailMsg = "<p class='text-success text-center w-700 f-20'>Order created successfully!</p>";
        } else {
            $mailMsg = "<p class='text-success text-center w-700 f-20'>Candidate created successfully!</p>";
        }
    } else {
        $mailMsg = "<p class='text-danger text-center w-700 f-20'>Data save error!</p>";
    }
}

$query = 'SELECT * FROM places';
$stmt = $conn->prepare($query);
$stmt->execute();
$places = $stmt->fetchAll();

?>

<section>
    <div class="container mt-3">
        <div class="row">
            <p class="f-22 text-grey w-700 mb-0 pb-0"><?php echo $interview->title ?></p>
            <div class="col-lg-12">
                <form method="post" action="order.php?i=<?php echo $_GET['i'] ?>" enctype="multipart/form-data" class="form">
                    <?php echo isset($mailMsg) ? $mailMsg : '' ?>
                    <div class="form-tag mb-2">Personal Info</div>
                    <div class="d-flex align-items-center form-row mb-3">
                        <label for="name"> Name<span class="star text-danger">*</span></label>
                        <input type="text" required id="name" name="name" placeholder="Enter Candidate Name" class="w-100 from-input">
                        <div class="form-icon me-2">
                            <i class="bi bi-person"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center form-row mb-3">
                        <label for="surname">Surname<span class="star text-danger">*</span></label>
                        <input type="text" required id="surname" name="surname" placeholder="Enter Candidate Surname" class="w-100 from-input">
                        <div class="form-icon me-2">
                            <i class="bi bi-person"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center form-row mb-3">
                        <label for="email">Email<span class="star text-danger">*</span></label>
                        <input id="email" required type="email" name="email" placeholder="Enter Candidate Email" class="w-100 from-input">
                        <div class="form-icon me-2">
                            <i class="bi bi-envelope"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center form-row mb-3">
                        <label for="phone">Phone<span class="star text-danger">*</span></label>
                        <input id="phone" required type="text" name="phone" placeholder="Enter Candidate Phone Number" class="w-100 from-input">
                        <div class="form-icon me-2">
                            <i class="bi bi-telephone"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center form-row mb-3">
                        <label for="ssn">Social Security Number<span class="star text-danger">*</span></label>
                        <input id="ssn" type="text" name="security" placeholder="Enter Candidate Social Security Number" class="w-100 from-input" required>
                        <div class="form-icon me-2">
                            <i class="bi bi-shield-fill-exclamation"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center form-row mb-3">
                        <label for="vasc">VASC ID</label>
                        <input id="vasc" type="text" name="vasc_id" placeholder="Enter Candidate VASC ID" class="w-100 from-input">
                        <div class="form-icon me-2">
                            <i class="bi bi-app-indicator"></i>
                        </div>
                    </div>
                    <?php if ($_GET['i'] == 2 || $_GET['i'] == 4 || $_GET['i'] == 26) : ?>
                        <div class="d-flex align-items-center form-row mb-3">
                            <label for="place">Place<span class="star text-danger">*</span></label>
                            <select id="place" name="place" class="form-select from-input" aria-label="Default select example">
                                <?php if (!empty($places)) : ?>
                                    <?php foreach ($places as $place) : ?>
                                        <option value="<?php echo $place->id ?>"><?php echo $place->name ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <!-- <div class="form-icon me-2">
                                <i class="bi bi-globe2"></i>
                              </div> -->
                        </div>
                    <?php endif; ?>
                    <div class="form-row mb-3 border-0">
                        <label for="cv" class="border-0">Documents</label>
                        <div class="drop-zone">
                            <span class="drop-zone__prompt">
                                <div class="d-flex flex-column justify-content-center align-items-center">
                                    <i class="bi bi-cloud-arrow-up-fill f-40"></i>
                                    Here you can upload several documents (Interview Templates, Documents or CV)
                                </div>
                            </span>
                            <input type="file" name="files[]" id="cv" class="drop-zone__input" accept="application/pdf" multiple>
                        </div>
                    </div>
                    <div class="form-tag mb-2">Billing</div>
                    <div class="d-flex align-items-center form-row mb-3">
                        <label for="pref" class="label-lg">Reference<br>(Invoice Recipient)<span class="star text-danger">*</span></label>
                        <input id="pref" type="text" required name="pref" value="<?php echo $customer->name ?>" placeholder="Enter Candidate Reference" class="w-100 from-input">
                        <div class="form-icon me-2">
                            <i class="bi bi-app-indicator"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center form-row mb-3">
                        <label for="ref" class="label-lg">Reference<span class="star text-danger">*</span></label>
                        <input id="ref" required type="text" name="ref" value="<?php echo $customer->cost_place ?>" placeholder="Enter Candidate Reference" class="w-100 from-input">
                        <div class="form-icon me-2">
                            <i class="bi bi-app-indicator"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center form-row mb-3">
                        <label for="" class="label-lg">Invoice Comment <br> (Visible on the invoice)</label>
                        <input type="text" name="comment" placeholder="Enter Invoice Comment" class="w-100 from-input">
                        <div class="form-icon me-2">
                            <i class="bi bi-card-text"></i>
                        </div>
                    </div>
                    <div class="form-row mb-1 border-0">
                        <label for="" class="border-0">Note</label>
                        <textarea name="note" id="" class="w-100 form-textarea" rows="4" placeholder="Here you can create a note about the order that is visible to both you and us. Please note that this note will not be visible to the individual."></textarea>
                    </div>

                    <div class="d-flex align-items-center ">
                        <p class="f-14 w-700 mb-0 pb-0 me-3">Send Mail<span class="star text-danger">*</span></label></p>
                        <div class="form-check me-2">
                            <input class="form-check-input" type="radio" name="sendMail" value="yes" checked id="flexRadioDefault1">
                            <label class="form-check-label" for="flexRadioDefault1">
                                Yes
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="sendMail" value="no" id="flexRadioDefault2">
                            <label class="form-check-label" for="flexRadioDefault2">
                                No
                            </label>
                        </div>
                    </div>

                    <div class="form-check mt-3">
                        <input class="form-check-input" required type="checkbox" value="" id="flexCheckDefault">
                        <label class="form-check-label" for="flexCheckDefault">
                            I agree to the <a class="text-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">Integrity Policy</a>
                        </label>
                    </div>
                    <!-- <div class="d-flex align-items-center form-row mb-3">
                      <label for="">Candidate's Country</label>
                      <select class="form-select from-input" aria-label="Default select example">
                        <option selected>Enter Candidate Country</option>
                        <option value="1">Pakistan</option>
                        <option value="2">Swedin</option>
                        <option value="3">Us</option>
                      </select>
                    </div> -->

                    <div class="d-flex justify-content-start mt-4">
                        <button type="submit" name="order" class="form-btn border-0">Send Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ">
            <div class="modal-header">
                <h5 class="modal-title f-16 w-600 text-black" id="exampleModalLabel">Integrity Policy</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h1 class="f-16 w-600 text-black">
                    Allmänt
                </h1>
                <p class="f-14 w-500 text-grey">
                    Recway AB, org.nr 559102-3444, (Recway ) värnar om din personliga integritet. Syftet med denna policy är att på ett tydligt och transparent sätt redogöra för hur Recway AB hanterar dina personuppgifter i enlighet med EU:s dataskyddsförordning 2016/679 (General Data Protection Regulation, GDPR). Nedan hittar du bl.a. information om vilka personuppgifter Recway AB behandlar, för vilka ändamål, den rättsliga grunden för behandlingen, hur länge dina uppgifter sparas samt vilka rättigheter du har.
                    För samtliga av våra kunder, granskade och egna medarbetare gäller att det är du som enskild som har makten över dina uppgifter.
                </p>

                <h1 class="f-16 w-600 text-black">
                    Vem är personuppgiftsansvarig?
                </h1>
                <p class="f-14 w-500 text-grey">
                    Våra kunder är personuppgiftsansvarig för behandlingen av dina personuppgifter och vi är personuppgiftsbiträde åt vår kund när det gäller tjänsterna:
                </p>
                <ul>
                    <li>
                        <p class="f-14 w-500 text-grey mb-0">
                            Säkerhetsprövningsintervju
                        </p>
                    </li>
                    <li>
                        <p class="f-14 w-500 text-grey mb-0">
                            Bakgrundskontroll
                        </p>
                    </li>
                    <li>
                        <p class="f-14 w-500 text-grey mb-0">
                            Utbildning
                        </p>
                    </li>
                </ul>
                <p class="f-14 w-500 text-grey">
                    Har du några frågor rörande behandlingen av dina personuppgifter, vänligen kontakta oss på dataprotection@recway.nu. För fler kontaktuppgifter, se rubriken ”Så kontaktar du oss”. Det är då vår kund som ansvarar för att informera dig som anställd, jobbsökande eller referent om personuppgiftsbehandlingen.
                </p>
                <h1 class="f-16 w-600 text-black">
                    Hur samlar vi in information?
                </h1>
                <p class="f-14 w-500 text-grey">
                    <b>Från kund: </b> Vi samlar in information om dig från våra kunder, t.ex. personuppgifter som du valt att inkludera i ditt CV och/eller andra dokument. Vi samlar in information om dig från våra kunder, t.ex. personuppgifter som du valt att inkludera i ditt CV och/eller andra dokument.
                </p>
                <p class="f-14 w-500 text-grey">
                    <b>Från tredje part: </b>
                    Vi samlar in information om dig från allmänt tillgängliga källor hos svenska eller utländska myndigheter samt från företag och/eller utbildningsinstitut angivna i ditt CV. Recway får information från kunden om den som ska intervjuas (namn, personnummer, kontaktinformation, tjänst som ska tillsättas, CV med tillhörande information om eventuella utbildningar och tidigare arbetsgivare. Vid en säkerhetsprövningsintervju samlar vi in information om den granskade från offentliga källor och öppet publicerade webbplatser. Detta material bearbetas därefter och slutligen sammanställs relevant information i en rapport som den granskade får ta del av.
                </p>

                <h1 class="f-16 w-600 text-black">
                    Varför samlar vi in personuppgifter?
                </h1>
                <p class="f-14 w-500 text-grey">
                    Avseende kandidater som genomgår en säkerhetsprövningsintervju behandlas personuppgifter i huvudsak för de ändamål som anges nedan.</p>

                <h1 class="f-16 w-600 text-black">
                    För administration och leverans av
                </h1>
                <p class="f-14 w-500 text-grey">
                    <b>säkerhetsprövningsintervju: </b>
                    Recway AB behandlar dina personuppgifter för att kunna producera och leverera våra tjänster, d.v.s. säkerhetsprövningsintervjuer.
                </p>

                <p class="f-14 w-500 text-grey">
                    <b>För marknadsföring:</b>
                    Recway AB använder dina personuppgifter för att tillhandahålla information/marknadsföring via e-post, sms eller andra kontaktvägar när du har en aktiv kundrelation med oss samt för att vi ska kunna utföra riktade erbjudanden och tjänster.
                </p>


                <p class="f-14 w-500 text-grey">
                    <b>För att leverera vår tjänst: </b>
                    Recway AB behandlar våra kunders personuppgifter främst i syfte att kunna leverera vår tjänst och uppfylla vårt avtal med dig, d.v.s. tillhandahålla säkerhetsprövningsintervjuer.
                </p>


                <p class="f-14 w-500 text-grey">
                    <b>För affärsutveckling: </b>
                    Recway AB använder information om våra kunder i syfte att ta fram statistiska data om tjänstens nyttjande. Denna statistik identifierar dock aldrig enskilda personer, utan sker på aggregerad nivå. Denna analys utförs i syfte att kunna utveckla, leverera och förbättra våra produkter och tjänster.Lagring av personuppgifter i samband med bakgrundskontroll
                </p>

                <h1 class="f-16 w-600 text-black">
                    Lagring
                </h1>
                <p class="f-14 w-500 text-grey">
                    Recway AB säkerställer att vår personuppgiftsbehandling sker enligt gällande lagstiftning, vilket innebär att dina personuppgifter inte bevaras längre än vad som är nödvändigt med hänsyn till ändamålen med behandlingen. Avseende våra kandidater raderas därför alltid alla personuppgifter 14 dagar efter slutförd leverans. Vid avbruten process raderas alla personuppgifter skyndsamt. Avseende våra kunder lagras dina personuppgifter så länge du är kund hos oss. Uppgifterna gallras ut när de inte längre är aktuella eller nödvändiga för de ändamål som de samlats in för, t.ex. direktmarknadsföring eller analyser. Viss information kan dock behållas längre om det krävs med hänsyn till andra lagkrav, t.ex. bokföringslagen (1999:1078). Vid all hantering av personuppgifter iakttas dock alltid högsta säkerhet och sekretess.
                </p>

                <h1 class="f-16 w-600 text-black">
                    Dina rättigheter
                </h1>
                <p class="f-14 w-500 text-grey">
                    Som registrerad har du flera olika rättigheter vad gäller dina personuppgifter och du har möjlighet att påverka din information och vad som sparas.
                </p>

                <p class="f-14 w-500 text-grey">
                    <b>Komplettering och rättelse av uppgifter: </b>
                    Recway AB kommer på din begäran eller efter eget initiativ att rätta eller komplettera uppgifter som upptäcks vara felaktiga, ofullständiga eller missvisande. Du har rätt att utan onödigt dröjsmål få dina personuppgifter raderade.
                </p>

                <p class="f-14 w-500 text-grey">
                    <b>Kopia av uppgifter:</b>
                    Kunden som har beställd en säkerhetsprövningsintervju får under 14 dagar tillgång till resultatet av säkerhetsprövningsintervjun. Eftersom alla dina personuppgifter raderas efter denna period, kan registerutdrag inte hämtas efter detta.
                </p>

                <p class="f-14 w-500 text-grey">
                    <b>Invändningar:</b>
                    Du kan när som helst avstå från att ta emot marknadsföringskommunikation från oss genom att i eventuella utskick välja att avsluta prenumeration. Om du behöver ytterligare hjälp avseende vår kommunikation, vänligen kontakta oss. Se rubriken ”Så kontaktar du oss” för kontaktuppgifter. Du har alltid rätt att göra invändningar till vår behandling av personuppgifter om du anser att vi inte har berättigade skäl till behandlingen om du anser att vi inte har berättigade skäl till behandlingen.
                </p>


                <p class="f-14 w-500 text-grey">
                    <b>Rätt att avbryta processen:</b>
                    Du har rätt att avbryta processen. På din begäran kommer Recway AB omedelbart sluta behandla dina personuppgifter och skyndsamt radera dina uppgifter. Om uppdragsgivaren väljer att avbryta processen kommer Recway AB skyndsamt radera dina uppgifter. Avbruten process påverkar dock inte lagligheten av behandlingen av dina personuppgifter innan processen avbröts.
                </p>



                <h1 class="f-16 w-600 text-black">
                    Utbildning
                </h1>
                <p class="f-14 w-500 text-grey">
                    Personuppgifter om de som anmäler sig till våra kurser Recway behandlar personuppgifter i samband med anmälan till våra kurser, för att administrera kursanmälan, genomföra kursen och för uppföljning. De kategorier av personuppgifter som behandlas är namn och kontaktuppgifter samt annan information som anges i anmälan. Den rättsliga grunden är att fullgöra det avtal som ingåtts genom anmälan, och när det gäller uppföljning, vårt berättigade intresse av att följa upp vår verksamhet. Uppgifterna lagras från anmälan till och med 1 år efter avslutad kurs.
                </p>


                <h1 class="f-16 w-600 text-black">
                    Våra leverantörer och var de finns
                </h1>
                <p class="f-14 w-500 text-grey">
                    Vi delar även personuppgifter med leverantörer av den IT-infrastruktur som vi behöver för att kunna tillhandahålla våra tjänster. Våra leverantörer lagrar data inom Sverige. Vi har vidtagit flera säkerhetsåtgärder såsom avancerad kryptering och pseudonymisering av data för att skydda dina personuppgifter.
                </p>


                <p class="f-14 w-500 text-grey">
                    <b>Klagomål: </b>
                    Om du anser att dina rättigheter inte respekteras av Recway AB får du gärna kontakta oss. Du har också alltid rätt att inge klagomål till tillsynsmyndigheten Integritetsskyddsmyndigheten om du anser att Recway AB behandlar dina personuppgifter i strid med tillämplig lagstiftning. Sådana klagomål kan inges via e-post, imy@imy.se , eller med brev till Integritetsskyddsmyndigheten, Box 8114, 104 20 Stockholm. Läs mer på www.imy.se
                </p>

                <h1 class="f-16 w-600 text-black">
                    Cookies och länkar till andra hemsidor
                </h1>
                <p class="f-14 w-500 text-grey">
                    Personuppgifter kan insamlas när du använder vår webbplats, och då lagras informationen om din användning och vilka sidor som besöks. Det kan vara teknisk information om din enhet och internetuppkoppling såsom operativsystem, webbläsarversion, IP-adress, cookies och unika identifierare. Vid besök på våra webbplatser där våra tjänster tillhandahålls kan olika tekniker användas för att känna igen dig i syfte att lära oss mer om våra användare. Detta kan ske direkt eller genom användning av teknik från tredje part. För att kunna använda vår webbplats fullt ut måste du acceptera cookies, och det gör du genom din webbläsares inställningar eller nere i sidfoten på din dator eller mobiltelefon. Vill du inte acceptera cookies kan du stänga av cookies via din webbläsares säkerhetsinställningar. Detta innebär dock att webbplatsen inte kommer att fungera som avsett. I händelse av att vår webbplats innehåller länkar till tredje parts webbplatser, hemsidor, eller material publicerat hos tredje part, är dessa länkar endast för informationssyfte. Eftersom Recway AB saknar kontroll över innehållet på dessa webbplatser eller dess material ansvarar vi inte för dess innehåll. Recway AB ansvarar inte heller för skador eller förluster som skulle kunna uppstå vid användning av dessa länkar.
                    Så kontaktar du oss
                </p>


                <p class="f-14 w-500 text-grey mb-0 pb-0">
                    För ytterligare information är du välkommen att kontakta oss på:
                </p>
                <p class="f-14 w-500 text-grey mb-0 pb-0">
                    Recway AB
                </p>

                <p class="f-14 w-500 text-grey mb-0 pb-0">
                    Tallvägen 1
                </p>
                <p class="f-14 w-500 text-grey mb-0 pb-0">
                    147 31 Tumba
                </p>
                <p class="f-14 w-500 text-grey mb-0 pb-0">
                    E-postadress: 070 65 65 770 dataprotection@recway.nu
                </p>
                <p class="f-14 w-500 text-grey mb-0 pb-0">
                    Telefonnummer: 08-611 10 20
                </p>
                <p class="f-14 w-500 text-grey mb-0 pb-0">
                    Förändringar av denna personuppgiftspolicy
                    Denna personuppgiftspolicy är reviderad per den 28 juni 2022 (version 2022:2).
                </p>

            </div>
            <div class="modal-footer">
                <button type="button" class="form-btn" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php

include_once "includes/footer.php";

?>