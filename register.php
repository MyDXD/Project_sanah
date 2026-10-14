<?php
require_once __DIR__ . '/app/bootstrap.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/dbconnect.php';
function register_escape($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
if (empty($_SESSION['register_csrf'])) {
    $_SESSION['register_csrf'] = bin2hex(random_bytes(32));
}
$values = array_fill_keys(['first_name', 'last_name', 'email', 'phone_number', 'address'], '');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $field => $unused) {
        $values[$field] = is_string($_POST[$field] ?? null) ? trim($_POST[$field]) : '';
    }
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirmation = is_string($_POST['passwordcheck'] ?? null) ? $_POST['passwordcheck'] : '';
    $token = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    if (!hash_equals($_SESSION['register_csrf'], $token)) {
        $error = 'กรุณาลองส่งแบบฟอร์มอีกครั้ง';
    } elseif (in_array('', $values, true) || $password === '') {
        $error = 'กรุณากรอกข้อมูลให้ครบทุกช่อง';
    } elseif (!filter_var($values['email'], FILTER_VALIDATE_EMAIL) || strlen($values['email']) > 255) {
        $error = 'กรุณากรอกอีเมลให้ถูกต้อง';
    } elseif (preg_match_all('/./us', $values['first_name']) > 255 || preg_match_all('/./us', $values['last_name']) > 255) {
        $error = 'ชื่อและนามสกุลต้องไม่เกิน 255 ตัวอักษร';
    } elseif (!preg_match('/^[0-9]{10}$/', $values['phone_number'])) {
        $error = 'กรุณากรอกเบอร์โทรศัพท์เป็นตัวเลข 10 หลัก';
    } elseif (strlen($password) > 72) {
        $error = 'รหัสผ่านยาวเกินไป กรุณาใช้ไม่เกิน 72 ไบต์';
    } elseif ($password !== $confirmation) {
        $error = 'กรุณากรอกรหัสผ่านให้ตรงกัน';
    } else {
        try {
            $check = $conn->prepare('SELECT user_id FROM userdata WHERE email = ?');
            $check->bind_param('s', $values['email']);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $error = 'อีเมลนี้ถูกใช้ไปแล้ว';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $insert = $conn->prepare("INSERT INTO userdata (email, password, first_name, last_name, title_name, address, phone_number, role) VALUES (?, ?, ?, ?, '', ?, ?, 'user')");
                $insert->bind_param('ssssss', $values['email'], $hash, $values['first_name'], $values['last_name'], $values['address'], $values['phone_number']);
                $insert->execute();
                session_regenerate_id(true);
                $_SESSION['email'] = $values['email'];
                $_SESSION['role'] = 'user';
                $_SESSION['user_id'] = $conn->insert_id;
                unset($_SESSION['register_csrf']);
                header('Location: index.php');
                exit;
            }
        } catch (mysqli_sql_exception $exception) {
            error_log('Registration failed: ' . $exception->getMessage());
            $error = 'ไม่สามารถสมัครสมาชิกได้ กรุณาลองอีกครั้ง';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก | ร้านสานะกิมหยง</title>
    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #faf6ef;
            color: #5f4a35;
            overflow-x: hidden;
        }

        /* =========================
           พื้นหลัง
        ========================= */

        .signup-page {
            min-height: calc(100vh - 70px);
            padding: 50px 20px;
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(circle at 10% 20%, rgba(222, 184, 130, 0.18), transparent 25%),
                radial-gradient(circle at 90% 80%, rgba(154, 100, 31, 0.12), transparent 25%),
                #faf6ef;
        }


        /* วงกลมพื้นหลัง */

        .bg-circle {
            position: absolute;
            border-radius: 50%;
            filter: blur(2px);
            opacity: 0.45;
            animation: floatCircle 7s ease-in-out infinite;
        }

        .circle1 {
            width: 180px;
            height: 180px;
            background: #ead7bb;
            top: 8%;
            left: -60px;
        }

        .circle2 {
            width: 250px;
            height: 250px;
            background: #f0dfc5;
            right: -90px;
            bottom: 5%;
            animation-delay: 2s;
        }

        .circle3 {
            width: 100px;
            height: 100px;
            background: #dfbd91;
            right: 15%;
            top: 15%;
            animation-delay: 1s;
        }


        @keyframes floatCircle {

            0%, 100% {
                transform: translateY(0) translateX(0);
            }

            50% {
                transform: translateY(-25px) translateX(15px);
            }

        }


        /* =========================
           ของตกแต่ง
        ========================= */

        .floating-item {
            position: absolute;
            font-size: 45px;
            opacity: 0.35;
            animation: floating 4s ease-in-out infinite;
            pointer-events: none;
        }

        .item1 {
            top: 12%;
            left: 12%;
        }

        .item2 {
            top: 30%;
            right: 10%;
            animation-delay: 1s;
        }

        .item3 {
            bottom: 12%;
            left: 10%;
            animation-delay: 2s;
        }

        .item4 {
            bottom: 20%;
            right: 13%;
            animation-delay: 0.5s;
        }


        @keyframes floating {

            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }

            50% {
                transform: translateY(-18px) rotate(8deg);
            }

        }


        /* =========================
           กล่องสมัครสมาชิก
        ========================= */

        .signup-card {

            max-width: 620px;
            width: 100%;

            margin: auto;

            padding: 45px;

            background: rgba(255, 255, 255, 0.94);

            border: 1px solid #eadfce;

            border-radius: 28px;

            box-shadow:
                0 20px 50px rgba(126, 91, 48, 0.15),
                0 5px 15px rgba(126, 91, 48, 0.08);

            position: relative;
            z-index: 5;

            animation: cardAppear 0.8s ease,
                       cardFloat 5s ease-in-out 1s infinite;

            backdrop-filter: blur(8px);
        }


        @keyframes cardAppear {

            from {
                opacity: 0;
                transform: translateY(40px) scale(0.96);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }

        }


        @keyframes cardFloat {

            0%, 100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-5px);
            }

        }


        /* =========================
           หัวข้อ
        ========================= */

        .signup-header {
            text-align: center;
            margin-bottom: 35px;
        }


        .signup-icon {

            width: 80px;
            height: 80px;

            margin: 0 auto 15px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #fff5e6;

            border-radius: 24px;

            font-size: 40px;

            box-shadow:
                0 8px 20px rgba(154, 100, 31, 0.12);

            animation: iconBounce 3s ease-in-out infinite;
        }


        @keyframes iconBounce {

            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }

            50% {
                transform: translateY(-7px) rotate(3deg);
            }

        }


        .signup-header h2 {

            margin: 0;

            font-size: 32px;

            font-weight: bold;

            color: #7f5018;

        }


        .signup-header p {

            margin-top: 8px;

            color: #9b8a75;

            font-size: 14px;

        }


        /* =========================
           ช่องกรอก
        ========================= */

        .form-group {
            margin-bottom: 20px;
        }


        .form-group label {

            display: block;

            margin-bottom: 8px;

            font-size: 14px;

            font-weight: bold;

            color: #6b5136;

        }


        .input-wrapper {
            position: relative;
        }


        .input-wrapper span {

            position: absolute;

            left: 14px;
            top: 50%;

            transform: translateY(-50%);

            font-size: 18px;

            transition: 0.25s;

            pointer-events: none;
        }


        .form-input {

            width: 100%;

            padding: 13px 15px 13px 45px;

            border: 1px solid #e5d8c6;

            border-radius: 12px;

            background: #fffdf9;

            color: #5f4a35;

            font-size: 15px;

            outline: none;

            transition: all 0.3s ease;

        }


        .form-input:hover {

            border-color: #cda46d;

            transform: translateY(-1px);

        }


        .form-input:focus {

            border-color: #a86d24;

            background: white;

            box-shadow:
                0 0 0 4px rgba(197, 138, 58, 0.12),
                0 5px 15px rgba(154, 100, 31, 0.08);

            transform: translateY(-2px);

        }


        .form-input:focus + span {
            transform: translateY(-50%) scale(1.15);
        }


        textarea.form-input {

            min-height: 110px;

            resize: vertical;

            padding-top: 13px;

        }


        /* =========================
           ปุ่ม
        ========================= */

        .signup-button {

            width: 100%;

            padding: 14px;

            border: none;

            border-radius: 13px;

            background: linear-gradient(
                135deg,
                #9a641f,
                #7f5018
            );

            color: white;

            font-size: 16px;

            font-weight: bold;

            cursor: pointer;

            box-shadow:
                0 7px 18px rgba(154, 100, 31, 0.25);

            transition: all 0.3s ease;

            position: relative;

            overflow: hidden;

        }


        .signup-button::before {

            content: "";

            position: absolute;

            top: 0;
            left: -100%;

            width: 100%;
            height: 100%;

            background: linear-gradient(
                90deg,
                transparent,
                rgba(255,255,255,0.25),
                transparent
            );

            transition: 0.5s;

        }


        .signup-button:hover {

            transform: translateY(-3px);

            box-shadow:
                0 12px 25px rgba(154, 100, 31, 0.3);

        }


        .signup-button:hover::before {
            left: 100%;
        }


        .signup-button:active {
            transform: translateY(1px);
        }


        /* =========================
           ข้อความด้านล่าง
        ========================= */

        .bottom-text {

            text-align: center;

            margin-top: 22px;

            color: #a18f79;

            font-size: 13px;

        }


        /* =========================
           Responsive
        ========================= */

        @media (max-width: 600px) {

            .signup-page {
                padding: 25px 12px;
            }

            .signup-card {
                padding: 30px 22px;
                border-radius: 22px;
            }

            .signup-header h2 {
                font-size: 27px;
            }

            .floating-item {
                display: none;
            }

        }

    </style>
