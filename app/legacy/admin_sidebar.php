<head>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<div class="w-64 h-screen bg-gray-800 p-6 flex-shrink-0 overflow-y-auto">

    <h2 class="text-white text-2xl font-semibold mb-8">
        Admin Dashboard
    </h2>

    <ul>

        <!-- แดชบอร์ด -->
        <li class="mb-4">
            <a href="dashboard.php"
               class="text-gray-300 hover:text-white">
                📊 แดชบอร์ด
            </a>
        </li>

        <!-- คำสั่งซื้อ -->
        <li class="mb-4">
            <a href="manage_oders.php"
               class="text-gray-300 hover:text-white">
                🛍️ การจัดการคำสั่งซื้อ
            </a>
        </li>

        <!-- สินค้า -->
        <li class="mb-4">
            <a href="products.php"
               class="text-gray-300 hover:text-white">
                📦 การจัดการสินค้า
            </a>
        </li>

        <!-- ผู้ใช้งาน -->
        <li class="mb-4">
            <a href="customer_data.php"
               class="text-gray-300 hover:text-white">
                👥 การจัดการผู้ใช้งานระบบ
            </a>
        </li>

        <!-- หัวข้อรายงาน -->
        <li class="mb-4">
            <a href="report_admin.php"
               class="text-gray-300 hover:text-white">
                📝 ความคิดเห็นจากลูกค้า
            </a>
        </li>

        <!-- คำสั่งซื้อหน้าร้าน -->
      
        <!-- ออกจากระบบ -->
        <li class="mb-4">
            <a href="#"
               class="text-red-300 hover:text-white"
               onclick="confirmLogout()">
                🚪 ออกจากระบบ
            </a>
        </li>

    </ul>

</div>

<script>
function confirmLogout() {

    Swal.fire({
        title: 'คุณแน่ใจหรือไม่?',
        text: 'คุณต้องการออกจากระบบใช่หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'ใช่, ออกจากระบบ!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {

        if (result.isConfirmed) {
            window.location.href = "../logout.php";
        }

    });

}
</script>