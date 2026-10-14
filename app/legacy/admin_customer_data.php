<?php
include "../dbconnect.php";

$sql = "SELECT * FROM userdata";
$result = $conn->query($sql);

session_start();

// ตรวจสอบสิทธิ์ผู้ดูแลระบบ
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login_form.php");
    exit();
}

// นับจำนวนผู้ใช้
$totalUsers = 0;
$totalAdmins = 0;
$totalCustomers = 0;

$countResult = $conn->query("SELECT * FROM userdata");

if ($countResult) {
    while ($user = $countResult->fetch_assoc()) {

        $totalUsers++;

        if ($user['role'] === 'admin') {
            $totalAdmins++;
        } else {
            $totalCustomers++;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>จัดการผู้ใช้ | สานะกิมหยง</title>

    <link
        href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css"
        rel="stylesheet">

    <style>

        body {
            background: #f6f1e8;
            font-family: Arial, sans-serif;
        }

        /* =========================
           Header
        ========================= */

        .page-header {
            background: linear-gradient(
                135deg,
                #fffaf2,
                #f3e5cf
            );

            border: 1px solid #eadfce;

            border-radius: 24px;

            padding: 28px 30px;

            margin-bottom: 22px;

            position: relative;

            overflow: hidden;

            box-shadow:
                0 10px 30px rgba(91,70,45,.08);
        }

        .page-header::after {
            content: "✦";

            position: absolute;

            right: 35px;

            top: -10px;

            font-size: 90px;

            color: rgba(180,120,50,.08);
        }

        /* =========================
           Stats
        ========================= */

        .stat-card {
            background: white;

            border: 1px solid #eee5d8;

            border-radius: 20px;

            padding: 20px;

            box-shadow:
                0 8px 25px rgba(91,70,45,.07);

            transition: .25s;
        }

        .stat-card:hover {
            transform: translateY(-5px);

            box-shadow:
                0 15px 35px rgba(91,70,45,.12);
        }

        .stat-icon {
            width: 50px;

            height: 50px;

            border-radius: 15px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 23px;

            background: #fff3d9;
        }

        /* =========================
           Search
        ========================= */

        .search-box {
            background: white;

            border: 1px solid #e5d9c8;

            border-radius: 14px;

            padding: 12px 16px 12px 42px;

            outline: none;

            transition: .2s;
        }

        .search-box:focus {
            border-color: #c28a43;

            box-shadow:
                0 0 0 4px rgba(194,138,67,.12);
        }

        /* =========================
           Add User
        ========================= */

        .add-user {
            background: #9a641f;

            color: white;

            padding: 12px 18px;

            border-radius: 14px;

            font-size: 14px;

            font-weight: 700;

            transition: .2s;

            box-shadow:
                0 5px 15px rgba(154,100,31,.20);
        }

        .add-user:hover {
            background: #7f5018;

            transform: translateY(-2px);
        }

        /* =========================
           User Table
        ========================= */

        .table-card {
            background: white;

            border: 1px solid #eadfce;

            border-radius: 24px;

            overflow: hidden;

            box-shadow:
                0 10px 30px rgba(91,70,45,.07);
        }

        .table-header {
            background: #faf7f1;
        }

        .table-header th {
            padding: 16px;

            font-size: 12px;

            color: #7c7062;

            font-weight: 700;

            white-space: nowrap;
        }

        .user-row {
            transition: .2s;
        }

        .user-row:hover {
            background: #fffaf2;

            box-shadow:
                inset 4px 0 0 #c28a43;
        }

        .user-row td {
            padding: 15px 16px;

            border-bottom: 1px solid #f0ebe3;

            vertical-align: middle;
        }

        /* =========================
           User ID
        ========================= */

        .user-id {
            width: 36px;

            height: 36px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 10px;

            background: #fff3dc;

            color: #9a641f;

            font-size: 12px;

            font-weight: 700;
        }

        /* =========================
           Avatar
        ========================= */

        .user-avatar {
            width: 42px;

            height: 42px;

            border-radius: 13px;

            background: #f8ead5;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 20px;

            margin-right: 10px;
        }

        /* =========================
           Role
        ========================= */

        .role-admin {
            display: inline-block;

            background: #fff0e0;

            color: #b45309;

            padding: 6px 11px;

            border-radius: 999px;

            font-size: 12px;

            font-weight: 700;
        }

        .role-user {
            display: inline-block;

            background: #edf4ff;

            color: #2563eb;

            padding: 6px 11px;

            border-radius: 999px;

            font-size: 12px;

            font-weight: 700;
        }

        /* =========================
           Buttons
        ========================= */

        .edit-btn {
            background: #edf4ff;

            color: #2563eb;

            padding: 8px 12px;

            border-radius: 10px;

            font-size: 12px;

            font-weight: 700;

            transition: .2s;
        }

        .edit-btn:hover {
            background: #2563eb;

            color: white;
        }

        .delete-btn {
            background: #fff0f0;

            color: #dc2626;

            padding: 8px 12px;

            border-radius: 10px;

            font-size: 12px;

            font-weight: 700;

            transition: .2s;
        }

        .delete-btn:hover {
            background: #dc2626;

            color: white;
        }

        /* =========================
           Empty
        ========================= */

        .empty-box {
            padding: 60px 20px;

            text-align: center;
        }

        /* =========================
           Animation
        ========================= */

        .fade-in {
            animation: fadeIn .5s ease;
        }

        @keyframes fadeIn {

            from {
                opacity: 0;

                transform: translateY(12px);
            }

            to {
                opacity: 1;

                transform: translateY(0);
            }

        }

        /* =========================
           Scrollbar
        ========================= */

        ::-webkit-scrollbar {
            width: 8px;

            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #eee8de;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbbda9;

            border-radius: 10px;
        }

    </style>

</head>


<body>

<div class="flex w-full h-screen">


    <!-- Sidebar -->

    <?php include 'sidebar.php'; ?>


    <!-- Main Content -->

    <div class="flex-1 p-6 overflow-y-auto h-screen">

        <div class="max-w-7xl mx-auto fade-in">


            <!-- =========================
                 HEADER
            ========================= -->

            <div class="page-header">

                <div class="relative z-10">

                    <p class="text-sm font-bold tracking-widest text-yellow-700 mb-2">
                        ADMIN PANEL
                    </p>

                    <h1 class="text-3xl font-bold text-gray-800">
                        ข้อมูลผู้ใช้ในระบบ
                    </h1>

                    <p class="text-gray-500 mt-2">
                        จัดการข้อมูลลูกค้าและผู้ดูแลระบบ
                    </p>

                </div>

            </div>


            <!-- =========================
                 STATS
            ========================= -->

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">


                <!-- Total Users -->

                <div class="stat-card">

                    <div class="flex items-center justify-between">

                        <div>

                            <p class="text-sm text-gray-500">
                                ผู้ใช้ทั้งหมด
                            </p>

                            <h2 class="text-3xl font-bold text-gray-800 mt-2">

                                <?= number_format($totalUsers) ?>

                            </h2>

                            <p class="text-xs text-gray-400 mt-2">
                                สมาชิกในระบบ
                            </p>

                        </div>

                        <div class="stat-icon">
                            👥
                        </div>

                    </div>

                </div>


                <!-- Customers -->

                <div class="stat-card">

                    <div class="flex items-center justify-between">

                        <div>

                            <p class="text-sm text-gray-500">
                                ลูกค้า
                            </p>

                            <h2 class="text-3xl font-bold text-gray-800 mt-2">

                                <?= number_format($totalCustomers) ?>

                            </h2>

                            <p class="text-xs text-gray-400 mt-2">
                                ผู้ใช้งานทั่วไป
                            </p>

                        </div>

                        <div class="stat-icon">
                            🛍️
                        </div>

                    </div>

                </div>


                <!-- Admin -->

                <div class="stat-card">

                    <div class="flex items-center justify-between">

                        <div>

                            <p class="text-sm text-gray-500">
                                ผู้ดูแลระบบ
                            </p>

                            <h2 class="text-3xl font-bold text-gray-800 mt-2">

                                <?= number_format($totalAdmins) ?>

                            </h2>

                            <p class="text-xs text-orange-600 mt-2 font-medium">
                                🔐 Admin
                            </p>

                        </div>

                        <div class="stat-icon">
                            🛡️
                        </div>

                    </div>

                </div>

            </div>


            <!-- =========================
                 SEARCH + ADD
            ========================= -->

            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-5">


                <!-- Search -->

                <form
                    action=""
                    method="GET"
                    class="relative w-full md:w-96">

                    <span
                        class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400">

                        🔎

                    </span>

                    <input
                        type="text"
                        name="search"
                        placeholder="ค้นหาผู้ใช้..."
                        class="search-box w-full">

                </form>


                <!-- Add User -->

                <a
                    href="create_customer.php"
                    class="add-user inline-flex items-center justify-center">

                    <span class="mr-2 text-lg">
                        ＋
                    </span>

                    เพิ่มผู้ใช้

                </a>

            </div>


            <!-- =========================
                 TABLE
            ========================= -->

            <div class="table-card">


                <!-- Table Header -->

                <div class="px-6 py-5 border-b border-gray-100 bg-white">

                    <div class="flex items-center justify-between">

                        <div>

                            <h2 class="text-xl font-bold text-gray-800">
                                รายชื่อผู้ใช้
                            </h2>

                            <p class="text-sm text-gray-400 mt-1">
                                ข้อมูลสมาชิกและสิทธิ์การใช้งาน
                            </p>

                        </div>

                        <div class="text-2xl">
                            👥
                        </div>

                    </div>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-full">

                        <thead class="table-header">

                            <tr>

                                <th class="text-center">
                                    ID
                                </th>

                                <th class="text-left">
                                    ผู้ใช้งาน
                                </th>

                                <th class="text-left">
                                    Email
                                </th>

                                <th class="text-left">
                                    ที่อยู่
                                </th>

                                <th class="text-left">
                                    เบอร์โทร
                                </th>

                                <th class="text-center">
                                    บทบาท
                                </th>

                                <th class="text-center">
                                    จัดการ
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if ($result->num_rows > 0): ?>


                            <?php while ($row = $result->fetch_assoc()): ?>


                                <tr class="user-row">


                                    <!-- ID -->

                                    <td>

                                        <div class="flex justify-center">

                                            <div class="user-id">

                                                <?= htmlspecialchars(
                                                    $row['user_id']
                                                ) ?>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- Name -->

                                    <td>

                                        <div class="flex items-center">

                                            <div class="user-avatar">
                                                👤
                                            </div>

                                            <div>

                                                <p class="font-semibold text-gray-800">

                                                    <?= htmlspecialchars(
                                                        $row['first_name']
                                                    ) ?>

                                                    <?= htmlspecialchars(
                                                        $row['last_name']
                                                    ) ?>

                                                </p>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- Email -->

                                    <td>

                                        <span class="text-sm text-gray-600">

                                            <?= htmlspecialchars(
                                                $row['email']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- Address -->

                                    <td>

                                        <div
                                            class="max-w-xs text-sm text-gray-500">

                                            <?= htmlspecialchars(
                                                $row['address']
                                            ) ?>

                                        </div>

                                    </td>


                                    <!-- Phone -->

                                    <td>

                                        <span class="text-sm text-gray-600">

                                            <?= htmlspecialchars(
                                                $row['phone_number']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- Role -->

                                    <td class="text-center">

                                        <?php if ($row['role'] === 'admin'): ?>

                                            <span class="role-admin">
                                                🛡️ Admin
                                            </span>

                                        <?php else: ?>

                                            <span class="role-user">
                                                👤 User
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- Actions -->

                                    <td>

                                        <div class="flex items-center justify-center gap-2">


                                            <button
                                                class="edit-btn"
                                                onclick="window.location.href='edit_customer.php?id=<?= htmlspecialchars($row['user_id']) ?>'">

                                                ✏️ แก้ไข

                                            </button>


                                            <button
                                                class="delete-btn"
                                                onclick="confirmDelete(<?= htmlspecialchars($row['user_id']) ?>)">

                                                🗑️ ลบ

                                            </button>


                                        </div>

                                    </td>

                                </tr>


                            <?php endwhile; ?>


                        <?php else: ?>


                            <tr>

                                <td
                                    colspan="7"
                                    class="empty-box">

                                    <div class="text-5xl mb-4">
                                        👥
                                    </div>

                                    <p class="text-gray-600 font-semibold">
                                        ไม่พบข้อมูลผู้ใช้
                                    </p>

                                    <p class="text-gray-400 text-sm mt-1">
                                        ยังไม่มีผู้ใช้งานในระบบ
                                    </p>

                                </td>

                            </tr>


                        <?php endif; ?>


                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>


<script>

function confirmDelete(userId) {

    const result = confirm(
        "คุณต้องการลบผู้ใช้นี้หรือไม่?"
    );

    if (result) {

        window.location.href =
            "delete_customer.php?id=" + userId;

    }

}

</script>


</body>

</html>