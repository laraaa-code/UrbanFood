<?php
require_once 'includes/config.php';
requireLogin();

// Obtiene el ID del restaurante desde la URL y lo convierte a entero para evitar inyeccion SQL
$id = intval($_GET['id'] ?? 0);
$db = getDB();

// Busca los datos del restaurante; si no existe redirige al inicio
$rest = $db->prepare("SELECT * FROM Restaurantes WHERE id_restaurante = ?");
$rest->bind_param("i", $id);
$rest->execute();
$restaurante = $rest->get_result()->fetch_assoc();
if (!$restaurante) {
  header('Location: index.php');
  exit;
}

// Carga todos los productos del restaurante ordenados por nombre
$productos = $db->query("SELECT * FROM Productos WHERE id_restaurante = $id ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
$db->close();

// Mapa de categoria a emoji para el encabezado visual del restaurante
$emojis = ['Pizza' => '🍕', 'Hamburguesas' => '🍔', 'Pollo' => '🍗', 'Sandwiches' => '🥖', 'Sushi' => '🍣', 'Tacos' => '🌮', 'Otros' => '🍽️'];
$em = $emojis[$restaurante['categoria']] ?? '🍽️';
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($restaurante['nombre']) ?> – UrbanFood</title>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box
    }

    :root {
      --red: #FF3008;
      --red-dark: #D4290A;
      --bg: #0D0D0D;
      --card: #1A1A1A;
      --card2: #222;
      --border: #2A2A2A;
      --text: #F5F5F5;
      --muted: #888;
      --green: #22c55e
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

    .nav-back {
      color: var(--muted);
      text-decoration: none;
      font-size: .9rem;
      display: flex;
      align-items: center;
      gap: .4rem
    }

    .nav-back:hover {
      color: var(--text)
    }

    .hero-rest {
      height: 220px;
      background: linear-gradient(135deg, #1e1e1e, #2a2a2a);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 6rem;
      position: relative
    }

    .hero-rest::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(to top, rgba(0, 0, 0, .8), transparent 60%)
    }

    .hero-info {
      position: absolute;
      bottom: 1.5rem;
      left: 2rem;
      z-index: 2
    }

    .hero-info h1 {
      font-family: 'Sora', sans-serif;
      font-size: 1.8rem;
      font-weight: 800
    }

    .hero-info p {
      color: #ccc;
      font-size: .88rem;
      margin-top: .2rem
    }

    .layout {
      max-width: 1100px;
      margin: 0 auto;
      padding: 2rem;
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 2rem;
      align-items: start
    }

    .menu-section h2 {
      font-family: 'Sora', sans-serif;
      font-size: 1.2rem;
      font-weight: 700;
      margin-bottom: 1.2rem;
      display: flex;
      align-items: center;
      gap: .5rem
    }

    .menu-section h2::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--border);
      margin-left: .5rem
    }

    .product-list {
      display: flex;
      flex-direction: column;
      gap: 1rem
    }

    .product-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 1.2rem 1.3rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      transition: border-color .2s
    }

    .product-card:hover {
      border-color: #444
    }

    .product-info {
      flex: 1
    }

    .product-name {
      font-weight: 600;
      font-size: .97rem;
      margin-bottom: .25rem
    }

    .product-desc {
      color: var(--muted);
      font-size: .82rem;
      line-height: 1.4
    }

    .product-price {
      color: var(--red);
      font-family: 'Sora', sans-serif;
      font-weight: 700;
      font-size: 1.1rem;
      white-space: nowrap
    }

    .qty-ctrl {
      display: flex;
      align-items: center;
      gap: .5rem
    }

    .qty-btn {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      border: 1px solid var(--border);
      background: transparent;
      color: var(--text);
      font-size: 1.1rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all .2s
    }

    .qty-btn:hover {
      background: var(--red);
      border-color: var(--red)
    }

    .qty-num {
      font-family: 'Sora', sans-serif;
      font-weight: 700;
      min-width: 20px;
      text-align: center;
      font-size: .95rem
    }

    .cart-box {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 1.5rem;
      position: sticky;
      top: 84px
    }

    .cart-title {
      font-family: 'Sora', sans-serif;
      font-size: 1.1rem;
      font-weight: 700;
      margin-bottom: 1.2rem;
      display: flex;
      align-items: center;
      justify-content: space-between
    }

    .cart-badge {
      background: var(--red);
      color: #fff;
      font-size: .75rem;
      padding: .15rem .5rem;
      border-radius: 50px;
      font-weight: 700
    }

    .cart-items {
      min-height: 80px;
      margin-bottom: 1rem
    }

    .cart-empty {
      color: var(--muted);
      font-size: .88rem;
      text-align: center;
      padding: 1.5rem 0
    }

    .cart-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: .5rem 0;
      border-bottom: 1px solid var(--border);
      font-size: .88rem
    }

    .cart-item:last-child {
      border-bottom: none
    }

    .cart-item-name {
      flex: 1;
      color: var(--text)
    }

    .cart-item-qty {
      color: var(--muted);
      margin: 0 .5rem
    }

    .cart-item-price {
      color: var(--red);
      font-weight: 600;
      white-space: nowrap
    }

    .cart-remove {
      color: var(--muted);
      cursor: pointer;
      margin-left: .5rem;
      font-size: .85rem
    }

    .cart-remove:hover {
      color: var(--red)
    }

    .cart-divider {
      height: 1px;
      background: var(--border);
      margin: 1rem 0
    }

    .cart-total {
      display: flex;
      justify-content: space-between;
      font-family: 'Sora', sans-serif;
      font-weight: 700;
      font-size: 1.1rem;
      margin-bottom: 1.2rem
    }

    .cart-total span:last-child {
      color: var(--red)
    }

    .btn-checkout {
      width: 100%;
      background: var(--red);
      color: #fff;
      border: none;
      padding: 1rem;
      border-radius: 12px;
      font-size: 1rem;
      font-family: 'Sora', sans-serif;
      font-weight: 700;
      cursor: pointer;
      transition: all .2s
    }

    .btn-checkout:hover {
      background: var(--red-dark);
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(255, 48, 8, .4)
    }

    .btn-checkout:disabled {
      background: #333;
      color: var(--muted);
      cursor: not-allowed;
      transform: none;
      box-shadow: none
    }

    .modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .7);
      backdrop-filter: blur(4px);
      z-index: 1000;
      align-items: center;
      justify-content: center
    }

    .modal-overlay.show {
      display: flex
    }

    .modal {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 2rem;
      max-width: 440px;
      width: 90%;
      text-align: center
    }

    .modal-icon {
      font-size: 3rem;
      margin-bottom: 1rem
    }

    .modal h3 {
      font-family: 'Sora', sans-serif;
      font-size: 1.3rem;
      font-weight: 700;
      margin-bottom: .5rem
    }

    .modal p {
      color: var(--muted);
      font-size: .9rem;
      margin-bottom: 1.5rem
    }

    .modal-info {
      background: #111;
      border-radius: 12px;
      padding: 1rem;
      margin-bottom: 1.5rem;
      text-align: left;
      font-size: .88rem
    }

    .modal-info div {
      display: flex;
      justify-content: space-between;
      padding: .3rem 0;
      border-bottom: 1px solid var(--border)
    }

    .modal-info div:last-child {
      border-bottom: none;
      font-weight: 700;
      color: var(--red)
    }

    .modal-btns {
      display: flex;
      gap: 1rem
    }

    .btn-confirm {
      flex: 1;
      background: var(--red);
      color: #fff;
      border: none;
      padding: .9rem;
      border-radius: 12px;
      font-family: 'Sora', sans-serif;
      font-weight: 700;
      cursor: pointer;
      font-size: .95rem;
      transition: all .2s
    }

    .btn-confirm:hover {
      background: var(--red-dark)
    }

    .btn-cancel {
      flex: 1;
      background: transparent;
      color: var(--muted);
      border: 1px solid var(--border);
      padding: .9rem;
      border-radius: 12px;
      font-family: 'Sora', sans-serif;
      font-weight: 600;
      cursor: pointer;
      font-size: .95rem;
      transition: all .2s
    }

    .btn-cancel:hover {
      border-color: #555;
      color: var(--text)
    }

    .success-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .8);
      z-index: 2000;
      align-items: center;
      justify-content: center
    }

    .success-overlay.show {
      display: flex
    }

    .success-box {
      background: var(--card);
      border: 1px solid rgba(34, 197, 94, .3);
      border-radius: 20px;
      padding: 2.5rem;
      max-width: 380px;
      width: 90%;
      text-align: center
    }

    .success-box .big-icon {
      font-size: 4rem;
      margin-bottom: 1rem;
      animation: bounce .6s ease
    }

    @keyframes bounce {

      0%,
      100% {
        transform: scale(1)
      }

      50% {
        transform: scale(1.2)
      }
    }

    .success-box h3 {
      font-family: 'Sora', sans-serif;
      font-size: 1.4rem;
      font-weight: 800;
      color: var(--green);
      margin-bottom: .5rem
    }

    .success-box p {
      color: var(--muted);
      font-size: .9rem;
      margin-bottom: .4rem
    }

    .repartidor-info {
      background: #111;
      border-radius: 10px;
      padding: .8rem 1rem;
      margin: 1rem 0;
      font-size: .9rem;
      color: var(--text)
    }

    .btn-ok {
      display: inline-block;
      background: var(--green);
      color: #fff;
      border: none;
      padding: .9rem 2rem;
      border-radius: 12px;
      font-family: 'Sora', sans-serif;
      font-weight: 700;
      cursor: pointer;
      font-size: .95rem;
      margin-top: .5rem;
      text-decoration: none;
      transition: all .2s
    }

    .btn-ok:hover {
      opacity: .9
    }
  </style>
