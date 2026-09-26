<?php
// Outlet apna poora menu save karta hai (naam, price, prep time)
require_once "db.php";

if (currentRole() != "admin") {
    send(["error" => "Please log in with your outlet account."]);
}

$sql = $pdo->prepare("SELECT id FROM outlets WHERE user_id = ?");
$sql->execute([$_SESSION["user_id"]]);
$outlet = $sql->fetch();

if (!$outlet) {
    send(["error" => "No outlet is linked to this account."]);
}

$outletId = $outlet["id"];

$ids    = $_POST["item_id"] ?? [];
$names  = $_POST["item_name"] ?? [];
$prices = $_POST["item_price"] ?? [];
$preps  = $_POST["item_prep"] ?? [];
$equips = $_POST["item_equipment_id"] ?? [];   // station ki id, ya khaali

$pdo->beginTransaction();

$keep = [];   // jo rows form mein bachi hain

$update = $pdo->prepare("UPDATE items SET name = ?, price = ?, prep_min = ?, equipment_id = ?
                         WHERE id = ? AND outlet_id = ?");
$insert = $pdo->prepare("INSERT INTO items (outlet_id, name, price, prep_min, equipment_id) VALUES (?, ?, ?, ?, ?)");

// Sirf apne hi outlet ka station chun sakte ho
$own = $pdo->prepare("SELECT id FROM equipment WHERE outlet_id = ?");
$own->execute([$outletId]);
$ownEquip = $own->fetchAll(PDO::FETCH_COLUMN);

for ($i = 0; $i < count($names); $i++) {

    $name = trim($names[$i]);
    $price = $prices[$i] ?? "";
    $prep = (isset($preps[$i]) && is_numeric($preps[$i]) && $preps[$i] > 0) ? $preps[$i] : 2;

    if ($name == "" || !is_numeric($price)) {
        continue;
    }

    $id = $ids[$i] ?? "";

    $equipId = $equips[$i] ?? "";
    $equipId = in_array($equipId, $ownEquip) ? (int)$equipId : null;

    if ($id == "") {
        // Nayi item
        $insert->execute([$outletId, $name, $price, $prep, $equipId]);
        $keep[] = $pdo->lastInsertId();
    } else {
        // Purani item, bas update
        $update->execute([$name, $price, $prep, $equipId, $id, $outletId]);
        $keep[] = $id;
    }
}

if (count($keep) == 0) {
    $pdo->rollBack();
    send(["error" => "Please keep at least one item with a name and price."]);
}

// Form se hatayi gayi items ko hata do.
// Purane orders order_items mein naam se save hote hain, isliye unpar asar nahi padta.
$marks = implode(",", array_fill(0, count($keep), "?"));
$params = $keep;
$params[] = $outletId;

$pdo->prepare("DELETE FROM items WHERE id NOT IN ($marks) AND outlet_id = ?")->execute($params);

$pdo->commit();

send(["ok" => true]);
