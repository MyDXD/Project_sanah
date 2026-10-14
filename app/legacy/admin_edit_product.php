<?php
// เชื่อมต่อฐานข้อมูล
include '../dbconnect.php';

// ตรวจสอบว่ามีการส่งฟอร์มหรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $product_detail = $_POST['product_detail'];
    $product_type = $_POST['product_type'];

    // ดึงข้อมูลรูปภาพเก่าจากฐานข้อมูล
    $sql = "SELECT product_img FROM products WHERE product_id = '$product_id'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $old_image = $row['product_img'];

    // ตรวจสอบว่ามีการอัปโหลดภาพใหม่หรือไม่
    if (isset($_FILES['product_img']) && $_FILES['product_img']['error'] == 0) {
        // ตั้งค่าโฟลเดอร์จัดเก็บรูปภาพ
        $target_dir = "../product_pic/";

        // สร้างชื่อไฟล์ใหม่ (สุ่มชื่อเพื่อป้องกันการชนกัน)
        $file_name = uniqid() . basename($_FILES["product_img"]["name"]);
        $target_file = $target_dir . $file_name;

        // ลบรูปภาพเก่าออกจากโฟลเดอร์
        if (!empty($old_image) && file_exists("../" . $old_image)) {
            unlink("../" . $old_image);
        }

        // ย้ายไฟล์รูปภาพใหม่ไปยังโฟลเดอร์
        if (move_uploaded_file($_FILES["product_img"]["tmp_name"], $target_file)) {
            // บันทึกเส้นทางไฟล์ใหม่ลงในฐานข้อมูล
            $img_path = "product_pic/" . $file_name;
            $sql = "UPDATE products 
                    SET product_name='$product_name', product_price='$product_price', product_detail='$product_detail', product_img='$img_path', product_type='$product_type' 
                    WHERE product_id='$product_id'";
        } else {
            echo "เกิดข้อผิดพลาดในการอัปโหลดรูปภาพใหม่";
            exit();
        }
    } else {
        // ถ้าไม่มีการอัปโหลดภาพใหม่ ให้ทำการอัปเดตข้อมูลอื่น ๆ โดยไม่เปลี่ยนรูปภาพ
        $sql = "UPDATE products 
                SET product_name='$product_name', product_price='$product_price', product_detail='$product_detail', product_type='$product_type' 
                WHERE product_id='$product_id'";
    }

    // อัปเดตข้อมูลในฐานข้อมูล
    if (mysqli_query($conn, $sql)) {
        header("Location: products.php");
        exit();
    } else {
        echo "เกิดข้อผิดพลาดในการแก้ไขผลิตภัณฑ์: " . mysqli_error($conn);
    }
}

// ดึงข้อมูลผลิตภัณฑ์เพื่อแสดงในฟอร์ม
$product_id = $_GET['id'];
$sql = "SELECT * FROM products WHERE product_id='$product_id'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    die("ผลิตภัณฑ์ไม่พบ");
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.0.0/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="max-w-lg w-full bg-white p-8 rounded-lg shadow-lg">
        <h1 class="text-2xl font-bold mb-6">Edit Product</h1>
        <form action="edit_product.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($row['product_id']); ?>">
            <div class="mb-4">
                <label for="product_name" class="block text-gray-700">ชื่อสินค้า</label>
                <input type="text" id="product_name" name="product_name"
                    value="<?php echo htmlspecialchars($row['product_name']); ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div class="mb-4">
                <label for="product_detail" class="block text-gray-700">รายละเอียดสินค้า</label>
                <textarea id="product_detail" name="product_detail" rows="4"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                    required><?php echo htmlspecialchars($row['product_detail']); ?></textarea>
            </div>
            <div class="mb-4">
                <label for="product_price" class="block text-gray-700">ราคา</label>
                <input type="number" id="product_price" name="product_price" step="0.01"
                    value="<?php echo htmlspecialchars($row['product_price']); ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div class="mb-4 h-auto">
                <label for="product_img" class="block text-gray-700">รูปภาพ</label>
                <input type="file" id="product_img" name="product_img"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <?php if ($row['product_img']): ?>
                    <img src="../<?php echo htmlspecialchars($row['product_img']); ?>" alt="Product Image"
                        class="w-72 h-72 object-cover">
                <?php endif; ?>
            </div>
            <!-- <div class="mb-4">
                <label for="cars" style=" border-style: solid 2px;">ประเภทสินค้า</label>
                <select name="product_type">
                    <option value="ตากแห้ง" <?php if ($row['product_type'] == 'ตากแห้ง')
                        echo 'selected'; ?>>ตากแห้ง
                    </option>
                    <option value="อบกรอบ" <?php if ($row['product_type'] == 'อบกรอบ')
                        echo 'selected'; ?>>อบกรอบ</option>
                    <option value="ขนมนำเข้า" <?php if ($row['product_type'] == 'ขนมนำเข้า')
                        echo 'selected'; ?>>ขนมนำเข้า
                    </option>
                    <option value="ชา/กาแฟ" <?php if ($row['product_type'] == 'ชา/กาแฟ')
                        echo 'selected'; ?>>ชา/กาแฟ
                    </option>
                    <option value="อาหารกระป๋อง" <?php if ($row['product_type'] == 'อาหารกระป๋อง')
                        echo 'selected'; ?>>
                        อาหารกระป๋อง</option>
                    <option value="อื่นๆ" <?php if ($row['product_type'] == 'อื่นๆ')
                        echo 'selected'; ?>>อื่นๆ</option>
                </select>
            </div> -->

            <?php
            // ดึงประเภทสินค้าทั้งหมดจากตาราง products โดยไม่เอาค่าซ้ำ
            $product_types_query = "SELECT DISTINCT product_type FROM products";
            $product_types_result = mysqli_query($conn, $product_types_query);
            ?>
            <div class="mb-4">
                <label for="product_type" style="border-style: solid 2px;">ประเภทสินค้า</label>
                <input list="product_types_list" value="<?php echo htmlspecialchars($row['product_type']); ?>" name="product_type" id="product_type" class="border p-2 w-full" required>
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
                class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">แก้ไขสินค้า</button>
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