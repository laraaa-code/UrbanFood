<?php
// Este archivo debe incluirse despues de llamar requireAdmin() en cada pagina del panel.
// Se encarga de generar el HTML inicial, los estilos globales del admin y la barra lateral de navegacion.
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $page_title ?? 'Admin' ?> – UrbanFood Admin</title>
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
      --bg: #060608;
      --sidebar: #0d0d14;
      --card: #111118;
      --card2: #161620;
      --border: #1e1e2e;
      --text: #F5F5F5;
      --muted: #666;
      --accent: #7c3aed;
      --green: #22c55e;
      --yellow: #f59e0b;
      --blue: #3b82f6
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: flex
    }

    .sidebar {
      width: 240px;
      min-height: 100vh;
      background: var(--sidebar);
      border-right: 1px solid var(--border);
      padding: 1.5rem 0;
      display: flex;
      flex-direction: column;
      position: fixed;
      top: 0;
      left: 0;
      bottom: 0
    }

    .sidebar-logo {
      padding: 0 1.5rem 1.5rem;
      border-bottom: 1px solid var(--border);
      margin-bottom: 1rem
    }

    .sidebar-logo .brand {
      font-family: 'Sora', sans-serif;
      font-size: 1.4rem;
      font-weight: 800
    }

    .sidebar-logo .brand .urban {
      color: var(--red)
    }

    .sidebar-logo .badge {
      font-size: .7rem;
      color: var(--accent);
      text-transform: uppercase;
      letter-spacing: 1px;
      font-weight: 700
    }

    .nav-group {
      padding: .5rem 1rem;
      margin-bottom: .25rem
    }

    .nav-group-label {
      font-size: .7rem;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: var(--muted);
      padding: 0 .5rem;
      margin-bottom: .4rem;
      font-weight: 600
    }

    .nav-item {
      display: flex;
      align-items: center;
      gap: .7rem;
      padding: .65rem .8rem;
      border-radius: 10px;
      color: var(--muted);
      text-decoration: none;
      font-size: .88rem;
      font-weight: 500;
      transition: all .2s;
      margin-bottom: .1rem
    }

    .nav-item:hover {
      background: rgba(255, 255, 255, .05);
      color: var(--text)
    }

    .nav-item.active {
      background: rgba(255, 48, 8, .12);
      color: var(--red);
      border: 1px solid rgba(255, 48, 8, .2)
    }

    .nav-item .icon {
      font-size: 1rem;
      width: 20px;
      text-align: center
    }

    .sidebar-footer {
      margin-top: auto;
      padding: 1rem 1.5rem;
      border-top: 1px solid var(--border)
    }

    .btn-logout-admin {
      width: 100%;
      background: transparent;
      border: 1px solid var(--border);
      color: var(--muted);
      padding: .6rem;
      border-radius: 8px;
      font-size: .82rem;
      cursor: pointer;
      transition: all .2s
    }

    .btn-logout-admin:hover {
      border-color: var(--red);
      color: var(--red)
    }

    .main {
      margin-left: 240px;
      flex: 1;
      min-height: 100vh;
      padding: 2rem
    }

    .page-header {
      margin-bottom: 2rem
    }

    .page-header h1 {
      font-family: 'Sora', sans-serif;
      font-size: 1.8rem;
      font-weight: 800
    }

    .page-header p {
      color: var(--muted);
      font-size: .88rem;
      margin-top: .3rem
    }

    .card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 1.5rem
    }

    .card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1.2rem
    }

    .card-title {
      font-family: 'Sora', sans-serif;
      font-weight: 700;
      font-size: 1rem
    }

    table {
      width: 100%;
      border-collapse: collapse
    }

    th {
      font-size: .75rem;
      text-transform: uppercase;
      letter-spacing: .5px;
      color: var(--muted);
      padding: .7rem 1rem;
      text-align: left;
      border-bottom: 1px solid var(--border)
    }

    td {
      padding: .8rem 1rem;
      border-bottom: 1px solid rgba(255, 255, 255, .04);
      font-size: .88rem
    }

    tr:last-child td {
      border-bottom: none
    }

    tr:hover td {
      background: rgba(255, 255, 255, .02)
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: .4rem;
      padding: .55rem 1rem;
      border-radius: 8px;
      font-size: .85rem;
      font-weight: 600;
      cursor: pointer;
      border: none;
      transition: all .2s;
      font-family: 'Inter', sans-serif;
      text-decoration: none
    }

    .btn-primary {
      background: var(--red);
      color: #fff
    }

    .btn-primary:hover {
      background: var(--red-dark)
    }

    .btn-sm {
      padding: .35rem .7rem;
      font-size: .78rem;
      border-radius: 6px
    }

    .btn-edit {
      background: rgba(59, 130, 246, .15);
      color: #60a5fa;
      border: 1px solid rgba(59, 130, 246, .25)
    }

    .btn-edit:hover {
      background: rgba(59, 130, 246, .25)
    }

    .btn-delete {
      background: rgba(255, 48, 8, .1);
      color: #ff6b55;
      border: 1px solid rgba(255, 48, 8, .2)
    }

    .btn-delete:hover {
      background: rgba(255, 48, 8, .2)
    }

    .btn-ghost {
      background: transparent;
      color: var(--muted);
      border: 1px solid var(--border)
    }

    .btn-ghost:hover {
      border-color: #555;
      color: var(--text)
    }

    .form-group {
      margin-bottom: 1.1rem
    }

    .form-group label {
      display: block;
      font-size: .8rem;
      font-weight: 500;
      color: var(--muted);
      margin-bottom: .4rem;
      text-transform: uppercase;
      letter-spacing: .4px
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      background: #0a0a0f;
      border: 1px solid var(--border);
      color: var(--text);
      padding: .8rem 1rem;
      border-radius: 10px;
      font-size: .9rem;
      outline: none;
      font-family: 'Inter', sans-serif;
      transition: border-color .2s
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      border-color: var(--red)
    }

    .form-group select option {
      background: #111
    }

    .modal-bg {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .7);
      backdrop-filter: blur(4px);
      z-index: 1000;
      align-items: center;
      justify-content: center
    }

    .modal-bg.show {
      display: flex
    }

    .modal {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 2rem;
      width: 90%;
      max-width: 480px;
      max-height: 90vh;
      overflow-y: auto
    }

    .modal h3 {
      font-family: 'Sora', sans-serif;
      font-size: 1.2rem;
      font-weight: 700;
      margin-bottom: 1.5rem
    }

    .modal-footer {
      display: flex;
      gap: .8rem;
      margin-top: 1.5rem;
      justify-content: flex-end
    }

    .alert {
      padding: .8rem 1rem;
      border-radius: 10px;
      font-size: .85rem;
      margin-bottom: 1.2rem
    }

    .alert-success {
      background: rgba(34, 197, 94, .1);
      border: 1px solid rgba(34, 197, 94, .25);
      color: #4ade80
    }

    .alert-error {
      background: rgba(255, 48, 8, .1);
      border: 1px solid rgba(255, 48, 8, .25);
      color: #ff6b55
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 1rem;
      margin-bottom: 2rem
    }

    .stat-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 1.2rem
    }

    .stat-icon {
      font-size: 1.5rem;
      margin-bottom: .5rem
    }

    .stat-value {
      font-family: 'Sora', sans-serif;
      font-size: 1.8rem;
      font-weight: 800
    }

    .stat-label {
      color: var(--muted);
      font-size: .8rem;
      margin-top: .2rem
    }

    .badge {
      padding: .2rem .6rem;
      border-radius: 50px;
      font-size: .72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .3px
    }

    .badge-pending {
      background: rgba(245, 158, 11, .15);
      color: #fbbf24;
      border: 1px solid rgba(245, 158, 11, .25)
    }

    .badge-camino {
      background: rgba(59, 130, 246, .15);
      color: #60a5fa;
      border: 1px solid rgba(59, 130, 246, .25)
    }

    .badge-entregado {
      background: rgba(34, 197, 94, .15);
      color: #4ade80;
      border: 1px solid rgba(34, 197, 94, .25)
    }
  </style>
