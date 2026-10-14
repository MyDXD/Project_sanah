<?php

/* =========================================================
   PHP ต้องอยู่บนสุด ห้ามมี HTML / script ก่อนหน้านี้
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../dbconnect.php";


/* =========================================================
   CHECK ADMIN
========================================================= */

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login_form.php");
    exit();
}


/* =========================================================
   AJAX / POST UPDATE
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    header('Content-Type: application/json; charset=utf-8');

    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        echo json_encode([
            "success" => false,
            "message" => "ไม่พบข้อมูลที่ส่งมา"
        ]);
        exit();
    }

    $order_id = isset($data['order_id'])
        ? intval($data['order_id'])
        : 0;


    if ($order_id <= 0) {
        echo json_encode([
            "success" => false,
            "message" => "ไม่พบรหัสคำสั่งซื้อ"
        ]);
        exit();
    }


    /* =====================================================
       UPDATE STATUS
    ===================================================== */

    if (isset($data['status'])) {

        $new_status = trim($data['status']);

        $allowed_status = [
            'Order Placed',
            'Payment Received',
            'Order Processing',
            'Completed',
            'Cancelled'
        ];

        if (!in_array($new_status, $allowed_status)) {

            echo json_encode([
                "success" => false,
                "message" => "สถานะไม่ถูกต้อง"
            ]);

            exit();
        }


        $stmt = mysqli_prepare(
            $conn,
            "UPDATE orders 
             SET order_status = ?
             WHERE order_id = ?"
        );

        if (!$stmt) {

            echo json_encode([
                "success" => false,
                "message" => "ไม่สามารถเตรียมคำสั่ง SQL ได้"
            ]);

            exit();
        }


        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $new_status,
            $order_id
        );


        if (mysqli_stmt_execute($stmt)) {

            echo json_encode([
                "success" => true,
                "message" => "เปลี่ยนสถานะเรียบร้อยแล้ว"
            ]);

        } else {

            echo json_encode([
                "success" => false,
                "message" => "ไม่สามารถเปลี่ยนสถานะได้"
            ]);
        }


        mysqli_stmt_close($stmt);
        exit();
    }


    /* =====================================================
       UPDATE TRANSPORT
    ===================================================== */

    if (isset($data['transport_id'])) {

        $new_transport_id = trim($data['transport_id']);


        if ($new_transport_id === '') {

            echo json_encode([
                "success" => false,
                "message" => "กรุณากรอกหมายเลขจัดส่ง"
            ]);

            exit();
        }


        /*
           บันทึกเลขพัสดุ
           และเปลี่ยนสถานะเป็น Order Processing
           ในครั้งเดียว
        */

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE orders
             SET transport_id = ?,
                 order_status = 'Order Processing'
             WHERE order_id = ?"
        );


        if (!$stmt) {

            echo json_encode([
                "success" => false,
                "message" => "ไม่สามารถเตรียมคำสั่ง SQL ได้"
            ]);

            exit();
        }


        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $new_transport_id,
            $order_id
        );


        if (mysqli_stmt_execute($stmt)) {

            echo json_encode([
                "success" => true,
                "message" => "บันทึกเลขพัสดุเรียบร้อยแล้ว"
            ]);

        } else {

            echo json_encode([
                "success" => false,
                "message" => "ไม่สามารถบันทึกเลขพัสดุได้"
            ]);
        }


        mysqli_stmt_close($stmt);
        exit();
    }


    echo json_encode([
        "success" => false,
        "message" => "ไม่พบคำสั่งที่ต้องการทำ"
    ]);

    exit();
}


/* =========================================================
   SEARCH / FILTER
========================================================= */

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

$status = isset($_GET['status'])
    ? $_GET['status']
    : 'all';


/* =========================================================
   GET ORDERS
========================================================= */

$order_query = "
    SELECT 
        orders.order_id,
        orders.payment_slip,
        orders.order_code,
        orders.total_price,
        orders.order_status,
        orders.order_date,
        orders.transport_id,
        userdata.first_name,
        userdata.last_name
    FROM orders
    JOIN userdata
        ON orders.user_id = userdata.user_id
";


$conditions = [];


if ($search !== '') {

    $safe_search = mysqli_real_escape_string(
        $conn,
        $search
    );

    $conditions[] =
        "LOWER(orders.order_code) LIKE LOWER('%$safe_search%')";
}


