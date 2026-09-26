<?php
/*
 * Student apne collected order ko 1 se 5 star deta hai.
 *
 * Rules:
 *   - order usi student ka hona chahiye
 *   - order 'collected' hona chahiye (jo cheez mili hi nahi, uski rating kaisi)
 *   - ek order par ek hi rating (order_id UNIQUE hai)
 */
require_once "db.php";

if (currentRole() != "student") {
    send(["error" => "Please log in as a student."]);
}

$token = trim($_POST["token"] ?? "");
$stars = (int)($_POST["stars"] ?? 0);

if ($stars < 1 || $stars > 5) {
    send(["error" => "Please choose between 1 and 5 stars."]);
}

// Order dhoondo — sirf apna aur sirf collected
$sql = $pdo->prepare("SELECT id, outlet_id FROM orders
                      WHERE token = ? AND user_id = ? AND status = 'collected'");
$sql->execute([$token, $_SESSION["user_id"]]);
$order = $sql->fetch();

if (!$order) {
    send(["error" => "This order cannot be rated yet."]);
}

// Pehle se rating to nahi de di?
$sql = $pdo->prepare("SELECT id FROM ratings WHERE order_id = ?");
$sql->execute([$order["id"]]);

if ($sql->fetch()) {
    send(["error" => "You have already rated this order."]);
}

$sql = $pdo->prepare("INSERT INTO ratings (order_id, outlet_id, user_id, stars, created_at)
                      VALUES (?, ?, ?, ?, NOW())");
$sql->execute([$order["id"], $order["outlet_id"], $_SESSION["user_id"], $stars]);

// Us order ke har item ko bhi wahi star do (item-wise rating, search ke liye)
$items = $pdo->prepare("SELECT item_name FROM order_items WHERE order_id = ?");
$items->execute([$order["id"]]);

$saveItem = $pdo->prepare("INSERT INTO item_ratings (order_id, outlet_id, item_name, stars, created_at)
                           VALUES (?, ?, ?, ?, NOW())");

foreach ($items->fetchAll() as $it) {
    $saveItem->execute([$order["id"], $order["outlet_id"], $it["item_name"], $stars]);
}

send(["ok" => true, "stars" => $stars]);
