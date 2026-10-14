<?php

session_start();
include "dbconnect.php";

// ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือยัง
if (!isset($_SESSION['user_id'])) {
    header("Location: login_form.php");
    exit();
}

// เมื่อกดส่งความคิดเห็น
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ดึง user_id จากคนที่กำลังล็อกอิน
    $user_id = (int)$_SESSION['user_id'];

    $issue_title = $_POST['issue_title'] ?? '';
    $issue_description = $_POST['issue_description'] ?? '';
    $issue_attachment = "";

    $sql = "INSERT INTO reports
            (user_id, issue_title, issue_description, issue_attachment)
            VALUES (?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if ($stmt) {

        $stmt->bind_param(
            "isss",
            $user_id,
            $issue_title,
            $issue_description,
            $issue_attachment
        );

        if ($stmt->execute()) {

            $stmt->close();

            // ส่งไปหน้าสำเร็จ
            header("Location: report_success.php");
            exit();

        } else {

            $error_message = "เกิดข้อผิดพลาดในการส่งความคิดเห็น: " . $stmt->error;
            $stmt->close();

        }

    } else {

        $error_message = "เกิดข้อผิดพลาด: " . $conn->error;

    }
}

// ค่อยเรียก Navbar หลังประมวลผล POST
include "nav_bar.php";

?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ส่งความคิดเห็น | ร้านสานะกิมหยง</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #faf6ef;
            color: #5f4a35;
        }

        .report-page {
            min-height: calc(100vh - 100px);
            padding: 50px 20px;
            position: relative;
            overflow: hidden;
        }

        .decoration {
            position: absolute;
            font-size: 55px;
            opacity: 0.25;
            animation: floating 4s ease-in-out infinite;
        }

        .decoration.one {
            left: 8%;
            top: 15%;
        }

        .decoration.two {
            right: 8%;
            bottom: 15%;
            animation-delay: 1.5s;
        }

        @keyframes floating {

            0%, 100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-12px);
            }

        }

        .content_container {

            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;

            box-shadow:
                0 10px 30px rgba(126, 91, 48, 0.12);

            margin: 10px auto;
            padding: 40px;

            max-width: 700px;
            width: 90%;

            position: relative;
            border: 1px solid #eadfce;
        }

        .title-area {
            text-align: center;
            margin-bottom: 30px;
        }

        .title-icon {

            width: 70px;
            height: 70px;

            margin: 0 auto 15px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 20px;

            background: #fff5e6;

            font-size: 35px;

            box-shadow:
                0 5px 15px rgba(154, 100, 31, 0.08);
        }

        .title-area h1 {
            margin: 0;
            color: #7f5018;
            font-size: 30px;
        }

        .title-area p {
            margin-top: 8px;
            color: #9b8a75;
            font-size: 15px;
        }

        .error-message {

            background: #fff0f0;
            color: #b42318;

            border: 1px solid #f0caca;

            padding: 12px 15px;
            border-radius: 10px;

            margin-bottom: 20px;
            text-align: center;
        }

        label {

            display: block;
            margin-bottom: 8px;

            font-weight: bold;
            color: #6b5136;
        }

        .input_align {

            width: 100%;
            box-sizing: border-box;

            padding: 13px 15px;
            margin-bottom: 22px;

            border: 1px solid #e5d8c6;
            border-radius: 12px;

            background: #fffdf9;
            color: #5f4a35;

            font-size: 15px;
            outline: none;

            transition: 0.25s;
        }

        .input_align:focus {

            border-color: #c58a3a;

            box-shadow:
                0 0 0 3px rgba(197, 138, 58, 0.12);

            background: white;
        }

        textarea.input_align {
            resize: vertical;
            min-height: 130px;
        }

        .report_button {

            width: 100%;

            padding: 13px 20px;

            border: none;
            border-radius: 12px;

            background: #9a641f;
            color: white;

            cursor: pointer;

            font-size: 16px;
            font-weight: bold;

            transition: 0.25s;

            box-shadow:
                0 5px 12px rgba(154, 100, 31, 0.2);
        }

        .report_button:hover {

            background: #7f5018;
            transform: translateY(-2px);

            box-shadow:
                0 8px 18px rgba(154, 100, 31, 0.25);
        }

        .bottom-text {

            text-align: center;

            margin-top: 20px;

            color: #a18f79;
            font-size: 13px;
        }

        @media (max-width: 600px) {

            .report-page {
                padding: 25px 10px;
            }

            .content_container {
                padding: 25px 20px;
                width: auto;
            }

            .title-area h1 {
                font-size: 25px;
            }

            .decoration {
                display: none;
            }

        }

    </style>

    <script>

        function confirmSubmission() {

            return confirm(
                "แน่ใจหรือไม่ว่าต้องการส่งความคิดเห็นนี้?"
            );

        }

    </script>

</head>

<body>

<div class="report-page">

    <div class="decoration one">🍪</div>
    <div class="decoration two">🍫</div>

    <div class="content_container">

        <div class="title-area">

            <div class="title-icon">
                💬
            </div>

            <h1>
                ส่งความคิดเห็น
            </h1>

            <p>
                สามารถส่งความคิดเห็นหรือข้อเสนอแนะให้ทางร้านได้เลย
            </p>

        </div>

        <?php if (isset($error_message)) { ?>

            <div class="error-message">
                <?php echo $error_message; ?>
            </div>

        <?php } ?>

        <form
            action=""
            method="post"
            onsubmit="return confirmSubmission()"
        >

            <label for="issue_title">
                หัวข้อความคิดเห็น
            </label>

            <input
                type="text"
                id="issue_title"
                name="issue_title"
                class="input_align"
                placeholder="เช่น อยากให้เพิ่มสินค้าใหม่"
                required
            >

            <label for="issue_description">
                รายละเอียดความคิดเห็น
            </label>

            <textarea
                id="issue_description"
                name="issue_description"
                class="input_align"
                rows="5"
                placeholder="บอกความคิดเห็นหรือข้อเสนอแนะของคุณให้เราทราบ..."
                required
            ></textarea>

            <button
                type="submit"
                class="report_button"
            >
                📨 ส่งความคิดเห็น
            </button>

        </form>

        <div class="bottom-text">
            ขอบคุณสำหรับความคิดเห็นและข้อเสนอแนะจากคุณนะครับ 🤎
        </div>

    </div>

</div>

</body>

</html>

<?php
$conn->close();
?>