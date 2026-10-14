<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_roles(['admin']);
redirect('/admin/products.php?new=1');
