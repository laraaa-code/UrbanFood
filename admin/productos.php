<?php
require_once '../includes/config.php';
requireAdmin();
$page_title = 'Productos';
$db = getDB();
$msg = '';
$msg_type = '';

// Crea un nuevo producto o actualiza uno existente segun si viene id_edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nombre       = trim($_POST['nombre'] ?? '');
  $precio       = floatval($_POST['precio'] ?? 0);
  $descripcion  = trim($_POST['descripcion'] ?? '');
  $id_rest      = intval($_POST['id_restaurante'] ?? 0);
  $id_edit      = intval($_POST['id_edit'] ?? 0);

  if ($nombre && $precio > 0 && $id_rest) {
    if ($id_edit) {
      $s = $db->prepare("UPDATE Productos SET nombre=?,precio=?,descripcion=?,id_restaurante=? WHERE id_producto=?");
      $s->bind_param("sdsii", $nombre, $precio, $descripcion, $id_rest, $id_edit);
      $s->execute();
      $msg = 'Producto actualizado.';
      $msg_type = 'success';
    } else {
      $s = $db->prepare("INSERT INTO Productos (nombre,precio,descripcion,id_restaurante) VALUES (?,?,?,?)");
      $s->bind_param("sdsi", $nombre, $precio, $descripcion, $id_rest);
      $s->execute();
      $msg = 'Producto creado.';
      $msg_type = 'success';
    }
  }
}

// Elimina un producto por ID recibido via GET
if (isset($_GET['delete'])) {
  $did = intval($_GET['delete']);
  $db->query("DELETE FROM Productos WHERE id_producto=$did");
  $msg = 'Producto eliminado.';
  $msg_type = 'success';
}

// Lista todos los productos con el nombre del restaurante al que pertenecen
$productos = $db->query("
    SELECT p.*, r.nombre AS restaurante
    FROM Productos p JOIN Restaurantes r ON p.id_restaurante=r.id_restaurante
    ORDER BY r.nombre, p.nombre
")->fetch_all(MYSQLI_ASSOC);

$restaurantes = $db->query("SELECT * FROM Restaurantes ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
$db->close();
include 'header.php';
?>
<div class="page-header">
  <h1>Productos</h1>
  <p>Gestiona el menú de cada restaurante</p>
</div>

<?php if ($msg): ?>
  <div class="alert alert-<?= $msg_type ?>"><?= $msg ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 360px;gap:1.5rem;align-items:start">
  <div class="card">
    <div class="card-header">
      <span class="card-title">Lista de Productos</span>
    </div>
    <table>
      <thead>
        <tr>
          <th>Producto</th>
          <th>Restaurante</th>
          <th>Precio</th>
          <th>Descripción</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($productos as $p): ?>
          <tr>
            <td><strong><?= htmlspecialchars($p['nombre']) ?></strong></td>
            <td style="color:var(--muted)"><?= htmlspecialchars($p['restaurante']) ?></td>
            <td style="color:var(--red);font-weight:700">$<?= number_format($p['precio'], 2) ?></td>
            <td style="color:var(--muted);font-size:.8rem"><?= htmlspecialchars(substr($p['descripcion'] ?? '', 0, 40)) ?></td>
            <td>
              <button class="btn btn-edit btn-sm" onclick='editProd(<?= json_encode($p) ?>)'>✏️</button>
              <a href="?delete=<?= $p['id_producto'] ?>" class="btn btn-delete btn-sm" onclick="return confirm('¿Eliminar?')">🗑️</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Formulario lateral compartido para crear y editar productos -->
  <div class="card" id="form-card">
    <div class="card-header">
      <span class="card-title" id="form-title">Nuevo Producto</span>
    </div>
    <form method="POST">
      <input type="hidden" name="id_edit" id="id_edit" value="0">
      <div class="form-group">
        <label>Nombre</label>
        <input type="text" name="nombre" id="f-nombre" placeholder="Nombre del producto" required>
      </div>
      <div class="form-group">
        <label>Precio ($)</label>
        <input type="number" step="0.01" name="precio" id="f-precio" placeholder="0.00" required>
      </div>
      <div class="form-group">
        <label>Descripción</label>
        <input type="text" name="descripcion" id="f-desc" placeholder="Descripción corta">
      </div>
      <div class="form-group">
        <label>Restaurante</label>
        <select name="id_restaurante" id="f-rest" required>
          <option value="">Seleccionar...</option>
          <?php foreach ($restaurantes as $r): ?>
            <option value="<?= $r['id_restaurante'] ?>"><?= htmlspecialchars($r['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="display:flex;gap:.7rem">
        <button type="submit" class="btn btn-primary" style="flex:1">Guardar</button>
        <button type="button" class="btn btn-ghost" onclick="resetForm()">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script>
  // Precarga los datos del producto en el formulario lateral para editarlo
  function editProd(p) {
    document.getElementById('id_edit').value = p.id_producto;
    document.getElementById('f-nombre').value = p.nombre;
    document.getElementById('f-precio').value = p.precio;
    document.getElementById('f-desc').value = p.descripcion || '';
    document.getElementById('f-rest').value = p.id_restaurante;
    document.getElementById('form-title').textContent = 'Editar Producto';
    document.getElementById('form-card').scrollIntoView({
      behavior: 'smooth'
    });
  }

  // Limpia el formulario y lo deja listo para crear un nuevo producto
  function resetForm() {
    document.getElementById('id_edit').value = 0;
    document.getElementById('f-nombre').value = '';
    document.getElementById('f-precio').value = '';
    document.getElementById('f-desc').value = '';
    document.getElementById('f-rest').value = '';
    document.getElementById('form-title').textContent = 'Nuevo Producto';
  }
</script>
</div>
</body>

</html>