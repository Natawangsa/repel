<?php
function sessionStart(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
}

function authUser(): ?array {
    sessionStart();
    return $_SESSION['user'] ?? null;
}

function requireAuth(string ...$roles): void {
    sessionStart();
    if (!isset($_SESSION['user'])) {
        redirect('/');
    }
    if (!empty($roles) && !in_array($_SESSION['user']['role'], $roles)) {
        http_response_code(403);
        die('<h1>403 — Akses Ditolak</h1>');
    }
}

function setFlash(string $type, string $msg): void {
    sessionStart();
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    sessionStart();
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

function csrfToken(): string {
    sessionStart();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verifyCsrf(): void {
    $token = $_POST['_token'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(419);
        die('CSRF token mismatch');
    }
}

function h(mixed $val): string {
    return htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
}

function old(string $key, string $default = ''): string {
    return h($_SESSION['old'][$key] ?? $default);
}

function saveOld(array $data): void {
    sessionStart();
    $_SESSION['old'] = $data;
}

function clearOld(): void {
    unset($_SESSION['old']);
}

function generateOrderId(): string {
    $last = dbSelectOne("SELECT order_id FROM orders ORDER BY id DESC LIMIT 1");
    $num  = $last ? (int)substr($last->order_id, 2) + 1 : 10001;
    return 'HF' . $num;
}

function generateCustomerId(): string {
    $last = dbSelectOne("SELECT customer_id FROM customers ORDER BY id DESC LIMIT 1");
    $num  = $last ? (int)substr($last->customer_id, 2) + 1 : 10001;
    return 'HF' . $num;
}

function formatRp(float $amount): string {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function statusBadge(string $status): string {
    $map = [
        'new_order'   => ['NEW ORDER',   '#FF8C00'],
        'in_revision' => ['IN REVISION', '#FFD700'],
        'approved'    => ['APPROVED',    '#28a745'],
        'ready_print' => ['SIAP CETAK',  '#6B4EFF'],
        'printing'    => ['PRINTING',    '#007bff'],
        'finishing'   => ['FINISHING',   '#6f42c1'],
        'done'        => ['DONE',        '#28a745'],
        'picked_up'   => ['DIAMBIL',     '#6B7280'],
        'cancelled'   => ['CANCELLED',   '#dc3545'],
    ];
    [$label, $color] = $map[$status] ?? [strtoupper($status), '#6c757d'];
    return "<span class='badge' style='background:{$color};color:#fff;'>{$label}</span>";
}

function payBadge(string $status): string {
    $map = ['paid'=>'#22c55e','partial'=>'#f97316','unpaid'=>'#ef4444'];
    $color = $map[$status] ?? '#6c757d';
    return "<span class='badge' style='background:{$color};color:#fff;'>".strtoupper($status)."</span>";
}

function urgentIcon(string $title = 'Urgent'): string {
    return "<svg class='urgent-icon' viewBox='0 0 24 24' fill='#ef4444' xmlns='http://www.w3.org/2000/svg' title='" . htmlspecialchars($title) . "'>
      <path d='M12 2L1 21h22L12 2z' fill='#ef4444'/>
      <text x='12' y='18' text-anchor='middle' font-size='11' font-weight='900' fill='white' font-family='Arial'>!</text>
    </svg>";
}

