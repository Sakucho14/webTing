-- Base de Datos para Webting v3.5 --

CREATE DATABASE IF NOT EXISTS `webting_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `webting_db`;

-- Tabla de usuarios --
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('player', 'admin') DEFAULT 'player',
  `email_verified` TINYINT(1) DEFAULT 0,
  `verification_code` VARCHAR(6) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Usuario Admin por Defecto: //admin// / loger2010 --
INSERT INTO `users` (`username`, `email`, `password_hash`, `role`, `email_verified`) 
VALUES ('//admin//', 'admin@webting.com', '$2y$10$w/O1d6R9uM2H40a/K5y80OaO3n2D4L9n3n2D4L9n3n2D4L9n3n2D4', 'admin', 1)
ON DUPLICATE KEY UPDATE `role`='admin';

-- Tabla de juegos (sin juegos por defecto) --
CREATE TABLE IF NOT EXISTS `games` (
  `id` VARCHAR(50) PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `subject` VARCHAR(50) NOT NULL,
  `difficulty` ENUM('Facil', 'Medio', 'Dificil') NOT NULL,
  `age_range` VARCHAR(20) NOT NULL,
  `competency` VARCHAR(100) NOT NULL,
  `logo_url` TEXT,
  `hover_description` TEXT,
  `iframe_url` TEXT NOT NULL,
  `iframe_scroll` TINYINT(1) DEFAULT 0,
  `iframe_sandbox` VARCHAR(200) DEFAULT '',
  `iframe_fullscreen` TINYINT(1) DEFAULT 1,
  `scoring_enabled` TINYINT(1) DEFAULT 1,
  `play_count` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de controles por juego --
CREATE TABLE IF NOT EXISTS `game_controls` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `game_id` VARCHAR(50) NOT NULL,
  `key_name` VARCHAR(50) NOT NULL,
  `action_desc` VARCHAR(150) NOT NULL,
  FOREIGN KEY (`game_id`) REFERENCES `games`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de puntuaciones --
CREATE TABLE IF NOT EXISTS `scores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `game_id` VARCHAR(50) NOT NULL,
  `user_id` INT NULL,
  `player_name` VARCHAR(50) NOT NULL,
  `score` INT NOT NULL,
  `scored_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`game_id`) REFERENCES `games`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de personalización del sitio --
CREATE TABLE IF NOT EXISTS `site_settings` (
  `setting_key` VARCHAR(50) PRIMARY KEY,
  `setting_value` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ajustes predeterminados del sitio --
INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('banner_title', 'Aprende Jugando en Vivo'),
('banner_subtitle', 'Plataforma de juegos educativos interactivos con emulacion en tiempo real.'),
('primary_color', '#FF5500'),
('secondary_color', '#00C2FF'),
('hero_image', 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=1200&q=80')
ON DUPLICATE KEY UPDATE `setting_key`=`setting_key`;

-- Nueva Tabla de Documentos (CMS) --
CREATE TABLE IF NOT EXISTS `documents` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `content_html` LONGTEXT NOT NULL,
  `published` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
