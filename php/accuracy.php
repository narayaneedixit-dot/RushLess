<?php
/*
 * Accuracy — humne kitna time bataya tha, aur sach mein kitna laga.
 *
 * Predicted  = order place karte waqt ka estimate (orders.predicted_min)
 * Actual     = order place hone se ready hone tak ka asli time
 * Error      = dono ka farak
 *
 * Yeh saboot hai ki system sach mein seekh raha hai ya nahi.
 */
require_once "db.php";

if (currentRole() != "college") {
    send(["error" => "Only the college account can see this."]);
}

$sql = $pdo->query("SELECT o.token, o.predicted_min, o.source, ou.name AS outlet,
                           ROUND(TIMESTAMPDIFF(SECOND, o.created_at, o.ready_at) / 60, 1) AS actual_min
                    FROM orders o
                    JOIN outlets ou ON ou.id = o.outlet_id
                    WHERE o.status = 'collected' AND o.ready_at IS NOT NULL
                    ORDER BY o.id DESC
                    LIMIT 20");
$rows = $sql->fetchAll();

$totalError = 0;
$count = 0;

foreach ($rows as $i => $r) {
    $error = round($r["actual_min"] - $r["predicted_min"], 1);
    $rows[$i]["error_min"] = $error;
    $totalError = $totalError + abs($error);
    $count++;
}

send([
    "orders" => $rows,
    "count" => $count,
    "avg_error_min" => $count > 0 ? round($totalError / $count, 1) : 0
]);
