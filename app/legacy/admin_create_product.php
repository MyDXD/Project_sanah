<?php
// เชื่อมต่อฐานข้อมูล
include '../dbconnect.php';

// ตรวจสอบว่ามีการส่งฟอร์มหรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $product_detail = $_POST['product_detail'];
    $product_type = $_POST['product_type'];
    $stock = $_POST['stock'];

    // อัพโหลดภาพ
    if (isset($_FILES['product_img']) && $_FILES['product_img']['error'] == 0) {
        // กำหนดโฟลเดอร์สำหรับจัดเก็บรูปภาพ
        $target_dir = "../product_pic/";

        // สร้างชื่อไฟล์ไม่ให้ซ้ำกัน (ใช้ uniqid เพื่อสุ่มชื่อไฟล์)
        $file_name = uniqid() . basename($_FILES["product_img"]["name"]);
        $target_file = $target_dir . $file_name;

        // ย้ายไฟล์ไปยังโฟลเดอร์ product_pic
        if (move_uploaded_file($_FILES["product_img"]["tmp_name"], $target_file)) {
            // ถ้าย้ายไฟล์สำเร็จ เก็บชื่อไฟล์ลงในฐานข้อมูล
            $img_path = "product_pic/" . $file_name;
            $sql = "INSERT INTO products (product_name, product_price, product_detail, product_img, product_type,stock) 
                    VALUES ('$product_name', '$product_price', '$product_detail', '$img_path', '$product_type','$stock')";
        } else {
            echo "เกิดข้อผิดพลาดในการอัปโหลดไฟล์";
            exit();
        }
    } else {
        // ถ้าไม่มีการอัปโหลดภาพ เก็บข้อมูลอื่น ๆ โดยไม่ต้องมี product_img
        $sql = "INSERT INTO products (product_name, product_price, product_detail, product_type,stock) 
                VALUES ('$product_name', '$product_price', '$product_detail', '$product_type','$stock')";
    }

    // แทรกข้อมูลลงในฐานข้อมูล
    if (mysqli_query($conn, $sql)) {
        header("Location: products.php");
        exit();
    } else {
        echo "เกิดข้อผิดพลาดในการเพิ่มผลิตภัณฑ์: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Product</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.0.0/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="max-w-lg w-full bg-white p-8 rounded-lg shadow-lg">
        <h1 class="text-2xl font-bold mb-6">เพิ่มข้อมูลสินค้า</h1>
        <form action="create_product.php" method="post" enctype="multipart/form-data">
            <div class="mb-4">
                <label for="product_name" class="block text-gray-700">ชื่อสินค้า</label>
                <input type="text" id="product_name" name="product_name"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div class="mb-4">
                <label for="product_detail" class="block text-gray-700">รายละเอียดสินค้า</label>
                <textarea id="product_detail" name="product_detail" rows="4"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg" required></textarea>
            </div>
            <div class="mb-4">
                <label for="product_price" class="block text-gray-700">ราคา</label>
                <input type="number" id="product_price" name="product_price" step="0.01"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div class="mb-4">
                <label for="stock" class="block text-gray-700">จำนวน</label>
                <input type="number" id="stock" name="stock" step="0.01"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div class="mb-4">
                <label for="image" class="block text-gray-700">รูปภาพ</label>
                <input type="file" id="product_img" name="product_img"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <?php
            // ดึงประเภทสินค้าทั้งหมดจากตาราง products โดยไม่เอาค่าซ้ำ
            $product_types_query = "SELECT DISTINCT product_type FROM products";
            $product_types_result = mysqli_query($conn, $product_types_query);
            ?>
            <div class="mb-4">
                <label for="product_type" style="border-style: solid 2px;">ประเภทสินค้า</label>
                <input list="product_types_list" name="product_type" id="product_type" class="border p-2 w-full" required>
                <datalist id="product_types_list">
                    <?php
                    // วนลูปแสดงผลประเภทสินค้าจากฐานข้อมูล
                    while ($row = mysqli_fetch_assoc($product_types_result)) {
                        $type = $row['product_type'];
                        echo "<option value='$type'>$type</option>";
                    }
                    ?>
                </datalist>
            </div>

            <button type="submit"
                class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">เพิ่มสินค้า</button>
        </form>
    </div>
</body>

<script>
    // ฟังก์ชั่นเพื่อแสดง/ซ่อนช่องพิมพ์ประเภทสินค้าใหม่
    function toggleOtherInput() {
        var selectBox = document.getElementById("product_type");
        var otherInputDiv = document.getElementById("other_product_type");

        // ถ้าผู้ใช้เลือก "อื่นๆ" แสดงช่องพิมพ์
        if (selectBox.value == "อื่นๆ") {
            otherInputDiv.style.display = "block";
        } else {
            otherInputDiv.style.display = "none";
        }
    }
</script>

</html>