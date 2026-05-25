<?php
require_once 'includes/config.php';
requireLogin();

// Obtiene todos los restaurantes ordenados alfabeticamente para mostrarlos en el grid
$db = getDB();
$restaurantes = $db->query("SELECT * FROM Restaurantes ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
$db->close();

// Mapa de categoria a emoji para mostrar un icono representativo en cada tarjeta
$emojis = ['Pizza' => '🍕', 'Hamburguesas' => '🍔', 'Pollo' => '🍗', 'Sandwiches' => '🥖', 'Sushi' => '🍣', 'Tacos' => '🌮', 'Otros' => '🍽️'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UrbanFood – Restaurantes</title>
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
      --card2: #222;
      --border: #2A2A2A;
      --text: #F5F5F5;
      --muted: #888;
      --green: #22c55e
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh
    }

    nav {
      background: #111;
      border-bottom: 1px solid var(--border);
      padding: 0 2rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      height: 64px;
      position: sticky;
      top: 0;
      z-index: 100;
      backdrop-filter: blur(10px)
    }

    .nav-logo {
      font-family: 'Sora', sans-serif;
      font-size: 1.6rem;
      font-weight: 800;
      color: var(--red)
    }

    .nav-logo span {
      color: var(--text)
    }

    .nav-right {
      display: flex;
      align-items: center;
      gap: 1.2rem
    }

    .nav-user {
      color: var(--muted);
      font-size: .88rem
    }

    .nav-user strong {
      color: var(--text)
    }

    .nav-link {
      color: var(--text);
      text-decoration: none;
      font-size: .9rem;
      font-weight: 500;
      padding: .5rem .9rem;
      border-radius: 8px;
      transition: background .2s
    }

    .nav-link:hover {
      background: var(--card2)
    }

    .nav-link.active {
      color: var(--red)
    }

    .btn-logout {
      background: transparent;
      border: 1px solid var(--border);
      color: var(--muted);
      padding: .45rem .9rem;
      border-radius: 8px;
      font-size: .85rem;
      cursor: pointer;
      transition: all .2s
    }

    .btn-logout:hover {
      border-color: var(--red);
      color: var(--red)
    }

    .hero {
      padding: 3rem 2rem 2rem;
      max-width: 1100px;
      margin: 0 auto
    }

    .hero h1 {
      font-family: 'Sora', sans-serif;
      font-size: 2.4rem;
      font-weight: 800;
      line-height: 1.15;
      margin-bottom: .5rem
    }

    .hero h1 span {
      color: var(--red)
    }

    .hero p {
      color: var(--muted);
      font-size: 1rem
    }

    .filters {
      max-width: 1100px;
      margin: 1.5rem auto;
      padding: 0 2rem;
      display: flex;
      gap: .7rem;
      flex-wrap: wrap
    }

    .filter-btn {
      background: var(--card);
      border: 1px solid var(--border);
      color: var(--muted);
      padding: .5rem 1.1rem;
      border-radius: 50px;
      font-size: .85rem;
      cursor: pointer;
      transition: all .2s;
      font-family: 'Inter', sans-serif
    }

    .filter-btn:hover,
    .filter-btn.active {
      background: var(--red);
      border-color: var(--red);
      color: #fff
    }

    .restaurants-grid {
      max-width: 1100px;
      margin: 0 auto;
      padding: 0 2rem 4rem;
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 1.5rem
    }

    .rest-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 18px;
      overflow: hidden;
      cursor: pointer;
      transition: transform .2s, border-color .2s, box-shadow .2s;
      text-decoration: none;
      display: block;
      color: inherit
    }

    .rest-card:hover {
      transform: translateY(-4px);
      border-color: #444;
      box-shadow: 0 12px 40px rgba(0, 0, 0, .4)
    }

    .rest-thumb {
      height: 160px;
      background: linear-gradient(135deg, #1e1e1e, #2a2a2a);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 4rem;
      position: relative;
      overflow: hidden
    }

    .rest-thumb::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(to top, rgba(0, 0, 0, .5), transparent)
    }

    .rest-category-badge {
      position: absolute;
      top: 1rem;
      left: 1rem;
      background: rgba(0, 0, 0, .6);
      backdrop-filter: blur(8px);
      border: 1px solid rgba(255, 255, 255, .1);
      color: #fff;
      padding: .25rem .7rem;
      border-radius: 50px;
      font-size: .75rem;
      font-weight: 600;
      z-index: 1
    }

    .rest-body {
      padding: 1.2rem 1.3rem
    }

    .rest-name {
      font-family: 'Sora', sans-serif;
      font-size: 1.1rem;
      font-weight: 700;
      margin-bottom: .3rem
    }

    .rest-address {
      color: var(--muted);
      font-size: .82rem;
      margin-bottom: .8rem;
      display: flex;
      align-items: center;
      gap: .3rem
    }

    .rest-meta {
      display: flex;
      align-items: center;
      gap: .8rem
    }

    .delivery-badge {
      background: rgba(255, 48, 8, .12);
      color: #ff6b55;
      padding: .25rem .7rem;
      border-radius: 6px;
      font-size: .78rem;
      font-weight: 600
    }

    .open-badge {
      color: var(--green);
      font-size: .8rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: .3rem
    }

    .open-badge::before {
      content: '';
      width: 6px;
      height: 6px;
      background: var(--green);
      border-radius: 50%;
      display: inline-block
    }
  </style>
