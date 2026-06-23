<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAuth('operator');
verifyCsrf();
$userId  = authUser()['id'];
$session = getOrCreateSession($userId);
dbRun("UPDATE print_sessions SET status='printing', updated_at=datetime('now') WHERE id=?", [$session->id]);
redirect('/operator/progress');
