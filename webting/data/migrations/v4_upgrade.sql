-- Webting V4 Upgrade Script

-- 1. Alterar tabla de usuarios para añadir recompensas
ALTER TABLE `users`
ADD COLUMN `points` INT DEFAULT 0 AFTER `role`,
ADD COLUMN `tier` ENUM('Bronce', 'Plata', 'Oro', 'Platino') DEFAULT 'Bronce' AFTER `points`;

-- 2. Sistema de Documentos
CREATE TABLE IF NOT EXISTS `document_files` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `file_type` VARCHAR(50) NOT NULL,
  `category` VARCHAR(100) DEFAULT 'General',
  `version` INT DEFAULT 1,
  `permissions` JSON DEFAULT NULL,
  `uploaded_by` INT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `document_versions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `document_id` INT NOT NULL,
  `version_num` INT NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `archived_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`document_id`) REFERENCES `document_files`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Sistema de Recompensas
CREATE TABLE IF NOT EXISTS `reward_catalog` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT,
  `cost_points` INT NOT NULL,
  `image_url` VARCHAR(255) DEFAULT '',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `reward_transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `type` ENUM('earn', 'redeem') NOT NULL,
  `amount` INT NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar recompensas por defecto
INSERT INTO `reward_catalog` (`title`, `description`, `cost_points`) VALUES
('Medalla de Pionero', 'Insignia exclusiva por participación temprana.', 500),
('Avatar Especial', 'Desbloquea un avatar premium para tu perfil.', 1000),
('Doble Puntuación (24h)', 'Tus puntos se duplican por 24 horas en los juegos.', 2500);
