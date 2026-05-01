<?php
$activeLink = "statuses";
include_once('includes/header.php');
$query = "SELECT * FROM interviews WHERE service_cat_id = {$_GET['serv_cat']}";
$stmt = $conn->prepare($query);
$stmt->execute();
$services = $stmt->fetchAll();
if (isset($_POST['add'])) {
    $status = $_POST['status'];
    $status_detail = $_POST['status_detail'];
    $icon = $_POST['icon'];
    $variable = $_POST['variable'];
    $status_sv = $_POST['status_sv'];
    $color = $_POST['color'];
    $status_type = $_GET['serv_cat'];
    //    $email_to = isset($_POST['email_to']) && !empty($_POST['email_to']) ? implode(",", $_POST['email_to']) : 0;
    $services_selected = $_POST['services'] ?? null;
    $status_message_ids = $_POST['status_message_ids'] ?? [];
    $status_messages = $_POST['status_messages'] ?? null;
    $status_message_cols = $_POST['status_msg_cols'] ?? null;
    $message = $_POST['message'];
    $msg_col = $_POST['msg_col'];
    $query = 'INSERT INTO statuses(variable, status, color, status_detail, status_sv,status_icon,status_type) VALUES(?,?,?,?,?,?,?)';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$variable, $status, $color, $status_detail, $status_sv, $icon, $status_type]);
    if (! empty($res)) {
        $status_id = $conn->lastInsertId();
        $query = 'INSERT INTO allowed_emails (cus_id, status_id, allowed) SELECT id AS cus_id, ? AS status_id, 1 AS allowed FROM customers';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$status_id]);
        if (! empty($status_messages)) {
            foreach ($status_messages as $key => $message) {
                $query = "ALTER TABLE messages ADD COLUMN {$status_message_cols[$key]} TEXT";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $query = "UPDATE messages SET {$status_message_cols[$key]} = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$message]);
                $query = 'INSERT INTO status_services(service_id, status_id, msg_col) VALUES(?,?,?)';
                $stmt = $conn->prepare($query);
                $res = $stmt->execute([$status_message_ids[$key], $status_id, $status_message_cols[$key]]);
            }
        }
        $services_included = array_filter($services, function ($obj) use ($status_message_ids, $services_selected) {
            return ! in_array($obj->id, $status_message_ids) && in_array($obj->id, $services_selected);
        });
        $query = "ALTER TABLE messages ADD COLUMN {$msg_col} TEXT";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $query = "UPDATE messages SET {$msg_col} = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$message]);
        if (! empty($services_included)) {
            foreach ($services_included as $service) {
                $query = 'INSERT INTO status_services(service_id, status_id, msg_col) VALUES(?,?,?)';
                $stmt = $conn->prepare($query);
                $res = $stmt->execute([$service->id, $status_id, $msg_col]);
            }
        }
        flash("statusAdded", "Status added successfully!");
    } else {
        flash("statusAdded", "Could not add status!", "errorMsg");
    }
}
?>
<?php flash("statusAdded"); ?>
<div class="mx-lg-4 main-content">
	<div class="container">
		<div class="row ">
			<div class="col-lg-12">
				<div class="table-section">
					<div class="d-flex justify-content-between align-items-center mb-3">
						<h1 class="main-heading">Add Status</h1>
					</div>
					<form class="update-form" method="post">
						<div class="row mb-3">
							<div class="col-lg-6 mb-3">
								<label class="form-label" for="status">Status</label>
								<input type="text" required name="status" class="form-control" id="status">
							</div>
							<div class="col-md-6 mb-3">
								<label class="form-label" for="status_detail">Status Detail</label>
								<input type="text" required name="status_detail" class="form-control" id="status_detail">
							</div>
							<div class="col-md-6 mb-3">
								<label class="form-label" for="status_sv">Translated Status (swedish)</label>
								<input type="text" required name="status_sv" class="form-control" id="status_sv">
							</div>
							<div class="col-md-6 mb-3">
								<label class="form-label" for="variable">Variable</label>
								<input type="text" required name="variable" class="form-control cols" id="variable">
							</div>
							<div class="col-md-12 mb-3">
								<label class="form-label" for="color">Color</label>
								<br>
								<input type="color" required name="color" class="" id="color">
							</div>
							<div class="col-md-12 mb-3">
								<label class="form-label" for="status_detail">Icon</label>
								<input type="text" required autocomplete="off" name="icon" class="form-control iconpicker" id="icon" aria-label="Icon Picker" aria-describedby="basic-addon1">
							</div>
							<div class="row">
								<?php if (! empty($services)) : ?>
									<label class="form-label">Services</label>
									<?php foreach ($services as $service) : ?>
										<div class="col-lg-12 d-flex flex-column mb-3">
											<input checked type="checkbox" id="<?php echo $service->title . $service->id ?>" value="<?php echo $service->id ?>" name="services[]" class="mb-3 form-check-input">
											<label class="form-check-label" for="<?php echo $service->title . $service->id ?>">
												<?php echo $service->title ?>
												<a href="#" data-id="<?php echo $service->id ?>" class="ms-2 service-message"><i class="bi bi-chat"></i></a>
											</label>
										</div>
									<?php endforeach; ?>
								<?php endif; ?>
							</div>
							<div class="col-lg-12 mb-3">
								<label class="form-label">Message <small>(for all services)</small></label>
								<textarea required name="message" class="sign-textarea w-100"></textarea>
							</div>
							<div class="col-lg-12 mb-3">
								<label class="form-label">Message Column</label>
								<input type="text" required name="msg_col" class="form-control cols msgCols">
							</div>
						</div>
						<div class="d-flex justify-content-end">
							<button type="submit" name="add" class="btn-primary bg-primary">Save</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
