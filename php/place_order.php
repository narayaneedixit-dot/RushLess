<?php
/*
 * Student ka order save karta hai aur ready time predict karta hai.
 *
 *   Predicted time = Queue Workload + Slot Adjustment + Is Order ka Workload
 *
 *   Queue Workload   = outlet par pending/preparing orders x average service time
 *   Average service  = last 20 collected orders ka average (service_log se)
 *                      3 se kam records hon to BASE_SERVICE_MIN use hota hai
 *                      aur jawab "Provisional Estimate" kehlata hai
 *   Slot Adjustment  = is ghante ka rush time (slot_stats table se)
 *   Order Workload   = sabse lambe item ka prep time
 *                      + har extra unit ke liye EXTRA_ITEM_MIN
 *                      (items saath-saath bante hain, isliye sabko jodte nahi)
 */
require_once "predict_lib.php";   // prediction engine ek hi jagah hai

if (!isset($_SESSION["user_id"]) || currentRole() != "student") {
    send(["error" => "Your session has expired. Please log in as a student again."]);
}

$outletId = $_POST["outlet_id"] ?? 0;
$cart = json_decode($_POST["cart"] ?? "[]", true);

if (!is_array($cart) || count($cart) == 0) {
    send(["error" => "Your cart is empty."]);
}

$sql = $pdo->prepare("SELECT id, is_open FROM outlets WHERE id = ?");
$sql->execute([$outletId]);
$outlet = $sql->fetch();

if (!$outlet) {
    send(["error" => "Outlet not found."]);
}

if ($outlet["is_open"] == 0) {
    send(["error" => "This outlet is closed right now."]);
}

// ---- Cart ke items database se check karo (browser ke price par bharosa nahi) ----
$lines = [];
$amount = 0;
$totalQty = 0;
$longestPrep = 0;

$sql = $pdo->prepare("SELECT name, price, prep_min, is_available, equipment_id FROM items WHERE id = ? AND outlet_id = ?");

foreach ($cart as $row) {
    $qty = (int)($row["qty"] ?? 0);

    if ($qty < 1 || $qty > 20) {
        send(["error" => "Quantity must be between 1 and 20."]);
    }

    $sql->execute([$row["item_id"] ?? 0, $outletId]);
    $item = $sql->fetch();

    if (!$item) {
        send(["error" => "One of the items is not on this menu any more."]);
    }

    if ($item["is_available"] == 0) {
        send(["error" => $item["name"] . " is out of stock right now."]);
    }

    if ($item["prep_min"] > $longestPrep) {
        $longestPrep = $item["prep_min"];
    }

    $amount = $amount + $item["price"] * $qty;
    $totalQty = $totalQty + $qty;
    $lines[] = ["name" => $item["name"], "qty" => $qty, "price" => $item["price"],
                "prep_min" => $item["prep_min"], "equipment_id" => $item["equipment_id"]];
}

// ---- Prediction: wahi engine jo search aur walk-in dono use karte hain ----
$eta = predict_eta($pdo, $outletId, $lines);

$avgMin       = $eta["avg_service_min"];
$provisional  = $eta["is_provisional"];
$queueCount   = $eta["queue_count"];
$queueMin     = $eta["queue_min"];
$slotMin      = $eta["slot_min"];
$orderMin     = $eta["order_min"];
$equipmentMin = $eta["equipment_min"];
$predictedMin = $eta["predicted_min"];

// ---- Sab kuch save karo ----
$pdo->beginTransaction();

$sql = $pdo->prepare("INSERT INTO orders
    (outlet_id, user_id, source, amount, queue_count, avg_service_min, queue_min, slot_min, order_min,
     equipment_min, predicted_min, is_provisional, created_at)
    VALUES (?, ?, 'online', ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
$sql->execute([$outletId, $_SESSION["user_id"], $amount, $queueCount, $avgMin, $queueMin, $slotMin,
               $orderMin, $equipmentMin, $predictedMin, $provisional]);

$orderId = $pdo->lastInsertId();

// Token: T-101, T-102 ...
$token = "T-" . (100 + $orderId);
$pdo->prepare("UPDATE orders SET token = ? WHERE id = ?")->execute([$token, $orderId]);

// prep_min aur equipment_id bhi save karte hain, taaki agla order yeh dekh sake
// ki kaunsa station kitna busy hai
$sql = $pdo->prepare("INSERT INTO order_items (order_id, item_name, qty, price, prep_min, equipment_id)
                      VALUES (?, ?, ?, ?, ?, ?)");

foreach ($lines as $line) {
    $sql->execute([$orderId, $line["name"], $line["qty"], $line["price"],
                   $line["prep_min"], $line["equipment_id"]]);
}

$pdo->commit();

send([
    "ok" => true,
    "token" => $token,
    "amount" => $amount,
    "queue_count" => $queueCount,
    "avg_service_min" => $avgMin,
    "queue_min" => round($queueMin, 1),
    "slot_min" => $slotMin,
    "slot_hour" => $eta["slot_hour"],
    "order_min" => $orderMin,
    "equipment_min" => $equipmentMin,
    "equipment_name" => $eta["equipment_name"],
    "predicted_min" => $predictedMin,
    "is_provisional" => $provisional,
    "ready_at" => $eta["ready_at"]
]);
