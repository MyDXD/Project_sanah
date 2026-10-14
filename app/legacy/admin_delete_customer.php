<?php
// เชื่อมต่อฐานข้อมูล
include '../dbconnect.php';

// ตรวจสอบว่ามีการส่งฟอร์มหรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id']; // เปลี่ยนจาก product_id เป็น user_id

    // ลบข้อมูลจากฐานข้อมูล
    $sql = "DELETE FROM `userdata` WHERE `user_id`=?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            header("Location: customer_data.php");
            exit();
        } else {
            echo "เกิดข้อผิดพลาดในการลบผู้ใช้: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . $conn->error;
    }
}

// ดึงข้อมูลผู้ใช้เพื่อแสดงในฟอร์ม
$user_id = $_GET['id']; // เปลี่ยนเป็น user_id
$sql = "SELECT * FROM `userdata` WHERE `user_id`=?";
    
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if (!$row) {
        die("ไม่พบผู้ใช้");
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
    <title>Delete User</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.0.0/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="max-w-lg w-full bg-white p-8 rounded-lg shadow-lg">
        <h1 class="text-2xl font-bold mb-6">Delete User</h1>
        <form action="delete_customer.php" method="post">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($row['user_id']); ?>"> <!-- เปลี่ยน product_id เป็น user_id -->
            <p class="mb-4">คุณแน่ใจหรือไม่ว่าต้องการลบผู้ใช้ <strong><?php echo htmlspecialchars($row['first_name']) . " " . htmlspecialchars($row['last_name']); ?></strong>?</p>
            <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600">ลบผู้ใช้</button>
            <a href="customer_data.php" class="ml-4 bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">ยกเลิก</a>
        </form>
    </div>
</body>
</html>
