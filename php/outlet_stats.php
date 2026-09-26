<?php
// Outlet dashboard ke upar dikhne wale aaj ke numbers
require_once "db.php";

if (currentRole() != "admin") {
    send(["error" => "Please log in with your outlet account."]);
}

// Is login ka outlet kaunsa hai
$sql = $pdo->prepare("SELECT id FROM outlets WHERE user_id = ?");
$sql->execute([$_SESSION["user_id"]]);
$outlet = $sql->fetch();

if (!$outlet) {
    send(["error" => "No outlet is linked to this account."]);
}

$outletId = $outlet["id"];

// 1. Aaj kitne order aaye
$sql = $pdo->prepare("SELECT COUNT(*) FROM orders
                      WHERE outlet_id = ? AND DATE(created_at) = CURDATE()");
$sql->execute([$outletId]);
$todayOrders = (int)$sql->fetchColumn();

// 2. Abhi kitne baaki hain (pending + preparing + ready)
$sql = $pdo->prepare("SELECT COUNT(*) FROM orders
                      WHERE outlet_id = ? AND status IN ('pending','preparing','ready')");
$sql->execute([$outletId]);
$waiting = (int)$sql->fetchColumn();

// 3. Average service time — wahi data jisse prediction banti hai
$sql = $pdo->prepare("SELECT COUNT(*) AS n, AVG(service_sec) AS avg_sec
                      FROM (SELECT service_sec FROM service_log
                            WHERE outlet_id = ? ORDER BY id DESC LIMIT 20) recent");
$sql->execute([$outletId]);
$row = $sql->fetch();

$records = (int)$row["n"];
$avgMin = 0;

if ($records > 0) {
    $avgMin = round($row["avg_sec"] / 60, 1);

    // bahut chhota time bhi 0 na dikhe
    if ($avgMin < 0.1) {
        $avgMin = 0.1;
    }
}

send([
    "today_orders" => $todayOrders,
    "waiting" => $waiting,
    "avg_service_min" => $avgMin,
    "records" => $records,
    "provisional" => $records < 3 ? 1 : 0
]);
