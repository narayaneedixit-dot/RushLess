<?php
// Student ke apne orders, sabse naya sabse upar
require_once "db.php";

if (currentRole() != "student") {
    send(["error" => "Please log in as a student to see your orders."]);
}

$sql = $pdo->prepare("SELECT o.token, o.status, o.amount, o.predicted_min, o.is_provisional,
                             o.created_at, o.ready_at, ou.name AS outlet,
                             (SELECT GROUP_CONCAT(CONCAT(item_name, ' x', qty) SEPARATOR ', ')
                                FROM order_items WHERE order_id = o.id) AS items,
                             (SELECT COUNT(*) FROM orders x
                               WHERE x.outlet_id = o.outlet_id
                                 AND x.status IN ('pending','preparing')
                                 AND x.id < o.id) AS ahead,
                             (SELECT stars FROM ratings WHERE order_id = o.id) AS my_rating
                      FROM orders o
                      JOIN outlets ou ON ou.id = o.outlet_id
                      WHERE o.user_id = ?
                      ORDER BY o.id DESC
                      LIMIT 10");
$sql->execute([$_SESSION["user_id"]]);

send(["orders" => $sql->fetchAll()]);
