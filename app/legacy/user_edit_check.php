<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php
include "dbconnect.php";
include "user_edit_check_form.php";

        if (isset($_POST['check'])) {
            $password = $_POST['password'];
            // $password = md5($password);
        
            $sql = "SELECT * FROM `userdata` WHERE `password`='$password'";
            $check = $conn->query($sql);

                echo '<script>
                    window.onload = function() {
                        Swal.fire({
                            title: "<p style=\'font-size: 2rem; color: #555;\'>รหัสผ่านถูกต้อง",
                            icon: "success",
                            confirmButtonText: "ตกลง",
                            customClass: {
                            popup: "swal-wide",
                            confirmButton: "swal-button"
                        }
                        }).then(function() {
                            window.location.href = "user_edit.php";
                        });
                    };
                </script>';
        exit();
    } else {
        echo '<script>
                    window.onload = function() {
                        Swal.fire({
                            title: "<p style=\'font-size: 2rem; color: #555;\'>รหัสผ่านไม่ถูกต้อง",
                            html: "<p style=\'font-size: 1.5rem; color: #555;\'>ตัวปลอมอะป่าว</p>",
                            icon: "error",
                            confirmButtonText: "ตกลง",
                            customClass: {
                            popup: "swal-wide",
                            confirmButton: "swal-button"
                        }
                        }).then(function() {
                            window.location.href = "user_edit_check_form.php";
                        });
                    };
                </script>';
        exit();
            }
    ?>