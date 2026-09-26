<?php
// Returns every outlet of one category (canteen or stationary)
require_once "db.php";

$category = $_GET["category"] ?? "canteen";

$sql = $pdo->prepare("SELECT o.id, o.name, o.photo, o.is_open,
                             (SELECT COUNT(*) FROM items WHERE outlet_id = o.id AND is_available = 1) AS item_count,
                             (SELECT ROUND(AVG(stars), 1) FROM ratings WHERE outlet_id = o.id) AS avg_rating,
                             (SELECT COUNT(*) FROM ratings WHERE outlet_id = o.id) AS rating_count
                      FROM outlets o
                      WHERE o.category = ?
                      ORDER BY o.id");
$sql->execute([$category]);

send(["outlets" => $sql->fetchAll()]);
