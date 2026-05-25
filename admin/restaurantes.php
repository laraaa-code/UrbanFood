<?php
require_once '../includes/config.php';
requireAdmin();
$page_title = 'Restaurantes';
$db = getDB();
$msg = '';
$msg_type = '';

// Crea un nuevo restaurante o actualiza uno existente segun si viene id_edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nombre    = trim($_POST['nombre'] ?? '');
  $direccion = trim($_POST['direccion'] ?? '');
  $telefono  = trim($_POST['telefono'] ?? '');
  $categoria = trim($_POST['categoria'] ?? '');
  $id_edit   = intval($_POST['id_edit'] ?? 0);

  if ($nombre) {
    if ($id_edit) {
      $s = $db->prepare("UPDATE Restaurantes SET nombre=?,direccion=?,telefono=?,categoria=? WHERE id_restaurante=?");
      $s->bind_param("ssssi", $nombre, $direccion, $telefono, $categoria, $id_edit);
      $s->execute();
      $msg = 'Restaurante actualizado.';
      $msg_type = 'success';
    } else {
      $s = $db->prepare("INSERT INTO Restaurantes (nombre,direccion,telefono,categoria) VALUES (?,?,?,?)");
      $s->bind_param("ssss", $nombre, $direccion, $telefono, $categoria);
      $s->execute();
      $msg = 'Restaurante creado.';
      $msg_type = 'success';
    }
  }
}

// Elimina un restaurante por ID recibido via GET
if (isset($_GET['delete'])) {
  $did = intval($_GET['delete']);
  $db->query("DELETE FROM Restaurantes WHERE id_restaurante=$did");
  $msg = 'Restaurante eliminado.';
  $msg_type = 'success';
}

$restaurantes = $db->query("SELECT * FROM Restaurantes ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
$categorias = ['Pizza', 'Hamburguesas', 'Pollo', 'Sandwiches', 'Sushi', 'Tacos', 'Mariscos', 'Comida Rápida', 'Otros'];
$db->close();
include 'header.php';
?>
<div class="page-header">
  <h1>Restaurantes</h1>
  <p>Gestiona los restaurantes disponibles</p>
</div>

<?php if ($msg): ?>
  <div class="alert alert-<?= $msg_type ?>"><?= $msg ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:1.5rem;align-items:start">
  <div class="card">
    <div class="card-header">
      <span class="card-title">Lista de Restaurantes</span>
    </div>
    <table>
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Categoría</th>
          <th>Dirección</th>
          <th>Teléfono</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($restaurantes as $r): ?>
          <tr>
            <td><strong><?= htmlspecialchars($r['nombre']) ?></strong></td>
            <td><?= htmlspecialchars($r['categoria'] ?? '') ?></td>
            <td style="color:var(--muted)"><?= htmlspecialchars($r['direccion'] ?? '') ?></td>
            <td style="color:var(--muted)"><?= htmlspecialchars($r['telefono'] ?? '') ?></td>
            <td>
              <!-- Pasa el objeto completo al JS para precargar el formulario de edicion -->
              <button class="btn btn-edit btn-sm" onclick='editRest(<?= json_encode($r) ?>)'>✏️ Editar</button>
              <a href="?delete=<?= $r['id_restaurante'] ?>" class="btn btn-delete btn-sm" onclick="return confirm('¿Eliminar restaurante?')">🗑️</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Formulario lateral compartido para crear y editar restaurantes -->
  <div class="card" id="form-card">
    <div class="card-header">
      <span class="card-title" id="form-title">Nuevo Restaurante</span>
    </div>
    <form method="POST">
      <input type="hidden" name="id_edit" id="id_edit" value="0">
      <div class="form-group">
        <label>Nombre</label>
        <input type="text" name="nombre" id="f-nombre" placeholder="Nombre del restaurante" required>
      </div>
      <div class="form-group">
        <label>Categoría</label>
        <select name="categoria" id="f-categoria">
          <?php foreach ($categorias as $c): ?>
            <option><?= $c ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Dirección</label>
        <input type="text" name="direccion" id="f-direccion" placeholder="Dirección">
      </div>
      <div class="form-group">
        <label>Teléfono</label>
        <input type="text" name="telefono" id="f-telefono" placeholder="0000-0000">
      </div>
      <div style="display:flex;gap:.7rem">
        <button type="submit" class="btn btn-primary" style="flex:1">Guardar</button>
        <button type="button" class="btn btn-ghost" onclick="resetForm()">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script>
  // Precarga los datos del restaurante en el formulario lateral para editarlo
  function editRest(r) {
    document.getElementById('id_edit').value = r.id_restaurante;
    document.getElementById('f-nombre').value = r.nombre;
    document.getElementById('f-direccion').value = r.direccion || '';
    document.getElementById('f-telefono').value = r.telefono || '';
    document.getElementById('f-categoria').value = r.categoria || '';
    document.getElementById('form-title').textContent = 'Editar Restaurante';
    document.getElementById('form-card').scrollIntoView({
      behavior: 'smooth'
    });
  }

  // Limpia el formulario y lo deja listo para crear un nuevo restaurante
  function resetForm() {
    document.getElementById('id_edit').value = 0;
    document.getElementById('f-nombre').value = '';
    document.getElementById('f-direccion').value = '';
    document.getElementById('f-telefono').value = '';
    document.getElementById('form-title').textContent = 'Nuevo Restaurante';
  }
</script>
</div>
</body>

</html>