-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 21, 2025 at 12:44 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `shoe_boutique`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 1, 'login', 'User logged in successfully', '::1', '2025-10-19 06:57:14'),
(2, 1, 'logout', 'User logged out', '::1', '2025-10-19 07:25:29'),
(3, 1, 'login', 'User logged in successfully', '::1', '2025-10-19 07:26:34'),
(4, 1, 'login', 'User logged in successfully', '::1', '2025-10-19 07:26:48'),
(5, 1, 'login', 'User logged in successfully', '::1', '2025-10-19 07:28:12'),
(6, 1, 'login', 'User logged in successfully', '::1', '2025-10-19 07:33:31'),
(7, 1, 'login', 'User logged in successfully', '::1', '2025-10-19 07:34:57'),
(8, 1, 'login', 'User logged in successfully', '::1', '2025-10-19 07:39:33'),
(9, 1, 'checkout', 'Created order #ORD-20251020-035136207 for customer type: walk_in', '::1', '2025-10-20 01:51:36'),
(10, 1, 'checkout', 'Created order #ORD-20251020-035516236 for customer type: whatsapp', '::1', '2025-10-20 01:55:16'),
(11, 1, 'checkout', 'Created order #ORD-20251020-035700895 for customer type: walk_in', '::1', '2025-10-20 01:57:00'),
(12, 1, 'update_order_status', 'Updated order #3 to ready', '::1', '2025-10-20 01:59:34'),
(13, 1, 'update_collection_status', 'Updated collection status for order #3 to ready', '::1', '2025-10-20 02:00:12'),
(14, 1, 'update_collection_status', 'Updated collection status for order #3 to collected', '::1', '2025-10-20 02:00:28'),
(15, 1, 'add_user', 'Added user: muchi', '::1', '2025-10-20 02:17:45'),
(16, 1, 'logout', 'User logged out', '::1', '2025-10-20 02:22:16'),
(17, 2, 'login', 'User logged in successfully', '::1', '2025-10-20 02:22:36'),
(18, 2, 'checkout', 'Created order #ORD-20251020-042357532 for customer type: existing', '::1', '2025-10-20 02:23:57'),
(19, 2, 'checkout', 'Created order #ORD-20251020-093839921 with receipt #RCPT-20251020-9022', '::1', '2025-10-20 07:38:39'),
(20, 2, 'generate_receipt', 'Generated receipt for order #ORD-20251020-093839921', '::1', '2025-10-20 07:38:44'),
(21, 2, 'generate_receipt', 'Generated receipt for order #ORD-20251020-093839921', '::1', '2025-10-20 07:39:49'),
(22, 2, 'generate_receipt', 'Generated receipt for order #ORD-20251020-093839921', '::1', '2025-10-20 08:29:26'),
(23, 2, 'generate_receipt', 'Generated receipt for order #ORD-20251020-093839921', '::1', '2025-10-20 08:29:39'),
(24, 2, 'generate_receipt', 'Generated receipt for order #ORD-20251020-093839921', '::1', '2025-10-20 08:36:36'),
(25, 2, 'generate_receipt', 'Generated receipt for order #ORD-20251020-093839921', '::1', '2025-10-20 08:38:57'),
(26, 2, 'generate_receipt', 'Generated receipt for order #ORD-20251020-093839921', '::1', '2025-10-20 08:41:43'),
(27, 2, 'generate_receipt', 'Generated receipt for order #ORD-20251020-093839921', '::1', '2025-10-20 08:41:45'),
(28, 2, 'generate_receipt', 'Generated receipt for order #ORD-20251020-093839921', '::1', '2025-10-20 08:41:52'),
(29, 2, 'generate_receipt', 'Generated receipt for order #ORD-20251020-093839921', '::1', '2025-10-20 08:41:54'),
(30, 2, 'generate_receipt', 'Generated receipt for order #ORD-20251020-093839921', '::1', '2025-10-20 08:42:17'),
(31, 2, 'generate_receipt', 'Generated receipt for order #ORD-20251020-093839921', '::1', '2025-10-20 08:42:22'),
(32, 2, 'generate_receipt', 'Generated receipt for order #ORD-20251020-093839921', '::1', '2025-10-20 08:42:29'),
(33, 2, 'generate_receipt', 'Generated receipt for order #ORD-20251020-093839921', '::1', '2025-10-20 08:42:33'),
(34, 2, 'update_order_status', 'Updated order #5 to ready', '::1', '2025-10-20 09:17:53'),
(35, 2, 'generate_receipt', 'Generated receipt for order #ORD-20251020-093839921', '::1', '2025-10-20 09:18:17'),
(36, 2, 'generate_receipt', 'Generated receipt for order #ORD-20251020-093839921', '::1', '2025-10-20 13:41:15'),
(37, 2, 'generate_receipt', 'Generated receipt for order #ORD-20251020-093839921', '::1', '2025-10-20 13:41:28'),
(38, 2, 'logout', 'User logged out', '::1', '2025-10-20 14:02:48'),
(39, 1, 'login', 'User logged in successfully', '::1', '2025-10-20 14:02:58'),
(40, 1, 'logout', 'User logged out', '::1', '2025-10-20 14:03:12'),
(41, 2, 'login', 'User logged in successfully', '::1', '2025-10-20 14:03:21'),
(42, 2, 'checkout', 'Created order #ORD-20251020-160708100 with receipt #RCPT-20251020-9677', '::1', '2025-10-20 14:07:08'),
(43, 2, 'generate_receipt', 'Generated receipt for order #ORD-20251020-160708100', '::1', '2025-10-20 14:07:14'),
(44, 2, 'logout', 'User logged out', '::1', '2025-10-20 14:32:59'),
(45, 1, 'login', 'User logged in successfully', '::1', '2025-10-20 14:33:09'),
(46, 1, 'update_product', 'Updated product: 1460 Boots', '::1', '2025-10-20 14:33:36'),
(47, 1, 'add_product', 'Added product: boyfriend', '::1', '2025-10-20 14:35:28'),
(48, 1, 'logout', 'User logged out', '::1', '2025-10-21 05:03:35'),
(49, 1, 'login', 'User logged in successfully', '::1', '2025-10-21 05:03:51'),
(50, 1, 'generate_receipt', 'Generated receipt for order #ORD-20251020-160708100', '::1', '2025-10-21 05:04:29');

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Nike', 'Athletic footwear and apparel', '2025-10-19 06:56:54'),
(2, 'Adidas', 'Sportswear and sneakers', '2025-10-19 06:56:54'),
(3, 'Clarks', 'Comfort footwear', '2025-10-19 06:56:54'),
(4, 'Dr. Martens', 'Iconic boots and shoes', '2025-10-19 06:56:54'),
(5, 'Steve Madden', 'Fashion footwear', '2025-10-19 06:56:54');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Sneakers', 'Casual and athletic sneakers', '2025-10-19 06:56:54'),
(2, 'Boots', 'Formal and casual boots', '2025-10-19 06:56:54'),
(3, 'Sandals', 'Open-toe footwear', '2025-10-19 06:56:54'),
(4, 'Loafers', 'Slip-on casual shoes', '2025-10-19 06:56:54'),
(5, 'Heels', 'Women\'s high-heeled shoes', '2025-10-19 06:56:54');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `customer_type` enum('walk_in','existing','whatsapp','phone_call','online','referral') DEFAULT 'walk_in'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `email`, `phone`, `address`, `created_at`, `customer_type`) VALUES
(1, 'Emmanuel', NULL, '+260978549730', NULL, '2025-10-20 01:54:43', 'whatsapp');

