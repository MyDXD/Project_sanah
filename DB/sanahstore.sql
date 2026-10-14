-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 24, 2025 at 05:26 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sanahstore`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `added_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_date` datetime NOT NULL DEFAULT current_timestamp(),
  `total_price` decimal(10,2) NOT NULL,
  `order_status` varchar(50) NOT NULL DEFAULT '',
  `order_code` varchar(50) DEFAULT NULL,
  `delivery_address` text DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_slip` varchar(255) DEFAULT NULL,
  `transport_id` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `order_date`, `total_price`, `order_status`, `order_code`, `delivery_address`, `phone_number`, `payment_method`, `transaction_id`, `payment_slip`, `transport_id`) VALUES
(191, 37, '2025-02-05 09:08:28', 500.00, 'Completed', 'ORDER_67a31c7c0dad6121', '25/1 บ้าน อ.หาดใหญ่ จังหวัด ภูเก็ต 90110 ', NULL, NULL, NULL, 'payment_slip/67a31d1ed26abslock.jpg', ''),
(192, 37, '2025-02-05 09:21:17', 40.00, 'Completed', 'ORDER_67a31f7da5405706', '25/1 บ้าน อ.หาดใหญ่ จังหวัด ภูเก็ต 90110 ', NULL, NULL, NULL, 'payment_slip/67a31f86d04a9slock.jpg', '1212312121 kerry'),
(193, 33, '2025-02-06 17:17:49', 240.00, 'Order Processing', 'ORDER_67a4e0ad4e472501', 'spd888', NULL, NULL, NULL, 'payment_slip/67a4e0b62752dmrbid.png', '546855 lineman'),
(194, 33, '2025-02-06 17:22:58', 380.00, 'Completed', 'ORDER_67a4e1e259c66477', 'spd888', NULL, NULL, NULL, NULL, ''),
(195, 33, '2025-02-06 17:26:49', 50.00, 'Completed', 'ORDER_67a4e2c9d31c2838', 'spd888', NULL, NULL, NULL, NULL, ''),
(196, 33, '2025-02-06 17:29:55', 100.00, 'Order Processing', 'ORDER_67a4e3839b6b4779', 'spd888', NULL, NULL, NULL, NULL, '888'),
(197, 33, '2025-02-06 17:35:33', 70.00, 'Order Processing', 'ORDER_67a4e4d5cdf67410', 'spd888', NULL, NULL, NULL, NULL, '888 flash'),
(198, 33, '2025-02-06 17:37:01', 40.00, 'Order Placed', 'ORDER_67a4e52d0cd42448', 'spd888', NULL, NULL, NULL, NULL, '546855');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(19, 191, 69, 1, 200.00),
(20, 191, 48, 1, 150.00),
(21, 191, 59, 3, 50.00),
(22, 192, 39, 1, 40.00),
(24, 194, 68, 2, 190.00),
(25, 195, 42, 1, 50.00),
(26, 196, 53, 1, 100.00),
(27, 197, 40, 1, 70.00),
(28, 198, 39, 1, 40.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_price` varchar(255) NOT NULL,
  `product_type` varchar(255) NOT NULL,
  `product_detail` varchar(255) NOT NULL,
  `product_img` longtext NOT NULL,
  `stock` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `product_price`, `product_type`, `product_detail`, `product_img`, `stock`) VALUES
(39, 'มะขามเปรี้ยว', '40', 'ของทานเล่น', '1 กระปุก', 'product_pic/67449a120b18bLINE_ALBUM_โปรเจค_241125_2.jpg', 0),
(40, 'ช็อคโกแลตแดง', '70', 'ช็อคโกแลต', '1 ห่อ', 'product_pic/67449a3f1fb92LINE_ALBUM_โปรเจค_241125_3.jpg', 3),
(42, 'เมล็ดทานตะวันกลิ่นวอทนัท', '50', 'ของทานเล่น', '1 ห่อ', 'product_pic/67449af607e45LINE_ALBUM_โปรเจค_241125_4.jpg', 8),
(43, 'เมล็ดทานตะวันกลิ่นมะพร้าว', '50', 'ของทานเล่น', '1 ห่อ', 'product_pic/67449b1fda3a0LINE_ALBUM_โปรเจค_241125_5.jpg', 1),
(45, 'กระเพาะปลามังกร', '600', 'กระเพราะปลา', '1 เเพ็ค 500 กรัม', 'product_pic/67449bea4b439LINE_ALBUM_โปรเจค_241125_7.jpg', 19),
(46, 'ปลาหมึกอาเจนติน่าตากแห้ง', '800', 'ตากแห้ง', '1 แพ็ค 500 กรัม', 'product_pic/67449c0c9cd7bLINE_ALBUM_โปรเจค_241125_8.jpg', 20),
(47, 'นมเเพะ GOAT', '100', 'นม', '1 แพ็ค 24 ซอง', 'product_pic/67449c3474a27LINE_ALBUM_โปรเจค_241125_9.jpg', 10),
(48, 'ช็อคโกแลตนูเทล่าB 10ชิ้น', '150', 'ช็อคโกแลต', '1 กล่อง 10 ชิ้น', 'product_pic/67449c6a42ab3LINE_ALBUM_โปรเจค_241125_10.jpg', 17),
(49, 'ผงโกโก้', '1200', 'โกโก้', '1 กล่อง 500 กรัม', 'product_pic/67449d19a2771LINE_ALBUM_โปรเจค_241125_13.jpg', 10),
(50, 'ช็อคโกแลตดิม', '100', 'ช็อคโกแลต', '1 ห่อ 200 กรัม', 'product_pic/67449d3dab8b1LINE_ALBUM_โปรเจค_241125_14.jpg', 10),
(51, 'ลูกพรุน', '150', 'อาหารกระป๋อง', '1 กระปุก 350 กรัม', 'product_pic/67449d6d8caadLINE_ALBUM_โปรเจค_241125_15.jpg', 10),
(52, 'ลูกอมรสผลไม้รวม', '100', 'อาหารกระป๋อง', '1 กระป๋อง', 'product_pic/67449d9e97171LINE_ALBUM_โปรเจค_241125_11.jpg', 10),
(53, 'ลูกอมรสมิ้น', '100', 'อาหารกระป๋อง', '1 กระป๋อง', 'product_pic/67449dd759637LINE_ALBUM_โปรเจค_241125_12.jpg', 9),
(54, 'เมล็ดทานตะวันกลิ่นวอทนัท', '50', 'ของทานเล่น', '1 ห่อ', 'product_pic/67449e0073228LINE_ALBUM_โปรเจค_241125_16.jpg', 10),
(55, 'กาแฟ OLDTOWN', '150', 'ชา/กาแฟ', '1 ห่อ 15 ซอง', 'product_pic/67449e304c65cLINE_ALBUM_โปรเจค_241125_17.jpg', 10),
(56, 'ขนมปังอบรสทุเรียน', '60', 'ของทานเล่น', '1 ห่อ 16 ซอง', 'product_pic/67449e66ef592LINE_ALBUM_โปรเจค_241125_18.jpg', 10),
(57, 'ช็อคโกแลตเหรียญ Wingo', '100', 'ช็อคโกแลต', '1 กระปุก', 'product_pic/67449e8bdb9a8LINE_ALBUM_โปรเจค_241125_19.jpg', 10),
(58, 'ถั่วอัดแท่ง', '80', 'ของทานเล่น', '1 ห่อ มีหลายรสชาติ', 'product_pic/67449ebf7bb9bLINE_ALBUM_โปรเจค_241125_20.jpg', 10),
(59, 'ขนมปังน้ำตาล HUP SENG', '50', 'ของทานเล่น', '1 ห่อ 12ซอง 250 กรัม', 'product_pic/67449f0315eefLINE_ALBUM_โปรเจค_241125_21.jpg', 6),
(60, 'ขนมปังรวม HWA TAI 1962', '100', 'ของทานเล่น', '1 ห่อ มี24 ซอง 505 กรัม', 'product_pic/67449f3b0a057LINE_ALBUM_โปรเจค_241125_22.jpg', 20),
(61, 'ปังธัญพืช 99 ', '80', 'ขนมนำเข้า', '1 ห่อ มี 12 ซอง', 'product_pic/67449f7304671LINE_ALBUM_โปรเจค_241125_23.jpg', 10),
(63, 'ช็อคโกแลตนูเทล่าB 6 ชิ้น', '100', 'ช็อคโกแลต', '1 กล่อง 6 ชิ้น\r\n\r\n', 'product_pic/6744a002145c1LINE_ALBUM_โปรเจค_241125_25.jpg', 10),
(64, 'ช็อคโกแลต FEIDIPO', '150', 'ช็อคโกแลต', '1 กล่อง 16 ชิ้น', 'product_pic/6744a0374f6c2LINE_ALBUM_โปรเจค_241125_26.jpg', 5),
(65, 'ปลากระป๋อง AYAM', '40', 'อาหารกระป๋อง', '1 กระป๋อง 425 กรัม', 'product_pic/6744a077d376aLINE_ALBUM_โปรเจค_241125_27.jpg', 10),
(66, 'ชาจัสมิน', '100', 'ชา/กาแฟ', '1 กระปุก 120 กรัม', 'product_pic/6744a09c2b9c8LINE_ALBUM_โปรเจค_241125_28.jpg', 5),
(67, 'ช็อคโกแลตVAN', '100', 'ช็อคโกแลต', '1 กล่อง ', 'product_pic/6744a0d167de2LINE_ALBUM_โปรเจค_241125_29.jpg', 5),
(68, 'ช็อคโกแลต FERRERO ROCHER', '190', 'ช็อคโกแลต', '1 กล่อง 12 ลูก', 'product_pic/6744a1550aeceLINE_ALBUM_โปรเจค_241125_26.jpg', 8),
(69, 'แยมนูเทล่า', '200', 'แยม', '1 กระปุก 350 กรัม', 'product_pic/6744a18934f82LINE_ALBUM_โปรเจค_241125_31.jpg', 9),
(70, 'เห็ดกระป๋อง ROCA', '120', 'อาหารกระป๋อง', '1 กระป๋อง 120 กรัม', 'product_pic/6744a1b7b5655LINE_ALBUM_โปรเจค_241125_32.jpg', 10),
(71, 'เห็ดกระป๋อง ', '60', 'อาหารกระป๋อง', '1 กระป๋อง 425 กรัม', 'product_pic/6744a1dcddf4eLINE_ALBUM_โปรเจค_241125_33.jpg', 10),
(72, 'ชาขิง Gold Roast', '140', 'ชา/กาแฟ', '1 ห่อ 12 ซอง', 'product_pic/6744a2a441305LINE_ALBUM_โปรเจค_241125_34.jpg', 10),
(73, 'ชา 3 in 1 Gold Roast', '140', 'ชา/กาแฟ', '1 ห่อ 12 ซอง', 'product_pic/6744a313866bbLINE_ALBUM_โปรเจค_241125_35.jpg', 10),
(74, 'มาม่าเกาหลีรสชีส', '150', 'อาหารกึ่งสำเร็จรูป', '1 ห่อ มี 6 ซอง', 'product_pic/6744a35b234eaLINE_ALBUM_โปรเจค_241125_36.jpg', 5),
(75, 'มาม่าเกาหลีไก่เผ็ด', '150', 'อาหารกึ่งสำเร็จรูป', '1 ห่อ มี 6 ซอง\r\n\r\n', 'product_pic/6744a388f02edLINE_ALBUM_โปรเจค_241125_37.jpg', 5),
(76, 'กาแฟ OLDTOWN WHITE COFFEE', '150', 'ชา/กาแฟ', '1 ห่อ 15 ซอง\r\n\r\n', 'product_pic/6744a3cf67bafLINE_ALBUM_โปรเจค_241125_38.jpg', 10),
(77, 'กาแฟอาแป๊ะ whit coffee', '180', 'ชา/กาแฟ', '1 ห่อ 570 กรัม 15 ซอง', 'product_pic/6744a4587ea27LINE_ALBUM_โปรเจค_241125_39.jpg', 5),
(78, 'นมข้าวโอ๊ต Gold Roast', '150', 'นม', '1 ห่อ 12 ซอง', 'product_pic/6744a4add3747LINE_ALBUM_โปรเจค_241125_40.jpg', 10),
(79, 'นมข้าวโอ๊ตรสช็อคโกแลต Gold Roast', '150', 'นม', '1 ห่อ 12 ซอง\r\n\r\n', 'product_pic/6744a50f94cd1LINE_ALBUM_โปรเจค_241125_41.jpg', 10),
(80, 'โกโก้ Hoya 3 in 1', '140', 'โกโก้', '1 ห่อ 20  ซอง', 'product_pic/6744a543edc1dLINE_ALBUM_โปรเจค_241125_42.jpg', 10),
(81, 'เกาลักหัวล้านไม่มีเปลือก', '100', 'อาหารกึ่งสำเร็จรูป', '5 ห่อ ห่อล่ะ 80 กรัม', 'product_pic/6744a5920086fLINE_ALBUM_โปรเจค_241125_43.jpg', 30),
(82, 'เกาลักมีเปลือก', '100', 'อาหารกึ่งสำเร็จรูป', '4 ห่อ ห่อล่ะ 100 กรัม', 'product_pic/6744a6231de39LINE_ALBUM_โปรเจค_241125_44.jpg', 20),
(83, 'บ๊วยรวม', '50', 'ของทานเล่น', '1 แพ็ค 500 กรัม ', 'product_pic/6744a6c489809LINE_ALBUM_โปรเจค_241125_45.jpg', 10),
(84, 'สาหร่ายเกาหลียังบัน', '50', 'ของทานเล่น', '1 แพ็ค มี 12 ซอง', 'product_pic/6744a760ad28aLINE_ALBUM_โปรเจค_241125_46.jpg', 10),
(85, 'บ๊วยลูกไนท์', '90', 'ของทานเล่น', '1 แพ็ค 500 กรัม', 'product_pic/6744a78f7ca93LINE_ALBUM_โปรเจค_241125_47.jpg', 15),
(86, 'ช็อคโกแลตแอลม่อน', '150', 'ช็อคโกแลต', '1 ห่อ ', 'product_pic/6744a7cc8a2afLINE_ALBUM_โปรเจค_241125_48.jpg', 10),
(87, 'Goji berries ', '120', 'ผลไม้อบแห้ง', '1 แพ็ค 500 กรัม', 'product_pic/6744a817d4068LINE_ALBUM_โปรเจค_241125_49.jpg', 10),
(88, 'ช็อคโกแลต ROCA', '90', 'ช็อคโกแลต', '1 แพ็ค 500 กรัม', 'product_pic/6744a86c599edLINE_ALBUM_โปรเจค_241125_50.jpg', 15),
(89, 'ผงพะโล้สำเร็จรูปสูตรพิเศษจากจีน', '150', 'นำเข้า', '1 กล่อง มี 10 ซอง', 'product_pic/6744a8c0ec7bfLINE_ALBUM_โปรเจค_241125_52.jpg', 5),
(90, 'ปีโป้กรอบ', '50', 'ของทานเล่น', '1 ห่อ 120 กรัม', 'product_pic/6744a8e539003LINE_ALBUM_โปรเจค_241125_53.jpg', 5),
(91, 'ช็อคโกแลตโกโก้ครั้น', '80', 'ช็อคโกแลต', '1 กระปุก', 'product_pic/6744a9197bf33LINE_ALBUM_โปรเจค_241125_54.jpg', 18),
(92, 'บิสกิตช็อคโกแลตกาแฟ', '50', 'ช็อคโกแลต', '1 กล่อง มี 12 ชิ้น', 'product_pic/6744a96b0cd93LINE_ALBUM_โปรเจค_241125_55.jpg', 5),
(93, 'เห็ดลิ้นจื๋อ', '1100', 'นำเข้า', '1 ถุง 1 กิโล', 'product_pic/6744a9b437571LINE_ALBUM_โปรเจค_241125_56.jpg', 5),
(94, 'สาหร่ายแผ่น', '60', 'อาหารกึ่งสำเร็จรูป', '1 แผ่น 300 กรัม', 'product_pic/6744a9e062d83LINE_ALBUM_โปรเจค_241125_57.jpg', 20),
(95, 'กุ้งแห้ง', '500', 'อาหารกึ่งสำเร็จรูป', '1 แพ็ค 500 กรัม', 'product_pic/6744aa2b2bc67LINE_ALBUM_โปรเจค_241125_58.jpg', 5),
(96, 'ปลาจิ๋อจ้าง', '200', 'ตากแห้ง', '1 แพ็ค 500 กรัม', 'product_pic/6744aa5bcb331LINE_ALBUM_โปรเจค_241125_59.jpg', 10),
(97, 'กาแฟ Togkat Ali', '200', 'ชา/กาแฟ', '1 แผง 20 ซอง ', 'product_pic/6744aa8e2dcdeLINE_ALBUM_โปรเจค_241125_60.jpg', 10),
(98, 'เต้าหู้ยี่', '90', 'นำเข้า', '1 กระปุก 250 กรัม', 'product_pic/6744aabaca9d6LINE_ALBUM_โปรเจค_241125_61.jpg', 20),
(99, 'หมึกอย่างเบนโตะ', '100', 'อบกรอบ', '1 แพ็ค 100 กรัม', 'product_pic/6744aaf595236LINE_ALBUM_โปรเจค_241125_62.jpg', 50),
(100, 'สตอเบอร์รี่อบกรอบ', '100', 'ผลไม้อบกรอบ', '1 ขีด', 'product_pic/6744ab460813aLINE_ALBUM_โปรเจค_241125_63.jpg', 50),
(101, 'เห็ดดอกใหญ่จากญี่ปุ่น', '180', 'นำเข้า', '1 แพ็ค 500 กรัม', 'product_pic/6744ab65cdc4eLINE_ALBUM_โปรเจค_241125_64.jpg', 20),
(102, 'เห็ดดอกเล็กจากจีน', '150', 'นำเข้า', '1 แพ็ค 500 กรัม', 'product_pic/6744ab825032bLINE_ALBUM_โปรเจค_241125_65.jpg', 20),
(103, 'ช็อคโกลแลต Beryls รสนม', '180', 'ช็อคโกแลต', '1 กระปุก 300 กรัม', 'product_pic/6744ac40cb98dLINE_ALBUM_โปรเจค_241125_66.jpg', 10),
(104, 'ช็อคโกแลตCHOCO CUBES', '120', 'ช็อคโกแลต', '1 กล่อง มี 12 ชิ้น', 'product_pic/6744ac8415076LINE_ALBUM_โปรเจค_241125_67.jpg', 10),
(105, 'ช็อคโกแลตโกโก้สุ่มTIROL', '80', 'ช็อคโกแลต', '1 กล่อง 10 ชิ้น', 'product_pic/6744accd913b5LINE_ALBUM_โปรเจค_241125_68.jpg', 6),
(106, 'กาแฟ Nescafe GOLD', '290', 'ชา/กาแฟ', '1 กระปุก 550 กรัม', 'product_pic/6744ad0f4d9e4LINE_ALBUM_โปรเจค_241125_69.jpg', 10),
(107, 'กาแฟ ucc 114', '200', 'ชา/กาแฟ', '1 กระปุก 500 กรัม', 'product_pic/6744ad6f8fd09LINE_ALBUM_โปรเจค_241125_70.jpg', 5),
(108, 'ช็อคโกแลตนม DAIRY MILK', '120', 'ช็อคโกแลต', '1 กระป๋อง 405 กรัม', 'product_pic/674576deeec0fLINE_ALBUM_โปรเจค_241125_71.jpg', 10),
(109, 'ช็อคโกแลต kitkat', '150', 'ช็อคโกแลต', '1 ห่อ 24 ชิ้น', 'product_pic/674577973b5d7LINE_ALBUM_โปรเจค_241125_72.jpg', 10),
(110, 'ช็อคโกลแลต Beryls รสนมผสมลูกเกด', '180', 'ช็อคโกแลต', '1 กระปุก 300 กรัม\r\n\r\n', 'product_pic/674577dcc8578LINE_ALBUM_โปรเจค_241125_75.jpg', 10),
(111, 'เยลลี่มาเลย์ konjac', '50', 'นำเข้า', '1 ห่อ 15 ซอง', 'product_pic/6745789291958LINE_ALBUM_โปรเจค_241125_73.jpg', 20),
(112, 'ช็อคโกแลตแอลม่อน Tango', '50', 'ช็อคโกแลต', '1 ซอง', 'product_pic/6745795651ebbLINE_ALBUM_โปรเจค_241125_74.jpg', 10),
(113, 'รังนกพม่า(ยางไม้)', '300', 'นำเข้า', '1 ถุง 500 กรัม', 'product_pic/674579a398612LINE_ALBUM_โปรเจค_241125_76.jpg', 10),
(114, 'กระเพาะปลาไซด์ใหญ่', '600', 'กระเพราะปลา', '1 ห่อ 500 กรัม', 'product_pic/67457a0abe98bLINE_ALBUM_โปรเจค_241125_77.jpg', 10),
(115, 'ช็อคโกแลตบิสกิต Bellco', '150', 'ช็อคโกแลต', '1 ห่อ 12 ซอง', 'product_pic/67457a59e13d1LINE_ALBUM_โปรเจค_241125_78.jpg', 10),
(116, 'ชาตรามือ 666', '200', 'ชา/กาแฟ', '1 ห่อ 500 กรัม', 'product_pic/67457a809afb1LINE_ALBUM_โปรเจค_241125_79.jpg', 5),
(117, 'ปลิงทะเลไซด์ยาว', '7000', 'นำเข้า', '1 ถุง 500 กรัม', 'product_pic/67457aa49feb1LINE_ALBUM_โปรเจค_241125_80.jpg', 10),
(118, 'ปลิงทะเลไซด์สั้น', '7000', 'นำเข้า', '1 ถุง 500 กรัม', 'product_pic/67457ac446b71LINE_ALBUM_โปรเจค_241125_81.jpg', 10),
(119, 'ช็อคโกแลตคุ้กกี้ TATAWA ', '35', 'ช็อคโกแลต', '1 ห่อ 10 ชิ้น', 'product_pic/67457affc51faLINE_ALBUM_โปรเจค_241125_82.jpg', 10),
(120, 'ขนมปังจากญี่ปุ่น', '50', 'นำเข้า', '1 ห่อ 8 ซอง 328 กรัม', 'product_pic/67457b301688aLINE_ALBUM_โปรเจค_241125_83.jpg', 10),
(121, 'ปลิงทะเลไซด์ยักษ์', '12000', 'นำเข้า', '1 ถุง 1 กิโล ', 'product_pic/67457b68ee9d2LINE_ALBUM_โปรเจค_241125_84.jpg', 5),
(122, 'หอยเชล', '4500', 'นำเข้า', '1 ถุง 1 กิโล', 'product_pic/67457bc53f51aLINE_ALBUM_โปรเจค_241125_85.jpg', 5),
(123, 'บลูเบอร์รี่คุ้กกี้ TATAWA', '35', 'ของทานเล่น', '1 ห่อ 10 ชิ้น', 'product_pic/67457cc9bc6c7LINE_ALBUM_โปรเจค_241125_86.jpg', 10);

-- --------------------------------------------------------

--
-- Table structure for table `userdata`
--

CREATE TABLE `userdata` (
  `user_id` int(11) NOT NULL,
  `email` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `first_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `last_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `title_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `address` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `phone_number` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `role` enum('user','admin') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `userdata`
--

INSERT INTO `userdata` (`user_id`, `email`, `password`, `first_name`, `last_name`, `title_name`, `address`, `phone_number`, `role`) VALUES
(27, 'niraname@gmail.com', '$2y$10$d2QwhLpFMMFRif4uFDLzeemM5laBwDc9fh6ET37uaDSm4JrZ8fJkO', 'นิรนาม', 'ไม่ทราบชื่อ', '', '', '', 'user'),
(28, 'user@user.com', '$2y$10$d2QwhLpFMMFRif4uFDLzeemM5laBwDc9fh6ET37uaDSm4JrZ8fJkO', 'userFirst', 'userLastname', 'tseestes', '284 O\'Reilly Fords, Port Marcellfurt, ND 20587', '0946645521', 'user'),
(32, 'dxd@dxd.com', '$2y$10$d2QwhLpFMMFRif4uFDLzeemM5laBwDc9fh6ET37uaDSm4JrZ8fJkO', 'D', 'XD', 'title_name', 'ไม่บอกเขิน', '0984445512', 'user'),
(33, 'spd888@gmail.com', '$2y$10$1R7VLaro.MYuW4UUsMfI/e6gvtDKSuySxuda6wAGcSyyAMgHscDd.', 'จริง', 'สกุล', '', 'spd888', '1029384756', 'user'),
(34, 'gameover@gmail.com', '$2y$10$MBi5BPMCvuKOYTonLiupA.3e0XXNWw2AzVsD5Psa4d3zin8NwtKGe', 'ครูอาพร', 'มณีนิล', '', 'วิทยาลัยหาดใหญ่อำนวยวิทย์', '0874753382', 'user'),
(35, 'userler@gmail.com', '555', 'ผู้', 'ใช้', '', 'ตำบล บ้านคำ อำเภอ คำมิง จังหวัด มาคำ ประเทศ คำแลน', '9517532648', 'user'),
(36, 'patlung@gmail.com', '$2y$10$EXeg3CC5aVZkOO8cm64qKOLnS1j/K1k5k5BOZOlHCShsxh2/076Um', 'เด็กเลี้ยงวัว', 'มหาลัยวัวชน', '', 'สักแห่งในพัทลุง', '546789321', 'user'),
(37, 'Dee@gmail.com', '$2y$10$kThQyFnHwGBISRD3HX2el.Yxjr4LmQykajQqQsCcSxkca8nREcTYK', 'ดี', 'ดี', '', '25/1 บ้าน อ.หาดใหญ่ จังหวัด ภูเก็ต 90110 ', '023456789', 'user'),
(39, 'nunu@gmail.com', '55\r\n', 'ืืnana', 'nini', '', 'nono\r\n', '999999999', 'user'),
(40, 'jjj@gmail.com', '888', 'ืืljklho', 'kjkljasd', '', '888', '8888888888', 'user'),
(41, 'kk@gmail.com', '999', 'kk', 'kk', '', '888', '8888888888', 'user'),
(42, 'ma@gmail.com', '$2y$10$z4BS5x9nfIsGKMJ9ndX6rOD9tPNe2MOCXZZkITvE69ExAVa0k5IDa', 'ma', 'ma', '', 'ma', '7777777777', 'user'),
(43, 'mi@gmail.com', '$2y$10$oXOucVS5URS7/dOJjDx0U.KbMy/AO2FOle7NReqokvlDjhoyooN4m', 'mi', 'mi', '', '66', '7777777777', 'user'),
(44, 'mu@gmail.com', '$2y$10$KmmKj/DrcrS6zFu/b69ZkexC0V0emxOVy7qtqS0A6pA1DtTXAudrS', 'mu', 'mu', '', '55', '5555555555', 'user'),
(45, 'na@gmail.com', '$2y$10$rtJ6.aKoAf5W16VQ/i.P4OJKpTKDL0j.Grmpgl49pHdXhJhKYWJoS', 'na', 'na', '', '44', '4444444444', 'user'),
(46, 'admin@admin.com', '$2y$10$EUYHyQ0PJXvNx6ZmzdzbeOamhEtnd8USTVudRT5LsF/yxXE1ozN.i', 'admin', 'admin', '', 'a', '9431678525', 'admin'),
(47, 'dee2@gmail.com', '$2y$10$e7WAD.TOcsdmhMQ9s8TjjOexTINMpbssBII0gaqxXNLxg0bSeBP7q', 'dee', 'dee', '', 'ที่นี้', '0123456789', 'user'),
(48, 'th650033@anw.ac.th', '$2y$10$rgYM6guRXMEhw/XGzvizhutadhqrZMpnsqm5Pylg7E4OVHtbqGSA6', 'narangsun', 'radwai', '', '-', '0900973333', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `order_id_2` (`order_id`,`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `userdata`
--
ALTER TABLE `userdata`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=202;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=201;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT for table `userdata`
--
ALTER TABLE `userdata`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `userdata` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `userdata` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