</head>
<body>
<?php include __DIR__ . "/nav_bar.php"; ?>
<div class="signup-page">


    <!-- พื้นหลัง -->

    <div class="bg-circle circle1"></div>
    <div class="bg-circle circle2"></div>
    <div class="bg-circle circle3"></div>


    <!-- ของตกแต่งลอย -->

    <div class="floating-item item1">🍪</div>
    <div class="floating-item item2">🍫</div>
    <div class="floating-item item3">🛍️</div>
    <div class="floating-item item4">🤎</div>


    <!-- กล่องสมัครสมาชิก -->

    <div class="signup-card">


        <div class="signup-header">

            <div class="signup-icon">
                📝
            </div>

            <h2>
                สมัครสมาชิก
            </h2>

            <p>
                สร้างบัญชีเพื่อเริ่มต้นใช้งานร้านสานะกิมหยง
            </p>

        </div>


        <?php if ($error !== ''): ?>
            <p role="alert" style="color: #b42318; margin-bottom: 20px;"><?= register_escape($error) ?></p>
        <?php endif; ?>
        <form
            class="space-y-6"
            action="register.php"
            method="post"
        >
            <input type="hidden" name="csrf_token" value="<?= register_escape($_SESSION['register_csrf']) ?>">


            <!-- ชื่อ -->

            <div class="form-group">

                <label for="first_name">
                    ชื่อจริง
                </label>

                <div class="input-wrapper">

                    <input
                        id="first_name"
                        name="first_name"
                        value="<?= register_escape($values['first_name']) ?>"
                        type="text"
                        required
                        class="form-input"
                        placeholder="กรอกชื่อจริง"
                    >

                    <span>👤</span>

                </div>

            </div>


            <!-- นามสกุล -->

            <div class="form-group">

                <label for="last_name">
                    นามสกุล
                </label>

                <div class="input-wrapper">

                    <input
                        id="last_name"
                        name="last_name"
                        value="<?= register_escape($values['last_name']) ?>"
                        type="text"
                        required
                        class="form-input"
                        placeholder="กรอกนามสกุล"
                    >

                    <span>👤</span>

                </div>

            </div>


            <!-- Email -->

            <div class="form-group">

                <label for="email">
                    อีเมล
                </label>

                <div class="input-wrapper">

                    <input
                        id="email"
                        name="email"
                        value="<?= register_escape($values['email']) ?>"
                        type="email"
                        required
                        class="form-input"
                        placeholder="example@email.com"
                    >

                    <span>📧</span>

                </div>

            </div>


            <!-- เบอร์ -->

            <div class="form-group">

                <label for="phone_number">
                    เบอร์โทรศัพท์
                </label>

                <div class="input-wrapper">

                    <input
                        id="phone_number"
                        name="phone_number"
                        value="<?= register_escape($values['phone_number']) ?>"
                        type="text"
                        required
                        maxlength="10"
                        class="form-input"
                        placeholder="กรอกเบอร์โทรศัพท์"
                    >

                    <span>📱</span>

                </div>

            </div>


            <!-- Password -->

            <div class="form-group">

                <label for="password">
                    รหัสผ่าน
                </label>

                <div class="input-wrapper">

                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        class="form-input"
                        placeholder="กรอกรหัสผ่าน"
                    >

                    <span>🔒</span>

                </div>

            </div>


            <!-- Password Check -->

            <div class="form-group">

                <label for="passwordcheck">
                    ยืนยันรหัสผ่าน
                </label>

                <div class="input-wrapper">

                    <input
                        id="passwordcheck"
                        name="passwordcheck"
                        type="password"
                        required
                        class="form-input"
                        placeholder="กรอกรหัสผ่านอีกครั้ง"
                    >

                    <span>🔐</span>

                </div>

            </div>


            <!-- Address -->

            <div class="form-group">

                <label for="address">
                    ที่อยู่สำหรับจัดส่ง
                </label>

                <div class="input-wrapper">

                    <textarea
                        id="address"
                        name="address"
                        autocomplete="address"
                        required
                        class="form-input"
                        rows="4"
                        placeholder="กรอกที่อยู่สำหรับจัดส่ง"
                    ><?= register_escape($values['address']) ?></textarea>

                    <span style="top: 22px;">
                        🏠
                    </span>

                </div>

            </div>


            <!-- ปุ่ม -->

            <div>

                <button
                    type="submit"
                    name="sign_up"
                    class="signup-button"
                >
                    ✨ สมัครสมาชิก
                </button>

            </div>


        </form>


        <div class="bottom-text">

            มีบัญชีอยู่แล้ว? <a href="login.php" style="color: #9a641f; text-decoration: underline;">เข้าสู่ระบบ</a>

        </div>


    </div>

</div>



</body>
</html>
