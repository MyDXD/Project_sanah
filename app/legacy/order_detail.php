<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "dbconnect.php";


// =========================
// CHECK LOGIN
// =========================

if (!isset($_SESSION['user_id'])) {
    echo "กรุณาล็อกอินก่อนเพื่อดูคำสั่งซื้อ";
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    echo "ไม่พบคำสั่งซื้อ";
    exit();
}


// =========================
// GET ORDER
// =========================

$order_sql = "
    SELECT *
    FROM orders
    WHERE order_id = '$order_id'
    AND user_id = '$user_id'
";

$order_result = mysqli_query($conn, $order_sql);

if (!$order_result || mysqli_num_rows($order_result) == 0) {
    echo "ไม่พบคำสั่งซื้อ";
    exit();
}

$order = mysqli_fetch_assoc($order_result);

$order_code = $order['order_code'];
$address = $order['delivery_address'];
$status = $order['order_status'];


// ถ้ามี transport_id ให้แสดง
$transport_id = '';

if (isset($order['transport_id'])) {
    $transport_id = $order['transport_id'];
}


// =========================
// STATUS
// =========================

$status_text = 'ไม่ทราบสถานะ';
$status_class = 'bg-gray-100 text-gray-600';

if ($status == 'Pending') {
    $status_text = '🕐 รอยืนยัน';
    $status_class = 'bg-yellow-100 text-yellow-700';
}

elseif ($status == 'Order Processing') {
    $status_text = '⚙️ กำลังดำเนินการ';
    $status_class = 'bg-blue-100 text-blue-700';
}

elseif ($status == 'Shipped') {
    $status_text = '🚚 จัดส่งแล้ว';
    $status_class = 'bg-purple-100 text-purple-700';
}

elseif ($status == 'Completed') {
    $status_text = '✅ เสร็จสิ้น';
    $status_class = 'bg-green-100 text-green-700';
}

elseif ($status == 'Cancelled') {
    $status_text = '❌ ยกเลิก';
    $status_class = 'bg-red-100 text-red-700';
}


// =========================
// GET PRODUCTS
// =========================

$item_sql = "
    SELECT
        oi.product_id,
        oi.quantity,
        oi.price,
        p.product_name,
        p.product_img
    FROM order_items oi
    JOIN products p
        ON oi.product_id = p.product_id
    WHERE oi.order_id = '$order_id'
";

$order_items_result = mysqli_query($conn, $item_sql);

$grand_total = 0;

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        รายละเอียดคำสั่งซื้อ | สานะกิมหยง
    </title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>

        body {
            background: #f6f1e8;
            font-family: Arial, sans-serif;
        }

        .card {
            background: white;
            border: 1px solid #eadfce;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(91,70,45,.07);
        }

        .header {
            background: linear-gradient(135deg,#fffaf2,#f3e5cf);
            border: 1px solid #eadfce;
            border-radius: 20px;
        }

        .product-img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 12px;
        }

    </style>

</head>


<body>

<?php include "nav_bar.php"; ?>


