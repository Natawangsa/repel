<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

sessionStart();

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri    = rtrim($uri, '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

// ── Static files ───────────────────────────────────────────────────────────
// (PHP built-in server serves files from public/ automatically)

// ── Route map ──────────────────────────────────────────────────────────────
function route(string $method, string $pattern, string $view): bool {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = rtrim($uri, '/') ?: '/';
    if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) return false;

    // Convert /path/:param to regex
    $regex = preg_replace('#:([a-z_]+)#', '(?P<$1>[^/]+)', $pattern);
    $regex = '#^' . $regex . '$#';

    if (!preg_match($regex, $uri, $m)) return false;

    // Push named params to $_GET
    foreach ($m as $k => $v) {
        if (is_string($k)) $_GET[$k] = $v;
    }

    require __DIR__ . '/../views/' . $view . '.php';
    return true;
}

// ── AUTH ───────────────────────────────────────────────────────────────────
if (route('GET',  '/',       'auth/login'))       exit;
if (route('GET',  '/login',  'auth/login'))       exit;
if (route('POST', '/login',  'auth/login_post'))  exit;
if (route('POST', '/logout', 'auth/logout'))      exit;

// ── ADMIN ──────────────────────────────────────────────────────────────────
if (route('GET',  '/admin/dashboard',      'admin/dashboard'))     exit;
if (route('GET',  '/admin/order',          'admin/order_form'))    exit;
if (route('POST', '/admin/order/store',    'admin/order_store'))   exit;
if (route('GET',  '/admin/order/detail',   'admin/order_detail'))  exit;
if (route('GET',  '/admin/payment',        'admin/payment'))       exit;
if (route('POST', '/admin/payment/store',  'admin/payment_store')) exit;
if (route('GET',  '/admin/customer',       'admin/customer'))      exit;
if (route('GET',  '/admin/search',         'admin/search'))        exit;
if (route('GET',  '/admin/reports',        'admin/reports'))       exit;
if (route('POST', '/admin/reports/filter', 'admin/reports_filter'))exit;
if (route('GET',  '/admin/settings',       'admin/settings'))      exit;
if (route('POST', '/admin/approve',        'admin/approve'))       exit;
if (route('POST', '/admin/order/pickup',   'admin/order_pickup'))  exit;
if (route('POST', '/admin/order/done',     'admin/order_done'))    exit;

// ── DESAINER ───────────────────────────────────────────────────────────────
if (route('GET',  '/desainer/dashboard',        'desainer/dashboard'))    exit;
if (route('GET',  '/desainer/order',            'desainer/order_detail')) exit;
if (route('POST', '/desainer/upload',           'desainer/upload'))       exit;
if (route('POST', '/desainer/request-approval', 'desainer/request_approval')) exit;
if (route('POST', '/desainer/goto-print',       'desainer/goto_print'))   exit;
if (route('GET',  '/desainer/settings',         'desainer/settings'))     exit;

// ── OPERATOR ───────────────────────────────────────────────────────────────
if (route('GET',  '/operator/dashboard',       'operator/dashboard'))      exit;
if (route('GET',  '/operator/progress',        'operator/progress'))       exit;
if (route('POST', '/operator/select',          'operator/select'))         exit;
if (route('POST', '/operator/start',           'operator/start'))          exit;
if (route('POST', '/operator/pause',           'operator/pause'))          exit;
if (route('POST', '/operator/resume',          'operator/resume'))         exit;
if (route('POST', '/operator/cancel',          'operator/cancel'))         exit;
if (route('POST', '/operator/finish',          'operator/finish'))         exit;
if (route('POST', '/operator/progress/update', 'operator/progress_update'))exit;
if (route('GET',  '/operator/settings',        'operator/settings'))       exit;

// ── FINISHING ──────────────────────────────────────────────────────────────
if (route('GET',  '/finishing/dashboard', 'finishing/dashboard')) exit;
if (route('POST', '/finishing/done',      'finishing/done'))      exit;
if (route('GET',  '/finishing/settings',  'finishing/settings'))  exit;

// ── OWNER ──────────────────────────────────────────────────────────────────
if (route('GET',  '/owner/dashboard',                 'owner/dashboard'))           exit;
if (route('GET',  '/owner/production-monitor',        'owner/production_monitor'))  exit;
if (route('GET',  '/owner/customer',                  'owner/customer'))            exit;
if (route('GET',  '/owner/search',                    'owner/search'))              exit;
if (route('GET',  '/owner/settings',                  'owner/settings'))            exit;
if (route('POST', '/owner/settings/change-password',  'owner/change_password'))     exit;

// ── API ─────────────────────────────────────────────────────────────────────
if (route('POST', '/api/printer-status', 'api/printer_status_post')) exit;
if (route('GET',  '/api/printer-status', 'api/printer_status_get'))  exit;

// ── 404 ────────────────────────────────────────────────────────────────────
http_response_code(404);
echo '<h1 style="font-family:sans-serif;text-align:center;margin-top:100px;">404 - Halaman tidak ditemukan</h1>';