-- --------------------------------------------------------

--
-- Table structure for table `customer_types`
--

CREATE TABLE `customer_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT '#6B7280',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_types`
--

INSERT INTO `customer_types` (`id`, `type_name`, `description`, `color`, `is_active`, `created_at`) VALUES
(1, 'walk_in', 'Walk-in customers who visit the store directly', '#10B981', 1, '2025-10-20 01:32:35'),
(2, 'existing', 'Returning customers with purchase history', '#3B82F6', 1, '2025-10-20 01:32:35'),
(3, 'whatsapp', 'Customers who contact via WhatsApp', '#25D366', 1, '2025-10-20 01:32:35'),
(4, 'phone_call', 'Customers who call to place orders', '#EF4444', 1, '2025-10-20 01:32:35'),
(5, 'online', 'Customers from online platforms/website', '#8B5CF6', 1, '2025-10-20 01:32:35'),
(6, 'referral', 'Customers referred by existing customers', '#F59E0B', 1, '2025-10-20 01:32:35');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `subtotal_amount` decimal(10,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `tpin` varchar(20) DEFAULT NULL,
  `status` enum('pending','ready','collected') DEFAULT 'pending',
  `collected_by_name` varchar(255) DEFAULT NULL,
  `collected_by_phone` varchar(20) DEFAULT NULL,
  `collected_by_relation` varchar(100) DEFAULT NULL,
  `collection_method` enum('in_store','courier','delivery') DEFAULT 'in_store',
  `collection_verified_by` int(11) DEFAULT NULL,
  `collection_notes` text DEFAULT NULL,
  `collected_at` timestamp NULL DEFAULT NULL,
  `payment_method` enum('cash','card','transfer') DEFAULT 'cash',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `customer_type` enum('walk_in','existing','whatsapp','phone_call','online','referral') DEFAULT 'walk_in'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `receipt_number`, `customer_id`, `total_amount`, `subtotal_amount`, `tax_amount`, `tpin`, `status`, `collected_by_name`, `collected_by_phone`, `collected_by_relation`, `collection_method`, `collection_verified_by`, `collection_notes`, `collected_at`, `payment_method`, `notes`, `created_by`, `created_at`, `updated_at`, `customer_type`) VALUES
