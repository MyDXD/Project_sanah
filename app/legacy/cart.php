<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("dbconnect.php");

date_default_timezone_set('Asia/Bangkok');

/* =========================
   ตรวจสอบ Login
========================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: login_form.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);


/* =========================
   ลบสินค้า
========================= */

if (isset($_GET['remove_product_id'])) {

    $product_id = intval($_GET['remove_product_id']);

    $delete_sql = "
        DELETE FROM cart
        WHERE product_id = $product_id
        AND user_id = $user_id
    ";

    mysqli_query($conn, $delete_sql);

    header("Location: cart.php");
    exit();
}


/* =========================
   ดึงที่อยู่
========================= */

$delivery_address = "ไม่พบที่อยู่การจัดส่ง";

$address_sql = "
    SELECT address
    FROM userdata
    WHERE user_id = $user_id
    LIMIT 1
";

$address_result = mysqli_query($conn, $address_sql);

if ($address_result && mysqli_num_rows($address_result) > 0) {

    $address_row = mysqli_fetch_assoc($address_result);

    if (!empty($address_row['address'])) {
        $delivery_address = $address_row['address'];
    }
}


/* =========================
   ดึงสินค้าในตะกร้า
========================= */

$cart_items = [];

$cart_sql = "
    SELECT
        cart.product_id,
        cart.quantity,
        products.product_name,
        products.product_price,
        products.product_img,
        products.stock
    FROM cart
    JOIN products
        ON cart.product_id = products.product_id
    WHERE cart.user_id = $user_id
    ORDER BY cart.product_id DESC
";

$cart_result = mysqli_query($conn, $cart_sql);

if ($cart_result) {

    while ($row = mysqli_fetch_assoc($cart_result)) {

        $cart_items[] = $row;

    }

}


