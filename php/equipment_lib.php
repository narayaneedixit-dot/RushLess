<?php
/*
 * Equipment save karne ka common code — outlet register karte waqt aur edit karte waqt,
 * dono jagah wahi chahiye.
 *
 * Wapas ek list deta hai: station ka naam => uski id. Isse items ko station se jodte hain.
 */

function saveEquipment($pdo, $outletId, $names, $qtys) {

    $map = [];

    for ($i = 0; $i < count($names); $i++) {

        $name = trim($names[$i] ?? "");

        if ($name == "") {
            continue;
        }

        $qty = (int)($qtys[$i] ?? 1);

        if ($qty < 1) {
            $qty = 1;       // kam se kam ek unit to hoga hi
        }

        $sql = $pdo->prepare("INSERT INTO equipment (outlet_id, name, quantity) VALUES (?, ?, ?)");
        $sql->execute([$outletId, $name, $qty]);

        $map[$name] = $pdo->lastInsertId();
    }

    return $map;
}
