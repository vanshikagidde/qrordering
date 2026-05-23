-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql100.infinityfree.com
-- Generation Time: Apr 01, 2026 at 02:55 PM
-- Server version: 11.4.10-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_41045365_qr_ordering`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `email` varchar(180) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `email`, `password_hash`, `name`, `created_at`) VALUES
(1, 'admin@gmail.com', '$2y$10$Y03TKI4zzdJh1iB/fE7zMOVFAlfouEBu0VkfRhp2DtbV7MdAArvRq', 'Super Admin', '2026-02-05 02:57:31');

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu`
--

INSERT INTO `menu` (`id`, `shop_id`, `item_name`, `price`) VALUES
(1, 1, 'Coffee', '50.00'),
(2, 5, 'Burger', '150.00'),
(3, 1, 'Pizza', '200.00'),
(4, 5, 'Cheese Pizza', '180.00'),
(5, 5, 'coke', '20.00'),
(6, 6, 'Pizza', '450.00'),
(7, 6, 'Burgir', '700.00'),
(8, 5, 'pepsi', '20.00'),
(9, 5, 'pasta', '190.00'),
(10, 5, 'chickenburger', '150.00');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `table_no` varchar(50) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `token` varchar(50) DEFAULT NULL,
  `status` enum('pending','paid','failed','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `shop_id`, `table_no`, `total`, `token`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, '1', '250.00', '27357dc72a0dc3f3cee8c6b9c9aed8e0', 'pending', '2026-01-19 17:29:13', '2026-01-24 18:31:11'),
(2, 1, NULL, '50.00', '01', 'paid', '2026-01-22 17:39:26', '2026-01-24 18:31:11'),
(3, 5, NULL, '150.00', 'c6855244a8254cc24bc8f1d700502483', 'pending', '2026-01-25 16:57:31', '2026-01-25 16:57:31'),
(4, 5, NULL, '300.00', '460', 'paid', '2026-01-25 17:01:41', '2026-01-25 17:01:51'),
(5, 5, NULL, '170.00', '0bb29cc6916e3e37166bc50e3601fa51', 'pending', '2026-01-28 06:43:32', '2026-01-28 06:43:32'),
(6, 5, NULL, '170.00', '21', 'paid', '2026-01-28 06:44:19', '2026-01-28 06:44:29'),
(7, 5, NULL, '350.00', '72604', 'paid', '2026-01-28 06:56:43', '2026-01-28 06:56:51'),
(8, 5, NULL, '150.00', '4714', 'paid', '2026-02-01 09:32:09', '2026-02-01 09:32:24'),
(9, 5, NULL, '350.00', 'dcdbceec75d74a1ba9a90aafe5cc5940', 'pending', '2026-02-02 08:14:38', '2026-02-02 08:14:38'),
(10, 5, NULL, '330.00', '03', 'paid', '2026-02-02 08:15:05', '2026-02-02 08:17:04'),
(11, 6, NULL, '450.00', 'd4abc70a0b7c50d13ac1c0fcd9659866', 'pending', '2026-02-02 09:21:59', '2026-02-02 09:21:59'),
(12, 6, NULL, '450.00', '02', 'paid', '2026-02-02 09:22:13', '2026-02-02 09:22:21'),
(13, 5, NULL, '330.00', '4028', 'paid', '2026-02-09 18:15:55', '2026-02-09 18:16:03'),
(14, 5, NULL, '370.00', '15551', 'paid', '2026-02-11 03:49:28', '2026-02-11 03:49:36'),
(15, 5, NULL, '150.00', 'be89a85d7e5d51a4b626a42ed4c3d267', 'pending', '2026-02-11 03:57:34', '2026-02-11 03:57:34'),
(16, 5, NULL, '150.00', '62921c8f3ca230e21085470b015625da', 'pending', '2026-02-11 03:57:51', '2026-02-11 03:57:51'),
(17, 5, NULL, '150.00', '9812', 'paid', '2026-02-11 03:57:53', '2026-02-11 03:58:00'),
(18, 5, NULL, '350.00', 'cd4df803b8ee02db89d90d8fa915b427', 'pending', '2026-03-16 06:25:00', '2026-03-16 06:25:00'),
(19, 5, NULL, '350.00', '09', 'paid', '2026-03-16 06:25:16', '2026-03-16 06:25:23'),
(20, 5, NULL, '480.00', 'ba003a02d1d4290e926407b28121d481', 'pending', '2026-03-24 07:21:09', '2026-03-24 07:21:09'),
(21, 5, NULL, '450.00', '79360ce74522db50a2b4d37f7e05bd83', 'pending', '2026-03-24 07:21:54', '2026-03-24 07:21:54'),
(22, 5, NULL, '1200.00', '79361', 'paid', '2026-03-24 07:35:52', '2026-03-24 07:36:00'),
(23, 5, NULL, '210.00', '79362', 'paid', '2026-03-25 06:39:36', '2026-03-25 06:39:43');

-- --------------------------------------------------------

--
-- Table structure for table `order_item`
--

CREATE TABLE `order_item` (
  `order_item_id` int(11) NOT NULL,
  `order_id` bigint(20) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `table_no` int(11) DEFAULT NULL,
  `item_id` int(11) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `quantity` int(11) NOT NULL
) ;

--
-- Dumping data for table `order_item`
--

INSERT INTO `order_item` (`order_item_id`, `order_id`, `shop_id`, `table_no`, `item_id`, `item_name`, `quantity`, `price`, `total_price`, `status`, `created_at`) VALUES
(1, 1, 1, 1, 1, 'Coffee', 2, '50.00', '100.00', 'pending', '2026-01-19 17:29:13'),
(2, 1, 1, 1, 2, 'Burger', 1, '150.00', '150.00', 'pending', '2026-01-19 17:29:13'),
(3, 2, 1, NULL, 1, 'Coffee', 1, '50.00', '50.00', 'pending', '2026-01-22 17:39:26'),
(4, 3, 5, NULL, 2, 'Burger', 1, '150.00', '150.00', 'pending', '2026-01-25 16:57:31'),
(5, 4, 5, NULL, 2, 'Burger', 2, '150.00', '300.00', 'served', '2026-01-25 17:01:41'),
(6, 5, 5, NULL, 2, 'Burger', 1, '150.00', '150.00', 'pending', '2026-01-28 06:43:32'),
(7, 5, 5, NULL, 5, 'coke', 1, '20.00', '20.00', 'pending', '2026-01-28 06:43:32'),
(8, 6, 5, NULL, 2, 'Burger', 1, '150.00', '150.00', 'pending', '2026-01-28 06:44:19'),
(9, 6, 5, NULL, 5, 'coke', 1, '20.00', '20.00', 'pending', '2026-01-28 06:44:19'),
(10, 7, 5, NULL, 2, 'Burger', 1, '150.00', '150.00', 'pending', '2026-01-28 06:56:43'),
(11, 7, 5, NULL, 4, 'Cheese Pizza', 1, '180.00', '180.00', 'pending', '2026-01-28 06:56:43'),
(12, 7, 5, NULL, 5, 'coke', 1, '20.00', '20.00', 'pending', '2026-01-28 06:56:43'),
(13, 8, 5, NULL, 2, 'Burger', 1, '150.00', '150.00', 'served', '2026-02-01 09:32:09'),
(14, 9, 5, NULL, 2, 'Burger', 1, '150.00', '150.00', 'preparing', '2026-02-02 08:14:38'),
(15, 9, 5, NULL, 4, 'Cheese Pizza', 1, '180.00', '180.00', 'preparing', '2026-02-02 08:14:38'),
(16, 9, 5, NULL, 5, 'coke', 1, '20.00', '20.00', 'preparing', '2026-02-02 08:14:38'),
(17, 10, 5, NULL, 2, 'Burger', 1, '150.00', '150.00', 'served', '2026-02-02 08:15:05'),
(18, 10, 5, NULL, 4, 'Cheese Pizza', 1, '180.00', '180.00', 'served', '2026-02-02 08:15:05'),
(19, 11, 6, NULL, 6, 'Pizza', 1, '450.00', '450.00', 'served', '2026-02-02 09:21:59'),
(20, 12, 6, NULL, 6, 'Pizza', 1, '450.00', '450.00', 'preparing', '2026-02-02 09:22:13'),
(21, 13, 5, NULL, 2, 'Burger', 1, '150.00', '150.00', 'served', '2026-02-09 18:15:55'),
(22, 13, 5, NULL, 4, 'Cheese Pizza', 1, '180.00', '180.00', 'served', '2026-02-09 18:15:55'),
(23, 14, 5, NULL, 2, 'Burger', 1, '150.00', '150.00', 'served', '2026-02-11 03:49:28'),
(24, 14, 5, NULL, 4, 'Cheese Pizza', 1, '180.00', '180.00', 'served', '2026-02-11 03:49:28'),
(25, 14, 5, NULL, 5, 'coke', 1, '20.00', '20.00', 'served', '2026-02-11 03:49:28'),
(26, 14, 5, NULL, 8, 'pepsi', 1, '20.00', '20.00', 'served', '2026-02-11 03:49:28'),
(27, 15, 5, NULL, 2, 'Burger', 1, '150.00', '150.00', 'pending', '2026-02-11 03:57:34'),
(28, 16, 5, NULL, 2, 'Burger', 1, '150.00', '150.00', 'pending', '2026-02-11 03:57:51'),
(29, 17, 5, NULL, 2, 'Burger', 1, '150.00', '150.00', 'served', '2026-02-11 03:57:53'),
(30, 18, 5, NULL, 2, 'Burger', 1, '150.00', '150.00', 'pending', '2026-03-16 06:25:00'),
(31, 18, 5, NULL, 4, 'Cheese Pizza', 1, '180.00', '180.00', 'pending', '2026-03-16 06:25:00'),
(32, 18, 5, NULL, 8, 'pepsi', 1, '20.00', '20.00', 'pending', '2026-03-16 06:25:00'),
(33, 19, 5, NULL, 2, 'Burger', 1, '150.00', '150.00', 'served', '2026-03-16 06:25:16'),
(34, 19, 5, NULL, 4, 'Cheese Pizza', 1, '180.00', '180.00', 'served', '2026-03-16 06:25:16'),
(35, 19, 5, NULL, 8, 'pepsi', 1, '20.00', '20.00', 'served', '2026-03-16 06:25:16'),
(36, 20, 5, NULL, 2, 'Burger', 1, '150.00', '150.00', 'pending', '2026-03-24 07:21:09'),
(37, 20, 5, NULL, 4, 'Cheese Pizza', 1, '180.00', '180.00', 'pending', '2026-03-24 07:21:09'),
(38, 20, 5, NULL, 10, 'chickenburger', 1, '150.00', '150.00', 'pending', '2026-03-24 07:21:09'),
(39, 21, 5, NULL, 2, 'Burger', 3, '150.00', '450.00', 'pending', '2026-03-24 07:21:54'),
(40, 22, 5, NULL, 2, 'Burger', 8, '150.00', '1200.00', 'served', '2026-03-24 07:35:52'),
(41, 23, 5, NULL, 5, 'coke', 1, '20.00', '20.00', 'pending', '2026-03-25 06:39:36'),
(42, 23, 5, NULL, 9, 'pasta', 1, '190.00', '190.00', 'pending', '2026-03-25 06:39:36');

-- --------------------------------------------------------

--
-- Table structure for table `shops`
--

CREATE TABLE `shops` (
  `id` int(11) NOT NULL,
  `shop_name` varchar(255) NOT NULL,
  `owner_name` varchar(100) NOT NULL,
  `email` varchar(180) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('pending','active','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shops`
--

INSERT INTO `shops` (`id`, `shop_name`, `owner_name`, `email`, `phone`, `password_hash`, `status`, `created_at`) VALUES
(1, 'shop', '', '', '', '', 'rejected', '2026-02-09 17:57:54'),
(5, 'puri', 'mahesh', 'mahesh@gmail.com', '8698636924', '$2y$10$NrzQAUZezITbGdeBrv2GX.YSWCGm.WDmjEVhZM0lixYEHRxDZKkUy', 'active', '2026-02-09 17:57:54'),
(6, 'Dhruvika\'s kitchen', 'Dhruvika Chitte', 'dhruvika71@gmail.com', '8128497598', '$2y$10$qub8ldU08V/HukHfPjR.f.IU2yoFcTd8LyrwLf053czKQy7M9KBRm', 'active', '2026-02-09 17:57:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shop_id` (`shop_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `shop_id` (`shop_id`);

--
-- Indexes for table `shops`
--
ALTER TABLE `shops`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `shop_name` (`shop_name`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `order_item`
--
ALTER TABLE `order_item`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shops`
--
ALTER TABLE `shops`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `menu`
--
ALTER TABLE `menu`
  ADD CONSTRAINT `menu_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