/* =========================
   ยืนยันคำสั่งซื้อ
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['checkout'])) {

    if (count($cart_items) === 0) {

        header("Location: cart.php");
        exit();

    }


    $order_date = date('Y-m-d H:i:s');

    $order_code =
        'ORDER_' .
        date('YmdHis') .
        mt_rand(100, 999);

    $order_status = 'Order Placed';

    $total_price = 0;

    $order_items = [];

    $stock_error = false;


    /* =========================
       ตรวจสอบจำนวนสินค้า
    ========================= */

    foreach ($cart_items as $item) {

        $product_id = intval($item['product_id']);

        $quantity = isset(
            $_POST['quantity_' . $product_id]
        )
            ? intval($_POST['quantity_' . $product_id])
            : intval($item['quantity']);

        if ($quantity < 1) {
            $quantity = 1;
        }

        $stock = intval($item['stock']);

        if ($quantity > $stock) {

            $stock_error = true;

            break;
        }


        $price = floatval($item['product_price']);

        $subtotal = $price * $quantity;

        $total_price += $subtotal;


        $order_items[] = [
            'product_id' => $product_id,
            'quantity'   => $quantity,
            'price'      => $price
        ];
    }


    /* =========================
       ถ้าสต็อกไม่พอ
    ========================= */

    if ($stock_error) {

        echo '
        <!DOCTYPE html>
        <html lang="th">
        <head>
            <meta charset="UTF-8">
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        </head>

        <body>

        <script>

        Swal.fire({
            icon: "warning",
            title: "สินค้าไม่เพียงพอ",
            text: "จำนวนสินค้าที่เลือกมากกว่าสินค้าคงเหลือ",
            confirmButtonText: "ตกลง",
            confirmButtonColor: "#9a641f"
        }).then(function() {

            window.location.href = "cart.php";

        });

        </script>

        </body>
        </html>
        ';

        exit();
    }


    /* =========================
       สร้างคำสั่งซื้อ
    ========================= */

    $delivery_address_db =
        mysqli_real_escape_string(
            $conn,
            $delivery_address
        );


    $order_sql = "
        INSERT INTO orders
        (
            order_code,
            user_id,
            total_price,
            order_status,
            order_date,
            delivery_address,
            transport_id
        )
        VALUES
        (
            '$order_code',
            $user_id,
            $total_price,
            '$order_status',
            '$order_date',
            '$delivery_address_db',
            ''
        )
    ";


    if (!mysqli_query($conn, $order_sql)) {

        die(
            "เกิดข้อผิดพลาดในการสร้างคำสั่งซื้อ: "
            . mysqli_error($conn)
        );

    }


    $order_id = mysqli_insert_id($conn);


    /* =========================
       เพิ่มรายการสินค้า
       + ลด Stock
    ========================= */

    foreach ($order_items as $item) {

        $product_id = $item['product_id'];

        $quantity = $item['quantity'];

        $price = $item['price'];


        $item_sql = "
            INSERT INTO order_items
            (
                order_id,
                product_id,
                quantity,
                price
            )
            VALUES
            (
                $order_id,
                $product_id,
                $quantity,
                $price
            )
        ";


        if (!mysqli_query($conn, $item_sql)) {

            die(
                "เกิดข้อผิดพลาดในการบันทึกรายการสินค้า: "
                . mysqli_error($conn)
            );

        }


        /* ลดสินค้าใน Stock */

        $stock_sql = "
            UPDATE products
            SET stock = stock - $quantity
            WHERE product_id = $product_id
        ";

        mysqli_query($conn, $stock_sql);
    }


    /* =========================
       ล้างตะกร้า
    ========================= */

    $clear_cart_sql = "
        DELETE FROM cart
        WHERE user_id = $user_id
    ";

    mysqli_query($conn, $clear_cart_sql);


    /* =========================
       สำเร็จ
    ========================= */

    echo '
    <!DOCTYPE html>
    <html lang="th">

    <head>

        <meta charset="UTF-8">

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    </head>

    <body>

    <script>

    Swal.fire({

        icon: "success",

        title: "สั่งซื้อสำเร็จ!",

        text: "สร้างคำสั่งซื้อเรียบร้อยแล้ว",

        confirmButtonText: "ดูคำสั่งซื้อ",

        confirmButtonColor: "#9a641f"

    }).then(function() {

        window.location.href = "order.php";

    });

    </script>

    </body>

    </html>
    ';

    exit();
}


/* =========================
   Navbar
========================= */

include "nav_bar.php";

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>ตะกร้าสินค้า | สานะกิมหยง</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>

        body {
            background: #f6f1e8;
        }

        .brown {
            color: #9a641f;
        }

        .bg-brown {
            background: #9a641f;
        }

        .cart-card {
            transition: .2s;
        }

        .cart-card:hover {
            box-shadow: 0 10px 25px rgba(91,70,45,.08);
        }

    </style>

</head>


<body>


