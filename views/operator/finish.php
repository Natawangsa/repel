<?php
require_once __DIR__ . "/../../includes/db.php";
require_once __DIR__ . "/../../includes/auth.php";
requireAuth("operator"); verifyCsrf();
$userId  = authUser()['id'];
$session = getOrCreateSession($userId);
$sessionId = $session->id;
$rows = dbSelect("SELECT o.id FROM print_session_orders pso JOIN orders o ON pso.order_id=o.id WHERE pso.session_id=?", [$sessionId]);
foreach ($rows as $r) dbRun("UPDATE orders SET status='finishing',updated_at=datetime('now') WHERE id=?", [$r->id]);
dbRun("DELETE FROM print_session_orders WHERE session_id=?", [$sessionId]);
dbRun("UPDATE print_sessions SET status='idle',progress=0,updated_at=datetime('now') WHERE id=?", [$sessionId]);
setFlash("success","Printing selesai! Order diteruskan ke Finishing.");
redirect("/operator/dashboard");
