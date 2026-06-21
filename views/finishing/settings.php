<?php
require_once __DIR__ . "/../../includes/db.php";
require_once __DIR__ . "/../../includes/auth.php";
require_once __DIR__ . "/../../includes/layout.php";
requireAuth("finishing"); layoutStart("Settings","settings");
?><h1 class="page-title">SETTINGS</h1><div class="card" style="max-width:500px;"><p style="color:#6B7280;">Pengaturan.</p></div><?php layoutEnd(); ?>
