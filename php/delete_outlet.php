<?php
// Deletes an outlet, its menu and its login account
require_once "db.php";

if (currentRole() != "college") {
    send(["error" => "Only the college account can delete an outlet."]);
}

$id = $_POST["outlet_id"] ?? 0;

$sql = $pdo->prepare("SELECT user_id FROM outlets WHERE id = ?");
$sql->execute([$id]);
$row = $sql->fetch();

if (!$row) {
    send(["error" => "Outlet not found."]);
}

// Order matters because of the foreign keys: items, then outlet, then user
$pdo->beginTransaction();

$pdo->prepare("DELETE FROM items WHERE outlet_id = ?")->execute([$id]);
$pdo->prepare("DELETE FROM outlets WHERE id = ?")->execute([$id]);
$pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$row["user_id"]]);

$pdo->commit();

send(["ok" => true]);
