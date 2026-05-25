<?php
date_default_timezone_set('America/El_Salvador');
require_once '../includes/config.php';
requireAdmin();
$page_title = 'Respaldo BD';

$backups_dir = __DIR__ . '/backups';
$schedule_file = $backups_dir . '/schedule.json';

if (!is_dir($backups_dir)) {
    mkdir($backups_dir, 0755, true);
}

function leerProgramacion($schedule_file) {
    if (!file_exists($schedule_file)) return null;
    $data = json_decode(file_get_contents($schedule_file), true);
    return $data;
}

function guardarProgramacion($schedule_file, $data) {
    file_put_contents($schedule_file, json_encode($data, JSON_PRETTY_PRINT));
}

function calcularProximaEjecucion($frecuencia, $hora) {
    $ahora = time();
    $hoy = date('Y-m-d');
    $ts_hoy = strtotime("$hoy $hora:00");

    switch ($frecuencia) {
        case 'diario':
            $proximo = $ts_hoy > $ahora ? $ts_hoy : $ts_hoy + 86400;
            break;
        case 'semanal':
            $proximo = strtotime('next monday ' . $hora . ':00');
            break;
        case 'quincenal':
            $proximo = $ts_hoy > $ahora ? $ts_hoy : $ts_hoy + (15 * 86400);
            break;
        case 'mensual':
            $proximo = strtotime('+1 month ' . $hora . ':00');
            break;
        default:
            $proximo = $ts_hoy + 86400;
    }
    return $proximo;
}

function generarRespaldo($directorio) {
    $db = getDB();
    $fecha = date('Ymd_His');
    $archivo = $directorio . '/respaldo_' . $fecha . '.sql';

    $sql = "-- Respaldo UrbanFoodDB\n-- Fecha: " . date('Y-m-d H:i:s') . "\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";

    $tablas = $db->query("SHOW TABLES");
    while ($fila = $tablas->fetch_row()) {
        $tabla = $fila[0];
        $create = $db->query("SHOW CREATE TABLE `$tabla`")->fetch_row();
        $sql .= "DROP TABLE IF EXISTS `$tabla`;\n" . $create[1] . ";\n\n";

        $datos = $db->query("SELECT * FROM `$tabla`");
        while ($row = $datos->fetch_assoc()) {
            $valores = [];
            foreach ($row as $val) {
                $valores[] = $val === null ? 'NULL' : "'" . $db->real_escape_string($val) . "'";
            }
            $sql .= "INSERT INTO `$tabla` VALUES (" . implode(', ', $valores) . ");\n";
        }
        $sql .= "\n";
    }

    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    $db->close();

    if (file_put_contents($archivo, $sql)) {
        return 'respaldo_' . $fecha . '.sql';
    }
    return false;
}

$mensaje = '';
$error = '';

