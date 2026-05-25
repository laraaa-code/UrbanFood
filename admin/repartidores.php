<?php
require_once '../includes/config.php';
requireAdmin();
$page_title = 'Repartidores';
$db = getDB();
$msg = '';
$msg_type = '';

// Crea un nuevo repartidor o actualiza uno existente segun si viene id_edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nombre  = trim($_POST['nombre'] ?? '');
  $telefono = trim($_POST['telefono'] ?? '');
  // El checkbox "activo" solo envia valor si esta marcado; si no, se asume 0
  $activo   = isset($_POST['activo']) ? 1 : 0;
  $id_edit  = intval($_POST['id_edit'] ?? 0);

  if ($nombre) {
    if ($id_edit) {
      $s = $db->prepare("UPDATE Repartidores SET nombre=?,telefono=?,activo=? WHERE id_repartidor=?");
      $s->bind_param("ssii", $nombre, $telefono, $activo, $id_edit);
      $s->execute();
      $msg = 'Repartidor actualizado.';
      $msg_type = 'success';
    } else {
      $s = $db->prepare("INSERT INTO Repartidores (nombre,telefono,activo) VALUES (?,?,?)");
      $s->bind_param("ssi", $nombre, $telefono, $activo);
      $s->execute();
      $msg = 'Repartidor creado.';
      $msg_type = 'success';
    }
  }
}

// Elimina un repartidor por ID recibido via GET
if (isset($_GET['delete'])) {
  $did = intval($_GET['delete']);
  $db->query("DELETE FROM Repartidores WHERE id_repartidor=$did");
  $msg = 'Repartidor eliminado.';
  $msg_type = 'success';
}

$repartidores = $db->query("SELECT * FROM Repartidores ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
$db->close();
include 'header.php';
?>
<div class="page-header">
  <h1>Repartidores</h1>
  <p>Gestiona tu equipo de repartidores</p>
</div>

<?php if ($msg): ?>
  <div class="alert alert-<?= $msg_type ?>"><?= $msg ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:1.5rem;align-items:start">
  <div class="card">
    <div class="card-header">
      <span class="card-title">Lista de Repartidores</span>
    </div>
    <table>
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Teléfono</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($repartidores as $r): ?>
          <tr>
            <td><strong><?= htmlspecialchars($r['nombre']) ?></strong></td>
            <td style="color:var(--muted)"><?= htmlspecialchars($r['telefono'] ?? '') ?></td>
            <td>
              <?php if ($r['activo']): ?>
                <span class="badge badge-entregado">✓ Activo</span>
              <?php else: ?>
                <span class="badge" style="background:rgba(102,102,102,.15);color:#666;border:1px solid #333">Inactivo</span>
              <?php endif; ?>
            </td>
            <td>
              <button class="btn btn-edit btn-sm" onclick='editRep(<?= json_encode($r) ?>)'>✏️ Editar</button>
              <a href="?delete=<?= $r['id_repartidor'] ?>" class="btn btn-delete btn-sm" onclick="return confirm('¿Eliminar?')">🗑️</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Formulario lateral compartido para crear y editar repartidores -->
  <div class="card" id="form-card">
    <div class="card-header">
      <span class="card-title" id="form-title">Nuevo Repartidor</span>
    </div>
    <form method="POST">
      <input type="hidden" name="id_edit" id="id_edit" value="0">
      <div class="form-group">
        <label>Nombre completo</label>
        <input type="text" name="nombre" id="f-nombre" placeholder="Nombre del repartidor" required>
      </div>
      <div class="form-group">
        <label>Teléfono</label>
        <input type="text" name="telefono" id="f-tel" placeholder="7000-0000">
      </div>
      <div class="form-group" style="display:flex;align-items:center;gap:.7rem">
        <input type="checkbox" name="activo" id="f-activo" value="1" checked style="width:auto;accent-color:var(--green)">
        <label for="f-activo" style="text-transform:none;letter-spacing:0;font-size:.9rem;color:var(--text);margin:0">Activo (disponible para pedidos)</label>
      </div>
      <div style="display:flex;gap:.7rem">
        <button type="submit" class="btn btn-primary" style="flex:1">Guardar</button>
        <button type="button" class="btn btn-ghost" onclick="resetForm()">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script>
  // Precarga los datos del repartidor en el formulario lateral para editarlo
  function editRep(r) {
    document.getElementById('id_edit').value = r.id_repartidor;
    document.getElementById('f-nombre').value = r.nombre;
    document.getElementById('f-tel').value = r.telefono || '';
    document.getElementById('f-activo').checked = r.activo == 1;
    document.getElementById('form-title').textContent = 'Editar Repartidor';
    document.getElementById('form-card').scrollIntoView({
      behavior: 'smooth'
    });
  }

  // Limpia el formulario y lo deja listo para crear un nuevo repartidor
  function resetForm() {
    document.getElementById('id_edit').value = 0;
    document.getElementById('f-nombre').value = '';
    document.getElementById('f-tel').value = '';
    document.getElementById('f-activo').checked = true;
    document.getElementById('form-title').textContent = 'Nuevo Repartidor';
  }
</script>
</div>
</body>

</html>