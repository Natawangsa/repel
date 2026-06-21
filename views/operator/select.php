<?php
require_once __DIR__ . "/../../includes/db.php";
require_once __DIR__ . "/../../includes/auth.php";
requireAuth("operator"); verifyCsrf();
$orderId = trim($_POST["order_id"] ?? "");
$action  = $_POST["action"] ?? "add";
$row = dbSelectOne("SELECT * FROM orders WHERE order_id=?", [$orderId]);
if (!$row) { redirect("/operator/dashboard"); }
dbRun("INSERT OR IGNORE INTO print_sessions (id,session_date,status,progress,estimate_time,ink_c,ink_m,ink_y,ink_k) VALUES (1,date('now'),'idle',0,'00:00',75,60,80,30)");
if ($action === "remove") {
    dbRun("DELETE FROM print_session_orders WHERE session_id=1 AND order_id=?", [$row->id]);
} else {
    $exists = dbSelectOne("SELECT id FROM print_session_orders WHERE session_id=1 AND order_id=?", [$row->id]);
    if (!$exists) dbRun("INSERT INTO print_session_orders (session_id,order_id) VALUES (1,?)", [$row->id]);
}
redirect("/operator/dashboard");
