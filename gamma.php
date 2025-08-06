<?php

header('Content-Type: application/json');

$db = [
    'host' => 'localhost',
    'port' => 5432,
    'dbname' => 'your_db',
    'user' => 'your_user',
    'pass' => 'your_pass',
];

try {
    $pdo = new PDO("pgsql:host={$db['host']};port={$db['port']};dbname={$db['dbname']}", $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "
        SELECT o.id, o.order_time, p.id AS product_id, p.name AS product_name, c.id AS category_id, c.name AS category_name
        FROM orders o
        JOIN products p ON o.product_id = p.id
        JOIN categories c ON p.category_id = c.id
        ORDER BY o.order_time DESC
        LIMIT 100
    ";
    $orders = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    $category_counts = [];
    $first_time = null;
    $last_time = null;
    foreach ($orders as $i => $order) {
        $cat = $order['category_name'];
        if (!isset($category_counts[$cat])) $category_counts[$cat] = 0;
        $category_counts[$cat]++;
        if ($i == 0) $last_time = $order['order_time'];
        $first_time = $order['order_time'];
    }
    $time_diff = null;
    if ($first_time && $last_time) {
        $t1 = strtotime($first_time);
        $t2 = strtotime($last_time);
        $time_diff = $t1 - $t2;
    }

    echo json_encode([
        'orders_count' => count($orders),
        'category_counts' => $category_counts,
        'time_diff_seconds' => $time_diff,
        'first_order_time' => $first_time,
        'last_order_time' => $last_time,
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
} 