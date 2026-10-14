<?php
require __DIR__ . '/app/bootstrap.php';
$user=require_roles(['admin','employee','user']);
$id=(int)($_GET['id'] ?? 0);
$order=one('SELECT user_id,payment_slip FROM orders WHERE order_id=?',[$id]);
if (!$order || ($user['role']==='user' && $order['user_id']!=$user['user_id'])) { http_response_code(404); exit; }
$root=realpath(__DIR__.'/payment_slip');
$file=realpath(__DIR__.'/'.($order['payment_slip'] ?? ''));
if (!$file || !str_starts_with($file,$root.DIRECTORY_SEPARATOR) || !is_file($file)) { http_response_code(404); exit; }
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($file);
if (!in_array($mime,['image/png','image/jpeg','image/webp'],true)) { http_response_code(404); exit; }
header('Content-Type: '.$mime);
header('Content-Length: '.filesize($file));
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
readfile($file);
