<?php
require_once __DIR__ . '/sales.php';
require_once __DIR__ . '/layout.php';
$section = $section ?? 'product';
$user = in_array($section, ['product', 'scan'], true) ? current_user() : require_roles($section === 'feedback' ? ['user', 'admin'] : ['user', 'employee', 'admin']);
$error = '';
$id = (int) ($_GET['id'] ?? $_GET['order_id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $user = require_roles(['user', 'employee', 'admin']);
        check_csrf();
        $action = $_POST['action'] ?? '';
        if ($section === 'product' && $action === 'add_cart') {
            $conn->begin_transaction();
            one('SELECT user_id FROM userdata WHERE user_id=? FOR UPDATE', [$user['user_id']]);
            $pid = integer($_POST['product_id'] ?? '', 1);
            $product = one('SELECT product_id,stock FROM products WHERE product_id=? AND is_active=1', [$pid]);
            $quantity = integer($_POST['quantity'] ?? 1, 1);
            $existing = one('SELECT SUM(quantity) quantity FROM cart WHERE user_id=? AND product_id=?', [$user['user_id'], $pid]);
            $quantity += (int) ($existing['quantity'] ?? 0);
            if (!$product || $quantity > $product['stock']) {
                throw new RuntimeException('สินค้าใน Stock ไม่พอ');
            }
            sql('DELETE FROM cart WHERE user_id=? AND product_id=?', [$user['user_id'], $pid]);
            sql('INSERT INTO cart(user_id,product_id,quantity) VALUES (?,?,?)', [$user['user_id'], $pid, $quantity]);
            $conn->commit();
            redirect('/cart.php', 'เพิ่มสินค้าในตะกร้าแล้ว');
        } elseif ($section === 'cart' && $action === 'remove') {
            sql('DELETE FROM cart WHERE user_id=? AND product_id=?', [$user['user_id'], integer($_POST['product_id'] ?? '', 1)]);
            redirect('/cart.php', 'ลบสินค้าออกจากตะกร้าแล้ว');
        } elseif ($section === 'cart' && $action === 'update') {
            $conn->begin_transaction();
            one('SELECT user_id FROM userdata WHERE user_id=? FOR UPDATE', [$user['user_id']]);
            foreach ($_POST['quantity'] ?? [] as $pid => $qty) {
                $qty = integer($qty, 1);
                $pid = integer($pid, 1);
                $p = one('SELECT stock FROM products WHERE product_id=? AND is_active=1', [$pid]);
                if (!$p || $qty > $p['stock']) {
                    throw new RuntimeException('จำนวนสินค้าเกิน Stock');
                }
                sql('UPDATE cart SET quantity=? WHERE user_id=? AND product_id=?', [$qty, $user['user_id'], $pid]);
            }
            $conn->commit();
            redirect('/cart.php', 'อัปเดตตะกร้าแล้ว');
        } elseif ($section === 'cart' && $action === 'checkout') {
            $oid = create_online_order($user, $_POST['quantity'] ?? [], trim($_POST['delivery_address'] ?? ''), trim($_POST['phone_number'] ?? ''), $_POST['payment_method'] ?? '');
            redirect('/order_detail.php?order_id=' . $oid, 'สร้างคำสั่งซื้อแล้ว กรุณาชำระเงินและแนบหลักฐาน');
        } elseif ($section === 'orders' && $action === 'status') {
            order_status(integer($_POST['order_id'] ?? '', 1), $_POST['status'] ?? '', $user, true);
            redirect('/order.php', 'อัปเดตคำสั่งซื้อแล้ว');
        } elseif ($section === 'orders' && $action === 'slip') {
            $oid = integer($_POST['order_id'] ?? '', 1);
            $conn->begin_transaction();
            $order = one('SELECT order_id FROM orders WHERE order_id=? AND user_id=? AND order_status IN (\'Order Placed\',\'Order Processing\') AND payment_status<>\'paid\' FOR UPDATE', [$oid, $user['user_id']]);
            if (!$order) {
                throw new RuntimeException('ออเดอร์นี้ไม่สามารถแนบสลิปได้');
            }
            $path = image_upload('payment_slip', 'payment_slip');
            if (!$path) {
                throw new RuntimeException('กรุณาเลือกภาพหลักฐาน');
            }
            sql('UPDATE orders SET payment_slip=? WHERE order_id=?', [$path, $oid]);
            $conn->commit();
            redirect('/order_detail.php?order_id=' . $oid, 'ส่งหลักฐานแล้ว รอร้านตรวจสอบการชำระเงิน');
        } elseif ($section === 'profile' && $action === 'profile') {
            $first = trim($_POST['first_name'] ?? '');
            $last = trim($_POST['last_name'] ?? '');
            $phone = trim($_POST['phone_number'] ?? '');
            $address = trim($_POST['address'] ?? '');
            if ($first === '' || strlen($first) > 255 || strlen($last) > 255 || ($phone !== '' && !preg_match('/^\d{10}$/', $phone))) {
                throw new RuntimeException('กรุณาตรวจสอบชื่อและเบอร์โทรศัพท์');
            }
            sql('UPDATE userdata SET first_name=?,last_name=?,phone_number=?,address=? WHERE user_id=?', [$first, $last, $phone, $address, $user['user_id']]);
            redirect('/profile.php', 'บันทึกโปรไฟล์แล้ว');
        } elseif ($section === 'profile' && $action === 'password') {
            $old = one('SELECT password FROM userdata WHERE user_id=?', [$user['user_id']]);
            $new = $_POST['new_password'] ?? '';
            if (!password_verify($_POST['current_password'] ?? '', $old['password'])) {
                throw new RuntimeException('รหัสผ่านปัจจุบันไม่ถูกต้อง');
            }
            if ($new === '' || strlen($new) > 72 || $new !== ($_POST['confirm_password'] ?? '')) {
                throw new RuntimeException('ตรวจสอบรหัสผ่านใหม่และการยืนยันรหัสผ่าน');
            }
            sql('UPDATE userdata SET password=? WHERE user_id=?', [password_hash($new, PASSWORD_DEFAULT), $user['user_id']]);
            session_regenerate_id(true);
            redirect('/profile.php', 'เปลี่ยนรหัสผ่านแล้ว');
        } elseif ($section === 'feedback' && $action === 'feedback') {
            $title = trim($_POST['issue_title'] ?? '');
            $description = trim($_POST['issue_description'] ?? '');
            if ($title === '' || $description === '' || strlen($title) > 255) {
                throw new RuntimeException('กรุณากรอกหัวข้อและรายละเอียด');
            }
            sql("INSERT INTO reports(user_id,issue_title,issue_description,issue_attachment) VALUES (?,?,?,'')", [$user['user_id'], $title, $description]);
            redirect('/report_user.php', 'ส่งความคิดเห็นแล้ว คุณสามารถติดตามคำตอบในหน้านี้');
        } else {
            throw new RuntimeException('ไม่พบการดำเนินการ');
        }
    } catch (Throwable $exception) {
        $conn->rollback();
        $error = $exception instanceof mysqli_sql_exception ? 'บันทึกข้อมูลไม่สำเร็จ กรุณาลองใหม่' : $exception->getMessage();
        error_log($exception->getMessage());
    }
}
$titles = ['product' => 'รายละเอียดสินค้า', 'scan' => 'สแกน QR สินค้า', 'cart' => 'ตะกร้าสินค้า', 'orders' => 'คำสั่งซื้อของฉัน', 'profile' => 'โปรไฟล์', 'feedback' => 'ความคิดเห็น / ข้อเสนอแนะ'];
$detailProduct = $section === 'product' ? one('SELECT product_id,product_name,product_price,product_type,product_detail,product_img,stock FROM products WHERE product_id=? AND is_active=1', [$id]) : null;
$detailOrder = $section === 'orders' && $id ? one('SELECT order_id,order_code,order_date,total_price,order_status,delivery_address,phone_number,payment_method,payment_status,payment_slip,transport_id FROM orders WHERE order_id=? AND user_id=?', [$id, $user['user_id']]) : null;
if (($section === 'product' && !$detailProduct) || ($section === 'orders' && $id && !$detailOrder)) {
    http_response_code(404);
}
page_start($titles[$section]);
error_notice($error);
if ($section === 'product') {
    $p = $detailProduct;
    if (!$p) {
        http_response_code(404);
        error_notice('ไม่พบสินค้าหรือสินค้านี้ปิดขายแล้ว');
    } else { ?>
        <section class="card grid">
            <div><?php if ($p['product_img']): ?><img class="detail-image" src="/<?= e($p['product_img']) ?>"
                        alt="<?= e($p['product_name']) ?>"><?php endif; ?></div>
            <div><span class="badge"><?= e($p['product_type']) ?></span>
                <h2><?= e($p['product_name']) ?></h2>
                <p><?= nl2br(e($p['product_detail'])) ?></p>
                <h2><?= money($p['product_price']) ?> บาท</h2>
                <p>พร้อมขาย <?= $p['stock'] ?> ชิ้น</p><?php if ($user && $p['stock'] > 0): ?>
                    <form method="post"><?php csrf_field(); ?><input type="hidden" name="action" value="add_cart"><input
                            type="hidden" name="product_id"
                            value="<?= $id ?>"><?php field('จำนวน', 'quantity', 1, 'number', true, 'min="1" max="' . $p['stock'] . '"'); ?>
                        <div class="actions"><button>เพิ่มลงตะกร้า</button></div>
                    </form><?php elseif (!$user): ?><a class="button"
                        href="/login.php">เข้าสู่ระบบเพื่อซื้อสินค้า</a><?php else: ?><span
                        class="badge low">สินค้าหมด</span><?php endif; ?>
            </div>
        </section><?php }
} elseif ($section === 'scan') {
    ?>
    <section class="card">
        <h2>สแกนเพื่อดูรายละเอียดสินค้า</h2>
        <p>สแกน QR ของร้าน หรือกรอกรหัสสินค้าจากฉลาก</p><button id="start-scan" type="button">📷 เปิดกล้องสแกน</button>
        <div id="scanner-area" hidden><video id="scanner-video" class="scanner" playsinline muted></video><button
                id="stop-scan" type="button">หยุดกล้อง</button>
            <p id="scanner-message" role="status"></p>
        </div>
        <form action="/product_content.php" method="get" class="toolbar">
            <?php field('รหัสสินค้า', 'id', '', 'number', true, 'min="1"'); ?><button>ดูสินค้า</button></form>
    </section>
    <script src="/assets/vendor/jsQR.js"></script>
    <script src="/assets/scanner.js"></script>
    <script>attachScanner(id => location.href = '/product_content.php?id=' + id);</script><?php
} elseif ($section === 'cart') {
    $items = rows('SELECT p.product_id,p.product_name,p.product_price,p.stock,p.is_active,SUM(c.quantity) quantity FROM cart c JOIN products p ON p.product_id=c.product_id WHERE c.user_id=? GROUP BY p.product_id ORDER BY p.product_id', [$user['user_id']]);
    if (!$items) { ?>
        <section class="card">
            <p>ตะกร้ายังว่าง</p><a class="button" href="/products.php">เลือกสินค้า</a>
        </section><?php } else {
        $total = 0;
        foreach ($items as $i) {
            $total += cents($i['product_price']) * (int) $i['quantity'];
        }
        ?>
        <section class="card">
            <form method="post" id="cart-form"><?php csrf_field(); ?><input type="hidden" name="action"
                    value="update"><?php table_start(['สินค้า', 'ราคา', 'จำนวน', 'คงเหลือ', 'รวม']);
                    foreach ($items as $i): ?>
                    <tr data-cart-price="<?= e($i['product_price']) ?>">
                        <td><a href="/product_content.php?id=<?= $i['product_id'] ?>"><?= e($i['product_name']) ?></a>
                            <?= $i['is_active'] ? '' : '(ปิดขายแล้ว)' ?></td>
                        <td><?= money($i['product_price']) ?></td>
                        <td><input aria-label="จำนวน <?= e($i['product_name']) ?>" type="number" min="1" max="<?= max(1, $i['stock']) ?>"
                                name="quantity[<?= $i['product_id'] ?>]"
                                value="<?= e($_POST['quantity'][$i['product_id']] ?? $i['quantity']) ?>" required></td>
                        <td><?= $i['stock'] ?></td>
                        <td class="cart-line"><?= money($i['product_price'] * $i['quantity']) ?></td>
                    </tr><?php endforeach;
                    table_end(); ?>
                <h2>ยอดรวม <span id="cart-total"><?= money($total / 100) ?></span> บาท</h2><button>บันทึกจำนวนสินค้า</button>
            </form>
            <div class="actions"><?php foreach ($items as $i): ?>
                    <form method="post" class="inline"><?php csrf_field(); ?><input type="hidden" name="action"
                            value="remove"><input type="hidden" name="product_id" value="<?= $i['product_id'] ?>"><button
                            class="secondary">ลบ <?= e($i['product_name']) ?></button></form><?php endforeach; ?>
            </div>
        </section>
        <section class="card">
            <h2>ยืนยันคำสั่งซื้อ</h2>
            <form method="post" id="checkout-form"><?php csrf_field(); ?><input type="hidden" name="action"
                    value="checkout"><?php foreach ($items as $i): ?><input type="hidden" name="quantity[<?= $i['product_id'] ?>]"
                        value="<?= e($_POST['quantity'][$i['product_id']] ?? $i['quantity']) ?>"><?php endforeach; ?><label>ที่อยู่จัดส่ง<textarea
                        name="delivery_address"
                        required><?= e($_POST['delivery_address'] ?? $user['address']) ?></textarea></label>
                <div class="form-grid">
                    <?php field('เบอร์โทรศัพท์', 'phone_number', $_POST['phone_number'] ?? $user['phone_number'], 'tel', true, 'pattern="[0-9]{10}"'); ?><label>วิธีชำระเงิน<select
                            name="payment_method">
                            <option value="transfer">โอน</option>
                            <option value="qr">QR Payment</option>
                        </select></label></div>
                <div class="actions"><button>ยืนยันคำสั่งซื้อ</button></div>
            </form>
        </section>
        <script>
            document.querySelectorAll('[data-cart-price] input').forEach(input => input.addEventListener('input', () => { let total = 0; document.querySelectorAll('[data-cart-price]').forEach(row => { const qty = Number(row.querySelector('input').value) || 0, price = Math.round(Number(row.dataset.cartPrice) * 100); row.querySelector('.cart-line').textContent = (qty * price / 100).toFixed(2); total += qty * price; document.querySelector('#checkout-form input[name="' + row.querySelector('input').name + '"]').value = qty; }); document.getElementById('cart-total').textContent = (total / 100).toFixed(2); }));
        </script><?php
    }
} elseif ($section === 'orders') {
    if ($id) {
        $o = $detailOrder;
        if (!$o) {
            http_response_code(404);
            error_notice('ไม่พบออเดอร์ของคุณ');
        } else {
            ?>
            <section class="card">
                <h2><?= e($o['order_code']) ?></h2>
                <p><?= e(ORDER_STATUSES[$o['order_status']] ?? $o['order_status']) ?> · <?= e($o['order_date']) ?></p>
                <p><?= nl2br(e($o['delivery_address'])) ?> · <?= e($o['phone_number']) ?></p>
                <p>วิธีชำระ: <?= e(PAYMENT_METHODS[$o['payment_method']] ?? 'ไม่ระบุ') ?> ·
                    <?= $o['payment_status'] === 'paid' ? 'ร้านตรวจสอบเงินแล้ว' : 'รอตรวจสอบการชำระเงิน' ?></p>
                <?php if ($o['transport_id']): ?>
                    <p>เลขพัสดุ: <?= e($o['transport_id']) ?></p>
                <?php endif;
                table_start(['สินค้า', 'จำนวน', 'ราคา', 'รวม']);
                foreach (rows('SELECT product_name,quantity,price FROM order_items WHERE order_id=?', [$id]) as $i): ?>
                    <tr>
                        <td><?= e($i['product_name']) ?></td>
                        <td><?= $i['quantity'] ?></td>
                        <td><?= money($i['price']) ?></td>
                        <td><?= money($i['price'] * $i['quantity']) ?></td>
                    </tr><?php endforeach;
                table_end(); ?>
                <h2>ยอดรวม <?= money($o['total_price']) ?> บาท</h2><?php
                 if (in_array($o['order_status'], ['Order Placed', 'Order Processing'], true) && $o['payment_status'] !== 'paid') {
                     $s = settings(); ?>
                    <p><?= nl2br(e($s['bank_details'])) ?></p><?php if ($o['payment_method'] === 'qr' && $s['payment_qr']): ?><img
                            class="detail-image" src="/<?= e($s['payment_qr']) ?>"
                            alt="QR ชำระเงิน"><?php endif;
                    if (!$s['bank_details'] && !$s['payment_qr']): ?>
                        <p class="muted">ติดต่อร้านเพื่อรับข้อมูลชำระเงิน</p><?php endif; ?>
                    <form method="post" enctype="multipart/form-data"><?php csrf_field(); ?><input type="hidden" name="action"
                            value="slip"><input type="hidden" name="order_id" value="<?= $id ?>"><label>หลักฐานชำระเงิน<input type="file"
                                name="payment_slip" accept="image/jpeg,image/png,image/webp" required></label>
                        <div class="actions"><button>แนบหลักฐานการชำระเงิน</button></div>
                    </form><?php
                 }
                 if ($o['payment_slip']): ?>
                    <p><a href="/slip.php?id=<?= $o['order_id'] ?>" target="_blank" rel="noopener">ดูหลักฐานที่แนบแล้ว</a></p>
                <?php endif; ?>
            </section>
            <?php
        }
    }
    table_start(['ออเดอร์', 'วันที่', 'ยอดรวม', 'สถานะ', 'รายละเอียด', 'ดำเนินการ']);
    foreach (rows('SELECT order_id,order_code,order_date,total_price,order_status FROM orders WHERE user_id=? ORDER BY order_id DESC', [$user['user_id']]) as $o): ?>
        <tr>
            <td><?= e($o['order_code']) ?></td>
            <td><?= e($o['order_date']) ?></td>
            <td><?= money($o['total_price']) ?></td>
            <td><?= e(ORDER_STATUSES[$o['order_status']] ?? $o['order_status']) ?></td>
            <td><a href="/order_detail.php?order_id=<?= $o['order_id'] ?>">ดูรายละเอียด / แนบสลิป</a></td>
            <td><?php if (in_array($o['order_status'], ['Order Placed', 'Shipped'], true)): ?>
                    <form method="post" data-confirm="ยืนยันการดำเนินการกับออเดอร์นี้?"><?php csrf_field(); ?><input type="hidden"
                            name="action" value="status"><input type="hidden" name="order_id" value="<?= $o['order_id'] ?>"><input
                            type="hidden" name="status" value="<?= $o['order_status'] === 'Shipped' ? 'Completed' : 'Cancelled' ?>"><button
                            class="<?= $o['order_status'] === 'Shipped' ? '' : 'danger' ?>"><?= $o['order_status'] === 'Shipped' ? 'ยืนยันได้รับสินค้า' : 'ยกเลิก' ?></button>
                    </form><?php endif; ?>
            </td>
        </tr><?php endforeach;
    table_end();
} elseif ($section === 'profile') {
    ?>
    <section class="card">
        <h2>ข้อมูลของฉัน</h2>
        <p><?= e($user['email']) ?> · <?= e($user['role']) ?></p>
        <form method="post"><?php csrf_field(); ?><input type="hidden" name="action" value="profile">
            <div class="form-grid">
                <?php field('ชื่อ', 'first_name', $_POST['first_name'] ?? $user['first_name']);
                field('นามสกุล', 'last_name', $_POST['last_name'] ?? $user['last_name'], 'text', false);
                field('เบอร์โทรศัพท์', 'phone_number', $_POST['phone_number'] ?? $user['phone_number'], 'tel', false); ?><label>ที่อยู่<textarea
                        name="address"><?= e($_POST['address'] ?? $user['address']) ?></textarea></label></div>
            <div class="actions"><button>บันทึกข้อมูล</button></div>
        </form>
    </section>
    <section class="card">
        <h2>เปลี่ยนรหัสผ่าน</h2>
        <form method="post"><?php csrf_field(); ?><input type="hidden" name="action" value="password">
            <div class="form-grid">
                <?php field('รหัสผ่านปัจจุบัน', 'current_password', '', 'password', true, 'autocomplete="current-password"');
                field('รหัสผ่านใหม่', 'new_password', '', 'password', true, 'autocomplete="new-password"');
                field('ยืนยันรหัสผ่านใหม่', 'confirm_password', '', 'password', true, 'autocomplete="new-password"'); ?>
            </div>
            <div class="actions"><button>เปลี่ยนรหัสผ่าน</button></div>
        </form>
    </section><?php
} elseif ($section === 'feedback') {
    ?>
    <section class="card">
        <h2>ส่งความคิดเห็น / ข้อเสนอแนะ</h2>
        <form method="post"><?php csrf_field(); ?><input type="hidden" name="action"
                value="feedback"><?php field('หัวข้อ', 'issue_title', $_POST['issue_title'] ?? ''); ?><label>รายละเอียด<textarea
                    name="issue_description" required><?= e($_POST['issue_description'] ?? '') ?></textarea></label>
            <div class="actions"><button>ส่งความคิดเห็น</button></div>
        </form>
    </section>
    <h2>ความคิดเห็นของฉัน</h2>
    <?php
    foreach (rows('SELECT issue_title,issue_description,status,created_at,admin_response,replied_at FROM reports WHERE user_id=? ORDER BY report_id DESC', [$user['user_id']]) as $r): ?>
        <article class="card">
            <h3><?= e($r['issue_title']) ?></h3>
            <p class="muted"><?= e($r['created_at']) ?> ·
                <?= e(['new' => 'ใหม่', 'in_progress' => 'กำลังดำเนินการ', 'resolved' => 'ตอบกลับแล้ว'][$r['status']] ?? $r['status']) ?>
            </p>
            <p class="feedback-body"><?= e($r['issue_description']) ?></p><?php if ($r['admin_response']): ?>
                <div class="notice"><strong>คำตอบจากร้าน</strong>
                    <p class="feedback-body"><?= e($r['admin_response']) ?></p><span><?= e($r['replied_at']) ?></span>
                </div><?php else: ?>
                <p class="muted">รอร้านตอบกลับ</p><?php endif; ?>
        </article>
    <?php endforeach;
}
page_end();
