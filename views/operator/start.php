<?php
require_once __DIR__ . "/../../includes/db.php";
require_once __DIR__ . "/../../includes/auth.php";
requireAuth("operator"); verifyCsrf();
$userId  = authUser()['id'];
$session = getOrCreateSession($userId);
$sessionId = $session->id;
$printerId = (int)($_POST['printer_id'] ?? 0);
dbRun("UPDATE print_sessions SET status='printing',progress=0,estimate_time='05:00',printer_id=?,updated_at=datetime('now') WHERE id=?", [$printerId, $sessionId]);
$rows = dbSelect("SELECT o.id FROM print_session_orders pso JOIN orders o ON pso.order_id=o.id WHERE pso.session_id=?", [$sessionId]);
foreach ($rows as $r) dbRun("UPDATE orders SET status='printing',updated_at=datetime('now') WHERE id=?", [$r->id]);
redirect("/operator/progress");
