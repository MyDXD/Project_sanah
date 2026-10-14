<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_roles(['admin']);
redirect('/admin/users.php?new=1');
