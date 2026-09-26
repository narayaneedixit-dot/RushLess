<?php
// Every outlet, for the college dashboard
require_once "db.php";

if (currentRole() != "college") {
    send(["error" => "Only the college account can see this."]);
}

$sql = $pdo->query("SELECT o.id, o.category, o.name, o.photo, o.is_open, u.email,
                           (SELECT COUNT(*) FROM items WHERE outlet_id = o.id) AS item_count,
                           (SELECT COUNT(*) FROM orders
                             WHERE outlet_id = o.id AND status IN ('pending','preparing','ready')) AS active_orders,
                           (SELECT COUNT(*) FROM orders
                             WHERE outlet_id = o.id AND status = 'collected') AS done_orders
                    FROM outlets o
                    JOIN users u ON u.id = o.user_id
                    ORDER BY o.id");

send(["outlets" => $sql->fetchAll()]);
