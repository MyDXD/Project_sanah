<?php
include 'dbconnect.php';

$recommendedProducts = [
    [
        'image' => './product_pic/product_recom/5.jpg',
        'name' => 'Mallows chocolate',
        'description' => 'The sweet of Chocolate'
    ],
    [
        'image' => './product_pic/product_recom/6.jpg',
        'name' => 'Nutella',
        'description' => 'The Magic of Chocolate'
    ]
];

$totalProducts = count($recommendedProducts);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>สินค้าแนะนำ</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            background-color: #faf8f3;
        }

        .slide {
            transition: opacity 0.4s ease;
        }


        /* =================================
           ของตกแต่งด้านข้างเว็บไซต์
        ================================== */

        .side-decoration {
            position: fixed;
            z-index: 5;
            pointer-events: none;
            user-select: none;
        }

        .side-decoration-left {
            left: 20px;
            top: 45%;
            transform: translateY(-50%);
        }

        .side-decoration-right {
            right: 20px;
            top: 45%;
            transform: translateY(-50%);
        }

        .side-decoration img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            filter: drop-shadow(0 8px 12px rgba(0, 0, 0, 0.12));
            animation: floating 4s ease-in-out infinite;
        }

        .side-decoration-right img {
            animation-delay: 1.5s;
        }

        @keyframes floating {

            0% {
                transform: translateY(0px) rotate(-3deg);
            }

            50% {
                transform: translateY(-12px) rotate(3deg);
            }

            100% {
                transform: translateY(0px) rotate(-3deg);
            }

        }


        /* หน้าจอขนาดกลาง */

        @media (max-width: 1200px) {

            .side-decoration {
                display: none;
            }

        }
    </style>
</head>

