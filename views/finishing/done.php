<?php
require_once __DIR__ . "/../../includes/db.php";
require_once __DIR__ . "/../../includes/auth.php";
requireAuth("finishing"); verifyCsrf();
$orderId = trim($_POST["order_id"] ?? "");
dbRun("UPDATE orders SET status='done',updated_at=datetime('now') WHERE order_id=?", [$orderId]);
setFlash("success", "Order {$orderId} selesai!");
redirect("/finishing/dashboard");
