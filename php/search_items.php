<?php
/*
 * Food search — ek item poore campus mein kahan-kahan mil raha hai.
 *
 * Sirf woh outlets dikhate hain jo (a) abhi khule hain aur (b) jinke paas woh item in stock hai.
 * Har result ke saath ETA bhi jaata hai, jo wahi prediction engine se aata hai jo
 * order place karte waqt chalta hai — sirf item ka prep time nahi.
 */
require_once "predict_lib.php";

$q = trim($_GET["q"] ?? "");

if (strlen($q) < 2) {
    send(["error" => "Please type at least 2 letters."]);
}

// LIKE search. % ko escape karte hain taaki koi poora menu na kheench le.
$term = "%" . str_replace(["%", "_"], ["\\%", "\\_"], $q) . "%";

$sql = $pdo->prepare("SELECT i.id AS item_id, i.name AS item_name, i.price, i.prep_min, i.equipment_id,
                             o.id AS outlet_id, o.name AS outlet_name, o.photo,
                             (SELECT ROUND(AVG(stars), 1) FROM item_ratings
                               WHERE outlet_id = o.id AND item_name = i.name) AS avg_rating,
                             (SELECT COUNT(*) FROM item_ratings
                               WHERE outlet_id = o.id AND item_name = i.name) AS rating_count
                      FROM items i
                      JOIN outlets o ON o.id = i.outlet_id
                      WHERE i.name LIKE ?
                        AND i.is_available = 1
                        AND o.is_open = 1
                      ORDER BY i.price ASC
                      LIMIT 20");
$sql->execute([$term]);
$rows = $sql->fetchAll();

$results = [];
$etaCache = [];   // ek outlet ka ETA baar baar mat nikalo

foreach ($rows as $r) {

    $outletId = $r["outlet_id"];
    // Alag item ka station alag ho sakta hai, isliye key mein equipment bhi
    $key = $outletId . "-" . $r["prep_min"] . "-" . $r["equipment_id"];

    if (!isset($etaCache[$key])) {
        // qty 1 maan kar ETA — student ek item dhoondh raha hai
        $etaCache[$key] = predict_eta($pdo, $outletId, [[
            "prep_min"     => $r["prep_min"],
            "qty"          => 1,
            "equipment_id" => $r["equipment_id"]
        ]]);
    }

    $eta = $etaCache[$key];

    $results[] = [
        "item_id"       => (int)$r["item_id"],
        "item_name"     => $r["item_name"],
        "price"         => $r["price"],
        "prep_min"      => $r["prep_min"],
        "outlet_id"     => (int)$outletId,
        "outlet_name"   => $r["outlet_name"],
        "avg_rating"    => $r["avg_rating"],           // rating na ho to NULL
        "rating_count"  => (int)$r["rating_count"],
        "in_stock"      => 1,
        "predicted_min" => $eta["predicted_min"],
        "ready_at"      => $eta["ready_at"],
        "queue_count"   => $eta["queue_count"],
        "queue_min"     => $eta["queue_min"],
        "slot_min"      => $eta["slot_min"],
        "order_min"     => $eta["order_min"],
        "equipment_min" => $eta["equipment_min"],
        "equipment_name"=> $eta["equipment_name"],
        "is_provisional"=> $eta["is_provisional"]
    ];
}

// Sabse jaldi milne wala pehle
usort($results, function ($a, $b) {
    return $a["predicted_min"] <=> $b["predicted_min"];
});

send(["query" => $q, "count" => count($results), "results" => $results]);
