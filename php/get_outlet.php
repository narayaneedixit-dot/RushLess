<?php
// One outlet with its menu, used to fill the edit form
require_once "db.php";

if (currentRole() != "college") {
    send(["error" => "Only the college account can see this."]);
}

$id = $_GET["id"] ?? 0;

$sql = $pdo->prepare("SELECT o.id, o.category, o.name, o.photo, u.email
                      FROM outlets o
                      JOIN users u ON u.id = o.user_id
                      WHERE o.id = ?");
$sql->execute([$id]);
$outlet = $sql->fetch();

if (!$outlet) {
    send(["error" => "Outlet not found."]);
}

// Item ke saath uske station ka naam bhi, taaki edit form mein dobara chun sake
$sql = $pdo->prepare("SELECT i.name, i.price, i.prep_min, e.name AS equipment_name
                      FROM items i
                      LEFT JOIN equipment e ON e.id = i.equipment_id
                      WHERE i.outlet_id = ? ORDER BY i.id");
$sql->execute([$id]);
$items = $sql->fetchAll();

$sql = $pdo->prepare("SELECT name, quantity FROM equipment WHERE outlet_id = ? ORDER BY id");
$sql->execute([$id]);

send(["outlet" => $outlet, "items" => $items, "equipment" => $sql->fetchAll()]);
