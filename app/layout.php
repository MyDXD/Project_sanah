<?php
require_once __DIR__ . '/bootstrap.php';
function page_start($title, $staff = false)
{
    $user = current_user();
    $role = $user['role'] ?? 'guest';
    $root = $role === 'admin' ? '/admin/' : '/employee/';
    $links = $staff ? [
        $root . 'dashboard.php' => '📊 แดชบอร์ด',
        $root . 'pos.php' => '🏪 ขายหน้าร้าน',
        $root . 'sales.php' => '🧾 รายการขายหน้าร้าน',
        $root . 'stock.php' => '📦 Stock / รับสินค้า',
        $root . 'stock_history.php' => '🕒 ประวัติ Stock',
        $root . 'orders.php' => '🛒 ออเดอร์ออนไลน์'
    ] : ['/' => '🏠 หน้าแรก', '/products.php' => '🛍️ สินค้า', '/scan.php' => '📷 สแกน QR', '/cart.php' => '🛒 ตะกร้า', '/order.php' => '📦 ออเดอร์ของฉัน', '/report_user.php' => '💬 ความคิดเห็น'];
    if ($role === 'employee') { unset($links['/report_user.php']); }
    if ($staff && $role === 'admin') {
        $links += ['/admin/products.php' => '🏷️ จัดการสินค้า / QR', '/admin/users.php' => '👥 ผู้ใช้งาน / พนักงาน', '/admin/feedback.php' => '💬 ความคิดเห็น', '/admin/reports.php' => '📈 รายงาน', '/admin/settings.php' => '⚙️ ตั้งค่าการชำระเงิน'];
    }
    if ($user) {
        $links['/profile.php'] = '👤 โปรไฟล์';
        if (!$staff && in_array($role, ['admin', 'employee'], true)) {
            $links[$root . 'dashboard.php'] = 'ระบบร้านค้า';
        }
        if ($staff) {
            $links['/'] = 'เว็บไซต์ร้าน';
        }
        $links['/logout.php'] = 'ออกจากระบบ';
    } else {
        $links['/login.php'] = 'เข้าสู่ระบบ';
        $links['/register.php'] = 'สมัครสมาชิก';
    }
    ?><!doctype html>
    <html lang="th">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title><?= e($title) ?> | สานะกิมหยง</title>
        <link rel="stylesheet" href="/assets/shop.css">
        <script defer src="/assets/shop.js"></script>
    </head>

    <body>
        <div class="shell">
            <aside class="sidebar"><a class="brand" href="/">สานะกิมหยง 🤎</a>
                <p><?= e(ucfirst($role)) ?>     <?= e($user['first_name'] ?? '') ?></p>
                <nav><?php foreach ($links as $url => $label): ?><a href="<?= e($url) ?>"><?= e($label) ?></a><?php endforeach; ?>
                </nav>
            </aside>
            <main>
                <header class="page-header">
                    <h1><?= e($title) ?></h1><span><?= date('d/m/Y') ?></span>
                </header>
                <?php if (!empty($_SESSION['flash'])): ?>
                    <div class="notice" role="status"><?= e($_SESSION['flash']) ?></div><?php unset($_SESSION['flash']); endif;
}
function page_end()
{
    echo '</main></div></body></html>';
}
function error_notice($error)
{
    if ($error) {
        echo '<div class="error" role="alert">' . e($error) . '</div>';
    }
}
function field($label, $name, $value = '', $type = 'text', $required = true, $attributes = '')
{
    echo '<label>' . e($label) . '<input name="' . e($name) . '" type="' . e($type) . '" value="' . e($value) . '" ' . ($required ? 'required ' : '') . $attributes . '></label>';
}
function date_filter()
{
    $start = $_GET['start'] ?? date('Y-m-01');
    $end = $_GET['end'] ?? date('Y-m-d');
    foreach ([$start, $end] as $date) {
        $parsed = DateTime::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date) {
            throw new RuntimeException('วันที่ไม่ถูกต้อง');
        }
    }
    if ($start > $end) {
        throw new RuntimeException('วันที่เริ่มต้นต้องไม่เกินวันที่สิ้นสุด');
    }
    return [$start, $end, $start . ' 00:00:00', (new DateTime($end))->modify('+1 day')->format('Y-m-d') . ' 00:00:00'];
}
function filter_form($start, $end)
{ ?>
                <form method="get" class="toolbar">
                    <?php field('ตั้งแต่วันที่', 'start', $start, 'date');
                    field('ถึงวันที่', 'end', $end, 'date'); ?><button>ดูรายงาน</button>
                </form>
            <?php }
function table_start($headers)
{
    echo '<div class="table-wrap"><table><thead><tr>';
    foreach ($headers as $header) {
        echo '<th>' . e($header) . '</th>';
    }
    echo '</tr></thead><tbody>';
}
function table_end()
{
    echo '</tbody></table></div>';
}
function cards($values)
{
    echo '<div class="stats">';
    foreach ($values as $label => $value) {
        echo '<article class="card stat"><span>' . e($label) . '</span><strong>' . e($value) . '</strong></article>';
    }
    echo '</div>';
}
