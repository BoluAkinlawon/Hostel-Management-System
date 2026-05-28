-- ═══════════════════════════════════════════════════════════════════════
-- Hostel Allocation Portal — Production Database Schema
-- ═══════════════════════════════════════════════════════════════════════
-- 1. Create database:  CREATE DATABASE rooms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- 2. Create a dedicated DB user (never use root in production):
--      CREATE USER 'rooms_user'@'localhost' IDENTIFIED BY 'strong_password_here';
--      GRANT SELECT, INSERT, UPDATE, DELETE ON rooms.* TO 'rooms_user'@'localhost';
--      FLUSH PRIVILEGES;
-- 3. Import this file: mysql -u rooms_user -p rooms < rooms.sql
-- 4. Run setup.php ONCE to hash the admin password, then DELETE setup.php.
-- ═══════════════════════════════════════════════════════════════════════

SET SQL_MODE   = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone  = "+00:00";
START TRANSACTION;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ─── admin ───────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `admin` (
  `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(50)  NOT NULL,
  `password`   VARCHAR(255) NOT NULL,          -- bcrypt hash; set via setup.php
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── users ───────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`             INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `firstname`      VARCHAR(100) NOT NULL,
  `lastname`       VARCHAR(100) NOT NULL,
  `email`          VARCHAR(150) DEFAULT NULL,
  `password`       VARCHAR(255) NOT NULL,          -- bcrypt; min cost 12
  `gender`         ENUM('Male','Female','Other') NOT NULL,
  `matric_number`  VARCHAR(30)  NOT NULL,
  `level`          SMALLINT(4)  NOT NULL,           -- 100/200/300/400
  `student_phone`  VARCHAR(20)  NOT NULL,
  `parent_phone`   VARCHAR(20)  NOT NULL,
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_matric`  (`matric_number`),
  UNIQUE KEY `uq_email`   (`email`),
  KEY `idx_level` (`level`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── hostel ──────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `hostel` (
  `id`             INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `matric_number`  VARCHAR(30)  NOT NULL,
  `department`     VARCHAR(120) NOT NULL,
  `level`          SMALLINT(4)  NOT NULL,
  `block`          TINYINT(3) UNSIGNED NOT NULL,
  `room_no`        TINYINT(3) UNSIGNED NOT NULL,
  `allocated_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_matric`   (`matric_number`),        -- one student, one room
  KEY `idx_block_room` (`block`, `room_no`),         -- occupancy checks
  KEY `idx_allocated`  (`allocated_at`),
  CONSTRAINT `fk_hostel_user`
    FOREIGN KEY (`matric_number`) REFERENCES `users` (`matric_number`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
