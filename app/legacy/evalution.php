<?php
include "nav_bar.php";
?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ประเมินเว็บไซต์ | ร้านสานะกิมหยง</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #faf6ef;
            color: #5f4a35;
        }

        .evaluation-page {
            min-height: calc(100vh - 70px);
            padding: 50px 20px;
            position: relative;
            overflow: hidden;
        }

        /* พื้นหลัง */

        .circle {
            position: absolute;
            border-radius: 50%;
            opacity: 0.35;
            animation: float 6s ease-in-out infinite;
        }

        .circle1 {
            width: 180px;
            height: 180px;
            background: #ead7bb;
            top: 10%;
            left: -60px;
        }

        .circle2 {
            width: 230px;
            height: 230px;
            background: #f0dfc5;
            right: -80px;
            bottom: 5%;
            animation-delay: 2s;
        }

        @keyframes float {

            0%, 100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-25px);
            }

        }

        /* ของตกแต่ง */

        .floating-item {
            position: absolute;
            font-size: 45px;
            opacity: 0.3;
            animation: floating 4s ease-in-out infinite;
        }

        .item1 {
            top: 15%;
            left: 12%;
        }

        .item2 {
            top: 25%;
            right: 12%;
            animation-delay: 1s;
        }

        .item3 {
            bottom: 15%;
            left: 12%;
            animation-delay: 2s;
        }

        .item4 {
            bottom: 20%;
            right: 10%;
            animation-delay: 0.5s;
        }

        @keyframes floating {

            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }

            50% {
                transform: translateY(-15px) rotate(8deg);
            }

        }

        /* กล่อง */

        .evaluation-card {

            max-width: 650px;
            width: 100%;

            margin: auto;

            padding: 40px;

            background: rgba(255, 255, 255, 0.96);

            border: 1px solid #eadfce;

            border-radius: 25px;

            box-shadow:
                0 15px 40px rgba(126, 91, 48, 0.15);

            position: relative;
            z-index: 5;

            animation: appear 0.8s ease;

        }

        @keyframes appear {

            from {
                opacity: 0;
                transform: translateY(35px) scale(0.97);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }

        }

        /* หัวข้อ */

        .title-area {
            text-align: center;
            margin-bottom: 30px;
        }

        .title-icon {

            width: 75px;
            height: 75px;

            margin: 0 auto 15px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #fff5e6;

            border-radius: 22px;

            font-size: 40px;

            animation: iconFloat 3s ease-in-out infinite;

        }

        @keyframes iconFloat {

            0%, 100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }

        }

        .title-area h1 {
            margin: 0;
            color: #7f5018;
            font-size: 30px;
        }

        .title-area p {
            color: #9b8a75;
            font-size: 14px;
        }

        /* คำถาม */

        .question {
            margin-bottom: 28px;
        }

        .question-title {
            display: block;
            margin-bottom: 12px;
            font-weight: bold;
            color: #6b5136;
        }

        /* ดาว */

        .stars {
            display: flex;
            justify-content: center;
            gap: 8px;
            direction: rtl;
        }

        .stars input {
            display: none;
        }

        .stars label {

            font-size: 45px;

            color: #d8d0c5;

            cursor: pointer;

            transition: 0.2s;

        }

        .stars label:hover,
        .stars label:hover ~ label,
        .stars input:checked ~ label {

            color: #f2b01e;

            transform: scale(1.12);

        }

        /* ตัวเลือก */

        .option-group {
            display: grid;
            gap: 10px;
        }

        .option {

            padding: 13px 15px;

            border: 1px solid #e5d8c6;

            border-radius: 12px;

            background: #fffdf9;

            cursor: pointer;

            transition: 0.25s;

        }

        .option:hover {

            border-color: #c58a3a;

            background: #fff8ed;

            transform: translateX(4px);

        }

        .option input {
            margin-right: 8px;
        }

        /* textarea */

        textarea {

            width: 100%;

            min-height: 120px;

            padding: 14px;

            border: 1px solid #e5d8c6;

            border-radius: 12px;

            background: #fffdf9;

            resize: vertical;

            outline: none;

            font-size: 15px;

        }

        textarea:focus {

            border-color: #c58a3a;

            box-shadow:
                0 0 0 4px rgba(197, 138, 58, 0.12);

        }

        /* ปุ่ม */

        .submit-button {

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

            transition: 0.3s;

        }

        .submit-button:hover {

            transform: translateY(-3px);

            box-shadow:
                0 10px 20px rgba(154, 100, 31, 0.25);

        }

        .bottom-text {

            text-align: center;

            margin-top: 20px;

            color: #a18f79;

            font-size: 13px;

        }

        @media (max-width: 600px) {

            .evaluation-card {
                padding: 28px 20px;
            }

            .floating-item {
                display: none;
            }

        }

    </style>

