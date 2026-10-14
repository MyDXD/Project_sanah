<?php
require_once __DIR__ . '/bootstrap.php';
$navUser = current_user();
?>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<header style="font-family:Arial,Tahoma,sans-serif">
    <div
        style="background:#1f2937;color:white;padding:10px 5%;display:flex;gap:18px;justify-content:flex-end;flex-wrap:wrap">
        <?php if ($navUser): ?>
            <a href="/profile.php">คุณ <?= e($navUser['first_name']) ?></a>
            <?php if (in_array($navUser['role'], ['admin', 'employee'], true)): ?><a
                    href="/<?= e($navUser['role']) ?>/dashboard.php">ระบบ<?= e($navUser['role'] === 'admin' ? 'ผู้ดูแล' : 'พนักงาน') ?></a><?php endif; ?>
            <a href="/cart.php">ตะกร้า</a><a href="/order.php">ออเดอร์ของฉัน</a><button
                onclick="confirmLogout()">ออกจากระบบ</button>
        <?php else: ?><a href="/login.php">เข้าสู่ระบบ</a><a href="/register.php">สมัครสมาชิก</a><?php endif; ?>
    </div>
    <nav
        style="background:white;padding:20px 5%;display:flex;align-items:center;gap:25px;flex-wrap:wrap;border-bottom:1px solid #eadfce;color:#5f4a35">
        <a href="/" style="font-weight:bold;font-size:22px">สานะกิมหยง 🤎</a><a href="/products.php">สินค้าทั้งหมด</a><a
            href="/scan.php">สแกน QR</a><a href="/contactus.php">ติดต่อร้าน</a><?php if (($navUser['role'] ?? '') !== 'employee'): ?><a href="/report_user.php">ความคิดเห็น /
            ข้อเสนอแนะ</a><?php endif; ?>
    </nav>
</header>
<script>function confirmLogout() { Swal.fire({ title: 'ออกจากระบบ?', icon: 'question', showCancelButton: true, confirmButtonText: 'ออกจากระบบ', cancelButtonText: 'ยกเลิก', confirmButtonColor: '#9a641f' }).then(result => { if (result.isConfirmed) location.href = '/logout.php'; }); }</script>
