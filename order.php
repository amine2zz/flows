<?php
require_once 'config.php';
initDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(array('success' => false, 'message' => 'Methode non autorisee.'), 405);
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    jsonResponse(array('success' => false, 'message' => 'Donnees invalides.'), 400);
}

$name    = trim(isset($data['name']) ? $data['name'] : '');
$phone   = trim(isset($data['phone']) ? $data['phone'] : '');
$address = trim(isset($data['address']) ? $data['address'] : '');
$city    = trim(isset($data['city']) ? $data['city'] : '');
$notes   = trim(isset($data['notes']) ? $data['notes'] : '');
$items   = isset($data['items']) && is_array($data['items']) ? $data['items'] : array();

if ($name === '' || $phone === '' || $address === '' || $city === '') {
    jsonResponse(array('success' => false, 'message' => 'Veuillez remplir tous les champs obligatoires.'));
}
if (empty($items)) {
    jsonResponse(array('success' => false, 'message' => 'Votre panier est vide.'));
}

try {
    $pdo = getDB();
    $pdo->beginTransaction();

    $productIds = array();
    foreach ($items as $item) {
        $pid = (int)(isset($item['id']) ? $item['id'] : 0);
        if ($pid > 0) $productIds[] = $pid;
    }
    if (empty($productIds)) {
        jsonResponse(array('success' => false, 'message' => 'Articles invalides.'));
    }

    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $stmt = $pdo->prepare("SELECT id, name, price, stock, sizes FROM products WHERE id IN ($placeholders) AND active = 1");
    $stmt->execute($productIds);
    $products = array();
    foreach ($stmt->fetchAll() as $row) {
        $products[(int)$row['id']] = $row;
    }

    $total       = 0;
    $orderItems  = array();

    foreach ($items as $item) {
        $pid  = (int)(isset($item['id']) ? $item['id'] : 0);
        $size = trim(isset($item['size']) ? $item['size'] : '');
        $qty  = max(1, min(10, (int)(isset($item['qty']) ? $item['qty'] : 1)));

        if (!isset($products[$pid])) {
            $pdo->rollBack();
            jsonResponse(array('success' => false, 'message' => 'Produit introuvable.'));
        }
        $prod = $products[$pid];
        $allowedSizes = array_map('trim', explode(',', $prod['sizes']));
        if (!in_array($size, $allowedSizes, true)) {
            $pdo->rollBack();
            jsonResponse(array('success' => false, 'message' => 'Taille invalide pour ' . $prod['name'] . '.'));
        }
        if ((int)$prod['stock'] < $qty) {
            $pdo->rollBack();
            jsonResponse(array('success' => false, 'message' => 'Stock insuffisant pour ' . $prod['name'] . '.'));
        }

        $lineTotal = (float)$prod['price'] * $qty;
        $total += $lineTotal;
        $orderItems[] = array(
            'product_id'   => $pid,
            'product_name' => $prod['name'],
            'size'         => $size,
            'qty'          => $qty,
            'unit_price'   => (float)$prod['price'],
        );
    }

    $orderRef = generateOrderRef();
    $ins = $pdo->prepare(
        'INSERT INTO orders (order_ref, customer_name, phone, address, city, notes, total, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $ins->execute(array($orderRef, $name, $phone, $address, $city, $notes ?: null, $total, 'pending'));
    $orderId = (int)$pdo->lastInsertId();

    $itemIns = $pdo->prepare(
        'INSERT INTO order_items (order_id, product_id, product_name, size, qty, unit_price)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stockUp = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');

    foreach ($orderItems as $oi) {
        $itemIns->execute(array($orderId, $oi['product_id'], $oi['product_name'], $oi['size'], $oi['qty'], $oi['unit_price']));
        $stockUp->execute(array($oi['qty'], $oi['product_id'], $oi['qty']));
    }

    $pdo->commit();

    jsonResponse(array(
        'success'   => true,
        'order_ref' => $orderRef,
        'total'     => $total,
        'message'   => 'Commande confirmee ! Nous vous contacterons bientot.',
        'items'     => $orderItems,
    ));

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse(array('success' => false, 'message' => 'Erreur serveur. Reessayez.'), 500);
}
