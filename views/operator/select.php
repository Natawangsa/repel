<?php
require_once __DIR__ . "/../../includes/db.php";
require_once __DIR__ . "/../../includes/auth.php";
requireAuth("operator"); verifyCsrf();
$userId  = authUser()['id'];
$session = getOrCreateSession($userId);
$sessionId = $session->id;
$orderId = trim($_POST["order_id"] ?? "");
$action  = $_POST["action"] ?? "add";
$row = dbSelectOne("SELECT * FROM orders WHERE order_id=?", [$orderId]);
if (!$row) { redirect("/operator/dashboard"); }
if ($action === "remove") {
    dbRun("DELETE FROM print_session_orders WHERE session_id=? AND order_id=?", [$sessionId, $row->id]);
} else {
    $exists = dbSelectOne("SELECT id FROM print_session_orders WHERE session_id=? AND order_id=?", [$sessionId, $row->id]);
    if (!$exists) dbRun("INSERT INTO print_session_orders (session_id,order_id) VALUES (?,?)", [$sessionId, $row->id]);
}
redirect("/operator/dashboard");