if ($status !== 'all') {

    $safe_status = mysqli_real_escape_string(
        $conn,
        $status
    );

    $conditions[] =
        "orders.order_status = '$safe_status'";
}


if (count($conditions) > 0) {

    $order_query .=
        " WHERE " . implode(" AND ", $conditions);
}


$order_query .= "
    ORDER BY orders.order_date DESC
";


$result = mysqli_query(
    $conn,
    $order_query
);

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>จัดการคำสั่งซื้อ | สานะกิมหยง</title>


    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    <style>

        body {
            background: #f6f1e8;
            font-family: Arial, sans-serif;
        }

        .page-header {
            background: linear-gradient(
                135deg,
                #fffaf2,
                #f3e5cf
            );

            border: 1px solid #eadfce;
            border-radius: 24px;
            padding: 26px 30px;
            margin-bottom: 20px;

            position: relative;
            overflow: hidden;

            box-shadow:
                0 10px 30px rgba(91,70,45,.08);
        }

        .page-header::after {
            content: "✦";

            position: absolute;
            right: 35px;
            top: -8px;

            font-size: 90px;

            color: rgba(180,120,50,.08);
        }

        .search-box {
            background: white;

            border: 1px solid #eadfce;

            border-radius: 16px;

            padding: 14px 18px;

            transition: .2s;

            outline: none;
        }

        .search-box:focus {
            border-color: #c28a43;

            box-shadow:
                0 0 0 4px rgba(194,138,67,.12);
        }

        .filter-container {
            background: white;

            border: 1px solid #eadfce;

            border-radius: 18px;

            padding: 7px;

            display: flex;

            gap: 5px;

            flex-wrap: wrap;

            box-shadow:
                0 5px 20px rgba(91,70,45,.05);
        }

        .filter-btn {
            border-radius: 12px;

            padding: 9px 14px;

            font-size: 13px;

            font-weight: 600;

            color: #786b5c;

            border: none;

            background: transparent;

            cursor: pointer;
        }

        .filter-btn:hover {
            background: #fff4df;
            color: #9a641f;
        }

        .filter-active {
            background: #9a641f !important;
            color: white !important;
        }

        .orders-card {
            background: white;

            border-radius: 24px;

            border: 1px solid #eadfce;

            overflow: hidden;

            box-shadow:
                0 10px 30px rgba(91,70,45,.07);
        }

        .orders-card-header {
            padding: 20px 24px;

            background: #fffaf4;

            border-bottom: 1px solid #eee5d8;
        }

        .table-header {
            background: #faf7f1;
        }

        .table-header th {
            color: #7c7062;

            font-size: 12px;

            font-weight: 700;

            padding: 15px 14px;

            white-space: nowrap;
        }

        .order-row {
            transition: .2s;
        }

        .order-row:hover {
            background: #fffaf2;
        }

        .order-row td {
            padding: 15px 14px;

            border-bottom: 1px solid #f0ebe3;

            white-space: nowrap;
        }

        .order-code {
            color: #8b5e34;
            font-weight: 700;
        }

        .order-id {
            width: 34px;
            height: 34px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 10px;

            background: #fff3dc;

            color: #9a641f;

            font-size: 12px;

            font-weight: 700;
        }

        .customer-avatar {
            width: 38px;
            height: 38px;

            border-radius: 12px;

            background: #f8ead5;

            display: flex;

            align-items: center;
            justify-content: center;

            margin-right: 10px;

            font-size: 18px;
        }

        .status-badge {
            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding: 7px 12px;

            border-radius: 999px;

            font-size: 11px;

            font-weight: 700;
        }

        .status-payment {
            background: #eaf4ff;
            color: #2563eb;
        }

        .status-processing {
            background: #fff4df;
            color: #c26d16;
        }

        .status-completed {
            background: #eaf8ef;
            color: #15803d;
        }

        .status-cancelled {
            background: #fff0f0;
            color: #dc2626;
        }

        .status-placed {
            background: #fff8df;
            color: #b7791f;
        }

        .status-default {
            background: #f3f3f3;
            color: #666;
        }

        .price {
            color: #9a641f;

            font-weight: 700;

            font-size: 14px;
        }

        .slip-image {
            width: 58px;
            height: 58px;

            object-fit: cover;

            border-radius: 12px;

            border: 2px solid #f0e5d4;

            cursor: pointer;
        }

        .action-btn {
            border: none;

            border-radius: 10px;

            padding: 7px 11px;

            font-size: 11px;

            font-weight: 700;

            transition: .2s;

            margin-right: 4px;

            cursor: pointer;
        }

        .action-btn:hover {
            transform: translateY(-2px);
        }

        .btn-confirm {
            background: #eaf8ef;
            color: #15803d;
        }

        .btn-cancel {
            background: #fff0f0;
            color: #dc2626;
        }

        .btn-transport {
            background: #edf4ff;
            color: #2563eb;
        }

        .modal-box {
            background: white;

            border-radius: 22px;

            padding: 25px;

            box-shadow:
                0 25px 70px rgba(0,0,0,.25);
        }

        .fade-in {
            animation: fadeIn .45s ease;
        }

        @keyframes fadeIn {

            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }

        }

    </style>

