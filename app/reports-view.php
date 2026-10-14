<?php
if ($section === 'dashboard' || $section === 'reports') {
    try {
        [$start, $end, $from, $until] = date_filter();
    } catch (RuntimeException $ex) {
        error_notice($ex->getMessage());
        return;
    }
    $inventory = one('SELECT COUNT(*) products,SUM(stock<=low_stock_threshold) low FROM products WHERE is_active=1');
    $today = one("SELECT COALESCE(SUM(total),0) total FROM pos_sales WHERE status='Completed' AND created_at>=CURDATE() AND created_at<CURDATE()+INTERVAL 1 DAY");
    $pending = one("SELECT COUNT(*) n FROM orders WHERE order_status IN ('Order Placed','Order Processing')");
    if (!$admin) {
        cards(['จำนวนสินค้า' => $inventory['products'], 'สินค้าใกล้หมด' => $inventory['low'] ?? 0, 'ยอดขายหน้าร้านวันนี้' => money($today['total']) . ' บาท', 'ออนไลน์รอดำเนินการ' => $pending['n']]);
        ?>
        <section class="card">
            <h2>งานประจำวัน</h2>
            <div class="actions"><a class="button" href="pos.php">เปิดรายการขาย</a><a class="button secondary"
                    href="stock.php">รับสินค้า / ตรวจ Stock</a><a class="button secondary" href="orders.php">เตรียมออเดอร์</a>
            </div>
        </section><?php
    } else {
        // Only completed sales are recognized as revenue. Costs are historical snapshots.
        $revenue = "SELECT order_date sold_at,total_price total,'online' channel FROM orders WHERE order_status='Completed' UNION ALL SELECT created_at,total,'pos' FROM pos_sales WHERE status='Completed'";
        $summary = one("SELECT COALESCE(SUM(total),0) total,COUNT(*) n,COALESCE(SUM(CASE WHEN channel='online' THEN total ELSE 0 END),0) online,COALESCE(SUM(CASE WHEN channel='pos' THEN total ELSE 0 END),0) pos FROM ($revenue) s WHERE sold_at>=? AND sold_at<?", [$from, $until]);
        $all = one("SELECT COALESCE(SUM(total),0) total FROM ($revenue) s");
        $day = one("SELECT COALESCE(SUM(total),0) total FROM ($revenue) s WHERE sold_at>=CURDATE() AND sold_at<CURDATE()+INTERVAL 1 DAY");
        $month = one("SELECT COALESCE(SUM(total),0) total FROM ($revenue) s WHERE sold_at>=DATE_FORMAT(CURDATE(),'%Y-%m-01') AND sold_at<LAST_DAY(CURDATE())+INTERVAL 1 DAY");
        $lines = "SELECT o.order_date sold_at,i.product_id,i.product_name COLLATE utf8mb4_unicode_ci product_name,i.quantity,i.price,i.unit_cost FROM order_items i JOIN orders o ON o.order_id=i.order_id WHERE o.order_status='Completed' UNION ALL SELECT s.created_at,i.product_id,i.product_name COLLATE utf8mb4_unicode_ci,i.quantity,i.price,i.unit_cost FROM pos_items i JOIN pos_sales s ON s.id=i.sale_id WHERE s.status='Completed'";
        $cost = one("SELECT COALESCE(SUM(quantity*unit_cost),0) cost,COALESCE(SUM(CASE WHEN unit_cost IS NOT NULL THEN quantity*(price-unit_cost) ELSE 0 END),0) profit,COALESCE(SUM(unit_cost IS NULL),0) unknown FROM ($lines) l WHERE sold_at>=? AND sold_at<?", [$from, $until]);
        $allCost = one("SELECT COALESCE(SUM(quantity*unit_cost),0) cost,COALESCE(SUM(CASE WHEN unit_cost IS NOT NULL THEN quantity*(price-unit_cost) ELSE 0 END),0) profit,COALESCE(SUM(unit_cost IS NULL),0) unknown FROM ($lines) l");
        $ordersCount = one('SELECT COUNT(*) n FROM orders');
        cards(['ยอดขายรวม' => money($all['total']) . ' บาท', 'ยอดขายวันนี้' => money($day['total']) . ' บาท', 'ยอดขายเดือนนี้' => money($month['total']) . ' บาท', 'คำสั่งซื้อออนไลน์ทั้งหมด' => $ordersCount['n'], 'สินค้าในระบบ' => $inventory['products'], 'สินค้าใกล้หมด' => $inventory['low'] ?? 0, 'ต้นทุนรวมที่ทราบ' => money($allCost['cost']) . ' บาท', 'กำไรรวมที่ทราบ' => money($allCost['profit']) . ' บาท']);
        ?>
        <p class="muted">ยอดขายนับเฉพาะรายการสำเร็จ ต้นทุนและกำไรใช้ราคาทุน ณ เวลาขาย มีรายการสินค้าเก่าที่ไม่ทราบต้นทุน
            <?= $allCost['unknown'] ?> รายการ และยังไม่รวมในกำไรที่ทราบ</p>
        <section class="card">
            <h2>รายงานตามช่วงเวลา</h2>
            <?php filter_form($start, $end);
            cards(['ยอดขายช่วงที่เลือก' => money($summary['total']), 'ยอดออนไลน์' => money($summary['online']), 'ยอดหน้าร้าน' => money($summary['pos']), 'รายการสำเร็จ' => $summary['n'], 'ต้นทุนที่ทราบ' => money($cost['cost']), 'กำไรที่ทราบ' => money($cost['profit'])]); ?>
            <p class="muted">รายการที่ไม่ทราบต้นทุนในช่วงนี้: <?= $cost['unknown'] ?></p><?php
            $daily = rows("SELECT DATE(sold_at) day,SUM(total) total FROM ($revenue) s WHERE sold_at>=? AND sold_at<? GROUP BY DATE(sold_at) ORDER BY day", [$from, $until]);
            $max = max(array_column($daily, 'total') ?: [1]);
            ?><?php if ($daily): ?>
                <div class="chart" aria-label="ยอดขายรายวัน"><?php foreach ($daily as $d): ?>
                        <div class="chart-item">
                            <div class="bar" style="height:<?= max(2, (float) $d['total'] / max(1, $max) * 130) ?>px"
                                title="<?= e($d['day']) ?>: <?= money($d['total']) ?> บาท"></div><?= e(substr($d['day'], 5)) ?>
                        </div><?php endforeach; ?>
                </div><?php else: ?>
                <p class="muted">ยังไม่มีรายการขายสำเร็จในช่วงวันที่เลือก</p><?php endif; ?>
        </section><?php
        table_start(['วันที่', 'ยอดขาย']);
        foreach ($daily as $d): ?>
            <tr>
                <td><?= e($d['day']) ?></td>
                <td><?= money($d['total']) ?></td>
            </tr><?php endforeach;
        table_end();
        ?>
        <h2>สินค้าขายดี</h2>
        <?php table_start(['สินค้า', 'ขายแล้ว (ชิ้น)', 'ยอดขาย', 'ต้นทุนที่ทราบ', 'กำไรที่ทราบ', 'รายการไม่ทราบทุน']);
        foreach (rows("SELECT product_id,MAX(product_name) name,SUM(quantity) qty,SUM(quantity*price) revenue,COALESCE(SUM(quantity*unit_cost),0) cost,COALESCE(SUM(CASE WHEN unit_cost IS NOT NULL THEN quantity*(price-unit_cost) ELSE 0 END),0) profit,SUM(unit_cost IS NULL) unknown FROM ($lines) l WHERE sold_at>=? AND sold_at<? GROUP BY product_id ORDER BY qty DESC LIMIT 20", [$from, $until]) as $r): ?>
            <tr>
                <td><?= e($r['name']) ?></td>
                <td><?= $r['qty'] ?></td>
                <td><?= money($r['revenue']) ?></td>
                <td><?= money($r['cost']) ?></td>
                <td><?= money($r['profit']) ?></td>
                <td><?= $r['unknown'] ?></td>
            </tr><?php endforeach;
        table_end();
        $stockRows = $section === 'dashboard'
            ? rows('SELECT product_name,stock,cost_price,low_stock_threshold FROM products WHERE is_active=1 ORDER BY stock LIMIT 8')
            : rows('SELECT p.product_name,p.cost_price,p.low_stock_threshold,(SELECT m.balance FROM stock_movements m WHERE m.product_id=p.product_id AND m.created_at<? ORDER BY m.created_at DESC,m.id DESC LIMIT 1) stock FROM products p ORDER BY stock,p.product_id', [$until]);
        ?>
        <h2><?= $section === 'dashboard' ? 'สินค้า Stock ต่ำสุด' : 'Stock ณ วันที่ ' . e($end) ?></h2>
        <?php if ($section === 'reports'): ?>
            <p class="muted">ยอดคงเหลือคำนวณจากประวัติ Stock ถึงวันสิ้นสุดที่เลือก ราคาทุนและมูลค่าใช้ราคาทุนปัจจุบัน
                วันที่ก่อนเริ่มบันทึกประวัติจะแสดงว่าไม่มีประวัติ</p><?php endif;
        table_start(['สินค้า', 'คงเหลือ', 'ราคาทุนปัจจุบัน', 'มูลค่าที่ทุนปัจจุบัน', 'สถานะ']);
        foreach ($stockRows as $p): ?>
            <tr>
                <td><?= e($p['product_name']) ?></td>
                <td><?= $p['stock'] === null ? 'ไม่มีประวัติ' : $p['stock'] ?></td>
                <td><?= $p['cost_price'] === null ? 'ไม่ทราบ' : money($p['cost_price']) ?></td>
                <td><?= $p['cost_price'] === null || $p['stock'] === null ? 'ไม่ทราบ' : money($p['stock'] * $p['cost_price']) ?></td>
                <td><?= $p['stock'] === null ? 'ไม่มีประวัติ' : ($p['stock'] <= $p['low_stock_threshold'] ? 'สินค้าใกล้หมด' : 'พร้อมขาย') ?></td>
            </tr><?php endforeach;
        table_end();
        ?>
        <div class="actions"><a class="button" href="sales.php?start=<?= e($start) ?>&end=<?= e($end) ?>">รายงานหน้าร้าน</a><a
                class="button" href="orders.php?start=<?= e($start) ?>&end=<?= e($end) ?>">รายงานออนไลน์</a><button
                data-print>พิมพ์รายงาน / บันทึก PDF</button></div>
        <?php
    }
    if (!$admin) {
        table_start(['สินค้าใกล้หมด', 'Stock']);
        foreach (rows('SELECT product_name,stock FROM products WHERE is_active=1 AND stock<=low_stock_threshold ORDER BY stock') as $p): ?>
            <tr>
                <td><?= e($p['product_name']) ?></td>
                <td><?= $p['stock'] ?></td>
            </tr><?php endforeach;
        table_end();
    }
} elseif ($section === 'sales') {
    if ($id) {
        $sale = one('SELECT s.*,u.email FROM pos_sales s LEFT JOIN userdata u ON u.user_id=s.actor_id WHERE s.id=?' . ($admin ? '' : ' AND s.actor_id=?'), $admin ? [$id] : [$id, $actor['user_id']]);
        if (!$sale) {
            error_notice('ไม่พบรายการขาย');
            return;
        }
        $columns = $admin ? 'product_name,quantity,price,unit_cost' : 'product_name,quantity,price';
        $items = rows("SELECT $columns FROM pos_items WHERE sale_id=?", [$id]);
        ?>
        <section class="card">
            <h2><?= e($sale['sale_code']) ?></h2>
            <p><?= e($sale['created_at']) ?> · ผู้ขาย <?= e($sale['email']) ?> ·
                <?= e(PAYMENT_METHODS[$sale['payment_method']] ?? $sale['payment_method']) ?></p>
            <p><?= e($sale['note']) ?></p>
            <?php table_start($admin ? ['สินค้า', 'จำนวน', 'ราคา', 'รวม', 'ต้นทุน', 'กำไร'] : ['สินค้า', 'จำนวน', 'ราคา', 'รวม']);
            foreach ($items as $item): ?>
                <tr>
                    <td><?= e($item['product_name']) ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td><?= money($item['price']) ?></td>
                    <td><?= money($item['price'] * $item['quantity']) ?></td><?php if ($admin): ?>
                        <td><?= $item['unit_cost'] === null ? 'ไม่ทราบ' : money($item['unit_cost'] * $item['quantity']) ?></td>
                        <td><?= $item['unit_cost'] === null ? 'ไม่ทราบ' : money(($item['price'] - $item['unit_cost']) * $item['quantity']) ?></td>
                    <?php endif; ?>
                </tr><?php endforeach;
            table_end(); ?>
            <h2>ยอดรวม <?= money($sale['total']) ?> บาท</h2>
            <div class="actions"><button data-print>พิมพ์รายการ</button><?php if ($admin): ?><a class="button"
                        href="pos.php?edit=<?= $id ?>">แก้ไขการขาย</a><?php endif; ?></div>
        </section><?php
    }
    try {
        [$start, $end, $from, $until] = date_filter();
    } catch (RuntimeException $ex) {
        error_notice($ex->getMessage());
        return;
    }
    filter_form($start, $end);
    $staffFilter = $admin ? '' : ' AND s.actor_id=?';
    $params = [$from, $until];
    if (!$admin) {
        $params[] = $actor['user_id'];
    }
    $list = rows("SELECT s.id,s.sale_code,s.created_at,s.total,s.payment_method,u.email" . ($admin ? ',(SELECT SUM(quantity*unit_cost) FROM pos_items WHERE sale_id=s.id) cost,(SELECT SUM(unit_cost IS NULL) FROM pos_items WHERE sale_id=s.id) unknown' : '') . " FROM pos_sales s LEFT JOIN userdata u ON u.user_id=s.actor_id WHERE s.created_at>=? AND s.created_at<? $staffFilter ORDER BY s.id DESC", $params);
    if (!$admin): ?>
        <p class="muted">แสดงประวัติการขายที่คุณทำรายการ</p><?php endif;
    table_start($admin ? ['รายการ', 'วันที่', 'ผู้ขาย', 'วิธีชำระ', 'ยอดขาย', 'ต้นทุน', 'กำไร', 'รายละเอียด'] : ['รายการ', 'วันที่', 'ผู้ขาย', 'วิธีชำระ', 'ยอดขาย', 'รายละเอียด']);
    foreach ($list as $s): ?>
        <tr>
            <td><?= e($s['sale_code']) ?></td>
            <td><?= e($s['created_at']) ?></td>
            <td><?= e($s['email']) ?></td>
            <td><?= e(PAYMENT_METHODS[$s['payment_method']] ?? $s['payment_method']) ?></td>
            <td><?= money($s['total']) ?></td><?php if ($admin): ?>
                <td><?= $s['unknown'] ? 'ไม่ครบ' : money($s['cost']) ?></td>
                <td><?= $s['unknown'] ? 'ไม่ทราบ' : money($s['total'] - $s['cost']) ?></td><?php endif; ?>
            <td><a href="?id=<?= $s['id'] ?>">ดู</a></td>
        </tr><?php endforeach;
    table_end();
} elseif ($section === 'orders') {
    if ($id) {
        $o = one('SELECT o.*,u.email FROM orders o JOIN userdata u ON u.user_id=o.user_id WHERE o.order_id=?', [$id]);
        if (!$o) {
            error_notice('ไม่พบออเดอร์');
            return;
        }
        ?>
        <section class="card">
            <h2><?= e($o['order_code']) ?></h2>
            <p>ลูกค้า <?= e($o['email']) ?> · <?= e($o['phone_number']) ?></p>
            <p><?= nl2br(e($o['delivery_address'])) ?></p>
            <p>สถานะ <?= e(ORDER_STATUSES[$o['order_status']] ?? $o['order_status']) ?> · ชำระเงิน
                <?= e($o['payment_status'] === 'paid' ? 'ตรวจสอบแล้ว' : 'รอตรวจสอบ') ?> ·
                <?= e(PAYMENT_METHODS[$o['payment_method']] ?? $o['payment_method'] ?? 'ไม่ระบุ') ?></p>
            <?php
            table_start($admin ? ['สินค้า', 'จำนวน', 'ราคาขาย', 'ต้นทุนต่อชิ้น'] : ['สินค้า', 'จำนวน', 'ราคาขาย']);
            foreach (rows('SELECT product_name,quantity,price' . ($admin ? ',unit_cost' : '') . ' FROM order_items WHERE order_id=?', [$id]) as $i): ?>
                <tr>
                    <td><?= e($i['product_name']) ?></td>
                    <td><?= $i['quantity'] ?></td>
                    <td><?= money($i['price']) ?></td><?php if ($admin): ?>
                        <td><?= $i['unit_cost'] === null ? 'ไม่ทราบ' : money($i['unit_cost']) ?></td><?php endif; ?>
                </tr><?php endforeach;
            table_end(); ?>
            <h2>ยอดรวม <?= money($o['total_price']) ?> บาท</h2><?php if ($o['payment_slip']): ?><a
                    href="/slip.php?id=<?= $o['order_id'] ?>" target="_blank" rel="noopener">เปิดหลักฐานการชำระเงิน</a>
                <p><img class="detail-image" src="/slip.php?id=<?= $o['order_id'] ?>" alt="หลักฐานชำระเงิน"></p><?php endif; ?>
            <?php if (!in_array($o['order_status'], ['Completed', 'Cancelled'], true)): ?>
                <div class="grid">
                    <form method="post"><?php csrf_field(); ?><input type="hidden" name="action" value="status"><input type="hidden"
                            name="order_id" value="<?= $id ?>"><label>เปลี่ยนสถานะ<select
                                name="status"><?php foreach (ORDER_TRANSITIONS[$o['order_status']] ?? [] as $key):
                                    $label = ORDER_STATUSES[$key]; ?>
                                    <option value="<?= $key ?>"><?= $label ?></option><?php endforeach; ?>
                            </select></label>
                        <div class="actions"><button>ยืนยันสถานะ</button></div>
                    </form>
                    <form method="post"><?php csrf_field(); ?><input type="hidden" name="action" value="tracking"><input
                            type="hidden" name="order_id"
                            value="<?= $id ?>"><?php field('เลขติดตามพัสดุ', 'transport_id', $o['transport_id'], 'text', false); ?>
                        <div class="actions"><button>บันทึกเลขพัสดุ</button></div>
                    </form>
                </div><?php if ($o['payment_status'] !== 'paid'): ?>
                    <form method="post" class="actions" data-confirm="ตรวจสอบและได้รับเงินครบแล้วใช่ไหม?"><?php csrf_field(); ?><input
                            type="hidden" name="action" value="payment"><input type="hidden" name="order_id"
                            value="<?= $id ?>"><button>ยืนยันตรวจสอบการชำระเงิน</button></form><?php endif; endif; ?>
        </section>
        <?php
    }
    $_GET['start'] ??= one('SELECT MIN(DATE(order_date)) first FROM orders')['first'] ?? date('Y-m-01');
    try {
        [$start, $end, $from, $until] = date_filter();
    } catch (RuntimeException $ex) {
        error_notice($ex->getMessage());
        return;
    }
    filter_form($start, $end);
    table_start(['ออเดอร์', 'ลูกค้า', 'วันที่', 'ยอดรวม', 'สถานะ', 'ชำระเงิน', 'รายละเอียด']);
    foreach (rows('SELECT o.order_id,o.order_code,o.order_date,o.total_price,o.order_status,o.payment_status,u.email FROM orders o JOIN userdata u ON u.user_id=o.user_id WHERE order_date>=? AND order_date<? ORDER BY o.order_id DESC', [$from, $until]) as $o): ?>
        <tr>
            <td><?= e($o['order_code']) ?></td>
            <td><?= e($o['email']) ?></td>
            <td><?= e($o['order_date']) ?></td>
            <td><?= money($o['total_price']) ?></td>
            <td><?= e(ORDER_STATUSES[$o['order_status']] ?? $o['order_status']) ?></td>
            <td><?= $o['payment_status'] === 'paid' ? 'ตรวจสอบแล้ว' : 'รอตรวจสอบ' ?></td>
            <td><a href="?id=<?= $o['order_id'] ?>">ดู / ดำเนินการ</a></td>
        </tr>
    <?php endforeach;
    table_end();
}
