<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ช่องทางการติดต่อ | สานะกิมหยง</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            background: #f6f1e8;
        }

        .brown {
            color: #9a641f;
        }

        .contact-card {
            transition: all .25s ease;
        }

        .contact-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(91, 70, 45, .12);
        }
    </style>
</head>

<body>

<?php include "nav_bar.php"; ?>

<div class="max-w-6xl mx-auto px-4 py-8">

    <!-- =========================
         HEADER
    ========================= -->

    <div class="bg-white rounded-3xl border border-[#eadfce] shadow-sm overflow-hidden mb-8">

        <div class="bg-gradient-to-r from-[#fffaf2] to-[#f3e5cf] px-6 py-10 text-center">

            <div class="flex justify-center mb-5">

                <div class="w-28 h-28 bg-white rounded-full p-2 shadow-md">
                    <img
                        src="pik/logo.png"
                        alt="ร้านสานะกิมหยง"
                        class="w-full h-full object-cover rounded-full">
                </div>

            </div>

            <h1 class="text-3xl font-bold text-gray-800">
                ติดต่อเรา
            </h1>

            <p class="text-gray-500 mt-2">
                ร้านสานะกิมหยง
            </p>

            <p class="text-sm text-gray-400 mt-1">
                สามารถติดต่อสอบถามข้อมูลหรือสั่งซื้อสินค้าได้ตามช่องทางด้านล่าง
            </p>

        </div>

    </div>


    <!-- =========================
         CONTACT CHANNEL
    ========================= -->

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">

        <!-- Facebook -->

        <a
            href="https://web.facebook.com/profile.php?id=100063548891339"
            target="_blank"
            rel="noopener noreferrer"
            class="contact-card bg-white rounded-2xl border border-[#eadfce] p-6 shadow-sm">

            <div class="flex items-center gap-4">

                <div class="w-14 h-14 rounded-2xl bg-blue-50 flex items-center justify-center text-2xl">
                    📘
                </div>

                <div>

                    <p class="text-sm text-gray-400">
                        Facebook
                    </p>

                    <h2 class="font-bold text-gray-800 mt-1">
                        ร้านสานะกิมหยง
                    </h2>

                </div>

            </div>

            <p class="text-sm text-gray-500 mt-4">
                ติดต่อสอบถามและติดตามข่าวสารของร้าน
            </p>

        </a>


        <!-- Gmail -->

        <a
            href="mailto:sanahkimyongonlline@gmail.com"
            class="contact-card bg-white rounded-2xl border border-[#eadfce] p-6 shadow-sm">

            <div class="flex items-center gap-4">

                <div class="w-14 h-14 rounded-2xl bg-red-50 flex items-center justify-center text-2xl">
                    ✉️
                </div>

                <div>

                    <p class="text-sm text-gray-400">
                        Gmail
                    </p>

                    <h2 class="font-bold text-gray-800 mt-1 break-all">
                        sanahkimyongonlline@gmail.com
                    </h2>

                </div>

            </div>

            <p class="text-sm text-gray-500 mt-4">
                ส่งข้อความหรือสอบถามรายละเอียดผ่านอีเมล
            </p>

        </a>


        <!-- Phone -->

        <a
            href="tel:0874753382"
            class="contact-card bg-white rounded-2xl border border-[#eadfce] p-6 shadow-sm">

            <div class="flex items-center gap-4">

                <div class="w-14 h-14 rounded-2xl bg-green-50 flex items-center justify-center text-2xl">
                    📞
                </div>

                <div>

                    <p class="text-sm text-gray-400">
                        โทรศัพท์
                    </p>

                    <h2 class="font-bold text-gray-800 mt-1">
                        087-475-3382
                    </h2>

                </div>

            </div>

            <p class="text-sm text-gray-500 mt-4">
                กดเพื่อโทรติดต่อร้านโดยตรง
            </p>

        </a>

    </div>


    <!-- =========================
         CONTACT INFO
    ========================= -->

    <div class="bg-white rounded-3xl border border-[#eadfce] shadow-sm p-6 mb-8">

        <div class="text-center mb-6">

            <p class="text-sm font-semibold brown">
                CONTACT US
            </p>

            <h2 class="text-2xl font-bold text-gray-800 mt-1">
                ช่องทางการติดต่อ
            </h2>

            <p class="text-gray-500 text-sm mt-2">
                เลือกช่องทางที่สะดวกสำหรับการติดต่อร้าน
            </p>

        </div>

        <div class="space-y-4">

            <div class="flex items-center gap-4 bg-[#f6f1e8] rounded-2xl p-4">

                <div class="w-11 h-11 bg-white rounded-xl flex items-center justify-center">
                    📘
                </div>

                <div>

                    <p class="text-xs text-gray-400">
                        Facebook
                    </p>

                    <p class="font-semibold text-gray-700">
                        ร้านสานะกิมหยง
                    </p>

                </div>

            </div>


            <div class="flex items-center gap-4 bg-[#f6f1e8] rounded-2xl p-4">

                <div class="w-11 h-11 bg-white rounded-xl flex items-center justify-center">
                    ✉️
                </div>

                <div>

                    <p class="text-xs text-gray-400">
                        Email
                    </p>

                    <p class="font-semibold text-gray-700 break-all">
                        sanahkimyongonlline@gmail.com
                    </p>

                </div>

            </div>


            <div class="flex items-center gap-4 bg-[#f6f1e8] rounded-2xl p-4">

                <div class="w-11 h-11 bg-white rounded-xl flex items-center justify-center">
                    📞
                </div>

                <div>

                    <p class="text-xs text-gray-400">
                        โทรศัพท์
                    </p>

                    <p class="font-semibold text-gray-700">
                        087-475-3382
                    </p>

                </div>

            </div>

        </div>

    </div>


    <!-- =========================
         MAP
    ========================= -->

    <div class="bg-white rounded-3xl border border-[#eadfce] shadow-sm overflow-hidden">

        <div class="p-6">

            <p class="text-sm font-semibold brown">
                LOCATION
            </p>

            <h2 class="text-2xl font-bold text-gray-800 mt-1">
                ที่ตั้งร้าน
            </h2>

            <p class="text-gray-500 text-sm mt-2">
                สามารถดูตำแหน่งร้านสานะกิมหยงผ่าน Google Maps
            </p>

        </div>

        <div class="relative overflow-hidden">

            <iframe
                src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d255.32320860957412!2d100.4696712867008!3d7.007701892124688!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x304d285b34cd82b9%3A0x1337623210bba75d!2sKim%20Yong%20Market!5e0!3m2!1sen!2sth!4v1732388839747!5m2!1sen!2sth"
                class="w-full h-[450px]"
                frameborder="0"
                allowfullscreen
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>

        </div>

    </div>


    <!-- =========================
         FOOTER TEXT
    ========================= -->

    <div class="text-center py-8">

        <p class="text-sm text-gray-400">
            ขอบคุณที่สนใจร้านสานะกิมหยง ❤️
        </p>

    </div>

</div>

</body>
</html>