<div class="max-w-7xl mx-auto px-4 py-10">


    <!-- =========================
         HEADER
    ========================= -->

    <div class="mb-8">

        <p class="text-sm font-semibold brown">
            SHOPPING CART
        </p>

        <h1 class="text-3xl font-bold text-gray-800 mt-1">
            🛒 ตะกร้าสินค้า
        </h1>

        <p class="text-gray-500 mt-2">
            ตรวจสอบสินค้าและจำนวนก่อนยืนยันคำสั่งซื้อ
        </p>

    </div>


    <?php if (count($cart_items) > 0): ?>


    <form method="POST">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">


            <!-- =========================
                 PRODUCT LIST
            ========================= -->

            <div class="lg:col-span-2 space-y-4">


                <?php foreach ($cart_items as $item): ?>

                    <?php

                    $product_id =
                        intval($item['product_id']);

                    $quantity =
                        intval($item['quantity']);

                    if ($quantity < 1) {
                        $quantity = 1;
                    }

                    $price =
                        floatval($item['product_price']);

                    $stock =
                        intval($item['stock']);

                    ?>

                    <div
                        class="cart-card bg-white rounded-2xl border border-[#eadfce] p-5">

                        <div class="flex gap-5">


                            <!-- รูปสินค้า -->

                            <div
                                class="w-28 h-28 sm:w-36 sm:h-36 bg-[#f6f1e8] rounded-xl overflow-hidden flex-shrink-0">

                                <?php if (!empty($item['product_img'])): ?>

                                    <img
                                        src="<?= htmlspecialchars($item['product_img']) ?>"
                                        alt="<?= htmlspecialchars($item['product_name']) ?>"
                                        class="w-full h-full object-cover">

                                <?php else: ?>

                                    <div
                                        class="w-full h-full flex items-center justify-center text-4xl">

                                        📦

                                    </div>

                                <?php endif; ?>

                            </div>


                            <!-- ข้อมูล -->

                            <div class="flex-1">


                                <div class="flex justify-between gap-3">

                                    <div>

                                        <h2
                                            class="font-bold text-gray-800 text-lg">

                                            <?= htmlspecialchars(
                                                $item['product_name']
                                            ) ?>

                                        </h2>

                                        <p
                                            class="text-sm text-gray-500 mt-1">

                                            ฿<?= number_format(
                                                $price,
                                                2
                                            ) ?>
                                            / ชิ้น

                                        </p>

                                    </div>


                                    <!-- ลบ -->

                                    <button
                                        type="button"
                                        onclick="confirmRemove(<?= $product_id ?>)"
                                        class="text-red-500 hover:text-red-700 text-sm">

                                        🗑️ ลบ

                                    </button>

                                </div>


                                <div
                                    class="flex flex-wrap items-center justify-between gap-4 mt-6">


                                    <!-- จำนวน -->

                                    <div>

                                        <p
                                            class="text-xs text-gray-400 mb-2">

                                            จำนวนสินค้า

                                        </p>

                                        <input
                                            type="number"
                                            name="quantity_<?= $product_id ?>"
                                            value="<?= $quantity ?>"
                                            min="1"
                                            max="<?= $stock ?>"
                                            data-price="<?= $price ?>"
                                            class="quantity-input w-20 text-center border border-[#eadfce] rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#eadfce]">

                                    </div>


                                    <!-- Stock -->

                                    <div>

                                        <p
                                            class="text-xs text-gray-400">

                                            สินค้าคงเหลือ

                                        </p>

                                        <p
                                            class="text-sm font-semibold text-gray-600 mt-1">

                                            <?= $stock ?> ชิ้น

                                        </p>

                                    </div>


                                    <!-- ราคา -->

                                    <div class="text-right">

                                        <p
                                            class="text-xs text-gray-400">

                                            ราคารวม

                                        </p>

                                        <p
                                            class="item-subtotal text-lg font-bold brown mt-1">

                                            ฿<?= number_format(
                                                $price * $quantity,
                                                2
                                            ) ?>

                                        </p>

                                    </div>


                                </div>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>


            </div>


            <!-- =========================
                 SUMMARY
            ========================= -->

            <div class="lg:col-span-1">


                <div
                    class="bg-white rounded-2xl border border-[#eadfce] p-6 sticky top-5">


                    <h2
                        class="text-xl font-bold text-gray-800">

                        สรุปคำสั่งซื้อ

                    </h2>


                    <div class="border-t border-[#eadfce] mt-5 pt-5">


                        <div
                            class="flex justify-between items-center">

                            <span
                                class="text-gray-500">

                                ราคารวม

                            </span>

                            <span
                                id="order-total"
                                class="text-2xl font-bold brown">

                                ฿0.00

                            </span>

                        </div>


                    </div>


                    <!-- ที่อยู่ -->

                    <div
                        class="border-t border-[#eadfce] mt-5 pt-5">

                        <p
                            class="text-sm font-bold text-gray-700 mb-2">

                            📍 ที่อยู่จัดส่ง

                        </p>

                        <div
                            class="bg-[#f6f1e8] rounded-xl p-4 text-sm text-gray-600">

                            <?= nl2br(
                                htmlspecialchars(
                                    $delivery_address
                                )
                            ) ?>

                        </div>

                    </div>


                    <!-- Hidden total -->

                    <input
                        type="hidden"
                        name="total_price"
                        id="hidden-total"
                        value="0">


                    <!-- ปุ่ม -->

                    <button
                        type="submit"
                        name="checkout"
                        class="w-full mt-6 bg-[#9a641f] hover:bg-[#7f5018] text-white rounded-xl py-3.5 font-bold transition">

                        ✓ ยืนยันคำสั่งซื้อ

                    </button>


                    <a
                        href="index.php"
                        class="block text-center mt-4 text-sm text-gray-500 hover:text-[#9a641f]">

                        ← เลือกซื้อสินค้าต่อ

                    </a>


                </div>

            </div>


        </div>

    </form>


    <?php else: ?>


        <!-- =========================
             EMPTY CART
        ========================= -->

        <div
            class="bg-white rounded-3xl border border-[#eadfce] p-12 text-center">


            <div class="text-7xl mb-5">
                🛒
            </div>


            <h2
                class="text-2xl font-bold text-gray-800">

                ตะกร้าสินค้าว่าง

            </h2>


            <p
                class="text-gray-500 mt-2">

                ยังไม่มีสินค้าในตะกร้าของคุณ

            </p>


            <a
                href="index.php"
                class="inline-block mt-6 bg-[#9a641f] hover:bg-[#7f5018] text-white px-7 py-3 rounded-xl font-semibold">

                🛍️ เลือกซื้อสินค้า

            </a>


        </div>


    <?php endif; ?>


