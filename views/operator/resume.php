<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAuth('operator');
verifyCsrf();
dbRun("UPDATE print_sessions SET status='printing', updated_at=datetime('now') WHERE id=1");
redirect('/operator/progress');
