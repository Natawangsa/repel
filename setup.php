<?php
/**
 * HaFI85 Digital Printing — Setup Database
 * Jalankan sekali: php setup.php
 */

$dbPath = __DIR__ . '/database.sqlite';

// Hapus database lama jika ada agar mulai bersih
if (file_exists($dbPath)) {
    unlink($dbPath);
    echo "✓ Database lama dihapus\n";
}

$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("PRAGMA journal_mode=WAL");

// ── Buat Tabel ─────────────────────────────────────────────────────────────

$db->exec("CREATE TABLE users (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    username   TEXT UNIQUE NOT NULL,
    password   TEXT NOT NULL,
    role       TEXT NOT NULL,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
)");

$db->exec("CREATE TABLE customers (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_id TEXT UNIQUE NOT NULL,
    name        TEXT NOT NULL,
    phone       TEXT NOT NULL,
    is_member   INTEGER DEFAULT 0,
    created_at  TEXT DEFAULT (datetime('now')),
    updated_at  TEXT DEFAULT (datetime('now'))
)");

$db->exec("CREATE TABLE orders (
    id                INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id          TEXT UNIQUE NOT NULL,
    customer_id       INTEGER NOT NULL,
    product_type      TEXT NOT NULL,
    panjang           REAL DEFAULT 0,
    lebar             REAL DEFAULT 0,
    bahan             TEXT,
    quantity          INTEGER DEFAULT 1,
    finishing_type    TEXT,
    deadline          TEXT,
    description       TEXT,
    design_file       TEXT,
    status            TEXT DEFAULT 'new_order',
    design_status     TEXT DEFAULT 'pending',
    total_price       REAL DEFAULT 0,
    down_payment      REAL DEFAULT 0,
    remaining_balance REAL DEFAULT 0,
    payment_method    TEXT DEFAULT 'cash',
    payment_status    TEXT DEFAULT 'unpaid',
    revision_note     TEXT,
    operator_note     TEXT,
    is_urgent         INTEGER DEFAULT 0,
    created_at        TEXT DEFAULT (datetime('now')),
    updated_at        TEXT DEFAULT (datetime('now'))
)");

$db->exec("CREATE TABLE expenses (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    amount      REAL NOT NULL,
    description TEXT,
    created_at  TEXT DEFAULT (datetime('now')),
    updated_at  TEXT DEFAULT (datetime('now'))
)");

$db->exec("CREATE TABLE print_sessions (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id       INTEGER NOT NULL,
    session_date  TEXT,
    status        TEXT DEFAULT 'idle',
    progress      INTEGER DEFAULT 0,
    estimate_time TEXT DEFAULT '00:00',
    ink_c         INTEGER DEFAULT 75,
    ink_m         INTEGER DEFAULT 60,
    ink_y         INTEGER DEFAULT 80,
    ink_k         INTEGER DEFAULT 30,
    printer_id    INTEGER DEFAULT 0,
    updated_at    TEXT DEFAULT (datetime('now'))
)");

$db->exec("CREATE TABLE print_session_orders (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id INTEGER NOT NULL,
    order_id   INTEGER NOT NULL
)");

$db->exec("CREATE TABLE printers (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    name            TEXT NOT NULL,
    machine         TEXT NOT NULL,
    ip_address      TEXT,
    connection_type TEXT DEFAULT 'lan',
    status          TEXT DEFAULT 'offline',
    ink_c           INTEGER DEFAULT 0,
    ink_m           INTEGER DEFAULT 0,
    ink_y           INTEGER DEFAULT 0,
    ink_k           INTEGER DEFAULT 0,
    progress        INTEGER DEFAULT 0,
    current_job     TEXT,
    error_msg       TEXT,
    last_seen       TEXT,
    created_at      TEXT DEFAULT (datetime('now')),
    updated_at      TEXT DEFAULT (datetime('now'))
)");

// ── Seed Users saja (tanpa customer & order) ───────────────────────────────
$users = [
    ['admin',     'admin123',     'admin'],
    ['desainer',  'desainer123',  'desainer'],
    ['operator_uv',      'operator123',  'operator'],
    ['operator_indoor',  'operator123',  'operator'],
    ['operator_bendera', 'operator123',  'operator'],
    ['operator_dtf',     'operator123',  'operator'],
    ['operator_banner',  'operator123',  'operator'],
    ['operator_a3',      'operator123',  'operator'],
    ['operator_laser1',  'operator123',  'operator'],
    ['operator_laser2',  'operator123',  'operator'],
    ['finishing', 'finishing123', 'finishing'],
    ['owner',     'owner123',     'owner'],
];

$stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
foreach ($users as [$u, $p, $r]) {
    $stmt->execute([$u, password_hash($p, PASSWORD_DEFAULT), $r]);
}

// Print sessions are now created on-demand per operator user (no hardcoded seed)

// Seed printers
$printerStmt = $db->prepare("INSERT INTO printers (name, machine, ip_address, connection_type) VALUES (?, ?, ?, ?)");
$printers = [
    ['PC Hafi 1 - UV',  'Nocai UV',            null,              'lan'],
    ['PC Indoor',       'Epson S40679',         '192.168.1.164',   'lan'],
    ['PC Bendera',      'Century Star',         '192.168.1.10',    'lan'],
    ['PC DTF',          'Kingjet DTF',          null,              'usb'],
    ['PC Banner',       'Allwin K3204B-512i',   null,              'usb'],
    ['PC Server - A3',  'Ricoh Pro C5100s',     '192.168.1.56',    'lan'],
    ['Laser Cutting 1', 'Laser Cutter',         '192.168.1.135',   'lan'],
    ['Laser Cutting 2', 'Laser Cutter',         null,              'usb'],
];
foreach ($printers as [$name, $machine, $ip, $conn]) {
    $printerStmt->execute([$name, $machine, $ip, $conn]);
}

echo "✓ Database baru berhasil dibuat (bersih, tanpa data dummy)!\n\n";
echo "✓ Akun login:\n";
echo "  admin            / admin123\n";
echo "  desainer         / desainer123\n";
echo "  operator_uv      / operator123     (PC Hafi 1 - UV)\n";
echo "  operator_indoor  / operator123     (PC Indoor)\n";
echo "  operator_bendera / operator123     (PC Bendera)\n";
echo "  operator_dtf     / operator123     (PC DTF)\n";
echo "  operator_banner  / operator123     (PC Banner)\n";
echo "  operator_a3      / operator123     (PC Server - A3)\n";
echo "  operator_laser1  / operator123     (Laser Cutting 1)\n";
echo "  operator_laser2  / operator123     (Laser Cutting 2)\n";
echo "  finishing        / finishing123\n";
echo "  owner            / owner123\n\n";
echo "Sekarang jalankan: php -S localhost:8000 -t public\n\n";

echo "=== ALUR KERJA ===\n";
echo "1. Admin input order\n";
echo "   - Ada file design dari pelanggan → langsung ke Operator\n";
echo "   - Tidak ada file design → ke Desainer dulu\n";
echo "2. Desainer upload design → Request Approval ke Admin\n";
echo "3. Admin approve → order masuk ke Operator\n";
echo "4. Operator pilih order → Mulai Print → Finish\n";
echo "5. Finishing kerjakan → Mark as Done\n";
echo "6. Admin dashboard: order muncul di 'Siap di Ambil'\n";
echo "7. Pelanggan ambil → Admin klik PAYMENT → input harga\n";
