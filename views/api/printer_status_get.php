<?php
/**
 * GET /api/printer-status
 * Returns current status of all printers as JSON.
 * Protected by session auth (operator/admin/owner).
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

// Allow both session auth and API key auth
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
$validKey = 'hafi85-printer-agent-key';

$authenticated = false;
if ($apiKey && hash_equals($validKey, $apiKey)) {
    $authenticated = true;
} else {
    sessionStart();
    if (isset($_SESSION['user'])) {
        $authenticated = true;
    }
}

if (!$authenticated) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$printers = dbSelect("SELECT * FROM printers ORDER BY id ASC");

$result = [];
foreach ($printers as $p) {
    $result[] = [
        'id'              => $p->id,
        'name'            => $p->name,
        'machine'         => $p->machine,
        'ip_address'      => $p->ip_address,
        'connection_type' => $p->connection_type ?? 'lan',
        'status'          => $p->status,
        'ink_c'           => (int)$p->ink_c,
        'ink_m'           => (int)$p->ink_m,
        'ink_y'           => (int)$p->ink_y,
        'ink_k'           => (int)$p->ink_k,
        'progress'        => (int)$p->progress,
        'current_job'     => $p->current_job,
        'error_msg'       => $p->error_msg,
        'last_seen'       => $p->last_seen,
    ];
}

echo json_encode(['printers' => $result]);