</head>


<body>

<div class="flex w-full h-screen">

    <?php include 'sidebar.php'; ?>


    <div class="flex-1 p-6 overflow-y-auto h-screen">

        <div class="max-w-7xl mx-auto fade-in">


            <!-- HEADER -->

            <div class="page-header">

                <div class="relative z-10">

                    <p class="text-sm font-bold tracking-widest text-yellow-700 mb-2">
                        ADMIN PANEL
                    </p>

                    <h1 class="text-3xl font-bold text-gray-800">
                        จัดการคำสั่งซื้อ
                    </h1>

                    <p class="text-gray-500 mt-2">
                        ตรวจสอบและจัดการคำสั่งซื้อของร้านสานะกิมหยง
                    </p>

                </div>

            </div>


            <!-- SEARCH -->

            <div class="flex flex-col xl:flex-row gap-4 mb-5">

                <form method="GET" class="flex-1">

                    <div class="relative">

                        <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400">
                            🔎
                        </span>

                        <input
                            id="searchInput"
                            type="text"
                            name="search"
                            value="<?= htmlspecialchars($search) ?>"
                            placeholder="ค้นหาด้วยรหัสคำสั่งซื้อ..."
                            class="search-box w-full pl-11 text-sm"
                        >

                    </div>

                </form>


                <!-- FILTER -->

                <div class="filter-container">

                    <button
                        type="button"
                        class="filter-btn <?= $status === 'all' ? 'filter-active' : '' ?>"
                        onclick="filterOrders('all')">

                        ทั้งหมด

                    </button>


                    <button
                        type="button"
                        class="filter-btn <?= $status === 'Order Placed' ? 'filter-active' : '' ?>"
                        onclick="filterOrders('Order Placed')">

                        🟡 รอชำระ

                    </button>


                    <button
                        type="button"
                        class="filter-btn <?= $status === 'Payment Received' ? 'filter-active' : '' ?>"
                        onclick="filterOrders('Payment Received')">

                        🔵 ชำระแล้ว

                    </button>


                    <button
                        type="button"
                        class="filter-btn <?= $status === 'Order Processing' ? 'filter-active' : '' ?>"
                        onclick="filterOrders('Order Processing')">

                        🟠 กำลังจัดส่ง

                    </button>


                    <button
                        type="button"
                        class="filter-btn <?= $status === 'Completed' ? 'filter-active' : '' ?>"
                        onclick="filterOrders('Completed')">

                        🟢 สำเร็จ

                    </button>


                    <button
                        type="button"
                        class="filter-btn <?= $status === 'Cancelled' ? 'filter-active' : '' ?>"
                        onclick="filterOrders('Cancelled')">

                        🔴 ยกเลิก

                    </button>

                </div>

            </div>


            <!-- TABLE -->

            <div class="orders-card">

                <div class="orders-card-header">

                    <div class="flex items-center justify-between">

                        <div>

                            <h2 class="text-xl font-bold text-gray-800">
                                รายการคำสั่งซื้อ
                            </h2>

                            <p class="text-sm text-gray-400 mt-1">
                                ตรวจสอบสถานะ การชำระเงิน และการจัดส่ง
                            </p>

                        </div>

                        <div class="text-2xl">
                            🛍️
                        </div>

                    </div>

                </div>


                <div class="overflow-x-auto">

                    <table class="w-full text-left">

                        <thead class="table-header">

                            <tr>

                                <th>ID</th>
                                <th>รหัสสั่งซื้อ</th>
                                <th>ลูกค้า</th>
                                <th>วันที่</th>
                                <th>สถานะ</th>
                                <th>ยอดรวม</th>
                                <th>รหัสขนส่ง</th>
                                <th>สลิป</th>
                                <th>การจัดการ</th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php if ($result && mysqli_num_rows($result) > 0): ?>

                            <?php while ($row = mysqli_fetch_assoc($result)): ?>

                                <?php
                                $currentStatus =
                                    $row['order_status'];
                                ?>

                                <tr class="order-row">

                                    <!-- ID -->

                                    <td>

                                        <div class="order-id">
                                            <?= htmlspecialchars($row['order_id']) ?>
                                        </div>

                                    </td>


                                    <!-- CODE -->

                                    <td>

                                        <span class="order-code">
                                            #<?= htmlspecialchars($row['order_code']) ?>
                                        </span>

                                    </td>


                                    <!-- CUSTOMER -->

                                    <td>

                                        <div class="flex items-center">

                                            <div class="customer-avatar">
                                                👤
                                            </div>

                                            <div class="font-semibold text-gray-700">

                                                <?= htmlspecialchars(
                                                    $row['first_name'] . ' ' . $row['last_name']
                                                ) ?>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- DATE -->

                                    <td>

                                        <span class="text-sm text-gray-500">
                                            <?= htmlspecialchars($row['order_date']) ?>
                                        </span>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                    <?php

                                    if ($currentStatus === 'Order Placed') {

                                        echo '<span class="status-badge status-placed">
                                                🟡 รอชำระเงิน
                                              </span>';

                                    } elseif ($currentStatus === 'Payment Received') {

                                        echo '<span class="status-badge status-payment">
                                                🔵 ชำระแล้ว
                                              </span>';

                                    } elseif ($currentStatus === 'Order Processing') {

                                        echo '<span class="status-badge status-processing">
                                                🟠 กำลังจัดส่ง
                                              </span>';

                                    } elseif ($currentStatus === 'Completed') {

                                        echo '<span class="status-badge status-completed">
                                                🟢 สำเร็จ
                                              </span>';

                                    } elseif ($currentStatus === 'Cancelled') {

                                        echo '<span class="status-badge status-cancelled">
                                                🔴 ยกเลิก
                                              </span>';

                                    } else {

                                        echo '<span class="status-badge status-default">
                                                ไม่ทราบสถานะ
                                              </span>';
                                    }

                                    ?>

                                    </td>


                                    <!-- PRICE -->

                                    <td>

                                        <span class="price">

                                            ฿<?= number_format(
                                                $row['total_price'],
                                                2
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- TRANSPORT -->

                                    <td>

                                        <?php if (!empty($row['transport_id'])): ?>

                                            <span class="text-sm font-medium text-gray-600">

                                                🚚
                                                <?= htmlspecialchars($row['transport_id']) ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="text-gray-400 text-sm">
                                                ยังไม่มี
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- SLIP -->

                                    <td>

                                        <?php if (!empty($row['payment_slip'])): ?>

                                            <img
                                                src="../<?= htmlspecialchars($row['payment_slip']) ?>"
                                                alt="Slip"
                                                class="slip-image"
                                                onclick="showFullScreenModal(this.src)"
                                            >

                                        <?php else: ?>

                                            <span class="text-gray-400 text-xs">
                                                ไม่มีสลิป
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- ACTION -->

                                    <td>

                                    <?php if (
                                        $currentStatus !== 'Completed'
                                        &&
                                        $currentStatus !== 'Cancelled'
                                    ): ?>

                                        <button
                                            type="button"
                                            onclick="confirmUpdate(
                                                <?= $row['order_id'] ?>,
                                                'Completed'
                                            )"
                                            class="action-btn btn-confirm">

                                            ✓ ยืนยัน

                                        </button>


                                        <button
                                            type="button"
                                            onclick="confirmUpdate(
                                                <?= $row['order_id'] ?>,
                                                'Cancelled'
                                            )"
                                            class="action-btn btn-cancel">

                                            ✕ ยกเลิก

                                        </button>


                                        <button
                                            type="button"
                                            onclick='openTransportModal(
                                                <?= $row['order_id'] ?>,
                                                <?= json_encode($row['transport_id'] ?? '') ?>
                                            )'
                                            class="action-btn btn-transport">

                                            🚚 จัดส่ง

                                        </button>

                                    <?php else: ?>

                                        <span class="text-xs text-gray-400">
                                            ไม่มีการจัดการ
                                        </span>

                                    <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="9"
                                    class="text-center py-16">

                                    <div class="text-5xl mb-3">
                                        📦
                                    </div>

                                    <p class="text-gray-500 font-medium">
                                        ไม่มีคำสั่งซื้อ
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


