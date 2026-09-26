<?php
// Outlet khud ko Open ya Closed kar sakta hai
require_once "db.php";

if (currentRole() != "admin") {
    send(["error" => "Please log in with your outlet account."]);
}

$sql = $pdo->prepare("SELECT id, is_open FROM outlets WHERE user_id = ?");
$sql->execute([$_SESSION["user_id"]]);
$outlet = $sql->fetch();

if (!$outlet) {
    send(["error" => "No outlet is linked to this account."]);
}

$newValue = $outlet["is_open"] == 1 ? 0 : 1;

$pdo->prepare("UPDATE outlets SET is_open = ? WHERE id = ?")->execute([$newValue, $outlet["id"]]);

send(["ok" => true, "is_open" => $newValue]);
