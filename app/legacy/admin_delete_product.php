<?php
// เชื่อมต่อฐานข้อมูล
include '../dbconnect.php';

// ตรวจสอบว่ามีการส่งฟอร์มหรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = $_POST['product_id'];

    // ลบข้อมูลจากฐานข้อมูล
    $sql = "DELETE FROM `products` WHERE `product_id`=?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $product_id);
        if ($stmt->execute()) {
            header("Location: products.php");
            exit();
        } else {
            echo "เกิดข้อผิดพลาดในการลบผลิตภัณฑ์: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . $conn->error;
    }
}

// ดึงข้อมูลผลิตภัณฑ์เพื่อแสดงในฟอร์ม
$product_id = $_GET['id'];
$sql = "SELECT * FROM `products` WHERE `product_id`=?";
    
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if (!$row) {
        die("ผลิตภัณฑ์ไม่พบ");
    }
    
    $stmt->close();
} else {
    echo "เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . $conn->error;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Product</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.0.0/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="max-w-lg w-full bg-white p-8 rounded-lg shadow-lg">
        <h1 class="text-2xl font-bold mb-6">Delete Product</h1>
        <form action="delete_product.php" method="post">
            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($row['product_id']); ?>">
            <p class="mb-4">คุณแน่ใจหรือไม่ว่าต้องการลบผลิตภัณฑ์ <strong><?php echo htmlspecialchars($row['product_name']); ?></strong>?</p>
            <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600">ลบสินค้า</button>
            <a href="products.php" class="ml-4 bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">ยกเลิก</a>
        </form>
    </div>
</body>
</html>
