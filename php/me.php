<?php
// Batata hai ki abhi kaun logged in hai (debugging aur page checks ke liye)
require_once "db.php";

send([
    "logged_in" => isset($_SESSION["user_id"]),
    "role" => currentRole()
]);
