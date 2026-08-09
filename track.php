<?php
require_once 'config.php';
initDB();
startSession();

$action = isset($_GET['action']) ? $_GET['action'] : 'my';

if ($action === 'my') {
    $u = getAuthUser();
    if (!$u) jsonResponse(array('ok'=>false,'message'=>'Non connecte.'), 401);
    try {
        $pdo = getDB();
        $orders = $pdo->prepare(
            'SELECT id,order_ref,total,status,city,created_at FROM orders WHERE user_id=? ORDER BY created_at DESC LIMIT 20'
        );
        $orders->execute(array($u['id']));
        $rows = $orders->fetchAll();
        foreach ($rows as &$r) {
            $items = $pdo->prepare('SELECT product_name,size,qty,unit_price FROM order_items WHERE order_id=?');
            $items->execute(array($r['id']));
            $r['items'] = $items->fetchAll();
            $r['status_label'] = statusLabel($r['status']);
        }
        jsonResponse(array('ok'=>true,'orders'=>$rows));
    } catch (PDOException $e) { jsonResponse(array('ok'=>false,'message'=>'Erreur serveur.'),500); }
}

if ($action === 'ref') {
    $ref = trim(isset($_GET['ref']) ? $_GET['ref'] : '');
    if (!$ref) jsonResponse(array('ok'=>false,'message'=>'Reference requise.'));
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT id,order_ref,customer_name,total,status,city,created_at FROM orders WHERE order_ref=? LIMIT 1');
        $stmt->execute(array($ref));
        $order = $stmt->fetch();
        if (!$order) jsonResponse(array('ok'=>false,'message'=>'Commande introuvable.'));
        $items = $pdo->prepare('SELECT product_name,size,qty,unit_price FROM order_items WHERE order_id=?');
        $items->execute(array($order['id']));
        $order['items'] = $items->fetchAll();
        $order['status_label'] = statusLabel($order['status']);
        jsonResponse(array('ok'=>true,'order'=>$order));
    } catch (PDOException $e) { jsonResponse(array('ok'=>false,'message'=>'Erreur serveur.'),500); }
}

jsonResponse(array('ok'=>false,'message'=>'Action inconnue.'), 400);
