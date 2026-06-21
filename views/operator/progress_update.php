<?php
require_once __DIR__ . "/../../includes/db.php";
require_once __DIR__ . "/../../includes/auth.php";
requireAuth("operator"); verifyCsrf();
dbRun("UPDATE print_sessions SET progress=?,estimate_time=?,ink_c=?,ink_m=?,ink_y=?,ink_k=?,updated_at=datetime('now') WHERE id=1",
    [(int)($_POST["progress"]??0), $_POST["estimate_time"]??"00:00", (int)($_POST["ink_c"]??75),(int)($_POST["ink_m"]??60),(int)($_POST["ink_y"]??80),(int)($_POST["ink_k"]??30)]);
setFlash("success","Progress diperbarui!");
redirect("/operator/progress");