<!-- =====================================================
     SLIP MODAL
===================================================== -->

<div
    id="fullScreenModal"
    class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">

    <span
        class="absolute top-5 right-7 text-4xl text-white cursor-pointer"
        onclick="closeFullScreenModal()">

        &times;

    </span>

    <img
        id="modalImage"
        class="max-w-full max-h-screen rounded-xl shadow-2xl"
    >

</div>


<!-- =====================================================
     TRANSPORT MODAL
===================================================== -->

<div
    id="transportModal"
    class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">

    <div class="modal-box w-96">

        <div class="flex items-center mb-5">

            <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-2xl mr-3">
                🚚
            </div>

            <div>

                <h2 class="text-xl font-bold text-gray-800">
                    หมายเลขจัดส่ง
                </h2>

                <p class="text-sm text-gray-400">
                    กรอกเลขพัสดุของคำสั่งซื้อ
                </p>

            </div>

        </div>


        <input
            type="hidden"
            id="orderId"
        >


        <input
            type="text"
            id="newTransportId"
            class="w-full p-3 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-100"
            placeholder="เช่น TH123456789"
        >


        <div class="flex justify-end gap-2 mt-5">

            <button
                type="button"
                onclick="closeTransportModal()"
                class="px-5 py-2.5 bg-gray-100 text-gray-600 rounded-xl font-medium">

                ยกเลิก

            </button>


            <button
                type="button"
                onclick="saveTransport()"
                class="px-5 py-2.5 bg-blue-600 text-white rounded-xl font-medium">

                บันทึก

            </button>

        </div>

    </div>

