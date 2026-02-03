<?php



include_once('includes/header.php');



if (isset($_POST['order'])) {

    $vasc_id = $_POST['vasc_id'];

    $security = $_POST['security'];

    $name = $_POST['name'];

    $surname = $_POST['surname'];

    $email = $_POST['email'];

    $phone = $_POST['phone'];

    $referensperson = $_POST['pref'];

    $reference = $_POST['ref'];

    $cus_id = $_POST['customer'];

    $interview_id = $_POST['interview'];

    $comment = $_POST['comment'];

    $note = $_POST['note'];

    $sendMail = $_POST['sendMail'];

    $sendMailCan = $_POST['sendMailCan'];

    $place = isset($_POST['place']) ? $_POST['place'] : null;

    $country = isset($_POST['country']) ? $_POST['country'] : null;



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



    $query = "INSERT INTO candidates (order_id, vasc_id, security, name, surname, email, phone, place, country, cv, referensperson, reference, comment, note, cus_id, interview_id, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

    $stmt = $conn->prepare($query);

    if (!empty($_FILES['files']['name'][0])) {

        $totalFiles = count($_FILES['files']['name']);



        $files = null;

        for ($i = 0; $i < $totalFiles; $i++) {

            $fileName = time() . '-' . $_FILES['files']['name'][$i];

            $fileName = str_replace(",", "", $fileName);

            $files .= $fileName . ',';

            // $cv = !empty($_FILES['cv']['name']) ? $fileName : null;

            move_uploaded_file($_FILES['files']['tmp_name'][$i], '../uploads/' . $fileName);

        }

    }

    $res = $stmt->execute([$uid, $vasc_id, $security, $name, $surname, $email, $phone, $place, $country, isset($files) ? $files : null, $referensperson, $reference, $comment, $note, $cus_id, $interview_id, $statusID]);



    if ($res) {

        $lastInsertId = $conn->lastInsertId();



        $query = 'SELECT * FROM candidates WHERE id = ?';

        $stmt = $conn->prepare($query);

        $stmt->execute([$lastInsertId]);

        $candidate = $stmt->fetch();



        $query = 'SELECT * FROM customers WHERE id = ?';

        $stmt = $conn->prepare($query);

        $stmt->execute([$cus_id]);

        $customer = $stmt->fetch();



        $query = 'SELECT * FROM places WHERE id = ?';

        $stmt = $conn->prepare($query);

        $stmt->execute([$candidate->place]);

        $place = $stmt->fetch();



        $query = 'SELECT * FROM interviews WHERE id = ?';

        $stmt = $conn->prepare($query);

        $stmt->execute([$interview_id]);

        $interview = $stmt->fetch();



        $query = 'SELECT * FROM service_categories WHERE id = ?';

        $stmt = $conn->prepare($query);

        $stmt->execute([$interview->service_cat_id]);

        $serviceCat = $stmt->fetch();



        $query = "INSERT INTO history (order_id, `desc`) VALUES (?,?)";

        $stmt = $conn->prepare($query);

        $res = $stmt->execute([$lastInsertId, 'Order Created']);



        if ($sendMail == 'yes') {

            $messages = getMessages($cus_id, $interview->id);



            $cus_msg = $interview->service_cat_id == 1 ? $messages->cus_msg : $messages->cus_msg_background;



            $cusBody = replace($cus_msg, $customer->name, $name . " " . $surname, $customer->company, $interview->title, '', '', '', '', '', $candidate->order_id, '', '', '', $candidate->vasc_id, $interview->title, !empty($place) ? $place->name : '');



            saveEmail("Customer", $customer->name, $candidate->order_id, 'Customer Message', $cusBody, $customer->email, $serviceCat->name);

            $mailMsg = sendMail($cusBody, $customer->email, $customer->name, $serviceCat->name);

        }



        if ($sendMailCan == 'yes') {

            $statusID = $interview->service_cat_id == 1 ? 1 : 13;

            $msg = getStatusMessage($statusID, $interview_id, $cus_id);

            if ($msg) {

                $msg = $msg->col;

            }



            $canBody = replace($msg, $customer->name, $name . " " . $surname, $customer->company, $interview->title, '', '', '', '', '', $candidate->order_id, '', '', '', $candidate->vasc_id, $interview->title, !empty($place) ? $place->name : '');



            saveEmail("Candidate", $name, $candidate->order_id, 'Candidate Message', $canBody, $email, $serviceCat->name);

            $mailMsg = sendMail($canBody, $_POST['email'], $_POST['name'], $serviceCat->name);

        }



        $adminBody = replace($messages->admin_msg, $customer->name, $name . " " . $surname, $customer->company, $interview->title, '', '', '', '', '', $candidate->order_id, '', '', '', $candidate->vasc_id, $interview->title, !empty($place) ? $place->name : '');



        $query = 'SELECT * FROM admin LIMIT 1';

        $stmt = $conn->prepare($query);

        $stmt->execute();

        $admin = $stmt->fetch();



        saveEmail("Admin", $admin->name, $candidate->order_id, 'Admin Message', $adminBody, $admin->email, 'Order Created');

        $mailMsg = sendMail($adminBody, $admin->email, $admin->name, "Order Created");



        $mailMsg = "<p class='alert alert-success'>Candidate created successfully!</p>";

    } else {

        $mailMsg = "<p class='alert alert-danger'>Data save error!</p>";

    }

}



