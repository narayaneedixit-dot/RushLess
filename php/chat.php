<?php
/*
 * Rule-based chatbot. It uses no AI, external API, or internet connection.
 * It identifies the question from keywords and uses live database data
 * to answer questions about orders, timings, menus, and outlets.
 */
require_once "db.php";

if (currentRole() != "student") {
    send(["reply" => "Please log in with a student account so I can look up your orders."]);
}

$userId = $_SESSION["user_id"];
$msg = strtolower(trim($_POST["message"] ?? ""));

if ($msg == "") {
    send(["reply" => "Choose a question below, or ask about your order status, estimated wait time, menu, or open outlets."]);
}

function has($msg, $words) {
    foreach ($words as $w) {
        if (strpos($msg, $w) !== false) {
            return true;
        }
    }
    return false;
}

function latestOrder($pdo, $userId) {
    $sql = $pdo->prepare("SELECT o.token, o.status, o.predicted_min, o.created_at, ou.name AS outlet,
                                 (SELECT COUNT(*) FROM orders x
                                   WHERE x.outlet_id = o.outlet_id
                                     AND x.status IN ('pending','preparing') AND x.id < o.id) AS ahead
                          FROM orders o
                          JOIN outlets ou ON ou.id = o.outlet_id
                          WHERE o.user_id = ? AND o.status <> 'collected'
                          ORDER BY o.id DESC LIMIT 1");
    $sql->execute([$userId]);
    return $sql->fetch();
}

// Order status
if (has($msg, ["order status", "where is my order", "my order", "status", "token", "order ready"])) {
    $o = latestOrder($pdo, $userId);

    if (!$o) {
        send(["reply" => "You do not have an active order. You can place an order from the Canteen page."]);
    }

    if ($o["status"] == "pending") {
        $reply = "Token " . $o["token"] . " (" . $o["outlet"] . ") is currently in the queue. "
               . $o["ahead"] . " order(s) are ahead of you.";
    } else if ($o["status"] == "preparing") {
        $reply = "Token " . $o["token"] . " is being prepared and should be ready soon.";
    } else {
        $reply = "Token " . $o["token"] . " is ready. Please collect it from the counter.";
    }

    send(["reply" => $reply]);
}

// Estimated wait time
if (has($msg, ["how long", "how much longer", "estimated wait", "wait time", "ready time", "time estimate"])) {
    $o = latestOrder($pdo, $userId);

    if (!$o) {
        send(["reply" => "You do not have an active order. Once you place an order, I can show its estimated ready time."]);
    }

    $readyAt = date("h:i A", strtotime($o["created_at"]) + $o["predicted_min"] * 60);

    send(["reply" => "The original estimate for token " . $o["token"] . " was " . $o["predicted_min"]
                   . " minutes, with an estimated ready time of around " . $readyAt . ". "
                   . "This estimate was based on the queue, rush-hour conditions, and your items."]);
}

// Open and closed outlets
if (has($msg, ["open", "closed", "outlet", "outlets"])) {
    $sql = $pdo->query("SELECT name, is_open FROM outlets WHERE category = 'canteen' ORDER BY id");
    $rows = $sql->fetchAll();

    if (count($rows) == 0) {
        send(["reply" => "No outlets have been registered yet."]);
    }

    $open = [];
    $closed = [];

    foreach ($rows as $r) {
        if ($r["is_open"] == 1) {
            $open[] = $r["name"];
        } else {
            $closed[] = $r["name"];
        }
    }

    $reply = count($open) > 0
        ? "Currently open: " . implode(", ", $open) . "."
        : "No outlets are currently open.";

    if (count($closed) > 0) {
        $reply .= " Currently closed: " . implode(", ", $closed) . ".";
    }

    send(["reply" => $reply]);
}

// Menu and prices
if (has($msg, ["menu", "price", "prices", "cost", "item", "items"])) {
    $sql = $pdo->query("SELECT i.name, i.price, ou.name AS outlet
                        FROM items i
                        JOIN outlets ou ON ou.id = i.outlet_id
                        WHERE i.is_available = 1 AND ou.is_open = 1
                        ORDER BY ou.id, i.id LIMIT 8");
    $rows = $sql->fetchAll();

    if (count($rows) == 0) {
        send(["reply" => "No outlets are currently open, so the menu is unavailable."]);
    }

    $parts = [];
    foreach ($rows as $r) {
        $parts[] = $r["name"] . " Rs " . (int)$r["price"];
    }

    send(["reply" => "Currently available: " . implode(", ", $parts)
                   . ". View the full menu on the Canteen page."]);
}

// Items with the shortest preparation times
if (has($msg, ["fast", "quick", "shortest preparation", "fastest"])) {
    $sql = $pdo->query("SELECT i.name, i.prep_min, ou.name AS outlet
                        FROM items i
                        JOIN outlets ou ON ou.id = i.outlet_id
                        WHERE i.is_available = 1 AND ou.is_open = 1
                        ORDER BY i.prep_min ASC LIMIT 3");
    $rows = $sql->fetchAll();

    if (count($rows) == 0) {
        send(["reply" => "No outlets are currently open."]);
    }

    $parts = [];
    foreach ($rows as $r) {
        $parts[] = $r["name"] . " (" . (float)$r["prep_min"] . " min)";
    }

    send(["reply" => "Items with the shortest preparation times: " . implode(", ", $parts) . "."]);
}

// Busy periods
if (has($msg, ["rush", "busy", "crowded", "crowd"])) {
    $sql = $pdo->query("SELECT hour_of_day, adjustment_min FROM slot_stats
                        ORDER BY adjustment_min DESC LIMIT 2");
    $rows = $sql->fetchAll();

    $parts = [];
    foreach ($rows as $r) {
        $parts[] = $r["hour_of_day"] . ":00 ( +" . (float)$r["adjustment_min"] . " min)";
    }

    send(["reply" => "The busiest times are: " . implode(" and ", $parts)
                   . ". Orders may take a little longer during these periods."]);
}

// How RushLess works
if (has($msg, ["how does", "how it works", "how rushless works"])) {
    send(["reply" => "RushLess estimates when each order will be ready. The estimate is based on three factors: "
                   . "the workload in the outlet queue, how busy the outlet is at that time, "
                   . "and how long your items take to prepare. It also considers the workload of the equipment "
                   . "used to prepare each item, so multiple orders sharing the same stove are accounted for. "
                   . "After an order is collected, its actual service time helps improve future estimates."]);
}

// Order cancellation
if (has($msg, ["cancel", "cancellation", "refund"])) {
    send(["reply" => "Orders cannot currently be cancelled in the app. Please speak to the counter. "
                   . "In-app cancellation is planned for a future update."]);
}

// Greeting
if (has($msg, ["hi", "hello", "hey"])) {
    send(["reply" => "Hello! I can help you check your order status, estimated wait time, menu, and which outlets are open."]);
}

send(["reply" => "I'm not sure I understood. Try asking: Where is my order? How much longer will it take, "
               . "what is on the menu, which outlets are open, or what can I get fastest."]);