</div>


<script>

/* =====================================================
   SLIP
===================================================== */

function showFullScreenModal(src) {

    document.getElementById("modalImage").src = src;

    document
        .getElementById("fullScreenModal")
        .classList.remove("hidden");
}


function closeFullScreenModal() {

    document
        .getElementById("fullScreenModal")
        .classList.add("hidden");
}


/* =====================================================
   FILTER
===================================================== */

function filterOrders(status) {

    const url =
        new URL(window.location.href);

    url.searchParams.delete("search");

    url.searchParams.set("status", status);

    window.location.href =
        url.toString();
}


/* =====================================================
   SEARCH
===================================================== */

document
    .getElementById("searchInput")
    .addEventListener("keydown", function(event) {

        if (event.key === "Enter") {

            event.preventDefault();

            const url =
                new URL(window.location.href);

            url.searchParams.set(
                "search",
                this.value.trim()
            );

            url.searchParams.set(
                "status",
                "all"
            );

            window.location.href =
                url.toString();
        }

    });


/* =====================================================
   CONFIRM UPDATE
===================================================== */

function confirmUpdate(orderId, newStatus) {

    let title = "";
    let color = "#9a641f";


    if (newStatus === "Completed") {

        title = "ยืนยันคำสั่งซื้อ";
        color = "#15803d";

    } else {

        title = "ยกเลิกคำสั่งซื้อ";
        color = "#dc2626";
    }


    Swal.fire({

        title:
            `คุณต้องการ ${title} หรือไม่?`,

        text:
            "การเปลี่ยนสถานะจะถูกบันทึกเข้าสู่ระบบ",

        icon:
            "warning",

        showCancelButton:
            true,

        confirmButtonColor:
            color,

        cancelButtonColor:
            "#aaa",

        confirmButtonText:
            "ยืนยัน",

        cancelButtonText:
            "ยกเลิก"

    }).then(function(result) {

        if (result.isConfirmed) {

            updateOrderStatus(
                orderId,
                newStatus
            );

        }

    });
}


