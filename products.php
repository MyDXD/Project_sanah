<?php

// =====================================================
// START SESSION
// ต้องอยู่ก่อน HTML ทุกอย่าง
// =====================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// =====================================================
// DATABASE
// =====================================================

include 'dbconnect.php';


// =====================================================
// SEARCH DATA
// =====================================================

$search = isset($_POST['search'])
    ? trim($_POST['search'])
    : '';

$product_type = isset($_POST['product_type'])
    ? trim($_POST['product_type'])
    : '';


// =====================================================
// PRODUCT TYPE
// =====================================================

$type_query = "
    SELECT DISTINCT product_type
    FROM products
    WHERE is_active=1 AND product_type IS NOT NULL
    AND product_type != ''
    ORDER BY product_type ASC
";

$type_result = $conn->query($type_query);


// =====================================================
// PRODUCT QUERY
// =====================================================

$sql = "
    SELECT *
    FROM products
    WHERE is_active=1
";


// ค้นหาชื่อสินค้า
if ($search !== '') {

    $safeSearch = $conn->real_escape_string($search);

    $sql .= "
        AND product_name LIKE '%{$safeSearch}%'
    ";
}


// กรองประเภทสินค้า
if ($product_type !== '') {

    $safeType = $conn->real_escape_string($product_type);

    $sql .= "
        AND product_type = '{$safeType}'
    ";
}


// เรียงสินค้าล่าสุดก่อน
$sql .= "
    ORDER BY product_id DESC
";


$result = $conn->query($sql);


// =====================================================
// CHECK FILTER
// =====================================================

