<?php
session_start();
include "../dbconnect.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login_form.php");
    exit();
}

/* บันทึกคำตอบ */
if (isset($_POST['reply_report'])) {

    $report_id = (int)$_POST['report_id'];
    $admin_reply = trim($_POST['admin_reply']);
    $admin_id = (int)($_SESSION['user_id'] ?? 0);

    if ($admin_reply != "") {

        $stmt = $conn->prepare("
            UPDATE reports
            SET admin_reply = ?,
                report_status = 'ตอบกลับแล้ว',
                replied_at = NOW(),
                admin_id = ?
            WHERE report_id = ?
        ");

        $stmt->bind_param(
            "sii",
            $admin_reply,
            $admin_id,
            $report_id
        );

        $stmt->execute();
        $stmt->close();
    }

    header("Location: report_admin.php");
    exit();
}

/* ดึงรายงานทั้งหมด */
$sql = "SELECT * FROM reports ORDER BY issue_date DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>หัวข้อรายงาน</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

<div class="flex w-full min-h-screen">

    <?php include "sidebar.php"; ?>

    <div class="flex-1 p-6">

        <h1 class="text-3xl font-bold mb-6">
            📝 หัวข้อรายงาน
        </h1>

        <?php while ($report = mysqli_fetch_assoc($result)): ?>

            <div class="bg-white rounded-xl shadow p-6 mb-5">

                <div class="flex justify-between">

                    <h2 class="text-xl font-bold">
                        <?= htmlspecialchars($report['issue_title']) ?>
                    </h2>

                    <span class="px-3 py-1 rounded-full text-sm
                        <?= $report['report_status'] == 'ตอบกลับแล้ว'
                            ? 'bg-green-100 text-green-700'
                            : 'bg-yellow-100 text-yellow-700' ?>">

                        <?= htmlspecialchars($report['report_status']) ?>

                    </span>

                </div>

                <p class="mt-3 text-gray-700">
                    <?= nl2br(htmlspecialchars($report['issue_description'])) ?>
                </p>

                <p class="text-sm text-gray-400 mt-2">
                    📅 <?= htmlspecialchars($report['issue_date']) ?>
                </p>


                <?php if (!empty($report['admin_reply'])): ?>

                    <div class="bg-gray-100 rounded-lg p-4 mt-4">

                        <b>💬 คำตอบจากแอดมิน</b>

                        <p class="mt-2">
                            <?= nl2br(htmlspecialchars($report['admin_reply'])) ?>
                        </p>

                        <p class="text-sm text-gray-400 mt-2">
                            <?= htmlspecialchars($report['replied_at']) ?>
                        </p>

                    </div>

                <?php endif; ?>


                <form method="POST" class="mt-4">

                    <input
                        type="hidden"
                        name="report_id"
                        value="<?= $report['report_id'] ?>"
                    >

                    <textarea
                        name="admin_reply"
                        rows="3"
                        class="w-full border rounded-lg p-3"
                        placeholder="พิมพ์ข้อความตอบกลับลูกค้า..."
                        required
                    ></textarea>

                    <button
                        type="submit"
                        name="reply_report"
                        class="mt-3 bg-gray-800 text-white px-5 py-2 rounded-lg hover:bg-gray-700"
                    >
                        💬 ตอบกลับ
                    </button>

                </form>

            </div>

        <?php endwhile; ?>

    </div>

</div>

</body>
</html>