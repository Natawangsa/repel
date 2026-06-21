<?php
require_once __DIR__ . "/../../includes/db.php";
require_once __DIR__ . "/../../includes/auth.php";
requireAuth("operator"); verifyCsrf();
$rows = dbSelect("SELECT o.id FROM print_session_orders pso JOIN orders o ON pso.order_id=o.id WHERE pso.session_id=1");
foreach ($rows as $r) dbRun("UPDATE orders SET status='finishing',updated_at=datetime('now') WHERE id=?", [$r->id]);
dbRun("DELETE FROM print_session_orders WHERE session_id=1");
dbRun("UPDATE print_sessions SET status='idle',progress=0,updated_at=datetime('now') WHERE id=1");
setFlash("success","Printing selesai! Order diteruskan ke Finishing.");
redirect("/operator/dashboard");