<body>


    <!-- =================================
         ของตกแต่งด้านซ้าย
    ================================== -->

    <div class="side-decoration side-decoration-left">

        <img
            src="./product_pic/product_recom/5.jpg"
            alt="">

    </div>


    <!-- =================================
         ของตกแต่งด้านขวา
    ================================== -->

    <div class="side-decoration side-decoration-right">

        <img
            src="./product_pic/product_recom/6.jpg"
            alt="">

    </div>


    <!--
        ไม่ต้อง include nav_bar.php ตรงนี้
        เพราะหน้า index.php ของคุณมี Navbar อยู่แล้ว
    -->


    <!-- =========================
         สินค้าแนะนำ
    ========================== -->

    <section class="max-w-6xl mx-auto px-4 py-10 md:py-14">

        <!-- หัวข้อ -->
        <div class="text-center mb-8">

            <p class="text-sm font-semibold tracking-[0.25em] text-amber-700 uppercase">
                Recommended
            </p>

            <h1 class="text-3xl md:text-4xl font-bold text-stone-800 mt-2">
                สินค้าแนะนำ
            </h1>

            <p class="text-stone-500 mt-2 text-sm md:text-base">
                สินค้าที่คัดสรรมาเป็นพิเศษจากทางร้าน
            </p>

        </div>


        <!-- =========================
             Carousel
        ========================== -->

        <?php if ($totalProducts > 0): ?>

            <div class="relative">

                <div
                    id="carousel"
                    class="relative w-full h-[280px] sm:h-[340px] md:h-[400px] overflow-hidden rounded-3xl shadow-md bg-white">

                    <?php foreach ($recommendedProducts as $index => $product): ?>

                        <div
                            class="slide absolute inset-0 <?php echo ($index === 0) ? '' : 'hidden'; ?>"
                            data-slide="<?php echo $index; ?>">

                            <!-- รูป -->
                            <img
                                src="<?php echo htmlspecialchars($product['image']); ?>"
                                alt="<?php echo htmlspecialchars($product['name']); ?>"
                                class="absolute inset-0 w-full h-full object-cover">


                            <!-- พื้นหลังมืดด้านล่าง -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/65 via-black/10 to-transparent">
                            </div>


                            <!-- ข้อมูลสินค้า -->
                            <div class="absolute bottom-0 left-0 right-0 p-6 md:p-10 text-white">

                                <span class="inline-block bg-amber-600 px-3 py-1 rounded-full text-xs font-medium mb-3">
                                    สินค้าแนะนำ
                                </span>

                                <h2 class="text-2xl md:text-4xl font-bold">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </h2>

                                <p class="mt-1 text-sm md:text-base text-white/90">
                                    <?php echo htmlspecialchars($product['description']); ?>
                                </p>

                            </div>

                        </div>

                    <?php endforeach; ?>


                    <?php if ($totalProducts > 1): ?>

                        <!-- ปุ่มก่อนหน้า -->
                        <button
                            type="button"
                            id="prevButton"
                            aria-label="สินค้าก่อนหน้า"
                            class="absolute left-4 top-1/2 -translate-y-1/2
                                   w-10 h-10 md:w-12 md:h-12
                                   rounded-full bg-white/90
                                   hover:bg-white
                                   shadow-md
                                   flex items-center justify-center
                                   text-stone-700
                                   transition
                                   hover:scale-105">

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-5 h-5 md:w-6 md:h-6"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor">

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 19l-7-7 7-7" />

                            </svg>

                        </button>


                        <!-- ปุ่มถัดไป -->
                        <button
                            type="button"
                            id="nextButton"
                            aria-label="สินค้าถัดไป"
                            class="absolute right-4 top-1/2 -translate-y-1/2
                                   w-10 h-10 md:w-12 md:h-12
                                   rounded-full bg-white/90
                                   hover:bg-white
                                   shadow-md
                                   flex items-center justify-center
                                   text-stone-700
                                   transition
                                   hover:scale-105">

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-5 h-5 md:w-6 md:h-6"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor">

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5l7 7-7 7" />

                            </svg>

                        </button>

                    <?php endif; ?>

                </div>


                <!-- จุดบอกตำแหน่ง -->
                <?php if ($totalProducts > 1): ?>

                    <div class="flex justify-center items-center gap-2 mt-5">

                        <?php foreach ($recommendedProducts as $index => $product): ?>

                            <button
                                type="button"
                                data-indicator="<?php echo $index; ?>"
                                aria-label="แสดงสินค้า <?php echo $index + 1; ?>"
                                class="indicator h-2 rounded-full transition-all duration-300
                                <?php echo ($index === 0)
                                    ? 'w-8 bg-amber-600'
                                    : 'w-2 bg-stone-300'; ?>">
                            </button>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </div>

        <?php else: ?>

            <!-- กรณีไม่มีสินค้า -->
            <div class="bg-white rounded-3xl shadow-sm p-16 text-center">

                <div class="text-5xl mb-4">
                    🛍️
                </div>

                <h2 class="text-xl font-semibold text-stone-700">
                    ยังไม่มีสินค้าแนะนำ
                </h2>

                <p class="text-stone-500 mt-2">
                    กรุณาเพิ่มสินค้าแนะนำ
                </p>

            </div>

        <?php endif; ?>

    </section>


    <!-- =========================
         JavaScript
    ========================== -->

    <?php if ($totalProducts > 1): ?>

        <script>

            const slides = document.querySelectorAll('[data-slide]');
            const indicators = document.querySelectorAll('[data-indicator]');

            const nextButton = document.getElementById('nextButton');
            const prevButton = document.getElementById('prevButton');

            let currentIndex = 0;


            function showSlide(index) {

                slides.forEach((slide, i) => {

                    if (i === index) {
                        slide.classList.remove('hidden');
                    } else {
                        slide.classList.add('hidden');
                    }

                });


                indicators.forEach((indicator, i) => {

                    if (i === index) {

                        indicator.classList.remove(
                            'w-2',
                            'bg-stone-300'
                        );

                        indicator.classList.add(
                            'w-8',
                            'bg-amber-600'
                        );

                    } else {

                        indicator.classList.remove(
                            'w-8',
                            'bg-amber-600'
                        );

                        indicator.classList.add(
                            'w-2',
                            'bg-stone-300'
                        );

                    }

                });

            }


            function nextSlide() {

                currentIndex++;

                if (currentIndex >= slides.length) {
                    currentIndex = 0;
                }

                showSlide(currentIndex);

            }


            function prevSlide() {

                currentIndex--;

                if (currentIndex < 0) {
                    currentIndex = slides.length - 1;
                }

                showSlide(currentIndex);

            }


            nextButton.addEventListener('click', nextSlide);

            prevButton.addEventListener('click', prevSlide);


            indicators.forEach((indicator, index) => {

                indicator.addEventListener('click', function () {

                    currentIndex = index;

                    showSlide(currentIndex);

                });

            });


            // เปลี่ยนอัตโนมัติทุก 5 วินาที
            setInterval(nextSlide, 5000);

        </script>

    <?php endif; ?>

</body>

</html>