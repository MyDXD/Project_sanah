<?php
include "nav_bar.php";
?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ส่งความคิดเห็นสำเร็จ | ร้านสานะกิมหยง</title>

    <style>

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #faf6ef;
            color: #5f4a35;
        }

        .success-page {

            min-height: calc(100vh - 100px);

            display: flex;
            justify-content: center;
            align-items: center;

            padding: 30px 20px;
        }

        .success-box {

            width: 90%;
            max-width: 600px;

            background: white;

            padding: 50px 35px;

            border-radius: 25px;

            text-align: center;

            border: 1px solid #eadfce;

            box-shadow:
                0 10px 30px rgba(126, 91, 48, 0.12);
        }

        .success-icon {

            width: 90px;
            height: 90px;

            margin: 0 auto 20px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: #fff5e6;

            color: #9a641f;

            font-size: 50px;
        }

        h1 {

            color: #7f5018;

            margin-bottom: 10px;
        }

        .main-text {

            font-size: 22px;
            font-weight: bold;

            color: #6b5136;
        }

        .sub-text {

            color: #9b8a75;

            margin-top: 10px;

            line-height: 1.7;
        }

        .button-group {

            margin-top: 30px;

            display: flex;

            justify-content: center;

            gap: 12px;

            flex-wrap: wrap;
        }

        .btn {

            text-decoration: none;

            padding: 12px 22px;

            border-radius: 10px;

            font-weight: bold;
        }

        .btn-comment {

            background: #9a641f;
            color: white;
        }

        .btn-comment:hover {
            background: #7f5018;
        }

        .btn-home {

            background: #f4eadc;
            color: #7f5018;
        }

        .btn-home:hover {
            background: #eadbc5;
        }

    </style>

</head>

<body>

<div class="success-page">

    <div class="success-box">

        <div class="success-icon">
            ✓
        </div>

        <h1>
            ความคิดเห็นของคุณ
        </h1>

        <div class="main-text">
            ได้ส่งเรียบร้อยแล้ว
        </div>

        <div class="sub-text">

            ทางร้านได้รับความคิดเห็นของคุณเรียบร้อยแล้วครับ

            <br>

            ขอบคุณสำหรับความคิดเห็นและข้อเสนอแนะจากคุณครับ

        </div>

        <div class="button-group">

            <a
                href="report_user.php"
                class="btn btn-comment"
            >
                💬 ส่งความคิดเห็นเพิ่มเติม
            </a>

            <a
                href="index.php"
                class="btn btn-home"
            >
                🏠 กลับหน้าหลัก
            </a>

        </div>

    </div>

</div>

</body>

</html>