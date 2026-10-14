<?php
session_start();
include "dbconnect.php";
include "nav_bar.php";
error_reporting(E_ALL & ~E_WARNING); // ปิดเฉพาะ Warning


if ($id = isset($_GET['id']) ? $_GET['id'] : null) {
    $query = mysqli_query($conn, "SELECT * FROM `products` WHERE product_id=$id");
    $row = mysqli_fetch_assoc($query);
    $productname = $row['product_name'];
    $productdetail = $row['product_detail'];
    $productprice = $row['product_price'];
    $producttype = $row['product_type'];
    $productstock = $row['stock'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = $_POST['product_id'];
    $user_id = $_SESSION['user_id']; // ตรวจสอบว่า user_id ถูกเก็บใน session หรือไม่

    if ($user_id) {
        // ตรวจสอบว่าสินค้าอยู่ในตะกร้าหรือยัง
        $check_cart_query = "SELECT * FROM `cart` WHERE `user_id` = '$user_id' AND `product_id` = '$product_id'";
        $check_cart_result = mysqli_query($conn, $check_cart_query);

        if (mysqli_num_rows($check_cart_result) > 0) {
            // ถ้าสินค้าอยู่ในตะกร้าแล้ว ให้แสดงข้อความเตือนโดยใช้ SweetAlert
            echo "<script>
                Swal.fire({
                    icon: 'warning',
                    title: 'สินค้าอยู่ในตะกร้าแล้ว',
                    text: 'คุณไม่สามารถเพิ่มสินค้าที่มีอยู่แล้วได้อีกครั้ง!'
                });
            </script>";
        } else {
            // ถ้าไม่มีสินค้าในตะกร้า ให้เพิ่มลงในฐานข้อมูล
            $insert_cart_query = "INSERT INTO `cart` (`user_id`, `product_id`, `quantity`) VALUES ('$user_id', '$product_id', 1)";
            if (mysqli_query($conn, $insert_cart_query)) {
                echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'เพิ่มสินค้าเรียบร้อย',
                        text: 'สินค้าถูกเพิ่มลงในตะกร้าของคุณแล้ว!'
                    });
                </script>";
            } else {
                echo "<script>
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถเพิ่มสินค้าได้ โปรดลองอีกครั้ง!'
                    });
                </script>";
            }
        }
    } else {
        echo '<script>
                    window.onload = function() {
                        Swal.fire({
                            title: "กรุณาเข้าสู่ระบบ",
                            html: "จะสามารถสั่งสินค้าได้เมื่อเป็นสมาชิก",
                            icon: "info",
                            confirmButtonText: "ตกลง",
                            customClass: {
                            popup: "swal-wide",
                            confirmButton: "swal-button"
                        }
                        }).then(function() {
                            window.location.href = "login.php";
                        });
                    };
                </script>';
            exit();

    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- เพิ่ม SweetAlert -->
    <title>รายละเอียดสินค้า</title>
</head>

<body>
    <div class="bg-white">
        <div
            class="mx-auto max-w-2xl bg-gray-100 mt-2 rounded-lg shadow-md px-4 py-16 sm:px-6 sm:py-24 lg:max-w-7xl lg:px-8">
            <div class="lg:grid lg:grid-cols-2 lg:items-start lg:gap-x-8">
                <div class="flex flex-col-reverse">
                    <div class="aspect-h-1 aspect-w-1 w-full">
                        <div id="tabs-1-panel-1" aria-labelledby="tabs-1-tab-1" role="tabpanel" tabindex="0">
                            <img src="<?= htmlspecialchars($row['product_img']) ?>" alt="รูปสินค้า"
                                class="h-2/3 w-2/3 m-16 px-4 sm:mt-16 sm:px-0 lg:mt-0 object-cover object-center sm:rounded-lg">
                        </div>
                    </div>
                </div>

                <div class="m-16 px-4 sm:mt-16 sm:px-0 lg:mt-0">
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900"><?php echo "$productname" ?></h1>

                    <div class="mt-3">
                        <p class="text-3xl tracking-tight text-gray-900">ราคา : <?php echo "$productprice" ?> บาท</p>
                    </div>
                    <div class="mt-3">
                        <p class="text-sm tracking-tight text-gray-900">คงเหลือ : <?php echo "$productstock" ?> ชิ้น</p>
                    </div>
                    <div class="mt-3">
                        <p class="text-xl tracking-tight text-gray-900">ประเภท : <?php echo "$producttype" ?></p>
                    </div>

                    <div class="mt-6">
                        <div class="space-y-6 text-base text-gray-700">
                            <p><?php echo "$productdetail" ?></p>
                        </div>
                    </div>

                    <!-- ฟอร์มเพิ่มสินค้าลงตะกร้า -->
                    <form method="POST">
                        <input type="hidden" name="product_id" value="<?php echo $id; ?>">
                        <div class="mt-10 flex">
                            <button type="submit"
                                class="flex max-w-xs flex-1 items-center justify-center rounded-md border border-transparent bg-indigo-600 px-8 py-3 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-50 sm:w-full">
                                เพิ่มลงในตะกร้า
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