$hasFilter =
    ($search !== '') ||
    ($product_type !== '');

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>สินค้าทั้งหมด | สานะกิมหยง</title>

    <script src="https://cdn.tailwindcss.com"></script>


    <style>

        /* =====================================================
           BODY
        ===================================================== */

        body {

            background:
                linear-gradient(
                    180deg,
                    #fffaf2 0%,
                    #f6f1e8 100%
                );

            font-family:
                Arial,
                sans-serif;

            color:
                #4b3a2a;

        }


        /* =====================================================
           SHOP HEADER
        ===================================================== */

        .shop-header {

            background:
                linear-gradient(
                    135deg,
                    #fffdf8,
                    #f3e3cc
                );

            border:
                1px solid #eadbc6;

            border-radius:
                28px;

            position:
                relative;

            overflow:
                hidden;

            box-shadow:
                0 15px 40px
                rgba(91,70,45,.08);

        }


        .shop-header::before {

            content:
                "✦";

            position:
                absolute;

            right:
                35px;

            top:
                -35px;

            font-size:
                140px;

            color:
                rgba(171,113,47,.08);

        }


        .shop-header::after {

            content:
                "✦";

            position:
                absolute;

            left:
                30px;

            bottom:
                -50px;

            font-size:
                120px;

            color:
                rgba(171,113,47,.05);

        }


        /* =====================================================
           SEARCH PANEL
        ===================================================== */

        .search-panel {

            background:
                rgba(255,255,255,.88);

            border:
                1px solid #eadfce;

            border-radius:
                22px;

            box-shadow:
                0 10px 30px
                rgba(91,70,45,.07);

            backdrop-filter:
                blur(8px);

        }


        .search-input {

            border:
                1px solid #dfd2bf;

            background:
                #fffdf9;

            border-radius:
                14px;

            padding:
                13px 16px 13px 44px;

            outline:
                none;

            transition:
                .2s;

        }


        .search-input:focus {

            border-color:
                #b77a32;

            box-shadow:
                0 0 0 4px
                rgba(183,122,50,.12);

        }


        /* =====================================================
           TYPE SELECT
        ===================================================== */

        .type-select {

            border:
                1px solid #dfd2bf;

            background:
                #fffdf9;

            border-radius:
                14px;

            padding:
                13px 40px 13px 15px;

            outline:
                none;

            color:
                #5d4b38;

            cursor:
                pointer;

        }


        .type-select:focus {

            border-color:
                #b77a32;

            box-shadow:
                0 0 0 4px
                rgba(183,122,50,.12);

        }


        /* =====================================================
           SEARCH BUTTON
        ===================================================== */

        .search-button {

            background:
                #98601f;

            color:
                white;

            border-radius:
                14px;

            padding:
                13px 22px;

            font-weight:
                700;

            transition:
                .2s;

            box-shadow:
                0 6px 15px
                rgba(152,96,31,.20);

        }


        .search-button:hover {

            background:
                #754814;

            transform:
                translateY(-2px);

        }


        /* =====================================================
           RESET BUTTON
        ===================================================== */

        .reset-button {

            background:
                #f5eee4;

            color:
                #795b39;

            border:
                1px solid #e4d8c7;

            border-radius:
                14px;

            padding:
                13px 18px;

            font-weight:
                600;

            transition:
                .2s;

        }


        .reset-button:hover {

            background:
                #eee2d2;

        }


        /* =====================================================
           PRODUCT CARD
        ===================================================== */

        .product-card {

            background:
                white;

            border:
                1px solid #eadfce;

            border-radius:
                20px;

            overflow:
                hidden;

            position:
                relative;

            transition:
                all .3s ease;

            box-shadow:
                0 8px 25px
                rgba(91,70,45,.06);

        }


        .product-card:hover {

            transform:
                translateY(-7px);

            box-shadow:
                0 18px 40px
                rgba(91,70,45,.13);

            border-color:
                #d9c4a7;

        }


        /* =====================================================
           PRODUCT IMAGE
        ===================================================== */

        .product-image-wrapper {

            height:
                260px;

            background:
                linear-gradient(
                    135deg,
                    #faf5ed,
                    #f1e7d7
                );

            overflow:
                hidden;

            position:
                relative;

        }


        .product-image {

            width:
                100%;

            height:
                100%;

            object-fit:
                cover;

            transition:
                transform .45s ease;

        }


        .product-card:hover
        .product-image {

            transform:
                scale(1.07);

        }


        .image-overlay {

            position:
                absolute;

            inset:
                0;

            background:
                linear-gradient(
                    to top,
                    rgba(55,38,20,.28),
                    transparent 45%
                );

            opacity:
                0;

            transition:
                .3s;

        }


        .product-card:hover
        .image-overlay {

            opacity:
                1;

        }


        /* =====================================================
           PRODUCT CONTENT
        ===================================================== */

        .product-content {

            padding:
                18px;

        }


        .product-name {

            color:
                #493523;

            font-size:
                17px;

            font-weight:
                700;

            line-height:
                1.5;

            transition:
                .2s;

        }


        .product-card:hover
        .product-name {

            color:
                #98601f;

        }


        /* =====================================================
           PRICE
        ===================================================== */

        .product-price {

            color:
                #98601f;

            font-size:
                20px;

            font-weight:
                800;

        }


        /* =====================================================
           TYPE BADGE
        ===================================================== */

        .product-type {

            display:
                inline-flex;

            align-items:
                center;

            background:
                #fff4df;

            color:
                #94611f;

            border:
                1px solid #f0dfc2;

            padding:
                5px 10px;

            border-radius:
                999px;

            font-size:
                11px;

            font-weight:
                700;

        }


        /* =====================================================
           STOCK
        ===================================================== */

        .stock-good,
        .stock-low,
        .stock-out {

            padding:
                5px 9px;

            border-radius:
                999px;

            font-size:
                11px;

            font-weight:
                700;

        }


        .stock-good {

            color:
                #15803d;

            background:
                #ecfdf3;

        }


        .stock-low {

            color:
                #c2410c;

            background:
                #fff3e8;

        }


        .stock-out {

            color:
                #dc2626;

            background:
                #fee2e2;

        }


        /* =====================================================
           PRODUCT DETAIL
        ===================================================== */

        .product-detail {

            color:
                #8b7b68;

            font-size:
                13px;

            line-height:
                1.6;

            display:
                -webkit-box;

            -webkit-line-clamp:
                2;

            -webkit-box-orient:
                vertical;

            overflow:
                hidden;

            min-height:
                42px;

        }


        /* =====================================================
           VIEW PRODUCT
        ===================================================== */

        .view-product {

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            width:
                100%;

            background:
                #faf5ed;

            color:
                #79501f;

            border:
                1px solid #eadbc6;

            border-radius:
                12px;

            padding:
                11px;

            font-size:
                13px;

            font-weight:
                700;

            transition:
                .2s;

        }


        .view-product:hover {

            background:
                #98601f;

            color:
                white;

            border-color:
                #98601f;

        }


        /* =====================================================
           RESULT HEADER
        ===================================================== */

        .result-header {

            background:
                rgba(255,255,255,.7);

            border:
                1px solid #eadfce;

            border-radius:
                16px;

            padding:
                14px 18px;

        }


        /* =====================================================
           EMPTY
        ===================================================== */

        .empty-box {

            background:
                white;

            border:
                1px solid #eadfce;

            border-radius:
                24px;

            padding:
                70px 20px;

            text-align:
                center;

            box-shadow:
                0 10px 30px
                rgba(91,70,45,.06);

        }


        /* =====================================================
           ANIMATION
        ===================================================== */

        .fade-in {

            animation:
                fadeIn .5s ease;

        }


        @keyframes fadeIn {

            from {

                opacity:
                    0;

                transform:
                    translateY(15px);

            }

            to {

                opacity:
                    1;

                transform:
                    translateY(0);

            }

        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 640px) {

            .product-image-wrapper {

                height:
                    190px;

            }

            .product-content {

                padding:
                    14px;

            }

            .product-name {

                font-size:
                    15px;

            }

            .product-price {

                font-size:
                    17px;

            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<?php include 'nav_bar.php'; ?>


<div class="container mx-auto px-4 py-6 md:py-10">


<!-- =====================================================
     HEADER
===================================================== -->

<div class="shop-header px-6 py-10 md:px-12 md:py-14 mb-7">

    <div class="relative z-10 text-center">

        <div class="text-4xl mb-3">
            🛍️
        </div>

        <p class="text-sm font-bold tracking-widest text-yellow-700 mb-2">
            SANA KIMYONG
        </p>

        <h1 class="text-3xl md:text-4xl font-bold text-gray-800">
            สินค้าทั้งหมด
        </h1>

        <p class="text-gray-500 mt-3 max-w-2xl mx-auto">
            เลือกชมสินค้าจากร้านสานะกิมหยง
            ค้นหาสินค้าที่คุณต้องการได้ง่าย ๆ
        </p>

    </div>

</div>


<!-- =====================================================
     SEARCH
===================================================== -->

<div class="search-panel p-4 md:p-5 mb-7">

    <form
        action=""
        method="POST"
        class="flex flex-col lg:flex-row gap-3">


        <!-- SEARCH INPUT -->

        <div class="relative flex-1">

            <span
                class="absolute left-4 top-1/2
                transform -translate-y-1/2
                text-gray-400">

                🔎

            </span>

            <input
                type="text"
                name="search"
                value="<?= htmlspecialchars($search) ?>"
                placeholder="ค้นหาชื่อสินค้า..."
                class="search-input w-full">

        </div>


        <!-- TYPE -->

        <select
            name="product_type"
            class="type-select">

            <option value="">
                ทุกประเภท
            </option>


            <?php

            if ($type_result && $type_result->num_rows > 0) {

                while (
                    $type_row =
                    $type_result->fetch_assoc()
                ) {

                    $typeValue =
                        $type_row['product_type'];

                    $type =
                        htmlspecialchars(
                            $typeValue
                        );

                    $selected =
                        ($product_type === $typeValue)
                        ? 'selected'
                        : '';

                    ?>

                    <option
                        value="<?= $type ?>"
                        <?= $selected ?>>

                        <?= $type ?>

                    </option>

                    <?php

                }

            }

            ?>

        </select>


        <!-- SEARCH -->

        <button
            type="submit"
            class="search-button">

            🔎 ค้นหา

        </button>


        <!-- RESET -->

        <?php if ($hasFilter): ?>

            <a
                href="<?= htmlspecialchars(
                    $_SERVER['PHP_SELF']
                ) ?>"
                class="reset-button
                inline-flex
                items-center
                justify-center">

                ✕ ล้าง

            </a>

        <?php endif; ?>


    </form>

</div>


<!-- =====================================================
     RESULT HEADER
===================================================== -->

<div class="result-header mb-6">

    <div
        class="flex flex-col sm:flex-row
        sm:items-center
        sm:justify-between
        gap-2">


        <div>

            <span class="text-sm text-gray-500">
                รายการสินค้า
            </span>


            <?php if ($search !== ''): ?>

                <span class="text-sm font-bold text-gray-800">

                    · ค้นหา
                    "<?= htmlspecialchars($search) ?>"

                </span>

            <?php endif; ?>


            <?php if ($product_type !== ''): ?>

                <span class="text-sm font-bold text-yellow-700">

                    ·
                    <?= htmlspecialchars($product_type) ?>

                </span>

            <?php endif; ?>

        </div>


        <?php if ($result): ?>

            <div
                class="text-sm font-semibold text-gray-500">

                พบ

                <span class="text-yellow-700">

                    <?= number_format(
                        $result->num_rows
                    ) ?>

                </span>

                รายการ

            </div>

        <?php endif; ?>


    </div>

</div>


<!-- =====================================================
     PRODUCTS
===================================================== -->

<?php if ($result && $result->num_rows > 0): ?>


<div
    class="grid
    grid-cols-1
    sm:grid-cols-2
    lg:grid-cols-3
    xl:grid-cols-4
    gap-5
    md:gap-6
    fade-in">


<?php while ($row = $result->fetch_assoc()): ?>


<?php

$id =
    $row['product_id'];

$stock =
    (int)$row['stock'];

?>


<!-- =====================================================
     PRODUCT CARD
===================================================== -->

<div class="product-card">


<!-- =====================================================
     IMAGE
===================================================== -->

<a
    href="product_content.php?id=<?= urlencode($id) ?>"
    class="block">


    <div class="product-image-wrapper">


        <?php if (!empty($row['product_img'])): ?>

            <img
                src="<?= htmlspecialchars(
                    $row['product_img']
                ) ?>"
                alt="<?= htmlspecialchars(
                    $row['product_name']
                ) ?>"
                class="product-image">

        <?php else: ?>

            <div
                class="w-full h-full
                flex items-center
                justify-center
                text-gray-400">

                <div class="text-center">

                    <div class="text-5xl mb-2">
                        📦
                    </div>

                    <p class="text-sm">
                        ไม่มีรูปสินค้า
                    </p>

                </div>

            </div>

        <?php endif; ?>


        <div class="image-overlay"></div>


    </div>

