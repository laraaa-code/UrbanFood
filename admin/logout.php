<?php
require_once '../includes/config.php';

// Elimina unicamente la sesion de admin sin afectar otras variables de sesion,
// luego redirige al login del panel
unset($_SESSION['is_admin']);
header('Location: login.php');
exit;
