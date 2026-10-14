<?php
require_once __DIR__ . '/app/bootstrap.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>เข้าสู่ระบบ | ร้านสานะกิมหยง</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            overflow: hidden;
        }

        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(2px);
            animation: floatBlob 8s ease-in-out infinite;
        }

        .blob1 {
            width: 260px;
            height: 260px;
            background: #ead2ad;
            top: -80px;
            left: -70px;
        }

        .blob2 {
            width: 220px;
            height: 220px;
            background: #e8c28d;
            right: -60px;
            bottom: -60px;
            animation-delay: 2s;
        }

        .blob3 {
            width: 130px;
            height: 130px;
            background: #f0dfc5;
            right: 15%;
            top: 15%;
            animation-delay: 4s;
        }

        @keyframes floatBlob {
            0%, 100% {
                transform: translate(0, 0);
            }

            50% {
                transform: translate(25px, -25px);
            }
        }

        .floating {
            position: absolute;
            font-size: 35px;
            animation: floating 4s ease-in-out infinite;
            user-select: none;
        }

        @keyframes floating {
            0%, 100% {
                transform: translateY(0) rotate(-5deg);
            }

            50% {
                transform: translateY(-20px) rotate(8deg);
            }
        }

        .sweet1 {
            left: 12%;
            top: 25%;
        }

        .sweet2 {
            right: 14%;
            top: 30%;
            animation-delay: 1s;
        }

        .sweet3 {
            left: 18%;
            bottom: 18%;
            animation-delay: 2s;
        }

        .sweet4 {
            right: 18%;
            bottom: 20%;
            animation-delay: 3s;
        }

        .login-card {
            animation: cardIn 0.8s ease-out;
        }

        @keyframes cardIn {
            from {
                opacity: 0;
                transform: translateY(30px) scale(.96);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .login-btn {
            transition: .3s;
        }

        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(154, 100, 31, .25);
        }

        /* ปุ่มย้อนกลับ */
        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 99999;
            background: white;
            color: #7f5018;
            border: 1px solid #eadfce;
            padding: 10px 18px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: 0.2s;
        }

        .back-btn:hover {
            background: #fffaf2;
            transform: translateY(-2px);
        }
    </style>
</head>

<body class="min-h-screen bg-[#f6f1e8]">

    <!-- ปุ่มย้อนกลับ -->
    <button
        type="button"
        onclick="history.back()"
        class="back-btn">

        ← ย้อนกลับ

    </button>


    <!-- ข้อความด้านบน -->
    <?php if (isset($_SESSION['message'])): ?>

        <div class="fixed top-5 left-1/2 -translate-x-1/2 z-50
                    bg-white border border-[#eadfce]
                    shadow-lg rounded-xl px-6 py-3 text-center">

            <p class="font-semibold text-[#7f5018]">
                <?= $_SESSION['message']; ?>
            </p>

        </div>

        <?php unset($_SESSION['message']); ?>

    <?php endif; ?>


    <!-- Background -->
    <div class="blob blob1"></div>
    <div class="blob blob2"></div>
    <div class="blob blob3"></div>


    <!-- ของตกแต่ง -->
    <div class="floating sweet1">🍫</div>
    <div class="floating sweet2">🍪</div>
    <div class="floating sweet3">🍬</div>
    <div class="floating sweet4">🧁</div>


    <!-- Login -->
    <div class="relative z-10 min-h-screen flex items-center justify-center px-4">

        <div class="login-card w-full max-w-md">

            <div class="bg-white/95 backdrop-blur-xl
                        rounded-3xl shadow-2xl
                        border border-[#eadfce] p-8">


                <!-- Logo -->
                <div class="text-center mb-8">

                    <div class="mx-auto w-20 h-20 rounded-2xl
                                bg-[#fffaf2]
                                flex items-center justify-center
                                text-5xl shadow-sm mb-4">

                        🛍️

                    </div>

                    <h1 class="text-3xl font-bold text-[#7f5018]">
                        ยินดีต้อนรับ
                    </h1>

                    <p class="text-gray-500 mt-2">
                        เข้าสู่ระบบร้านสานะกิมหยง
                    </p>

                </div>


                <!-- Form -->
                <form action="login.php" method="POST" class="space-y-5">
                    <?php csrf_field(); ?>
                    <?php if (!empty($_SESSION['login_error'])): ?>
                        <p role="alert" style="color:#b42318"><?=e($_SESSION['login_error'])?></p>
                        <?php unset($_SESSION['login_error']); ?>
                    <?php endif; ?>

                    <!-- Email -->
                    <div>

                        <label class="block text-sm font-semibold
                                      text-[#5f401f] mb-2">

                            อีเมล

                        </label>

                        <input
                            type="email"
                            name="email"
                            required
                            placeholder="กรอกอีเมลของคุณ"
                            class="w-full rounded-xl
                                   border border-[#eadfce]
                                   bg-[#fffdf9]
                                   px-4 py-3
                                   outline-none transition
                                   focus:ring-2
                                   focus:ring-[#c58a3a]
                                   focus:border-transparent">

                    </div>


                    <!-- Password -->
                    <div>

                        <label class="block text-sm font-semibold
                                      text-[#5f401f] mb-2">

                            รหัสผ่าน

                        </label>

                        <input
                            type="password"
                            name="password"
                            required
                            placeholder="กรอกรหัสผ่าน"
                            class="w-full rounded-xl
                                   border border-[#eadfce]
                                   bg-[#fffdf9]
                                   px-4 py-3
                                   outline-none transition
                                   focus:ring-2
                                   focus:ring-[#c58a3a]
                                   focus:border-transparent">

                    </div>


                    <!-- Button -->
                    <button
                        type="submit"
                        name="login"
                        class="login-btn w-full rounded-xl
                               bg-[#9a641f]
                               hover:bg-[#7f5018]
                               text-white font-semibold py-3">

                        เข้าสู่ระบบ

                    </button>

                </form>


                <!-- Register -->
                <div class="text-center mt-6">

                    <span class="text-sm text-gray-500">
                        ยังไม่มีบัญชี?
                    </span>

                    <a
                        href="register.php"
                        class="text-sm font-semibold
                               text-[#9a641f]
                               hover:underline ml-1">

                        สมัครสมาชิก

                    </a>

                </div>

            </div>

        </div>

    </div>

</body>
</html>