</head>

<body>
  <nav>
    <div class="nav-logo">Urban<span>Food</span></div>
    <div class="nav-right">
      <a href="index.php" class="nav-link active">🏠 Restaurantes</a>
      <a href="mis-pedidos.php" class="nav-link">📋 Mis Pedidos</a>
      <span class="nav-user">Hola, <strong><?= htmlspecialchars($_SESSION['cliente_nombre']) ?></strong></span>
      <form method="POST" action="logout.php" style="display:inline">
        <button type="submit" class="btn-logout">Salir</button>
      </form>
    </div>
  </nav>

  <div class="hero">
    <h1>¿Qué quieres<br><span>comer hoy?</span></h1>
    <p>Elige tu restaurante favorito y haz tu pedido</p>
  </div>

  <!-- Botones de filtro generados dinamicamente a partir de las categorias existentes en la BD -->
  <div class="filters">
    <button class="filter-btn active" onclick="filterAll(this)">🍽️ Todos</button>
    <?php
    $cats = array_unique(array_column($restaurantes, 'categoria'));
    foreach ($cats as $cat):
      $em = $emojis[$cat] ?? '🍽️';
    ?>
      <button class="filter-btn" onclick="filterCat(this,'<?= htmlspecialchars($cat) ?>')"><?= $em ?> <?= htmlspecialchars($cat) ?></button>
    <?php endforeach; ?>
  </div>

  <!-- Grid de tarjetas de restaurantes; cada una lleva el atributo data-cat para el filtrado -->
  <div class="restaurants-grid" id="grid">
    <?php foreach ($restaurantes as $r):
      $em = $emojis[$r['categoria']] ?? '🍽️';
    ?>
      <a href="restaurante.php?id=<?= $r['id_restaurante'] ?>" class="rest-card" data-cat="<?= htmlspecialchars($r['categoria']) ?>">
        <div class="rest-thumb">
          <span><?= $em ?></span>
          <div class="rest-category-badge"><?= htmlspecialchars($r['categoria']) ?></div>
        </div>
        <div class="rest-body">
          <div class="rest-name"><?= htmlspecialchars($r['nombre']) ?></div>
          <div class="rest-address">📍 <?= htmlspecialchars($r['direccion'] ?? 'San Salvador') ?></div>
          <div class="rest-meta">
            <span class="delivery-badge">🛵 Delivery</span>
            <span class="open-badge">Abierto ahora</span>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>

  <script>
    // Muestra todas las tarjetas y marca el boton "Todos" como activo
    function filterAll(btn) {
      document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      document.querySelectorAll('.rest-card').forEach(c => c.style.display = 'block');
    }

    // Filtra las tarjetas por categoria comparando el atributo data-cat de cada una
    function filterCat(btn, cat) {
      document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      document.querySelectorAll('.rest-card').forEach(c => {
        c.style.display = c.dataset.cat === cat ? 'block' : 'none';
      });
    }
  </script>
</body>

</html>