(1, 'ORD-20251020-035136207', 'RCPT-20251020-000001', NULL, 270.00, 245.45, 24.55, NULL, 'pending', NULL, NULL, NULL, 'in_store', NULL, NULL, NULL, 'cash', NULL, 1, '2025-10-20 01:51:36', '2025-10-20 04:42:03', 'walk_in'),
(2, 'ORD-20251020-035516236', 'RCPT-20251020-000002', 1, 369.99, 336.35, 33.64, NULL, 'pending', NULL, NULL, NULL, 'in_store', NULL, NULL, NULL, 'cash', NULL, 1, '2025-10-20 01:55:16', '2025-10-20 04:42:03', 'whatsapp'),
(3, 'ORD-20251020-035700895', 'RCPT-20251020-000003', NULL, 310.00, 281.82, 28.18, NULL, 'collected', NULL, NULL, NULL, 'in_store', NULL, NULL, NULL, 'cash', NULL, 1, '2025-10-20 01:57:00', '2025-10-20 04:42:03', 'walk_in'),
(4, 'ORD-20251020-042357532', 'RCPT-20251020-000004', 1, 160.00, 145.45, 14.55, NULL, 'pending', NULL, NULL, NULL, 'in_store', NULL, NULL, NULL, 'cash', NULL, 2, '2025-10-20 02:23:57', '2025-10-20 04:42:03', 'existing'),
(5, 'ORD-20251020-093839921', 'RCPT-20251020-9022', NULL, 341.00, 310.00, 31.00, 'TPIN-2510204069', 'ready', NULL, NULL, NULL, 'in_store', NULL, NULL, NULL, 'cash', NULL, 2, '2025-10-20 07:38:39', '2025-10-20 09:17:53', 'walk_in'),
(6, 'ORD-20251020-160708100', 'RCPT-20251020-9677', NULL, 704.00, 640.00, 64.00, '1007515019', 'pending', NULL, NULL, NULL, 'in_store', NULL, NULL, NULL, 'cash', NULL, 2, '2025-10-20 14:07:08', '2025-10-20 14:07:08', 'walk_in');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `unit_price`, `total_price`) VALUES
(1, 1, 1, 1, 150.00, 150.00),
(2, 1, 2, 1, 120.00, 120.00),
(3, 2, 1, 1, 150.00, 150.00),
(4, 2, 5, 1, 89.99, 89.99),
(5, 2, 4, 1, 130.00, 130.00),
(6, 3, 3, 1, 160.00, 160.00),
(7, 3, 1, 1, 150.00, 150.00),
(8, 4, 3, 1, 160.00, 160.00),
(9, 5, 3, 1, 160.00, 160.00),
(10, 5, 1, 1, 150.00, 150.00),
(11, 6, 3, 4, 160.00, 640.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `sku` varchar(100) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `cost_price` decimal(10,2) NOT NULL,
  `size` varchar(20) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `min_stock_level` int(11) DEFAULT 5,
  `image_url` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `sku`, `category_id`, `brand_id`, `price`, `cost_price`, `size`, `color`, `stock_quantity`, `min_stock_level`, `image_url`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Air Max 270', 'Comfortable running sneakers with air cushioning', 'NK-AM270-BLK-10', 1, 1, 150.00, 90.00, '10', 'Black', 21, 5, NULL, 1, '2025-10-19 06:56:54', '2025-10-20 07:38:39'),
(2, 'Classic Leather', 'Timeless sneakers with premium leather', 'AD-CL-WHT-9', 1, 2, 120.00, 70.00, '9', 'White', 14, 5, NULL, 1, '2025-10-19 06:56:54', '2025-10-20 01:51:36'),
(3, '1460 Boots', 'Iconic 8-eye lace-up boots', 'DM-1460-BLK-8', 2, 4, 160.00, 100.00, '8', 'Black', 12, 3, NULL, 1, '2025-10-19 06:56:54', '2025-10-20 14:33:36'),
(4, 'Desert Boots', 'Classic suede desert boots', 'CL-DB-TAN-9', 2, 3, 130.00, 80.00, '9', 'Tan', 7, 3, NULL, 1, '2025-10-19 06:56:54', '2025-10-20 01:55:16'),
(5, 'Candie Heels', 'Elegant high-heeled pumps', 'SM-CH-BLK-7', 5, 5, 89.99, 50.00, '7', 'Black', 11, 4, NULL, 1, '2025-10-19 06:56:54', '2025-10-20 01:55:16'),
(6, 'boyfriend', 'ladies office shoes', 'sku', 2, 1, 400.00, 300.00, '45', 'green', 80, 5, NULL, 1, '2025-10-20 14:35:28', '2025-10-20 14:35:28');

-- --------------------------------------------------------

--
-- Table structure for table `receipt_settings`
--

CREATE TABLE `receipt_settings` (
  `id` int(11) NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `business_address` text DEFAULT NULL,
  `business_phone` varchar(20) DEFAULT NULL,
  `business_email` varchar(100) DEFAULT NULL,
  `tax_rate` decimal(5,2) DEFAULT 10.00,
  `tax_identification_number` varchar(50) DEFAULT NULL,
  `receipt_footer` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `receipt_settings`
--

INSERT INTO `receipt_settings` (`id`, `business_name`, `business_address`, `business_phone`, `business_email`, `tax_rate`, `tax_identification_number`, `receipt_footer`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Blossom Collection ', 'Katunjila Complex shop 3 & 4, Chachacha Rd. ', '+260977384024', 'blossomcollections@gmail.com', 5.00, '1007515019', 'Thank you for your business! Returns accepted within 7 days with original receipt.', 1, '2025-10-20 04:42:03', '2025-10-20 08:38:45');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `sale_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `order_id`, `product_id`, `quantity`, `unit_price`, `total_amount`, `sale_date`, `created_at`) VALUES
(1, 1, 1, 1, 150.00, 150.00, '2025-10-20', '2025-10-20 01:51:36'),
(2, 1, 2, 1, 120.00, 120.00, '2025-10-20', '2025-10-20 01:51:36'),
(3, 2, 1, 1, 150.00, 150.00, '2025-10-20', '2025-10-20 01:55:16'),
(4, 2, 5, 1, 89.99, 89.99, '2025-10-20', '2025-10-20 01:55:16'),
(5, 2, 4, 1, 130.00, 130.00, '2025-10-20', '2025-10-20 01:55:16'),
(6, 3, 3, 1, 160.00, 160.00, '2025-10-20', '2025-10-20 01:57:00'),
(7, 3, 1, 1, 150.00, 150.00, '2025-10-20', '2025-10-20 01:57:00'),
(8, 4, 3, 1, 160.00, 160.00, '2025-10-20', '2025-10-20 02:23:57'),
(9, 5, 3, 1, 160.00, 160.00, '2025-10-20', '2025-10-20 07:38:39'),
(10, 5, 1, 1, 150.00, 150.00, '2025-10-20', '2025-10-20 07:38:39'),
(11, 6, 3, 4, 160.00, 640.00, '2025-10-20', '2025-10-20 14:07:08');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','salesperson','viewer') DEFAULT 'salesperson',
  `full_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `full_name`, `is_active`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@boutique.com', 'admin', 'System Administrator', 1, '2025-10-19 06:56:54'),
(2, 'muchi', '$2y$10$Rb.WR11dnAsObUWq2uMci.Y.Zc6h9YoR1yhIqMNE.cY0237x4eC2O', 'muchemwa.mutuka@gmail.com', 'salesperson', 'Muchemwa Mutuka', 1, '2025-10-20 02:17:45');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customer_types`
--
ALTER TABLE `customer_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type_name` (`type_name`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `orders_verified_by_fk` (`collection_verified_by`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `brand_id` (`brand_id`);

--
-- Indexes for table `receipt_settings`
--
ALTER TABLE `receipt_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customer_types`
--
ALTER TABLE `customer_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `receipt_settings`
--
ALTER TABLE `receipt_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_verified_by_fk` FOREIGN KEY (`collection_verified_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
