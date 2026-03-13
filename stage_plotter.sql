-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 10, 2026 at 08:18 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

-- Uncomment the following when adding table data:
-- SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
-- START TRANSACTION;
-- SET time_zone = "+00:00";

--
-- Database: `stage_plotter`
--
CREATE DATABASE IF NOT EXISTS `stage_plotter` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `stage_plotter`;

-- --------------------------------------------------------

--
-- Table structure for table `input_list_channel_inplstch`
--

DROP TABLE IF EXISTS `input_list_channel_inplstch`;
CREATE TABLE IF NOT EXISTS `input_list_channel_inplstch` (
  `id_inplstch` int(11) NOT NULL AUTO_INCREMENT,
  `id_inplst_inplstch` int(11) NOT NULL,
  `channel_num_inplstch` int(11) NOT NULL,
  `label_inplstch` varchar(100) NOT NULL DEFAULT '',
  `id_pele_inplstch` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_inplstch`),
  UNIQUE KEY `input_list_channel_inplstch_index_0` (`id_inplst_inplstch`,`channel_num_inplstch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `input_list_inplst`
--

DROP TABLE IF EXISTS `input_list_inplst`;
CREATE TABLE IF NOT EXISTS `input_list_inplst` (
  `id_inplst` int(11) NOT NULL AUTO_INCREMENT,
  `id_staplot_inplst` int(11) NOT NULL,
  `notes_inplst` text DEFAULT NULL,
  PRIMARY KEY (`id_inplst`),
  UNIQUE KEY `id_staplot_inplst` (`id_staplot_inplst`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plot_element_pele`
--

DROP TABLE IF EXISTS `plot_element_pele`;
CREATE TABLE IF NOT EXISTS `plot_element_pele` (
  `id_pele`        int(11)                                                            NOT NULL AUTO_INCREMENT,
  `id_staplot_pele` int(11)                                                           NOT NULL,
  `x_pos_pele`     decimal(6,2)                                                       NOT NULL,
  `y_pos_pele`     decimal(6,2)                                                       NOT NULL,
  `rotation_pele`  smallint(6)                                                        NOT NULL DEFAULT 0,
  `z_index_pele`   int(11)                                                            NOT NULL DEFAULT 1,
  `px_size_pele`   int(11)                                                            NOT NULL DEFAULT 48,
  `src_pele`       varchar(255)                                                       NOT NULL DEFAULT '',
  `type_pele`      enum('Guitar','Percussion','Keys','Strings','Winds','Amps','Misc') NOT NULL DEFAULT 'Misc',
  `name_pele`      varchar(100)                                                       NOT NULL DEFAULT '',
  `flipped_pele`   tinyint(1)                                                         NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_pele`),
  KEY `id_staplot_pele` (`id_staplot_pele`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plot_permission_pltperm`
--

DROP TABLE IF EXISTS `plot_permission_pltperm`;
CREATE TABLE IF NOT EXISTS `plot_permission_pltperm` (
  `id_pltperm` int(11) NOT NULL AUTO_INCREMENT,
  `id_staplot_pltperm` int(11) NOT NULL,
  `id_usr_pltperm` int(11) NOT NULL,
  `permission_level_pltperm` enum('view','edit','admin') NOT NULL,
  `created_at_pltperm` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_pltperm`),
  UNIQUE KEY `plot_permission_pltperm_index_1` (`id_staplot_pltperm`,`id_usr_pltperm`),
  KEY `id_usr_pltperm` (`id_usr_pltperm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shared_plot_shrplot`
--

DROP TABLE IF EXISTS `shared_plot_shrplot`;
CREATE TABLE IF NOT EXISTS `shared_plot_shrplot` (
  `id_shrplot` int(11) NOT NULL AUTO_INCREMENT,
  `id_staplot_shrplot` int(11) NOT NULL,
  `share_token_shrplot` varchar(64) NOT NULL,
  `created_at_shrplot` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_shrplot`),
  UNIQUE KEY `share_token_shrplot` (`share_token_shrplot`),
  KEY `id_staplot_shrplot` (`id_staplot_shrplot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stage_plot_staplot`
--

DROP TABLE IF EXISTS `stage_plot_staplot`;
CREATE TABLE IF NOT EXISTS `stage_plot_staplot` (
  `id_staplot` int(11) NOT NULL AUTO_INCREMENT,
  `title_staplot` varchar(50) NOT NULL,
  `gig_date_staplot` date NOT NULL,
  `venue_staplot` varchar(100) DEFAULT NULL,
  `description_staplot` varchar(255) DEFAULT NULL,
  `width_staplot` decimal(6,2) NOT NULL DEFAULT 50.00,
  `depth_staplot` decimal(6,2) NOT NULL DEFAULT 40.00,
  `created_at_staplot` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at_staplot` timestamp NOT NULL DEFAULT current_timestamp()
                                          ON UPDATE current_timestamp(),
  `is_active_staplot` tinyint(1) NOT NULL DEFAULT 1,
  `is_public_staplot` tinyint(1) NOT NULL DEFAULT 0,
  `id_usr_staplot` int(11) NOT NULL,
  PRIMARY KEY (`id_staplot`),
  KEY `id_usr_staplot` (`id_usr_staplot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_usr`
--

DROP TABLE IF EXISTS `user_usr`;
CREATE TABLE IF NOT EXISTS `user_usr` (
  `id_usr` int(11) NOT NULL AUTO_INCREMENT,
  `first_name_usr` varchar(50) NOT NULL,
  `last_name_usr` varchar(50) NOT NULL,
  `email_usr` varchar(100) NOT NULL,
  `phone_usr` varchar(20) DEFAULT NULL,
  `username_usr` varchar(20) NOT NULL,
  `password_hash_usr` varchar(255) NOT NULL,
  `role_usr` ENUM('member', 'admin', 'super_admin') NOT NULL DEFAULT 'member',
  `is_active_usr` tinyint(1) DEFAULT 1,
  `created_at_usr` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at_usr` timestamp NOT NULL DEFAULT current_timestamp()
                                      ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_usr`),
  UNIQUE KEY `email_usr` (`email_usr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Constraints for dumped tables
--

--
-- Constraints for table `input_list_channel_inplstch`
--
ALTER TABLE `input_list_channel_inplstch`
  ADD CONSTRAINT `input_list_channel_inplstch_ibfk_2` FOREIGN KEY (`id_inplst_inplstch`) REFERENCES `input_list_inplst` (`id_inplst`);

--
-- Constraints for table `input_list_inplst`
--
ALTER TABLE `input_list_inplst`
  ADD CONSTRAINT `input_list_inplst_ibfk_1` FOREIGN KEY (`id_staplot_inplst`) REFERENCES `stage_plot_staplot` (`id_staplot`);

--
-- Constraints for table `plot_element_pele`
--
ALTER TABLE `plot_element_pele`
  ADD CONSTRAINT `plot_element_pele_ibfk_1` FOREIGN KEY (`id_staplot_pele`) REFERENCES `stage_plot_staplot` (`id_staplot`);

--
-- Constraints for table `plot_permission_pltperm`
--
ALTER TABLE `plot_permission_pltperm`
  ADD CONSTRAINT `plot_permission_pltperm_ibfk_1` FOREIGN KEY (`id_staplot_pltperm`) REFERENCES `stage_plot_staplot` (`id_staplot`),
  ADD CONSTRAINT `plot_permission_pltperm_ibfk_2` FOREIGN KEY (`id_usr_pltperm`) REFERENCES `user_usr` (`id_usr`);

--
-- Constraints for table `shared_plot_shrplot`
--
ALTER TABLE `shared_plot_shrplot`
  ADD CONSTRAINT `shared_plot_shrplot_ibfk_1` FOREIGN KEY (`id_staplot_shrplot`) REFERENCES `stage_plot_staplot` (`id_staplot`);

--
-- Constraints for table `stage_plot_staplot`
--
ALTER TABLE `stage_plot_staplot`
  ADD CONSTRAINT `stage_plot_staplot_ibfk_1` FOREIGN KEY (`id_usr_staplot`) REFERENCES `user_usr` (`id_usr`);

COMMIT;

-- Note: The following are just default admin and super_admin accounts for testing and should be changed or removed in production.
--
-- DUMP admin user for testing
-- username: admin
-- password: password (hashed in the database)
--

INSERT INTO user_usr (first_name_usr, last_name_usr, email_usr, phone_usr, username_usr, password_hash_usr, role_usr, is_active_usr, created_at_usr, updated_at_usr)
VALUES ('Alyssa', 'Companioni', 'alyssamcompanioni@students.abtech.edu', '828-123-1234', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, current_timestamp(), current_timestamp());

--
-- DUMP super_admin user for testing
-- username: superadmin
-- password: password (hashed in the database)
--

INSERT INTO user_usr (first_name_usr, last_name_usr, email_usr, phone_usr, username_usr, password_hash_usr, role_usr, is_active_usr, created_at_usr, updated_at_usr)
VALUES ('Alyssa', 'Companioni', 'alyssamcompanioni@google.com', '828-123-1234', 'superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 1, current_timestamp(), current_timestamp());
