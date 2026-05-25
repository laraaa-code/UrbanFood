<?php
require_once '../includes/config.php';

// Si ya hay una sesion de admin activa, redirige directo al dashboard
if (isAdmin()) {
  header('Location: index.php');
  exit;
}

$error = '';

// Valida la clave de administrador definida en config.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (($_POST['admin_key'] ?? '') === ADMIN_KEY) {
    $_SESSION['is_admin'] = true;
    header('Location: index.php');
    exit;
  } else {
    $error = 'Clave de administrador incorrecta.';
  }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin – UrbanFood</title>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box
    }

    :root {
      --red: #FF3008;
      --bg: #060608;
      --card: #111118;
      --border: #1e1e2e;
      --text: #F5F5F5;
      --muted: #666;
      --accent: #7c3aed
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center
    }

    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: radial-gradient(ellipse at 20% 50%, rgba(124, 58, 237, .12) 0%, transparent 60%), radial-gradient(ellipse at 80% 50%, rgba(255, 48, 8, .08) 0%, transparent 60%);
      pointer-events: none
    }

    .wrapper {
      max-width: 400px;
      width: 90%;
      z-index: 1
    }

    .brand {
      text-align: center;
      margin-bottom: 2.5rem
    }

    .brand-logo {
      font-family: 'Sora', sans-serif;
      font-size: 2rem;
      font-weight: 800;
      letter-spacing: -1px
    }

    .brand-logo .urban {
      color: var(--red)
    }

    .brand-logo .food {
      color: var(--text)
    }

    .brand-badge {
      display: inline-block;
      background: rgba(124, 58, 237, .2);
      border: 1px solid rgba(124, 58, 237, .4);
      color: #a78bfa;
      padding: .25rem .8rem;
      border-radius: 50px;
      font-size: .75rem;
      font-weight: 700;
      letter-spacing: 1px;
      text-transform: uppercase;
      margin-top: .5rem
    }

    .card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 2.5rem;
      box-shadow: 0 30px 80px rgba(0, 0, 0, .6)
    }

    .card h2 {
      font-family: 'Sora', sans-serif;
      font-size: 1.4rem;
      font-weight: 700;
      margin-bottom: .3rem
    }

    .card .sub {
      color: var(--muted);
      font-size: .85rem;
      margin-bottom: 2rem
    }

    .form-group {
      margin-bottom: 1.2rem
    }

    .form-group label {
      display: block;
      font-size: .8rem;
      font-weight: 500;
      color: var(--muted);
      margin-bottom: .5rem;
      text-transform: uppercase;
      letter-spacing: .5px
    }

    .form-group input {
      width: 100%;
      background: #0a0a0f;
      border: 1px solid var(--border);
      color: var(--text);
      padding: .9rem 1.1rem;
      border-radius: 12px;
      font-size: .95rem;
      outline: none;
      transition: border-color .2s, box-shadow .2s;
      font-family: 'Inter', sans-serif
    }

    .form-group input:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(124, 58, 237, .15)
    }

    .btn {
      width: 100%;
      background: linear-gradient(135deg, var(--accent), #9333ea);
      color: #fff;
      border: none;
      padding: 1rem;
      border-radius: 12px;
      font-size: 1rem;
      font-family: 'Sora', sans-serif;
      font-weight: 700;
      cursor: pointer;
      transition: all .2s
    }

    .btn:hover {
      opacity: .9;
      transform: translateY(-1px);
      box-shadow: 0 6px 24px rgba(124, 58, 237, .4)
    }

    .error {
      background: rgba(255, 48, 8, .1);
      border: 1px solid rgba(255, 48, 8, .3);
      color: #ff6b55;
      padding: .8rem 1rem;
      border-radius: 10px;
      font-size: .85rem;
      margin-bottom: 1.2rem
    }

    .back {
      text-align: center;
      margin-top: 1.5rem
    }

    .back a {
      color: var(--muted);
      font-size: .82rem;
      text-decoration: none
    }

    .back a:hover {
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
  background:#1a1a24;
  border:1px solid var(--border);
  color:var(--text);
  padding:.8rem 1rem;
  border-radius:12px;
  font-size:.85rem;
  transition:.2s;
}

.manual-btn:hover{
  border-color:var(--accent);
  transform:translateY(-1px);
}

.manual-btn.download{
  background:rgba(124,58,237,.12);
}
  </style>
</head>

<body>
  <div class="wrapper">
    <div class="brand">
      <div class="brand-logo"><span class="urban">Urban</span><span class="food">Food</span></div>
      <div class="brand-badge">⚙ Panel Admin</div>
    </div>
    <div class="card">
      <h2>Acceso Administrador</h2>
      <p class="sub">Ingresa la clave de administrador para continuar</p>

      <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-group">
          <label>Clave de administrador</label>
          <input type="password" name="admin_key" placeholder="••••••••••••" required autofocus>
        </div>
        <button type="submit" class="btn">Entrar al panel →</button>
      </form>

      <div class="manual-section">
  <a href="../manuales/manual_admin.pdf" target="_blank" class="manual-btn">
    📘 Ver Manual
  </a>

  <a href="../manuales/manual_admin.pdf" download class="manual-btn download">
    ⬇ Descargar Manual
  </a>
</div>
    </div>
    
  </div>
</body>

</html>