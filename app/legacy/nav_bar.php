<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<body>
    <?php
    include("dbconnect.php");

    if (isset($_SESSION['email'])) {
        $email = $_SESSION['email'];
        $query = mysqli_query($conn, "SELECT * FROM `userdata` WHERE `email`='$email'");

        if (!$query) {
            echo "Error: " . mysqli_error($conn);
        } else {
            if ($row = mysqli_fetch_assoc($query)) {
                $you = 'คุณ';
                $firstname = $row['first_name'];
                $lastname = $row['last_name'];
                $logout = 'ออกจากระบบ';
                $cart = "ตรวจสอบตระกร้าสินค้า";
                $order = "ตรวจสอบออเดอร์";
                $login = "";
                $signup = "";
            }
        }
    } else {
        $you = "";
        $firstname = "";
        $lastname = "";
        $logout = "";
        $login = "เข้าสู่ระบบ";
        $signup = "สมัครสมาชิก";
        $cart = "";
        $order = "";
    }
    ?>

    <div class="bg-white">
        <header class="relative">
            <nav aria-label="Top">

                <div class="bg-gray-900">
                    <div class="mx-auto flex h-10 max-w-7xl items-center justify-end px-4 sm:px-6 lg:px-8">
                        <div class="flex items-center space-x-6">

                            <a href="user_edit.php"
                                class="text-sm font-medium text-white hover:text-gray-100">
                                <?php echo "$you $firstname"; ?>
                            </a>

                            <button class="text-red-300 hover:text-white" onclick="confirmLogout()">
                                <?php echo $logout; ?>
                            </button>

                            <a href="login.php"
                                class="text-sm font-medium text-white hover:text-gray-100">
                                <?php echo $login; ?>
                            </a>

                            <a href="register.php"
                                class="text-sm font-medium text-white hover:text-gray-100">
                                <?php echo $signup; ?>
                            </a>

                        </div>
                    </div>
                </div>

                <div class="bg-white">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                        <div class="border-b border-gray-200">

                            <div class="flex h-16 items-center justify-between">

                                <!-- Logo -->
                                <div class="items-center">
                                    <a href="index.php">
                                        <img class="h-12 w-auto"
                                            src="pik/logo.png"
                                            alt="Logo">
                                    </a>
                                </div>


                                <!-- เมนู -->
                                <div class="items-center justifly-center space-x-8">

                                    <a href="products.php"
                                        class="text-sm font-medium text-gray-700 hover:text-gray-800">
                                        สินค้าทั้งหมด
                                    </a>

                                    <a href="contactus.php"
                                        class="text-sm font-medium text-gray-700 hover:text-gray-800">
                                        ช่องทางติดต่อเพิ่มเติม
                                    </a>

                                   <a href="report_user.php"
   class="text-sm font-medium text-gray-700 hover:text-gray-800">
    ส่งความคิดเห็น
</a>

                                    <a href="cart.php"
                                        class="text-sm font-medium text-gray-700 hover:text-gray-800">
                                        <?php echo $cart; ?>
                                    </a>

                                    <a href="order.php"
                                        class="text-sm font-medium text-gray-700 hover:text-gray-800">
                                        <?php echo $order; ?>
                                    </a>

                                </div>


                                <div class="items-center justifly-end space-x-4">
                                </div>

                            </div>

                        </div>

                    </div>
                </div>

            </nav>
        </header>
    </div>
</body>


<script>
    // ฟังก์ชันสำหรับการยืนยันการออกจากระบบ
    function confirmLogout() {

        Swal.fire({
            title: 'คุณแน่ใจหรือไม่?',
            text: "คุณต้องการออกจากระบบใช่หรือไม่?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'ใช่, ออกจากระบบ!',
            cancelButtonText: 'ยกเลิก'

        }).then((result) => {

            if (result.isConfirmed) {
                window.location.href = "./logout.php";
            }

        })

    }
</script>
