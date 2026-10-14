<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_roles(['admin']);
redirect('/admin/users.php?edit=' . (int)($_GET['id'] ?? $_GET['product_id'] ?? $_GET['user_id'] ?? 0));
