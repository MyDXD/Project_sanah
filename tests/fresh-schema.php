<?php
if(PHP_SAPI!=='cli') { exit; }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$testConn=new mysqli('db','root','root_local');
$name='sanah_test_'.bin2hex(random_bytes(8));
$testConn->query("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
try {
    $testConn->select_db($name);
    foreach(['sanahstore.sql','02-roles-pos.sql','02-roles-pos.sql'] as $file) {
        $testConn->multi_query(file_get_contents(__DIR__.'/../DB/'.$file));
        do { if($result=$testConn->store_result()) { $result->free(); } } while($testConn->more_results() && $testConn->next_result());
    }
    $schema=$testConn->query("SHOW COLUMNS FROM userdata LIKE 'role'")->fetch_assoc();
    if(!str_contains($schema['Type'],'employee')) { throw new RuntimeException('Employee role missing'); }
    $count=$testConn->query('SELECT COUNT(*) n FROM reports')->fetch_assoc()['n'];
    $products=$testConn->query('SELECT COUNT(*) n FROM products')->fetch_assoc()['n'];
    $movements=$testConn->query('SELECT COUNT(*) n FROM stock_movements')->fetch_assoc()['n'];
    if($count!=0 || $products!=$movements) { throw new RuntimeException('Fresh schema or repeatable baseline failed'); }
    echo "Fresh import and repeat migration passed; $products products preserved.\n";
} finally { $testConn->query("DROP DATABASE `$name`"); }