</head>


<body>

<div class="evaluation-page">


    <!-- พื้นหลัง -->

    <div class="circle circle1"></div>
    <div class="circle circle2"></div>


    <!-- ของตกแต่ง -->

    <div class="floating-item item1">🍪</div>
    <div class="floating-item item2">⭐</div>
    <div class="floating-item item3">🍫</div>
    <div class="floating-item item4">🤎</div>


    <div class="evaluation-card">


        <div class="title-area">

            <div class="title-icon">
                ⭐
            </div>

            <h1>
                ประเมินเว็บไซต์
            </h1>

            <p>
                ความคิดเห็นของคุณช่วยให้เราพัฒนาเว็บไซต์ให้ดียิ่งขึ้น
            </p>

        </div>


        <form id="evaluationForm">


            <!-- คะแนน -->

            <div class="question">

                <span class="question-title">
                    1. คุณพึงพอใจกับเว็บไซต์ของเรามากแค่ไหน?
                </span>

                <div class="stars">

                    <input
                        type="radio"
                        id="star5"
                        name="rating"
                        value="5"
                        required
                    >
                    <label for="star5">★</label>

                    <input
                        type="radio"
                        id="star4"
                        name="rating"
                        value="4"
                    >
                    <label for="star4">★</label>

                    <input
                        type="radio"
                        id="star3"
                        name="rating"
                        value="3"
                    >
                    <label for="star3">★</label>

                    <input
                        type="radio"
                        id="star2"
                        name="rating"
                        value="2"
                    >
                    <label for="star2">★</label>

                    <input
                        type="radio"
                        id="star1"
                        name="rating"
                        value="1"
                    >
                    <label for="star1">★</label>

                </div>

            </div>


            <!-- ความง่าย -->

            <div class="question">

                <span class="question-title">
                    2. เว็บไซต์ใช้งานง่ายหรือไม่?
                </span>

                <div class="option-group">

                    <label class="option">
                        <input
                            type="radio"
                            name="easy"
                            value="มากที่สุด"
                            required
                        >
                        มากที่สุด
                    </label>

                    <label class="option">
                        <input
                            type="radio"
                            name="easy"
                            value="มาก"
                        >
                        มาก
                    </label>

                    <label class="option">
                        <input
                            type="radio"
                            name="easy"
                            value="ปานกลาง"
                        >
                        ปานกลาง
                    </label>

                    <label class="option">
                        <input
                            type="radio"
                            name="easy"
                            value="น้อย"
                        >
                        น้อย
                    </label>

                    <label class="option">
                        <input
                            type="radio"
                            name="easy"
                            value="น้อยที่สุด"
                        >
                        น้อยที่สุด
                    </label>

                </div>

            </div>


            <!-- ความคิดเห็น -->

            <div class="question">

                <label
                    for="comment"
                    class="question-title"
                >
                    3. ข้อเสนอแนะเพิ่มเติม
                </label>

                <textarea
                    id="comment"
                    name="comment"
                    placeholder="สามารถเขียนความคิดเห็นหรือข้อเสนอแนะได้ที่นี่..."
                ></textarea>

            </div>


            <button
                type="submit"
                class="submit-button"
            >
                ⭐ ส่งแบบประเมิน
            </button>


        </form>


        <div class="bottom-text">
            ขอบคุณสำหรับความคิดเห็นของคุณ 🤎
        </div>


    </div>

</div>


<script>

document.getElementById("evaluationForm").addEventListener("submit", function(e) {

    e.preventDefault();

    alert(
        "ขอบคุณสำหรับการประเมินเว็บไซต์ครับ 🤎\n\n" +
        "ความคิดเห็นของคุณจะช่วยให้เราพัฒนาเว็บไซต์ให้ดียิ่งขึ้น"
    );

    this.reset();

});

</script>


</body>

</html>