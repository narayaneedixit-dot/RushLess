<?php
/*
 * Prediction engine — ek hi jagah, taaki har jagah wahi formula chale.
 *
 *   ETA = max(Queue Workload, Equipment Wait) + Slot Adjustment + Order Workload
 *
 * Teen cheezein alag-alag hain:
 *   Queue Workload   counter kitni jaldi orders nipta raha hai (average service time)
 *   Equipment Wait   jis station par tumhara item banega, uspar pehle se kitna kaam pada hai
 *   Order Workload   tumhare apne item ka banne ka time
 *
 * Queue aur Equipment ka max lete hain, jod te nahi. Dono ek hi intezaar ko do tareeke se
 * naapte hain — counter ki taraf se, aur machine ki taraf se. Jodne par time double ho jaata.
 * Jo zyada hai wahi asli rukawat hai.
 *
 * Yeh file teen jagah use hoti hai:
 *   place_order.php    student ka order
 *   walkin_order.php   counter par aaya order
 *   search_items.php   search results mein har outlet ka ETA
 */
require_once "db.php";

if (!defined("BASE_SERVICE_MIN")) {
    define("BASE_SERVICE_MIN", 4);      // jab tak history nahi hai
}
if (!defined("EXTRA_ITEM_MIN")) {
    define("EXTRA_ITEM_MIN", 0.5);      // har extra unit ka thoda sa time
}

// Last 20 collected orders ka average service time (minute mein)
function average_service($pdo, $outletId) {

    $sql = $pdo->prepare("SELECT COUNT(*) AS n, AVG(service_sec) AS avg_sec
                          FROM (SELECT service_sec FROM service_log
                                WHERE outlet_id = ? ORDER BY id DESC LIMIT 20) recent");
    $sql->execute([$outletId]);
    $row = $sql->fetch();

    $records = (int)$row["n"];

    if ($records < 3) {
        return ["minutes" => BASE_SERVICE_MIN, "records" => $records, "provisional" => 1];
    }

    $minutes = round($row["avg_sec"] / 60, 2);

    if ($minutes < 0.5) {
        $minutes = 0.5;                 // ek galat entry prediction na bigaad de
    }

    return ["minutes" => $minutes, "records" => $records, "provisional" => 0];
}

// Kitne orders abhi banne baaki hain
function active_queue($pdo, $outletId) {
    $sql = $pdo->prepare("SELECT COUNT(*) FROM orders
                          WHERE outlet_id = ? AND status IN ('pending','preparing')");
    $sql->execute([$outletId]);
    return (int)$sql->fetchColumn();
}

// Is ghante ka rush adjustment
function slot_adjustment($pdo) {
    $sql = $pdo->prepare("SELECT adjustment_min FROM slot_stats WHERE hour_of_day = ?");
    $sql->execute([(int)date("G")]);
    $value = $sql->fetchColumn();

    return $value === false ? 0.0 : (float)$value;
}

/*
 * Har station par abhi kitna kaam pada hai.
 *
 * Queue ke har pending/preparing order ke items dekhte hain, aur unka kaam
 * station ke hisaab se jodte hain. Phir us station ke units se baant dete hain,
 * kyunki 2 stove ek saath do cheezein bana sakte hain.
 *
 *   station ka wait = (us station par pada kaam) / (us station ke units)
 *
 * Dhyan do: yeh sirf queue ka backlog baanta ja raha hai. Naye order ka apna
 * prep time kabhi machines se divide nahi hota — ek Maggi do stove par aadhe
 * time mein nahi banti.
 */
function station_backlog($pdo, $outletId) {

    $sql = $pdo->prepare("SELECT oi.equipment_id, e.name, e.quantity,
                                 SUM(oi.prep_min * oi.qty) AS work_min
                          FROM order_items oi
                          JOIN orders o ON o.id = oi.order_id
                          JOIN equipment e ON e.id = oi.equipment_id
                          WHERE o.outlet_id = ?
                            AND o.status IN ('pending','preparing')
                          GROUP BY oi.equipment_id, e.name, e.quantity");
    $sql->execute([$outletId]);

    $out = [];

    foreach ($sql->fetchAll() as $r) {

        $units = (int)$r["quantity"];

        if ($units < 1) {
            $units = 1;
        }

        $out[(int)$r["equipment_id"]] = [
            "name"      => $r["name"],
            "quantity"  => $units,
            "work_min"  => round($r["work_min"], 2),
            "wait_min"  => round($r["work_min"] / $units, 2)
        ];
    }

    return $out;
}

/*
 * Poora ETA.
 *
 * $lines = [["prep_min" => 5, "qty" => 2, "equipment_id" => 3], ...]
 *
 * Items saath-saath bante hain, isliye sabka time jodte nahi — sabse lamba lete hain.
 */
function predict_eta($pdo, $outletId, $lines) {

    $avg = average_service($pdo, $outletId);
    $queueCount = active_queue($pdo, $outletId);
    $slotMin = slot_adjustment($pdo);

    $queueMin = $queueCount * $avg["minutes"];

    // ---- apne order ka kaam ----
    $longestPrep = 0;
    $totalQty = 0;
    $usedStations = [];     // sirf woh station jo is order ko chahiye

    foreach ($lines as $line) {

        $prep = (float)$line["prep_min"];
        $qty = (int)$line["qty"];

        if ($prep > $longestPrep) {
            $longestPrep = $prep;
        }

        $totalQty = $totalQty + $qty;

        if (!empty($line["equipment_id"])) {
            $usedStations[(int)$line["equipment_id"]] = true;
        }
    }

    $orderMin = round($longestPrep + EXTRA_ITEM_MIN * ($totalQty - 1), 2);

    // ---- equipment wait: sirf un stations ka jo is order ko chahiye ----
    $backlog = station_backlog($pdo, $outletId);

    $equipmentMin = 0;
    $busiest = null;

    foreach ($usedStations as $equipId => $yes) {

        if (!isset($backlog[$equipId])) {
            continue;                       // us station par abhi kuch pending nahi hai
        }

        $wait = $backlog[$equipId]["wait_min"];

        if ($wait > $equipmentMin) {
            $equipmentMin = $wait;
            $busiest = $backlog[$equipId];
        }
    }

    // Counter ki throughput aur station ka backlog — jo zyada ho wahi asli rukawat hai
    $waitMin = $queueMin > $equipmentMin ? $queueMin : $equipmentMin;

    $predictedMin = round($waitMin + $slotMin + $orderMin, 1);

    return [
        "queue_count"     => $queueCount,
        "avg_service_min" => $avg["minutes"],
        "records"         => $avg["records"],
        "is_provisional"  => $avg["provisional"],
        "queue_min"       => round($queueMin, 2),
        "equipment_min"   => $equipmentMin,
        "equipment_name"  => $busiest ? $busiest["name"] : null,
        "equipment_units" => $busiest ? $busiest["quantity"] : null,
        "wait_min"        => round($waitMin, 2),
        "slot_min"        => $slotMin,
        "slot_hour"       => (int)date("G"),
        "order_min"       => $orderMin,
        "predicted_min"   => $predictedMin,
        "ready_at"        => date("h:i A", time() + $predictedMin * 60)
    ];
}
