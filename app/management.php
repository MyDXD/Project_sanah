<?php
require_once __DIR__ . '/sales.php';
require_once __DIR__ . '/layout.php';
$section = $section ?? 'dashboard';
$adminOnly = ['products', 'users', 'feedback', 'reports', 'settings', 'qr'];
$adminRoute = str_starts_with($_SERVER['SCRIPT_NAME'] ?? '', '/admin/');
$actor = require_roles($adminRoute || in_array($section, $adminOnly, true) ? ['admin'] : ['admin', 'employee']);
$admin = $actor['role'] === 'admin';
$base = $admin ? '/admin/' : '/employee/';
$error = '';
$edit = (int) ($_GET['edit'] ?? 0);
$id = (int) ($_GET['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        check_csrf();
        $action = $_POST['action'] ?? '';
        if ($section === 'stock' && $action === 'receive') {
            $pid = integer($_POST['product_id'] ?? '', 1);
            $quantity = integer($_POST['quantity'] ?? '', 1);
            $reason = trim($_POST['reason'] ?? '');
            if ($reason === '' || strlen($reason) > 255) {
                throw new RuntimeException('กรุณาระบุเหตุผลไม่เกิน 255 ไบต์');
            }
            $conn->begin_transaction();
            $product = one('SELECT * FROM products WHERE product_id=? AND is_active=1 FOR UPDATE', [$pid]);
            if (!$product) {
                throw new RuntimeException('ไม่พบสินค้า');
            }
            stock_change($product, $quantity, $actor['user_id'], 'รับสินค้า: ' . $reason);
            $conn->commit();
        } elseif ($section === 'pos' && $action === 'sale') {
            $quantities = array_filter($_POST['quantity'] ?? [], fn($qty) => (string) $qty !== '0' && (string) $qty !== '');
            $saleId = save_pos($actor, $quantities, $_POST['payment_method'] ?? '', trim($_POST['note'] ?? ''), (int) ($_POST['edit_id'] ?? 0));
            redirect($base . 'sales.php?id=' . $saleId, 'บันทึกการขายและตัด Stock แล้ว');
        } elseif ($section === 'orders' && $action === 'status') {
            order_status(integer($_POST['order_id'] ?? '', 1), $_POST['status'] ?? '', $actor);
        } elseif ($section === 'orders' && $action === 'payment') {
            $oid = integer($_POST['order_id'] ?? '', 1);
            $conn->begin_transaction();
            $order = one('SELECT * FROM orders WHERE order_id=? FOR UPDATE', [$oid]);
            if (!$order || in_array($order['order_status'], ['Cancelled', 'Completed'], true)) {
                throw new RuntimeException('ออเดอร์นี้ไม่สามารถแก้ไขการชำระเงินได้');
            }
            sql("UPDATE orders SET payment_status='paid' WHERE order_id=?", [$oid]);
            audit($actor['user_id'], 'verify_payment', (string) $oid);
            $conn->commit();
        } elseif ($section === 'orders' && $action === 'tracking') {
            $tracking = trim($_POST['transport_id'] ?? '');
            if (strlen($tracking) > 255) {
                throw new RuntimeException('เลขพัสดุยาวเกินไป');
            }
            sql("UPDATE orders SET transport_id=? WHERE order_id=? AND order_status NOT IN ('Cancelled','Completed')", [$tracking, integer($_POST['order_id'] ?? '', 1)]);
            audit($actor['user_id'], 'order_tracking', $_POST['order_id']);
        } elseif ($section === 'products' && $action === 'archive') {
            $pid = integer($_POST['product_id'] ?? '', 1);
            $conn->begin_transaction();
            one('SELECT product_id FROM products WHERE product_id=? FOR UPDATE', [$pid]);
            sql('UPDATE products SET is_active=0 WHERE product_id=?', [$pid]);
            audit($actor['user_id'], 'product_archive', (string) $pid);
            $conn->commit();
        } elseif ($section === 'products' && $action === 'product') {
            $pid = (int) ($_POST['product_id'] ?? 0);
            $name = trim($_POST['product_name'] ?? '');
            $type = trim($_POST['product_type'] ?? '');
            $detail = trim($_POST['product_detail'] ?? '');
            if ($name === '' || $type === '' || strlen($name) > 255 || strlen($type) > 255) {
                throw new RuntimeException('กรุณาระบุชื่อและประเภทสินค้าให้ถูกต้อง');
            }
            $price = decimal(cents($_POST['product_price'] ?? ''));
            $cost = ($_POST['cost_price'] ?? '') === '' ? null : decimal(cents($_POST['cost_price']));
            $stock = integer($_POST['stock'] ?? '');
            $threshold = integer($_POST['low_stock_threshold'] ?? '');
            $image = image_upload('product_img', 'product_pic');
            $conn->begin_transaction();
            if ($pid) {
                $product = one('SELECT * FROM products WHERE product_id=? FOR UPDATE', [$pid]);
                if (!$product) {
                    throw new RuntimeException('ไม่พบสินค้า');
                }
                $delta = $stock - (int) $product['stock'];
                if ($delta !== 0) {
                    stock_change($product, $delta, $actor['user_id'], 'Admin ปรับยอด Stock');
                }
                sql('UPDATE products SET product_name=?,product_type=?,product_detail=?,product_price=?,cost_price=?,low_stock_threshold=?,product_img=?,is_active=1 WHERE product_id=?', [$name, $type, $detail, $price, $cost, $threshold, $image ?? $product['product_img'], $pid]);
            } else {
                sql('INSERT INTO products(product_name,product_type,product_detail,product_price,cost_price,stock,low_stock_threshold,product_img) VALUES (?,?,?,?,?,0,?,?)', [$name, $type, $detail, $price, $cost, $threshold, $image ?? '']);
                $pid = $conn->insert_id;
                $product = ['product_id' => $pid, 'product_name' => $name, 'stock' => 0];
                stock_change($product, $stock, $actor['user_id'], 'เพิ่มสินค้าใหม่');
            }
            audit($actor['user_id'], 'product_save', (string) $pid);
            $conn->commit();
            redirect($base . 'products.php', 'บันทึกสินค้าแล้ว');
        } elseif ($section === 'users' && $action === 'user') {
            $uid = (int) ($_POST['user_id'] ?? 0);
            $role = $_POST['role'] ?? '';
            $active = isset($_POST['is_active']) ? 1 : 0;
            $email = trim($_POST['email'] ?? '');
            $first = trim($_POST['first_name'] ?? '');
            $last = trim($_POST['last_name'] ?? '');
            $phone = trim($_POST['phone_number'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $password = $_POST['password'] ?? '';
            if (!in_array($role, ['admin', 'employee', 'user'], true) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255 || $first === '' || strlen($first) > 255 || strlen($last) > 255 || ($phone !== '' && !preg_match('/^\d{10}$/', $phone))) {
                throw new RuntimeException('กรุณาตรวจสอบข้อมูลผู้ใช้งาน');
            }
            if ($uid === $actor['user_id'] && ($role !== 'admin' || !$active)) {
                throw new RuntimeException('ไม่สามารถระงับหรือลดสิทธิ์บัญชีที่กำลังใช้งาน');
            }
            $conn->begin_transaction();
            // Serialize changes to admin membership to preserve at least one active admin.
            $admins = rows("SELECT user_id FROM userdata WHERE role='admin' AND is_active=1 FOR UPDATE");
            $existing = $uid ? one('SELECT * FROM userdata WHERE user_id=? FOR UPDATE', [$uid]) : null;
            if ($uid && !$existing) {
                throw new RuntimeException('ไม่พบบัญชี');
            }
            if ($existing && $existing['role'] === 'admin' && $existing['is_active'] && count($admins) <= 1 && ($role !== 'admin' || !$active)) {
                throw new RuntimeException('ระบบต้องมี Admin ที่ใช้งานได้อย่างน้อยหนึ่งคน');
            }
            if (one('SELECT user_id FROM userdata WHERE email=? AND user_id<>?', [$email, $uid])) {
                throw new RuntimeException('อีเมลนี้ถูกใช้แล้ว');
            }
            if (!$uid && $password === '') {
                throw new RuntimeException('กรุณาระบุรหัสผ่าน');
            }
            if (strlen($password) > 72) {
                throw new RuntimeException('รหัสผ่านยาวเกินไป');
            }
            if ($uid) {
                sql('UPDATE userdata SET email=?,first_name=?,last_name=?,phone_number=?,address=?,role=?,is_active=? WHERE user_id=?', [$email, $first, $last, $phone, $address, $role, $active, $uid]);
                if ($password !== '') {
                    sql('UPDATE userdata SET password=? WHERE user_id=?', [password_hash($password, PASSWORD_DEFAULT), $uid]);
                }
            } else {
                sql("INSERT INTO userdata(email,password,first_name,last_name,title_name,address,phone_number,role,is_active) VALUES (?,?,?,?,'',?,?,?,?)", [$email, password_hash($password, PASSWORD_DEFAULT), $first, $last, $address, $phone, $role, $active]);
                $uid = $conn->insert_id;
            }
            audit($actor['user_id'], 'user_save', $uid . ':' . $role . ':' . $active);
            $conn->commit();
            redirect($base . 'users.php', 'บันทึกผู้ใช้งานแล้ว');
        } elseif ($section === 'feedback' && $action === 'reply') {
            $status = $_POST['status'] ?? '';
            $reply = trim($_POST['admin_response'] ?? '');
            if (!in_array($status, ['new', 'in_progress', 'resolved'], true) || ($status === 'resolved' && $reply === '')) {
                throw new RuntimeException('กรุณาระบุสถานะและคำตอบ');
            }
            sql('UPDATE reports SET admin_response=?,status=?,replied_by=?,replied_at=NOW() WHERE report_id=?', [$reply, $status, $actor['user_id'], integer($_POST['report_id'] ?? '', 1)]);
            audit($actor['user_id'], 'feedback_reply', $_POST['report_id']);
        } elseif ($section === 'settings' && $action === 'settings') {
            $qr = image_upload('payment_qr', 'uploads');
            sql("UPDATE shop_settings SET setting_value=? WHERE setting_key='bank_details'", [trim($_POST['bank_details'] ?? '')]);
            if ($qr) {
                sql("UPDATE shop_settings SET setting_value=? WHERE setting_key='payment_qr'", [$qr]);
            }
            audit($actor['user_id'], 'settings', 'payment settings');
        } else {
            throw new RuntimeException('ไม่พบการดำเนินการ');
        }
        redirect($base . $section . '.php' . ($id ? '?id=' . $id : ''), 'บันทึกข้อมูลแล้ว');
    } catch (Throwable $exception) {
        $conn->rollback();
        $error = $exception instanceof mysqli_sql_exception ? 'บันทึกข้อมูลไม่สำเร็จ กรุณาลองใหม่' : $exception->getMessage();
        error_log($exception->getMessage());
    }
}
$titles = ['dashboard' => 'แดชบอร์ด', 'stock' => 'Stock / รับสินค้า', 'stock_history' => 'ประวัติ Stock', 'pos' => 'ขายหน้าร้าน', 'sales' => 'รายการขายหน้าร้าน', 'orders' => 'คำสั่งซื้อออนไลน์', 'products' => 'จัดการสินค้า', 'qr' => 'QR สินค้า', 'users' => 'ผู้ใช้งาน / พนักงาน', 'feedback' => 'ความคิดเห็นลูกค้า', 'reports' => 'รายงานยอดขายและกำไร', 'settings' => 'ตั้งค่าการชำระเงิน'];
page_start($titles[$section] ?? '', true);
error_notice($error);
if ($section === 'products') {
    $product = $edit ? one('SELECT * FROM products WHERE product_id=?', [$edit]) : null;
    if (isset($_GET['new']) || $edit) {
        $p = $product ?? [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error) {
            $p = array_merge($p, $_POST);
        }
        ?>
        <section class="card">
            <h2><?= $edit ? 'แก้ไขสินค้า' : 'เพิ่มสินค้า' ?></h2>
            <form method="post" enctype="multipart/form-data"><?php csrf_field(); ?><input type="hidden" name="action"
                    value="product"><input type="hidden" name="product_id" value="<?= $edit ?>">
                <div class="form-grid"><?php
                field('ชื่อสินค้า', 'product_name', $p['product_name'] ?? '');
                field('ประเภทสินค้า', 'product_type', $p['product_type'] ?? '', 'text', true, 'list="types"');
                field('ราคาขาย (บาท)', 'product_price', $p['product_price'] ?? '', 'number', true, 'min="0" max="9999999.99" step="0.01"');
                field('ราคาทุน (ว่าง = ยังไม่ทราบ)', 'cost_price', $p['cost_price'] ?? '', 'number', false, 'min="0" max="9999999.99" step="0.01"');
                field('Stock', 'stock', $p['stock'] ?? 0, 'number', true, 'min="0"');
                field('แจ้งเตือนเมื่อ Stock ไม่เกิน', 'low_stock_threshold', $p['low_stock_threshold'] ?? 5, 'number', true, 'min="0"');
                ?><label>รูปสินค้า<input type="file" name="product_img"
                            accept="image/png,image/jpeg,image/webp"></label><label>รายละเอียด<textarea
                            name="product_detail"><?= e($p['product_detail'] ?? '') ?></textarea></label></div><datalist
                    id="types"><?php foreach (rows('SELECT DISTINCT product_type FROM products') as $type): ?>
                        <option value="<?= e($type['product_type']) ?>"><?php endforeach; ?>
                </datalist>
                <div class="actions"><button>บันทึกสินค้า</button><a href="products.php" class="button secondary">กลับ</a></div>
            </form>
        </section><?php
    }
    ?>
    <div class="toolbar"><a class="button" href="?new=1">+ เพิ่มสินค้า</a><a class="button secondary" href="qr.php">ดู /
            พิมพ์ QR ทั้งหมด</a></div><?php
            table_start(['รูป', 'สินค้า', 'ประเภท', 'ราคาขาย', 'ราคาทุน', 'Stock', 'สถานะ', 'จัดการ']);
            foreach (rows('SELECT * FROM products ORDER BY is_active DESC,product_id DESC') as $p) { ?>
        <tr>
            <td><?php if ($p['product_img']): ?><img class="thumb" src="/<?= e($p['product_img']) ?>" alt=""><?php endif; ?></td>
            <td><?= e($p['product_name']) ?></td>
            <td><?= e($p['product_type']) ?></td>
            <td><?= money($p['product_price']) ?></td>
            <td><?= $p['cost_price'] === null ? 'ไม่ทราบ' : money($p['cost_price']) ?></td>
            <td><?= $p['stock'] ?>         <?= $p['stock'] <= $p['low_stock_threshold'] ? '<span class="badge low">ใกล้หมด</span>' : '' ?></td>
            <td><?= $p['is_active'] ? 'ขายอยู่' : 'ปิดขาย' ?></td>
            <td><a href="?edit=<?= $p['product_id'] ?>">แก้ไข</a> · <a
                    href="qr.php?id=<?= $p['product_id'] ?>">QR</a><?php if ($p['is_active']): ?>
                    <form class="inline" method="post" data-confirm="ปิดขายสินค้านี้? ประวัติการขายจะยังอยู่">
                        <?php csrf_field(); ?><input type="hidden" name="action" value="archive"><input type="hidden"
                            name="product_id" value="<?= $p['product_id'] ?>"><button class="danger">ลบ / ปิดขาย</button></form>
                <?php endif; ?>
            </td>
        </tr><?php }
            table_end();
} elseif ($section === 'stock' || $section === 'stock_history') {
    if ($section === 'stock') {
        $search = trim($_GET['q'] ?? '');
        $products = rows('SELECT product_id,product_name,product_price,product_type,stock,low_stock_threshold FROM products WHERE is_active=1 AND product_name LIKE ? ORDER BY stock,product_id', ['%' . $search . '%']);
        ?>
        <form class="toolbar" method="get"><?php field('ค้นหาสินค้า', 'q', $search, 'search', false); ?><button>ค้นหา</button>
        </form>
        <section class="card">
            <h2>บันทึกการรับสินค้า</h2>
            <form method="post"><?php csrf_field(); ?><input type="hidden" name="action" value="receive">
                <div class="form-grid"><label>สินค้า<select name="product_id" required><?php foreach ($products as $p): ?>
                                <option value="<?= $p['product_id'] ?>"><?= e($p['product_name']) ?> (<?= $p['stock'] ?>)</option>
                            <?php endforeach; ?>
                        </select></label><?php field('จำนวนที่รับเพิ่ม', 'quantity', 1, 'number', true, 'min="1"');
                        field('เหตุผล / เลขเอกสาร', 'reason'); ?>
                </div>
                <div class="actions"><button>รับสินค้า / เพิ่ม Stock</button></div>
            </form>
        </section><?php
        table_start(['รหัส', 'สินค้า', 'ราคาขาย', 'คงเหลือ', 'แจ้งเตือน']);
        foreach ($products as $p): ?>
            <tr>
                <td><?= $p['product_id'] ?></td>
                <td><?= e($p['product_name']) ?></td>
                <td><?= money($p['product_price']) ?></td>
                <td><?= $p['stock'] ?></td>
                <td><?= $p['stock'] <= $p['low_stock_threshold'] ? '<span class="badge low">สินค้าใกล้หมด</span>' : 'พร้อมขาย' ?></td>
            </tr>
        <?php endforeach;
        table_end();
    } else {
        try {
            [$start, $end, $from, $until] = date_filter();
        } catch (RuntimeException $ex) {
            error_notice($ex->getMessage());
            page_end();
            return;
        }
        filter_form($start, $end);
        table_start(['วันที่', 'สินค้า', 'เพิ่ม / ลด', 'ยอดหลังปรับ', 'ผู้ทำรายการ', 'เหตุผล', 'อ้างอิง']);
        foreach (rows('SELECT m.*,p.product_name,u.first_name,u.email FROM stock_movements m LEFT JOIN products p ON p.product_id=m.product_id LEFT JOIN userdata u ON u.user_id=m.actor_id WHERE m.created_at>=? AND m.created_at<? ORDER BY m.id DESC', [$from, $until]) as $m): ?>
            <tr>
                <td><?= e($m['created_at']) ?></td>
                <td><?= e($m['product_name']) ?></td>
                <td><?= ($m['delta'] > 0 ? '+' : '') . $m['delta'] ?></td>
                <td><?= $m['balance'] ?></td>
                <td><?= e($m['email'] ?? 'ยอดยกมา') ?></td>
                <td><?= e($m['reason']) ?></td>
                <td><?= e($m['reference_code']) ?></td>
            </tr><?php endforeach;
        table_end();
    }
} elseif ($section === 'pos') {
    $sale = $edit && $admin ? one("SELECT id,payment_method,note FROM pos_sales WHERE id=? AND status='Completed'", [$edit]) : null;
    $initial = $sale ? array_column(rows('SELECT product_id,quantity FROM pos_items WHERE sale_id=?', [$edit]), 'quantity', 'product_id') : [];
    if ($error) {
        $initial = $_POST['quantity'] ?? [];
    }
    $catalog = rows('SELECT product_id,product_name,product_price,stock FROM products WHERE is_active=1 ORDER BY product_name');
    ?>
    <section class="card">
        <h2><?= $sale ? 'แก้ไขรายการขาย #' . $edit : 'เปิดรายการขายใหม่' ?></h2>
        <div class="toolbar"><label>ค้นหาชื่อ / รหัส / สแกนเนอร์<input id="pos-search"
                    placeholder="ชื่อสินค้า, รหัส หรือ URL ของ QR"></label><button type="button" id="start-scan">📷 สแกน
                QR</button></div>
        <div id="scanner-area" hidden><video id="scanner-video" class="scanner" playsinline muted></video><button
                type="button" id="stop-scan">หยุดกล้อง</button>
            <p id="scanner-message" role="status"></p>
        </div>
        <form method="post" id="pos-form"><?php csrf_field(); ?><input type="hidden" name="action" value="sale"><input
                type="hidden" name="edit_id"
                value="<?= $sale ? $edit : 0 ?>"><?php table_start(['สินค้า', 'ราคา', 'Stock', 'จำนวน', 'รวม']);
                foreach ($catalog as $p): ?>
                <tr data-product="<?= $p['product_id'] ?>" data-name="<?= e($p['product_name']) ?>"
                    data-price="<?= e($p['product_price']) ?>">
                    <td><?= e($p['product_name']) ?> <span class="muted">#<?= $p['product_id'] ?></span></td>
                    <td><?= money($p['product_price']) ?></td>
                    <td><?= $p['stock'] ?></td>
                    <td><input aria-label="จำนวน <?= e($p['product_name']) ?>" type="number" min="0" step="1"
                            max="<?= $p['stock'] + ($sale ? ($initial[$p['product_id']] ?? 0) : 0) ?>"
                            name="quantity[<?= $p['product_id'] ?>]" value="<?= e($initial[$p['product_id']] ?? 0) ?>"></td>
                    <td class="line-total">0.00</td>
                </tr><?php endforeach;
                table_end(); ?>
            <h2>ยอดรวม <span id="pos-total">0.00</span> บาท</h2>
            <div class="form-grid"><label>ชำระเงิน<select
                        name="payment_method"><?php foreach (PAYMENT_METHODS as $key => $label): ?>
                            <option value="<?= $key ?>" <?= ($sale['payment_method'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?>
                            </option><?php endforeach; ?>
                    </select></label><?php field('หมายเหตุ', 'note', $sale['note'] ?? '', 'text', false); ?></div>
            <?php $settings = settings();
            if ($settings['bank_details']): ?>
                <p><?= nl2br(e($settings['bank_details'])) ?></p><?php endif;
            if ($settings['payment_qr']): ?><img
                    class="detail-image" src="/<?= e($settings['payment_qr']) ?>" alt="QR สำหรับชำระเงิน"><?php endif; ?>
            <p class="muted">ยืนยันเมื่อได้รับชำระเงินแล้ว ระบบจะดึงราคาจากฐานข้อมูลและตัด Stock</p>
            <div class="actions"><button type="submit">ยืนยันการขาย</button></div>
        </form>
    </section>
    <script src="/assets/vendor/jsQR.js"></script>
    <script src="/assets/scanner.js"></script>
    <script src="/assets/pos.js"></script><?php
} elseif ($section === 'qr') {
    $products = $id ? rows('SELECT product_id,product_name FROM products WHERE product_id=?', [$id]) : rows('SELECT product_id,product_name FROM products WHERE is_active=1 ORDER BY product_id');
    ?>
    <div class="toolbar"><button data-print>พิมพ์ QR สินค้า</button></div>
    <div class="qr-grid"><?php foreach ($products as $p): ?>
            <article class="card qr-label">
                <h3><?= e($p['product_name']) ?></h3>
                <div data-qr="/product_content.php?id=<?= $p['product_id'] ?>"></div>
                <p>#<?= $p['product_id'] ?></p><a class="no-print"
                    href="/product_content.php?id=<?= $p['product_id'] ?>">ดูรายละเอียดสินค้า</a>
            </article><?php endforeach; ?>
    </div>
    <script src="/assets/vendor/qrcode.js"></script>
    <script src="/assets/qr.js"></script><?php
} elseif ($section === 'users') {
    $u = $edit ? one('SELECT user_id,email,first_name,last_name,phone_number,address,role,is_active FROM userdata WHERE user_id=?', [$edit]) : null;
    if (isset($_GET['new']) || $edit) {
        if ($error) {
            $u = array_merge($u ?? [], $_POST);
            $u['is_active'] = isset($_POST['is_active']);
        }
        ?>
        <section class="card">
            <h2><?= $edit ? 'แก้ไขบัญชี' : 'เพิ่มบัญชี' ?></h2>
            <form method="post"><?php csrf_field(); ?><input type="hidden" name="action" value="user"><input type="hidden"
                    name="user_id" value="<?= $edit ?>">
                <div class="form-grid">
                    <?php field('ชื่อ', 'first_name', $u['first_name'] ?? '');
                    field('นามสกุล', 'last_name', $u['last_name'] ?? '', 'text', false);
                    field('อีเมล', 'email', $u['email'] ?? '', 'email');
                    field($edit ? 'รหัสผ่านใหม่ (ว่าง = ไม่เปลี่ยน)' : 'รหัสผ่าน', 'password', '', 'password', !$edit, 'autocomplete="new-password"');
                    field('เบอร์โทรศัพท์', 'phone_number', $u['phone_number'] ?? '', 'tel', false); ?><label>Role<select
                            name="role"><?php foreach (['user', 'employee', 'admin'] as $role): ?>
                                <option <?= ($u['role'] ?? 'user') === $role ? 'selected' : '' ?> value="<?= $role ?>"><?= ucfirst($role) ?>
                                </option><?php endforeach; ?>
                        </select></label><label>ที่อยู่<textarea
                            name="address"><?= e($u['address'] ?? '') ?></textarea></label><label class="inline"><input
                            type="checkbox" name="is_active" <?= ($u['is_active'] ?? 1) ? 'checked' : '' ?>> เปิดใช้งานบัญชี
                        (เอาออกเพื่อระงับ)</label></div>
                <div class="actions"><button>บันทึก</button><a class="button secondary" href="users.php">กลับ</a></div>
            </form>
        </section><?php
    }
    ?><a class="button" href="?new=1">+ เพิ่มลูกค้า / พนักงาน / Admin</a>
    <p></p>
    <?php table_start(['ชื่อ', 'อีเมล', 'Role', 'สถานะ', 'จัดการ']);
    foreach (rows('SELECT user_id,first_name,last_name,email,role,is_active FROM userdata ORDER BY user_id DESC') as $u): ?>
        <tr>
            <td><?= e($u['first_name'] . ' ' . $u['last_name']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= e($u['role']) ?></td>
            <td><?= $u['is_active'] ? 'ใช้งาน' : 'ระงับ' ?></td>
            <td><a href="?edit=<?= $u['user_id'] ?>">แก้ไข / ระงับ / กำหนด Role</a></td>
        </tr>
    <?php endforeach;
    table_end();
} elseif ($section === 'feedback') {
    foreach (rows('SELECT r.*,u.email FROM reports r LEFT JOIN userdata u ON u.user_id=r.user_id ORDER BY r.report_id DESC') as $r): ?>
        <article class="card">
            <h2><?= e($r['issue_title']) ?></h2>
            <p class="muted"><?= e($r['email']) ?> · <?= e($r['created_at']) ?></p>
            <p class="feedback-body"><?= e($r['issue_description']) ?></p>
            <form method="post"><?php csrf_field(); ?><input type="hidden" name="action" value="reply"><input type="hidden"
                    name="report_id" value="<?= $r['report_id'] ?>"><label>ตอบกลับ<textarea
                        name="admin_response"><?= e($r['admin_response']) ?></textarea></label><label>สถานะ<select
                        name="status"><?php foreach (['new' => 'ใหม่', 'in_progress' => 'กำลังดำเนินการ', 'resolved' => 'ตอบกลับแล้ว'] as $key => $label): ?>
                            <option value="<?= $key ?>" <?= $r['status'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select></label>
                <div class="actions"><button>บันทึกคำตอบและสถานะ</button></div>
            </form>
        </article><?php endforeach;
} elseif ($section === 'settings') {
    $s = settings(); ?>
    <section class="card">
        <form method="post" enctype="multipart/form-data"><?php csrf_field(); ?><input type="hidden" name="action"
                value="settings"><label>ข้อมูลบัญชีธนาคาร / คำแนะนำชำระเงิน<textarea
                    name="bank_details"><?= e($s['bank_details']) ?></textarea></label><label>ภาพ QR รับชำระเงิน<input
                    type="file" name="payment_qr"
                    accept="image/png,image/jpeg,image/webp"></label><?php if ($s['payment_qr']): ?><img class="detail-image"
                    src="/<?= e($s['payment_qr']) ?>" alt="QR ชำระเงิน"><?php endif; ?>
            <div class="actions"><button>บันทึกการตั้งค่า</button></div>
        </form>
    </section>
    <?php
} elseif (in_array($section, ['dashboard', 'reports', 'sales', 'orders'], true)) {
    require __DIR__ . '/reports-view.php';
}
page_end();
