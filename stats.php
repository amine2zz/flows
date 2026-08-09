<?php
header('Cache-Control: no-store');
require_once 'config.php';
initDB();

try {
    $pdo = getDB();

    $byCategory = $pdo->query(
        "SELECT p.category, COUNT(oi.id) AS sold, SUM(oi.qty * oi.unit_price) AS revenue
         FROM order_items oi
         JOIN products p ON p.id = oi.product_id
         JOIN orders o ON o.id = oi.order_id
         WHERE o.status != 'cancelled'
         GROUP BY p.category
         ORDER BY sold DESC"
    )->fetchAll();

    $topProducts = $pdo->query(
        "SELECT oi.product_name, SUM(oi.qty) AS sold
         FROM order_items oi
         JOIN orders o ON o.id = oi.order_id
         WHERE o.status != 'cancelled'
         GROUP BY oi.product_id, oi.product_name
         ORDER BY sold DESC
         LIMIT 6"
    )->fetchAll();

    $ordersCount = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status != 'cancelled'")->fetchColumn();
    $revenue     = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status != 'cancelled'")->fetchColumn();

    $recentOrders = $pdo->query(
        "SELECT order_ref, city, total, status, created_at
         FROM orders ORDER BY created_at DESC LIMIT 5"
    )->fetchAll();

    if (empty($byCategory)) {
        $byCategory = array(
            array('category' => 'T-Shirts', 'sold' => 0, 'revenue' => 0),
            array('category' => 'Hoodies', 'sold' => 0, 'revenue' => 0),
            array('category' => 'Accessoires', 'sold' => 0, 'revenue' => 0),
        );
    }

    jsonResponse(array(
        'orders_count'  => $ordersCount,
        'revenue'       => $revenue,
        'by_category'   => $byCategory,
        'top_products'  => $topProducts,
        'recent_orders' => $recentOrders,
    ));

} catch (PDOException $e) {
    jsonResponse(array('error' => 'Erreur serveur.'), 500);
}
