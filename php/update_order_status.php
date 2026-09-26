<?php
/*
 * Order ko aage badhata hai: pending -> preparing -> ready -> collected
 * Collected hote hi asli service time service_log mein save hota hai,
 * aur agli prediction usi average se banti hai.
 */
require_once "db.php";

if (currentRole() != "admin") {
    send(["error" => "Please log in with your outlet account."]);
}

$orderId = $_POST["order_id"] ?? 0;
$action  = $_POST["action"] ?? "";

// action => [abhi ka status, naya status, kaunsa time column]
$flow = [
    "start"   => ["pending", "preparing", "started_at"],
    "ready"   => ["preparing", "ready", "ready_at"],
    "collect" => ["ready", "collected", "collected_at"]
];

if (!isset($flow[$action])) {
    send(["error" => "Unknown action."]);
}

$from = $flow[$action][0];
$to = $flow[$action][1];
$column = $flow[$action][2];

// Yeh order isi outlet ka hai?
$sql = $pdo->prepare("SELECT o.* FROM orders o
                      JOIN outlets ou ON ou.id = o.outlet_id
                      WHERE o.id = ? AND ou.user_id = ?");
$sql->execute([$orderId, $_SESSION["user_id"]]);
$order = $sql->fetch();

if (!$order) {
    send(["error" => "Order not found."]);
}

if ($order["status"] != $from) {
    send(["error" => "This order is '" . $order["status"] . "', so that step is not allowed."]);
}

$pdo->beginTransaction();

$sql = $pdo->prepare("UPDATE orders SET status = ?, $column = NOW() WHERE id = ?");
$sql->execute([$to, $orderId]);

// Collected hone par asli time save karo
if ($action == "collect") {

    $sql = $pdo->prepare("SELECT started_at, ready_at FROM orders WHERE id = ?");
    $sql->execute([$orderId]);
    $times = $sql->fetch();

    $serviceSec = strtotime($times["ready_at"]) - strtotime($times["started_at"]);

    if ($serviceSec < 1) {
        $serviceSec = 1;
    }

    $pdo->prepare("INSERT INTO service_log (order_id, outlet_id, service_sec, logged_at) VALUES (?, ?, ?, NOW())")
        ->execute([$orderId, $order["outlet_id"], $serviceSec]);
}

$pdo->commit();

send(["ok" => true, "status" => $to]);
