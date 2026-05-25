<?php
require_once 'includes/config.php';

// Limpia todos los datos de la sesion del cliente y la destruye,
// luego redirige al login
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
