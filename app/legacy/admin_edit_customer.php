<?php
// เชื่อมต่อฐานข้อมูล
include '../dbconnect.php';

// ตรวจสอบว่ามีการส่งฟอร์มหรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $address = $_POST['address'];
    $phone_number = $_POST['phone_number'];
    $role = $_POST['role'];

    // เข้ารหัสรหัสผ่าน (ถ้าต้องการเข้ารหัส)
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // คำสั่ง SQL สำหรับอัปเดตข้อมูล
    $sql = "UPDATE userdata SET 
                email = '$email', 
                password = '$hashed_password', 
                first_name = '$first_name', 
                last_name = '$last_name', 
                address = '$address', 
                phone_number = '$phone_number',
                role = '$role' 
            WHERE user_id = '$user_id'";

    // ตรวจสอบการอัปเดต
    if (mysqli_query($conn, $sql)) {
        header("Location: customer_data.php");
        exit();
    } else {
        echo "เกิดข้อผิดพลาด: " . mysqli_error($conn);
    }
}

// ดึงข้อมูลผู้ใช้เพื่อแสดงในฟอร์ม
$user_id = $_GET['id'];
$sql = "SELECT * FROM userdata WHERE user_id='$user_id'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    die("ไม่พบผู้ใช้");
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
        <h1 class="text-2xl font-bold mb-6">เพิ่มผู้ใช้</h1>
        <form action="" method="post">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($row['user_id']); ?>">
            <div class="mb-4">
                <label for="role" class="block text-gray-700">บทบาท</label>
                <select id="role" name="role" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                    <option value="user" <?php if ($row['role'] == 'user')
                        echo 'selected'; ?>>User</option>
                    <option value="admin" <?php if ($row['role'] == 'admin')
                        echo 'selected'; ?>>Admin</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="first_name" class="block text-gray-700">ชื่อ</label>
                <input type="text" id="first_name" name="first_name"
                    value="<?php echo htmlspecialchars($row['first_name']); ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div class="mb-4">
                <label for="last_name" class="block text-gray-700">นามสกุล</label>
                <input type="text" id="last_name" name="last_name"
                    value="<?php echo htmlspecialchars($row['last_name']); ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div class="mb-4">
                <label for="email" class="block text-gray-700">อีเมล</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($row['email']); ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div class="mb-4">
                <label for="password" class="block text-gray-700">รหัสผ่าน</label>
                <input type="password" id="password" name="password"
                    value="<?php echo htmlspecialchars($row['password']); ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div class="mb-4">
                <label for="phone_number" class="block text-gray-700">หมายเลขโทรศัพท์</label>
                <input type="text" id="phone_number" name="phone_number"
                    value="<?php echo htmlspecialchars($row['phone_number']); ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div class="mb-4">
                <label for="address" class="block text-gray-700">ที่อยู่</label>
                <textarea id="address" name="address" rows="4"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                    required><?php echo htmlspecialchars($row['address']); ?></textarea>
            </div>
            <button type="submit" name="submit"
                class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">บันทึก</button>
        </form>
    </div>
</body>

</html>