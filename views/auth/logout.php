<?php
require_once __DIR__ . '/../../includes/auth.php';
sessionStart();
verifyCsrf();
session_destroy();
redirect('/');
