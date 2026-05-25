<?php
require_once 'includes/config.php';
requireLogin();

$db = getDB();

// Obtiene todos los pedidos del cliente en sesion, incluyendo nombre del restaurante
// y datos del repartidor, ordenados del mas reciente al mas antiguo
$pedidos = $db->prepare("
    SELECT p.*, r.nombre AS restaurante, rep.nombre AS repartidor, rep.telefono AS rep_tel
    FROM Pedidos p
    JOIN Restaurantes r ON p.id_restaurante = r.id_restaurante
    JOIN Repartidores rep ON p.id_repartidor = rep.id_repartidor
    WHERE p.id_cliente = ?
    ORDER BY p.fecha DESC
");
$pedidos->bind_param("i", $_SESSION['cliente_id']);
$pedidos->execute();
$pedidos_list = $pedidos->get_result()->fetch_all(MYSQLI_ASSOC);

// Carga los productos de cada pedido individualmente y los indexa por id_pedido
// para poder mostrarlos al expandir cada tarjeta
$detalles = [];
foreach ($pedidos_list as $p) {
  $d = $db->prepare("
        SELECT dp.*, pr.nombre AS producto
        FROM Detalle_Pedidos dp
        JOIN Productos pr ON dp.id_producto = pr.id_producto
        WHERE dp.id_pedido = ?
    ");
  $d->bind_param("i", $p['id_pedido']);
  $d->execute();
  $detalles[$p['id_pedido']] = $d->get_result()->fetch_all(MYSQLI_ASSOC);
}
$db->close();

// Mapas de estado a etiqueta legible y color para el badge visual
$estado_labels = ['pendiente' => 'Pendiente', 'en_camino' => 'En camino', 'entregado' => 'Entregado'];
$estado_colors = ['pendiente' => '#f59e0b', 'en_camino' => '#3b82f6', 'entregado' => '#22c55e'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mis Pedidos – UrbanFood</title>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box
    }

    :root {
      --red: #FF3008;
      --bg: #0D0D0D;
      --card: #1A1A1A;
      --card2: #222;
      --border: #2A2A2A;
      --text: #F5F5F5;
      --muted: #888
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
      z-index: 100
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

    .container {
      max-width: 800px;
      margin: 0 auto;
      padding: 2rem
    }

    .page-title {
      font-family: 'Sora', sans-serif;
      font-size: 1.8rem;
      font-weight: 800;
      margin-bottom: 2rem
    }

    .page-title span {
      color: var(--red)
    }

    .pedido-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 18px;
      margin-bottom: 1.2rem;
      overflow: hidden;
      transition: border-color .2s
    }

    .pedido-card:hover {
      border-color: #444
    }

    .pedido-header {
      padding: 1.3rem 1.5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      cursor: pointer
    }

    .pedido-header-left h3 {
      font-family: 'Sora', sans-serif;
      font-weight: 700;
      font-size: 1rem;
      margin-bottom: .25rem
    }

    .pedido-header-left p {
      color: var(--muted);
      font-size: .82rem
    }

    .pedido-header-right {
      display: flex;
      align-items: center;
      gap: 1rem
    }

    .estado-badge {
      padding: .3rem .85rem;
      border-radius: 50px;
      font-size: .78rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .5px
    }

    .pedido-total {
      font-family: 'Sora', sans-serif;
      font-weight: 700;
      color: var(--red);
      font-size: 1.05rem
    }

    .toggle-icon {
      color: var(--muted);
      transition: transform .3s;
      font-size: .85rem
    }

    .toggle-icon.open {
      transform: rotate(180deg)
    }

    .pedido-details {
      border-top: 1px solid var(--border);
      padding: 1.2rem 1.5rem;
      display: none
    }

    .pedido-details.show {
      display: block
    }

    .detail-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: .45rem 0;
      border-bottom: 1px solid rgba(255, 255, 255, .05);
      font-size: .88rem
    }

    .detail-row:last-child {
      border-bottom: none
    }

    .detail-row .name {
      color: var(--text)
    }

    .detail-row .qty {
      color: var(--muted);
      margin: 0 1rem
    }

    .detail-row .price {
      color: var(--red);
      font-weight: 600
    }

    .repartidor-bar {
      background: #111;
      border-radius: 10px;
      padding: .8rem 1rem;
      margin-top: 1rem;
      display: flex;
      align-items: center;
      gap: .7rem;
      font-size: .85rem
    }

    .repartidor-bar .icon {
      font-size: 1.2rem
    }

    .empty-state {
      text-align: center;
      padding: 4rem 2rem;
      color: var(--muted)
    }

    .empty-state .icon {
      font-size: 4rem;
      margin-bottom: 1rem
    }

    .empty-state h3 {
      font-family: 'Sora', sans-serif;
      font-size: 1.2rem;
      color: var(--text);
      margin-bottom: .5rem
    }

    .empty-state a {
      color: var(--red);
      text-decoration: none;
      font-weight: 600
    }
  </style>
</head>

<body>
  <nav>
    <div class="nav-logo">Urban<span>Food</span></div>
    <div class="nav-right">
      <a href="index.php" class="nav-link">🏠 Restaurantes</a>
      <a href="mis-pedidos.php" class="nav-link active">📋 Mis Pedidos</a>
      <span style="color:var(--muted);font-size:.88rem">Hola, <strong style="color:var(--text)"><?= htmlspecialchars($_SESSION['cliente_nombre']) ?></strong></span>
      <form method="POST" action="logout.php" style="display:inline">
        <button type="submit" class="btn-logout">Salir</button>
      </form>
    </div>
  </nav>

  <div class="container">
    <div class="page-title">Mis <span>Pedidos</span></div>

    <?php if (empty($pedidos_list)): ?>
      <div class="empty-state">
        <div class="icon">🛵</div>
        <h3>Aún no tienes pedidos</h3>
        <p>¿Qué esperas? <a href="index.php">¡Pide ahora!</a></p>
      </div>
    <?php else: ?>
      <?php foreach ($pedidos_list as $p):
        $color = $estado_colors[$p['estado']] ?? '#888';
        $label = $estado_labels[$p['estado']] ?? $p['estado'];
        $fecha = date('d/m/Y H:i', strtotime($p['fecha']));
      ?>
        <div class="pedido-card">
          <!-- Cabecera clickeable que muestra restaurante, numero de pedido, estado y total -->
          <div class="pedido-header" onclick="toggleDetails(<?= $p['id_pedido'] ?>)">
            <div class="pedido-header-left">
              <h3>🏪 <?= htmlspecialchars($p['restaurante']) ?></h3>
              <p>Pedido #<?= $p['id_pedido'] ?> · <?= $fecha ?></p>
            </div>
            <div class="pedido-header-right">
              <span class="estado-badge" style="background:<?= $color ?>22;color:<?= $color ?>;border:1px solid <?= $color ?>44"><?= $label ?></span>
              <span class="pedido-total">$<?= number_format($p['total'], 2) ?></span>
              <span class="toggle-icon" id="icon-<?= $p['id_pedido'] ?>">▼</span>
            </div>
          </div>

          <!-- Detalle expandible con los productos del pedido y datos del repartidor -->
          <div class="pedido-details" id="details-<?= $p['id_pedido'] ?>">
            <?php foreach ($detalles[$p['id_pedido']] as $d): ?>
              <div class="detail-row">
                <span class="name"><?= htmlspecialchars($d['producto']) ?></span>
                <span class="qty">×<?= $d['cantidad'] ?></span>
                <span class="price">$<?= number_format($d['subtotal'], 2) ?></span>
              </div>
            <?php endforeach; ?>
            <div class="repartidor-bar">
              <span class="icon">🛵</span>
              <span>Repartidor: <strong><?= htmlspecialchars($p['repartidor']) ?></strong> — <?= htmlspecialchars($p['rep_tel']) ?></span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <script>
    // Alterna la visibilidad del detalle de un pedido y rota el icono de flecha
    function toggleDetails(id) {
      const d = document.getElementById('details-' + id);
      const icon = document.getElementById('icon-' + id);
      d.classList.toggle('show');
      icon.classList.toggle('open');
    }
  </script>
</body>

</html>