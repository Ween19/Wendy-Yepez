<?php
require_once __DIR__ . '/auth.php';

if ($_SESSION['id_rol'] != 1) {
    header("Location: /HABITA/dashboard.php");
    exit();
}
?>