</div>


<script>

/* =========================
   คำนวณราคา
========================= */

function calculateTotal() {

    let total = 0;

    document
        .querySelectorAll('.quantity-input')
        .forEach(function(input) {

            let quantity =
                parseInt(input.value) || 1;

            let max =
                parseInt(input.max);

            let price =
                parseFloat(input.dataset.price) || 0;


            if (quantity < 1) {

                quantity = 1;

                input.value = 1;

            }


            if (max > 0 && quantity > max) {

                quantity = max;

                input.value = max;

            }


            let subtotal =
                quantity * price;


            total += subtotal;


            /* แสดงราคาสินค้าแต่ละรายการ */

            let card =
                input.closest('.cart-card');

            if (card) {

                let subtotalElement =
                    card.querySelector('.item-subtotal');

                if (subtotalElement) {

                    subtotalElement.textContent =
                        '฿' +
                        subtotal.toLocaleString(
                            'th-TH',
                            {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            }
                        );

                }

            }

        });


    document
        .getElementById('order-total')
        .textContent =
        '฿' +
        total.toLocaleString(
            'th-TH',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );


    document
        .getElementById('hidden-total')
        .value =
        total.toFixed(2);

}


/* =========================
   เปลี่ยนจำนวน
========================= */

document
    .querySelectorAll('.quantity-input')
    .forEach(function(input) {

        input.addEventListener(
            'input',
            calculateTotal
        );

    });


/* =========================
   ลบสินค้า
========================= */

function confirmRemove(productId) {

    Swal.fire({

        title: 'ลบสินค้าออกจากตะกร้า?',

        text: 'สินค้ารายการนี้จะถูกนำออกจากตะกร้า',

        icon: 'warning',

        showCancelButton: true,

        confirmButtonColor: '#dc2626',

        cancelButtonColor: '#9a641f',

        confirmButtonText: 'ลบสินค้า',

        cancelButtonText: 'ยกเลิก'

    }).then(function(result) {

        if (result.isConfirmed) {

            window.location.href =
                'cart.php?remove_product_id='
                + productId;

        }

    });

}


/* =========================
   โหลดครั้งแรก
========================= */

window.addEventListener(
    'load',
    calculateTotal
);

</script>


</body>
</html>