</head>

<body>
  <nav>
    <div class="nav-logo">Urban<span>Food</span></div>
    <a href="index.php" class="nav-back">← Volver al inicio</a>
  </nav>

  <div class="hero-rest">
    <span><?= $em ?></span>
    <div class="hero-info">
      <h1><?= htmlspecialchars($restaurante['nombre']) ?></h1>
      <p>📍 <?= htmlspecialchars($restaurante['direccion'] ?? '') ?> &nbsp;|&nbsp; 📞 <?= htmlspecialchars($restaurante['telefono'] ?? '') ?></p>
    </div>
  </div>

  <div class="layout">
    <!-- Lista de productos del restaurante -->
    <div class="menu-section">
      <h2>Menú</h2>
      <div class="product-list">
        <?php foreach ($productos as $p): ?>
          <div class="product-card">
            <div class="product-info">
              <div class="product-name"><?= htmlspecialchars($p['nombre']) ?></div>
              <?php if ($p['descripcion']): ?>
                <div class="product-desc"><?= htmlspecialchars($p['descripcion']) ?></div>
              <?php endif; ?>
            </div>
            <div style="display:flex;align-items:center;gap:1rem">
              <div class="product-price">$<?= number_format($p['precio'], 2) ?></div>
              <!-- Controles de cantidad por producto; llaman a changeQty con el ID del producto -->
              <div class="qty-ctrl">
                <button class="qty-btn" onclick="changeQty(<?= $p['id_producto'] ?>, -1)">−</button>
                <span class="qty-num" id="qty-<?= $p['id_producto'] ?>">0</span>
                <button class="qty-btn" onclick="changeQty(<?= $p['id_producto'] ?>, 1)">+</button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Carrito lateral fijo que muestra los productos seleccionados y el total -->
    <div class="cart-box">
      <div class="cart-title">
        🛒 Tu pedido
        <span class="cart-badge" id="cart-count">0</span>
      </div>
      <div class="cart-items" id="cart-items">
        <div class="cart-empty">Agrega productos del menú</div>
      </div>
      <div class="cart-divider"></div>
      <div class="cart-total">
        <span>Total</span>
        <span id="cart-total">$0.00</span>
      </div>
      <button class="btn-checkout" id="btn-checkout" onclick="openModal()" disabled>
        Confirmar pedido →
      </button>
    </div>
  </div>

  <!-- Modal de confirmacion: muestra el resumen del pedido antes de enviarlo -->
  <div class="modal-overlay" id="modal">
    <div class="modal">
      <div class="modal-icon">🛵</div>
      <h3>Confirmar pedido</h3>
      <p>¿Listo para pedir? Se te asignará un repartidor automáticamente.</p>
      <div class="modal-info" id="modal-info"></div>
      <div class="modal-btns">
        <button class="btn-cancel" onclick="closeModal()">Cancelar</button>
        <button class="btn-confirm" onclick="submitOrder()">¡Pedir ahora!</button>
      </div>
    </div>
  </div>

  <!-- Pantalla de exito que aparece luego de que el pedido se procesa correctamente -->
  <div class="success-overlay" id="success">
    <div class="success-box">
      <div class="big-icon">🎉</div>
      <h3>¡Pedido realizado!</h3>
      <p>Tu pedido está en camino</p>
      <div class="repartidor-info" id="repartidor-info"></div>
      <a href="mis-pedidos.php" class="btn-ok">Ver mis pedidos</a>
    </div>
  </div>

  <script>
    // Convierte el array de productos PHP a un objeto JS indexado por id_producto
    // para poder acceder a nombre y precio directamente desde el carrito
    const productos = <?= json_encode(array_column($productos, null, 'id_producto')) ?>;
    const restauranteId = <?= $id ?>;

    // Objeto que almacena { id_producto: cantidad } de los items en el carrito
    let cart = {};

    // Incrementa o decrementa la cantidad de un producto en el carrito.
    // Si la cantidad llega a 0, elimina el producto del carrito.
    function changeQty(id, delta) {
      if (!cart[id]) cart[id] = 0;
      cart[id] = Math.max(0, cart[id] + delta);
      document.getElementById('qty-' + id).textContent = cart[id];
      if (cart[id] === 0) delete cart[id];
      updateCart();
    }

    // Recalcula el total, el contador de items y regenera el HTML del carrito.
    // Habilita o deshabilita el boton de confirmar segun si hay items.
    function updateCart() {
      const items = Object.entries(cart);
      const countEl = document.getElementById('cart-count');
      const totalEl = document.getElementById('cart-total');
      const itemsEl = document.getElementById('cart-items');
      const btn = document.getElementById('btn-checkout');

      let total = 0,
        count = 0;
      if (items.length === 0) {
        itemsEl.innerHTML = '<div class="cart-empty">Agrega productos del menú</div>';
        countEl.textContent = 0;
        totalEl.textContent = '$0.00';
        btn.disabled = true;
        return;
      }

      let html = '';
      for (const [id, qty] of items) {
        const p = productos[id];
        const sub = p.precio * qty;
        total += sub;
        count += qty;
        html += `<div class="cart-item">
      <span class="cart-item-name">${p.nombre}</span>
      <span class="cart-item-qty">×${qty}</span>
      <span class="cart-item-price">$${sub.toFixed(2)}</span>
      <span class="cart-remove" onclick="removeItem(${id})">✕</span>
    </div>`;
      }

      itemsEl.innerHTML = html;
      countEl.textContent = count;
      totalEl.textContent = '$' + total.toFixed(2);
      btn.disabled = false;
    }

    // Elimina completamente un producto del carrito y resetea su contador visual
    function removeItem(id) {
      delete cart[id];
      document.getElementById('qty-' + id).textContent = 0;
      updateCart();
    }

    // Construye el resumen del pedido en el modal y lo muestra
    function openModal() {
      let html = '';
      let total = 0;
      for (const [id, qty] of Object.entries(cart)) {
        const p = productos[id];
        const sub = p.precio * qty;
        total += sub;
        html += `<div><span>${p.nombre} ×${qty}</span><span>$${sub.toFixed(2)}</span></div>`;
      }
      html += `<div><span><strong>Total</strong></span><span><strong>$${total.toFixed(2)}</strong></span></div>`;
      document.getElementById('modal-info').innerHTML = html;
      document.getElementById('modal').classList.add('show');
    }

    // Oculta el modal de confirmacion
    function closeModal() {
      document.getElementById('modal').classList.remove('show');
    }

    // Envia el pedido al servidor via fetch (POST JSON).
    // Si tiene exito, muestra la pantalla de confirmacion con el repartidor asignado.
    // Si falla, muestra el mensaje de error devuelto por el servidor.
    function submitOrder() {
      const items = Object.entries(cart).map(([id, qty]) => ({
        id: parseInt(id),
        qty
      }));
      fetch('procesar-pedido.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            restaurante_id: restauranteId,
            items
          })
        })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            closeModal();
            document.getElementById('repartidor-info').textContent =
              `🛵 Repartidor asignado: ${data.repartidor} (${data.telefono})`;
            document.getElementById('success').classList.add('show');
            // Limpia el carrito y resetea todos los contadores visuales
            cart = {};
            updateCart();
            document.querySelectorAll('.qty-num').forEach(el => el.textContent = '0');
          } else {
            alert('Error: ' + data.error);
          }
        });
    }
  </script>
</body>

</html>