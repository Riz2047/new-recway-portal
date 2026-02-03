<?php

require_once "includes/functions.php";



if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Get the uploaded file data

    $file = $_FILES['file'];

    $id = $_POST['id'];



    // Define the upload directory

    $upload_dir = 'security-report-uploads/';



    // Generate a unique file name

//    $filename = uniqid() . '_' . basename($file['name']);

    $filename = $_POST['filename'] . ".pdf";



    if(file_exists($upload_dir . $filename)) {

        unlink($upload_dir . $filename);

    }



    // Move the uploaded file to the upload directory

    if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {

        update("candidates", ["basic_investigation_result" => $filename], "id", $id);

        echo 'File uploaded successfully!';

    } else {

        echo 'Error uploading file: Unable to move file to upload directory.';

    }

} else {

    echo 'Error uploading file: Invalid request method.';

}

?>

