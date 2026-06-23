<?php
function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $path = __DIR__ . '/../database.sqlite';
        $pdo  = new PDO('sqlite:' . $path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        $pdo->exec("PRAGMA foreign_keys = ON");
    }
    return $pdo;
}

function dbSelect(string $sql, array $params = []): array {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function dbSelectOne(string $sql, array $params = []): ?object {
    $st = db()->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();
    return $row ?: null;
}

function dbInsert(string $table, array $data): int {
    $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
    $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');
    $cols = implode(',', array_keys($data));
    $vals = implode(',', array_fill(0, count($data), '?'));
    $st = db()->prepare("INSERT INTO $table ($cols) VALUES ($vals)");
    $st->execute(array_values($data));
    return (int) db()->lastInsertId();
}

function dbUpdate(string $table, array $data, string $where, array $whereParams = []): void {
    $data['updated_at'] = date('Y-m-d H:i:s');
    $set = implode(',', array_map(fn($k) => "$k=?", array_keys($data)));
    $st  = db()->prepare("UPDATE $table SET $set WHERE $where");
    $st->execute([...array_values($data), ...$whereParams]);
}

function dbRun(string $sql, array $params = []): void {
    $st = db()->prepare($sql);
    $st->execute($params);
}

function getOrCreateSession(int $userId): object {
    $session = dbSelectOne("SELECT * FROM print_sessions WHERE user_id=?", [$userId]);
    if (!$session) {
        dbRun("INSERT INTO print_sessions (user_id,session_date,status,progress,estimate_time,ink_c,ink_m,ink_y,ink_k) VALUES (?,date('now'),'idle',0,'00:00',75,60,80,30)", [$userId]);
        $session = dbSelectOne("SELECT * FROM print_sessions WHERE user_id=?", [$userId]);
    }
    return $session;
}
