<?php
// Outlet ka apna menu (dashboard par edit karne ke liye)
require_once "db.php";

if (currentRole() != "admin") {
    send(["error" => "Please log in with your outlet account."]);
}

$sql = $pdo->prepare("SELECT id FROM outlets WHERE user_id = ?");
$sql->execute([$_SESSION["user_id"]]);
$outlet = $sql->fetch();

if (!$outlet) {
    send(["error" => "No outlet is linked to this account."]);
}

$sql = $pdo->prepare("SELECT id, name, price, prep_min, is_available, equipment_id FROM items WHERE outlet_id = ? ORDER BY id");
$sql->execute([$outlet["id"]]);
$items = $sql->fetchAll();

// Outlet ke paas kaunse station hain (dropdown ke liye)
$sql = $pdo->prepare("SELECT id, name, quantity FROM equipment WHERE outlet_id = ? ORDER BY id");
$sql->execute([$outlet["id"]]);

send(["items" => $items, "equipment" => $sql->fetchAll()]);