</a>


<!-- =====================================================
     CONTENT
===================================================== -->

<div class="product-content">


<!-- TYPE + STOCK -->

<div
    class="flex
    items-center
    justify-between
    gap-2
    mb-3">


    <span class="product-type">

        <?= htmlspecialchars(
            $row['product_type']
        ) ?>

    </span>


    <?php if ($stock <= 0): ?>

        <span class="stock-out">
            🚨 หมด
        </span>

    <?php elseif ($stock <= 5): ?>

        <span class="stock-low">

            ⚠
            เหลือ
            <?= number_format($stock) ?>

        </span>

    <?php else: ?>

        <span class="stock-good">

            ✓ มีสินค้า

        </span>

    <?php endif; ?>


</div>


<!-- NAME -->

<h3 class="product-name mb-2">

    <?= htmlspecialchars(
        $row['product_name']
    ) ?>

</h3>


<!-- PRICE -->

<div class="product-price mb-2">

    ฿<?= number_format(
        (float)$row['product_price'],
        2
    ) ?>

</div>


<!-- STOCK -->

<p class="text-xs text-gray-400 mb-3">

    คงเหลือ

    <span class="font-semibold text-gray-600">

        <?= number_format($stock) ?>

    </span>

    ชิ้น

</p>


<!-- DETAIL -->

