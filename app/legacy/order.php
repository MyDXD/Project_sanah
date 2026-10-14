<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("dbconnect.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login_form.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);

/* =========================
   จัดการคำสั่งซื้อ
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $order_id = intval($_POST['order_id'] ?? 0);

    // ยืนยันรับสินค้า
    if (($_POST['action'] ?? '') === 'confirm') {

        mysqli_query($conn, "
            UPDATE orders
            SET order_status = 'Completed'
            WHERE order_id = $order_id
            AND user_id = $user_id
            AND order_status = 'Order Processing'
        ");

        header("Location: order.php");
        exit();
    }

    // ยกเลิกคำสั่งซื้อ
    if (($_POST['action'] ?? '') === 'cancel') {

        mysqli_query($conn, "
            UPDATE orders
            SET order_status = 'Cancelled'
            WHERE order_id = $order_id
            AND user_id = $user_id
            AND order_status = 'Order Placed'
        ");

        header("Location: order.php");
        exit();
    }

    // อัปโหลดสลิป
    if (isset($_FILES['payment_slip']) &&
        $_FILES['payment_slip']['error'] === UPLOAD_ERR_OK) {

        $folder = "payment_slip/";

        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        $filename = uniqid() . "_" .
            basename($_FILES['payment_slip']['name']);

        $path = $folder . $filename;

        if (move_uploaded_file(
            $_FILES['payment_slip']['tmp_name'],
            $path
        )) {

            mysqli_query($conn, "
                UPDATE orders
                SET payment_slip = '$path',
                    order_status = 'Payment Received'
                WHERE order_id = $order_id
                AND user_id = $user_id
                AND order_status = 'Order Placed'
            ");
        }

        header("Location: order.php");
        exit();
    }
}


/* =========================
   ดึงคำสั่งซื้อ
========================= */

$sql = "
    SELECT
        order_id,
        order_code,
        total_price,
        order_status,
        order_date,
        payment_slip,
        transport_id
    FROM orders
    WHERE user_id = $user_id
    ORDER BY order_date DESC
";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>รายการคำสั่งซื้อ</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body class="bg-[#f6f1e8]">

<?php include "nav_bar.php"; ?>


<div class="max-w-6xl mx-auto px-4 py-10">

    <div class="mb-8">

        <h1 class="text-3xl font-bold text-[#7f5018]">
            รายการคำสั่งซื้อของฉัน
        </h1>

        <p class="text-gray-500 mt-2">
            ตรวจสอบสถานะและรายละเอียดคำสั่งซื้อของคุณ
        </p>

    </div>


    <?php if ($result && mysqli_num_rows($result) > 0): ?>

    <div class="bg-white rounded-2xl shadow-sm border border-[#eadfce] overflow-x-auto">

        <table class="w-full text-sm">

            <thead class="bg-[#fffaf2]">

                <tr>

                    <th class="p-4 text-left">คำสั่งซื้อ</th>
                    <th class="p-4 text-left">วันที่</th>
                    <th class="p-4 text-left">ราคา</th>
                    <th class="p-4 text-left">สถานะ</th>
                    <th class="p-4 text-left">เลขพัสดุ</th>
                    <th class="p-4 text-left">รายละเอียด</th>
                    <th class="p-4 text-left">จัดการ</th>

                </tr>

            </thead>


            <tbody>

            <?php while ($row = mysqli_fetch_assoc($result)): ?>

                <?php

                $status = $row['order_status'];

                $statusText = [
                    'Order Placed' => 'รอชำระเงิน',
                    'Payment Received' => 'ชำระเงินแล้ว รอตรวจสอบ',
                    'Order Processing' => 'กำลังจัดส่ง',
                    'Completed' => 'จัดส่งสำเร็จ',
                    'Cancelled' => 'ยกเลิก'
                ];

                $statusColor = [
                    'Order Placed' => 'bg-yellow-100 text-yellow-700',
                    'Payment Received' => 'bg-blue-100 text-blue-700',
                    'Order Processing' => 'bg-orange-100 text-orange-700',
                    'Completed' => 'bg-green-100 text-green-700',
                    'Cancelled' => 'bg-red-100 text-red-700'
                ];

                ?>

                <tr class="border-t hover:bg-[#fffaf2]">

                    <td class="p-4 font-semibold text-[#9a641f]">
                        #<?= htmlspecialchars($row['order_code']) ?>
                    </td>

                    <td class="p-4 text-gray-500">
                        <?= htmlspecialchars($row['order_date']) ?>
                    </td>

                    <td class="p-4 font-semibold">
                        ฿<?= number_format($row['total_price'], 2) ?>
                    </td>

                    <td class="p-4">

                        <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold
                        <?= $statusColor[$status] ?? 'bg-gray-100 text-gray-600' ?>">

                            <?= $statusText[$status] ?? $status ?>

                        </span>

                    </td>


                    <!-- เลขพัสดุ -->
                    <td class="p-4">

                        <?php if (!empty($row['transport_id'])): ?>

                            <div class="text-blue-600 font-semibold">
                                🚚 <?= htmlspecialchars($row['transport_id']) ?>
                            </div>

                        <?php else: ?>

                            <span class="text-gray-400">
                                ยังไม่มี
                            </span>

                        <?php endif; ?>

                    </td>


                    <!-- รายละเอียด -->
                    <td class="p-4">

                        <a
                            href="order_detail.php?order_id=<?= $row['order_id'] ?>"
                            class="text-[#9a641f] font-semibold hover:underline">

                            ดูรายละเอียด

                        </a>

                    </td>


                    <!-- จัดการ -->
                    <td class="p-4">

                        <?php if ($status === 'Order Placed'): ?>

                            <div class="flex flex-col gap-2">

                                <button
                                    type="button"
                                    onclick="openPayment(<?= $row['order_id'] ?>)"
                                    class="bg-[#9a641f] text-white px-3 py-2 rounded-lg hover:bg-[#7f5018] whitespace-nowrap">

                                    💳 ชำระเงิน / อัปโหลดสลิป

                                </button>

                                <button
                                    type="button"
                                    onclick="cancelOrder(<?= $row['order_id'] ?>)"
                                    class="bg-red-500 text-white px-3 py-2 rounded-lg hover:bg-red-600">

                                    ยกเลิก

                                </button>

                            </div>


                        <?php elseif ($status === 'Payment Received'): ?>

                            <span class="text-gray-400 text-xs">
                                รอร้านตรวจสอบ
                            </span>


                        <?php elseif ($status === 'Order Processing'): ?>

                            <button
                                type="button"
                                onclick="confirmReceive(<?= $row['order_id'] ?>)"
                                class="bg-green-500 text-white px-3 py-2 rounded-lg hover:bg-green-600 whitespace-nowrap">

                                ✓ ยืนยันรับสินค้า

                            </button>


                        <?php elseif ($status === 'Completed'): ?>

                            <span class="text-green-600 font-semibold">
                                ✓ สำเร็จ
                            </span>


                        <?php elseif ($status === 'Cancelled'): ?>

                            <span class="text-red-500">
                                ยกเลิกแล้ว
                            </span>

                        <?php endif; ?>

                    </td>

                </tr>

            <?php endwhile; ?>

            </tbody>

        </table>

    </div>


    <?php else: ?>

        <div class="bg-white rounded-2xl p-10 text-center shadow-sm border border-[#eadfce]">

            <div class="text-5xl mb-4">
                🛒
            </div>

            <p class="text-gray-500">
                ยังไม่มีคำสั่งซื้อ
            </p>

        </div>

    <?php endif; ?>

</div>


<!-- ==================================================
     MODAL ชำระเงิน
================================================== -->

<div
    id="paymentModal"
    class="hidden fixed inset-0 bg-black/60 z-50 overflow-y-auto">

    <div class="min-h-screen flex items-center justify-center p-4">

        <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden">

            <!-- Header -->

            <div class="bg-[#fffaf2] px-6 py-5 border-b border-[#eadfce]">

                <div class="flex items-center justify-between">

                    <div>

                        <h2 class="text-xl font-bold text-[#7f5018]">
                            💳 ชำระเงิน
                        </h2>

                        <p class="text-sm text-gray-500 mt-1">
                            สแกน QR เพื่อชำระเงิน
                        </p>

                    </div>

                    <button
                        type="button"
                        onclick="closePayment()"
                        class="text-gray-400 hover:text-gray-700 text-2xl">

                        ×

                    </button>

                </div>

            </div>


            <!-- QR -->

            <div class="p-6">

                <div class="bg-[#fffaf2] rounded-2xl p-4 border border-[#eadfce]">

                    <img
                        src="admin/img/sanah_slip.jpg"
                        alt="QR Code สำหรับชำระเงิน"
                        class="w-full max-w-[280px] mx-auto rounded-xl object-contain">

                </div>


                <div class="text-center mt-4">

                    <p class="font-semibold text-[#7f5018]">
                        สแกน QR เพื่อชำระเงิน
                    </p>

                    <p class="text-xs text-gray-500 mt-1">
                        กรุณาตรวจสอบยอดเงินก่อนชำระ
                    </p>

                </div>


                <!-- Upload -->

                <form
                    method="POST"
                    enctype="multipart/form-data"
                    class="mt-6">

                    <input
                        type="hidden"
                        name="order_id"
                        id="paymentOrderId">


                    <label class="block text-sm font-semibold text-gray-700 mb-2">

                        หลังจากชำระเงินแล้ว
                        <span class="text-red-500">*</span>

                    </label>


                    <input
                        type="file"
                        name="payment_slip"
                        accept="image/jpeg,image/png,image/jpg,image/webp"
                        required
                        class="w-full border border-[#eadfce] rounded-xl p-3 text-sm bg-white">


                    <p class="text-xs text-gray-400 mt-2">
                        รองรับไฟล์ JPG, PNG และ WEBP
                    </p>


                    <button
                        type="submit"
                        class="w-full mt-4 bg-[#9a641f] hover:bg-[#7f5018] text-white font-semibold py-3 rounded-xl">

                        📤 ส่งสลิปการชำระเงิน

                    </button>

                </form>

            </div>

        </div>

    </div>

</div>


<script>

function openPayment(orderId) {

    document.getElementById('paymentOrderId').value = orderId;

    document.getElementById('paymentModal')
        .classList.remove('hidden');

    document.body.classList.add('overflow-hidden');

}


function closePayment() {

    document.getElementById('paymentModal')
        .classList.add('hidden');

    document.body.classList.remove('overflow-hidden');

}


function confirmReceive(orderId) {

    Swal.fire({

        title: 'ยืนยันรับสินค้า?',

        text: 'เมื่อยืนยันแล้ว สถานะจะเปลี่ยนเป็นจัดส่งสำเร็จ',

        icon: 'question',

        showCancelButton: true,

        confirmButtonText: 'ยืนยันรับสินค้า',

        cancelButtonText: 'ยกเลิก',

        confirmButtonColor: '#16a34a',

        cancelButtonColor: '#9ca3af'

    }).then((result) => {

        if (result.isConfirmed) {

            submitAction(orderId, 'confirm');

        }

    });

}


function cancelOrder(orderId) {

    Swal.fire({

        title: 'ยกเลิกคำสั่งซื้อ?',

        text: 'เมื่อยกเลิกแล้วจะไม่สามารถดำเนินการต่อได้',

        icon: 'warning',

        showCancelButton: true,

        confirmButtonText: 'ยกเลิกคำสั่งซื้อ',

        cancelButtonText: 'ไม่ใช่',

        confirmButtonColor: '#dc2626',

        cancelButtonColor: '#9ca3af'

    }).then((result) => {

        if (result.isConfirmed) {

            submitAction(orderId, 'cancel');

        }

    });

}


function submitAction(orderId, action) {

    const form = document.createElement('form');

    form.method = 'POST';

    form.action = 'order.php';

    form.innerHTML = `
        <input type="hidden" name="order_id" value="${orderId}">
        <input type="hidden" name="action" value="${action}">
    `;

    document.body.appendChild(form);

    form.submit();

}


// ปิด Modal เมื่อคลิกพื้นหลัง
document.getElementById('paymentModal').addEventListener('click', function(e) {

    if (e.target === this) {
        closePayment();
    }

});

</script>

</body>
</html>