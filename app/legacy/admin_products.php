<?php
include "../dbconnect.php";

session_start();

/* =========================
   ตรวจสอบสิทธิ์ Admin
========================= */

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login_form.php");
    exit();
}


/* =========================
   รับค่าค้นหา
========================= */

$search = isset($_GET['search']) ? $_GET['search'] : '';
$productType = isset($_GET['product_type']) ? $_GET['product_type'] : '';


/* =========================
   ดึงประเภทสินค้า
========================= */

$typeResult = $conn->query("
    SELECT DISTINCT product_type
    FROM products
    WHERE product_type IS NOT NULL
    AND product_type != ''
    ORDER BY product_type ASC
");


/* =========================
   ค้นหา + กรองประเภท
========================= */

$searchSafe = $conn->real_escape_string($search);

$sql = "
    SELECT *
    FROM products
    WHERE product_name LIKE '%$searchSafe%'
";

if ($productType !== '') {

    $productTypeSafe = $conn->real_escape_string($productType);

    $sql .= "
        AND product_type = '$productTypeSafe'
    ";
}

$sql .= " ORDER BY product_id DESC";

$result = $conn->query($sql);


/* =========================
   นับข้อมูลสินค้า
========================= */

$totalProducts = 0;
$totalStock = 0;
$lowStock = 0;

$countResult = $conn->query("SELECT * FROM products");

if ($countResult) {

    while ($product = $countResult->fetch_assoc()) {

        $totalProducts++;

        $totalStock += (int)$product['stock'];

        if ((int)$product['stock'] <= 5) {
            $lowStock++;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>คลังสินค้า | สานะกิมหยง</title>

    <link
        href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css"
        rel="stylesheet">

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
            padding: 28px 30px;
            margin-bottom: 22px;
            position: relative;
            overflow: hidden;

            box-shadow:
                0 10px 30px rgba(91,70,45,.08);
        }

        .page-header::after {
            content: "✦";

            position: absolute;
            right: 35px;
            top: -10px;

            font-size: 90px;

            color: rgba(180,120,50,.08);
        }

        .stat-card {
            background: white;

            border: 1px solid #eee5d8;

            border-radius: 20px;

            padding: 20px;

            box-shadow:
                0 8px 25px rgba(91,70,45,.07);

            transition: .25s;

            position: relative;

            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);

            box-shadow:
                0 15px 35px rgba(91,70,45,.12);
        }

        .stat-icon {
            width: 50px;
            height: 50px;

            border-radius: 15px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 23px;

            background: #fff3d9;
        }

        .search-box {
            background: white;

            border: 1px solid #e5d9c8;

            border-radius: 14px;

            padding: 12px 16px 12px 42px;

            outline: none;

            transition: .2s;
        }

        .search-box:focus {
            border-color: #c28a43;

            box-shadow:
                0 0 0 4px rgba(194,138,67,.12);
        }

        .add-product {
            background: #9a641f;

            color: white;

            padding: 12px 18px;

            border-radius: 14px;

            font-size: 14px;

            font-weight: 700;

            transition: .2s;

            box-shadow:
                0 5px 15px rgba(154,100,31,.20);
        }

        .add-product:hover {
            background: #7f5018;

            transform: translateY(-2px);
        }

        .table-card {
            background: white;

            border: 1px solid #eadfce;

            border-radius: 24px;

            overflow: hidden;

            box-shadow:
                0 10px 30px rgba(91,70,45,.07);
        }

        .table-header {
            background: #faf7f1;
        }

        .table-header th {
            padding: 16px;

            font-size: 12px;

            color: #7c7062;

            font-weight: 700;

            white-space: nowrap;
        }

        .product-row {
            transition: .2s;
        }

        .product-row:hover {
            background: #fffaf2;

            box-shadow:
                inset 4px 0 0 #c28a43;
        }

        .product-row td {
            padding: 15px 16px;

            border-bottom: 1px solid #f0ebe3;

            vertical-align: middle;
        }

        .product-image-box {
            width: 70px;
            height: 70px;

            border-radius: 14px;

            background: #faf5ec;

            border: 1px solid #eee3d3;

            display: flex;

            align-items: center;

            justify-content: center;

            overflow: hidden;
        }

        .product-image {
            width: 100%;
            height: 100%;

            object-fit: cover;

            transition: .25s;
        }

        .product-row:hover .product-image {
            transform: scale(1.06);
        }

        .price {
            color: #9a641f;

            font-weight: 700;

            font-size: 15px;
        }

        .stock-good {
            display: inline-block;

            background: #ecfdf3;

            color: #15803d;

            padding: 6px 11px;

            border-radius: 999px;

            font-size: 12px;

            font-weight: 700;
        }

        .stock-low {
            display: inline-block;

            background: #fff3e8;

            color: #c2410c;

            padding: 6px 11px;

            border-radius: 999px;

            font-size: 12px;

            font-weight: 700;
        }

        .type-badge {
            display: inline-block;

            background: #fff5df;

            color: #94611f;

            padding: 6px 11px;

            border-radius: 999px;

            font-size: 12px;

            font-weight: 600;
        }

        .edit-btn {
            background: #edf4ff;

            color: #2563eb;

            padding: 8px 12px;

            border-radius: 10px;

            font-size: 12px;

            font-weight: 700;

            transition: .2s;
        }

        .edit-btn:hover {
            background: #2563eb;
            color: white;
        }

        .delete-btn {
            background: #fff0f0;

            color: #dc2626;

            padding: 8px 12px;

            border-radius: 10px;

            font-size: 12px;

            font-weight: 700;

            transition: .2s;
        }

        .delete-btn:hover {
            background: #dc2626;
            color: white;
        }

        .empty-box {
            padding: 60px 20px;

            text-align: center;
        }

        .fade-in {
            animation: fadeIn .5s ease;
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

        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #eee8de;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbbda9;
            border-radius: 10px;
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
                        คลังสินค้า
                    </h1>

                    <p class="text-gray-500 mt-2">
                        จัดการสินค้า ราคา และจำนวนสินค้าภายในร้าน
                    </p>

                </div>

            </div>


            <!-- STAT -->

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">


                <div class="stat-card">

                    <div class="flex items-center justify-between">

                        <div>

                            <p class="text-sm text-gray-500">
                                สินค้าทั้งหมด
                            </p>

                            <h2 class="text-3xl font-bold text-gray-800 mt-2">
                                <?= number_format($totalProducts) ?>
                            </h2>

                            <p class="text-xs text-gray-400 mt-2">
                                รายการสินค้า
                            </p>

                        </div>

                        <div class="stat-icon">
                            📦
                        </div>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="flex items-center justify-between">

                        <div>

                            <p class="text-sm text-gray-500">
                                สินค้าในคลัง
                            </p>

                            <h2 class="text-3xl font-bold text-gray-800 mt-2">
                                <?= number_format($totalStock) ?>
                            </h2>

                            <p class="text-xs text-gray-400 mt-2">
                                ชิ้นทั้งหมด
                            </p>

                        </div>

                        <div class="stat-icon">
                            🏪
                        </div>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="flex items-center justify-between">

                        <div>

                            <p class="text-sm text-gray-500">
                                สินค้าใกล้หมด
                            </p>

                            <h2 class="text-3xl font-bold text-gray-800 mt-2">
                                <?= number_format($lowStock) ?>
                            </h2>

                            <p class="text-xs text-orange-600 mt-2 font-medium">
                                ⚠ เหลือไม่เกิน 5 ชิ้น
                            </p>

                        </div>

                        <div class="stat-icon">
                            ⚠️
                        </div>

                    </div>

                </div>

            </div>


            <!-- SEARCH -->

            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-5">


                <form
                    action=""
                    method="GET"
                    class="flex flex-col md:flex-row gap-3 w-full lg:w-auto">


                    <div class="relative w-full md:w-80">

                        <span
                            class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400">
                            🔎
                        </span>

                        <input
                            type="text"
                            name="search"
                            value="<?= htmlspecialchars($search) ?>"
                            placeholder="ค้นหาชื่อสินค้า..."
                            class="search-box w-full">

                    </div>


                    <select
                        name="product_type"
                        onchange="this.form.submit()"
                        class="search-box w-full md:w-64">

                        <option value="">
                            ทุกประเภทสินค้า
                        </option>

                        <?php if ($typeResult): ?>

                            <?php while ($type = $typeResult->fetch_assoc()): ?>

                                <option
                                    value="<?= htmlspecialchars($type['product_type']) ?>"
                                    <?= ($productType === $type['product_type']) ? 'selected' : '' ?>>

                                    <?= htmlspecialchars($type['product_type']) ?>

                                </option>

                            <?php endwhile; ?>

                        <?php endif; ?>

                    </select>


                    <button
                        type="submit"
                        class="add-product inline-flex items-center justify-center">

                        🔎 ค้นหา

                    </button>

                </form>


                <a
                    href="create_product.php"
                    class="add-product inline-flex items-center justify-center">

                    <span class="mr-2 text-lg">
                        ＋
                    </span>

                    เพิ่มสินค้า

                </a>

            </div>


            <!-- TABLE -->

            <div class="table-card">

                <div class="px-6 py-5 border-b border-gray-100 bg-white">

                    <div class="flex items-center justify-between">

                        <div>

                            <h2 class="text-xl font-bold text-gray-800">
                                รายการสินค้า
                            </h2>

                            <p class="text-sm text-gray-400 mt-1">
                                จัดการข้อมูลสินค้าในคลัง
                            </p>

                        </div>

                        <div class="text-2xl">
                            🛍️
                        </div>

                    </div>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-full">

                        <thead class="table-header">

                            <tr>

                                <th class="text-center">
                                    รูปภาพ
                                </th>

                                <th class="text-left">
                                    ชื่อสินค้า
                                </th>

                                <th class="text-center">
                                    ราคา
                                </th>

                                <th class="text-center">
                                    จำนวน
                                </th>

                                <th class="text-center">
                                    ประเภท
                                </th>

                                <th class="text-left">
                                    รายละเอียด
                                </th>

                                <th class="text-center">
                                    จัดการ
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php if ($result && $result->num_rows > 0): ?>

                            <?php while ($row = $result->fetch_assoc()): ?>

                                <tr class="product-row">


                                    <!-- รูป -->

                                    <td>

                                        <div class="flex justify-center">

                                            <div class="product-image-box">

                                                <?php if (!empty($row['product_img'])): ?>

                                                    <img
                                                        src="../<?= htmlspecialchars($row['product_img']) ?>"
                                                        class="product-image"
                                                        alt="Product">

                                                <?php else: ?>

                                                    <span class="text-gray-400 text-xs">
                                                        ไม่มีรูป
                                                    </span>

                                                <?php endif; ?>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- ชื่อ -->

                                    <td>

                                        <div class="font-semibold text-gray-800">

                                            <?= htmlspecialchars(
                                                $row['product_name']
                                            ) ?>

                                        </div>

                                    </td>


                                    <!-- ราคา -->

                                    <td class="text-center">

                                        <span class="price">

                                            ฿<?= number_format(
                                                (float)$row['product_price'],
                                                2
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- จำนวน -->

                                    <td class="text-center">

                                        <?php if ((int)$row['stock'] <= 5): ?>

                                            <span class="stock-low">

                                                ⚠
                                                <?= number_format(
                                                    (int)$row['stock']
                                                ) ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="stock-good">

                                                <?= number_format(
                                                    (int)$row['stock']
                                                ) ?>

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- ประเภท -->

                                    <td class="text-center">

                                        <span class="type-badge">

                                            <?= htmlspecialchars(
                                                $row['product_type']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- รายละเอียด -->

                                    <td>

                                        <div class="text-sm text-gray-500 max-w-xs">

                                            <?= htmlspecialchars(
                                                $row['product_detail']
                                            ) ?>

                                        </div>

                                    </td>


                                    <!-- จัดการ -->

                                    <td>

                                        <div class="flex items-center justify-center gap-2">


                                            <button
                                                class="edit-btn"
                                                onclick="window.location.href='edit_product.php?id=<?= (int)$row['product_id'] ?>'">

                                                ✏️ แก้ไข

                                            </button>


                                            <button
                                                class="delete-btn"
                                                onclick="confirmDelete(<?= (int)$row['product_id'] ?>)">

                                                🗑️ ลบ

                                            </button>


                                        </div>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="7"
                                    class="empty-box">

                                    <div class="text-5xl mb-4">
                                        📦
                                    </div>

                                    <p class="text-gray-600 font-semibold">
                                        ไม่พบสินค้า
                                    </p>

                                    <p class="text-gray-400 text-sm mt-1">
                                        ลองค้นหาด้วยชื่อสินค้าอื่น
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


<script>

function confirmDelete(productId) {

    const result = confirm(
        "คุณต้องการลบสินค้านี้หรือไม่?"
    );

    if (result) {

        window.location.href =
            "delete_product.php?id=" + productId;

    }

}

</script>


</body>

</html>