<p class="product-detail mb-4">

    <?= htmlspecialchars(
        $row['product_detail']
    ) ?>

</p>


<!-- VIEW BUTTON -->

<a
    href="product_content.php?id=<?= urlencode($id) ?>"
    class="view-product">

    ดูรายละเอียดสินค้า

    <span class="ml-2">
        →
    </span>

</a>


</div>


</div>


<?php endwhile; ?>


</div>


<?php else: ?>


<!-- =====================================================
     EMPTY
===================================================== -->

<div class="empty-box">


    <div class="text-6xl mb-5">
        🛍️
    </div>


    <h3
        class="text-xl
        font-bold
        text-gray-700">

        ไม่พบสินค้าที่ค้นหา

    </h3>


    <p
        class="text-gray-400
        text-sm
        mt-2">

        ลองเปลี่ยนชื่อสินค้า
        หรือเลือกประเภทสินค้าอื่น

    </p>


    <?php if ($hasFilter): ?>

        <a
            href="<?= htmlspecialchars(
                $_SERVER['PHP_SELF']
            ) ?>"
            class="inline-flex
            mt-5
            px-5
            py-3
            rounded-xl
            bg-yellow-700
            text-white
            text-sm
            font-semibold
            hover:bg-yellow-800">

            ดูสินค้าทั้งหมด

        </a>

    <?php endif; ?>


</div>


<?php endif; ?>


</div>


</body>

</html>