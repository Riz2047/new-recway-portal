<?php

require_once "functions.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Get the uploaded file data

    $file = $_FILES['file'];

    $id = $_POST['id'];

    // Define the upload directory

    $upload_dir = '../report-uploads';

    // Generate a unique file name

    $filename = uniqid() . '_' . basename($file['name']);

    // Move the uploaded file to the upload directory

    if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {

        update("candidates", ["report" => $filename], "id", $id);

        echo 'File uploaded successfully!';

    } else {

        echo 'Error uploading file: Unable to move file to upload directory.';

    }

} else {

    echo 'Error uploading file: Invalid request method.';

}

?>

