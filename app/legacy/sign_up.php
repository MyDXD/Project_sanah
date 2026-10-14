<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<?php
include "dbconnect.php";

if (isset($_POST['sign_up'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $firstname = $_POST['first_name'];
    $lastname = $_POST['last_name'];
    $passwordcheck = $_POST['passwordcheck'];
    $address = $_POST['address']; // รับค่าที่อยู่
    $phone_number = $_POST['phone_number']; // รับค่าที่อยู่

    // ตรวจสอบว่าอีเมลนี้ถูกใช้ไปแล้วหรือยัง
    $checkemail = $conn->prepare("SELECT * FROM userdata WHERE email = ?");
    $checkemail->bind_param("s", $email);
    $checkemail->execute();
    $check = $checkemail->get_result();

    if ($check->num_rows > 0) {
        echo '<script>
                window.onload = function() {
                    Swal.fire({
                        title: "<p style=\'font-size: 2rem; color: #555;\'>ไม่สามารถสมัครสมาชิกได้",
                        html: "<p style=\'font-size: 1.5rem; color: #555;\'>อีเมลนี้ถูกใช้ไปแล้ว</p>",
                        icon: "error",
                        confirmButtonText: "ตกลง",
                        customClass: {
                            popup: "swal-wide",
                            confirmButton: "swal-button"
                        }
                    }).then(function() {
                        window.location.href = "sign_upform.php";
                    });
                };
            </script>';
    } elseif ($password !== $passwordcheck) {
        echo '<script>
                window.onload = function() {
                    Swal.fire({
                        title: "<p style=\'font-size: 2rem; color: #555;\'>ไม่สามารถสมัครสมาชิกได้",
                        html: "<p style=\'font-size: 1.5rem; color: #555;\'>กรุณากรอกรหัสผ่านให้ตรงกัน</p>",
                        icon: "error",
                        confirmButtonText: "ตกลง",
                        customClass: {
                            popup: "swal-wide",
                            confirmButton: "swal-button"
                        }
                    }).then(function() {
                        window.location.href = "sign_upform.php";
                    });
                };
            </script>';
    } else {
        // เข้ารหัสรหัสผ่าน
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // เพิ่มข้อมูลผู้ใช้ใหม่ในฐานข้อมูล พร้อมที่อยู่
        $insert = $conn->prepare("INSERT INTO userdata (email, password, first_name, last_name, address, phone_number, role) VALUES (?, ?, ?, ?, ?, ?, 'user')");
        $insert->bind_param("ssssss", $email, $hashed_password, $firstname, $lastname, $address, $phone_number);

        if ($insert->execute()) {
            // ดึงข้อมูลผู้ใช้หลังจากลงทะเบียนสำเร็จ
            session_start();
            $user_id = $conn->insert_id; // ดึง ID ของผู้ใช้ที่ถูกสร้างขึ้น
            
            // เก็บข้อมูลผู้ใช้ในเซสชัน
            $_SESSION['email'] = $email;
            $_SESSION['role'] = 'user'; // กำหนดบทบาทเป็น 'user'
            $_SESSION['user_id'] = $user_id; // เพิ่มการตั้งค่า user_id
            echo '<script>
                window.onload = function() {
                    Swal.fire({
                        title: "<p style=\'font-size: 2rem; color: #555;\'>สมัครสมาชิกสำเร็จ",
                        html: "<p style=\'font-size: 1.5rem; color: #555;\'>ยินดีต้อนรับ</p>",
                        icon: "success",
                        confirmButtonText: "ตกลง",
                        customClass: {
                            popup: "swal-wide",
                            confirmButton: "swal-button"
                        }
                    }).then(function() {
                        window.location.href = "index.php";
                    });
                };
                </script>';
        } else {
            echo "เกิดข้อผิดพลาด: " . $conn->error;
        }
    }
}
?>