/* =====================================================
   UPDATE STATUS
===================================================== */

function updateOrderStatus(
    orderId,
    newStatus
) {

    fetch(window.location.href, {

        method: "POST",

        headers: {
            "Content-Type":
                "application/json"
        },

        body: JSON.stringify({

            order_id:
                orderId,

            status:
                newStatus

        })

    })

    .then(function(response) {

        return response.text();

    })

    .then(function(text) {

        let data;

        try {

            data = JSON.parse(text);

        } catch (e) {

            console.error(
                "Server response:",
                text
            );

            throw new Error(
                "เซิร์ฟเวอร์ไม่ได้ส่ง JSON กลับมา"
            );
        }


        if (!data.success) {

            throw new Error(
                data.message ||
                "ไม่สามารถเปลี่ยนสถานะได้"
            );
        }


        return Swal.fire({

            title:
                "สำเร็จ!",

            text:
                "อัปเดตสถานะคำสั่งซื้อแล้ว",

            icon:
                "success",

            confirmButtonText:
                "ตกลง",

            confirmButtonColor:
                "#9a641f"

        });

    })

    .then(function() {

        location.reload();

    })

    .catch(function(error) {

        console.error(error);

        Swal.fire({

            title:
                "เกิดข้อผิดพลาด",

            text:
                error.message,

            icon:
                "error",

            confirmButtonText:
                "ตกลง"

        });

    });
}


/* =====================================================
   TRANSPORT MODAL
===================================================== */

function openTransportModal(
    orderId,
    currentTransportId
) {

    document.getElementById(
        "orderId"
    ).value = orderId;


    document.getElementById(
        "newTransportId"
    ).value =
        currentTransportId || "";


    document.getElementById(
        "transportModal"
    ).classList.remove("hidden");
}


function closeTransportModal() {

    document.getElementById(
        "transportModal"
    ).classList.add("hidden");
}


/* =====================================================
   SAVE TRANSPORT
===================================================== */

function saveTransport() {

    const orderId =
        document.getElementById(
            "orderId"
        ).value;


    const transportId =
        document.getElementById(
            "newTransportId"
        ).value.trim();


    if (!transportId) {

        Swal.fire({

            title:
                "กรุณากรอกหมายเลขจัดส่ง",

            icon:
                "warning",

            confirmButtonText:
                "ตกลง"

        });

        return;
    }


    fetch(window.location.href, {

        method: "POST",

        headers: {
            "Content-Type":
                "application/json"
        },

        body: JSON.stringify({

            order_id:
                orderId,

            transport_id:
                transportId

        })

    })

    .then(function(response) {

        return response.text();

    })

    .then(function(text) {

        let data;

        try {

            data = JSON.parse(text);

        } catch (e) {

            console.error(
                "Server response:",
                text
            );

            throw new Error(
                "เซิร์ฟเวอร์ไม่ได้ส่งข้อมูลที่ถูกต้องกลับมา"
            );
        }


        if (!data.success) {

            throw new Error(
                data.message ||
                "ไม่สามารถบันทึกหมายเลขจัดส่งได้"
            );
        }


        return Swal.fire({

            title:
                "บันทึกสำเร็จ!",

            text:
                "บันทึกเลขพัสดุและเปลี่ยนสถานะเป็นกำลังจัดส่งแล้ว",

            icon:
                "success",

            confirmButtonText:
                "ตกลง",

            confirmButtonColor:
                "#9a641f"

        });

    })

    .then(function() {

        /*
           โหลดหน้าใหม่ทันที
           ข้อมูลเลขพัสดุจะแสดงเลย
        */

        location.reload();

    })

    .catch(function(error) {

        console.error(error);

        Swal.fire({

            title:
                "เกิดข้อผิดพลาด",

            text:
                error.message,

            icon:
                "error",

            confirmButtonText:
                "ตกลง"

        });

    });

}


/* =====================================================
   CLOSE MODALS
===================================================== */

document
    .getElementById("fullScreenModal")
    .addEventListener(
        "click",
        function(e) {

            if (e.target === this) {

                closeFullScreenModal();

            }

        }
    );


document
    .getElementById("transportModal")
    .addEventListener(
        "click",
        function(e) {

            if (e.target === this) {

                closeTransportModal();

            }

        }
    );

</script>

</body>
</html>