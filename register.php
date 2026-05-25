<?php
require_once 'includes/config.php';

// Si el cliente ya tiene sesion activa, no necesita registrarse
if (isLoggedIn()) {
  header('Location: index.php');
  exit;
}

$error = '';
$success = '';

// Procesa el formulario de registro cuando se envia por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nombre   = trim($_POST['nombre'] ?? '');
  $email    = trim($_POST['email'] ?? '');
  $telefono = trim($_POST['telefono'] ?? '');
  $direccion = trim($_POST['direccion'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirm  = $_POST['confirm'] ?? '';

  // Validaciones en cadena: campos obligatorios, formato de email,
  // longitud minima de contrasena y confirmacion correcta
  if (!$nombre || !$email || !$password) {
    $error = 'Nombre, email y contraseña son obligatorios.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Email inválido.';
  } elseif (strlen($password) < 6) {
    $error = 'La contraseña debe tener al menos 6 caracteres.';
  } elseif ($password !== $confirm) {
    $error = 'Las contraseñas no coinciden.';
  } else {
    $db = getDB();

    // Verifica que el email no este ya registrado antes de insertar
    $check = $db->prepare("SELECT id_cliente FROM Clientes WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
      $error = 'Este email ya está registrado.';
    } else {
      // Encripta la contrasena con bcrypt antes de guardarla en la BD
      $hash = password_hash($password, PASSWORD_BCRYPT);
      $stmt = $db->prepare("INSERT INTO Clientes (nombre, email, password, telefono, direccion) VALUES (?,?,?,?,?)");
      $stmt->bind_param("sssss", $nombre, $email, $hash, $telefono, $direccion);
      if ($stmt->execute()) {
        $success = '¡Cuenta creada! Ahora puedes iniciar sesión.';
      } else {
        $error = 'Error al crear la cuenta. Inténtalo de nuevo.';
      }
    }
    $db->close();
  }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UrbanFood – Crear Cuenta</title>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box
    }

    :root {
      --red: #FF3008;
      --red-dark: #D4290A;
      --bg: #0D0D0D;
      --card: #1A1A1A;
      --border: #2A2A2A;
      --text: #F5F5F5;
      --muted: #888
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
      position: relative;
    }

    body::before {
      content: '';
      position: fixed;
      top: -200px;
      right: -200px;
      width: 600px;
      height: 600px;
      background: radial-gradient(circle, rgba(255, 48, 8, 0.15) 0%, transparent 70%);
      pointer-events: none
    }

    .login-wrapper {
      width: 100%;
      max-width: 460px;
      z-index: 1
    }

    .brand {
      text-align: center;
      margin-bottom: 2rem
    }

    .brand-logo {
      font-family: 'Sora', sans-serif;
      font-size: 2.2rem;
      font-weight: 800;
      color: var(--red);
      letter-spacing: -1px
    }

    .brand-logo span {
      color: var(--text)
    }

    .card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 2.5rem;
      box-shadow: 0 20px 60px rgba(0, 0, 0, .5)
    }

    .card h2 {
      font-family: 'Sora', sans-serif;
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: .4rem
    }

    .card .subtitle {
      color: var(--muted);
      font-size: .88rem;
      margin-bottom: 2rem
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem
    }

    .form-group {
      margin-bottom: 1.2rem
    }

    .form-group label {
      display: block;
      font-size: .82rem;
      font-weight: 500;
      color: var(--muted);
      margin-bottom: .5rem;
      text-transform: uppercase;
      letter-spacing: .5px
    }

    .form-group input {
      width: 100%;
      background: #111;
      border: 1px solid var(--border);
      color: var(--text);
      padding: .9rem 1.1rem;
      border-radius: 12px;
      font-size: .95rem;
      font-family: 'Inter', sans-serif;
      transition: border-color .2s, box-shadow .2s;
      outline: none
    }

    .form-group input:focus {
      border-color: var(--red);
      box-shadow: 0 0 0 3px rgba(255, 48, 8, .15)
    }

    .btn-primary {
      width: 100%;
      background: var(--red);
      color: #fff;
      border: none;
      padding: 1rem;
      border-radius: 12px;
      font-size: 1rem;
      font-family: 'Sora', sans-serif;
      font-weight: 700;
      cursor: pointer;
      transition: all .2s;
      letter-spacing: .3px
    }

    .btn-primary:hover {
      background: var(--red-dark);
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(255, 48, 8, .4)
    }

    .register-link {
      text-align: center;
      color: var(--muted);
      font-size: .88rem;
      margin-top: 1.5rem
    }

    .register-link a {
      color: var(--red);
      text-decoration: none;
      font-weight: 600
    }

    .error-msg {
      background: rgba(255, 48, 8, .12);
      border: 1px solid rgba(255, 48, 8, .3);
      color: #ff6b55;
      padding: .85rem 1rem;
      border-radius: 10px;
      font-size: .88rem;
      margin-bottom: 1.2rem
    }

    .success-msg {
      background: rgba(34, 197, 94, .12);
      border: 1px solid rgba(34, 197, 94, .3);
      color: #4ade80;
      padding: .85rem 1rem;
      border-radius: 10px;
      font-size: .88rem;
      margin-bottom: 1.2rem
    }

    .success-msg a {
      color: #4ade80;
      font-weight: 700
    }
  </style>
</head>

<body>
  <div class="login-wrapper">
    <div class="brand">
      <div class="brand-logo">Urban<span>Food</span></div>
    </div>
    <div class="card">
      <h2>Crear cuenta</h2>
      <p class="subtitle">Únete y pide tu comida favorita</p>

      <?php if ($error): ?>
        <div class="error-msg"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="success-msg"><?= $success ?> <a href="login.php">Inicia sesión →</a></div>
      <?php endif; ?>

      <?php if (!$success): ?>
        <form method="POST">
          <div class="form-row">
            <div class="form-group">
              <label>Nombre completo</label>
              <input type="text" name="nombre" placeholder="Tu nombre" required value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label>Teléfono</label>
              <input type="text" name="telefono" placeholder="7000-0000" value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>">
            </div>
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="tucorreo@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Dirección de entrega</label>
            <input type="text" name="direccion" placeholder="Tu dirección" value="<?= htmlspecialchars($_POST['direccion'] ?? '') ?>">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Contraseña</label>
              <input type="password" name="password" placeholder="Mín. 6 caracteres" required>
            </div>
            <div class="form-group">
              <label>Confirmar</label>
              <input type="password" name="confirm" placeholder="Repetir contraseña" required>
            </div>
          </div>
          <button type="submit" class="btn-primary">Crear mi cuenta</button>
        </form>
      <?php endif; ?>

      <div class="register-link">
        ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
      </div>
    </div>
  </div>
</body>

</html>