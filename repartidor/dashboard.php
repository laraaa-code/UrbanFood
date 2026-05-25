<?php
require_once '../includes/config.php';

if (!isset($_SESSION['repartidor_id'])) {
    header('Location: login.php');
    exit;
}

$db = getDB();
$rid = $_SESSION['repartidor_id'];

// Cambiar estado del pedido activo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_estado'])) {
    $pid    = intval($_POST['pedido_id']);
    $estado = $_POST['nuevo_estado'];
    if (in_array($estado, ['en_camino', 'entregado'])) {
        $s = $db->prepare("UPDATE Pedidos SET estado=? WHERE id_pedido=? AND id_repartidor=?");
        $s->bind_param("sii", $estado, $pid, $rid);
        $s->execute();
    }
    header('Location: dashboard.php');
    exit;
}

// Pedido activo (pendiente o en_camino)
$activo_stmt = $db->prepare("
    SELECT p.*, c.nombre AS cliente, c.telefono AS cliente_tel, c.direccion AS cliente_dir,
           r.nombre AS restaurante, r.direccion AS rest_dir, r.telefono AS rest_tel
    FROM Pedidos p
    JOIN Clientes c ON p.id_cliente = c.id_cliente
    JOIN Restaurantes r ON p.id_restaurante = r.id_restaurante
    WHERE p.id_repartidor = ? AND p.estado IN ('pendiente','en_camino')
    ORDER BY p.fecha DESC LIMIT 1
");
$activo_stmt->bind_param("i", $rid);
$activo_stmt->execute();
$pedido_activo = $activo_stmt->get_result()->fetch_assoc();

// Detalle del pedido activo
$detalle_activo = [];
if ($pedido_activo) {
    $d = $db->prepare("
        SELECT dp.*, pr.nombre AS producto
        FROM Detalle_Pedidos dp
        JOIN Productos pr ON dp.id_producto = pr.id_producto
        WHERE dp.id_pedido = ?
    ");
    $d->bind_param("i", $pedido_activo['id_pedido']);
    $d->execute();
    $detalle_activo = $d->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Historial (entregados)
$hist_stmt = $db->prepare("
    SELECT p.*, c.nombre AS cliente, r.nombre AS restaurante
    FROM Pedidos p
    JOIN Clientes c ON p.id_cliente = c.id_cliente
    JOIN Restaurantes r ON p.id_restaurante = r.id_restaurante
    WHERE p.id_repartidor = ? AND p.estado = 'entregado'
    ORDER BY p.fecha DESC
");
$hist_stmt->bind_param("i", $rid);
$hist_stmt->execute();
$historial = $hist_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Detalle de cada pedido del historial
$hist_detalles = [];
foreach ($historial as $h) {
    $d = $db->prepare("
        SELECT dp.*, pr.nombre AS producto
        FROM Detalle_Pedidos dp
        JOIN Productos pr ON dp.id_producto = pr.id_producto
        WHERE dp.id_pedido = ?
    ");
    $d->bind_param("i", $h['id_pedido']);
    $d->execute();
    $hist_detalles[$h['id_pedido']] = $d->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Stats rápidas
$stats = $db->prepare("
    SELECT COUNT(*) AS total, COALESCE(SUM(total),0) AS ganado
    FROM Pedidos WHERE id_repartidor=? AND estado='entregado'
");
$stats->bind_param("i", $rid);
$stats->execute();
$stats = $stats->get_result()->fetch_assoc();

$db->close();

$estado_labels = ['pendiente'=>'Pendiente','en_camino'=>'En camino','entregado'=>'Entregado'];
$estado_colors = ['pendiente'=>'#f59e0b','en_camino'=>'#3b82f6','entregado'=>'#22c55e'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UrbanFood – Mi Panel</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{
  --bg:#0D0D0D;--card:#1A1A1A;--card2:#222;--border:#2A2A2A;
  --text:#F5F5F5;--muted:#888;
  --green:#22c55e;--red:#FF3008;--yellow:#f59e0b;--blue:#3b82f6
}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}

/* NAV */
nav{background:#111;border-bottom:1px solid var(--border);padding:0 1.5rem;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50}
.nav-left{display:flex;align-items:center;gap:.8rem}
.nav-logo{font-family:'Sora',sans-serif;font-size:1.4rem;font-weight:800;color:var(--red)}
.nav-logo span{color:var(--text)}
.nav-badge{background:rgba(34,197,94,.15);border:1px solid rgba(34,197,94,.3);color:var(--green);padding:.2rem .7rem;border-radius:50px;font-size:.72rem;font-weight:700}
.nav-right{display:flex;align-items:center;gap:.8rem}
.nav-name{color:var(--muted);font-size:.85rem}
.nav-name strong{color:var(--text)}
.btn-out{background:transparent;border:1px solid var(--border);color:var(--muted);padding:.4rem .8rem;border-radius:8px;font-size:.8rem;cursor:pointer;transition:all .2s}
.btn-out:hover{border-color:var(--red);color:var(--red)}

.container{max-width:720px;margin:0 auto;padding:1.5rem}

/* STATS */
.stats{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem}
.stat{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:1.1rem 1.3rem}
.stat-val{font-family:'Sora',sans-serif;font-size:1.8rem;font-weight:800}
.stat-lbl{color:var(--muted);font-size:.8rem;margin-top:.2rem}

/* SECTION TITLE */
.section-title{font-family:'Sora',sans-serif;font-size:1rem;font-weight:700;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem}
.section-title::after{content:'';flex:1;height:1px;background:var(--border)}

/* PEDIDO ACTIVO */
.active-card{background:var(--card);border:1px solid var(--border);border-radius:18px;overflow:hidden;margin-bottom:2rem}
.active-card.en_camino{border-color:rgba(59,130,246,.4)}
.active-card.pendiente{border-color:rgba(245,158,11,.4)}

.active-top{padding:1.2rem 1.5rem;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border)}
.active-top h3{font-family:'Sora',sans-serif;font-weight:700;font-size:1rem}
.active-top p{color:var(--muted);font-size:.82rem;margin-top:.15rem}
.estado-pill{padding:.3rem .9rem;border-radius:50px;font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px}

.active-body{padding:1.3rem 1.5rem}
.info-row{display:flex;align-items:flex-start;gap:.6rem;margin-bottom:.9rem;font-size:.88rem}
.info-row .icon{font-size:1rem;margin-top:.05rem;flex-shrink:0}
.info-row .label{color:var(--muted);font-size:.78rem;margin-bottom:.1rem}
.info-row .value{color:var(--text);font-weight:500}

.items-list{background:#111;border-radius:12px;padding:1rem;margin-bottom:1.2rem}
.item-row{display:flex;justify-content:space-between;padding:.35rem 0;border-bottom:1px solid rgba(255,255,255,.05);font-size:.85rem}
.item-row:last-child{border-bottom:none;padding-top:.5rem;font-weight:700;color:var(--red)}

/* ESTADO BUTTONS */
.estado-btns{display:grid;gap:.8rem}
.btn-estado{border:none;padding:.9rem;border-radius:12px;font-family:'Sora',sans-serif;font-weight:700;font-size:.95rem;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:.5rem}
.btn-camino{background:rgba(59,130,246,.15);color:#60a5fa;border:1px solid rgba(59,130,246,.3)}
.btn-camino:hover{background:rgba(59,130,246,.3)}
.btn-entregado{background:rgba(34,197,94,.15);color:var(--green);border:1px solid rgba(34,197,94,.3)}
.btn-entregado:hover{background:rgba(34,197,94,.3)}

/* SIN PEDIDO ACTIVO */
.no-active{background:var(--card);border:1px solid var(--border);border-radius:18px;padding:2.5rem;text-align:center;margin-bottom:2rem;color:var(--muted)}
.no-active .icon{font-size:3rem;margin-bottom:.8rem;display:block}
.no-active p{font-size:.9rem}

/* HISTORIAL */
.hist-card{background:var(--card);border:1px solid var(--border);border-radius:14px;margin-bottom:.9rem;overflow:hidden;transition:border-color .2s}
.hist-card:hover{border-color:#3a3a3a}
.hist-header{padding:1rem 1.3rem;display:flex;align-items:center;justify-content:space-between;cursor:pointer}
.hist-header-left h4{font-weight:600;font-size:.92rem;margin-bottom:.2rem}
.hist-header-left p{color:var(--muted);font-size:.78rem}
.hist-header-right{display:flex;align-items:center;gap:.8rem}
.hist-total{font-family:'Sora',sans-serif;font-weight:700;color:var(--green);font-size:1rem}
.chevron{color:var(--muted);font-size:.8rem;transition:transform .3s}
.chevron.open{transform:rotate(180deg)}
.hist-detail{display:none;border-top:1px solid var(--border);padding:1rem 1.3rem}
.hist-detail.show{display:block}
.hist-item{display:flex;justify-content:space-between;font-size:.83rem;padding:.3rem 0;border-bottom:1px solid rgba(255,255,255,.04)}
.hist-item:last-child{border-bottom:none}
.hist-cliente{font-size:.8rem;color:var(--muted);margin-top:.7rem}
</style>
</head>
<body>
<nav>
  <div class="nav-left">
    <div class="nav-logo">Urban<span>Food</span></div>
    <span class="nav-badge">🛵 Repartidor</span>
  </div>
  <div class="nav-right">
    <span class="nav-name">Hola, <strong><?= htmlspecialchars($_SESSION['repartidor_nombre']) ?></strong></span>
    <form method="POST" action="logout.php">
      <button type="submit" class="btn-out">Salir</button>
    </form>
  </div>
</nav>

<div class="container">

  <!-- STATS -->
  <div class="stats">
    <div class="stat">
      <div class="stat-val"><?= $stats['total'] ?></div>
      <div class="stat-lbl">📦 Pedidos entregados</div>
    </div>
    <div class="stat">
      <div class="stat-val" style="color:var(--green)">$<?= number_format($stats['ganado'],2) ?></div>
      <div class="stat-lbl">💰 Total en pedidos</div>
    </div>
  </div>

  <!-- PEDIDO ACTIVO -->
  <div class="section-title">⚡ Pedido Activo</div>

  <?php if ($pedido_activo): ?>
  <div class="active-card <?= $pedido_activo['estado'] ?>">
    <div class="active-top">
      <div>
        <h3>Pedido #<?= $pedido_activo['id_pedido'] ?> · <?= htmlspecialchars($pedido_activo['restaurante']) ?></h3>
        <p><?= date('d/m/Y H:i', strtotime($pedido_activo['fecha'])) ?></p>
      </div>
      <?php
        $ec = $estado_colors[$pedido_activo['estado']];
        $el = $estado_labels[$pedido_activo['estado']];
      ?>
      <span class="estado-pill" style="background:<?= $ec ?>22;color:<?= $ec ?>;border:1px solid <?= $ec ?>44"><?= $el ?></span>
    </div>

    <div class="active-body">
      <div class="info-row">
        <span class="icon">🏪</span>
        <div>
          <div class="label">Recoger en</div>
          <div class="value"><?= htmlspecialchars($pedido_activo['restaurante']) ?> — <?= htmlspecialchars($pedido_activo['rest_dir'] ?? '') ?></div>
        </div>
      </div>
      <div class="info-row">
        <span class="icon">📍</span>
        <div>
          <div class="label">Entregar a</div>
          <div class="value"><?= htmlspecialchars($pedido_activo['cliente']) ?> — <?= htmlspecialchars($pedido_activo['cliente_dir'] ?? 'Sin dirección registrada') ?></div>
        </div>
      </div>
      <div class="info-row">
        <span class="icon">📞</span>
        <div>
          <div class="label">Teléfono cliente</div>
          <div class="value"><?= htmlspecialchars($pedido_activo['cliente_tel'] ?? '—') ?></div>
        </div>
      </div>

      <!-- Items -->
      <div class="items-list">
        <?php foreach($detalle_activo as $d): ?>
        <div class="item-row">
          <span><?= htmlspecialchars($d['producto']) ?></span>
          <span style="color:var(--muted)">×<?= $d['cantidad'] ?></span>
          <span>$<?= number_format($d['subtotal'],2) ?></span>
        </div>
        <?php endforeach; ?>
        <div class="item-row">
          <span>Total</span>
          <span></span>
          <span>$<?= number_format($pedido_activo['total'],2) ?></span>
        </div>
      </div>

      <!-- Botones de estado -->
      <form method="POST">
        <input type="hidden" name="pedido_id" value="<?= $pedido_activo['id_pedido'] ?>">
        <div class="estado-btns">
          <?php if($pedido_activo['estado'] === 'pendiente'): ?>
          <button type="submit" name="nuevo_estado" value="en_camino" class="btn-estado btn-camino">
            🛵 Marcar como En camino
          </button>
          <?php endif; ?>
          <?php if(in_array($pedido_activo['estado'], ['pendiente','en_camino'])): ?>
          <button type="submit" name="nuevo_estado" value="entregado" class="btn-estado btn-entregado"
            onclick="return confirm('¿Confirmar entrega del pedido #<?= $pedido_activo['id_pedido'] ?>?')">
            ✅ Marcar como Entregado
          </button>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <?php else: ?>
  <div class="no-active">
    <span class="icon">😴</span>
    <p>No tienes un pedido activo en este momento.<br>Espera a que se te asigne uno.</p>
  </div>
  <?php endif; ?>

  <!-- HISTORIAL -->
  <div class="section-title">📋 Historial de Pedidos</div>

  <?php if(empty($historial)): ?>
  <div style="text-align:center;color:var(--muted);padding:2rem;font-size:.9rem">
    Aún no tienes pedidos entregados.
  </div>
  <?php else: ?>
  <?php foreach($historial as $h): ?>
  <div class="hist-card">
    <div class="hist-header" onclick="toggle(<?= $h['id_pedido'] ?>)">
      <div class="hist-header-left">
        <h4>🏪 <?= htmlspecialchars($h['restaurante']) ?></h4>
        <p>#<?= $h['id_pedido'] ?> · <?= date('d/m/Y H:i', strtotime($h['fecha'])) ?> · <?= htmlspecialchars($h['cliente']) ?></p>
      </div>
      <div class="hist-header-right">
        <span class="hist-total">$<?= number_format($h['total'],2) ?></span>
        <span class="chevron" id="chev-<?= $h['id_pedido'] ?>">▼</span>
      </div>
    </div>
    <div class="hist-detail" id="det-<?= $h['id_pedido'] ?>">
      <?php foreach($hist_detalles[$h['id_pedido']] as $d): ?>
      <div class="hist-item">
        <span><?= htmlspecialchars($d['producto']) ?></span>
        <span style="color:var(--muted)">×<?= $d['cantidad'] ?></span>
        <span style="color:var(--green)">$<?= number_format($d['subtotal'],2) ?></span>
      </div>
      <?php endforeach; ?>
      <div class="hist-cliente">👤 Cliente: <?= htmlspecialchars($h['cliente']) ?></div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>

</div>

<script>
function toggle(id) {
  const det  = document.getElementById('det-'+id);
  const chev = document.getElementById('chev-'+id);
  det.classList.toggle('show');
  chev.classList.toggle('open');
}
</script>
</body>
</html>
