<?php
require_once 'includes/config.php';

// Si ya hay sesion activa, redirige directo al inicio
if (isLoggedIn()) {
  header('Location: index.php');
  exit;
}

$error = '';

// Procesa el formulario de login cuando se envia por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($email && $password) {
    $db = getDB();

    // Busca el cliente por email para verificar su contrasena
    $stmt = $db->prepare("SELECT id_cliente, nombre, password FROM Clientes WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $cliente = $result->fetch_assoc();

    // Compara la contrasena ingresada con el hash almacenado en la BD
    if ($cliente && password_verify($password, $cliente['password'])) {
      // Guarda los datos del cliente en sesion para usarlos en otras paginas
      $_SESSION['cliente_id'] = $cliente['id_cliente'];
      $_SESSION['cliente_nombre'] = $cliente['nombre'];
      header('Location: index.php');
      exit;
    } else {
      $error = 'Email o contraseña incorrectos.';
    }
    $db->close();
  } else {
    $error = 'Por favor completa todos los campos.';
  }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UrbanFood – Iniciar Sesión</title>
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
      --muted: #888;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
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

    body::after {
      content: '';
      position: fixed;
      bottom: -200px;
      left: -200px;
      width: 500px;
      height: 500px;
      background: radial-gradient(circle, rgba(255, 48, 8, 0.08) 0%, transparent 70%);
      pointer-events: none
    }

    .login-wrapper {
      width: 100%;
      max-width: 600px;
      padding: 2rem;
      z-index: 1
    }

    .brand {
      text-align: center;
      margin-bottom: 2.5rem
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

    .brand p {
      color: var(--muted);
      font-size: .9rem;
      margin-top: .3rem
    }

    .card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 2.5rem;
      box-shadow: 0 20px 60px rgba(0, 0, 0, .5);
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
      margin-top: .5rem;
      letter-spacing: .3px
    }

    .btn-primary:hover {
      background: var(--red-dark);
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(255, 48, 8, .4)
    }

    .btn-primary:active {
      transform: translateY(0)
    }

    .divider {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin: 1.5rem 0;
      color: var(--muted);
      font-size: .82rem
    }

    .divider::before,
    .divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--border)
    }

    .register-link {
      text-align: center;
      color: var(--muted);
      font-size: .88rem
    }

    .register-link a {
      color: var(--red);
      text-decoration: none;
      font-weight: 600
    }

    .register-link a:hover {
      text-decoration: underline
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

    .admin-hint {
      text-align: center;
      margin-top: 1.5rem
    }

    .admin-hint a {
      color: var(--muted);
      font-size: .8rem;
      text-decoration: none
    }

    .admin-hint a:hover {
      color: var(--text)
    }

    .manual-section{
  margin-top:1.5rem;
  display:flex;
  gap:1rem;
  justify-content:center;
  flex-wrap:wrap;
}

.manual-btn{
  text-decoration:none;
  background:#111;
  border:1px solid var(--border);
  color:var(--text);
  padding:.8rem 1rem;
  border-radius:12px;
  font-size:.85rem;
  transition:.2s;
}

.manual-btn:hover{
  border-color:var(--red);
  transform:translateY(-1px);
}

.manual-btn.download{
  background:rgba(255,48,8,.12);
}
  </style>
</head>

<body>
  <div class="login-wrapper">
    <div class="brand">
      <div class="brand-logo">Urban<span>Food</span></div>
      <p>Tu comida favorita, cuando quieras</p>
    </div>

    <div class="card">
      <h2>¡Bienvenido de vuelta!</h2>
      <p class="subtitle">Inicia sesión para hacer tu pedido</p>

      <?php if ($error): ?>
        <div class="error-msg"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" placeholder="tucorreo@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Contraseña</label>
          <input type="password" name="password" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn-primary">Iniciar sesión</button>
      </form>

      <div class="divider">o</div>

      <div class="register-link">
        ¿No tienes cuenta? <a href="register.php">Créala aquí</a>
      </div>

      <div class="manual-section">
  <a href="manuales/manual_cliente.pdf" target="_blank" class="manual-btn">
    📘 Ver Manual
  </a>

  <a href="manuales/manual_cliente.pdf" download class="manual-btn download">
    ⬇ Descargar Manual
  </a>
</div>
    </div>

  
  </div>
</body>

</html>