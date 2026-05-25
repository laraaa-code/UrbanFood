<?php
require_once '../includes/config.php';
unset($_SESSION['repartidor_id'], $_SESSION['repartidor_nombre']);
header('Location: login.php');
exit;
?>
