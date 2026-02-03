<?php

include_once('../includes/functions.php');

unset($_SESSION['staff']);
if (isset($_SESSION['previous_page'])) {
    unset($_SESSION['previous_page']);
}

redirect('signin.php');
