<?php
require_once '../includes/config.php';
requireAdmin();
$page_title = 'Clientes';
$db = getDB();

// Obtiene todos los clientes con el total de pedidos realizados y el monto acumulado gastado.
// Usa LEFT JOIN para incluir clientes que aun no han hecho pedidos (con total 0).
$clientes = $db->query("
    SELECT c.*, COUNT(p.id_pedido) AS total_pedidos, COALESCE(SUM(p.total),0) AS total_gastado
    FROM Clientes c
    LEFT JOIN Pedidos p ON c.id_cliente=p.id_cliente
    GROUP BY c.id_cliente ORDER BY c.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
$db->close();
include 'header.php';
?>
<div class="page-header">
  <h1>Clientes</h1>
  <p>Lista de clientes registrados</p>
</div>

<div class="card">
  <table>
    <thead>
      <tr>
        <th>Nombre</th>
        <th>Email</th>
        <th>Teléfono</th>
        <th>Dirección</th>
        <th>Pedidos</th>
        <th>Total Gastado</th>
        <th>Registro</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($clientes as $c): ?>
        <tr>
          <td><strong><?= htmlspecialchars($c['nombre']) ?></strong></td>
          <td style="color:var(--muted)"><?= htmlspecialchars($c['email']) ?></td>
          <td style="color:var(--muted)"><?= htmlspecialchars($c['telefono'] ?? '—') ?></td>
          <td style="color:var(--muted);font-size:.82rem"><?= htmlspecialchars($c['direccion'] ?? '—') ?></td>
          <td style="text-align:center"><span class="badge badge-pending"><?= $c['total_pedidos'] ?></span></td>
          <td style="color:var(--red);font-weight:700">$<?= number_format($c['total_gastado'], 2) ?></td>
          <td style="color:var(--muted);font-size:.8rem"><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
</div>
</body>

</html>