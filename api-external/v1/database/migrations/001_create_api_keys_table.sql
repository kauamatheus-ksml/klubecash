-- Tabela para armazenar API Keys de parceiros
CREATE TABLE `api_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key_hash` varchar(255) NOT NULL,
  `key_prefix` varchar(10) NOT NULL,
  `partner_name` varchar(100) NOT NULL,
  `partner_email` varchar(100) NOT NULL,
  `permissions` TEXT NOT NULL, -- JSON com permissões
  `rate_limit_per_minute` int(11) DEFAULT 60,
  `rate_limit_per_hour` int(11) DEFAULT 1000,
  `is_active` tinyint(1) DEFAULT 1,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  `webhook_url` varchar(255) NULL DEFAULT NULL,
  `webhook_secret` varchar(255) NULL DEFAULT NULL,
  `notes` text NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_hash` (`key_hash`),
  UNIQUE KEY `key_prefix` (`key_prefix`),
  INDEX `partner_email` (`partner_email`),
  INDEX `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para controle de rate limiting
CREATE TABLE `api_rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `api_key_id` int(11) NOT NULL,
  `endpoint` varchar(255) NOT NULL,
  `requests_count` int(11) DEFAULT 0,
  `window_start` timestamp DEFAULT CURRENT_TIMESTAMP,
  `window_type` enum('minute','hour','day') DEFAULT 'minute',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`api_key_id`) REFERENCES `api_keys` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_rate_limit` (`api_key_id`, `endpoint`, `window_type`, `window_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para logs de API
CREATE TABLE `api_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `api_key_id` int(11) NULL DEFAULT NULL,
  `endpoint` varchar(255) NOT NULL,
  `method` varchar(10) NOT NULL,
  `status_code` int(11) NOT NULL,
  `response_time_ms` int(11) NULL DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text NULL DEFAULT NULL,
  `request_body` text NULL DEFAULT NULL,
  `response_body` text NULL DEFAULT NULL,
  `error_message` text NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`api_key_id`) REFERENCES `api_keys` (`id`) ON DELETE SET NULL,
  INDEX `endpoint` (`endpoint`),
  INDEX `api_key_id` (`api_key_id`),
  INDEX `created_at` (`created_at`),
  INDEX `status_code` (`status_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir uma API Key de exemplo para testes
INSERT INTO `api_keys` (
  `key_hash`, 
  `key_prefix`, 
  `partner_name`, 
  `partner_email`, 
  `permissions`,
  `notes`
) VALUES (
  SHA2(CONCAT('kc_test_key_12345678901234567890123456789012345678901234567890123456', UNIX_TIMESTAMP()), 256),
  'kc_test',
  'Partner de Teste',
  'teste@example.com',
  '["users.read", "users.create", "stores.read", "transactions.read", "transactions.create", "cashback.read", "cashback.calculate"]',
  'API Key para testes de desenvolvimento'
);