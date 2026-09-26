<?php
// Session khatam karke wapas login page par bhej deta hai
session_start();
session_destroy();
header("Location: ../index.html");
exit;