$query = 'SELECT * FROM customers';

$stmt = $conn->prepare($query);

$stmt->execute();

$customers = $stmt->fetchAll();



$query = 'SELECT * FROM interviews';

$stmt = $conn->prepare($query);

$stmt->execute();

$interviews = $stmt->fetchAll();



$query = 'SELECT * FROM places';

$stmt = $conn->prepare($query);

$stmt->execute();

$places = $stmt->fetchAll();



?>





<div class="row">



    <div class="col-lg-12">

        <?php

        $pageTitle = "Add Candidate";

        $pageLink = "";

        include_once "buttons-row.php";

        ?>

        <div class="box shadow">

            <?php echo isset($mailMsg) ? $mailMsg : '' ?>

            <p class="f-14">Enter the individual's personal data for the security clearance interview.</p>

            <p class="f-14 text-danger">* Required</p>

            <form class="row" method="post" action="" enctype="multipart/form-data">

                <div class="col-lg-12">

                    <p class="f-16 mb-0 pb-0 w-600 mt-2"><span class="text-danger">*</span> Customer</p>

                    <select class="form-select mb-3" name="customer" id="">

                        <?php foreach ($customers as $customer) : ?>

                            <option <?php echo isset($cus_id) && $cus_id == $customer->id ? 'selected' : '' ?> value="<?php echo $customer->id ?>"><?php echo $customer->name ?></option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="col-lg-12">

                    <p class="f-16 mb-0 pb-0 w-600 mt-2"><span class="text-danger">*</span> Interview</p>

                    <select class="form-select mb-3" name="interview" id="interview">

                        <?php foreach ($interviews as $interview) : ?>

                            <option value="<?php echo $interview->id ?>"><?php echo $interview->title ?></option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="col-lg-12">

                    <p class="f-16 mb-0 pb-0 w-600 mt-2"><span class="text-danger">*</span> Send Mail - Customer</p>

                    <label class="me-2 ms-3">

                        <input class="mb-3" type="radio" name="sendMail" value="yes" checked> Yes

                    </label>

                    <label>

                        <input class="mb-3" type="radio" name="sendMail" value="no"> No

                    </label>

                </div>

                <div class="col-lg-12">

                    <p class="f-16 mb-0 pb-0 w-600 mt-2"><span class="text-danger">*</span> Send Mail - Candidate</p>

                    <label class="me-2 ms-3">

                        <input class="mb-3" type="radio" name="sendMailCan" value="yes" checked> Yes

                    </label>

                    <label>

                        <input class="mb-3" type="radio" name="sendMailCan" value="no"> No

                    </label>

                </div>

                <div class="col-lg-6">

                    <p class="f-16 mb-0 pb-0 w-600 mt-2"><span class="text-danger">*</span> Social Security Number</p>

                    <input type="text" name="security" required class="sign-input w-100 mb-3" placeholder="Social Security Number (yyyymmddxxxx)">

                </div>

                <div class="col-lg-6">

                    <p class="f-16 mb-0 pb-0 w-600 mt-2"><span class="text-danger"></span> VASC ID</p>

                    <input type="text" name="vasc_id" class="sign-input w-100 mb-3" placeholder="VASC ID">

                </div>

                <div class="col-lg-6">

                    <p class="f-16 mb-0 pb-0 w-600 mt-2"><span class="text-danger">*</span> Name</p>

                    <input type="text" name="name" required class="sign-input w-100 mb-3" placeholder="Name ">

                </div>

                <div class="col-lg-6">

                    <p class="f-16 mb-0 pb-0 w-600 mt-2"><span class="text-danger">*</span> Surname</p>

                    <input type="text" name="surname" required class="sign-input w-100 mb-3" placeholder="Surname ">

                </div>

                <div class="col-lg-6">

                    <p class="f-16 mb-0 pb-0 w-600 mt-2"><span class="text-danger">*</span> Email</p>

                    <input type="email" name="email" required class="sign-input w-100 mb-3" placeholder="Email Address ">

                </div>

                <div class="col-lg-6">

                    <p class="f-16 mb-0 pb-0 w-600 mt-2"><span class="text-danger">*</span> Phone</p>

                    <input type="text" name="phone" required class="sign-input w-100 mb-3" placeholder="Phone ">

                </div>

                <div class="col-lg-12 d-none" id="place">

                    <p class="f-16 mb-0 pb-0 w-600 mt-2"><span class="text-danger">*</span> Place</p>

                    <select class="form-select mb-3" disabled name="place" id="">

                        <?php if (!empty($places)) : ?>

                            <?php foreach ($places as $place) : ?>

                                <option value="<?php echo $place->id ?>"><?php echo $place->name ?></option>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </select>

                </div>

                <div class="col-lg-12 d-none" id="country">

                    <p class="f-16 mb-0 pb-0 w-600 mt-2"><span class="text-danger">*</span> Country</p>

                    <select class="form-select mb-3" disabled name="country" id="">

                        <option value="Afghanistan">Afghanistan</option>

                        <option value="Aland Islands">Aland Islands</option>

                        <option value="Albania">Albania</option>

                        <option value="Algeria">Algeria</option>

                        <option value="American Samoa">American Samoa</option>

                        <option value="Andorra">Andorra</option>

                        <option value="Angola">Angola</option>

                        <option value="Anguilla">Anguilla</option>

                        <option value="Antarctica">Antarctica</option>

                        <option value="Antigua and Barbuda">Antigua and Barbuda</option>

                        <option value="Argentina">Argentina</option>

                        <option value="Armenia">Armenia</option>

                        <option value="Aruba">Aruba</option>

                        <option value="Australia">Australia</option>

                        <option value="Austria">Austria</option>

                        <option value="Azerbaijan">Azerbaijan</option>

                        <option value="Bahamas">Bahamas</option>

                        <option value="Bahrain">Bahrain</option>

                        <option value="Bangladesh">Bangladesh</option>

                        <option value="Barbados">Barbados</option>

                        <option value="Belarus">Belarus</option>

                        <option value="Belgium">Belgium</option>

                        <option value="Belize">Belize</option>

                        <option value="Benin">Benin</option>

                        <option value="Bermuda">Bermuda</option>

                        <option value="Bhutan">Bhutan</option>

                        <option value="Bolivia">Bolivia</option>

                        <option value="Bonaire, Sint Eustatius and Saba">Bonaire, Sint Eustatius and Saba</option>

                        <option value="Bosnia and Herzegovina">Bosnia and Herzegovina</option>

                        <option value="Botswana">Botswana</option>

                        <option value="Bouvet Island">Bouvet Island</option>

                        <option value="Brazil">Brazil</option>

                        <option value="British Indian Ocean Territory">British Indian Ocean Territory</option>

                        <option value="Brunei Darussalam">Brunei Darussalam</option>

                        <option value="Bulgaria">Bulgaria</option>

                        <option value="Burkina Faso">Burkina Faso</option>

                        <option value="Burundi">Burundi</option>

                        <option value="Cambodia">Cambodia</option>

                        <option value="Cameroon">Cameroon</option>

                        <option value="Canada">Canada</option>

                        <option value="Cape Verde">Cape Verde</option>

                        <option value="Cayman Islands">Cayman Islands</option>

                        <option value="Central African Republic">Central African Republic</option>

                        <option value="Chad">Chad</option>

                        <option value="Chile">Chile</option>

                        <option value="China">China</option>

                        <option value="Christmas Island">Christmas Island</option>

                        <option value="Cocos (Keeling) Islands">Cocos (Keeling) Islands</option>

                        <option value="Colombia">Colombia</option>

                        <option value="Comoros">Comoros</option>

                        <option value="Congo">Congo</option>

                        <option value="Congo, Democratic Republic of the Congo">Congo, Democratic Republic of the Congo</option>

                        <option value="Cook Islands">Cook Islands</option>

                        <option value="Costa Rica">Costa Rica</option>

                        <option value="Cote D'Ivoire">Cote D'Ivoire</option>

                        <option value="Croatia">Croatia</option>

                        <option value="Cuba">Cuba</option>

                        <option value="Curacao">Curacao</option>

                        <option value="Cyprus">Cyprus</option>

                        <option value="Czech Republic">Czech Republic</option>

                        <option value="Denmark">Denmark</option>

                        <option value="Djibouti">Djibouti</option>

                        <option value="Dominica">Dominica</option>

                        <option value="Dominican Republic">Dominican Republic</option>

                        <option value="Ecuador">Ecuador</option>

                        <option value="Egypt">Egypt</option>

                        <option value="El Salvador">El Salvador</option>

                        <option value="Equatorial Guinea">Equatorial Guinea</option>

                        <option value="Eritrea">Eritrea</option>

                        <option value="Estonia">Estonia</option>

                        <option value="Ethiopia">Ethiopia</option>

                        <option value="Falkland Islands (Malvinas)">Falkland Islands (Malvinas)</option>

                        <option value="Faroe Islands">Faroe Islands</option>

                        <option value="Fiji">Fiji</option>

                        <option value="Finland">Finland</option>

                        <option value="France">France</option>

                        <option value="French Guiana">French Guiana</option>

                        <option value="French Polynesia">French Polynesia</option>

                        <option value="French Southern Territories">French Southern Territories</option>

                        <option value="Gabon">Gabon</option>

                        <option value="Gambia">Gambia</option>

                        <option value="Georgia">Georgia</option>

                        <option value="Germany">Germany</option>

                        <option value="Ghana">Ghana</option>

                        <option value="Gibraltar">Gibraltar</option>

                        <option value="Greece">Greece</option>

                        <option value="Greenland">Greenland</option>

                        <option value="Grenada">Grenada</option>

                        <option value="Guadeloupe">Guadeloupe</option>

                        <option value="Guam">Guam</option>

                        <option value="Guatemala">Guatemala</option>

                        <option value="Guernsey">Guernsey</option>

                        <option value="Guinea">Guinea</option>

                        <option value="Guinea-Bissau">Guinea-Bissau</option>

                        <option value="Guyana">Guyana</option>

                        <option value="Haiti">Haiti</option>

                        <option value="Heard Island and Mcdonald Islands">Heard Island and Mcdonald Islands</option>

                        <option value="Holy See (Vatican City State)">Holy See (Vatican City State)</option>

                        <option value="Honduras">Honduras</option>

                        <option value="Hong Kong">Hong Kong</option>

                        <option value="Hungary">Hungary</option>

                        <option value="Iceland">Iceland</option>

                        <option value="India">India</option>

                        <option value="Indonesia">Indonesia</option>

                        <option value="Iran, Islamic Republic of">Iran, Islamic Republic of</option>

                        <option value="Iraq">Iraq</option>

                        <option value="Ireland">Ireland</option>

                        <option value="Isle of Man">Isle of Man</option>

                        <option value="Israel">Israel</option>

                        <option value="Italy">Italy</option>

                        <option value="Jamaica">Jamaica</option>

                        <option value="Japan">Japan</option>

                        <option value="Jersey">Jersey</option>

                        <option value="Jordan">Jordan</option>

                        <option value="Kazakhstan">Kazakhstan</option>

                        <option value="Kenya">Kenya</option>

                        <option value="Kiribati">Kiribati</option>

                        <option value="Korea, Democratic People's Republic of">Korea, Democratic People's Republic of</option>

                        <option value="Korea, Republic of">Korea, Republic of</option>

                        <option value="Kosovo">Kosovo</option>

                        <option value="Kuwait">Kuwait</option>

                        <option value="Kyrgyzstan">Kyrgyzstan</option>

                        <option value="Lao People's Democratic Republic">Lao People's Democratic Republic</option>

                        <option value="Latvia">Latvia</option>

                        <option value="Lebanon">Lebanon</option>

                        <option value="Lesotho">Lesotho</option>

                        <option value="Liberia">Liberia</option>

                        <option value="Libyan Arab Jamahiriya">Libyan Arab Jamahiriya</option>

                        <option value="Liechtenstein">Liechtenstein</option>

                        <option value="Lithuania">Lithuania</option>

                        <option value="Luxembourg">Luxembourg</option>

                        <option value="Macao">Macao</option>

                        <option value="Macedonia, the Former Yugoslav Republic of">Macedonia, the Former Yugoslav Republic of</option>

                        <option value="Madagascar">Madagascar</option>

                        <option value="Malawi">Malawi</option>

                        <option value="Malaysia">Malaysia</option>

                        <option value="Maldives">Maldives</option>

                        <option value="Mali">Mali</option>

                        <option value="Malta">Malta</option>

                        <option value="Marshall Islands">Marshall Islands</option>

                        <option value="Martinique">Martinique</option>

                        <option value="Mauritania">Mauritania</option>

                        <option value="Mauritius">Mauritius</option>

                        <option value="Mayotte">Mayotte</option>

                        <option value="Mexico">Mexico</option>

                        <option value="Micronesia, Federated States of">Micronesia, Federated States of</option>

                        <option value="Moldova, Republic of">Moldova, Republic of</option>

                        <option value="Monaco">Monaco</option>

                        <option value="Mongolia">Mongolia</option>

                        <option value="Montenegro">Montenegro</option>

                        <option value="Montserrat">Montserrat</option>

                        <option value="Morocco">Morocco</option>

                        <option value="Mozambique">Mozambique</option>

                        <option value="Myanmar">Myanmar</option>

                        <option value="Namibia">Namibia</option>

                        <option value="Nauru">Nauru</option>

                        <option value="Nepal">Nepal</option>

                        <option value="Netherlands">Netherlands</option>

                        <option value="Netherlands Antilles">Netherlands Antilles</option>

                        <option value="New Caledonia">New Caledonia</option>

                        <option value="New Zealand">New Zealand</option>

                        <option value="Nicaragua">Nicaragua</option>

                        <option value="Niger">Niger</option>

                        <option value="Nigeria">Nigeria</option>

                        <option value="Niue">Niue</option>

                        <option value="Norfolk Island">Norfolk Island</option>

                        <option value="Northern Mariana Islands">Northern Mariana Islands</option>

                        <option value="Norway">Norway</option>

                        <option value="Oman">Oman</option>

                        <option value="Pakistan">Pakistan</option>

                        <option value="Palau">Palau</option>

                        <option value="Palestinian Territory, Occupied">Palestinian Territory, Occupied</option>

                        <option value="Panama">Panama</option>

                        <option value="Papua New Guinea">Papua New Guinea</option>

                        <option value="Paraguay">Paraguay</option>

                        <option value="Peru">Peru</option>

                        <option value="Philippines">Philippines</option>

                        <option value="Pitcairn">Pitcairn</option>

                        <option value="Poland">Poland</option>

                        <option value="Portugal">Portugal</option>

                        <option value="Puerto Rico">Puerto Rico</option>

                        <option value="Qatar">Qatar</option>

                        <option value="Reunion">Reunion</option>

                        <option value="Romania">Romania</option>

                        <option value="Russian Federation">Russian Federation</option>

                        <option value="Rwanda">Rwanda</option>

                        <option value="Saint Barthelemy">Saint Barthelemy</option>

                        <option value="Saint Helena">Saint Helena</option>

                        <option value="Saint Kitts and Nevis">Saint Kitts and Nevis</option>

                        <option value="Saint Lucia">Saint Lucia</option>

                        <option value="Saint Martin">Saint Martin</option>

                        <option value="Saint Pierre and Miquelon">Saint Pierre and Miquelon</option>

                        <option value="Saint Vincent and the Grenadines">Saint Vincent and the Grenadines</option>

                        <option value="Samoa">Samoa</option>

                        <option value="San Marino">San Marino</option>

                        <option value="Sao Tome and Principe">Sao Tome and Principe</option>

                        <option value="Saudi Arabia">Saudi Arabia</option>

                        <option value="Senegal">Senegal</option>

                        <option value="Serbia">Serbia</option>

                        <option value="Serbia and Montenegro">Serbia and Montenegro</option>

                        <option value="Seychelles">Seychelles</option>

                        <option value="Sierra Leone">Sierra Leone</option>

                        <option value="Singapore">Singapore</option>

                        <option value="Sint Maarten">Sint Maarten</option>

                        <option value="Slovakia">Slovakia</option>

                        <option value="Slovenia">Slovenia</option>

                        <option value="Solomon Islands">Solomon Islands</option>

                        <option value="Somalia">Somalia</option>

                        <option value="South Africa">South Africa</option>

                        <option value="South Georgia and the South Sandwich Islands">South Georgia and the South Sandwich Islands</option>

                        <option value="South Sudan">South Sudan</option>

                        <option value="Spain">Spain</option>

                        <option value="Sri Lanka">Sri Lanka</option>

                        <option value="Sudan">Sudan</option>

                        <option value="Suriname">Suriname</option>

                        <option value="Svalbard and Jan Mayen">Svalbard and Jan Mayen</option>

                        <option value="Swaziland">Swaziland</option>

                        <option selected value="Sweden">Sweden</option>

                        <option value="Switzerland">Switzerland</option>

                        <option value="Syrian Arab Republic">Syrian Arab Republic</option>

                        <option value="Taiwan, Province of China">Taiwan, Province of China</option>

                        <option value="Tajikistan">Tajikistan</option>

                        <option value="Tanzania, United Republic of">Tanzania, United Republic of</option>

                        <option value="Thailand">Thailand</option>

                        <option value="Timor-Leste">Timor-Leste</option>

                        <option value="Togo">Togo</option>

                        <option value="Tokelau">Tokelau</option>

                        <option value="Tonga">Tonga</option>

                        <option value="Trinidad and Tobago">Trinidad and Tobago</option>

                        <option value="Tunisia">Tunisia</option>

                        <option value="Turkey">Turkey</option>

                        <option value="Turkmenistan">Turkmenistan</option>

                        <option value="Turks and Caicos Islands">Turks and Caicos Islands</option>

                        <option value="Tuvalu">Tuvalu</option>

                        <option value="Uganda">Uganda</option>

                        <option value="Ukraine">Ukraine</option>

                        <option value="United Arab Emirates">United Arab Emirates</option>

                        <option value="United Kingdom">United Kingdom</option>

                        <option value="United States">United States</option>

                        <option value="United States Minor Outlying Islands">United States Minor Outlying Islands</option>

                        <option value="Uruguay">Uruguay</option>

                        <option value="Uzbekistan">Uzbekistan</option>

                        <option value="Vanuatu">Vanuatu</option>

                        <option value="Venezuela">Venezuela</option>

                        <option value="Viet Nam">Viet Nam</option>

                        <option value="Virgin Islands, British">Virgin Islands, British</option>

                        <option value="Virgin Islands, U.s.">Virgin Islands, U.s.</option>

                        <option value="Wallis and Futuna">Wallis and Futuna</option>

                        <option value="Western Sahara">Western Sahara</option>

                        <option value="Yemen">Yemen</option>

                        <option value="Zambia">Zambia</option>

                        <option value="Zimbabwe">Zimbabwe</option>

                    </select>

                </div>

                <div class="col-lg-12">

                    <div class="form-group file-area w-100">

                        <div class="d-flex justify-content-between">

                            <label for="images" class="f-16 w-600 mt-2">Documents</label>

                        </div>

                        <input class="sign-input w-100 " type="file" name="files[]" id="cv" accept="application/pdf" multiple />

                        <div class="file-dummy sign-input  ">

                            <div class="success "></div>

                            <div class="file-icon"><i style="font-size: 28px; color: #5c636a" class="fa-solid fa-cloud-arrow-up "></i></div>

                            <div class="default ">Here you can upload several documents <small>(Interview Templates, Documents or CV)</small></div>

                        </div>

                    </div>

                </div>



                <div class="col-lg-12 mt-5">

                    <h1 class="f-20">Billing</h1>

                </div>

                <div class="col-lg-12">

                    <p class="f-16 mb-0 pb-0 w-600 mt-2"><span class="text-danger">*</span> Reference (Invoice Recepient)</p>

                    <input type="text" required name="pref" class="sign-input w-100 mb-3" placeholder="Reference (Invoice Recepient)">

                </div>

                <div class="col-lg-12">

                    <p class="f-16 mb-0 pb-0 w-600 mt-2"><span class="text-danger">*</span> Reference</p>

                    <input type="text" required name="ref" class="sign-input w-100 mb-3" placeholder="Reference">

                </div>

                <div class="col-lg-12">

                    <p class="f-16 mb-0 pb-0 w-600 mt-2">Invoice Comment (Visible on the invoice)</p>

                    <textarea class="w-100 sign-textarea" placeholder="Invoice Comment (Visible on the invoice)" name="comment" id="" rows="3"></textarea>

                </div>



                <div class="col-lg-12 mt-5">

                    <h1 class="f-20">Note</h1>

                </div>

                <div class="col-lg-12">

                    <textarea placeholder="Here you can create a note about the order that is visible to both you and us. Please note that this note will not be visible to the individual." class="w-100 sign-textarea" name="note" id="" rows="3"></textarea>

                </div>



                <div class="col-lg-12 mt-2 mb-5 w-100 d-flex justify-content-end">

                    <button type="submit" name="order" class="btn-fill w-25 "><a>Send Order</a></button>

                </div>

            </form>

        </div>

    </div>

</div>





<?php



include_once('includes/footer.php');



?>



<script>

    $('#interview').on('change', function() {

        if ($(this).val() == 2 || $(this).val() == 4 || $(this).val() == 26) {

            $('#place').removeClass('d-none')

            $("select[name='place']").prop("disabled", false)

        } else {

            $('#place').addClass('d-none')

            $("select[name='place']").prop("disabled", true)

        }



        if ($(this).val() == 10 || $(this).val() == 12 || $(this).val() == 13) {

            $('#country').removeClass('d-none')

            $("select[name='country']").prop("disabled", false)

        } else {

            $('#country').addClass('d-none')

            $("select[name='country']").prop("disabled", true)

        }

    })

</script>