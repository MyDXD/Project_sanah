<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("dbconnect.php");

if (!isset($_SESSION['email'])) {
    header("Location: login_form.php");
    exit();
}

$email_session = $_SESSION['email'];

$query = mysqli_query($conn, "SELECT * FROM userdata WHERE email='$email_session'");

if (!$query || mysqli_num_rows($query) == 0) {
    echo "ไม่พบข้อมูลผู้ใช้";
    exit();
}

$row = mysqli_fetch_assoc($query);

$firstname = $row['first_name'];
$lastname = $row['last_name'];
$email = $row['email'];
$phone_number = $row['phone_number'];
$address = $row['address'];
$user_id = $row['user_id'];

if (isset($_POST['submit'])) {

    $firstname = mysqli_real_escape_string($conn, $_POST['first_name']);
    $lastname = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone_number']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    if (!empty($_POST['password'])) {

        $password = mysqli_real_escape_string($conn, $_POST['password']);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $update = "UPDATE userdata SET
            email='$email',
            password='$hashed_password',
            first_name='$firstname',
            last_name='$lastname',
            address='$address',
            phone_number='$phone'
            WHERE user_id='$user_id'";

    } else {

        $update = "UPDATE userdata SET
            email='$email',
            first_name='$firstname',
            last_name='$lastname',
            address='$address',
            phone_number='$phone'
            WHERE user_id='$user_id'";
    }

    if (mysqli_query($conn, $update)) {

        $_SESSION['email'] = $email;

        echo "<script>
            Swal.fire({
                title: 'แก้ไขข้อมูลสำเร็จ',
                icon: 'success',
                confirmButtonText: 'ตกลง'
            }).then(() => {
                window.location.href = 'index.php';
            });
        </script>";

    } else {
        echo "เกิดข้อผิดพลาด: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.tailwindcss.com"></script>

    <title>แก้ไขข้อมูล</title>
</head>

<body class="bg-[#f6f1e8]">

<?php include 'nav_bar.php'; ?>

<div class="min-h-screen py-12 px-4">

    <div class="max-w-2xl mx-auto">

        <div class="bg-white rounded-3xl shadow-sm border border-[#eadfce] p-8">

            <div class="text-center mb-8">
                <div class="text-4xl mb-3">👤</div>

                <h2 class="text-3xl font-bold text-[#5f401f]">
                    แก้ไขโปรไฟล์
                </h2>

                <p class="text-gray-500 mt-2">
                    แก้ไขข้อมูลส่วนตัวของคุณ
                </p>
            </div>

            <form method="post" class="space-y-5">

                <!-- ชื่อ -->
                <div>
                    <label class="block text-sm font-semibold text-[#5f401f] mb-2">
                        ชื่อจริง
                    </label>

                    <input
                        type="text"
                        name="first_name"
                        value="<?php echo htmlspecialchars($firstname); ?>"
                        required
                        class="w-full rounded-xl border border-[#eadfce] px-4 py-3 outline-none focus:ring-2 focus:ring-[#c58a3a]">
                </div>

                <!-- นามสกุล -->
                <div>
                    <label class="block text-sm font-semibold text-[#5f401f] mb-2">
                        นามสกุล
                    </label>

                    <input
                        type="text"
                        name="last_name"
                        value="<?php echo htmlspecialchars($lastname); ?>"
                        required
                        class="w-full rounded-xl border border-[#eadfce] px-4 py-3 outline-none focus:ring-2 focus:ring-[#c58a3a]">
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-semibold text-[#5f401f] mb-2">
                        อีเมล
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="<?php echo htmlspecialchars($email); ?>"
                        required
                        class="w-full rounded-xl border border-[#eadfce] px-4 py-3 outline-none focus:ring-2 focus:ring-[#c58a3a]">
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-semibold text-[#5f401f] mb-2">
                        รหัสผ่านใหม่
                    </label>

                    <input
                        type="password"
                        name="password"
                        placeholder="เว้นว่างถ้าไม่ต้องการเปลี่ยนรหัสผ่าน"
                        class="w-full rounded-xl border border-[#eadfce] px-4 py-3 outline-none focus:ring-2 focus:ring-[#c58a3a]">

                    <p class="text-xs text-gray-400 mt-1">
                        หากไม่กรอก จะใช้รหัสผ่านเดิม
                    </p>
                </div>

                <!-- เบอร์โทร -->
                <div>
                    <label class="block text-sm font-semibold text-[#5f401f] mb-2">
                        เบอร์โทรศัพท์
                    </label>

                    <input
                        type="text"
                        name="phone_number"
                        value="<?php echo htmlspecialchars($phone_number); ?>"
                        required
                        class="w-full rounded-xl border border-[#eadfce] px-4 py-3 outline-none focus:ring-2 focus:ring-[#c58a3a]">
                </div>

                <!-- ที่อยู่ -->
                <div>
                    <label class="block text-sm font-semibold text-[#5f401f] mb-2">
                        ที่อยู่สำหรับจัดส่ง
                    </label>

                    <textarea
                        name="address"
                        rows="4"
                        required
                        class="w-full rounded-xl border border-[#eadfce] px-4 py-3 outline-none focus:ring-2 focus:ring-[#c58a3a]"><?php echo htmlspecialchars($address); ?></textarea>
                </div>

                <!-- ปุ่ม -->
                <div class="pt-3">
                    <button
                        type="submit"
                        name="submit"
                        class="w-full rounded-xl bg-[#9a641f] hover:bg-[#7f5018] text-white font-semibold py-3 transition shadow-sm">
                        บันทึกการแก้ไข
                    </button>
                </div>

            </form>

        </div>

    </div>

</div>

</body>
</html>