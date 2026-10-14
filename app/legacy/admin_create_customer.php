<?php
// เชื่อมต่อฐานข้อมูล
include '../dbconnect.php';

// ตรวจสอบว่ามีการส่งฟอร์มหรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $title_name = $_POST['title_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $phone_number = $_POST['phone_number'];
    $address = $_POST['address'];

    // สร้าง SQL Query
    $sql = "INSERT INTO userdata (first_name, last_name, email, password, phone_number, address)
            VALUES ('$first_name', '$last_name', '$email', '$password', '$phone_number', '$address' )";

    // แทรกข้อมูลลงในฐานข้อมูล
    if (mysqli_query($conn, $sql)) {
        header("Location: customer_data.php");

        exit();
    } else {
        echo "เกิดข้อผิดพลาดในการเพิ่มผู้ใช้: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.0.0/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="max-w-lg w-full bg-white p-8 rounded-lg shadow-lg">
        <h1 class="text-2xl font-bold mb-6">เพิ่มผู้ใช้</h1>
        <form action="" method="post"> <!-- แก้ชื่อไฟล์ที่นี่ -->
            <div class="mb-4">
                <label for="first_name" class="block text-gray-700">ชื่อ</label>
                <input type="text" id="first_name" name="first_name"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div class="mb-4">
                <label for="last_name" class="block text-gray-700">นามสกุล</label>
                <input type="text" id="last_name" name="last_name"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div class="mb-4">
                <label for="title_name" class="block text-gray-700">ชือเล่น</label>
                <input type="text" id="title_name" name="title_name"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div class="mb-4">
                <label for="email" class="block text-gray-700">อีเมล</label>
                <input type="email" id="email" name="email"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div class="mb-4">
                <label for="password" class="block text-gray-700">รหัสผ่าน</label>
                <input type="password" id="password" name="password"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div class="mb-4">
                <label for="phone_number" class="block text-gray-700">หมายเลขโทรศัพท์</label>
                <input type="text"  id="phone_number" name="phone_number"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
            </div>
            <div class="mb-4">
                <label for="address" class="block text-gray-700">ที่อยู่</label>
                <textarea id="address" name="address" rows="4"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg" required></textarea>
            </div>
            <button type="submit" name="submit"
                class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">เพิ่มผู้ใช้</button>
        </form>
    </div>
</body>

</html>
