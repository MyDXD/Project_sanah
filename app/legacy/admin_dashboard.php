<?php

// =====================================================
// START SESSION
// =====================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// =====================================================
// DATABASE
// =====================================================

include "../dbconnect.php";


// =====================================================
// CHECK ADMIN
// =====================================================

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login_form.php");
    exit();
}


// =====================================================
// UPDATE ORDER STATUS
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {

    $order_id = isset($_POST['order_id'])
        ? (int)$_POST['order_id']
        : 0;

    $new_status = isset($_POST['new_status'])
        ? trim($_POST['new_status'])
        : '';


    // สถานะที่อนุญาต
    $allowed_statuses = [
        'Pending',
        'Order Processing',
        'Shipped',
        'Completed',
        'Cancelled'
    ];


    if (
        $order_id > 0 &&
        in_array($new_status, $allowed_statuses, true)
    ) {

        $stmt = $conn->prepare("
            UPDATE orders
            SET order_status = ?
            WHERE order_id = ?
        ");

        $stmt->bind_param(
            "si",
            $new_status,
            $order_id
        );

        $stmt->execute();

        $stmt->close();
    }


    // กลับมาหน้า Dashboard
    header("Location: dashboard.php");
    exit();
}


// =====================================================
// TOTAL SALES
// =====================================================

$total_sales_query = "
    SELECT SUM(total_price) AS total_sales
    FROM orders
    WHERE order_status = 'Completed'
";

$total_sales_result = mysqli_query(
    $conn,
    $total_sales_query
);

$total_sales_data =
    mysqli_fetch_assoc($total_sales_result);

$total_sales =
    $total_sales_data['total_sales'] ?? 0;


// =====================================================
// TOTAL STOCK
// =====================================================

$stock_query = "
    SELECT SUM(stock) AS total_stock
    FROM products
";

$stock_result = mysqli_query(
    $conn,
    $stock_query
);

$stock_data =
    mysqli_fetch_assoc($stock_result);

$total_stock =
    $stock_data['total_stock'] ?? 0;


// =====================================================
// PENDING ORDERS
// =====================================================

$pending_orders_query = "
    SELECT COUNT(*) AS pending_orders
    FROM orders
    WHERE order_status != 'Completed'
    AND order_status != 'Cancelled'
";

$pending_orders_result = mysqli_query(
    $conn,
    $pending_orders_query
);

$pending_orders_data =
    mysqli_fetch_assoc($pending_orders_result);

$pending_orders =
    $pending_orders_data['pending_orders'] ?? 0;


// =====================================================
// RECENT ORDERS
// =====================================================

$recent_orders_query = "
    SELECT
        orders.order_id,
        orders.order_code,
        orders.total_price,
        orders.order_status,
        orders.order_date,
        userdata.first_name,
        userdata.last_name
    FROM orders
    JOIN userdata
        ON orders.user_id = userdata.user_id
    ORDER BY orders.order_date DESC
    LIMIT 10
";

$recent_orders_result = mysqli_query(
    $conn,
    $recent_orders_query
);

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dashboard | สานะกิมหยง</title>


    <link
        href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css"
        rel="stylesheet"
    >


    <style>

        /* =====================================
           พื้นฐาน
        ===================================== */

        body {

            background: #f6f1e8;

            font-family:
                Arial,
                sans-serif;

        }


        /* =====================================
           Header
        ===================================== */

        .dashboard-header {

            background:
                linear-gradient(
                    135deg,
                    #fffaf2,
                    #f3e5cf
                );

            border:
                1px solid #eadfce;

            border-radius:
                24px;

            padding:
                28px;

            margin-bottom:
                24px;

            position:
                relative;

            overflow:
                hidden;

            box-shadow:
                0 10px 30px
                rgba(91,70,45,.08);

        }


        .dashboard-header::after {

            content:
                "✦";

            position:
                absolute;

            right:
                30px;

            top:
                5px;

            font-size:
                90px;

            color:
                rgba(180,120,50,.08);

        }


        /* =====================================
           Statistics
        ===================================== */

        .stat-card {

            background:
                #ffffff;

            border:
                1px solid #eee5d8;

            border-radius:
                22px;

            padding:
                22px;

            box-shadow:
                0 8px 25px
                rgba(91,70,45,.07);

            transition:
                all .25s ease;

            position:
                relative;

            overflow:
                hidden;

        }


        .stat-card:hover {

            transform:
                translateY(-6px);

            box-shadow:
                0 15px 35px
                rgba(91,70,45,.12);

        }


        .stat-card::after {

            content:
                "";

            position:
                absolute;

            width:
                80px;

            height:
                80px;

            right:
                -25px;

            bottom:
                -25px;

            background:
                #fff5df;

            border-radius:
                50%;

        }


        .stat-icon {

            width:
                52px;

            height:
                52px;

            border-radius:
                16px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            font-size:
                25px;

            background:
                #fff3d9;

            position:
                relative;

            z-index:
                2;

        }


        /* =====================================
           Orders Card
        ===================================== */

        .orders-card {

            background:
                #ffffff;

            border:
                1px solid #eee5d8;

            border-radius:
                24px;

            overflow:
                hidden;

            box-shadow:
                0 10px 30px
                rgba(91,70,45,.07);

        }


        .orders-header {

            background:
                #fffaf4;

            padding:
                22px 24px;

            border-bottom:
                1px solid #eee5d8;

        }


        .order-row {

            transition:
                all .2s ease;

        }


        .order-row:hover {

            background:
                #fffaf2;

            box-shadow:
                inset 5px 0 0 #c28a43;

        }


        /* =====================================
           Order Code
        ===================================== */

        .order-code {

            color:
                #8b5e34;

            font-weight:
                700;

        }


        /* =====================================
           Price
        ===================================== */

        .order-price {

            color:
                #9a641f;

            font-weight:
                700;

        }


        /* =====================================
           Status
        ===================================== */

        .status-badge {

            display:
                inline-flex;

            align-items:
                center;

            padding:
                6px 11px;

            border-radius:
                999px;

            font-size:
                11px;

            font-weight:
                700;

            white-space:
                nowrap;

        }


        .status-pending {

            background:
                #fff7ed;

            color:
                #c2410c;

        }


        .status-processing {

            background:
                #eff6ff;

            color:
                #2563eb;

        }


        .status-shipped {

            background:
                #f5f3ff;

            color:
                #7c3aed;

        }


        .status-completed {

            background:
                #ecfdf3;

            color:
                #15803d;

        }


        .status-cancelled {

            background:
                #fef2f2;

            color:
                #dc2626;

        }


        /* =====================================
           Action Buttons
        ===================================== */

        .action-button {

            border:
                1px solid;

            border-radius:
                10px;

            padding:
                7px 10px;

            font-size:
                11px;

            font-weight:
                700;

            transition:
                all .2s ease;

            cursor:
                pointer;

        }


        .action-button:hover {

            transform:
                translateY(-2px);

        }


        .btn-confirm {

            background:
                #ecfdf3;

            color:
                #15803d;

            border-color:
                #bbf7d0;

        }


        .btn-confirm:hover {

            background:
                #15803d;

            color:
                white;

        }


        .btn-ship {

            background:
                #f5f3ff;

            color:
                #7c3aed;

            border-color:
                #ddd6fe;

        }


        .btn-ship:hover {

            background:
                #7c3aed;

            color:
                white;

        }


        .btn-complete {

            background:
                #eff6ff;

            color:
                #2563eb;

            border-color:
                #bfdbfe;

        }


        .btn-complete:hover {

            background:
                #2563eb;

            color:
                white;

        }


        .btn-cancel {

            background:
                #fef2f2;

            color:
                #dc2626;

            border-color:
                #fecaca;

        }


        .btn-cancel:hover {

            background:
                #dc2626;

            color:
                white;

        }


        /* =====================================
           Animation
        ===================================== */

        .fade-in {

            animation:
                fadeIn .5s ease;

        }


        @keyframes fadeIn {

            from {

                opacity:
                    0;

                transform:
                    translateY(12px);

            }

            to {

                opacity:
                    1;

                transform:
                    translateY(0);

            }

        }


        /* =====================================
           Scrollbar
        ===================================== */

        ::-webkit-scrollbar {

            width:
                8px;

            height:
                8px;

        }


        ::-webkit-scrollbar-track {

            background:
                #eee8de;

        }


        ::-webkit-scrollbar-thumb {

            background:
                #cbbda9;

            border-radius:
                10px;

        }


    </style>

</head>


<body>


<div class="flex w-full h-screen">


    <!-- SIDEBAR -->

    <?php include 'sidebar.php'; ?>


    <!-- CONTENT -->

    <div class="flex-1 p-6 overflow-y-auto h-screen">


        <div class="max-w-7xl mx-auto fade-in">


            <!-- =====================================
                 HEADER
            ====================================== -->

            <div class="dashboard-header">

                <div class="relative z-10">

                    <p class="text-sm font-bold tracking-widest text-amber-700 mb-2">

                        ADMIN PANEL

                    </p>


                    <h1 class="text-3xl md:text-4xl font-bold text-gray-800">

                        Dashboard

                    </h1>


                    <p class="text-gray-500 mt-2">

                        ภาพรวมการจัดการคลังสินค้าร้านสานะกิมหยงออนไลน์

                    </p>

                </div>

            </div>


            <!-- =====================================
                 STATISTICS
            ====================================== -->

            <div
                class="grid
                grid-cols-1
                md:grid-cols-2
                lg:grid-cols-4
                gap-5
                mb-6"
            >


                <!-- SALES -->

                <div class="stat-card">

                    <div class="flex items-start justify-between">

                        <div>

                            <p class="text-sm text-gray-500">

                                ยอดขายรวม

                            </p>


                            <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mt-2">

                                ฿<?= number_format($total_sales, 2) ?>

                            </h2>


                            <p class="text-xs text-green-600 mt-2 font-medium">

                                ✓ จากคำสั่งซื้อที่เสร็จสมบูรณ์

                            </p>

                        </div>


                        <div class="stat-icon">

                            💰

                        </div>

                    </div>

                </div>


                <!-- STOCK -->

                <div class="stat-card">

                    <div class="flex items-start justify-between">

                        <div>

                            <p class="text-sm text-gray-500">

                                สินค้าในสต็อก

                            </p>


                            <h2 class="text-3xl font-bold text-gray-800 mt-2">

                                <?= number_format($total_stock) ?>

                            </h2>


                            <p class="text-xs text-gray-400 mt-2">

                                ชิ้นทั้งหมดในคลัง

                            </p>

                        </div>


                        <div class="stat-icon">

                            📦

                        </div>

                    </div>

                </div>


                <!-- PENDING -->

                <div class="stat-card">

                    <div class="flex items-start justify-between">

                        <div>

                            <p class="text-sm text-gray-500">

                                รอดำเนินการ

                            </p>


                            <h2 class="text-3xl font-bold text-gray-800 mt-2">

                                <?= number_format($pending_orders) ?>

                            </h2>


                            <p class="text-xs text-orange-600 mt-2 font-medium">

                                ⚠ คำสั่งซื้อที่ยังไม่เสร็จสิ้น

                            </p>

                        </div>


                        <div class="stat-icon">

                            🛒

                        </div>

                    </div>

                </div>


                <!-- SHOP -->

                <div class="stat-card">

                    <div class="flex items-start justify-between">

                        <div>

                            <p class="text-sm text-gray-500">

                                ร้านค้า

                            </p>


                            <h2 class="text-xl font-bold text-gray-800 mt-2">

                                สานะกิมหยง

                            </h2>


                            <p class="text-xs text-gray-400 mt-2">

                                Online Store

                            </p>

                        </div>


                        <div class="stat-icon">

                            🏪

                        </div>

                    </div>

                </div>

            </div>


            <!-- =====================================
                 RECENT ORDERS
            ====================================== -->

            <div class="orders-card">


                <!-- HEADER -->

                <div
                    class="orders-header
                    flex
                    flex-col
                    md:flex-row
                    md:items-center
                    md:justify-between
                    gap-2"
                >

                    <div>

                        <h2 class="text-xl font-bold text-gray-800">

                            คำสั่งซื้อล่าสุด

                        </h2>


                        <p class="text-sm text-gray-400 mt-1">

                            แสดงรายการคำสั่งซื้อล่าสุด 10 รายการ

                        </p>

                    </div>


                    <div class="text-sm text-gray-400">

                        🕒 อัปเดตล่าสุด

                    </div>

                </div>


                <!-- TABLE -->

                <div class="overflow-x-auto">

                    <table class="min-w-full">


                        <thead class="bg-gray-50">

                            <tr>

                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500">

                                    ออเดอร์

                                </th>


                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500">

                                    ลูกค้า

                                </th>


                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500">

                                    ยอดรวม

                                </th>


                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500">

                                    สถานะ

                                </th>


                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500">

                                    วันที่

                                </th>


                                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500">

                                    จัดการ

                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if ($recent_orders_result && mysqli_num_rows($recent_orders_result) > 0): ?>


                            <?php while ($order = mysqli_fetch_assoc($recent_orders_result)): ?>


                                <tr class="order-row border-b border-gray-100">


                                    <!-- ORDER -->

                                    <td class="px-5 py-4">

                                        <span class="order-code">

                                            #<?= htmlspecialchars(
                                                $order['order_code']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- CUSTOMER -->

                                    <td class="px-5 py-4">

                                        <div class="flex items-center">


                                            <div
                                                class="w-9 h-9
                                                rounded-full
                                                bg-amber-100
                                                flex
                                                items-center
                                                justify-center
                                                mr-3"
                                            >

                                                👤

                                            </div>


                                            <p class="font-medium text-gray-700">

                                                <?= htmlspecialchars(
                                                    $order['first_name']
                                                    . ' '
                                                    . $order['last_name']
                                                ) ?>

                                            </p>

                                        </div>

                                    </td>


                                    <!-- PRICE -->

                                    <td class="px-5 py-4">

                                        <span class="order-price">

                                            ฿<?= number_format(
                                                $order['total_price'],
                                                2
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- STATUS -->

                                    <td class="px-5 py-4">


                                        <?php

                                        $status =
                                            $order['order_status'];

                                        ?>


                                        <?php if ($status === 'Pending'): ?>

                                            <span class="status-badge status-pending">

                                                🕐 รอยืนยัน

                                            </span>


                                        <?php elseif ($status === 'Order Processing'): ?>

                                            <span class="status-badge status-processing">

                                                ⚙ กำลังดำเนินการ

                                            </span>


                                        <?php elseif ($status === 'Shipped'): ?>

                                            <span class="status-badge status-shipped">

                                                🚚 จัดส่งแล้ว

                                            </span>


                                        <?php elseif ($status === 'Completed'): ?>

                                            <span class="status-badge status-completed">

                                                ✓ เสร็จสิ้น

                                            </span>


                                        <?php elseif ($status === 'Cancelled'): ?>

                                            <span class="status-badge status-cancelled">

                                                ✕ ยกเลิก

                                            </span>


                                        <?php else: ?>

                                            <span class="status-badge status-pending">

                                                <?= htmlspecialchars($status) ?>

                                            </span>

                                        <?php endif; ?>


                                    </td>


                                    <!-- DATE -->

                                    <td class="px-5 py-4">

                                        <span class="text-sm text-gray-500">

                                            <?= htmlspecialchars(
                                                $order['order_date']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- ACTION -->

                                    <td class="px-5 py-4">


                                        <div class="flex flex-wrap gap-2">


                                            <?php if ($status === 'Pending'): ?>


                                                <!-- ยืนยัน -->

                                                <form
                                                    method="POST"
                                                    style="display:inline;"
                                                    onsubmit="return confirm('ยืนยันคำสั่งซื้อนี้หรือไม่?');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="order_id"
                                                        value="<?= $order['order_id'] ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="new_status"
                                                        value="Order Processing"
                                                    >

                                                    <button
                                                        type="submit"
                                                        name="update_status"
                                                        class="action-button btn-confirm"
                                                    >

                                                        ✓ ยืนยัน

                                                    </button>

                                                </form>


                                                <!-- ยกเลิก -->

                                                <form
                                                    method="POST"
                                                    style="display:inline;"
                                                    onsubmit="return confirm('ต้องการยกเลิกคำสั่งซื้อนี้หรือไม่?');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="order_id"
                                                        value="<?= $order['order_id'] ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="new_status"
                                                        value="Cancelled"
                                                    >

                                                    <button
                                                        type="submit"
                                                        name="update_status"
                                                        class="action-button btn-cancel"
                                                    >

                                                        ✕ ยกเลิก

                                                    </button>

                                                </form>


                                            <?php elseif ($status === 'Order Processing'): ?>


                                                <!-- จัดส่ง -->

                                                <form
                                                    method="POST"
                                                    style="display:inline;"
                                                    onsubmit="return confirm('ยืนยันว่าคำสั่งซื้อนี้ถูกจัดส่งแล้วหรือไม่?');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="order_id"
                                                        value="<?= $order['order_id'] ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="new_status"
                                                        value="Shipped"
                                                    >

                                                    <button
                                                        type="submit"
                                                        name="update_status"
                                                        class="action-button btn-ship"
                                                    >

                                                        🚚 จัดส่ง

                                                    </button>

                                                </form>


                                                <!-- ยกเลิก -->

                                                <form
                                                    method="POST"
                                                    style="display:inline;"
                                                    onsubmit="return confirm('ต้องการยกเลิกคำสั่งซื้อนี้หรือไม่?');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="order_id"
                                                        value="<?= $order['order_id'] ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="new_status"
                                                        value="Cancelled"
                                                    >

                                                    <button
                                                        type="submit"
                                                        name="update_status"
                                                        class="action-button btn-cancel"
                                                    >

                                                        ✕ ยกเลิก

                                                    </button>

                                                </form>


                                            <?php elseif ($status === 'Shipped'): ?>


                                                <!-- เสร็จสิ้น -->

                                                <form
                                                    method="POST"
                                                    style="display:inline;"
                                                    onsubmit="return confirm('ยืนยันว่าลูกค้าได้รับสินค้าแล้วหรือไม่?');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="order_id"
                                                        value="<?= $order['order_id'] ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="new_status"
                                                        value="Completed"
                                                    >

                                                    <button
                                                        type="submit"
                                                        name="update_status"
                                                        class="action-button btn-complete"
                                                    >

                                                        ✓ เสร็จสิ้น

                                                    </button>

                                                </form>


                                                <!-- ยกเลิก -->

                                                <form
                                                    method="POST"
                                                    style="display:inline;"
                                                    onsubmit="return confirm('ต้องการยกเลิกคำสั่งซื้อนี้หรือไม่?');"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="order_id"
                                                        value="<?= $order['order_id'] ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="new_status"
                                                        value="Cancelled"
                                                    >

                                                    <button
                                                        type="submit"
                                                        name="update_status"
                                                        class="action-button btn-cancel"
                                                    >

                                                        ✕ ยกเลิก

                                                    </button>

                                                </form>


                                            <?php elseif ($status === 'Completed'): ?>


                                                <span class="text-xs text-green-600 font-semibold">

                                                    ✓ ดำเนินการเสร็จสิ้น

                                                </span>


                                            <?php elseif ($status === 'Cancelled'): ?>


                                                <span class="text-xs text-red-500 font-semibold">

                                                    ✕ ยกเลิกแล้ว

                                                </span>


                                            <?php endif; ?>


                                        </div>


                                    </td>


                                </tr>


                            <?php endwhile; ?>


                        <?php else: ?>


                            <tr>

                                <td
                                    colspan="6"
                                    class="px-5 py-12 text-center"
                                >

                                    <div class="text-5xl mb-3">

                                        📦

                                    </div>


                                    <p class="text-gray-500 font-medium">

                                        ยังไม่มีคำสั่งซื้อ

                                    </p>

                                </td>

                            </tr>


                        <?php endif; ?>


                        </tbody>

                    </table>

                </div>

            </div>


        </div>

    </div>

</div>


</body>

</html>