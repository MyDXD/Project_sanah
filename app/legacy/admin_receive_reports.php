<?php
require_once __DIR__ . '/../dbconnect.php';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $date = date('Y-m-d');

    $sql = "INSERT INTO reports (title, description, date) VALUES ('$title', '$description', '$date')";
    if ($conn->query($sql) === TRUE) {
        echo "New report added successfully";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Fetch reports from database
$sql = "SELECT * FROM reports ORDER BY date DESC";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receive Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Sidebar -->
    <div class="flex">
        <div class="w-64 h-screen bg-gray-800 p-6">
            <h2 class="text-white text-2xl font-semibold mb-8">Admin Dashboard</h2>
            <ul>
                <li class="mb-4">
                    <a href="dashboard.php" class="text-gray-300 hover:text-white">แดชบอร์ด</a>
                </li>
                <li class="mb-4">
                    <a href="#" class="text-gray-300 hover:text-white">คำสั่งซื้อ</a>
                </li>
                <li class="mb-4">
                    <a href="products.php" class="text-gray-300 hover:text-white">สินค้า</a>
                </li>
                <li class="mb-4">
                    <a href="customer_data.php" class="text-gray-300 hover:text-white">ลูกค้า</a>
                </li>
                <li class="mb-4">
                    <a href="receive_reports.php" class="text-gray-300 hover:text-white">รายงาน</a>
                </li>
            </ul>
        </div>
        <div class="flex-1 p-6">
            <!-- Recent Reports Table -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold mb-4">Recent Reports</h3>
                <table class="min-w-full table-auto">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Report ID</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Title</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Description</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td class='px-4 py-2 text-sm text-gray-700'>" . $row["report_id"] . "</td>";
                                echo "<td class='px-4 py-2 text-sm text-gray-700'>" . $row["title"] . "</td>";
                                echo "<td class='px-4 py-2 text-sm text-gray-700'>" . $row["description"] . "</td>";
                                echo "<td class='px-4 py-2 text-sm text-gray-700'>" . $row["date"] . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' class='px-4 py-2 text-sm text-gray-700 text-center'>No reports found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>