</div>
</div>
<?php
include_once('includes/footer.php');
?>
<script>
	$(document).ready(function() {
		if (localStorage) {
			var posReader = localStorage["posStorage"];
			if (posReader) {
				$('.layout').scrollTop(posReader);
				localStorage.removeItem("posStorage");
			}
		}
		$('.layout').scroll(function(e) {
			localStorage["posStorage"] = $(this).scrollTop();
		})
		$("#success-alert").fadeTo(2000, 500).slideUp(500, function() {
			$("#success-alert").slideUp(500);
		});
	})
	$('.service-message').click(function(e) {
		e.preventDefault()
		var id = $(this).attr("data-id");
		$(this).closest('label').after('<div class="col-lg-12 ps-0"> <p class="f-14 mb-0 pb-0 w-500">Message</p> <input type="hidden" name="status_message_ids[]" value="' + id + '"> <textarea required name="status_messages[]" class="sign-textarea w-100 mb-3" placeholder="Enter message "></textarea> </div><div class="col-lg-12 ps-0"><p class="f-14 mb-0 pb-0 w-500">Message Column</p><input type="text" required name="status_msg_cols[]" class="sign-input w-100 mb-3 cols msgCols" placeholder="Enter message column "></div>')
		$(this).remove()
	})
	$(document).on('input', '.cols', function() {
		var inputValue = $(this).val();
		var regex = /^[a-zA-Z][a-zA-Z0-9_]*$/;
		if (!regex.test(inputValue)) {
			$(this).val(inputValue.substring(0, inputValue.length - 1));
		}
	})
	$(document).on('keyup', 'input[name="variable"]', function() {
		var that = $(this)
		that.parents().eq(0).find("small").remove()
		$.ajax({
			url: "../includes/ajax.php",
			method: "post",
			data: {
				statusVariable: true,
				variable: $(this).val()
			},
			success: function(response) {
				if (response == "1") {
					that.parents().eq(0).find("small").remove()
					that.after("<small class='text-danger var-error'>Variable already exists</small>");
					$("button[name='add']").prop('disabled', true)
				} else {
					that.parents().eq(0).find("small").remove()
					if ($("form").find(".var-error").length === 0) {
						$("button[name='add']").prop('disabled', false)
					}
				}
			}
		})
	})
	$(document).on('keyup', '.msgCols', function() {
		var that = $(this)
		that.parents().eq(0).find("small").remove()
		$.ajax({
			url: "../includes/ajax.php",
			method: "post",
			data: {
				msgColVariable: true,
				variable: $(this).val()
			},
			success: function(response) {
				if (response == "1") {
					that.parents().eq(0).find("small").remove()
					that.after("<small class='text-danger var-error'>Message column already exists</small>");
					$("button[name='add']").prop('disabled', true)
				} else {
					that.parents().eq(0).find("small").remove()
					if ($("form").find(".var-error").length === 0) {
						$("button[name='add']").prop('disabled', false)
					}
				}
			}
		})
	})
	$(document).ready(function() {
		$('form.update-form').on('submit', function(e) {
			// Check if at least one checkbox is selected
			if ($('input[name="services[]"]:checked').length === 0) {
				e.preventDefault();
				alert("Please select at least one service before saving.");
			}
		});
	});
</script>