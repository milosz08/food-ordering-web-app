-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- Host: mysql26.mydevil.net
-- Generation Time: Jan 16, 2023 at 10:05 PM
-- Server version: 8.0.30
-- PHP Version: 7.3.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";



/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `si_proj`
--
CREATE DATABASE IF NOT EXISTS `si_proj` DEFAULT CHARACTER SET utf16 COLLATE utf16_polish_ci;
USE `si_proj`;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_type`
--

CREATE TABLE `delivery_type` (
  `id` bigint NOT NULL,
  `name` varchar(50) COLLATE utf16_polish_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf16 COLLATE=utf16_polish_ci;

--
-- Dumping data for table `delivery_type`
--

INSERT INTO `delivery_type` (`id`, `name`) VALUES
(1, 'Odbiór osobisty'),
(2, 'Dostawa kurierem');

-- --------------------------------------------------------

--
-- Table structure for table `discounts`
--

CREATE TABLE `discounts` (
  `id` bigint UNSIGNED NOT NULL,
  `code` varchar(20) CHARACTER SET utf16 COLLATE utf16_polish_ci DEFAULT NULL,
  `description` varchar(200) COLLATE utf16_polish_ci DEFAULT NULL,
  `percentage_discount` decimal(10,2) UNSIGNED NOT NULL,
  `usages` int UNSIGNED NOT NULL DEFAULT '0',
  `max_usages` int UNSIGNED DEFAULT NULL,
  `expired_date` date DEFAULT NULL,
  `restaurant_id` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf16 COLLATE=utf16_polish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dishes`
--

CREATE TABLE `dishes` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf16_polish_ci NOT NULL,
  `description` varchar(200) CHARACTER SET utf16 COLLATE utf16_polish_ci DEFAULT NULL,
  `photo_url` varchar(500) COLLATE utf16_polish_ci DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `prepared_time` int UNSIGNED NOT NULL DEFAULT '5',
  `dish_type_id` bigint UNSIGNED DEFAULT NULL,
  `restaurant_id` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf16 COLLATE=utf16_polish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dish_types`
--

CREATE TABLE `dish_types` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(50) COLLATE utf16_polish_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf16 COLLATE=utf16_polish_ci;


-- --------------------------------------------------------

--
-- Table structure for table `notifs_grades_to_delete`
--

CREATE TABLE `notifs_grades_to_delete` (
  `id` bigint UNSIGNED NOT NULL,
  `description` varchar(350) CHARACTER SET utf16 COLLATE utf16_polish_ci DEFAULT NULL,
  `send_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type_id` bigint UNSIGNED DEFAULT NULL,
  `rating_id` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf16 COLLATE=utf16_polish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifs_grade_delete_types`
--

CREATE TABLE `notifs_grade_delete_types` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(50) CHARACTER SET utf16 COLLATE utf16_polish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf16 COLLATE=utf16_polish_ci;

--
-- Dumping data for table `notifs_grade_delete_types`
--

INSERT INTO `notifs_grade_delete_types` (`id`, `name`) VALUES
(4, 'Inny powód'),
(1, 'Opinia okazała się nieadekwatna'),
(3, 'Opinia w inny sposób niż powyższe łamie regulamin '),
(2, 'Opinia zawiera niecenzuralny kontekst wypowiedzi');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `discount_id` bigint UNSIGNED DEFAULT NULL,
  `status_id` bigint UNSIGNED NOT NULL DEFAULT '1',
  `order_adress` bigint UNSIGNED DEFAULT NULL,
  `delivery_type` bigint NOT NULL,
  `restaurant_id` bigint UNSIGNED DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `estimate_time` time DEFAULT NULL,
  `date_order` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `finish_order` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf16 COLLATE=utf16_polish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders_with_dishes`
--

CREATE TABLE `orders_with_dishes` (
  `order_id` bigint UNSIGNED DEFAULT NULL,
  `dish_id` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf16 COLLATE=utf16_polish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_status`
--

CREATE TABLE `order_status` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(30) COLLATE utf16_polish_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf16 COLLATE=utf16_polish_ci;

--
-- Dumping data for table `order_status`
--

INSERT INTO `order_status` (`id`, `name`) VALUES
(1, 'W trakcie realizacji'),
(2, 'Gotowe'),
(3, 'Anulowane');

-- --------------------------------------------------------

--
-- Table structure for table `ota_token_types`
--

