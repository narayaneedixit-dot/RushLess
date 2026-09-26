<?php
// Creates a student account
require_once "db.php";

$name     = trim($_POST["name"] ?? "");
$email    = strtolower(trim($_POST["email"] ?? ""));
$roll     = trim($_POST["roll_no"] ?? "");
$branch   = trim($_POST["branch"] ?? "");
$semester = trim($_POST["semester"] ?? "");
$password = $_POST["password"] ?? "";

if ($name == "" || $email == "" || $roll == "" || $branch == "" || $semester == "" || $password == "") {
    send(["error" => "Please fill in all the fields."]);
}

$check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$check->execute([$email]);
if ($check->fetch()) {
    send(["error" => "This email is already registered."]);
}

$sql = $pdo->prepare("INSERT INTO users (role, email, password, name, roll_no, branch, semester, created_at)
                      VALUES ('student', ?, ?, ?, ?, ?, ?, NOW())");
$sql->execute([$email, password_hash($password, PASSWORD_DEFAULT), $name, $roll, $branch, $semester]);

$_SESSION["user_id"] = $pdo->lastInsertId();
$_SESSION["role"] = "student";

send(["ok" => true]);
