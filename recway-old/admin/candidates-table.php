<div class="data-table ">
    <div class="row">
        <input class="sign-input w-25 ms-2 mb-1 mt-0" type="text" placeholder="Filter Invoice Date" name="stats_date" id="invoice_date_filter">
    </div>
    <div class="candidates-table">
        <form action="" method="post" id="d-form">
            <table id="dataTable" data-table="candidate" class="table display nowrap" style="width:100%;">
                <thead>
                    <tr>
                        <th><input id="delete-all" class="d-check" type="checkbox" name="all"></th>
                        <th>#</th>
                        <th>Action</th>
                        <th>Order ID</th>
                        <th>Place</th>
                        <th>VASC ID</th>
                        <th>Name</th>
                        <th>SSN</th>
                        <th>Company</th>
                        <th>Customer</th>
                        <th>Staff</th>
                        <th>Status</th>
                        <th>Invoice Sent</th>
                        <th>Economy</th>
                        <th>Criminal Record</th>
                        <th>Background Check Date</th>
                        <th>Invoice Date</th>
                        <th>Order Created</th>
                        <?php if ((isset($_GET['service']) && $_GET['service'] == INTERVIEW_ID) || ! isset($_GET['service'])) : ?>

                            <th>Interview Date</th>
                        <?php endif; ?>

                        <th>Delivery Date</th>
                        <th>Service Type</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($candidates)) : ?>

                        <?php foreach ($candidates as $key => $candidate) : ?>

                            <?php

                            $query = 'SELECT * FROM customers WHERE id = ?';

                            $stmt = $conn->prepare($query);

                            $stmt->execute([$candidate->cus_id]);

                            $customer = $stmt->fetch();

                            $query = 'SELECT * FROM interviews WHERE id = ?';

                            $stmt = $conn->prepare($query);

                            $stmt->execute([$candidate->interview_id]);

                            $interview = $stmt->fetch();

                            $query = 'SELECT * FROM places WHERE id = ?';

                            $stmt = $conn->prepare($query);

                            $stmt->execute([$candidate->place]);

                            $place = $stmt->fetch();

                            ?>

                            <?php

                            if ($candidate->staff_id != 0) {

                                $query = 'SELECT * FROM staff WHERE id = ?';

                                $stmt = $conn->prepare($query);

                                $stmt->execute([$candidate->staff_id]);

                                $staff = $stmt->fetch();

                            } else {

                                $staff = "";

                            }

                            ?>

                            <tr>
                                <td><input type="checkbox" class="delete-candidate d-check" value="<?php echo $candidate->id ?>" name="delete[]"></td>
                                <td><?php echo $key + 1 ?></td>
                                <td class="text-center">
                                    <div class="dropdown profile-dropdown ">
                                        <button class=" " type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu shadow-sm" aria-labelledby="dropdownMenuButton1">
                                            <li class="mb-2"><a class="dropdown-item f-14" href="invoice.php?id=<?php echo $candidate->id ?>"><i class="bi bi-eye me-2 f-16 w-600"></i>View</a></li>
                                            <li class="mb-2"><a class="dropdown-item f-14" href="update-candidate.php?id=<?php echo $candidate->id ?>"><i class="bi bi-pencil-square w-600 me-2 f-16"></i>
                                                    Edit</a></li>
                                            <li class="mb-2"><a class="dropdown-item f-14" href="change-staff.php?id=<?php echo $candidate->id ?>"><i class="bi bi-people me-2 f-16 w-600"></i>Change Staff</a>
                                            </li>
                                            <li class="mb-2"><a class="dropdown-item f-14" href="update-status.php?id=<?php echo $candidate->id ?>"><i class="bi bi-pen me-2 f-16 w-600"></i>Change Status</a>
                                            </li>
                                            <li class="mb-2"><a class="dropdown-item f-14" href="comment.php?id=<?php echo $candidate->id ?>"><i class="bi bi-chat-quote me-2 f-16 w-600"></i>Comment</a>
                                            </li>
                                            <li class="mb-2"><a class="dropdown-item f-14" href="report.php?id=<?php echo $candidate->id ?>"><i class="bi bi-file-bar-graph me-2 f-16 w-600"></i>Generate Report</a>
                                            </li>
                                        </ul>
                                    </div>

                                </td>
                                <td><?php echo $candidate->order_id ?></td>
                                <td><?php echo ! empty($place) ? $place->name : "Null" ?></td>
                                <td><?php echo $candidate->vasc_id ?></td>
                                <td><a style="text-decoration: none; color: var(--black)" href="invoice.php?id=<?php echo $candidate->id ?><?php echo isset($_GET['status']) ? '&status=' . $_GET['status'] : '' ?>"><?php echo $candidate->name . " " . $candidate->surname ?></a></td>
                                <td><?php echo $candidate->security ?></td>
                                <td><a href="update-customer.php?id=<?php echo $customer->id ?>"><?php echo $customer->company ?></a></td>
                                <td><a href="update-customer.php?id=<?php echo $customer->id ?>"><?php echo $customer->name ?></a></td>
                                <td><?php echo ! empty($staff) ? $staff->name : "Not Assigned" ?></td>
                                <?php $status = getStatusById($candidate->status) ?>

                                <td><span style="background-color: <?php echo $status->color ?>; padding: 5px; border-radius: 20px; color: white;font-size: 12px"><?php echo $status->status ?></span></td>
                                <!--                            <td class="text-center"><input class="reported" --><?php //echo $candidate->reported == 1 ? 'checked' : ''

                                                                                                                    ?><!-- data-id="--><?php //echo $candidate->id

                                                                                                                                        ?><!--" type="checkbox" name="reported"></td>-->
                                <td class="text-center"><input class="invoice_sent" <?php echo $candidate->invoice_sent == 1 ? 'checked' : '' ?> data-id="<?php echo $candidate->id ?>" type="checkbox" name="invoice_sent"></td>
                                <td class="">
                                    <div class="d-flex justify-content-center">
                                        <label class="me-2">
                                            <input class="economy-radio" <?php echo $candidate->economy == 0 ? 'checked' : '' ?> type="radio" name="<?php echo $candidate->order_id ?>">
                                            <span class="custom-economy-radio uncheck_economy" data-id="<?php echo $candidate->id ?>"></span>
                                        </label>
                                        <label>
                                            <input class="economy2-radio" <?php echo $candidate->economy == 1 ? 'checked' : '' ?> type="radio" name="<?php echo $candidate->order_id ?>">
                                            <span class="custom-economy2-radio check_economy" data-id="<?php echo $candidate->id ?>"></span>
                                        </label>
                                    </div>
                                </td>
                                <td class="">
                                    <div class="d-flex justify-content-center">
                                        <label class="me-2">
                                            <input class="economy-radio" <?php echo $candidate->criminal_record == 0 ? 'checked' : '' ?> type="radio" name="<?php echo $candidate->order_id ?>-criminal">
                                            <span class="custom-economy-radio uncheck_criminal" data-id="<?php echo $candidate->id ?>"></span>
                                        </label>
                                        <label>
                                            <input class="economy2-radio" <?php echo $candidate->criminal_record == 1 ? 'checked' : '' ?> type="radio" name="<?php echo $candidate->order_id ?>-criminal">
                                            <span class="custom-economy2-radio check_criminal" data-id="<?php echo $candidate->id ?>"></span>
                                        </label>
                                    </div>
                                </td>
                                <td class="background_check_date"><?php echo ! empty($candidate->background_check_date) ? $candidate->background_check_date : 'Null' ?></td>
                                <td class="invoice_date"><?php echo ! empty($candidate->invoice_date) ? $candidate->invoice_date : 'Null' ?></td>
                                <td><?php echo $candidate->created ?></td>
                                <?php if ((isset($_GET['service']) && $_GET['service'] == INTERVIEW_ID) || ! isset($_GET['service'])) : ?>

                                    <td><?php echo ! empty($candidate->booked) ? $candidate->booked : "Null" ?></td>
                                <?php endif; ?>

                                <td><?php echo ! empty($candidate->delivery_date) ? $candidate->delivery_date : "Null" ?></td>
                                <td><?php echo $interview->title ?></td>
                            </tr>
                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>
        </form>
    </div>
</div>