</head>

<body>
  <!-- Barra lateral fija con navegacion entre secciones del panel admin.
     El item activo se resalta comparando $current_page con el nombre del archivo actual. -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <div class="brand"><span class="urban">Urban</span>Food</div>
      <div class="badge">⚙ Admin Panel</div>
    </div>

    <div class="nav-group">
      <div class="nav-group-label">General</div>
      <a href="index.php" class="nav-item <?= $current_page === 'index.php' ? 'active' : '' ?>">
        <span class="icon">📊</span> Dashboard
      </a>
      <a href="pedidos.php" class="nav-item <?= $current_page === 'pedidos.php' ? 'active' : '' ?>">
        <span class="icon">📋</span> Pedidos
      </a>
    </div>

    <div class="nav-group">
      <div class="nav-group-label">Gestión</div>
      <a href="restaurantes.php" class="nav-item <?= $current_page === 'restaurantes.php' ? 'active' : '' ?>">
        <span class="icon">🏪</span> Restaurantes
      </a>
      <a href="productos.php" class="nav-item <?= $current_page === 'productos.php' ? 'active' : '' ?>">
        <span class="icon">🍽️</span> Productos
      </a>
      <a href="repartidores.php" class="nav-item <?= $current_page === 'repartidores.php' ? 'active' : '' ?>">
        <span class="icon">🛵</span> Repartidores
      </a>
      <a href="clientes.php" class="nav-item <?= $current_page === 'clientes.php' ? 'active' : '' ?>">
        <span class="icon">👤</span> Clientes
      </a>
    </div>

    <div class="nav-group">
      <div class="nav-group-label">Sistema</div>
      <a href="backup.php" class="nav-item <?= $current_page === 'backup.php' ? 'active' : '' ?>">
        <span class="icon">💾</span> Respaldo BD
      </a>
    </div>

    <div class="sidebar-footer">
      <form method="POST" action="logout.php">
        <button type="submit" class="btn-logout-admin">Cerrar sesión</button>
      </form>
    </div>
  </aside>
  <div class="main">