<div class="max-w-6xl mx-auto px-4 py-8">


    <!-- HEADER -->

    <div class="header p-6 mb-6">

        <p class="text-sm font-bold text-amber-700">
            ORDER DETAILS
        </p>

        <h1 class="text-2xl font-bold text-gray-800 mt-2">
            รายละเอียดคำสั่งซื้อ
        </h1>

        <p class="text-amber-700 font-bold mt-2">
            #<?= htmlspecialchars($order_code) ?>
        </p>

    </div>


    <!-- STATUS -->

    <div class="card p-6 mb-6">

        <h2 class="text-xl font-bold text-gray-800 mb-4">
            📦 สถานะคำสั่งซื้อ
        </h2>

        <div class="grid md:grid-cols-2 gap-4">


            <!-- STATUS -->

            <div class="bg-gray-50 rounded-xl p-5">

                <p class="text-sm text-gray-500 mb-2">
                    สถานะปัจจุบัน
                </p>

                <span
                    class="inline-block px-4 py-2 rounded-full font-bold <?= $status_class ?>"
                >

                    <?= $status_text ?>

                </span>

            </div>


            <!-- TRANSPORT -->

            <div class="bg-amber-50 rounded-xl p-5">

                <p class="text-sm text-gray-500 mb-2">
                    หมายเลขพัสดุ
                </p>


                <?php if ($transport_id != ''): ?>

                    <p class="text-lg font-bold text-gray-800">

                        🚚
                        <?= htmlspecialchars($transport_id) ?>

                    </p>

                    <p class="text-xs text-gray-500 mt-1">
                        หมายเลขสำหรับติดตามพัสดุ
                    </p>

                <?php else: ?>

                    <p class="text-gray-400">
                        ยังไม่มีหมายเลขพัสดุ
                    </p>

                <?php endif; ?>

            </div>

        </div>

    </div>


    <!-- ADDRESS -->

    <div class="card p-6 mb-6">

        <h2 class="text-xl font-bold text-gray-800 mb-3">
            📍 สถานที่จัดส่ง
        </h2>

        <div class="bg-gray-50 rounded-xl p-4 text-gray-700">

            <?= nl2br(htmlspecialchars($address)) ?>

        </div>

    </div>


    <!-- PRODUCTS -->

    <div class="card overflow-hidden">

        <div class="p-6 border-b">

            <h2 class="text-xl font-bold text-gray-800">
                🛍️ รายการสินค้า
            </h2>

        </div>


        <?php if (
            $order_items_result &&
            mysqli_num_rows($order_items_result) > 0
        ): ?>


            <div class="overflow-x-auto">

                <table class="w-full">

                    <thead class="bg-gray-50">

                        <tr>

                            <th class="p-4 text-left">
                                สินค้า
                            </th>

                            <th class="p-4 text-center">
                                จำนวน
                            </th>

                            <th class="p-4 text-right">
                                ราคาต่อชิ้น
                            </th>

                            <th class="p-4 text-right">
                                ราคารวม
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php while (
                        $item =
                        mysqli_fetch_assoc(
                            $order_items_result
                        )
                    ): ?>


                        <?php

                        $item_total =
                            $item['price']
                            * $item['quantity'];

                        $grand_total +=
                            $item_total;

                        ?>


                        <tr
                            class="
                            border-t
                            hover:bg-amber-50
                            "
                        >


                            <!-- PRODUCT -->

                            <td class="p-4">

                                <div
                                    class="
                                    flex
                                    items-center
                                    gap-3
                                    "
                                >

                                    <img
                                        src="<?= htmlspecialchars(
                                            $item['product_img']
                                        ) ?>"
                                        class="product-img"
                                        alt=""
                                    >

                                    <span
                                        class="font-medium"
                                    >

                                        <?= htmlspecialchars(
                                            $item['product_name']
                                        ) ?>

                                    </span>

                                </div>

                            </td>


                            <!-- QUANTITY -->

                            <td class="p-4 text-center">

                                <?= (int)$item['quantity'] ?>

                            </td>


                            <!-- PRICE -->

                            <td class="p-4 text-right">

                                <?= number_format(
                                    $item['price'],
                                    2
                                ) ?>

                                บาท

                            </td>


                            <!-- TOTAL -->

                            <td
                                class="
                                p-4
                                text-right
                                font-bold
                                text-amber-700
                                "
                            >

                                <?= number_format(
                                    $item_total,
                                    2
                                ) ?>

                                บาท

                            </td>


                        </tr>


                    <?php endwhile; ?>


                    </tbody>

                </table>

            </div>


            <!-- TOTAL -->

            <div class="p-6">

                <div
                    class="
                    bg-amber-50
                    rounded-xl
                    p-5
                    flex
                    justify-between
                    items-center
                    "
                >

                    <span class="font-bold text-gray-700">
                        ยอดรวมทั้งหมด
                    </span>

                    <span
                        class="
                        text-2xl
                        font-bold
                        text-amber-700
                        "
                    >

                        <?= number_format(
                            $grand_total,
                            2
                        ) ?>

                        บาท

                    </span>

                </div>

            </div>


        <?php else: ?>

            <div class="p-10 text-center">

                <div class="text-4xl mb-3">
                    📦
                </div>

                <p class="text-gray-500">
                    ไม่พบรายการสินค้าในคำสั่งซื้อนี้
                </p>

            </div>

        <?php endif; ?>


    </div>

</div>

</body>

</html>