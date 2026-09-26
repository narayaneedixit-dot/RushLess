<?php
// Returns one outlet and its menu
require_once "db.php";

$outletId = $_GET["outlet_id"] ?? 0;

$sql = $pdo->prepare("SELECT id, name, photo, is_open FROM outlets WHERE id = ?");
$sql->execute([$outletId]);
$outlet = $sql->fetch();

if (!$outlet) {
    send(["error" => "Outlet not found."]);
}

$sql = $pdo->prepare("SELECT id, name, price, prep_min, is_available FROM items WHERE outlet_id = ? ORDER BY id");
$sql->execute([$outletId]);

send(["outlet" => $outlet, "items" => $sql->fetchAll()]);
