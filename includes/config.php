<?php
// Constantes de conexion a la base de datos MySQL
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'larissa2008');
define('DB_NAME', 'UrbanFoodDB');

// Clave secreta para acceder al panel de administracion
define('ADMIN_KEY', 'urbanfood2024');

// Crea y retorna una conexion activa a la base de datos.
// Termina la ejecucion si no puede conectarse.
// Ejemplo: $db = getDB(); $db->query("SELECT ...");
function getDB()
{
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");
    return $conn;
}

// Inicia la sesion de PHP para manejar datos del usuario entre paginas
session_start();

// Verifica si hay un cliente con sesion activa
function isLoggedIn()
{
    return isset($_SESSION['cliente_id']);
}

// Verifica si el usuario actual tiene rol de administrador
function isAdmin()
{
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

// Redirige al login si el cliente no tiene sesion activa.
// Se llama al inicio de cada pagina que requiere autenticacion de cliente.
function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Redirige al login del admin si el usuario no tiene permisos de administrador.
// Se llama al inicio de cada pagina del panel admin.
function requireAdmin()
{
    if (!isAdmin()) {
        header('Location: admin/login.php');
        exit;
    }
}
