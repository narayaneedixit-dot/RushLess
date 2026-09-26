<?php
// Database connection and small helpers used by every PHP file

session_start();

// PHP ke warning/notice seedha screen par na aayein, warna JSON toot jaata hai
ini_set("display_errors", "0");
error_reporting(E_ALL);
ob_start();

$host = "localhost";
$dbname = "rushless";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    send(["error" => "Database connection failed. Start MySQL and import database.sql."]);
}

// Agar koi PHP error aaye to bhi jawab JSON hi rahega
set_exception_handler(function ($e) {
    send(["error" => "Server error: " . $e->getMessage()]);
});

// Send a JSON reply and stop
function send($data) {
    if (ob_get_length() !== false) {
        ob_end_clean();          // PHP ka koi extra output hata do
    }
    header("Content-Type: application/json");
    echo json_encode($data);
    exit;
}

// Kaun logged in hai
function currentRole() {
    return $_SESSION["role"] ?? "";
}
