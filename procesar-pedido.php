<?php
require_once 'includes/config.php';
header('Content-Type: application/json');

// Solo clientes autenticados pueden hacer pedidos
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

// Lee el cuerpo de la peticion JSON enviado desde el carrito
$data = json_decode(file_get_contents('php://input'), true);
$restaurante_id = intval($data['restaurante_id'] ?? 0);
$items = $data['items'] ?? [];

if (!$restaurante_id || empty($items)) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

$db = getDB();

// Selecciona un repartidor activo al azar para asignarlo al pedido
$rep_result = $db->query("SELECT * FROM Repartidores WHERE activo = 1 ORDER BY RAND() LIMIT 1");
$repartidor = $rep_result->fetch_assoc();

if (!$repartidor) {
    echo json_encode(['success' => false, 'error' => 'No hay repartidores disponibles']);
    exit;
}

// Obtiene los precios reales de los productos desde la BD para calcular el total
// (no se confía en los precios que pudieran venir del cliente)
$total = 0;
$productos_ids = array_map(function ($i) {
    return intval($i['id']);
}, $items);
$ids_str = implode(',', $productos_ids);
$prods_result = $db->query("SELECT * FROM Productos WHERE id_producto IN ($ids_str)");
$prods = [];
while ($r = $prods_result->fetch_assoc()) $prods[$r['id_producto']] = $r;

foreach ($items as $item) {
    $pid = intval($item['id']);
    $qty = intval($item['qty']);
    if (isset($prods[$pid])) {
        $total += $prods[$pid]['precio'] * $qty;
    }
}

// Inserta el pedido principal con el cliente, restaurante, repartidor y total calculado
$stmt = $db->prepare("INSERT INTO Pedidos (id_cliente, id_restaurante, id_repartidor, total, estado) VALUES (?,?,?,?,'pendiente')");
$stmt->bind_param("iiid", $_SESSION['cliente_id'], $restaurante_id, $repartidor['id_repartidor'], $total);
$stmt->execute();
$pedido_id = $stmt->insert_id;

// Inserta cada linea de detalle del pedido (producto, cantidad y subtotal)
$stmt2 = $db->prepare("INSERT INTO Detalle_Pedidos (id_pedido, id_producto, cantidad, subtotal) VALUES (?,?,?,?)");
foreach ($items as $item) {
    $pid = intval($item['id']);
    $qty = intval($item['qty']);
    $sub = $prods[$pid]['precio'] * $qty;
    $stmt2->bind_param("iiid", $pedido_id, $pid, $qty, $sub);
    $stmt2->execute();
}

$db->close();

// Devuelve al cliente el ID del pedido y los datos del repartidor asignado
echo json_encode([
    'success' => true,
    'pedido_id' => $pedido_id,
    'repartidor' => $repartidor['nombre'],
    'telefono' => $repartidor['telefono']
]);
