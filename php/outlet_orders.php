<?php
// Outlet ka apna queue: uske pending, preparing aur ready orders
require_once "db.php";

if (currentRole() != "admin") {
    send(["error" => "Please log in with your outlet account."]);
}

// Is login ka outlet kaunsa hai
$sql = $pdo->prepare("SELECT id, name, is_open FROM outlets WHERE user_id = ?");
$sql->execute([$_SESSION["user_id"]]);
$outlet = $sql->fetch();

if (!$outlet) {
    send(["error" => "No outlet is linked to this account."]);
}

$sql = $pdo->prepare("SELECT o.id, o.token, o.status, o.amount, o.predicted_min, o.created_at, o.source,
                             COALESCE(u.name, 'Walk-in customer') AS student,
                             COALESCE(u.roll_no, 'counter') AS roll_no,
                             (SELECT GROUP_CONCAT(CONCAT(item_name, ' x', qty) SEPARATOR ', ')
                                FROM order_items WHERE order_id = o.id) AS items
                      FROM orders o
                      LEFT JOIN users u ON u.id = o.user_id
                      WHERE o.outlet_id = ? AND o.status <> 'collected'
                      ORDER BY o.id");
$sql->execute([$outlet["id"]]);

send(["outlet" => $outlet, "orders" => $sql->fetchAll()]);
