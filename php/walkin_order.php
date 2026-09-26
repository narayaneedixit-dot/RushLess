<?php
/*
 * Walk-in order — counter par aaye customer ka order outlet khud daalta hai.
 *
 * Yeh isliye zaroori hai: agar counter ka aadha kaam system ke bahar hoga,
 * to queue ka hisaab galat rahega aur online students ko galat time milega.
 * Walk-in bhi usi queue mein aata hai, isliye prediction sach ke kareeb rehti hai.
 *
 * Prediction yahan bhi lagti hai, taaki outlet ko pata rahe ki kitna time lagega.
 */
require_once "predict_lib.php";   // wahi prediction engine

if (currentRole() != "admin") {
    send(["error" => "Please log in with your outlet account."]);
}

// Is login ka outlet
$sql = $pdo->prepare("SELECT id FROM outlets WHERE user_id = ?");
$sql->execute([$_SESSION["user_id"]]);
$outlet = $sql->fetch();

if (!$outlet) {
    send(["error" => "No outlet is linked to this account."]);
}

$outletId = $outlet["id"];
$cart = json_decode($_POST["cart"] ?? "[]", true);

if (!is_array($cart) || count($cart) == 0) {
    send(["error" => "Please add at least one item."]);
}

// Items database se check karo
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

// Prediction wahi engine, online order jaisa
$eta = predict_eta($pdo, $outletId, $lines);

$avgMin       = $eta["avg_service_min"];
$provisional  = $eta["is_provisional"];
$queueCount   = $eta["queue_count"];
$queueMin     = $eta["queue_min"];
$slotMin      = $eta["slot_min"];
$orderMin     = $eta["order_min"];
$equipmentMin = $eta["equipment_min"];
$predictedMin = $eta["predicted_min"];

// Save — user_id NULL, kyunki walk-in customer ka account nahi hota
$pdo->beginTransaction();

$sql = $pdo->prepare("INSERT INTO orders
    (outlet_id, user_id, source, amount, queue_count, avg_service_min, queue_min, slot_min,
     order_min, equipment_min, predicted_min, is_provisional, created_at)
    VALUES (?, NULL, 'walkin', ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
$sql->execute([$outletId, $amount, $queueCount, $avgMin, $queueMin, $slotMin,
               $orderMin, $equipmentMin, $predictedMin, $provisional]);

$orderId = $pdo->lastInsertId();
$token = "T-" . (100 + $orderId);
$pdo->prepare("UPDATE orders SET token = ? WHERE id = ?")->execute([$token, $orderId]);

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
    "predicted_min" => $predictedMin,
    "ready_at" => $eta["ready_at"]
]);
