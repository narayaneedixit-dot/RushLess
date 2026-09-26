<?php
// Ek item ko out of stock ya wapas available karta hai
require_once "db.php";

if (currentRole() != "admin") {
    send(["error" => "Please log in with your outlet account."]);
}

$itemId = $_POST["item_id"] ?? 0;

// Item isi outlet ka hona chahiye
$sql = $pdo->prepare("SELECT i.id, i.is_available
                      FROM items i
                      JOIN outlets o ON o.id = i.outlet_id
                      WHERE i.id = ? AND o.user_id = ?");
$sql->execute([$itemId, $_SESSION["user_id"]]);
$item = $sql->fetch();

if (!$item) {
    send(["error" => "Item not found."]);
}

$newValue = $item["is_available"] == 1 ? 0 : 1;

$pdo->prepare("UPDATE items SET is_available = ? WHERE id = ?")->execute([$newValue, $itemId]);

send(["ok" => true, "is_available" => $newValue]);
