<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สานะร้านขายสินค้าอาหารแห้งออนไลน์</title>
</head>
<body>
    <?php
        include "nav_bar.php";
        include "recom_product.php";
    ?>
    
</body>
</html>
