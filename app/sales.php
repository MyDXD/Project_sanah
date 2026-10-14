<?php
require_once __DIR__ . '/bootstrap.php';
function stock_change($product, $delta, $actor, $reason, $reference = '')
{
    $balance = (int) $product['stock'] + $delta;
    if ($balance > 2147483647) {
        throw new RuntimeException('จำนวน Stock เกินขอบเขตที่รองรับ');
    }
    if ($balance < 0) {
        throw new RuntimeException('สินค้า ' . $product['product_name'] . ' มี Stock ไม่พอ');
    }
    sql('UPDATE products SET stock=? WHERE product_id=?', [$balance, $product['product_id']]);
    sql('INSERT INTO stock_movements(product_id,actor_id,delta,balance,reason,reference_code) VALUES (?,?,?,?,?,?)', [$product['product_id'], $actor, $delta, $balance, $reason, $reference]);
}
function sale_lines($quantities, $actor, $reference, $reason)
{
    if (!$quantities || count($quantities) > 200) {
        throw new RuntimeException('กรุณาเลือกสินค้า');
    }
    ksort($quantities, SORT_NUMERIC);
    $lines = [];
    $total = 0;
    foreach ($quantities as $id => $quantity) {
        $quantity = integer($quantity, 1);
        $product = one('SELECT * FROM products WHERE product_id=? AND is_active=1 FOR UPDATE', [integer($id, 1)]);
        if (!$product) {
            throw new RuntimeException('ไม่พบสินค้า');
        }
        $price = cents($product['product_price']);
        $subtotal = $price * $quantity;
        $total += $subtotal;
        if ($total > 9999999999) {
            throw new RuntimeException('ยอดขายเกินขอบเขตที่รองรับ');
        }
        stock_change($product, -$quantity, $actor, $reason, $reference);
        $lines[] = ['id' => $id, 'quantity' => $quantity, 'name' => $product['product_name'], 'price' => decimal($price), 'cost' => $product['cost_price']];
    }
    return [$lines, decimal($total)];
}
function create_online_order($user, $quantities, $address, $phone, $method)
{
    global $conn;
    if (!in_array($method, ['transfer', 'qr'], true) || trim($address) === '' || !preg_match('/^\d{10}$/', $phone)) {
        throw new RuntimeException('ตรวจสอบที่อยู่ เบอร์โทรศัพท์ และวิธีชำระเงิน');
    }
    $conn->begin_transaction();
    try {
        one('SELECT user_id FROM userdata WHERE user_id=? FOR UPDATE', [$user['user_id']]);
        $cart = rows('SELECT product_id,quantity FROM cart WHERE user_id=? FOR UPDATE', [$user['user_id']]);
        $allowed = array_column($cart, 'product_id');
        if (!$cart || array_diff(array_keys($quantities), $allowed) || count($quantities) !== count(array_unique($allowed))) {
            throw new RuntimeException('ตะกร้าเปลี่ยนไป กรุณาตรวจสอบอีกครั้ง');
        }
        $code = 'WEB-' . bin2hex(random_bytes(8));
        [$lines, $total] = sale_lines($quantities, $user['user_id'], $code, 'ขายออนไลน์');
        sql("INSERT INTO orders(user_id,total_price,order_status,order_code,delivery_address,phone_number,payment_method,transport_id) VALUES (?,?,'Order Placed',?,?,?,?,'')", [$user['user_id'], $total, $code, $address, $phone, $method]);
        $id = $conn->insert_id;
        foreach ($lines as $line) {
            sql('INSERT INTO order_items(order_id,product_id,quantity,price,unit_cost,product_name) VALUES (?,?,?,?,?,?)', [$id, $line['id'], $line['quantity'], $line['price'], $line['cost'], $line['name']]);
        }
        sql('DELETE FROM cart WHERE user_id=?', [$user['user_id']]);
        $conn->commit();
        return $id;
    } catch (Throwable $exception) {
        $conn->rollback();
        throw $exception;
    }
}
function save_pos($actor, $quantities, $method, $note, $edit = 0)
{
    global $conn;
    if (!isset(PAYMENT_METHODS[$method]) || strlen($note) > 255) {
        throw new RuntimeException('ตรวจสอบวิธีชำระเงินและหมายเหตุ');
    }
    $conn->begin_transaction();
    try {
        if ($edit) {
            if ($actor['role'] !== 'admin') {
                throw new RuntimeException('เฉพาะ Admin แก้ไขการขายได้');
            }
            $sale = one('SELECT * FROM pos_sales WHERE id=? FOR UPDATE', [$edit]);
            if (!$sale || $sale['status'] !== 'Completed') {
                throw new RuntimeException('ไม่พบรายการขายที่แก้ไขได้');
            }
            $code = $sale['sale_code'];
            foreach (rows('SELECT * FROM pos_items WHERE sale_id=? ORDER BY product_id', [$edit]) as $line) {
                $product = one('SELECT * FROM products WHERE product_id=? FOR UPDATE', [$line['product_id']]);
                stock_change($product, (int) $line['quantity'], $actor['user_id'], 'คืน Stock ก่อนแก้ไขการขาย', $code);
            }
            sql('DELETE FROM pos_items WHERE sale_id=?', [$edit]);
            $id = $edit;
        } else {
            $code = 'POS-' . bin2hex(random_bytes(8));
            sql('INSERT INTO pos_sales(sale_code,actor_id,payment_method,note) VALUES (?,?,?,?)', [$code, $actor['user_id'], $method, $note]);
            $id = $conn->insert_id;
        }
        [$lines, $total] = sale_lines($quantities, $actor['user_id'], $code, $edit ? 'แก้ไขการขายหน้าร้าน' : 'ขายหน้าร้าน');
        foreach ($lines as $line) {
            sql('INSERT INTO pos_items(sale_id,product_id,product_name,quantity,price,unit_cost) VALUES (?,?,?,?,?,?)', [$id, $line['id'], $line['name'], $line['quantity'], $line['price'], $line['cost']]);
        }
        sql('UPDATE pos_sales SET total=?,payment_method=?,note=? WHERE id=?', [$total, $method, $note, $id]);
        audit($actor['user_id'], $edit ? 'pos_edit' : 'pos_create', $code);
        $conn->commit();
        return $id;
    } catch (Throwable $exception) {
        $conn->rollback();
        throw $exception;
    }
}
function order_status($id, $status, $actor, $customer = false)
{
    global $conn;
    if (!isset(ORDER_STATUSES[$status])) {
        throw new RuntimeException('สถานะไม่ถูกต้อง');
    }
    $conn->begin_transaction();
    try {
        $order = one('SELECT * FROM orders WHERE order_id=? FOR UPDATE', [$id]);
        if (!$order || ($customer && $order['user_id'] != $actor['user_id'])) {
            throw new RuntimeException('ไม่พบออเดอร์');
        }
        if ($customer && !(($order['order_status'] === 'Order Placed' && $status === 'Cancelled') || ($order['order_status'] === 'Shipped' && $status === 'Completed'))) {
            throw new RuntimeException('ยังไม่สามารถดำเนินการนี้ได้');
        }
        if (!in_array($status, ORDER_TRANSITIONS[$order['order_status']] ?? [], true)) {
            throw new RuntimeException('ไม่สามารถเปลี่ยนสถานะตามลำดับนี้ได้');
        }
        if (in_array($status, ['Shipped', 'Completed'], true) && $order['payment_status'] !== 'paid') {
            throw new RuntimeException('ต้องตรวจสอบการชำระเงินก่อนจัดส่ง');
        }
        if ($status === 'Cancelled' && !$order['stock_restored']) {
            foreach (rows('SELECT * FROM order_items WHERE order_id=? ORDER BY product_id', [$id]) as $line) {
                $product = one('SELECT * FROM products WHERE product_id=? FOR UPDATE', [$line['product_id']]);
                stock_change($product, (int) $line['quantity'], $actor['user_id'], 'คืน Stock จากออเดอร์ยกเลิก', $order['order_code']);
            }
            sql('UPDATE orders SET stock_restored=1 WHERE order_id=?', [$id]);
        }
        sql('UPDATE orders SET order_status=? WHERE order_id=?', [$status, $id]);
        audit($actor['user_id'], 'order_status', $id . ':' . $status);
        $conn->commit();
    } catch (Throwable $exception) {
        $conn->rollback();
        throw $exception;
    }
}
