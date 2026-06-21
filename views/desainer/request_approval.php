<?php
require_once __DIR__ . "/../../includes/db.php";
require_once __DIR__ . "/../../includes/auth.php";
requireAuth("desainer"); verifyCsrf();
$orderId = trim($_POST["order_id"] ?? "");
dbUpdate("orders", ["design_status" => "need_approval"], "order_id = ?", [$orderId]);
setFlash("success", "Design dikirim untuk approval!");
redirect("/desainer/dashboard");
