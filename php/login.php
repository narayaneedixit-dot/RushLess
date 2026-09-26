<?php
// Checks the email and password against the users table
require_once "db.php";

$email = strtolower(trim($_POST["email"] ?? ""));
$password = $_POST["password"] ?? "";

if ($email == "" || $password == "") {
    send(["error" => "Please enter your email and password."]);
}

$sql = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$sql->execute([$email]);
$user = $sql->fetch();

if (!$user || !password_verify($password, $user["password"])) {
    send(["error" => "Wrong email or password."]);
}

$_SESSION["user_id"] = $user["id"];
$_SESSION["role"] = $user["role"];

send(["ok" => true, "role" => $user["role"]]);
