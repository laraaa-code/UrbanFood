<?php
require_once '../includes/config.php';
requireAdmin();
$page_title = 'Dashboard';
$db = getDB();

// Consultas de resumen para las tarjetas estadisticas del dashboard
$total_pedidos  = $db->query("SELECT COUNT(*) as c FROM Pedidos")->fetch_assoc()['c'];
$total_ventas   = $db->query("SELECT COALESCE(SUM(total),0) as t FROM Pedidos")->fetch_assoc()['t'];
$total_clientes = $db->query("SELECT COUNT(*) as c FROM Clientes")->fetch_assoc()['c'];
$total_rests    = $db->query("SELECT COUNT(*) as c FROM Restaurantes")->fetch_assoc()['c'];
$pendientes     = $db->query("SELECT COUNT(*) as c FROM Pedidos WHERE estado='pendiente'")->fetch_assoc()['c'];

// Obtiene los 10 pedidos mas recientes con datos del cliente y del restaurante
$recent = $db->query("
    SELECT p.id_pedido, p.fecha, p.total, p.estado,
           c.nombre AS cliente, r.nombre AS restaurante
    FROM Pedidos p
    JOIN Clientes c ON p.id_cliente=c.id_cliente
    JOIN Restaurantes r ON p.id_restaurante=r.id_restaurante
    ORDER BY p.fecha DESC LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

$db->close();
include 'header.php';
?>
<div class="page-header">
  <h1>Dashboard</h1>
  <p>Resumen general de UrbanFood</p>
</div>

<!-- Tarjetas con metricas clave del negocio -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon">📦</div>
    <div class="stat-value"><?= $total_pedidos ?></div>
    <div class="stat-label">Total Pedidos</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">💰</div>
    <div class="stat-value">$<?= number_format($total_ventas, 2) ?></div>
    <div class="stat-label">Total Ventas</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">👤</div>
    <div class="stat-value"><?= $total_clientes ?></div>
    <div class="stat-label">Clientes</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">🏪</div>
    <div class="stat-value"><?= $total_rests ?></div>
    <div class="stat-label">Restaurantes</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">⏳</div>
    <div class="stat-value" style="color:var(--yellow)"><?= $pendientes ?></div>
    <div class="stat-label">Pendientes</div>
  </div>
</div>

<!-- Tabla con los ultimos 10 pedidos registrados -->
<div class="card">
  <div class="card-header">
    <span class="card-title">Pedidos Recientes</span>
    <a href="pedidos.php" class="btn btn-ghost btn-sm">Ver todos</a>
  </div>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Cliente</th>
        <th>Restaurante</th>
        <th>Fecha</th>
        <th>Total</th>
        <th>Estado</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($recent as $p):
        $badges = ['pendiente' => 'badge-pending', 'en_camino' => 'badge-camino', 'entregado' => 'badge-entregado'];
        $labels = ['pendiente' => 'Pendiente', 'en_camino' => 'En camino', 'entregado' => 'Entregado'];
      ?>
        <tr>
          <td>#<?= $p['id_pedido'] ?></td>
          <td><?= htmlspecialchars($p['cliente']) ?></td>
          <td><?= htmlspecialchars($p['restaurante']) ?></td>
          <td><?= date('d/m/Y H:i', strtotime($p['fecha'])) ?></td>
          <td style="color:var(--red);font-weight:700">$<?= number_format($p['total'], 2) ?></td>
          <td><span class="badge <?= $badges[$p['estado']] ?>"><?= $labels[$p['estado']] ?></span></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
</div>
</body>

</html>