CREATE TABLE `ota_token_types` (
  `id` bigint UNSIGNED NOT NULL,
  `type` varchar(20) COLLATE utf16_polish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf16 COLLATE=utf16_polish_ci;

--
-- Dumping data for table `ota_token_types`
--

INSERT INTO `ota_token_types` (`id`, `type`) VALUES
(1, 'change password'),
(2, 'activate account');

-- --------------------------------------------------------

--
-- Table structure for table `ota_user_token`
--

CREATE TABLE `ota_user_token` (
  `id` bigint UNSIGNED NOT NULL,
  `ota_token` varchar(10) COLLATE utf16_polish_ci NOT NULL,
  `expiration_date` datetime NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT '0',
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `type_id` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf16 COLLATE=utf16_polish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `restaurants`
--

CREATE TABLE `restaurants` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(50) COLLATE utf16_polish_ci NOT NULL,
  `street` varchar(100) COLLATE utf16_polish_ci NOT NULL,
  `building_locale_nr` varchar(10) COLLATE utf16_polish_ci NOT NULL,
  `post_code` varchar(6) COLLATE utf16_polish_ci NOT NULL,
  `city` varchar(60) COLLATE utf16_polish_ci NOT NULL,
  `phone_number` varchar(9) CHARACTER SET utf16 COLLATE utf16_polish_ci NOT NULL,
  `banner_url` varchar(1000) CHARACTER SET utf16 COLLATE utf16_polish_ci DEFAULT NULL,
  `profile_url` varchar(1000) CHARACTER SET utf16 COLLATE utf16_polish_ci DEFAULT NULL,
  `delivery_price` decimal(10,2) UNSIGNED DEFAULT NULL,
  `min_price` decimal(10,2) UNSIGNED DEFAULT NULL,
  `description` varchar(600) CHARACTER SET utf16 COLLATE utf16_polish_ci NOT NULL DEFAULT '',
  `accept` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `user_id` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf16 COLLATE=utf16_polish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `restaurants_grades`
--

CREATE TABLE `restaurants_grades` (
  `id` bigint UNSIGNED NOT NULL,
  `restaurant_grade` tinyint UNSIGNED NOT NULL DEFAULT '5',
  `delivery_grade` tinyint UNSIGNED NOT NULL DEFAULT '5',
  `description` varchar(200) CHARACTER SET utf16 COLLATE utf16_polish_ci DEFAULT NULL,
  `give_on` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `anonymously` bit(1) NOT NULL DEFAULT b'0',
  `pending_to_delete` bit(1) NOT NULL DEFAULT b'0',
  `order_id` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf16 COLLATE=utf16_polish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_hours`
--

CREATE TABLE `restaurant_hours` (
  `id` bigint UNSIGNED NOT NULL,
  `open_hour` time NOT NULL,
  `close_hour` time NOT NULL,
  `weekday_id` bigint UNSIGNED DEFAULT NULL,
  `restaurant_id` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf16 COLLATE=utf16_polish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(30) COLLATE utf16_polish_ci NOT NULL,
  `role_eng` varchar(5) CHARACTER SET utf16 COLLATE utf16_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf16 COLLATE=utf16_polish_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `role_eng`) VALUES
(1, 'klient', 'user'),
(2, 'właściciel', 'owner'),
(3, 'administrator', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `first_name` varchar(50) COLLATE utf16_polish_ci NOT NULL,
  `last_name` varchar(50) COLLATE utf16_polish_ci NOT NULL,
  `login` varchar(30) COLLATE utf16_polish_ci NOT NULL,
  `password` char(72) CHARACTER SET utf16 COLLATE utf16_polish_ci NOT NULL,
  `email` varchar(100) COLLATE utf16_polish_ci NOT NULL,
  `phone_number` varchar(9) CHARACTER SET utf16 COLLATE utf16_polish_ci NOT NULL,
  `role_id` bigint UNSIGNED DEFAULT NULL,
  `photo_url` varchar(500) CHARACTER SET utf16 COLLATE utf16_polish_ci DEFAULT NULL,
  `is_activated` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf16 COLLATE=utf16_polish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `login`, `password`, `email`, `phone_number`, `role_id`, `is_activated`) VALUES
(1,'User','Test','user123','$2y$10$50xuXULao/W6HcXATy7Dqe6Z6AYtCJBqJyb7cCLB/mCzmZq6HLcse', 'user123@restaurant.miloszgilga.pl','123456789', 1, 1),
(2,'Owner','Test','owner123','$2y$10$50xuXULao/W6HcXATy7Dqe6Z6AYtCJBqJyb7cCLB/mCzmZq6HLcse', 'owner123@restaurant.miloszgilga.pl','549812130', 2, 1),
(3,'Admin','Test','admin123','$2y$10$50xuXULao/W6HcXATy7Dqe6Z6AYtCJBqJyb7cCLB/mCzmZq6HLcse', 'admin123@restaurant.miloszgilga.pl','289999999', 3, 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_address`
--

CREATE TABLE `user_address` (
  `id` bigint UNSIGNED NOT NULL,
  `street` varchar(100) COLLATE utf16_polish_ci NOT NULL,
  `building_nr` varchar(5) CHARACTER SET utf16 COLLATE utf16_polish_ci NOT NULL,
  `locale_nr` varchar(5) CHARACTER SET utf16 COLLATE utf16_polish_ci DEFAULT NULL,
  `post_code` varchar(6) COLLATE utf16_polish_ci NOT NULL,
  `city` varchar(60) CHARACTER SET utf16 COLLATE utf16_polish_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `is_prime` bit(1) NOT NULL DEFAULT b'0'
) ENGINE=InnoDB DEFAULT CHARSET=utf16 COLLATE=utf16_polish_ci;

--
-- Dumping data for table `user_address`
--

INSERT INTO `user_address` (`id`, `street`, `building_nr`, `locale_nr`, `post_code`, `city`, `user_id`, `is_prime`) VALUES
(1, 'Bolesława Krzywoustego','2b', NULL,'43-410','Katowice',1,b'1'),
(2, 'Akademicka','10','42','42-289','Gliwice',2,b'1'),
(3, 'Łużycka','9a','42','44-222','Gliwice',3,b'1');

-- --------------------------------------------------------

--
-- Table structure for table `weekdays`
--

CREATE TABLE `weekdays` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(12) CHARACTER SET utf16 COLLATE utf16_polish_ci NOT NULL,
  `name_eng` varchar(9) CHARACTER SET utf16 COLLATE utf16_general_ci NOT NULL,
  `alias` varchar(3) CHARACTER SET utf16 COLLATE utf16_polish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf16 COLLATE=utf16_polish_ci;

--
-- Dumping data for table `weekdays`
--

INSERT INTO `weekdays` (`id`, `name`, `name_eng`, `alias`) VALUES
(1, 'poniedziałek', 'monday', 'pn'),
(2, 'wtorek', 'tuesday', 'wt'),
(3, 'środa', 'wednesday', 'śr'),
(4, 'czwartek', 'thursday', 'czw'),
(5, 'piątek', 'friday', 'pt'),
(6, 'sobota', 'saturday', 'sb'),
(7, 'niedziela', 'sunday', 'ndz');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `delivery_type`
--
ALTER TABLE `delivery_type`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `discounts`
--
ALTER TABLE `discounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restaurant_id` (`restaurant_id`);

--
-- Indexes for table `dishes`
--
ALTER TABLE `dishes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`),
  ADD KEY `dishes_ibfk_1` (`dish_type_id`),
  ADD KEY `restaurant_id` (`restaurant_id`);

--
-- Indexes for table `dish_types`
--
ALTER TABLE `dish_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifs_grades_to_delete`
--
ALTER TABLE `notifs_grades_to_delete`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type_id` (`type_id`),
  ADD KEY `rating_id` (`rating_id`);

--
-- Indexes for table `notifs_grade_delete_types`
--
ALTER TABLE `notifs_grade_delete_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `orders_ibfk_1` (`discount_id`),
  ADD KEY `orders_ibfk_3` (`status_id`),
  ADD KEY `orders_ibfk_6` (`delivery_type`),
  ADD KEY `restaurant_id` (`restaurant_id`),
  ADD KEY `order_adress` (`order_adress`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders_with_dishes`
--
ALTER TABLE `orders_with_dishes`
  ADD KEY `dish_in_order_ibfk_1` (`order_id`),
  ADD KEY `dish_in_order_ibfk_2` (`dish_id`);

--
-- Indexes for table `order_status`
--
ALTER TABLE `order_status`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ota_token_types`
--
ALTER TABLE `ota_token_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ota_user_token`
--
ALTER TABLE `ota_user_token`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ota_token` (`ota_token`),
  ADD KEY `type_id` (`type_id`),
  ADD KEY `ota_user_token_ibfk_1` (`user_id`);

--
-- Indexes for table `restaurants`
--
ALTER TABLE `restaurants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `name` (`name`),
  ADD KEY `accept` (`accept`),
  ADD KEY `min_price` (`min_price`),
  ADD KEY `delivery_price` (`delivery_price`);

--
-- Indexes for table `restaurants_grades`
--
ALTER TABLE `restaurants_grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id_2` (`order_id`),
  ADD KEY `grade` (`restaurant_grade`);

--
-- Indexes for table `restaurant_hours`
--
ALTER TABLE `restaurant_hours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `open_hour` (`open_hour`),
  ADD KEY `close_hour` (`close_hour`),
  ADD KEY `restaurant_id` (`restaurant_id`),
  ADD KEY `restaurant_hours_ibfk_1` (`weekday_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`login`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone_number` (`phone_number`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `user_address`
--
ALTER TABLE `user_address`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `weekdays`
--
ALTER TABLE `weekdays`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`),
  ADD KEY `alias` (`alias`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `delivery_type`
--
ALTER TABLE `delivery_type`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `discounts`
--
ALTER TABLE `discounts`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `dishes`
--
ALTER TABLE `dishes`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

--
-- AUTO_INCREMENT for table `dish_types`
--
ALTER TABLE `dish_types`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `notifs_grades_to_delete`
--
ALTER TABLE `notifs_grades_to_delete`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifs_grade_delete_types`
--
ALTER TABLE `notifs_grade_delete_types`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT for table `order_status`
--
ALTER TABLE `order_status`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ota_token_types`
--
ALTER TABLE `ota_token_types`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ota_user_token`
--
ALTER TABLE `ota_user_token`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=156;

--
-- AUTO_INCREMENT for table `restaurants`
--
ALTER TABLE `restaurants`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT for table `restaurants_grades`
--
ALTER TABLE `restaurants_grades`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `restaurant_hours`
--
ALTER TABLE `restaurant_hours`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `user_address`
--
ALTER TABLE `user_address`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `weekdays`
--
ALTER TABLE `weekdays`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `discounts`
--
ALTER TABLE `discounts`
  ADD CONSTRAINT `discounts_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `dishes`
--
ALTER TABLE `dishes`
  ADD CONSTRAINT `dishes_ibfk_1` FOREIGN KEY (`dish_type_id`) REFERENCES `dish_types` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `dishes_ibfk_2` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `dish_types`
--
ALTER TABLE `dish_types`
  ADD CONSTRAINT `dish_types_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifs_grades_to_delete`
--
ALTER TABLE `notifs_grades_to_delete`
  ADD CONSTRAINT `notifs_grades_to_delete_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `notifs_grade_delete_types` (`id`),
  ADD CONSTRAINT `notifs_grades_to_delete_ibfk_2` FOREIGN KEY (`rating_id`) REFERENCES `restaurants_grades` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`discount_id`) REFERENCES `discounts` (`id`) ON DELETE SET NULL ON UPDATE SET NULL,
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`status_id`) REFERENCES `order_status` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `orders_ibfk_6` FOREIGN KEY (`delivery_type`) REFERENCES `delivery_type` (`id`),
  ADD CONSTRAINT `orders_ibfk_7` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE SET NULL ON UPDATE SET NULL,
  ADD CONSTRAINT `orders_ibfk_8` FOREIGN KEY (`order_adress`) REFERENCES `user_address` (`id`) ON DELETE SET NULL ON UPDATE SET NULL,
  ADD CONSTRAINT `orders_ibfk_9` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE SET NULL;

--
-- Constraints for table `orders_with_dishes`
--
ALTER TABLE `orders_with_dishes`
  ADD CONSTRAINT `dish_in_order_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `dish_in_order_ibfk_2` FOREIGN KEY (`dish_id`) REFERENCES `dishes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ota_user_token`
--
ALTER TABLE `ota_user_token`
  ADD CONSTRAINT `ota_user_token_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ota_user_token_ibfk_2` FOREIGN KEY (`type_id`) REFERENCES `ota_token_types` (`id`) ON DELETE SET NULL ON UPDATE SET NULL;

--
-- Constraints for table `restaurants`
--
ALTER TABLE `restaurants`
  ADD CONSTRAINT `restaurants_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `restaurants_grades`
--
ALTER TABLE `restaurants_grades`
  ADD CONSTRAINT `restaurants_grades_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE SET NULL;

--
-- Constraints for table `restaurant_hours`
--
ALTER TABLE `restaurant_hours`
  ADD CONSTRAINT `restaurant_hours_ibfk_1` FOREIGN KEY (`weekday_id`) REFERENCES `weekdays` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `restaurant_hours_ibfk_2` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `user_address`
--
ALTER TABLE `user_address`
  ADD CONSTRAINT `user_address_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`m1428_admin`@`%.devil` EVENT `remove_not_activated_user_scheduler` ON SCHEDULE EVERY 2 DAY STARTS '2022-12-11 14:56:44' ON COMPLETION NOT PRESERVE ENABLE COMMENT 'Usuwanie usera po 48h nieaktywowania konta.' DO DELETE FROM users WHERE is_activated = false$$

CREATE DEFINER=`m1428_admin`@`%.devil` EVENT `remove_expired_ota_token_scheduler` ON SCHEDULE EVERY 1 DAY STARTS '2022-12-11 14:53:06' ON COMPLETION PRESERVE ENABLE COMMENT 'Usuwanie OTA token po wygaśnięciu i nieużyciu po 24h.' DO DELETE FROM ota_user_token WHERE expiration_date < NOW() AND is_used = false$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
