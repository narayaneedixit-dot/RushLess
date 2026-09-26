<?php
// Creates one outlet and its login account.
// Only the college master account is allowed to do this.
require_once "db.php";
require_once "equipment_lib.php";

if (currentRole() != "college") {
    send(["error" => "Only the college account can register an outlet."]);
}

$category = $_POST["category"] ?? "";
$outlet   = trim($_POST["outlet_name"] ?? "");
$email    = strtolower(trim($_POST["email"] ?? ""));
$password = $_POST["password"] ?? "";
$names    = $_POST["item_name"] ?? [];
$prices   = $_POST["item_price"] ?? [];
$preps    = $_POST["item_prep"] ?? [];
$itemEquip = $_POST["item_equipment"] ?? [];   // har item ka station (naam se)
$eqNames  = $_POST["equip_name"] ?? [];
$eqQtys   = $_POST["equip_qty"] ?? [];

if ($outlet == "" || $email == "" || $password == "") {
    send(["error" => "Please fill in the outlet name, email and password."]);
}
if (!in_array($category, ["canteen", "stationary"])) {
    send(["error" => "Please choose a category."]);
}

// Email already used?
$check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$check->execute([$email]);
if ($check->fetch()) {
    send(["error" => "This email is already registered."]);
}

// Photo upload (optional)
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

// Save user, outlet and items together
$pdo->beginTransaction();

$sql = $pdo->prepare("INSERT INTO users (role, email, password, created_at) VALUES ('admin', ?, ?, NOW())");
$sql->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);
$userId = $pdo->lastInsertId();

$sql = $pdo->prepare("INSERT INTO outlets (user_id, category, name, photo, created_at) VALUES (?, ?, ?, ?, NOW())");
$sql->execute([$userId, $category, $outlet, $photo]);
$outletId = $pdo->lastInsertId();

// Pehle equipment, kyunki items unse jude honge
$equipIds = saveEquipment($pdo, $outletId, $eqNames, $eqQtys);

$sql = $pdo->prepare("INSERT INTO items (outlet_id, name, price, prep_min, equipment_id) VALUES (?, ?, ?, ?, ?)");
$saved = 0;

for ($i = 0; $i < count($names); $i++) {
    $itemName = trim($names[$i]);
    $itemPrice = $prices[$i] ?? "";

    if ($itemName != "" && is_numeric($itemPrice)) {
        $prep = (isset($preps[$i]) && is_numeric($preps[$i]) && $preps[$i] > 0) ? $preps[$i] : 2;

        // Item ka station: naam se dhoondo. Na mile to NULL (koi machine nahi chahiye).
        $station = trim($itemEquip[$i] ?? "");
        $equipId = isset($equipIds[$station]) ? $equipIds[$station] : null;

        $sql->execute([$outletId, $itemName, $itemPrice, $prep, $equipId]);
        $saved++;
    }
}

if ($saved == 0) {
    $pdo->rollBack();
    send(["error" => "Please add at least one menu item with its price."]);
}

$pdo->commit();

send(["ok" => true]);