$programacion = leerProgramacion($schedule_file);
if ($programacion && $programacion['activo']) {
    $ahora = time();
    $proximo = $programacion['proximo_ts'];
    if ($ahora >= $proximo) {
        $resultado = generarRespaldo($backups_dir);
        if ($resultado) {
            $programacion['ultimo_respaldo'] = date('Y-m-d H:i:s');
            $programacion['ultimo_archivo'] = $resultado;
            $programacion['proximo_ts'] = calcularProximaEjecucion($programacion['frecuencia'], $programacion['hora']);
            guardarProgramacion($schedule_file, $programacion);
            $mensaje = "✅ Respaldo automático ejecutado: $resultado";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['respaldar'])) {
        $dir_custom = trim($_POST['directorio_manual'] ?? '');
        $dir_destino = ($dir_custom !== '') ? rtrim($dir_custom, '/\\') : $backups_dir;

        if (!is_dir($dir_destino)) {
            if (!mkdir($dir_destino, 0755, true)) {
                $error = "No se pudo crear el directorio: $dir_destino";
            }
        }

        if (!$error) {
            $resultado = generarRespaldo($dir_destino);
            if ($resultado) {
                $mensaje = "Respaldo creado: $resultado — Ubicación: $dir_destino";
            } else {
                $error = "No se pudo guardar el archivo. Verifica los permisos del directorio.";
            }
        }
    }

    if (isset($_POST['guardar_programacion'])) {
        $frecuencia = $_POST['frecuencia'] ?? 'diario';
        $hora = $_POST['hora'] ?? '02:00';
        $hora = substr($hora, 0, 5);

        $nueva = [
            'activo' => true,
            'frecuencia' => $frecuencia,
            'hora' => $hora,
            'directorio' => $backups_dir,
            'creado' => date('Y-m-d H:i:s'),
            'ultimo_respaldo' => $programacion['ultimo_respaldo'] ?? null,
            'ultimo_archivo' => $programacion['ultimo_archivo'] ?? null,
            'proximo_ts' => calcularProximaEjecucion($frecuencia, $hora),
        ];
        guardarProgramacion($schedule_file, $nueva);
        $programacion = $nueva;
        $mensaje = "Programación guardada. Próximo respaldo: " . date('d/m/Y H:i', $nueva['proximo_ts']);
    }

    if (isset($_POST['desactivar'])) {
        if ($programacion) {
            $programacion['activo'] = false;
            guardarProgramacion($schedule_file, $programacion);
            $mensaje = "Programación de respaldos automáticos desactivada.";
        }
    }

    if (isset($_POST['activar'])) {
        if ($programacion) {
            $programacion['activo'] = true;
            $programacion['proximo_ts'] = calcularProximaEjecucion($programacion['frecuencia'], $programacion['hora']);
            guardarProgramacion($schedule_file, $programacion);
            $mensaje = "Programación activada. Próximo respaldo: " . date('d/m/Y H:i', $programacion['proximo_ts']);
        }
    }

    if (isset($_POST['eliminar_archivo'])) {
        $archivo = basename($_POST['eliminar_archivo']);
        $ruta = $backups_dir . '/' . $archivo;
        if (file_exists($ruta) && pathinfo($ruta, PATHINFO_EXTENSION) === 'sql') {
            unlink($ruta);
            $mensaje = "Archivo $archivo eliminado.";
        } else {
            $error = "No se pudo eliminar el archivo.";
        }
    }
}

$programacion = leerProgramacion($schedule_file);

$respaldos = [];
if (is_dir($backups_dir)) {
    $archivos = glob($backups_dir . '/respaldo_*.sql');
    if ($archivos) {
        rsort($archivos);
        foreach ($archivos as $ruta) {
            $respaldos[] = [
                'nombre' => basename($ruta),
                'size' => round(filesize($ruta) / 1024, 1),
                'fecha' => date('d/m/Y H:i', filemtime($ruta)),
            ];
        }
    }
}

$frecuencias = [
    'diario' => 'Diario',
    'semanal' => 'Semanal (lunes)',
    'quincenal' => 'Quincenal',
    'mensual' => 'Mensual',
];

include 'header.php';
?>

