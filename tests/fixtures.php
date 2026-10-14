<?php
if (PHP_SAPI!=='cli') { exit; }
require __DIR__.'/../app/bootstrap.php';
$path=__DIR__.'/.fixtures.json';
$mode=$argv[1] ?? 'state';
if($mode==='setup') {
    if(file_exists($path)) { throw new RuntimeException('Clean up previous fixtures first'); }
    $tag='test_'.bin2hex(random_bytes(6)); $users=[];
    foreach(['admin','employee','user','outsider','disabled'] as $label) {
        $role=in_array($label,['outsider','disabled'])?'user':$label;
        $email=$tag.'_'.$label.'@example.com';
        sql("INSERT INTO userdata(email,password,first_name,last_name,title_name,address,phone_number,role,is_active) VALUES (?,?,'Integration','Test','','Test address','0123456789',?,?)",[$email,password_hash('Integration123!',PASSWORD_DEFAULT),$role,$label==='disabled'?0:1]);
        $users[$label]=['id'=>$conn->insert_id,'email'=>$email];
    }
    sql("INSERT INTO products(product_name,product_price,product_type,product_detail,product_img,stock,cost_price) VALUES (?,'25.00','Test','Test','',50,'10.00')",[$tag]);
    $fixtures=['tag'=>$tag,'users'=>$users,'product'=>$conn->insert_id];
    file_put_contents($path,json_encode($fixtures)); echo json_encode($fixtures); exit;
}
$f=json_decode(file_get_contents($path),true);
$ids=array_column($f['users'],'id'); $placeholders=implode(',',array_fill(0,count($ids),'?'));
if($mode==='cleanup') {
    foreach(rows("SELECT payment_slip FROM orders WHERE user_id IN ($placeholders)",$ids) as $order) {
        if ($order['payment_slip'] && preg_match('#^payment_slip/[a-f0-9]{32}\.(png|jpg|webp)$#',$order['payment_slip'])) { @unlink(__DIR__.'/../'.$order['payment_slip']); }
    }
    $conn->begin_transaction();
    sql("DELETE i FROM pos_items i JOIN pos_sales s ON s.id=i.sale_id WHERE s.actor_id IN ($placeholders)",$ids);
    sql("DELETE FROM pos_sales WHERE actor_id IN ($placeholders)",$ids);
    sql('DELETE FROM order_items WHERE product_id=?',[$f['product']]);
    sql("DELETE FROM orders WHERE user_id IN ($placeholders)",$ids);
    sql("DELETE FROM reports WHERE user_id IN ($placeholders)",$ids);
    sql("DELETE FROM audit_log WHERE actor_id IN ($placeholders)",$ids);
    sql("DELETE FROM stock_movements WHERE actor_id IN ($placeholders) OR product_id=?",array_merge($ids,[$f['product']]));
    sql("DELETE FROM cart WHERE user_id IN ($placeholders)",$ids);
    sql('DELETE FROM products WHERE product_id=?',[$f['product']]);
    sql("DELETE FROM userdata WHERE user_id IN ($placeholders)",$ids);
    // The registration test uses an exact dedicated email.
    sql('DELETE FROM userdata WHERE email=?',[$f['tag'].'_registered@example.com']);
    sql('DELETE FROM userdata WHERE email=?',[$f['tag'].'_created@example.com']);
    $conn->commit(); unlink($path); echo "Fixtures removed\n"; exit;
}
if($mode==='suspend') { sql('UPDATE userdata SET is_active=0 WHERE user_id=?',[$f['users']['employee']['id']]); exit; }
if($mode==='cost') { sql('UPDATE products SET cost_price=99 WHERE product_id=?',[$f['product']]); exit; }
if($mode==='state') {
    echo json_encode([
      'product'=>one('SELECT * FROM products WHERE product_id=?',[$f['product']]),
      'pos'=>rows("SELECT * FROM pos_sales WHERE actor_id IN ($placeholders) ORDER BY id",$ids),
      'pos_items'=>rows('SELECT * FROM pos_items WHERE product_id=? ORDER BY id',[$f['product']]),
      'orders'=>rows("SELECT * FROM orders WHERE user_id IN ($placeholders) ORDER BY order_id",$ids),
      'order_items'=>rows('SELECT * FROM order_items WHERE product_id=? ORDER BY order_item_id',[$f['product']]),
      'stock'=>rows('SELECT * FROM stock_movements WHERE product_id=? ORDER BY id',[$f['product']]),
      'registered'=>one('SELECT role,is_active FROM userdata WHERE email=?',[$f['tag'].'_registered@example.com']),
      'created'=>one('SELECT user_id,role,is_active FROM userdata WHERE email=?',[$f['tag'].'_created@example.com'])
    ]); exit;
}
