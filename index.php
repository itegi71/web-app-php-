<?php
require_once 'includes/functions.php';

if (isLoggedIn()) {
    header('Location: pages/home.php');
} else {
    header('Location: pages/login.php');
}
?>