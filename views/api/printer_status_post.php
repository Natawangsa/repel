<?php
/**
 * POST /api/printer-status
 * Receives printer status data from local agent.
 * Protected by API key.
 */
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

// API key auth
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
$validKey = 'hafi85-printer-agent-key'; // TODO: move to config

if (!hash_equals($validKey, $apiKey)) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['printers']) || !is_array($input['printers'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload. Expected {"printers": [...]}']);
    exit;
}

$updated = 0;
foreach ($input['printers'] as $p) {
    $name = $p['name'] ?? '';
    if (!$name) continue;

    $existing = dbSelectOne("SELECT id FROM printers WHERE name = ?", [$name]);
    if (!$existing) continue;

    $data = [];
    if (isset($p['status']))      $data['status']      = $p['status'];
    if (isset($p['ink_c']))       $data['ink_c']       = (int)$p['ink_c'];
    if (isset($p['ink_m']))       $data['ink_m']       = (int)$p['ink_m'];
    if (isset($p['ink_y']))       $data['ink_y']       = (int)$p['ink_y'];
    if (isset($p['ink_k']))       $data['ink_k']       = (int)$p['ink_k'];
    if (isset($p['progress']))    $data['progress']    = (int)$p['progress'];
    if (isset($p['current_job'])) $data['current_job'] = $p['current_job'];
    if (isset($p['error_msg']))   $data['error_msg']   = $p['error_msg'];
    if (isset($p['ip_address']))  $data['ip_address']  = $p['ip_address'];
    $data['last_seen'] = date('Y-m-d H:i:s');

    if (!empty($data)) {
        dbUpdate('printers', $data, 'id = ?', [$existing->id]);
        $updated++;
    }
}

echo json_encode(['success' => true, 'updated' => $updated]);