<style>
  .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
  .section-title { font-family: 'Sora', sans-serif; font-size: .95rem; font-weight: 700; margin-bottom: 1rem; display: flex; align-items: center; gap: .5rem; }
  .schedule-status { display: flex; align-items: center; gap: .8rem; padding: 1rem 1.2rem; border-radius: 12px; margin-bottom: 1.2rem; }
  .schedule-status.active { background: rgba(34,197,94,.08); border: 1px solid rgba(34,197,94,.2); }
  .schedule-status.inactive { background: rgba(102,102,102,.08); border: 1px solid rgba(102,102,102,.2); }
  .status-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
  .status-dot.on { background: #22c55e; box-shadow: 0 0 8px #22c55e88; animation: pulse 2s infinite; }
  .status-dot.off { background: #555; }
  @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.5} }
  .status-text { flex: 1; }
  .status-text strong { font-size: .9rem; display: block; }
  .status-text span { font-size: .78rem; color: var(--muted); }
  .info-row { display: flex; justify-content: space-between; align-items: center; padding: .55rem 0; border-bottom: 1px solid rgba(255,255,255,.04); font-size: .84rem; }
  .info-row:last-child { border-bottom: none; }
  .info-label { color: var(--muted); }
  .info-val { font-weight: 600; }
  .badge-activo { background: rgba(34,197,94,.12); color: #4ade80; border: 1px solid rgba(34,197,94,.25); padding: .15rem .6rem; border-radius: 50px; font-size: .7rem; font-weight: 700; text-transform: uppercase; }
  .badge-inactivo { background: rgba(102,102,102,.12); color: #888; border: 1px solid rgba(102,102,102,.25); padding: .15rem .6rem; border-radius: 50px; font-size: .7rem; font-weight: 700; text-transform: uppercase; }
  .btn-danger { background: rgba(255,48,8,.1); color: #ff6b55; border: 1px solid rgba(255,48,8,.2); }
  .btn-danger:hover { background: rgba(255,48,8,.2); }
  .btn-success { background: rgba(34,197,94,.12); color: #4ade80; border: 1px solid rgba(34,197,94,.25); }
  .btn-success:hover { background: rgba(34,197,94,.2); }
  .empty-state { text-align: center; padding: 2rem; color: var(--muted); font-size: .85rem; }
  .file-row { display: flex; align-items: center; gap: .8rem; padding: .65rem 0; border-bottom: 1px solid rgba(255,255,255,.04); }
  .file-row:last-child { border-bottom: none; }
  .file-icon { font-size: 1.2rem; }
  .file-info { flex: 1; min-width: 0; }
  .file-name { font-size: .84rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .file-meta { font-size: .75rem; color: var(--muted); margin-top: .1rem; }
  .countdown { font-family: 'Sora', sans-serif; font-size: 1.05rem; font-weight: 700; color: var(--accent); }
  .hint { font-size: .75rem; color: var(--muted); margin-top: .5rem; line-height: 1.5; }
</style>

<div class="page-header">
  <h1>💾 Respaldo de Base de Datos</h1>
  <p>Genera respaldos manuales o programa copias de seguridad automáticas</p>
</div>

<?php if ($mensaje): ?>
  <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="grid-2" style="margin-bottom:1.5rem">

  <div class="card">
    <div class="section-title">⚡ Respaldo Manual</div>
    <form method="POST">
      <div class="form-group">
        <label>Ubicación donde guardar <span style="color:var(--accent);font-size:.72rem;text-transform:none;letter-spacing:0">(opcional)</span></label>
        <input type="text" name="directorio_manual" placeholder="Dejar vacío → /admin/backups/">
        <p class="hint">📁 Si se deja vacío, se guarda automáticamente en la carpeta por defecto. El archivo se llamará <code style="background:#1a1a26;padding:.1rem .3rem;border-radius:4px">respaldo_AAAAMMDD_HHMMSS.sql</code></p>
      </div>
      <button type="submit" name="respaldar" class="btn btn-primary" style="width:100%;justify-content:center;padding:.75rem">
        💾 Generar Respaldo Ahora
      </button>
    </form>
  </div>

  <div class="card">
    <div class="section-title">🕐 Programación Automática</div>
    <?php if ($programacion): ?>
      <div class="schedule-status <?= $programacion['activo'] ? 'active' : 'inactive' ?>">
        <div class="status-dot <?= $programacion['activo'] ? 'on' : 'off' ?>"></div>
        <div class="status-text">
          <strong><?= $programacion['activo'] ? 'Activo' : 'Inactivo' ?></strong>
          <span><?= $programacion['activo'] ? 'Los respaldos se ejecutan automáticamente' : 'La programación está pausada' ?></span>
        </div>
        <span class="badge-<?= $programacion['activo'] ? 'activo' : 'inactivo' ?>"><?= $programacion['activo'] ? 'ON' : 'OFF' ?></span>
      </div>

      <div class="info-row">
        <span class="info-label">Frecuencia</span>
        <span class="info-val"><?= $frecuencias[$programacion['frecuencia']] ?? $programacion['frecuencia'] ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">Hora programada</span>
        <span class="info-val"><?= htmlspecialchars($programacion['hora']) ?></span>
      </div>
      <?php if ($programacion['activo']): ?>
      <div class="info-row">
        <span class="info-label">Próximo respaldo</span>
        <span class="countdown"><?= date('d/m/Y H:i', $programacion['proximo_ts']) ?></span>
      </div>
      <?php endif; ?>
      <?php if (!empty($programacion['ultimo_respaldo'])): ?>
      <div class="info-row">
        <span class="info-label">Último respaldo</span>
        <span class="info-val" style="color:var(--green)"><?= htmlspecialchars($programacion['ultimo_respaldo']) ?></span>
      </div>
      <?php endif; ?>

      <div style="display:flex;gap:.6rem;margin-top:1.2rem">
        <?php if ($programacion['activo']): ?>
          <form method="POST" style="flex:1">
            <button type="submit" name="desactivar" class="btn btn-danger" style="width:100%;justify-content:center">⏸ Pausar</button>
          </form>
        <?php else: ?>
          <form method="POST" style="flex:1">
            <button type="submit" name="activar" class="btn btn-success" style="width:100%;justify-content:center">▶ Activar</button>
          </form>
        <?php endif; ?>
        <button class="btn btn-ghost" onclick="document.getElementById('modal-programar').classList.add('show')" style="flex:1;justify-content:center">⚙ Editar</button>
      </div>
    <?php else: ?>
      <div style="text-align:center;padding:1.5rem 0">
        <div style="font-size:2.5rem;margin-bottom:.8rem">🗓️</div>
        <p style="color:var(--muted);font-size:.85rem;margin-bottom:1.2rem">No hay ninguna programación configurada.</p>
        <button class="btn btn-primary" onclick="document.getElementById('modal-programar').classList.add('show')">+ Configurar Respaldo Automático</button>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <span class="card-title">📁 Historial de Respaldos</span>
    <span style="font-size:.8rem;color:var(--muted)"><?= count($respaldos) ?> archivo<?= count($respaldos) !== 1 ? 's' : '' ?> · <code style="background:#1a1a26;padding:.1rem .4rem;border-radius:5px">/admin/backups/</code></span>
  </div>

  <?php if (empty($respaldos)): ?>
    <div class="empty-state">
      <div style="font-size:2rem;margin-bottom:.5rem">🗄️</div>
      <p>No hay respaldos aún. Genera uno manual o configura la programación automática.</p>
    </div>
  <?php else: ?>
    <?php foreach ($respaldos as $r): ?>
      <div class="file-row">
        <div class="file-icon">🗃️</div>
        <div class="file-info">
          <div class="file-name"><?= htmlspecialchars($r['nombre']) ?></div>
          <div class="file-meta"><?= $r['fecha'] ?> · <?= $r['size'] ?> KB</div>
        </div>
        <form method="POST" onsubmit="return confirm('¿Eliminar este respaldo?')">
          <input type="hidden" name="eliminar_archivo" value="<?= htmlspecialchars($r['nombre']) ?>">
          <button type="submit" class="btn btn-sm btn-delete">🗑 Eliminar</button>
        </form>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<div class="modal-bg" id="modal-programar">
  <div class="modal" style="max-width:440px">
    <h3>🗓️ Programar Respaldo Automático</h3>

    <form method="POST">
      <div class="form-group">
        <label>Frecuencia</label>
        <select name="frecuencia">
          <?php foreach ($frecuencias as $val => $label): ?>
            <option value="<?= $val ?>" <?= ($programacion['frecuencia'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Hora del respaldo</label>
        <input type="time" name="hora" value="<?= htmlspecialchars($programacion['hora'] ?? '02:00') ?>">
        <p class="hint">💡 Se recomienda programarlo en horas de baja actividad (ej. 2:00 AM). El respaldo se ejecuta automáticamente cuando alguien accede al panel después de la hora programada.</p>
      </div>

      <div style="background:rgba(124,58,237,.08);border:1px solid rgba(124,58,237,.2);border-radius:10px;padding:.9rem 1rem;font-size:.8rem;color:#a78bfa;margin-bottom:1.2rem;line-height:1.6">
        ℹ️ <strong>¿Cómo funciona?</strong> El sistema verifica automáticamente si ya pasó la hora programada cada vez que se abre esta página. Para ejecución garantizada sin intervención, configura una tarea Cron en el servidor apuntando a esta URL.
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('modal-programar').classList.remove('show')">Cancelar</button>
        <button type="submit" name="guardar_programacion" class="btn btn-primary">💾 Guardar Programación</button>
      </div>
    </form>
  </div>
</div>

<script>
  document.getElementById('modal-programar').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('show');
  });
  <?php if ($programacion && $programacion['activo']): ?>
  setTimeout(function() { location.reload(); }, 60000);
  <?php endif; ?>
</script>

</div>
</body>
</html>
