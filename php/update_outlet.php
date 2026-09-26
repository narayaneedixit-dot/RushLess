<?php
// Updates an outlet: its details, its login email, and its whole menu
require_once "db.php";
require_once "equipment_lib.php";

if (currentRole() != "college") {
    send(["error" => "Only the college account can edit an outlet."]);
}

$id       = $_POST["outlet_id"] ?? 0;
$category = $_POST["category"] ?? "";
$outlet   = trim($_POST["outlet_name"] ?? "");
$email    = strtolower(trim($_POST["email"] ?? ""));
$password = $_POST["password"] ?? "";
$names    = $_POST["item_name"] ?? [];
$prices   = $_POST["item_price"] ?? [];
$preps    = $_POST["item_prep"] ?? [];
$itemEquip = $_POST["item_equipment"] ?? [];
$eqNames  = $_POST["equip_name"] ?? [];
$eqQtys   = $_POST["equip_qty"] ?? [];

if ($outlet == "" || $email == "") {
    send(["error" => "Please fill in the outlet name and email."]);
}
if (!in_array($category, ["canteen", "stationary"])) {
    send(["error" => "Please choose a category."]);
}

// Does this outlet exist?
$sql = $pdo->prepare("SELECT user_id FROM outlets WHERE id = ?");
$sql->execute([$id]);
$row = $sql->fetch();

if (!$row) {
    send(["error" => "Outlet not found."]);
}
$userId = $row["user_id"];

// Is this email used by somebody else?
$sql = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
$sql->execute([$email, $userId]);
if ($sql->fetch()) {
    send(["error" => "This email is already registered."]);
}

// New photo only if a new file was chosen
$photo = null;
if (isset($_FILES["outlet_photo"]) && $_FILES["outlet_photo"]["error"] == 0) {
    $ext = strtolower(pathinfo($_FILES["outlet_photo"]["name"], PATHINFO_EXTENSION));
    if (!in_array($ext, ["jpg", "jpeg", "png", "webp"])) {
        send(["error" => "Photo must be a JPG, PNG or WEBP file."]);
    }
    $newName = "outlet_" . time() . "." . $ext;
    move_uploaded_file($_FILES["outlet_photo"]["tmp_name"], "../uploads/" . $newName);
    $photo = "uploads/" . $newName;
}

$pdo->beginTransaction();

// Outlet details
if ($photo) {
    $sql = $pdo->prepare("UPDATE outlets SET category = ?, name = ?, photo = ? WHERE id = ?");
    $sql->execute([$category, $outlet, $photo, $id]);
} else {
    $sql = $pdo->prepare("UPDATE outlets SET category = ?, name = ? WHERE id = ?");
    $sql->execute([$category, $outlet, $id]);
}

// Login email, and password only if a new one was typed
$sql = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
$sql->execute([$email, $userId]);

if ($password != "") {
    $sql = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $sql->execute([password_hash($password, PASSWORD_DEFAULT), $userId]);
}

// Menu aur equipment: purane hata kar naye daal dete hain (simple aur samajhne mein aasan).
// Items pehle jaate hain, kyunki woh equipment se jude hote hain.
$pdo->prepare("DELETE FROM items WHERE outlet_id = ?")->execute([$id]);
$pdo->prepare("DELETE FROM equipment WHERE outlet_id = ?")->execute([$id]);

$equipIds = saveEquipment($pdo, $id, $eqNames, $eqQtys);

$sql = $pdo->prepare("INSERT INTO items (outlet_id, name, price, prep_min, equipment_id) VALUES (?, ?, ?, ?, ?)");
$saved = 0;

for ($i = 0; $i < count($names); $i++) {
    $itemName = trim($names[$i]);
    $itemPrice = $prices[$i] ?? "";

    if ($itemName != "" && is_numeric($itemPrice)) {
        $prep = (isset($preps[$i]) && is_numeric($preps[$i]) && $preps[$i] > 0) ? $preps[$i] : 2;

        $station = trim($itemEquip[$i] ?? "");
        $equipId = isset($equipIds[$station]) ? $equipIds[$station] : null;

        $sql->execute([$id, $itemName, $itemPrice, $prep, $equipId]);
        $saved++;
    }
}

if ($saved == 0) {
    $pdo->rollBack();
    send(["error" => "Please add at least one menu item with its price."]);
}

$pdo->commit();

send(["ok" => true]);
