<?php
require_once '../includes/config.php';
requireAdmin();
$page_title = 'Pedidos';
$db = getDB();
$msg = '';

// Actualiza el estado de un pedido si el valor enviado es valido
if (isset($_POST['update_estado'])) {
  $pid    = intval($_POST['pedido_id']);
  $estado = $_POST['estado'];
  if (in_array($estado, ['pendiente', 'en_camino', 'entregado'])) {
    $s = $db->prepare("UPDATE Pedidos SET estado=? WHERE id_pedido=?");
    $s->bind_param("si", $estado, $pid);
    $s->execute();
    $msg = 'Estado actualizado.';
  }
}

// Obtiene todos los pedidos con datos del cliente, restaurante y repartidor
$pedidos = $db->query("
    SELECT p.*, c.nombre AS cliente, r.nombre AS restaurante,
           rep.nombre AS repartidor, rep.telefono AS rep_tel
    FROM Pedidos p
    JOIN Clientes c ON p.id_cliente=c.id_cliente
    JOIN Restaurantes r ON p.id_restaurante=r.id_restaurante
    JOIN Repartidores rep ON p.id_repartidor=rep.id_repartidor
    ORDER BY p.fecha DESC
")->fetch_all(MYSQLI_ASSOC);

// Carga los productos de cada pedido e indexa por id_pedido para el detalle expandible
$detalles = [];
foreach ($pedidos as $p) {
  $d = $db->prepare("
        SELECT dp.*, pr.nombre AS producto
        FROM Detalle_Pedidos dp
        JOIN Productos pr ON dp.id_producto=pr.id_producto
        WHERE dp.id_pedido=?
    ");
  $d->bind_param("i", $p['id_pedido']);
  $d->execute();
  $detalles[$p['id_pedido']] = $d->get_result()->fetch_all(MYSQLI_ASSOC);
}
$db->close();
include 'header.php';
?>
<div class="page-header">
  <h1>Pedidos</h1>
  <p>Gestiona y actualiza el estado de los pedidos</p>
</div>

<?php if ($msg): ?>
  <div class="alert alert-success"><?= $msg ?></div>
<?php endif; ?>

<div class="card">
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Cliente</th>
        <th>Restaurante</th>
        <th>Repartidor</th>
        <th>Fecha</th>
        <th>Total</th>
        <th>Estado</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $badges = ['pendiente' => 'badge-pending', 'en_camino' => 'badge-camino', 'entregado' => 'badge-entregado'];
      $labels = ['pendiente' => 'Pendiente', 'en_camino' => 'En camino', 'entregado' => 'Entregado'];
      foreach ($pedidos as $p): ?>
        <tr>
          <td><strong>#<?= $p['id_pedido'] ?></strong></td>
          <td><?= htmlspecialchars($p['cliente']) ?></td>
          <td><?= htmlspecialchars($p['restaurante']) ?></td>
          <td style="color:var(--muted)"><?= htmlspecialchars($p['repartidor']) ?></td>
          <td style="color:var(--muted);font-size:.8rem"><?= date('d/m/Y H:i', strtotime($p['fecha'])) ?></td>
          <td style="color:var(--red);font-weight:700">$<?= number_format($p['total'], 2) ?></td>
          <td><span class="badge <?= $badges[$p['estado']] ?>"><?= $labels[$p['estado']] ?></span></td>
          <td>
            <button class="btn btn-ghost btn-sm" onclick="toggleDetails(<?= $p['id_pedido'] ?>)">👁 Detalle</button>
            <button class="btn btn-edit btn-sm" onclick="openEstado(<?= $p['id_pedido'] ?>,'<?= $p['estado'] ?>')">🔄 Estado</button>
          </td>
        </tr>
        <!-- Fila oculta con el detalle de productos del pedido; se muestra al hacer clic en "Detalle" -->
        <tr id="row-detail-<?= $p['id_pedido'] ?>" style="display:none">
          <td colspan="8" style="background:#0a0a0f;padding:1rem 1.5rem">
            <div style="font-size:.85rem;font-weight:600;margin-bottom:.5rem;color:var(--muted)">Detalle del pedido #<?= $p['id_pedido'] ?></div>
            <?php foreach ($detalles[$p['id_pedido']] as $d): ?>
              <div style="display:flex;justify-content:space-between;padding:.3rem 0;border-bottom:1px solid var(--border);font-size:.85rem">
                <span><?= htmlspecialchars($d['producto']) ?></span>
                <span style="color:var(--muted)">×<?= $d['cantidad'] ?></span>
                <span style="color:var(--red)">$<?= number_format($d['subtotal'], 2) ?></span>
              </div>
            <?php endforeach; ?>
            <div style="margin-top:.5rem;font-size:.82rem;color:var(--muted)">🛵 <?= htmlspecialchars($p['repartidor']) ?> — <?= htmlspecialchars($p['rep_tel']) ?></div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Modal para cambiar el estado de un pedido especifico -->
<div class="modal-bg" id="estado-modal">
  <div class="modal" style="max-width:340px">
    <h3>Actualizar Estado</h3>
    <form method="POST">
      <input type="hidden" name="update_estado" value="1">
      <input type="hidden" name="pedido_id" id="modal-pedido-id">
      <div class="form-group">
        <label>Nuevo Estado</label>
        <select name="estado" id="modal-estado">
          <option value="pendiente">⏳ Pendiente</option>
          <option value="en_camino">🛵 En camino</option>
          <option value="entregado">✅ Entregado</option>
        </select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal()">Cancelar</button>
        <button type="submit" class="btn btn-primary">Actualizar</button>
      </div>
    </form>
  </div>
</div>

<script>
  // Muestra u oculta la fila de detalle de un pedido
  function toggleDetails(id) {
    const row = document.getElementById('row-detail-' + id);
    row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
  }

  // Abre el modal de cambio de estado precargando el ID y el estado actual del pedido
  function openEstado(id, estado) {
    document.getElementById('modal-pedido-id').value = id;
    document.getElementById('modal-estado').value = estado;
    document.getElementById('estado-modal').classList.add('show');
  }

  // Cierra el modal de cambio de estado
  function closeModal() {
    document.getElementById('estado-modal').classList.remove('show');
  }
</script>
</div>
</body>

</html>