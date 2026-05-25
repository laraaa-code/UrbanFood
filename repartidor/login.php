<?php
require_once '../includes/config.php';

if (isset($_SESSION['repartidor_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $telefono = trim($_POST['telefono'] ?? '');

    if ($telefono) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM Repartidores WHERE telefono = ? AND activo = 1");
        $stmt->bind_param("s", $telefono);
        $stmt->execute();
        $rep = $stmt->get_result()->fetch_assoc();
        $db->close();

        if ($rep) {
            $_SESSION['repartidor_id']     = $rep['id_repartidor'];
            $_SESSION['repartidor_nombre'] = $rep['nombre'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Número no encontrado o repartidor inactivo.';
        }
    } else {
        $error = 'Ingresa tu número de teléfono.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UrbanFood – Repartidor</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{--bg:#0D0D0D;--card:#1A1A1A;--border:#2A2A2A;--text:#F5F5F5;--muted:#888;--green:#22c55e;--red:#FF3008}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1.5rem}
body::before{content:'';position:fixed;top:-150px;left:50%;transform:translateX(-50%);width:500px;height:500px;background:radial-gradient(circle,rgba(34,197,94,.12) 0%,transparent 70%);pointer-events:none}

.wrapper{width:100%;max-width:400px;z-index:1}
.brand{text-align:center;margin-bottom:2rem}
.brand-logo{font-family:'Sora',sans-serif;font-size:2rem;font-weight:800;color:var(--red)}
.brand-logo span{color:var(--text)}
.brand-sub{display:inline-block;margin-top:.4rem;background:rgba(34,197,94,.15);border:1px solid rgba(34,197,94,.3);color:var(--green);padding:.25rem .9rem;border-radius:50px;font-size:.78rem;font-weight:700;letter-spacing:.5px}

.card{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:2.5rem;box-shadow:0 20px 60px rgba(0,0,0,.5)}
.card h2{font-family:'Sora',sans-serif;font-size:1.4rem;font-weight:700;margin-bottom:.3rem}
.card .sub{color:var(--muted);font-size:.87rem;margin-bottom:2rem}

.form-group{margin-bottom:1.2rem}
.form-group label{display:block;font-size:.8rem;font-weight:600;color:var(--muted);margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.5px}
.form-group input{width:100%;background:#111;border:1px solid var(--border);color:var(--text);padding:.9rem 1.1rem;border-radius:12px;font-size:1rem;outline:none;font-family:'Inter',sans-serif;transition:border-color .2s,box-shadow .2s}
.form-group input:focus{border-color:var(--green);box-shadow:0 0 0 3px rgba(34,197,94,.12)}

.btn{width:100%;background:var(--green);color:#0D0D0D;border:none;padding:1rem;border-radius:12px;font-size:1rem;font-family:'Sora',sans-serif;font-weight:800;cursor:pointer;transition:all .2s}
.btn:hover{opacity:.9;transform:translateY(-1px);box-shadow:0 6px 20px rgba(34,197,94,.35)}

.error{background:rgba(255,48,8,.1);border:1px solid rgba(255,48,8,.25);color:#ff6b55;padding:.8rem 1rem;border-radius:10px;font-size:.87rem;margin-bottom:1.2rem}

.back{text-align:center;margin-top:1.5rem}
.back a{color:var(--muted);font-size:.82rem;text-decoration:none}
.back a:hover{color:var(--text)}

.manual-section{
  margin-top:1.5rem;
  display:flex;
  gap:1rem;
  justify-content:center;
  flex-wrap:wrap;
}

.manual-btn{
  text-decoration:none;
  background:#111;
  border:1px solid var(--border);
  color:var(--text);
  padding:.8rem 1rem;
  border-radius:12px;
  font-size:.85rem;
  transition:.2s;
}

.manual-btn:hover{
  border-color:var(--green);
  transform:translateY(-1px);
}

.manual-btn.download{
  background:rgba(34,197,94,.12);
}
</style>
</head>
<body>
<div class="wrapper">
  <div class="brand">
    <div class="brand-logo">Urban<span>Food</span></div>
    <div class="brand-sub">🛵 Portal Repartidor</div>
  </div>

  <div class="card">
    <h2>Ingresa tu número</h2>
    <p class="sub">Usa el teléfono registrado en el sistema para acceder</p>

    <?php if($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Teléfono registrado</label>
        <input type="text" name="telefono" placeholder="7000-0000" required autofocus value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>">
      </div>
      <button type="submit" class="btn">Entrar →</button>
    </form>

    <div class="manual-section">
  <a href="../manuales/manual_repartidor.pdf" target="_blank" class="manual-btn">
    📘 Ver Manual
  </a>

  <a href="../manuales/manual_repartidor.pdf" download class="manual-btn download">
    ⬇ Descargar Manual
  </a>
</div>
  </div>
  
</div>
</body>
</html>
