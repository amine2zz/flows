<?php
require_once 'config.php';
initDB();
startSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    jsonResponse(array('success'=>false,'message'=>'Methode non autorisee.'), 405);

$u    = getAuthUser();
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) jsonResponse(array('success'=>false,'message'=>'Donnees invalides.'), 400);

$name    = trim(isset($data['name'])    ? $data['name']    : '');
$phone   = trim(isset($data['phone'])   ? $data['phone']   : '');
$address = trim(isset($data['address']) ? $data['address'] : '');
$city    = trim(isset($data['city'])    ? $data['city']    : '');
$notes   = trim(isset($data['notes'])   ? $data['notes']   : '');
$items   = isset($data['items']) && is_array($data['items']) ? $data['items'] : array();

if (!$name || !$phone || !$address || !$city)
    jsonResponse(array('success'=>false,'message'=>'Veuillez remplir tous les champs.'));
if (empty($items))
    jsonResponse(array('success'=>false,'message'=>'Panier vide.'));

try {
    $pdo = getDB();
    $pdo->beginTransaction();

    $pids = array();
    foreach ($items as $item) { $pid = (int)(isset($item['id']) ? $item['id'] : 0); if ($pid > 0) $pids[] = $pid; }
    if (empty($pids)) jsonResponse(array('success'=>false,'message'=>'Articles invalides.'));

    $ph   = implode(',', array_fill(0, count($pids), '?'));
    $stmt = $pdo->prepare("SELECT id,name,price,stock,sizes FROM products WHERE id IN ($ph) AND active=1");
    $stmt->execute($pids);
    $prods = array();
    foreach ($stmt->fetchAll() as $r) $prods[(int)$r['id']] = $r;

    $total = 0; $orderItems = array();
    foreach ($items as $item) {
        $pid  = (int)(isset($item['id'])   ? $item['id']   : 0);
        $size = trim(isset($item['size'])  ? $item['size'] : '');
        $qty  = max(1, min(10, (int)(isset($item['qty']) ? $item['qty'] : 1)));
        if (!isset($prods[$pid])) { $pdo->rollBack(); jsonResponse(array('success'=>false,'message'=>'Produit introuvable.')); }
        $prod = $prods[$pid];
        $allowed = array_map('trim', explode(',', $prod['sizes']));
        if (!in_array($size, $allowed, true)) { $pdo->rollBack(); jsonResponse(array('success'=>false,'message'=>'Taille invalide.')); }
        if ((int)$prod['stock'] < $qty) { $pdo->rollBack(); jsonResponse(array('success'=>false,'message'=>'Stock insuffisant pour '.$prod['name'].'.')); }
        $total += (float)$prod['price'] * $qty;
        $orderItems[] = array('product_id'=>$pid,'product_name'=>$prod['name'],'size'=>$size,'qty'=>$qty,'unit_price'=>(float)$prod['price']);
    }

    $ref = generateOrderRef();
    $pdo->prepare('INSERT INTO orders (order_ref,user_id,customer_name,phone,address,city,notes,total) VALUES (?,?,?,?,?,?,?,?)')
        ->execute(array($ref, $u ? (int)$u['id'] : null, $name, $phone, $address, $city, $notes ?: null, $total));
    $orderId = (int)$pdo->lastInsertId();

    $iIns = $pdo->prepare('INSERT INTO order_items (order_id,product_id,product_name,size,qty,unit_price) VALUES (?,?,?,?,?,?)');
    $sUpd = $pdo->prepare('UPDATE products SET stock=stock-? WHERE id=? AND stock>=?');
    foreach ($orderItems as $oi) {
        $iIns->execute(array($orderId,$oi['product_id'],$oi['product_name'],$oi['size'],$oi['qty'],$oi['unit_price']));
        $sUpd->execute(array($oi['qty'],$oi['product_id'],$oi['qty']));
    }
    $pdo->commit();

    jsonResponse(array('success'=>true,'order_ref'=>$ref,'total'=>$total,'items'=>$orderItems,'message'=>'Commande confirmee !'));
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse(array('success'=>false,'message'=>'Erreur serveur.'), 500);
}
