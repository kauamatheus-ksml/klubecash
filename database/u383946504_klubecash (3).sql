-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 21/09/2025 às 00:42
-- Versão do servidor: 11.8.3-MariaDB-log
-- Versão do PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `u383946504_klubecash`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `admin_reserva_cashback`
--

CREATE TABLE `admin_reserva_cashback` (
  `id` int(11) NOT NULL,
  `valor_total` decimal(10,2) DEFAULT 0.00,
  `valor_disponivel` decimal(10,2) DEFAULT 0.00,
  `valor_usado` decimal(10,2) DEFAULT 0.00,
  `ultima_atualizacao` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `admin_reserva_cashback`
--

INSERT INTO `admin_reserva_cashback` (`id`, `valor_total`, `valor_disponivel`, `valor_usado`, `ultima_atualizacao`) VALUES
(1, 21.70, 21.70, 0.00, '2025-09-19 19:27:05');

-- --------------------------------------------------------

--
-- Estrutura para tabela `admin_reserva_movimentacoes`
--

CREATE TABLE `admin_reserva_movimentacoes` (
  `id` int(11) NOT NULL,
  `transacao_id` int(11) DEFAULT NULL,
  `valor` decimal(10,2) DEFAULT NULL,
  `tipo` enum('credito','debito') DEFAULT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `data_operacao` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `admin_reserva_movimentacoes`
--

INSERT INTO `admin_reserva_movimentacoes` (`id`, `transacao_id`, `valor`, `tipo`, `descricao`, `data_operacao`) VALUES
(16, 1186, 7.00, 'credito', 'Reserva de cashback - Pagamento #1186 aprovado - Total de clientes: 1', '2025-09-19 19:26:06'),
(17, 1185, 7.00, 'credito', 'Reserva de cashback - Pagamento #1185 aprovado - Total de clientes: 1', '2025-09-19 19:26:46'),
(18, 1184, 0.70, 'credito', 'Reserva de cashback - Pagamento #1184 aprovado - Total de clientes: 1', '2025-09-19 19:27:02'),
(19, 1183, 7.00, 'credito', 'Reserva de cashback - Pagamento #1183 aprovado - Total de clientes: 1', '2025-09-19 19:27:05');

-- --------------------------------------------------------

--
-- Estrutura para tabela `admin_saldo`
--

CREATE TABLE `admin_saldo` (
  `id` int(11) NOT NULL,
  `valor_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_disponivel` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_pendente` decimal(10,2) NOT NULL DEFAULT 0.00,
  `ultima_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `admin_saldo`
--

INSERT INTO `admin_saldo` (`id`, `valor_total`, `valor_disponivel`, `valor_pendente`, `ultima_atualizacao`) VALUES
(1, 0.00, 0.00, 0.00, '2025-09-17 21:10:33');

-- --------------------------------------------------------

--
-- Estrutura para tabela `admin_saldo_movimentacoes`
--

CREATE TABLE `admin_saldo_movimentacoes` (
  `id` int(11) NOT NULL,
  `transacao_id` int(11) DEFAULT NULL,
  `valor` decimal(10,2) NOT NULL,
  `tipo` enum('credito','debito') NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `data_operacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `api_keys`
--

CREATE TABLE `api_keys` (
  `id` int(11) NOT NULL,
  `key_hash` varchar(255) NOT NULL,
  `key_prefix` varchar(10) NOT NULL,
  `partner_name` varchar(100) NOT NULL,
  `partner_email` varchar(100) NOT NULL,
  `permissions` text NOT NULL,
  `rate_limit_per_minute` int(11) DEFAULT 60,
  `rate_limit_per_hour` int(11) DEFAULT 1000,
  `is_active` tinyint(1) DEFAULT 1,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `webhook_url` varchar(255) DEFAULT NULL,
  `webhook_secret` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `api_keys`
--

INSERT INTO `api_keys` (`id`, `key_hash`, `key_prefix`, `partner_name`, `partner_email`, `permissions`, `rate_limit_per_minute`, `rate_limit_per_hour`, `is_active`, `last_used_at`, `created_at`, `expires_at`, `webhook_url`, `webhook_secret`, `notes`) VALUES
(5, 'bb8ed1ec755809d6472a0b1ec1275a16fc497b71509eb0723eccc9e25810e186', 'kc_live', 'API Live Test', 'live@klubecash.com', '[\"*\"]', 1000, 10000, 1, '2025-08-25 22:49:35', '2025-08-25 22:18:01', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `api_logs`
--

CREATE TABLE `api_logs` (
  `id` int(11) NOT NULL,
  `api_key_id` int(11) DEFAULT NULL,
  `endpoint` varchar(255) NOT NULL,
  `method` varchar(10) NOT NULL,
  `status_code` int(11) NOT NULL,
  `response_time_ms` int(11) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `request_body` text DEFAULT NULL,
  `response_body` text DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `api_rate_limits`
--

CREATE TABLE `api_rate_limits` (
  `id` int(11) NOT NULL,
  `api_key_id` int(11) NOT NULL,
  `endpoint` varchar(255) NOT NULL,
  `requests_count` int(11) DEFAULT 0,
  `window_start` timestamp NULL DEFAULT current_timestamp(),
  `window_type` enum('minute','hour','day') DEFAULT 'minute'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cashback_movimentacoes`
--

CREATE TABLE `cashback_movimentacoes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `loja_id` int(11) NOT NULL,
  `criado_por` int(11) DEFAULT NULL,
  `tipo_operacao` enum('credito','uso','estorno') NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `saldo_anterior` decimal(10,2) NOT NULL,
  `saldo_atual` decimal(10,2) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `transacao_origem_id` int(11) DEFAULT NULL,
  `transacao_uso_id` int(11) DEFAULT NULL,
  `data_operacao` timestamp NULL DEFAULT current_timestamp(),
  `pagamento_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `cashback_movimentacoes`
--

INSERT INTO `cashback_movimentacoes` (`id`, `usuario_id`, `loja_id`, `criado_por`, `tipo_operacao`, `valor`, `saldo_anterior`, `saldo_atual`, `descricao`, `transacao_origem_id`, `transacao_uso_id`, `data_operacao`, `pagamento_id`) VALUES
(176, 142, 38, NULL, 'credito', 5.00, 7.50, 12.50, 'Cashback MVP instantâneo - Código: KC25091915200140626', 381, NULL, '2025-09-19 18:20:27', NULL),
(177, 184, 38, NULL, 'credito', 7.50, 0.00, 7.50, 'Cashback MVP instantâneo - Código: KC25091915360042525', 382, NULL, '2025-09-19 18:36:08', NULL),
(178, 9, 59, NULL, 'credito', 7.00, 10.00, 17.00, 'Cashback da compra - Transação #380 (Pagamento #1186 aprovado)', 380, NULL, '2025-09-19 19:26:06', NULL),
(179, 9, 59, NULL, 'credito', 7.00, 17.00, 24.00, 'Cashback da compra - Transação #379 (Pagamento #1185 aprovado)', 379, NULL, '2025-09-19 19:26:46', NULL),
(180, 9, 59, NULL, 'credito', 0.70, 24.00, 24.70, 'Cashback da compra - Transação #377 (Pagamento #1184 aprovado)', 377, NULL, '2025-09-19 19:27:02', NULL),
(181, 9, 59, NULL, 'credito', 7.00, 24.70, 31.70, 'Cashback da compra - Transação #378 (Pagamento #1183 aprovado)', 378, NULL, '2025-09-19 19:27:05', NULL),
(203, 9, 59, NULL, 'credito', 5.00, 31.70, 36.70, 'Cashback creditado via correção automática - Transação #372', 372, NULL, '2025-09-19 20:44:44', NULL),
(204, 180, 59, NULL, 'credito', 200.00, 200.00, 400.00, 'Cashback creditado via correção automática - Transação #367', 367, NULL, '2025-09-19 20:44:44', NULL),
(205, 9, 59, NULL, 'credito', 0.55, 36.70, 37.25, 'Cashback da compra - Transação #394 (Pagamento #1197 aprovado via MP)', 394, NULL, '2025-09-19 20:47:59', NULL),
(206, 9, 59, NULL, 'credito', 0.60, 37.25, 37.85, 'Cashback da compra - Transação #390 (Pagamento #1193 aprovado via MP)', 390, NULL, '2025-09-19 20:50:32', NULL),
(207, 9, 59, NULL, 'credito', 0.84, 37.85, 38.69, 'Cashback da compra - Transação #395 (Pagamento #1198 aprovado via MP)', 395, NULL, '2025-09-19 20:51:21', NULL),
(208, 142, 38, NULL, 'credito', 0.59, 12.50, 13.09, 'Cashback MVP instantâneo - Código: KC25091923122480714', 397, NULL, '2025-09-20 02:12:33', NULL),
(209, 142, 38, NULL, 'credito', 592.50, 13.09, 605.59, 'Cashback MVP instantâneo - Código: KC25091923130625250', 398, NULL, '2025-09-20 02:13:15', NULL),
(210, 9, 59, NULL, 'credito', 0.70, 38.69, 39.39, 'Cashback da compra - Transação #387 (Pagamento #1191 aprovado via MP)', 387, NULL, '2025-09-20 02:45:51', NULL),
(211, 9, 59, NULL, 'credito', 0.55, 39.39, 39.94, 'Cashback da compra - Transação #389 (Pagamento #1192 aprovado via MP)', 389, NULL, '2025-09-20 02:45:54', NULL),
(212, 9, 59, NULL, 'credito', 0.35, 39.94, 40.29, 'Cashback MVP instantâneo - Código: KC25092017244146332', 408, NULL, '2025-09-20 20:24:44', NULL),
(213, 9, 59, NULL, 'credito', 7.00, 40.29, 47.29, 'Cashback MVP instantâneo - Código: KC25092017333453271', 479, NULL, '2025-09-20 20:33:38', NULL),
(214, 9, 59, NULL, 'credito', 7.00, 47.29, 54.29, 'Cashback MVP instantâneo - Código: KC25092017580789307', 489, NULL, '2025-09-20 20:58:09', NULL),
(215, 9, 59, NULL, 'credito', 8.40, 54.29, 62.69, 'Cashback MVP instantâneo - Código: KC25092018020382205', 490, NULL, '2025-09-20 21:02:05', NULL),
(216, 162, 59, NULL, 'credito', 0.70, 0.00, 0.70, 'Cashback MVP instantâneo - Código: KC25092018040669205', 491, NULL, '2025-09-20 21:04:08', NULL),
(217, 9, 59, NULL, 'credito', 2.10, 62.69, 64.79, 'Cashback MVP instantâneo - Código: KC25092018153868724', 492, NULL, '2025-09-20 21:15:40', NULL),
(218, 9, 59, NULL, 'credito', 0.70, 64.79, 65.49, 'Cashback MVP instantâneo - Código: KC25092018205562020', 493, NULL, '2025-09-20 21:20:58', NULL),
(219, 9, 59, NULL, 'credito', 0.35, 65.49, 65.84, 'Cashback MVP instantâneo - Código: KC25092018262598117', 494, NULL, '2025-09-20 21:26:27', NULL),
(220, 9, 59, NULL, 'credito', 0.70, 65.84, 66.54, 'Cashback MVP instantâneo - Código: KC25092019244321620', 495, NULL, '2025-09-20 22:24:44', NULL),
(221, 9, 34, NULL, 'credito', 0.50, 0.00, 0.50, 'Cashback da compra - Transação #496 (Pagamento #1199 aprovado via MP)', 496, NULL, '2025-09-20 22:26:29', NULL),
(222, 9, 59, NULL, 'credito', 0.70, 66.54, 67.24, 'Cashback MVP instantâneo - Código: KC25092019330204471', 497, NULL, '2025-09-20 22:33:04', NULL),
(223, 9, 59, NULL, 'credito', 7.00, 67.24, 74.24, 'Cashback MVP instantâneo - Código: KC25092019385359159', 498, NULL, '2025-09-20 22:38:55', NULL),
(224, 9, 34, NULL, 'credito', 1.00, 0.50, 1.50, 'Cashback da compra - Transação #499 (Pagamento #1200 aprovado via MP)', 499, NULL, '2025-09-20 22:42:13', NULL),
(225, 9, 59, NULL, 'credito', 1.40, 74.24, 75.64, 'Cashback MVP instantâneo - Código: KC25092019505029714', 500, NULL, '2025-09-20 22:50:51', NULL),
(226, 9, 59, NULL, 'credito', 0.70, 75.64, 76.34, 'Cashback MVP instantâneo - Código: KC25092019543428478', 501, NULL, '2025-09-20 22:54:36', NULL),
(227, 9, 59, NULL, 'credito', 0.70, 76.34, 77.04, 'Cashback MVP instantâneo - Código: KC25092020281827847', 502, NULL, '2025-09-20 23:28:22', NULL),
(228, 9, 59, NULL, 'credito', 0.35, 77.04, 77.39, 'Cashback MVP instantâneo - Código: KC25092020354955166', 503, NULL, '2025-09-20 23:35:51', NULL),
(229, 9, 59, NULL, 'credito', 1.40, 77.39, 78.79, 'Cashback MVP instantâneo - Código: KC25092020402709755', 504, NULL, '2025-09-20 23:40:32', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `cashback_notificacoes`
--

CREATE TABLE `cashback_notificacoes` (
  `id` int(11) NOT NULL,
  `transacao_id` int(11) NOT NULL,
  `status` enum('enviada','erro','pendente') NOT NULL DEFAULT 'pendente',
  `observacao` text DEFAULT NULL,
  `data_tentativa` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cashback_notification_retries`
--

CREATE TABLE `cashback_notification_retries` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `attempts` int(11) DEFAULT 1,
  `last_error` text DEFAULT NULL,
  `next_retry` datetime DEFAULT NULL,
  `status` enum('pending','success','failed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `cashback_notification_retries`
--

INSERT INTO `cashback_notification_retries` (`id`, `transaction_id`, `attempts`, `last_error`, `next_retry`, `status`, `created_at`, `updated_at`) VALUES
(1, 999999, 1, 'Teste de falha simulada', '2025-09-20 18:30:43', 'pending', '2025-09-20 20:30:43', '2025-09-20 20:30:43'),
(2, 489, 1, 'HTTP Error: 400', '2025-09-20 18:58:09', 'pending', '2025-09-20 20:58:09', '2025-09-20 20:58:09'),
(3, 490, 1, 'HTTP Error: 400', '2025-09-20 19:02:05', 'pending', '2025-09-20 21:02:05', '2025-09-20 21:02:05'),
(4, 491, 1, 'HTTP Error: 400', '2025-09-20 19:04:08', 'pending', '2025-09-20 21:04:08', '2025-09-20 21:04:08'),
(5, 492, 1, 'HTTP Error: 400', '2025-09-20 19:15:41', 'pending', '2025-09-20 21:15:41', '2025-09-20 21:15:41'),
(6, 396, 2, 'Falha no envio WhatsApp: Erro desconhecido', '2025-09-20 20:19:17', 'pending', '2025-09-20 21:18:36', '2025-09-20 21:19:16'),
(7, 493, 1, 'HTTP Error: 400', '2025-09-20 19:20:58', 'pending', '2025-09-20 21:20:58', '2025-09-20 21:20:58'),
(8, 488, 1, 'Falha no envio WhatsApp: Erro desconhecido', '2025-09-20 19:23:07', 'pending', '2025-09-20 21:23:07', '2025-09-20 21:23:07'),
(9, 494, 1, 'HTTP Error: 400', '2025-09-20 19:26:27', 'pending', '2025-09-20 21:26:27', '2025-09-20 21:26:27'),
(10, 495, 1, 'HTTP Error: 400', '2025-09-20 20:24:45', 'pending', '2025-09-20 22:24:45', '2025-09-20 22:24:45'),
(11, 496, 1, 'HTTP Error: 400', '2025-09-20 20:25:27', 'pending', '2025-09-20 22:25:27', '2025-09-20 22:25:27'),
(12, 497, 1, 'HTTP Error: 400', '2025-09-20 20:33:04', 'pending', '2025-09-20 22:33:04', '2025-09-20 22:33:04'),
(13, 498, 2, 'HTTP Error: 400', '2025-09-20 21:38:56', 'pending', '2025-09-20 22:38:55', '2025-09-20 22:38:56'),
(14, 499, 2, 'HTTP Error: 400', '2025-09-20 21:40:47', 'pending', '2025-09-20 22:40:47', '2025-09-20 22:40:47'),
(15, 1, 1, 'HTTP Error: 400', '2025-09-20 20:44:21', 'pending', '2025-09-20 22:44:21', '2025-09-20 22:44:21'),
(16, 100, 2, 'HTTP Error: 400', '2025-09-20 21:45:40', 'pending', '2025-09-20 22:45:38', '2025-09-20 22:45:40'),
(17, 10000, 2, 'HTTP Error: 400', '2025-09-20 21:45:53', 'pending', '2025-09-20 22:45:52', '2025-09-20 22:45:53'),
(18, 500, 2, 'HTTP Error: 400', '2025-09-20 21:50:52', 'pending', '2025-09-20 22:50:52', '2025-09-20 22:50:52'),
(19, 501, 1, 'HTTP Error: 500', '2025-09-20 20:54:36', 'pending', '2025-09-20 22:54:36', '2025-09-20 22:54:36');

-- --------------------------------------------------------

--
-- Estrutura para tabela `cashback_saldos`
--

CREATE TABLE `cashback_saldos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `loja_id` int(11) NOT NULL,
  `saldo_disponivel` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_creditado` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_usado` decimal(10,2) NOT NULL DEFAULT 0.00,
  `data_criacao` timestamp NULL DEFAULT current_timestamp(),
  `ultima_atualizacao` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `cashback_saldos`
--

INSERT INTO `cashback_saldos` (`id`, `usuario_id`, `loja_id`, `saldo_disponivel`, `total_creditado`, `total_usado`, `data_criacao`, `ultima_atualizacao`) VALUES
(163, 180, 59, 400.00, 400.00, 0.00, '2025-09-15 01:44:47', '2025-09-19 20:44:44'),
(164, 181, 38, 12.50, 12.50, 0.00, '2025-09-15 18:33:01', '2025-09-15 18:33:01'),
(165, 142, 38, 605.59, 605.59, 0.00, '2025-09-15 18:33:35', '2025-09-20 02:13:15'),
(166, 9, 59, 78.79, 78.79, 0.00, '2025-09-16 15:26:24', '2025-09-20 23:40:32'),
(168, 184, 38, 7.50, 7.50, 0.00, '2025-09-19 18:36:08', '2025-09-19 18:36:08'),
(186, 162, 59, 0.70, 0.70, 0.00, '2025-09-20 21:04:08', '2025-09-20 21:04:08'),
(191, 9, 34, 1.50, 1.50, 0.00, '2025-09-20 22:26:29', '2025-09-20 22:42:13');

-- --------------------------------------------------------

--
-- Estrutura para tabela `comissoes_status_historico`
--

CREATE TABLE `comissoes_status_historico` (
  `id` int(11) NOT NULL,
  `comissao_id` int(11) NOT NULL,
  `status_anterior` enum('pendente','aprovado','cancelado') NOT NULL,
  `status_novo` enum('pendente','aprovado','cancelado') NOT NULL,
  `observacao` text DEFAULT NULL,
  `data_alteracao` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes_2fa`
--

CREATE TABLE `configuracoes_2fa` (
  `id` int(11) NOT NULL,
  `habilitado` tinyint(1) DEFAULT 0,
  `tempo_expiracao_minutos` int(11) DEFAULT 5,
  `max_tentativas` int(11) DEFAULT 3,
  `data_atualizacao` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes_cashback`
--

CREATE TABLE `configuracoes_cashback` (
  `id` int(11) NOT NULL,
  `porcentagem_cliente` decimal(5,2) NOT NULL,
  `porcentagem_admin` decimal(5,2) NOT NULL,
  `porcentagem_loja` decimal(5,2) NOT NULL,
  `data_atualizacao` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `configuracoes_cashback`
--

INSERT INTO `configuracoes_cashback` (`id`, `porcentagem_cliente`, `porcentagem_admin`, `porcentagem_loja`, `data_atualizacao`) VALUES
(1, 7.00, 1.00, 0.00, '2025-09-19 22:27:42');

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes_notificacao`
--

CREATE TABLE `configuracoes_notificacao` (
  `id` int(11) NOT NULL,
  `email_nova_transacao` tinyint(1) DEFAULT 1,
  `email_pagamento_aprovado` tinyint(1) DEFAULT 1,
  `email_saldo_disponivel` tinyint(1) DEFAULT 1,
  `email_saldo_baixo` tinyint(1) DEFAULT 1,
  `email_saldo_expirado` tinyint(1) DEFAULT 1,
  `push_nova_transacao` tinyint(1) DEFAULT 1,
  `push_saldo_disponivel` tinyint(1) DEFAULT 1,
  `push_promocoes` tinyint(1) DEFAULT 1,
  `data_atualizacao` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `configuracoes_notificacao`
--

INSERT INTO `configuracoes_notificacao` (`id`, `email_nova_transacao`, `email_pagamento_aprovado`, `email_saldo_disponivel`, `email_saldo_baixo`, `email_saldo_expirado`, `push_nova_transacao`, `push_saldo_disponivel`, `push_promocoes`, `data_atualizacao`) VALUES
(1, 1, 1, 1, 1, 1, 1, 1, 1, '2025-05-19 14:40:18');

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes_saldo`
--

CREATE TABLE `configuracoes_saldo` (
  `id` int(11) NOT NULL,
  `permitir_uso_saldo` tinyint(1) DEFAULT 1,
  `valor_minimo_uso` decimal(10,2) DEFAULT 1.00,
  `percentual_maximo_uso` decimal(5,2) DEFAULT 100.00,
  `tempo_expiracao_dias` int(11) DEFAULT 0,
  `notificar_saldo_baixo` tinyint(1) DEFAULT 1,
  `limite_saldo_baixo` decimal(10,2) DEFAULT 10.00,
  `permitir_transferencia` tinyint(1) DEFAULT 0,
  `taxa_transferencia` decimal(5,2) DEFAULT 0.00,
  `data_atualizacao` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `configuracoes_saldo`
--

INSERT INTO `configuracoes_saldo` (`id`, `permitir_uso_saldo`, `valor_minimo_uso`, `percentual_maximo_uso`, `tempo_expiracao_dias`, `notificar_saldo_baixo`, `limite_saldo_baixo`, `permitir_transferencia`, `taxa_transferencia`, `data_atualizacao`) VALUES
(1, 1, 10.00, 100.00, 0, 1, 10.00, 0, 0.00, '2025-08-14 18:52:54');

-- --------------------------------------------------------

--
-- Estrutura para tabela `email_campaigns`
--

CREATE TABLE `email_campaigns` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `assunto` varchar(255) NOT NULL,
  `conteudo_html` text NOT NULL,
  `conteudo_texto` text DEFAULT NULL,
  `data_criacao` timestamp NULL DEFAULT current_timestamp(),
  `data_agendamento` datetime DEFAULT NULL,
  `status` enum('rascunho','agendado','enviando','enviado','cancelado') DEFAULT 'rascunho',
  `total_emails` int(11) DEFAULT 0,
  `emails_enviados` int(11) DEFAULT 0,
  `emails_falharam` int(11) DEFAULT 0,
  `criado_por` varchar(100) DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `email_campaigns`
--

INSERT INTO `email_campaigns` (`id`, `titulo`, `assunto`, `conteudo_html`, `conteudo_texto`, `data_criacao`, `data_agendamento`, `status`, `total_emails`, `emails_enviados`, `emails_falharam`, `criado_por`) VALUES
(2, 'Newsletter - Contagem Regressiva Final', '🚀 Últimos dias antes do lançamento da Klube Cash!', '<div style=\"max-width: 600px; margin: 0 auto; font-family: Inter, sans-serif; background: #ffffff;\">\r\n    <!-- Header com gradiente -->\r\n    <div style=\"background: linear-gradient(135deg, #FF7A00, #FF9A40); color: white; padding: 2rem; text-align: center; border-radius: 12px 12px 0 0;\">\r\n        <img src=\"https://klubecash.com/assets/images/logobranco.png\" alt=\"Klube Cash\" style=\"height: 60px; margin-bottom: 1rem;\">\r\n        <h1 style=\"margin: 0; font-size: 2rem; font-weight: 800;\">⏰ ÚLTIMA SEMANA!</h1>\r\n        <p style=\"margin: 1rem 0 0; font-size: 1.2rem; opacity: 0.95;\">O lançamento da Klube Cash está chegando!</p>\r\n    </div>\r\n    \r\n    <!-- Conteúdo principal -->\r\n    <div style=\"background: white; padding: 2rem;\">\r\n        <h2 style=\"color: #FF7A00; text-align: center; margin-bottom: 1.5rem; font-size: 1.5rem;\">🎯 Faltam apenas alguns dias!</h2>\r\n        \r\n        <!-- Contagem regressiva visual -->\r\n        <div style=\"background: #FFF7ED; border: 2px solid #FF7A00; border-radius: 12px; padding: 2rem; margin: 1.5rem 0; text-align: center;\">\r\n            <h3 style=\"color: #FF7A00; margin: 0 0 1rem; font-size: 1.2rem;\">📅 Data de Lançamento Oficial:</h3>\r\n            <p style=\"font-size: 2rem; font-weight: 800; color: #FF7A00; margin: 0; text-shadow: 0 2px 4px rgba(0,0,0,0.1);\">9 de Junho • 18:00</p>\r\n            <p style=\"color: #666; margin: 0.5rem 0 0; font-size: 1rem;\">Horário de Brasília</p>\r\n        </div>\r\n        \r\n        <h3 style=\"color: #333; margin: 2rem 0 1rem; font-size: 1.3rem;\">🎁 Benefícios exclusivos para primeiros cadastrados:</h3>\r\n        <div style=\"background: #F8FAFC; border-left: 4px solid #FF7A00; padding: 1.5rem; border-radius: 0 8px 8px 0;\">\r\n            <ul style=\"color: #444; line-height: 2; margin: 0; padding-left: 1.5rem; font-size: 1rem;\">\r\n                \r\n                <li><strong style=\"color: #FF7A00;\">Cashback Garantido</strong> de 5%</li>\r\n                <li><strong style=\"color: #FF7A00;\">Acesso antecipado</strong> às melhores ofertas</li>\r\n                <li><strong style=\"color: #FF7A00;\">Suporte premium</strong></li>\r\n                <li><strong style=\"color: #FF7A00;\">Zero taxas\r\n            </ul>\r\n        </div>\r\n        \r\n        <!-- Call to action -->\r\n        <div style=\"text-align: center; margin: 2.5rem 0;\">\r\n            <a href=\"https://klubecash.com\" style=\"background: linear-gradient(135deg, #FF7A00, #FF9A40); color: white; padding: 1.2rem 2.5rem; text-decoration: none; border-radius: 30px; font-weight: 700; display: inline-block; font-size: 1.1rem; box-shadow: 0 4px 15px rgba(255, 122, 0, 0.3); transition: transform 0.2s ease;\">\r\n                🚀 Estar Pronto no Lançamento\r\n            </a>\r\n        </div>\r\n        \r\n        <!-- Informações adicionais -->\r\n        <div style=\"background: #F0F9FF; border-left: 4px solid #3B82F6; padding: 1.5rem; border-radius: 0 8px 8px 0; margin: 2rem 0;\">\r\n            <h4 style=\"color: #1E40AF; margin: 0 0 0.5rem; font-size: 1.1rem;\">📱 Como funciona:</h4>\r\n            <p style=\"color: #1E3A8A; margin: 0; line-height: 1.6;\">\r\n                1. Faça suas compras normalmente<br>\r\n                2. Apresente seu email ou codigo cadastrado na Klube Cash<br>\r\n                3. Receba dinheiro de volta automaticamente na sua Conta Klube Cash<br>\r\n                4. Use seu cashback em novas compras\r\n            </p>\r\n        </div>\r\n    </div>\r\n    \r\n    <!-- Footer -->\r\n    <div style=\"background: #FFF7ED; padding: 2rem; text-align: center; border-radius: 0 0 12px 12px; border-top: 1px solid #FFE4B5;\">\r\n        <p style=\"color: #666; font-size: 0.9rem; margin: 0 0 1rem;\">\r\n            Siga-nos nas redes sociais para acompanhar todas as novidades:\r\n        </p>\r\n        <div style=\"margin-bottom: 1rem;\">\r\n            <a href=\"https://instagram.com/klubecash\" style=\"color: #FF7A00; text-decoration: none; margin: 0 1rem; font-weight: 600;\">📸 Instagram</a>\r\n            <a href=\"https://tiktok.com/@klube.cash\" style=\"color: #FF7A00; text-decoration: none; margin: 0 1rem; font-weight: 600;\">🎵 TikTok</a>\r\n        </div>\r\n        <p style=\"color: #999; font-size: 0.8rem; margin: 0;\">\r\n            &copy; 2025 Klube Cash. Todos os direitos reservados.<br>\r\n            Você está recebendo este email porque se cadastrou em nossa lista de lançamento.\r\n        </p>\r\n    </div>\r\n</div>', '\r\n    \r\n    \r\n        \r\n        ⏰ ÚLTIMA SEMANA!\r\n        O lançamento da Klube Cash está chegando!\r\n    \r\n    \r\n    \r\n    \r\n        🎯 Faltam apenas alguns dias!\r\n        \r\n        \r\n        \r\n            📅 Data de Lançamento Oficial:\r\n            9 de Junho • 18:00\r\n            Horário de Brasília\r\n        \r\n        \r\n        🎁 Benefícios exclusivos para primeiros cadastrados:\r\n        \r\n            \r\n                \r\n                Cashback Garantido de 5%\r\n                Acesso antecipado às melhores ofertas\r\n                Suporte premium\r\n                Zero taxas\r\n            \r\n        \r\n        \r\n        \r\n        \r\n            \r\n                🚀 Estar Pronto no Lançamento\r\n            \r\n        \r\n        \r\n        \r\n        \r\n            📱 Como funciona:\r\n            \r\n                1. Faça suas compras normalmente\n\r\n                2. Apresente seu email ou codigo cadastrado na Klube Cash\n\r\n                3. Receba dinheiro de volta automaticamente na sua Conta Klube Cash\n\r\n                4. Use seu cashback em novas compras\r\n            \r\n        \r\n    \r\n    \r\n    \r\n    \r\n        \r\n            Siga-nos nas redes sociais para acompanhar todas as novidades:\r\n        \r\n        \r\n            📸 Instagram\r\n            🎵 TikTok\r\n        \r\n        \r\n            &copy; 2025 Klube Cash. Todos os direitos reservados.\n\r\n            Você está recebendo este email porque se cadastrou em nossa lista de lançamento.\r\n        \r\n    \r\n', '2025-06-03 02:13:18', NULL, 'cancelado', 0, 0, 0, 'admin'),
(3, 'Newsletter - Contagem Regressiva Final', '🚀 Últimos dias antes do lançamento da Klube Cash!', '<div style=\"max-width: 600px; margin: 0 auto; font-family: Inter, sans-serif; background: #ffffff;\">\r\n    <!-- Header com gradiente -->\r\n    <div style=\"background: linear-gradient(135deg, #FF7A00, #FF9A40); color: white; padding: 2rem; text-align: center; border-radius: 12px 12px 0 0;\">\r\n        <img src=\"https://klubecash.com/assets/images/logobranco.png\" alt=\"Klube Cash\" style=\"height: 60px; margin-bottom: 1rem;\">\r\n        <h1 style=\"margin: 0; font-size: 2rem; font-weight: 800;\">⏰ ÚLTIMA SEMANA!</h1>\r\n        <p style=\"margin: 1rem 0 0; font-size: 1.2rem; opacity: 0.95;\">O lançamento da Klube Cash está chegando!</p>\r\n    </div>\r\n    \r\n    <!-- Conteúdo principal -->\r\n    <div style=\"background: white; padding: 2rem;\">\r\n        <h2 style=\"color: #FF7A00; text-align: center; margin-bottom: 1.5rem; font-size: 1.5rem;\">🎯 Faltam apenas alguns dias!</h2>\r\n        \r\n        <!-- Contagem regressiva visual -->\r\n        <div style=\"background: #FFF7ED; border: 2px solid #FF7A00; border-radius: 12px; padding: 2rem; margin: 1.5rem 0; text-align: center;\">\r\n            <h3 style=\"color: #FF7A00; margin: 0 0 1rem; font-size: 1.2rem;\">📅 Data de Lançamento Oficial:</h3>\r\n            <p style=\"font-size: 2rem; font-weight: 800; color: #FF7A00; margin: 0; text-shadow: 0 2px 4px rgba(0,0,0,0.1);\">9 de Junho • 18:00</p>\r\n            <p style=\"color: #666; margin: 0.5rem 0 0; font-size: 1rem;\">Horário de Brasília</p>\r\n        </div>\r\n        \r\n        <h3 style=\"color: #333; margin: 2rem 0 1rem; font-size: 1.3rem;\">🎁 Benefícios exclusivos para primeiros cadastrados:</h3>\r\n        <div style=\"background: #F8FAFC; border-left: 4px solid #FF7A00; padding: 1.5rem; border-radius: 0 8px 8px 0;\">\r\n            <ul style=\"color: #444; line-height: 2; margin: 0; padding-left: 1.5rem; font-size: 1rem;\">\r\n                \r\n                <li><strong style=\"color: #FF7A00;\">Cashback Garantido</strong> de 5%</li>\r\n                <li><strong style=\"color: #FF7A00;\">Acesso antecipado</strong> às melhores ofertas</li>\r\n                <li><strong style=\"color: #FF7A00;\">Suporte premium</strong></li>\r\n                <li><strong style=\"color: #FF7A00;\">Zero taxas\r\n            </ul>\r\n        </div>\r\n        \r\n        <!-- Call to action -->\r\n        <div style=\"text-align: center; margin: 2.5rem 0;\">\r\n            <a href=\"https://klubecash.com\" style=\"background: linear-gradient(135deg, #FF7A00, #FF9A40); color: white; padding: 1.2rem 2.5rem; text-decoration: none; border-radius: 30px; font-weight: 700; display: inline-block; font-size: 1.1rem; box-shadow: 0 4px 15px rgba(255, 122, 0, 0.3); transition: transform 0.2s ease;\">\r\n                🚀 Estar Pronto no Lançamento\r\n            </a>\r\n        </div>\r\n        \r\n        <!-- Informações adicionais -->\r\n        <div style=\"background: #F0F9FF; border-left: 4px solid #3B82F6; padding: 1.5rem; border-radius: 0 8px 8px 0; margin: 2rem 0;\">\r\n            <h4 style=\"color: #1E40AF; margin: 0 0 0.5rem; font-size: 1.1rem;\">📱 Como funciona:</h4>\r\n            <p style=\"color: #1E3A8A; margin: 0; line-height: 1.6;\">\r\n                1. Faça suas compras normalmente<br>\r\n                2. Apresente seu email ou codigo cadastrado na Klube Cash<br>\r\n                3. Receba dinheiro de volta automaticamente na sua Conta Klube Cash<br>\r\n                4. Use seu cashback em novas compras\r\n            </p>\r\n        </div>\r\n    </div>\r\n    \r\n    <!-- Footer -->\r\n    <div style=\"background: #FFF7ED; padding: 2rem; text-align: center; border-radius: 0 0 12px 12px; border-top: 1px solid #FFE4B5;\">\r\n        <p style=\"color: #666; font-size: 0.9rem; margin: 0 0 1rem;\">\r\n            Siga-nos nas redes sociais para acompanhar todas as novidades:\r\n        </p>\r\n        <div style=\"margin-bottom: 1rem;\">\r\n            <a href=\"https://instagram.com/klubecash\" style=\"color: #FF7A00; text-decoration: none; margin: 0 1rem; font-weight: 600;\">📸 Instagram</a>\r\n            <a href=\"https://tiktok.com/@klube.cash\" style=\"color: #FF7A00; text-decoration: none; margin: 0 1rem; font-weight: 600;\">🎵 TikTok</a>\r\n        </div>\r\n        <p style=\"color: #999; font-size: 0.8rem; margin: 0;\">\r\n            &copy; 2025 Klube Cash. Todos os direitos reservados.<br>\r\n            Você está recebendo este email porque se cadastrou em nossa lista de lançamento.\r\n        </p>\r\n    </div>\r\n</div>', '\r\n    \r\n    \r\n        \r\n        ⏰ ÚLTIMA SEMANA!\r\n        O lançamento da Klube Cash está chegando!\r\n    \r\n    \r\n    \r\n    \r\n        🎯 Faltam apenas alguns dias!\r\n        \r\n        \r\n        \r\n            📅 Data de Lançamento Oficial:\r\n            9 de Junho • 18:00\r\n            Horário de Brasília\r\n        \r\n        \r\n        🎁 Benefícios exclusivos para primeiros cadastrados:\r\n        \r\n            \r\n                \r\n                Cashback Garantido de 5%\r\n                Acesso antecipado às melhores ofertas\r\n                Suporte premium\r\n                Zero taxas\r\n            \r\n        \r\n        \r\n        \r\n        \r\n            \r\n                🚀 Estar Pronto no Lançamento\r\n            \r\n        \r\n        \r\n        \r\n        \r\n            📱 Como funciona:\r\n            \r\n                1. Faça suas compras normalmente\n\r\n                2. Apresente seu email ou codigo cadastrado na Klube Cash\n\r\n                3. Receba dinheiro de volta automaticamente na sua Conta Klube Cash\n\r\n                4. Use seu cashback em novas compras\r\n            \r\n        \r\n    \r\n    \r\n    \r\n    \r\n        \r\n            Siga-nos nas redes sociais para acompanhar todas as novidades:\r\n        \r\n        \r\n            📸 Instagram\r\n            🎵 TikTok\r\n        \r\n        \r\n            &copy; 2025 Klube Cash. Todos os direitos reservados.\n\r\n            Você está recebendo este email porque se cadastrou em nossa lista de lançamento.\r\n        \r\n    \r\n', '2025-06-03 02:13:26', NULL, 'cancelado', 0, 0, 0, 'admin'),
(4, 'Newsletter - Dicas de Cashback', '💰 Como maximizar seu cashback - Dicas exclusivas da Klube Cash', '<div style=\"max-width: 600px; margin: 0 auto; font-family: Inter, sans-serif; background: #ffffff;\">\r\n    <!-- Header -->\r\n    <div style=\"background: linear-gradient(135deg, #10B981, #34D399); color: white; padding: 2rem; text-align: center; border-radius: 12px 12px 0 0;\">\r\n        <img src=\"https://klubecash.com/assets/images/logobranco.png\" alt=\"Klube Cash\" style=\"height: 60px; margin-bottom: 1rem;\">\r\n        <h1 style=\"margin: 0; font-size: 1.8rem; font-weight: 800;\">💡 Dicas de Ouro para Cashback</h1>\r\n        <p style=\"margin: 1rem 0 0; font-size: 1.1rem; opacity: 0.95;\">Aprenda a maximizar seus ganhos!</p>\r\n    </div>\r\n    \r\n    <!-- Conteúdo -->\r\n    <div style=\"background: white; padding: 2rem;\">\r\n        <h2 style=\"color: #059669; margin-bottom: 1.5rem; font-size: 1.4rem;\">🎯 Como ganhar ainda mais dinheiro de volta</h2>\r\n        \r\n        <p style=\"color: #666; line-height: 1.8; margin-bottom: 2rem; font-size: 1rem;\">\r\n            Preparamos dicas exclusivas para você se tornar um expert em cashback e maximizar seus ganhos desde o primeiro dia na Klube Cash!\r\n        </p>\r\n        \r\n        <!-- Dicas -->\r\n        <div style=\"margin: 2rem 0;\">\r\n            <!-- Dica 1 -->\r\n            <div style=\"background: #F0FDF4; border-left: 4px solid #10B981; padding: 1.5rem; margin-bottom: 1.5rem; border-radius: 0 8px 8px 0;\">\r\n                <h3 style=\"color: #065F46; margin: 0 0 1rem; font-size: 1.2rem;\">🛒 Dica #1: Planeje suas Compras</h3>\r\n                <p style=\"color: #064E3B; margin: 0; line-height: 1.6;\">\r\n                    <strong>Concentre suas compras</strong> em dias específicos da semana. Muitas lojas oferecem cashback extra às quartas e sextas-feiras. Você pode ganhar até <strong>12% de volta</strong> em vez dos 5% padrão!\r\n                </p>\r\n            </div>\r\n            \r\n            <!-- Dica 2 -->\r\n            <div style=\"background: #FEF7FF; border-left: 4px solid #A855F7; padding: 1.5rem; margin-bottom: 1.5rem; border-radius: 0 8px 8px 0;\">\r\n                <h3 style=\"color: #7C2D12; margin: 0 0 1rem; font-size: 1.2rem;\">💳 Dica #2: Combine Promoções</h3>\r\n                <p style=\"color: #92400E; margin: 0; line-height: 1.6;\">\r\n                    Use cupons de desconto das lojas <strong>junto</strong> com o cashback da Klube Cash. É desconto duplo! Já tivemos clientes que economizaram 30% em uma única compra combinando ofertas.\r\n                </p>\r\n            </div>\r\n            \r\n            <!-- Dica 3 -->\r\n            <div style=\"background: #FFF7ED; border-left: 4px solid #FF7A00; padding: 1.5rem; margin-bottom: 1.5rem; border-radius: 0 8px 8px 0;\">\r\n                <h3 style=\"color: #C2410C; margin: 0 0 1rem; font-size: 1.2rem;\">📱 Dica #3: Use o App (em breve)</h3>\r\n                <p style=\"color: #EA580C; margin: 0; line-height: 1.6;\">\r\n                    Nosso app móvel terá <strong>notificações em tempo real</strong> quando você estiver perto de lojas parceiras. Você nunca mais vai esquecer de usar seu cashback!\r\n                </p>\r\n            </div>\r\n            \r\n            <!-- Dica 4 -->\r\n            <div style=\"background: #EFF6FF; border-left: 4px solid #3B82F6; padding: 1.5rem; margin-bottom: 1.5rem; border-radius: 0 8px 8px 0;\">\r\n                <h3 style=\"color: #1D4ED8; margin: 0 0 1rem; font-size: 1.2rem;\">🎁 Dica #4: Indique Amigos</h3>\r\n                <p style=\"color: #1E40AF; margin: 0; line-height: 1.6;\">\r\n                    Para cada amigo que você indicar, <strong>ambos ganham R$ 15 de bônus</strong>. É uma maneira fácil de aumentar seu saldo sem gastar nada!\r\n                </p>\r\n            </div>\r\n        </div>\r\n        \r\n        <!-- Exemplo prático -->\r\n        <div style=\"background: #F8FAFC; border: 2px solid #E2E8F0; border-radius: 12px; padding: 2rem; margin: 2rem 0;\">\r\n            <h3 style=\"color: #374151; margin: 0 0 1rem; font-size: 1.3rem;\">📊 Exemplo Prático</h3>\r\n            <p style=\"color: #6B7280; margin: 0 0 1rem; line-height: 1.6;\">\r\n                <strong>Situação:</strong> Compra de R$ 200 em roupas numa quarta-feira\r\n            </p>\r\n            <ul style=\"color: #4B5563; line-height: 1.8; margin: 0; padding-left: 1.5rem;\">\r\n                <li>Cashback padrão (5%): R$ 10</li>\r\n                <li>Bônus dia da semana (+2%): R$ 4</li>\r\n                <li>Cupom da loja (15% desconto): R$ 30</li>\r\n                <li><strong style=\"color: #10B981;\">Total economizado: R$ 44 (22% da compra!)</strong></li>\r\n            </ul>\r\n        </div>\r\n        \r\n        <!-- CTA -->\r\n        <div style=\"text-align: center; margin: 2.5rem 0;\">\r\n            <a href=\"https://klubecash.com\" style=\"background: linear-gradient(135deg, #10B981, #34D399); color: white; padding: 1.2rem 2.5rem; text-decoration: none; border-radius: 30px; font-weight: 700; display: inline-block; font-size: 1.1rem; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);\">\r\n                💰 Quero Começar a Economizar\r\n            </a>\r\n        </div>\r\n        \r\n        <div style=\"background: #FFFBEB; border: 2px solid #F59E0B; border-radius: 8px; padding: 1.5rem; margin: 2rem 0;\">\r\n            <p style=\"color: #92400E; margin: 0; text-align: center; font-weight: 600;\">\r\n                💡 <strong>Lembre-se:</strong> Essas dicas funcionam melhor quando usadas em conjunto. \r\n                Teste diferentes combinações e descubra qual funciona melhor para seu perfil de compras!\r\n            </p>\r\n        </div>\r\n    </div>\r\n    \r\n    <!-- Footer -->\r\n    <div style=\"background: #F0FDF4; padding: 2rem; text-align: center; border-radius: 0 0 12px 12px; border-top: 1px solid #BBF7D0;\">\r\n        <p style=\"color: #166534; font-size: 0.9rem; margin: 0 0 1rem; font-weight: 600;\">\r\n            🏆 Compartilhe essas dicas e ajude seus amigos a economizar também!\r\n        </p>\r\n        <div style=\"margin-bottom: 1rem;\">\r\n            <a href=\"https://instagram.com/klubecash\" style=\"color: #10B981; text-decoration: none; margin: 0 1rem; font-weight: 600;\">📸 Instagram</a>\r\n            <a href=\"https://tiktok.com/@klube.cash\" style=\"color: #10B981; text-decoration: none; margin: 0 1rem; font-weight: 600;\">🎵 TikTok</a>\r\n        </div>\r\n        <p style=\"color: #999; font-size: 0.8rem; margin: 0;\">\r\n            &copy; 2025 Klube Cash. Todos os direitos reservados.\r\n        </p>\r\n    </div>\r\n</div>', '\r\n    \r\n    \r\n        \r\n        💡 Dicas de Ouro para Cashback\r\n        Aprenda a maximizar seus ganhos!\r\n    \r\n    \r\n    \r\n    \r\n        🎯 Como ganhar ainda mais dinheiro de volta\r\n        \r\n        \r\n            Preparamos dicas exclusivas para você se tornar um expert em cashback e maximizar seus ganhos desde o primeiro dia na Klube Cash!\r\n        \r\n        \r\n        \r\n        \r\n            \r\n            \r\n                🛒 Dica #1: Planeje suas Compras\r\n                \r\n                    Concentre suas compras em dias específicos da semana. Muitas lojas oferecem cashback extra às quartas e sextas-feiras. Você pode ganhar até 12% de volta em vez dos 5% padrão!\r\n                \r\n            \r\n            \r\n            \r\n            \r\n                💳 Dica #2: Combine Promoções\r\n                \r\n                    Use cupons de desconto das lojas junto com o cashback da Klube Cash. É desconto duplo! Já tivemos clientes que economizaram 30% em uma única compra combinando ofertas.\r\n                \r\n            \r\n            \r\n            \r\n            \r\n                📱 Dica #3: Use o App (em breve)\r\n                \r\n                    Nosso app móvel terá notificações em tempo real quando você estiver perto de lojas parceiras. Você nunca mais vai esquecer de usar seu cashback!\r\n                \r\n            \r\n            \r\n            \r\n            \r\n                🎁 Dica #4: Indique Amigos\r\n                \r\n                    Para cada amigo que você indicar, ambos ganham R$ 15 de bônus. É uma maneira fácil de aumentar seu saldo sem gastar nada!\r\n                \r\n            \r\n        \r\n        \r\n        \r\n        \r\n            📊 Exemplo Prático\r\n            \r\n                Situação: Compra de R$ 200 em roupas numa quarta-feira\r\n            \r\n            \r\n                Cashback padrão (5%): R$ 10\r\n                Bônus dia da semana (+2%): R$ 4\r\n                Cupom da loja (15% desconto): R$ 30\r\n                Total economizado: R$ 44 (22% da compra!)\r\n            \r\n        \r\n        \r\n        \r\n        \r\n            \r\n                💰 Quero Começar a Economizar\r\n            \r\n        \r\n        \r\n        \r\n            \r\n                💡 Lembre-se: Essas dicas funcionam melhor quando usadas em conjunto. \r\n                Teste diferentes combinações e descubra qual funciona melhor para seu perfil de compras!\r\n            \r\n        \r\n    \r\n    \r\n    \r\n    \r\n        \r\n            🏆 Compartilhe essas dicas e ajude seus amigos a economizar também!\r\n        \r\n        \r\n            📸 Instagram\r\n            🎵 TikTok\r\n        \r\n        \r\n            &copy; 2025 Klube Cash. Todos os direitos reservados.\r\n        \r\n    \r\n', '2025-06-03 02:14:28', NULL, 'cancelado', 0, 0, 0, 'admin'),
(5, 'Newsletter - Contagem Regressiva Final', '🚀 Últimos dias antes do lançamento da Klube Cash!', '<meta charset=\"UTF-8\">\r\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n<div style=\"max-width: 600px; margin: 0 auto; font-family: Inter, sans-serif;\">\r\n    <div style=\"background: linear-gradient(135deg, #FF7A00, #FF9A40); color: white; padding: 2rem; text-align: center; border-radius: 12px 12px 0 0;\">\r\n        <img src=\"https://klubecash.com/assets/images/logobranco.png\" alt=\"Klube Cash\" style=\"height: 60px; margin-bottom: 1rem;\">\r\n        <h1 style=\"margin: 0; font-size: 1.8rem; font-weight: 800;\">A KlubeCash Chegou!</h1>\r\n        <p style=\"margin: 1rem 0 0; font-size: 1.1rem; opacity: 0.95;\">Você tem acesso antecipado ao sistema.</p>\r\n    </div>\r\n    \r\n    <div style=\"background: white; padding: 2rem;\">\r\n        <h2 style=\"color: #333; margin-bottom: 1.5rem;\">Olá, futuro membro da Klube Cash! 👋</h2>\r\n        \r\n        <p style=\"color: #666; line-height: 1.8; margin-bottom: 2rem;\">\r\n            O KlubeCash está quase pronto para ser lançado e você foi um dos escolhidos para ter <strong>acesso antecipado</strong>! Como os primeiros inscritos têm prioridade, registre-se agora e seja um dos pioneiros a descobrir todas as novidades e vantagens incríveis que preparamos.\r\n        </p>\r\n        \r\n        <div style=\"background: #FFF7ED; border-left: 4px solid #FF7A00; padding: 1.5rem; border-radius: 0 8px 8px 0; margin: 2rem 0;\">\r\n            <h3 style=\"color: #EA580C; margin: 0 0 1rem;\">✨ Seja um Pioneiro KlubeCash!</h3>\r\n            <p style=\"color: #9A3412; margin: 0; line-height: 1.6;\">\r\n                Ao se registrar no acesso antecipado, você garante sua vaga para explorar em primeira mão uma plataforma pensada para revolucionar a sua forma de ganhar cashback e aproveitar benefícios exclusivos. Não fique de fora!\r\n            </p>\r\n        </div>\r\n        \r\n        <h3 style=\"color: #FF7A00; margin: 2rem 0 1rem;\">📋 Por que se registrar agora?</h3>\r\n        <ul style=\"color: #666; line-height: 1.8; margin: 0 0 2rem; padding-left: 1.5rem;\">\r\n            <li><strong>Acesso Exclusivo:</strong> Garanta sua entrada VIP antes do lançamento oficial.</li>\r\n            <li><strong>Vantagens Únicas:</strong> Descubra funcionalidades e ofertas especiais para os primeiros membros.</li>\r\n            <li><strong>Novidades em Primeira Mão:</strong> Fique sabendo de tudo sobre o KlubeCash antes de todo mundo.</li>\r\n        </ul>\r\n        \r\n        <div style=\"text-align: center; margin: 2.5rem 0;\">\r\n            <a href=\"https://klubecash.com/registro\" style=\"background: linear-gradient(135deg, #FF7A00, #FF9A40); color: white; padding: 1.2rem 2.5rem; text-decoration: none; border-radius: 30px; font-weight: 700; display: inline-block; font-size: 1.1rem; box-shadow: 0 4px 15px rgba(255, 122, 0, 0.3);\">\r\n                🚀 Quero Acesso Antecipado!\r\n            </a>\r\n        </div>\r\n        \r\n        <p style=\"color: #666; line-height: 1.6; margin: 1rem 0;\">\r\n            Estamos ansiosos para ter você conosco desde o início dessa jornada! Clique no botão acima e faça parte da comunidade KlubeCash. As vagas para o acesso antecipado são limitadas!\r\n        </p>\r\n    </div>\r\n    \r\n    <div style=\"background: #FFF7ED; padding: 2rem; text-align: center; border-radius: 0 0 12px 12px; border-top: 1px solid #FFE4B5;\">\r\n        <p style=\"color: #666; font-size: 0.9rem; margin: 0 0 1rem;\">\r\n            Siga-nos nas redes sociais para mais novidades!\r\n        </p>\r\n        <div style=\"margin-bottom: 1rem;\">\r\n            <a href=\"https://instagram.com/klubecash\" style=\"color: #FF7A00; text-decoration: none; margin: 0 1rem; font-weight: 600;\">📸 Instagram</a>\r\n            <a href=\"https://tiktok.com/@klube.cash\" style=\"color: #FF7A00; text-decoration: none; margin: 0 1rem; font-weight: 600;\">🎵 TikTok</a>\r\n        </div>\r\n        <p style=\"color: #999; font-size: 0.8rem; margin: 0;\">\r\n            &copy; 2025 Klube Cash. Todos os direitos reservados.<br>\r\n            Você está recebendo este email porque se cadastrou em nossa lista de lançamento ou demonstrou interesse no KlubeCash.\r\n        </p>\r\n    </div>\r\n</div>', '\r\n\r\n\r\n    \r\n        \r\n        A KlubeCash Chegou!\r\n        Você tem acesso antecipado ao sistema.\r\n    \r\n    \r\n    \r\n        Olá, futuro membro da Klube Cash! 👋\r\n        \r\n        \r\n            O KlubeCash está quase pronto para ser lançado e você foi um dos escolhidos para ter acesso antecipado! Como os primeiros inscritos têm prioridade, registre-se agora e seja um dos pioneiros a descobrir todas as novidades e vantagens incríveis que preparamos.\r\n        \r\n        \r\n        \r\n            ✨ Seja um Pioneiro KlubeCash!\r\n            \r\n                Ao se registrar no acesso antecipado, você garante sua vaga para explorar em primeira mão uma plataforma pensada para revolucionar a sua forma de ganhar cashback e aproveitar benefícios exclusivos. Não fique de fora!\r\n            \r\n        \r\n        \r\n        📋 Por que se registrar agora?\r\n        \r\n            Acesso Exclusivo: Garanta sua entrada VIP antes do lançamento oficial.\r\n            Vantagens Únicas: Descubra funcionalidades e ofertas especiais para os primeiros membros.\r\n            Novidades em Primeira Mão: Fique sabendo de tudo sobre o KlubeCash antes de todo mundo.\r\n        \r\n        \r\n        \r\n            \r\n                🚀 Quero Acesso Antecipado!\r\n            \r\n        \r\n        \r\n        \r\n            Estamos ansiosos para ter você conosco desde o início dessa jornada! Clique no botão acima e faça parte da comunidade KlubeCash. As vagas para o acesso antecipado são limitadas!\r\n        \r\n    \r\n    \r\n    \r\n        \r\n            Siga-nos nas redes sociais para mais novidades!\r\n        \r\n        \r\n            📸 Instagram\r\n            🎵 TikTok\r\n        \r\n        \r\n            &copy; 2025 Klube Cash. Todos os direitos reservados.\n\r\n            Você está recebendo este email porque se cadastrou em nossa lista de lançamento ou demonstrou interesse no KlubeCash.\r\n        \r\n    \r\n', '2025-06-03 02:15:35', '2025-06-02 23:16:00', 'agendado', 25, 0, 0, 'admin');

-- --------------------------------------------------------

--
-- Estrutura para tabela `email_envios`
--

CREATE TABLE `email_envios` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `status` enum('pendente','enviado','falhou','bounce') DEFAULT 'pendente',
  `tentativas` int(11) DEFAULT 0,
  `data_envio` timestamp NULL DEFAULT NULL,
  `erro_mensagem` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `email_templates`
--

CREATE TABLE `email_templates` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `assunto_padrao` varchar(255) DEFAULT NULL,
  `conteudo_html` text NOT NULL,
  `tipo` enum('newsletter','promocional','informativo') DEFAULT 'newsletter',
  `ativo` tinyint(1) DEFAULT 1,
  `data_criacao` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `store_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ip_block`
--

CREATE TABLE `ip_block` (
  `id` int(11) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `block_expiry` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `attempt_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `lojas`
--

CREATE TABLE `lojas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `nome_fantasia` varchar(100) NOT NULL,
  `razao_social` varchar(150) NOT NULL,
  `cnpj` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha_hash` varchar(255) DEFAULT NULL,
  `telefone` varchar(20) NOT NULL,
  `categoria` varchar(50) DEFAULT 'Outros',
  `porcentagem_cashback` decimal(5,2) NOT NULL,
  `descricao` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `status` enum('pendente','aprovado','rejeitado') DEFAULT 'pendente',
  `observacao` text DEFAULT NULL,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  `data_aprovacao` timestamp NULL DEFAULT NULL,
  `porcentagem_cliente` decimal(5,2) DEFAULT 5.00 COMMENT 'Percentual de cashback para o cliente (%)',
  `porcentagem_admin` decimal(5,2) DEFAULT 5.00 COMMENT 'Percentual de comissão para o admin/plataforma (%)',
  `cashback_ativo` tinyint(1) DEFAULT 1 COMMENT 'Se a loja oferece cashback (0=inativo, 1=ativo)',
  `data_config_cashback` timestamp NULL DEFAULT NULL COMMENT 'Data da última configuração de cashback'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `lojas`
--

INSERT INTO `lojas` (`id`, `usuario_id`, `nome_fantasia`, `razao_social`, `cnpj`, `email`, `senha_hash`, `telefone`, `categoria`, `porcentagem_cashback`, `descricao`, `website`, `logo`, `status`, `observacao`, `data_cadastro`, `data_aprovacao`, `porcentagem_cliente`, `porcentagem_admin`, `cashback_ativo`, `data_config_cashback`) VALUES
(34, 55, 'Kaua Matheus da Silva Lopes', 'Kaua Matheus da Silva Lopes', '59826857000108', 'kaua@syncholding.com.br', NULL, '(38) 99104-5205', 'Serviços', 5.00, 'Criador de Sites', 'https://syncholding.com.br', NULL, 'aprovado', NULL, '2025-05-25 19:17:34', '2025-09-14 11:25:48', 5.00, 5.00, 1, '2025-09-13 10:56:04'),
(38, 63, 'KLUBE DIGITAL', 'Klube Digital Estratégia e Performance Ltda.', '18431312000115', 'acessoriafredericofagundes@gmail.com', NULL, '(34) 99335-7697', 'Serviços', 5.00, '', '', NULL, 'aprovado', NULL, '2025-06-07 16:11:42', '2025-06-08 19:36:33', 2.50, 2.50, 1, '2025-08-30 01:23:59'),
(59, 159, 'Sync Holding', 'Kaua Matheus da Silva Lopes', '59826857000109', 'kauamathes123487654@gmail.com', NULL, '(34) 99800-2600', 'Serviços', 7.00, '', 'https://syncholding.com.br', NULL, 'aprovado', NULL, '2025-08-15 13:52:55', '2025-08-15 13:53:38', 7.00, 1.00, 1, '2025-09-19 22:27:42'),
(60, 173, 'ELITE SEMIJOIAS MOZAR FRANCISCO LUIZ ME', 'MOZAR FRANCISCO LUIZ', '18381956000146', 'elitesemijoiaspatosdeminas@gmail.com', NULL, '(34) 99217-2404', 'Outros', 10.00, 'ATACADO DE SEMIJOIAS', '', NULL, 'aprovado', NULL, '2025-08-29 17:22:01', '2025-08-29 18:03:45', 10.00, 0.00, 1, '2025-08-30 01:57:18'),
(61, 175, 'Digo.com', 'Digo Comércio e Varejo', '62491384000140', 'digovarejo@gmail.com', NULL, '(11) 97088-3167', 'Eletrônicos', 10.00, 'Varejista iPhone', '', NULL, 'aprovado', NULL, '2025-09-13 14:49:08', '2025-09-13 15:15:48', 5.00, 5.00, 1, NULL),
(62, 177, 'RSF SONHO DE NOIVA EVENTOS E NEGOCIOS LTDA', 'RSF SONHO DE NOIVA EVENTOS E NEGOCIOS LTDA', '22640009000108', 'cleacasamentos@gmail.com', NULL, '(85) 99632-4231', 'Serviços', 10.00, '', '', NULL, 'aprovado', NULL, '2025-09-14 19:47:52', '2025-09-14 19:55:58', 5.00, 5.00, 1, NULL),
(63, NULL, 'dasfDA', 'DSAsdsa', '22640009000104', 'cleacasamentos@gmail.com.br', NULL, '(35) 45454-4544', 'Eletrônicos', 10.00, '', 'https://cleacasamentos.com.br', NULL, 'pendente', NULL, '2025-09-14 19:51:57', NULL, 5.00, 5.00, 1, NULL),
(64, NULL, 'teste', 'ds', '22640009000110', 'kauanupix@gmail.com', NULL, '(85) 99632-4231', 'Outros', 10.00, '', 'https://cleacasamentos.com.br', NULL, 'pendente', NULL, '2025-09-14 20:01:31', NULL, 5.00, 5.00, 1, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `lojas_contato`
--

CREATE TABLE `lojas_contato` (
  `id` int(11) NOT NULL,
  `loja_id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `valor` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `lojas_endereco`
--

CREATE TABLE `lojas_endereco` (
  `id` int(11) NOT NULL,
  `loja_id` int(11) NOT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `logradouro` varchar(255) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `lojas_endereco`
--

INSERT INTO `lojas_endereco` (`id`, `loja_id`, `cep`, `logradouro`, `numero`, `complemento`, `bairro`, `cidade`, `estado`) VALUES
(12, 38, '38706-325', 'Rua Doutor Dolor Borges', '300', '', 'Planalto', 'Patos de Minas', 'MG'),
(29, 59, '38705-376', 'Rua Francisco Braga da Mota', '146', 'Ap 101', 'jardim panoramico', 'Patos de Minas', 'MG'),
(30, 60, '38700-973', 'Rua Major Gote', '1800', 'CAIXA POSTAL 2063', 'CENTRO', 'Patos de Minas - MG', 'MG'),
(31, 61, '12970-000', 'Rua das Araucárias', '55', '', 'Ipe', 'Piracaia', 'SP'),
(32, 62, '60713-240', 'R AMERICO ROCHA LIMA', '584', '', 'Manoel Satiro', 'Fortaleza', 'CE');

-- --------------------------------------------------------

--
-- Estrutura para tabela `lojas_favoritas`
--

CREATE TABLE `lojas_favoritas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `loja_id` int(11) NOT NULL,
  `data_criacao` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacoes`
--

CREATE TABLE `notificacoes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `mensagem` text NOT NULL,
  `tipo` enum('info','success','warning','error') DEFAULT 'info',
  `data_criacao` timestamp NULL DEFAULT current_timestamp(),
  `lida` tinyint(1) DEFAULT 0,
  `data_leitura` timestamp NULL DEFAULT NULL,
  `link` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `notificacoes`
--

INSERT INTO `notificacoes` (`id`, `usuario_id`, `titulo`, `mensagem`, `tipo`, `data_criacao`, `lida`, `data_leitura`, `link`) VALUES
(616, 9, 'Cashback Liberado!', 'Seu cashback de R$ 5,00 da loja Sync Holding foi liberado e está disponível para uso!', 'success', '2025-09-16 15:26:24', 0, NULL, ''),
(617, 9, 'Cashback Liberado!', 'Seu cashback de R$ 5,00 da loja Sync Holding foi liberado e está disponível para uso!', 'success', '2025-09-16 15:26:54', 0, NULL, ''),
(623, 9, 'Cashback disponível!', 'Seu cashback de R$ 7,00 da loja Sync Holding está disponível.', 'success', '2025-09-19 19:26:06', 0, NULL, ''),
(624, 159, 'Pagamento aprovado', 'Seu pagamento de comissão no valor de R$ 3,00 foi aprovado.', 'success', '2025-09-19 19:26:06', 0, NULL, ''),
(625, 9, 'Cashback disponível!', 'Seu cashback de R$ 7,00 da loja Sync Holding está disponível.', 'success', '2025-09-19 19:26:46', 0, NULL, ''),
(626, 159, 'Pagamento aprovado', 'Seu pagamento de comissão no valor de R$ 3,00 foi aprovado.', 'success', '2025-09-19 19:26:46', 0, NULL, ''),
(627, 9, 'Cashback disponível!', 'Seu cashback de R$ 0,70 da loja Sync Holding está disponível.', 'success', '2025-09-19 19:27:02', 0, NULL, ''),
(628, 159, 'Pagamento aprovado', 'Seu pagamento de comissão no valor de R$ 0,30 foi aprovado.', 'success', '2025-09-19 19:27:02', 0, NULL, ''),
(629, 9, 'Cashback disponível!', 'Seu cashback de R$ 7,00 da loja Sync Holding está disponível.', 'success', '2025-09-19 19:27:05', 0, NULL, ''),
(630, 159, 'Pagamento aprovado', 'Seu pagamento de comissão no valor de R$ 3,00 foi aprovado.', 'success', '2025-09-19 19:27:05', 0, NULL, '');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pagamentos_comissao`
--

CREATE TABLE `pagamentos_comissao` (
  `id` int(11) NOT NULL,
  `loja_id` int(11) NOT NULL,
  `criado_por` int(11) DEFAULT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `metodo_pagamento` varchar(50) NOT NULL,
  `numero_referencia` varchar(100) DEFAULT NULL,
  `comprovante` varchar(255) DEFAULT NULL,
  `observacao` text DEFAULT NULL,
  `observacao_admin` text DEFAULT NULL,
  `data_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_aprovacao` timestamp NULL DEFAULT NULL,
  `status` enum('pendente','aprovado','rejeitado','pix_aguardando','pix_expirado') DEFAULT 'pendente',
  `pix_charge_id` varchar(255) DEFAULT NULL,
  `pix_qr_code` text DEFAULT NULL,
  `pix_qr_code_image` text DEFAULT NULL,
  `pix_paid_at` timestamp NULL DEFAULT NULL,
  `mp_payment_id` varchar(255) DEFAULT NULL,
  `mp_qr_code` text DEFAULT NULL,
  `mp_qr_code_base64` longtext DEFAULT NULL,
  `mp_status` varchar(50) DEFAULT 'pending',
  `openpix_charge_id` varchar(255) DEFAULT NULL,
  `openpix_qr_code` text DEFAULT NULL,
  `openpix_qr_code_image` varchar(500) DEFAULT NULL,
  `openpix_correlation_id` varchar(255) DEFAULT NULL,
  `openpix_status` varchar(50) DEFAULT NULL,
  `openpix_paid_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `pagamentos_comissao`
--

INSERT INTO `pagamentos_comissao` (`id`, `loja_id`, `criado_por`, `valor_total`, `metodo_pagamento`, `numero_referencia`, `comprovante`, `observacao`, `observacao_admin`, `data_registro`, `data_aprovacao`, `status`, `pix_charge_id`, `pix_qr_code`, `pix_qr_code_image`, `pix_paid_at`, `mp_payment_id`, `mp_qr_code`, `mp_qr_code_base64`, `mp_status`, `openpix_charge_id`, `openpix_qr_code`, `openpix_qr_code_image`, `openpix_correlation_id`, `openpix_status`, `openpix_paid_at`) VALUES
(1183, 59, NULL, 3.00, 'pix_mercadopago', '', '', '', '', '2025-09-16 23:21:42', '2025-09-19 19:27:05', 'aprovado', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(1184, 59, NULL, 0.30, 'pix_mercadopago', '', '', '', '', '2025-09-16 23:22:03', '2025-09-19 19:27:02', 'aprovado', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(1185, 59, NULL, 3.00, 'pix_mercadopago', '', '', '', '', '2025-09-16 23:22:38', '2025-09-19 19:26:46', 'aprovado', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(1186, 59, NULL, 3.00, 'pix_mercadopago', '', '', '', '', '2025-09-16 23:23:14', '2025-09-19 19:26:06', 'aprovado', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(1187, 59, NULL, 3.00, 'pix_mercadopago', '', '', ' - CPF usado: 04355521630 - Device: device_a21b3ffcb4c08b1a0fe4b6404b817a80', ' - PIX rejeitado/cancelado no MP: cancelled', '2025-09-19 19:27:58', NULL, 'pix_aguardando', NULL, NULL, NULL, NULL, '126818862292', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c3334662252040000530398654043.005802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter1268188622926304FE8F', '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', 'cancelled', NULL, NULL, NULL, NULL, NULL, NULL),
(1188, 59, NULL, 6.00, 'pix_mercadopago', '', '', ' - CPF usado: 04355521630 - Device: device_e01182b45f962d4d4ebefe8f247ece1e', ' - PIX rejeitado/cancelado no MP: cancelled', '2025-09-19 19:29:07', NULL, 'pix_aguardando', NULL, NULL, NULL, NULL, '126260000009', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c3334662252040000530398654046.005802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter126260000009630481E8', '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', 'cancelled', NULL, NULL, NULL, NULL, NULL, NULL),
(1189, 59, NULL, 10.00, 'pix_mercadopago', '', '', '', NULL, '2025-09-19 19:33:36', NULL, 'pendente', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(1190, 59, NULL, 6.00, 'pix_mercadopago', '', '', ' - CPF usado: 04355521630 - Device: device_9e85a234eba19faa43ca9dd7c75ba225', NULL, '2025-09-19 19:34:05', NULL, 'pix_aguardando', NULL, NULL, NULL, NULL, '126817685778', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c3334662252040000530398654046.005802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter12681768577863044828', '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', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(1191, 59, NULL, 1.00, 'pix_mercadopago', '', '', ' - CPF usado: 04355521630 - Device: device_8b89334e451ba408b7b3814c21a43656', 'Pagamento PIX aprovado automaticamente via Mercado Pago - ID MP: 126263554525', '2025-09-19 19:44:07', '2025-09-20 02:45:51', 'aprovado', NULL, NULL, NULL, '2025-09-20 02:45:51', '126263554525', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c3334662252040000530398654041.005802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter1262635545256304C3F7', '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', 'approved', NULL, NULL, NULL, NULL, NULL, NULL),
(1192, 59, NULL, 1.10, 'pix_mercadopago', '', '', ' - CPF usado: 04355521630 - Device: device_984601465e18e851b19f21bde741348b', 'Pagamento PIX aprovado automaticamente via Mercado Pago - ID MP: 126819335626', '2025-09-19 19:47:09', '2025-09-20 02:45:54', 'aprovado', NULL, NULL, NULL, '2025-09-20 02:45:54', '126819335626', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c3334662252040000530398654041.105802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter12681933562663049489', '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', 'approved', NULL, NULL, NULL, NULL, NULL, NULL),
(1193, 59, NULL, 1.20, 'pix_mercadopago', '', '', ' - CPF usado: 04355521630 - Device: device_411c99d0c7582b15f94688cc453a522b', 'Pagamento PIX aprovado automaticamente via Mercado Pago - ID MP: 126264356975', '2025-09-19 19:55:50', '2025-09-19 20:50:32', 'aprovado', NULL, NULL, NULL, '2025-09-19 20:50:32', '126264356975', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c3334662252040000530398654041.205802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter12626435697563041F58', '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', 'approved', NULL, NULL, NULL, NULL, NULL, NULL),
(1194, 59, NULL, 1.10, 'pix_mercadopago', '', '', ' - CPF usado: 04355521630 - Device: device_b0baf433f6306efd8a857e98608327ea', NULL, '2025-09-19 20:03:00', NULL, 'pix_aguardando', NULL, NULL, NULL, NULL, '126822568520', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c3334662252040000530398654041.105802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter12682256852063040C72', 'iVBORw0KGgoAAAANSUhEUgAABWQAAAVkAQMAAABpQ4TyAAAABlBMVEX///8AAABVwtN+AAAK40lEQVR42uzdQXbaWhIGYPkwYMgSWApLw0tjKSyBIQMfqc/jIW5V6eLgTtpW+nz/JM9JQJ8yq1d16w4iIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIi8r/NdlrkNLxN0yX+pfnnTfmbH8Own6bbl5yHYf7d28/pS3bT9H7/+fjPh3btK25/vl8iaGlpaWlpaWlpaWlpaWn/gPZcfr49aM7tAf/m/a59vz+g6m+fP/zzX9emPD2U4129aep90ybUgZaWlpaWlpaWlpaWlnbN2mW5eqt9/83xrh562vTh9MpD019i4Tx0a95WfV9paWlpaWlpaWlpaWlp/zrt5Z/PjHft2FXX9mtTJu3YOqfzq+7iK9PS0tLS0tLS0tLS0tL+/2hv5emYytT2lzftQynXVuMeHm3XuYD+oKWlpaWlpaWlpaWlpaX9Ru2yRTsr5zOn8xnT4V6hn9uvtz86x77uFF95ztgeMk8Nzx/6vdlmWlpaWlpaWlpaWlpa2u/UPttclFq0l1iuzsq6uWguV7df/5Lf3rNES0tLS0tLS0tLS0tL+w3abmrndGoPGoa8B/dcCudnm4uGOC089EaO/+vQ0tLS0tLS0tLS0tLSfqd2H6det63J2ZYOhQeEfbjL0dmUsWnDWdMpzt8OsXO6fa3mpaWlpaWlpaWlpaWlpV2RNtW8U7pNZanNZWtbmlv14RaVqRTOTTCV9Ue0tLS0tLS0tLS0tLS0tH9C2056bqMu933bcdH5AR935TUdWL2/eq3Q65ds2ivXZvFAS0tLS0tLS0tLS0tLu07ttv3dtn02TwknZTouOqWp4ect2l3s805pDVI7c3otr05LS0tLS0tLS0tLS0u7Vu3c9Gybi2rTMzQ7l8dGO4O+h8fI8Zied3x8aVDvyxUsr9z5QktLS0tLS0tLS0tLS/sD2nDi81TK1OXSoU16QJq7rVextDOnQ/dCz2Xn9MXQ0tLS0tLS0tLS0tLS0n5J202q1MMD3oehaK9L/an0dd9/cf1ok1xpaWlpaWlpaWlpaWlpV679xb6g8KCwaHdZ64Y7X1p/t971Et7iPIQLZK7tS6ev9nlpaWlpaWlpaWlpaWlpv0dbz5xOrWN6eJSrYTr40srUtLJ2KjXwUHTvvQK6fsmZlpaWlpaWlpaWlpaW9u/Q1rJ1nrsNx0OXl30OpUzdxjOn05OrWP7N8bG5aFoeWH0aWlpaWlpaWlpaWlpaWtrXtaGoPpSVSem4aFiR9PyY6DYua+ocXO3c+dKmh/P/K6ClpaWlpaWlpaWlpaVdo7b7Z2/pge+PMjVop96Z022sfccyatzZ2jst1x+1J9PS0tLS0tLS0tLS0tKuTxv2BaWaNzU5x9bsTGdOP8qXfLr+qPMl+yncIHpNX0JLS0tLS0tLS0tLS0u7Um1oet5HZ/Oln8f7BSj1xtBWpoalQ6dHGzbUvJfllxzjjaHneFB1S0tLS0tLS0tLS0tLS7tm7T7O227j5qKx1Ly5c9py7RXOHW3nVdO/17n8u9HS0tLS0tLS0tLS0tLS/oY2by6aH5iOh05ReYl/OWvL74/pzGl95WO8QTS98kRLS0tLS0tLS0tLS0u7Zu1UNhZNsc87X9eS9uDO17V8dEeOn+y7DWdO68jxPt75MhfOtLS0tLS0tLS0tLS0tOvT5lo3Kafe7Sm7+zHR2wPCBqN92WR0iDVv0B/jW+yfrD86fT7ZTEtLS0tLS0tLS0tLS/uz2rsyl6uHh/at2+xMw7v1Os12F+dYrmKp64+m5QHWwwt3ctLS0tLS0tLS0tLS0tLSfqnPe//NfNnnsUwPvz+0m3ZM9Bxf+VoeEprFLZ2bQ6dfVei0tLS0tLS0tLS0tLS0P6sdyk0r23JNy7Np4Sn2e/Og711bdblwPj7uevnitDAtLS0tLS0tLS0tLS3tD2q3bW/QodS86cxpGvgN08Lne/Mz1LztS2oucXPRtKx5090vtLS0tLS0tLS0tLS0tOvThpOfh/Ldp2EoZ0835QzqZurnutSmzumuKb88LUxLS0tLS0tLS0tLS0tL+yVtOS46pl+717Scy6umgd905jQvazr2yvz2ikP7d6KlpaWlpaWlpaWlpaVdn7aeOQ3Twqcy6DvEGjhdNzrFA6tD+5LLkzOnu3JxzL7UuC9vLqKlpaWlpaWlpaWlpaX9du1LS4fmzullWSinD7X2a7045u3T9mu3+qalpaWlpaWlpaWlpaVdmTaVqblvOTc/h8eDQvNzKmdOw9Bu22B0iXO4Y7s9JRXOnx5cpaWlpaWlpaWlpaWlpV2hdtkpnZbNz3SaM83f5vVHTZfSv0WlexT09Pm/LC0tLS0tLS0tLS0tLS3tV7TzvqBtb3PR2JYN7Vql3lqy21Kh1w/fyvqxNI83qUJvfd7rCxU6LS0tLS0tLS0tLS0t7Tq03TI1D/p2W7TtwOqQ+rqnhy4UzpdezXtt2uHVmpeWlpaWlpaWlpaWlpb2e7W1xk0/v91X1Y5PXvGjdE6nZQf1FEeNd+XXodS83TVItLS0tLS0tLS0tLS0tKvSnodw5vTauwClP4fbat9t6aBe2xUsrVMa7uYMt6h0v+SzW1RoaWlpaWlpaWlpaWlpaV/XTnFGN935kiv0Y5wW3t+nh4enfd75lXN5324OnZPVn08L09LS0tLS0tLS0tLS0v649hxr3VCutgflVm297HN6mu7NoZulMuT+ZFpaWlpaWlpaWlpaWtpVasMK23RcNF0CGpqdQzxr+pGOibbjottWMB8ea4/GZc17Xk4H/3JzES0tLS0tLS0tLS0tLe3PauccHg8OG4su7QFDPC66j7Vvp/263FyUv6xpc+37+fwtLS0tLS0tLS0tLS0tLe2XtPvWXW37loZyxrSq5/5uuq6lu6xp6E4LL+96CUuaBlpaWlpaWlpaWlpaWtp1arfL3bhpSjgcD31ffHjTRo3TK6fCeXjS550L5k3bXHT+VVlOS0tLS0tLS0tLS0tL++Pacxv0bWXqrjU/01nTXbtB9Hn7dSrTwvOrT23dUa156+lXWlpaWlpaWlpaWlpa2pVq696guUxdNj0/Wtlam571QYdHh7QzzDu1Id62yeiaTr/S0tLS0tLS0tLS0tLSrlHb1ecDmFO5CKUO7y5X2IbNRW15bl+bco5VNy0tLS0tLS0tLS0tLS3tn9WGKeFD2Vi07PNu2gPOrc+bRo4P5aDq7UN1c1H9fwW0tLS0tLS0tLS0tLS0K9VulzXr6THYO6a+b5sannVZm/bfPqt506sPbf9tO3O6paWlpaWlpaWlpaWlpV2z9lx+Pg3P9t5u2q9ptHibat503ehhcQXLJnVQU83buUmUlpaWlpaWlpaWlpaWdn3a5ehsyPuTpUPdpucU7+IMHdR0B2dYplvnb7sbeWlpaWlpaWlpaWlpaWlp/5Q2V+ipuJ7Ktt66aHd49HvHUuaPywo9NYuv5fpRWlpaWlpaWlpaWlpa2r9E+9Y+U6eE6/HRoHwy6PuW+r5zjrGAnrf1Dq/eUENLS0tLS0tLS0tLS0v7k9oyLfxWTqGO7QG72CENNW9odt4f/NYOsHamhecPpwOrQ3x1WlpaWlpaWlpaWlpa2lVql5uL5ptDwwaj0Dlty4Y25QbR6/2M6bXp2vqjMMS7W94cmtqvn9W8tLS0tLS0tLS0tLS0tLQvakVERERERERERERERERERERERERERERERERERERWnf8EAAD//4qKsS3SkD9+AAAAAElFTkSuQmCC', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(1195, 59, NULL, 1.00, 'pix_mercadopago', '', '', ' - CPF usado: 04355521630 - Device: device_d582ce9ed2944c4985d2dcc9d6a63b7c', NULL, '2025-09-19 20:04:38', NULL, 'pix_aguardando', NULL, NULL, NULL, NULL, '126821991208', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c3334662252040000530398654041.005802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter126821991208630446A2', 'iVBORw0KGgoAAAANSUhEUgAABWQAAAVkAQMAAABpQ4TyAAAABlBMVEX///8AAABVwtN+AAAK4ElEQVR42uzdS1Lj2BIGYDkYMGQJXoqX5loaS/ESGHpA+NxoynI+JIM76oH6xvdPCKKx9Llm2ZknzyQiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIifzbPY5HXaTfG2zQdxuX6Rz9/jzxd//J9mvZjfDzkNE1jnP/5UH3IR17G+HH9/fjPh17iZR//fb9E0NLS0tLS0tLS0tLS0tL+Bu2p/f7xojnlhVUb6vkFHx+ertqfH3q9KS/XhzyFeh/agjrQ0tLS0tLS0tLS0tLSblnbXjjXvD/TleX38uHylW8vvn7lWgP3mjeq7zMtLS0tLS0tLS0tLS3tf1Hbc7y9+GlZKKeat2kv0Tmdv+pcQO9paWlpaWlpaWlpaWlp/7+0L/GCj3nbWd0/VHKOGrcXzvP8LS0tLS0tLS0tLS0tLS3t39EuW7QfunTmdIQ2KvTU5z3lvu5YNovjYVNMDc8f+rXZZlpaWlpaWlpaWlpaWtq/qf10c9HK0qEfN+X65qI2cvzYQ37PniVaWlpaWlpaWlpaWlraP6tdTX3RvMHomGveOadWOB9undO6uWjK08LpIdOvh5aWlpaWlpaWlpaWlvZvavd56vU5mpyHvP92fsHbdf52delQySW0ow3xlmW66w+hpaWlpaWlpaWlpaWl3a62Tb320dm0sahoa9kat6d0fbpFZbTCOf69Rlt/REtLS0tLS0tLS0tLS0v7a9rnO0uI3pZ937l1++P2gver8ly013K/V+j9Ien60d4snmhpaWlpaWlpaWlpaWm3rT3Hi/uUcFGW46LriYe/RL83+ryjrEGKM6fn8tVpaWlpaWlpaWlpaWlpN6odi77lbtn0XD1r+lQK5fg53xi6kuPtoUldNhedH95/S0tLS0tLS0tLS0tLS/s92uXUa296jmh2jsWZ09WHXlZr3znLzukjoaWlpaWlpaWlpaWlpaV9XJtOfL621u2n2pU7X+bE2dN51Dg95N71oyE509LS0tLS0tLS0tLS0m5cG/3e89qW3nrpZ7xwatrykEvTTq0Grs3icnD1y229tLS0tLS0tLS0tLS0tN+jTS+Kmjfd+TLacdG3tTK1rz0ayzbsjzyCPJYHV5c7lGhpaWlpaWlpaWlpaWm3q5115yntu00/x9pln0/LA6tx5nTcuYrlZ463zUXprOn+k8qclpaWlpaWlpaWlpaWlvZfaucB33m70Xz29CV+zhX7sQ36Rqs25ZAr9Pngavnq9c6XaBrXZjEtLS0tLS0tLS0tLS3tFrWr/213LVcv0aLtLzq161r2d7f17krzOGre2s+Ng6y9SUxLS0tLS0tLS0tLS0u7HW3aF3TIA79v8cK4puU9LgH99CGl9q3a8pD9SDeIntvoMS0tLS0tLS0tLS0tLe0mtX1l7VzrLlfY9pp3LlPP8cIopC+lgD7eCug0f7vPy3RXCmdaWlpaWlpaWlpaWlra7WnL2cnnGJmNmrfWvv30ZpSr5zgKeljTTu3Dp9bDfbBzSktLS0tLS0tLS0tLS0v7/Ni0cPqb5Z0vfUvvWyvru3bKK3/TmdPjWtO46M/lDlNaWlpaWlpaWlpaWlrajWr7dS1x50s9c3pcXNfyviyc7++7TWdOU793Prga/25p5JiWlpaWlpaWlpaWlpZ2Y9opBn0PeenQS77kcyovihc8xWDvPi8hOueR413RH/O36B/a5w28tLS0tLS0tLS0tLS0tJvU3itXDzftbrXZGR9+bndylrs4L8urWErNO8o87pc1Ly0tLS0tLS0tLS0tLS3tg9ra5y3HRKfc31259HP1BtFDTA23vu6u9H2ntZtDx1cVOi0tLS0tLS0tLS0tLe23a09RecbmopH7vLs7G4veo2w95QOr5/yVp/JPMBfOx9uH+7TwZ3e+0NLS0tLS0tLS0tLS0n6vdmVaeLrtv03a+azp27Jz2mveeMhYHmQ95qnhXvOe8sUxtLS0tLS0tLS0tLS0tJvU3p+/XXnB/POU1c/LW1WW2r5U9/13TwvT0tLS0tLS0tLS0tLS0q5o23HRlRVJfVXSKZf38+/Pyz8+tGVN5c6XtKypXBjzxZ4lWlpaWlpaWlpaWlpa2u/S9jOnaVr4dXFc9KkNAE+lbC2/v+Zp4X7m9KVdHLNvNe7Dm4toaWlpaWlpaWlpaWlp/7p2denQvLL2JU8Lp7J1tEK5v2h5cUwaOS4XyayMHg9aWlpaWlpaWlpaWlrabWqT+rX1Lcv8bX/Rvo3Mzr+/5g7qW57DvcTtKS/5oOr6El1aWlpaWlpaWlpaWlra7Wrb1GtvcqYXlD247+0uznM+Cnpp71i/RWW5VPeLaWFaWlpaWlpaWlpaWlpa2n+lLcdFXz/Tpgp9dXNRKfNfRtrSm/RpWni6/ZzL/vNj/z+BlpaWlpaWlpaWlpaW9tu05W/K5qK33Oet6ujzjnxgdSp93dc8arxSOJfNRfs8r0xLS0tLS0tLS0tLS0u7VW3M6JbNRbP2cucr9s7pWHZQ7xXQ5cxpX6b75ZlTWlpaWlpaWlpaWlpa2u/RPscFKK9LfSwdKtdo1pq3L9G9anunNF3Jkm5RWX3IZ7eo0NLS0tLS0tLS0tLS0tI+qO0zup9U6B8veMl7lt5bUV36vPPNobW8j5tDRzm4WpY2fTYtTEtLS0tLS0tLS0tLS7sBbW/Rlhf1gd962WdfOrTcXNSvH+3KlOg409LS0tLS0tLS0tLS0m5SO7VZ3bnWPeSnzM3ONOAbLzxfP3yOdmw85NI2GKWa97ScDn5szxItLS0tLS0tLS0tLS3t92ify/RrG51Nm4v6sdH9GPfOnF5/v1zL10upeZfaWvs+PC1MS0tLS0tLS0tLS0tLS/ugdsqrkablgO/Ii3bnYjr1e/f5RfUOmNVp4eVdL6nTPNHS0tLS0tLS0tLS0tJuU7uSvnRo3DYYzS98b83hc6gPtxfuYmp4tc/7HtePntq/Gy0tLS0tLS0tLS0tLe1Gtc+xLyimhfsln7t4wUcT9CkGfFPNu7zrpdS8uzhrOi1r3iL5YlqYlpaWlpaWlpaWlpaW9hu1fW/QXOsum56p1i3KUdqvUfumr1qGeUcM8cYmo3Nrv9LS0tLS0tLS0tLS0tJuT7uqv4T25U7HdGV1bTuImZSjneIc+ShoyilX3bS0tLS0tLS0tLS0tLS0v1c7RTF9uA329ptDn+LBfdA3KvRLq9Sn6O/2zUX9/xXQ0tLS0tLS0tLS0tLSblT7vKxZo1ydSt/3x60lOyufosYtg76f1LzTlK4fLeuP0iZeWlpaWlpaWlpaWlpa2u1qT+3319u0cH/hU/kZLxxtie45at3D4gqWp9JBLTXvyk2itLS0tLS0tLS0tLS0tNvTLkdn0/KhWGFbL0BZbXqOUS/0PCz23tZlun3+dn4YLS0tLS0tLS0tLS0tLe0f0dap4eNV2yv0OHs6r/o9Lw+ujjxy3Cv0U/7qfV8wLS0tLS0tLS0tLS0t7X9Em65rmTcXlZo3HR89NeXr7WGX+Mpj+eFjvvtlxFcuB1dpaWlpaWlpaWlpaWlpN6lt08K71gmtm4vKQG+/9DNq393yAGuqeaNgHvHh/T0lLS0tLS0tLS0tLS0t7Wa0y81F6cxpmr8tZ03nSz/TV42zpufQLWvf9yik082hq0t0aWlpaWlpaWlpaWlpaWl/QSsiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiKy6fwvAAD//x7QrgCJrnA0AAAAAElFTkSuQmCC', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(1196, 59, NULL, 0.50, 'pix_mercadopago', '', '', ' - CPF usado: 04355521630 - Device: device_7e9b25b554004462bee75deef32e8aef', NULL, '2025-09-19 20:20:36', NULL, 'pix_aguardando', NULL, NULL, NULL, NULL, '126268688153', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c3334662252040000530398654040.505802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter12626868815363046416', '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', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(1197, 59, NULL, 1.10, 'pix_mercadopago', '', '', ' - CPF usado: 04355521630 - Device: device_923feaf26b70a3f88c7346f4544cd4ac', 'Pagamento PIX aprovado automaticamente via Mercado Pago - ID MP: 126271700787', '2025-09-19 20:47:29', '2025-09-19 20:47:59', 'aprovado', NULL, NULL, NULL, '2025-09-19 20:47:59', '126271700787', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c3334662252040000530398654041.105802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter1262717007876304E058', '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', 'approved', NULL, NULL, NULL, NULL, NULL, NULL),
(1198, 59, NULL, 0.96, 'pix_mercadopago', '', '', ' - CPF usado: 04355521630 - Device: device_dd2f69a3460dcdfc7086fdf88c57c1d9', 'Pagamento PIX aprovado automaticamente via Mercado Pago - ID MP: 126270991941', '2025-09-19 20:50:55', '2025-09-19 20:51:21', 'aprovado', NULL, NULL, NULL, '2025-09-19 20:51:21', '126270991941', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c3334662252040000530398654040.965802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter12627099194163042DBE', 'iVBORw0KGgoAAAANSUhEUgAABWQAAAVkAQMAAABpQ4TyAAAABlBMVEX///8AAABVwtN+AAAKl0lEQVR42uzdQVLjyBIG4CJYeOkj+Cg+GhyNo/gILL1woBfjkFyZqTJ2v+5pNBHfvyFgQPrcu5zMymoiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIiIi8u9mN63yUX7l7Z8ffv7z9dLafpre2+v1967/8TD/0an/fnnIy/X79/iwkPSQ+nNaWlpaWlpaWlpaWlpa2t/Tnsr3Sbv/50+/0vdJe9Wd1889zn90nN9ete+zssWv/Y9paWlpaWlpaWlpaWlpN6ztleZV+3KtcY+3WvdrVrb+9VqmXuav5/mFu/4pjvMffcw1b89lfnMunHv1faalpaWlpaWlpaWlpaX9b2lz2foWa96lbK2d0tB2nb+GjzzFj/p6p+alpaWlpaWlpaWlpaWl/Y9qlxctte4yQvt6/fm1XD0V5b3R2a59mQvoiZaWlpaWlpaWlpaWlpb2L2rLtHAurqdbvze/qPd5Q8V+7C9MlXofPb6sy/zfm22mpaWlpaWlpaWlpaWl/Zva9eaiQc1bNxdda95LL1dP89nT43eF8zcP+Y09S7S0tLS0tLS0tLS0tLR/Tfso+17ztrJ8KHVQT6ty9WW9THcf26+DWve3Q0tLS0tLS0tLS0tLS/uvaw+3ZUO5XK15Kx3U1u5dgLIrV7KEId7p1n59nT9i/sjDZbq0tLS0tLS0tLS0tLS0m9L2kdlzqXXTC8L+2zY3PVP7Nb1o+cj5Ls708FN5SDpPSktLS0tLS0tLS0tLS0v7e9p0TcuS5bqWb1q0dUvvqbV1Zd7ixTFL8pnT9T/K+eGZU1paWlpaWlpaWlpaWtof1J5WZetLP3sa9FM5Ltov/9yVF59HNe9L16aaN3/k9YgxLS0tLS0tLS0tLS0t7Xa0g4HfjzvHRYcPrlewTFPef9sPrIbNRW3+eii17uGJzUW0tLS0tLS0tLS0tLS0G9Ce52bouTc7Wzl7+nYblX0dXoCyftF+VL6+9k5qLZQPjzqntLS0tLS0tLS0tLS0tLTPa/O08HrPUjhjOtTeaxZf73qp08Kf9w+0pgOrtLS0tLS0tLS0tLS0tBvVhhfW8rS/6Kv0ey9zjRtatWHQtxTOLd0cuowY941FrTzkTEtLS0tLS0tLS0tLS7tZ7aDmnW66cFz0c3Tny+uw6dlHjuv+23RwtaWP3NuvtLS0tLS0tLS0tLS0tNvVht9p8d7O/frSz/dZ9146p3X/bW+/DtRJe5g/8qmFW1RoaWlpaWlpaWlpaWlpaf+Mdld25NYKPQ36Du58qcrhtHBKvvOltXBw9XAr7xstLS0tLS0tLS0tLS3tdrXppOfx7vHQ8XUt/UXn+JA6Ypw/8tutv/vaa91UddPS0tLS0tLS0tLS0tJuT9vK7+zu3PnSRi+qI8e79T6ij6JdRo3fb6PGgz24T/Z5aWlpaWlpaWlpaWlpaf+2dndn6VC4QbR3Ul/78dHWm573V9mGgjkt0d3Hh7yWwvn5Pi8tLS0tLS0tLS0tLS3tD2incp3mUqZ27bQ+gLksHUpzuPUClKqtHdTloYOPTEtLS0tLS0tLS0tLS0v7p7S7WFyHynwft/VeepFdi+vaLJ5Ksd1X/Y7PnK7Le1paWlpaWlpaWlpaWtpNak+3KeFa89ZB33zmNCnTmdNH64/CEt31wdUHNS8tLS0tLS0tLS0tLS3tNrThxGeZ2R3cHJrK1W9q3b4G6aWcNb309uuh9G5paWlpaWlpaWlpaWlpN6rNv1PydX+U9pszp/fXH+3L2dOn525paWlpaWlpaWlpaWlpaX9duyuXf4aKPBXXwztgws2h6czpok2Veesjxp/xo17K3DItLS0tLS0tLS0tLS3tdrV1RreWq6nWzcuGlks/+8rf4XUt4aO2dbO4P2y3nh6mpaWlpaWlpaWlpaWl3aR2XfNO/axpi83O/Z3Oaa15p1XBnKeF+5nTQc17pKWlpaWlpaWlpaWlpd2ydphe836VcjXUvGl5bk3Qvt1+eHkwvPt855SWlpaWlpaWlpaWlpaW9sG0cNK1OBX8tW7V7teLdtPU8FS29R7vXhwTdKHMf+aGGlpaWlpaWlpaWlpaWtof1IY7X4p+sKU3vHBdvoatvWnVbz1zGr5Pc8qnR2dPaWlpaWlpaWlpaWlpabegLX3L75cOrbW7eGC15puPPKx1hw+hpaWlpaWlpaWlpaWl3ZQ2/W7Yf3uvXD3Epud51p/L/tt7B1fDw1LNO8XCmZaWlpaWlpaWlpaWlnZ72rr/tsXbU1ofnU37b6e0B/f6gkNcOrRbK1spnNvoIx9oaWlpaWlpaWlpaWlpaf+0dv2i4Z0vocgeLh0KI8e9Qv/qD/lcTwun/1fwzAYjWlpaWlpaWlpaWlpa2h/U7soSolpxDl50/6zpUjCn75fluV/r/u7y5lN7MrS0tLS0tLS0tLS0tLQ/q23zoO9UytVvOqfv5YX9Ief4sDBy/Nm1ff9tLZxD+5WWlpaWlpaWlpaWlpZ2o9rhLSp5c9EUXxRuUWmj21S+XX+07wXzvbs5H24uoqWlpaWlpaWlpaWlpaX9v7TrEeDPUd/3UorpuiIp3Rw6dfXngwq9rfYG09LS0tLS0tLS0tLS0m5Ne4ot2nOsdXOLti8dqhuM8rbe8pCvUuPWf4LXfmNo2htMS0tLS0tLS0tLS0tLu0ltLVfTxqJFWzcYHcqx0eUhx9g5nUoTdL8eOe7t11+ZFqalpaWlpaWlpaWlpaX9GW1b9y+Pt6bnVxql7fO39QKUpdm5K+3XwfWjqXPaykelpaWlpaWlpaWlpaWlpf2D2l1ZlTTFQd+vVGy/x0s/W+z31m29y0P2cWlTnhYePuSwOv1KS0tLS0tLS0tLS0tLuzVtOnN67i/8uJWpeeA31bxtfW3LtwdW38vNoetTrqlZTEtLS0tLS0tLS0tLS7s17enBdS3rM6h5SjiNHB/j18+5cN4X/T7qcgf1mc4pLS0tLS0tLS0tLS0t7Q9qpzhC2+ILQq0byteuvaSH9M7pObZdc9JVLNNoiJeWlpaWlpaWlpaWlpZ2u9qhPmj3/RrNehCzd0x36/7nemPRWJvycP8tLS0tLS0tLS0tLS0tLe3/r20tX9dyffBLv+tlqdTrxqJlWvjYNxj1M6dT/CeY+gHW0OftD9n9aqVOS0tLS0tLS0tLS0tL+9e035SrfXVtXjpUa95Urh5jv7eOGCfd8pEv8wHWQceZlpaWlpaWlpaWlpaWdnvaU/k+lavpRbnJ2W4Dv4Oa9/4S3dBBPaxr3mc2F9HS0tLS0tLS0tLS0tL+rDY1QZdm50d8QRqhrWXpbhonrD/qS3TD/O2p7L9NH5mWlpaWlpaWlpaWlpaW9l/Q5uOi023P0uDm0FPp907TVI6N1sq8TgtPv7BviZaWlpaWlpaWlpaWlnaz2nTny2fc2psX7dZtvWX9Ub045tIL6EOpdR+GlpaWlpaWlpaWlpaWdgvaMi38kl7wHmvcunSoalspmD/u1L6tbC66X3XT0tLS0tLS0tLS0tLSbkr7eHNRW9+mcoh/cZ6/T8dFX9JD32MbdlrfHLp4/sCeJVpaWlpaWlpaWlpaWlpaERERERERERERERERERERERERERERERERERERke3nfwEAAP//YCx6RJl6uo4AAAAASUVORK5CYII=', 'approved', NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `pagamentos_comissao` (`id`, `loja_id`, `criado_por`, `valor_total`, `metodo_pagamento`, `numero_referencia`, `comprovante`, `observacao`, `observacao_admin`, `data_registro`, `data_aprovacao`, `status`, `pix_charge_id`, `pix_qr_code`, `pix_qr_code_image`, `pix_paid_at`, `mp_payment_id`, `mp_qr_code`, `mp_qr_code_base64`, `mp_status`, `openpix_charge_id`, `openpix_qr_code`, `openpix_qr_code_image`, `openpix_correlation_id`, `openpix_status`, `openpix_paid_at`) VALUES
(1199, 34, NULL, 1.00, 'pix_mercadopago', '', '', ' - CPF usado: 00000000191 - Device: device_a6d0ed984bf6644ad7aa267ebf6253dd', 'Pagamento PIX aprovado automaticamente via Mercado Pago - ID MP: 126959632736', '2025-09-20 22:25:33', '2025-09-20 22:26:29', 'aprovado', NULL, NULL, NULL, '2025-09-20 22:26:29', '126959632736', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c3334662252040000530398654041.005802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter1269596327366304A0B7', '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', 'approved', NULL, NULL, NULL, NULL, NULL, NULL),
(1200, 34, NULL, 2.00, 'pix_mercadopago', '', '', ' - CPF usado: 00000000191 - Device: device_288ae381daaded28e711826547f4e4be', 'Pagamento PIX aprovado automaticamente via Mercado Pago - ID MP: 126404758249', '2025-09-20 22:40:57', '2025-09-20 22:42:13', 'aprovado', NULL, NULL, NULL, '2025-09-20 22:42:13', '126404758249', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c3334662252040000530398654042.005802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter1264047582496304BBCF', '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', 'approved', NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `pagamentos_devolucoes`
--

CREATE TABLE `pagamentos_devolucoes` (
  `id` int(11) NOT NULL,
  `pagamento_id` int(11) NOT NULL,
  `mp_payment_id` varchar(255) NOT NULL,
  `mp_refund_id` varchar(255) DEFAULT NULL,
  `valor_devolucao` decimal(10,2) NOT NULL,
  `motivo` text NOT NULL,
  `tipo` enum('total','parcial') DEFAULT 'total',
  `status` enum('solicitado','processando','aprovado','rejeitado','erro') DEFAULT 'solicitado',
  `solicitado_por` int(11) NOT NULL,
  `aprovado_por` int(11) DEFAULT NULL,
  `data_solicitacao` timestamp NULL DEFAULT current_timestamp(),
  `data_processamento` timestamp NULL DEFAULT NULL,
  `observacao_admin` text DEFAULT NULL,
  `dados_mp` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pagamentos_transacoes`
--

CREATE TABLE `pagamentos_transacoes` (
  `id` int(11) NOT NULL,
  `pagamento_id` int(11) NOT NULL,
  `transacao_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `pagamentos_transacoes`
--

INSERT INTO `pagamentos_transacoes` (`id`, `pagamento_id`, `transacao_id`) VALUES
(200, 1183, 378),
(201, 1184, 377),
(202, 1185, 379),
(203, 1186, 380),
(204, 1187, 383),
(205, 1188, 384),
(206, 1189, 385),
(207, 1190, 386),
(208, 1191, 387),
(209, 1192, 389),
(210, 1193, 390),
(211, 1194, 391),
(212, 1195, 388),
(213, 1196, 392),
(214, 1197, 394),
(215, 1198, 395),
(216, 1199, 496),
(217, 1200, 499);

-- --------------------------------------------------------

--
-- Estrutura para tabela `pagamento_transacoes`
--

CREATE TABLE `pagamento_transacoes` (
  `id` int(11) NOT NULL,
  `pagamento_id` int(11) NOT NULL,
  `transacao_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `recuperacao_senha`
--

CREATE TABLE `recuperacao_senha` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `data_expiracao` timestamp NOT NULL,
  `usado` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `sessoes`
--

CREATE TABLE `sessoes` (
  `id` varchar(255) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `data_inicio` timestamp NULL DEFAULT current_timestamp(),
  `data_expiracao` timestamp NOT NULL,
  `ip` varchar(45) NOT NULL,
  `user_agent` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `store_balance_payments`
--

CREATE TABLE `store_balance_payments` (
  `id` int(11) NOT NULL,
  `loja_id` int(11) NOT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `metodo_pagamento` varchar(50) NOT NULL DEFAULT 'pix',
  `numero_referencia` varchar(100) DEFAULT NULL,
  `comprovante` varchar(255) DEFAULT NULL,
  `observacao` text DEFAULT NULL,
  `status` enum('pendente','em_processamento','aprovado','rejeitado') DEFAULT 'pendente',
  `data_criacao` timestamp NULL DEFAULT current_timestamp(),
  `data_processamento` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `transacoes_cashback`
--

CREATE TABLE `transacoes_cashback` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `loja_id` int(11) NOT NULL,
  `criado_por` int(11) DEFAULT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `valor_cashback` decimal(10,2) NOT NULL,
  `valor_cliente` decimal(10,2) NOT NULL,
  `valor_admin` decimal(10,2) NOT NULL,
  `valor_loja` decimal(10,2) NOT NULL,
  `codigo_transacao` varchar(50) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `data_transacao` timestamp NULL DEFAULT current_timestamp(),
  `data_criacao_usuario` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('pendente','aprovado','cancelado','pagamento_pendente') DEFAULT 'pendente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `transacoes_cashback`
--

INSERT INTO `transacoes_cashback` (`id`, `usuario_id`, `loja_id`, `criado_por`, `valor_total`, `valor_cashback`, `valor_cliente`, `valor_admin`, `valor_loja`, `codigo_transacao`, `descricao`, `data_transacao`, `data_criacao_usuario`, `status`) VALUES
(367, 180, 59, NULL, 800.00, 200.00, 200.00, 0.00, 0.00, 'KC25091422441319527-SH', 'Desenvolvimento do Site e sistemas Web, Clea Casamentos', '2025-09-14 22:42:00', '2025-09-15 01:44:47', 'aprovado'),
(372, 9, 59, NULL, 100.00, 10.00, 5.00, 5.00, 0.00, 'KC25091612224971701', '', '2025-09-16 12:22:00', '2025-09-16 15:22:52', 'aprovado'),
(373, 9, 59, NULL, 10.00, 1.00, 0.50, 0.50, 0.00, 'KC25091612273805918', '', '2025-09-16 12:26:00', '2025-09-16 15:28:14', 'pagamento_pendente'),
(374, 9, 59, NULL, 10.00, 0.40, 0.20, 0.20, 0.00, 'KC25091619393260353', '', '2025-09-16 19:39:00', '2025-09-16 22:39:39', 'pendente'),
(375, 9, 59, NULL, 10.00, 0.40, 0.20, 0.20, 0.00, 'KC25091619404944909', '', '2025-09-16 19:40:00', '2025-09-16 22:40:52', 'pendente'),
(376, 9, 59, NULL, 100.00, 4.00, 2.00, 2.00, 0.00, 'KC25091619412052358', '', '2025-09-16 19:41:00', '2025-09-16 22:41:22', 'pendente'),
(377, 9, 59, NULL, 10.00, 1.00, 0.70, 0.30, 0.00, 'KC25091619415389959', '', '2025-09-16 19:41:00', '2025-09-16 22:41:56', 'aprovado'),
(378, 9, 59, NULL, 100.00, 10.00, 7.00, 3.00, 0.00, 'KC25091620182192857', '', '2025-09-16 20:18:00', '2025-09-16 23:18:23', 'aprovado'),
(379, 9, 59, NULL, 100.00, 10.00, 7.00, 3.00, 0.00, 'KC25091620222473508', '', '2025-09-16 20:22:00', '2025-09-16 23:22:27', 'aprovado'),
(380, 9, 59, NULL, 100.00, 10.00, 7.00, 3.00, 0.00, 'KC25091620225112075', '', '2025-09-16 20:22:00', '2025-09-16 23:23:01', 'aprovado'),
(381, 142, 38, NULL, 200.00, 10.00, 5.00, 5.00, 0.00, 'KC25091915200140626', '', '2025-09-19 15:19:00', '2025-09-19 18:20:27', 'aprovado'),
(382, 184, 38, NULL, 300.00, 15.00, 7.50, 7.50, 0.00, 'KC25091915360042525', '', '2025-09-19 15:35:00', '2025-09-19 18:36:08', 'aprovado'),
(383, 9, 59, NULL, 30.00, 3.00, 2.10, 0.90, 0.00, 'KC25091916274693789', '', '2025-09-19 16:27:00', '2025-09-19 19:27:48', 'pagamento_pendente'),
(384, 9, 59, NULL, 60.00, 6.00, 4.20, 1.80, 0.00, 'KC25091916284176794', '', '2025-09-19 16:28:00', '2025-09-19 19:28:43', 'pagamento_pendente'),
(385, 9, 59, NULL, 100.00, 10.00, 7.00, 3.00, 0.00, 'KC25091916324012440', '', '2025-09-19 16:32:00', '2025-09-19 19:33:27', 'pagamento_pendente'),
(386, 9, 59, NULL, 60.00, 6.00, 4.20, 1.80, 0.00, 'KC25091916335247555', '', '2025-09-19 16:33:00', '2025-09-19 19:33:56', 'pagamento_pendente'),
(387, 9, 59, NULL, 10.00, 1.00, 0.70, 0.30, 0.00, 'KC25091916432212857', '', '2025-09-19 16:43:00', '2025-09-19 19:43:23', 'aprovado'),
(388, 9, 59, NULL, 10.00, 1.00, 0.70, 0.30, 0.00, 'KC25091916435390248', '', '2025-09-19 16:43:00', '2025-09-19 19:43:55', 'pagamento_pendente'),
(389, 9, 59, NULL, 11.00, 1.10, 0.55, 0.55, 0.00, 'KC25091916465958159', '', '2025-09-19 16:46:00', '2025-09-19 19:47:00', 'aprovado'),
(390, 9, 59, NULL, 12.00, 1.20, 0.60, 0.60, 0.00, 'KC25091916554006253', '', '2025-09-19 16:55:00', '2025-09-19 19:55:41', 'aprovado'),
(391, 9, 59, NULL, 11.00, 1.10, 0.55, 0.55, 0.00, 'KC25091917024445566', '', '2025-09-19 17:02:00', '2025-09-19 20:02:47', 'pagamento_pendente'),
(392, 9, 59, NULL, 5.00, 0.50, 0.25, 0.25, 0.00, 'KC25091917202566466', '', '2025-09-19 17:11:00', '2025-09-19 20:20:27', 'pagamento_pendente'),
(393, 9, 59, NULL, 10.50, 1.05, 0.53, 0.53, 0.00, 'KC25091917465013123', '', '2025-09-19 17:46:00', '2025-09-19 20:46:51', 'pendente'),
(394, 9, 59, NULL, 11.00, 1.10, 0.55, 0.55, 0.00, 'KC25091917471349718', '', '2025-09-19 17:47:00', '2025-09-19 20:47:14', 'aprovado'),
(395, 9, 59, NULL, 12.00, 0.96, 0.84, 0.12, 0.00, 'KC25091917504217506', '', '2025-09-19 17:50:00', '2025-09-19 20:50:44', 'aprovado'),
(396, 9, 59, NULL, 100.00, 8.00, 7.00, 1.00, 0.00, 'KC25091921513995418', '', '2025-09-19 21:51:00', '2025-09-20 00:51:41', 'pendente'),
(397, 142, 38, NULL, 23.70, 1.19, 0.59, 0.59, 0.00, 'KC25091923122480714', '', '2025-09-19 23:12:00', '2025-09-20 02:12:33', 'aprovado'),
(398, 142, 38, NULL, 23700.00, 1185.00, 592.50, 592.50, 0.00, 'KC25091923130625250', '', '2025-09-19 23:12:00', '2025-09-20 02:13:15', 'aprovado'),
(399, 185, 34, NULL, 100.00, 10.00, 5.00, 0.00, 0.00, NULL, NULL, '2025-09-20 20:10:30', '2025-09-20 20:10:30', 'aprovado'),
(400, 185, 34, NULL, 100.00, 10.00, 5.00, 0.00, 0.00, NULL, NULL, '2025-09-20 20:14:35', '2025-09-20 20:14:35', 'aprovado'),
(401, 185, 34, NULL, 100.00, 10.00, 5.00, 0.00, 0.00, NULL, NULL, '2025-09-20 20:15:45', '2025-09-20 20:15:45', 'aprovado'),
(402, 185, 34, NULL, 100.00, 10.00, 5.00, 0.00, 0.00, NULL, NULL, '2025-09-20 20:16:18', '2025-09-20 20:16:18', 'aprovado'),
(403, 185, 34, NULL, 100.00, 10.00, 5.00, 0.00, 0.00, NULL, NULL, '2025-09-20 20:18:29', '2025-09-20 20:18:29', 'aprovado'),
(404, 185, 34, NULL, 100.00, 10.00, 5.00, 0.00, 0.00, NULL, NULL, '2025-09-20 20:18:48', '2025-09-20 20:18:48', 'aprovado'),
(405, 185, 34, NULL, 100.00, 10.00, 5.00, 0.00, 0.00, NULL, NULL, '2025-09-20 20:19:05', '2025-09-20 20:19:05', 'aprovado'),
(406, 185, 34, NULL, 100.00, 10.00, 5.00, 0.00, 0.00, NULL, NULL, '2025-09-20 20:20:12', '2025-09-20 20:20:12', 'aprovado'),
(407, 185, 34, NULL, 100.00, 10.00, 5.00, 0.00, 0.00, NULL, NULL, '2025-09-20 20:21:44', '2025-09-20 20:21:44', 'aprovado'),
(408, 9, 59, NULL, 5.00, 0.40, 0.35, 0.05, 0.00, 'KC25092017244146332', '', '2025-09-20 17:24:00', '2025-09-20 20:24:44', 'aprovado'),
(409, 185, 34, NULL, 100.00, 10.00, 5.00, 0.00, 0.00, NULL, NULL, '2025-09-20 20:25:48', '2025-09-20 20:25:48', 'aprovado'),
(410, 9, 34, NULL, 100.00, 10.00, 5.00, 0.00, 0.00, NULL, NULL, '2025-09-20 20:26:04', '2025-09-20 20:26:04', 'aprovado'),
(411, 186, 34, NULL, 50.00, 5.00, 2.50, 0.00, 0.00, NULL, NULL, '2025-09-20 20:30:15', '2025-09-20 20:30:15', 'aprovado'),
(412, 187, 34, NULL, 121.00, 12.10, 6.05, 0.00, 0.00, NULL, NULL, '2025-08-18 20:30:17', '2025-09-20 20:30:17', 'aprovado'),
(413, 187, 34, NULL, 43.00, 4.30, 2.15, 0.00, 0.00, NULL, NULL, '2025-06-30 20:30:17', '2025-09-20 20:30:17', 'aprovado'),
(414, 187, 34, NULL, 147.00, 14.70, 7.35, 0.00, 0.00, NULL, NULL, '2025-07-18 20:30:18', '2025-09-20 20:30:18', 'aprovado'),
(415, 187, 34, NULL, 102.00, 10.20, 5.10, 0.00, 0.00, NULL, NULL, '2025-06-30 20:30:18', '2025-09-20 20:30:18', 'aprovado'),
(416, 187, 34, NULL, 132.00, 13.20, 6.60, 0.00, 0.00, NULL, NULL, '2025-07-25 20:30:18', '2025-09-20 20:30:18', 'aprovado'),
(417, 187, 34, NULL, 80.00, 8.00, 4.00, 0.00, 0.00, NULL, NULL, '2025-09-20 20:30:18', '2025-09-20 20:30:18', 'aprovado'),
(418, 188, 34, NULL, 147.00, 14.70, 7.35, 0.00, 0.00, NULL, NULL, '2025-07-24 20:30:20', '2025-09-20 20:30:20', 'aprovado'),
(419, 188, 34, NULL, 118.00, 11.80, 5.90, 0.00, 0.00, NULL, NULL, '2025-08-18 20:30:20', '2025-09-20 20:30:20', 'aprovado'),
(420, 188, 34, NULL, 56.00, 5.60, 2.80, 0.00, 0.00, NULL, NULL, '2025-07-29 20:30:21', '2025-09-20 20:30:21', 'aprovado'),
(421, 188, 34, NULL, 34.00, 3.40, 1.70, 0.00, 0.00, NULL, NULL, '2025-09-11 20:30:21', '2025-09-20 20:30:21', 'aprovado'),
(422, 188, 34, NULL, 31.00, 3.10, 1.55, 0.00, 0.00, NULL, NULL, '2025-08-25 20:30:21', '2025-09-20 20:30:21', 'aprovado'),
(423, 188, 34, NULL, 131.00, 13.10, 6.55, 0.00, 0.00, NULL, NULL, '2025-07-31 20:30:22', '2025-09-20 20:30:22', 'aprovado'),
(424, 188, 34, NULL, 147.00, 14.70, 7.35, 0.00, 0.00, NULL, NULL, '2025-07-06 20:30:22', '2025-09-20 20:30:22', 'aprovado'),
(425, 188, 34, NULL, 128.00, 12.80, 6.40, 0.00, 0.00, NULL, NULL, '2025-07-08 20:30:22', '2025-09-20 20:30:22', 'aprovado'),
(426, 188, 34, NULL, 124.00, 12.40, 6.20, 0.00, 0.00, NULL, NULL, '2025-07-26 20:30:23', '2025-09-20 20:30:23', 'aprovado'),
(427, 188, 34, NULL, 102.00, 10.20, 5.10, 0.00, 0.00, NULL, NULL, '2025-07-11 20:30:23', '2025-09-20 20:30:23', 'aprovado'),
(428, 188, 34, NULL, 63.00, 6.30, 3.15, 0.00, 0.00, NULL, NULL, '2025-08-30 20:30:23', '2025-09-20 20:30:23', 'aprovado'),
(429, 188, 34, NULL, 107.00, 10.70, 5.35, 0.00, 0.00, NULL, NULL, '2025-07-10 20:30:24', '2025-09-20 20:30:24', 'aprovado'),
(430, 188, 34, NULL, 60.00, 6.00, 3.00, 0.00, 0.00, NULL, NULL, '2025-08-16 20:30:24', '2025-09-20 20:30:24', 'aprovado'),
(431, 188, 34, NULL, 117.00, 11.70, 5.85, 0.00, 0.00, NULL, NULL, '2025-07-08 20:30:24', '2025-09-20 20:30:24', 'aprovado'),
(432, 188, 34, NULL, 78.00, 7.80, 3.90, 0.00, 0.00, NULL, NULL, '2025-09-15 20:30:25', '2025-09-20 20:30:25', 'aprovado'),
(433, 188, 34, NULL, 92.00, 9.20, 4.60, 0.00, 0.00, NULL, NULL, '2025-07-05 20:30:25', '2025-09-20 20:30:25', 'aprovado'),
(434, 188, 34, NULL, 71.00, 7.10, 3.55, 0.00, 0.00, NULL, NULL, '2025-06-26 20:30:25', '2025-09-20 20:30:25', 'aprovado'),
(435, 188, 34, NULL, 143.00, 14.30, 7.15, 0.00, 0.00, NULL, NULL, '2025-07-17 20:30:25', '2025-09-20 20:30:25', 'aprovado'),
(436, 188, 34, NULL, 39.00, 3.90, 1.95, 0.00, 0.00, NULL, NULL, '2025-07-06 20:30:26', '2025-09-20 20:30:26', 'aprovado'),
(437, 188, 34, NULL, 112.00, 11.20, 5.60, 0.00, 0.00, NULL, NULL, '2025-07-09 20:30:26', '2025-09-20 20:30:26', 'aprovado'),
(438, 188, 34, NULL, 150.00, 15.00, 7.50, 0.00, 0.00, NULL, NULL, '2025-07-16 20:30:26', '2025-09-20 20:30:26', 'aprovado'),
(439, 188, 34, NULL, 120.00, 12.00, 6.00, 0.00, 0.00, NULL, NULL, '2025-06-24 20:30:27', '2025-09-20 20:30:27', 'aprovado'),
(440, 188, 34, NULL, 86.00, 8.60, 4.30, 0.00, 0.00, NULL, NULL, '2025-09-04 20:30:27', '2025-09-20 20:30:27', 'aprovado'),
(441, 188, 34, NULL, 62.00, 6.20, 3.10, 0.00, 0.00, NULL, NULL, '2025-09-03 20:30:27', '2025-09-20 20:30:27', 'aprovado'),
(442, 188, 34, NULL, 66.00, 6.60, 3.30, 0.00, 0.00, NULL, NULL, '2025-06-23 20:30:28', '2025-09-20 20:30:28', 'aprovado'),
(443, 188, 34, NULL, 120.00, 12.00, 6.00, 0.00, 0.00, NULL, NULL, '2025-09-20 20:30:28', '2025-09-20 20:30:28', 'aprovado'),
(444, 189, 34, NULL, 47.00, 4.70, 2.35, 0.00, 0.00, NULL, NULL, '2025-09-01 20:30:30', '2025-09-20 20:30:30', 'aprovado'),
(445, 189, 34, NULL, 117.00, 11.70, 5.85, 0.00, 0.00, NULL, NULL, '2025-09-07 20:30:30', '2025-09-20 20:30:30', 'aprovado'),
(446, 189, 34, NULL, 36.00, 3.60, 1.80, 0.00, 0.00, NULL, NULL, '2025-08-20 20:30:30', '2025-09-20 20:30:30', 'aprovado'),
(447, 189, 34, NULL, 350.00, 35.00, 17.50, 0.00, 0.00, NULL, NULL, '2025-09-20 20:30:31', '2025-09-20 20:30:31', 'aprovado'),
(448, 185, 34, NULL, 91.00, 9.10, 4.55, 0.00, 0.00, NULL, NULL, '2025-09-05 20:30:32', '2025-09-20 20:30:32', 'aprovado'),
(449, 185, 34, NULL, 115.00, 11.50, 5.75, 0.00, 0.00, NULL, NULL, '2025-08-14 20:30:32', '2025-09-20 20:30:32', 'aprovado'),
(450, 185, 34, NULL, 65.00, 6.50, 3.25, 0.00, 0.00, NULL, NULL, '2025-07-01 20:30:33', '2025-09-20 20:30:33', 'aprovado'),
(451, 185, 34, NULL, 94.00, 9.40, 4.70, 0.00, 0.00, NULL, NULL, '2025-08-01 20:30:33', '2025-09-20 20:30:33', 'aprovado'),
(452, 185, 34, NULL, 33.00, 3.30, 1.65, 0.00, 0.00, NULL, NULL, '2025-07-03 20:30:33', '2025-09-20 20:30:33', 'aprovado'),
(453, 185, 34, NULL, 81.00, 8.10, 4.05, 0.00, 0.00, NULL, NULL, '2025-09-09 20:30:34', '2025-09-20 20:30:34', 'aprovado'),
(454, 185, 34, NULL, 65.00, 6.50, 3.25, 0.00, 0.00, NULL, NULL, '2025-08-10 20:30:34', '2025-09-20 20:30:34', 'aprovado'),
(455, 185, 34, NULL, 73.00, 7.30, 3.65, 0.00, 0.00, NULL, NULL, '2025-09-15 20:30:34', '2025-09-20 20:30:34', 'aprovado'),
(456, 185, 34, NULL, 61.00, 6.10, 3.05, 0.00, 0.00, NULL, NULL, '2025-08-04 20:30:34', '2025-09-20 20:30:34', 'aprovado'),
(457, 185, 34, NULL, 56.00, 5.60, 2.80, 0.00, 0.00, NULL, NULL, '2025-08-12 20:30:35', '2025-09-20 20:30:35', 'aprovado'),
(458, 185, 34, NULL, 42.00, 4.20, 2.10, 0.00, 0.00, NULL, NULL, '2025-07-30 20:30:35', '2025-09-20 20:30:35', 'aprovado'),
(459, 185, 34, NULL, 144.00, 14.40, 7.20, 0.00, 0.00, NULL, NULL, '2025-09-10 20:30:35', '2025-09-20 20:30:35', 'aprovado'),
(460, 185, 34, NULL, 112.00, 11.20, 5.60, 0.00, 0.00, NULL, NULL, '2025-08-12 20:30:36', '2025-09-20 20:30:36', 'aprovado'),
(461, 185, 34, NULL, 115.00, 11.50, 5.75, 0.00, 0.00, NULL, NULL, '2025-07-03 20:30:36', '2025-09-20 20:30:36', 'aprovado'),
(462, 185, 34, NULL, 47.00, 4.70, 2.35, 0.00, 0.00, NULL, NULL, '2025-07-13 20:30:36', '2025-09-20 20:30:36', 'aprovado'),
(463, 185, 34, NULL, 126.00, 12.60, 6.30, 0.00, 0.00, NULL, NULL, '2025-06-28 20:30:37', '2025-09-20 20:30:37', 'aprovado'),
(464, 185, 34, NULL, 39.00, 3.90, 1.95, 0.00, 0.00, NULL, NULL, '2025-08-18 20:30:37', '2025-09-20 20:30:37', 'aprovado'),
(465, 185, 34, NULL, 107.00, 10.70, 5.35, 0.00, 0.00, NULL, NULL, '2025-09-01 20:30:37', '2025-09-20 20:30:37', 'aprovado'),
(466, 185, 34, NULL, 42.00, 4.20, 2.10, 0.00, 0.00, NULL, NULL, '2025-08-08 20:30:38', '2025-09-20 20:30:38', 'aprovado'),
(467, 185, 34, NULL, 60.00, 6.00, 3.00, 0.00, 0.00, NULL, NULL, '2025-09-10 20:30:38', '2025-09-20 20:30:38', 'aprovado'),
(468, 185, 34, NULL, 54.00, 5.40, 2.70, 0.00, 0.00, NULL, NULL, '2025-09-15 20:30:38', '2025-09-20 20:30:38', 'aprovado'),
(469, 185, 34, NULL, 140.00, 14.00, 7.00, 0.00, 0.00, NULL, NULL, '2025-07-28 20:30:38', '2025-09-20 20:30:38', 'aprovado'),
(470, 185, 34, NULL, 147.00, 14.70, 7.35, 0.00, 0.00, NULL, NULL, '2025-08-12 20:30:39', '2025-09-20 20:30:39', 'aprovado'),
(471, 185, 34, NULL, 147.00, 14.70, 7.35, 0.00, 0.00, NULL, NULL, '2025-07-27 20:30:39', '2025-09-20 20:30:39', 'aprovado'),
(472, 185, 34, NULL, 137.00, 13.70, 6.85, 0.00, 0.00, NULL, NULL, '2025-08-25 20:30:39', '2025-09-20 20:30:39', 'aprovado'),
(473, 185, 34, NULL, 142.00, 14.20, 7.10, 0.00, 0.00, NULL, NULL, '2025-09-10 20:30:40', '2025-09-20 20:30:40', 'aprovado'),
(474, 185, 34, NULL, 149.00, 14.90, 7.45, 0.00, 0.00, NULL, NULL, '2025-07-09 20:30:40', '2025-09-20 20:30:40', 'aprovado'),
(475, 185, 34, NULL, 73.00, 7.30, 3.65, 0.00, 0.00, NULL, NULL, '2025-09-13 20:30:40', '2025-09-20 20:30:40', 'aprovado'),
(476, 185, 34, NULL, 111.00, 11.10, 5.55, 0.00, 0.00, NULL, NULL, '2025-07-10 20:30:41', '2025-09-20 20:30:41', 'aprovado'),
(477, 185, 34, NULL, 66.00, 6.60, 3.30, 0.00, 0.00, NULL, NULL, '2025-09-03 20:30:41', '2025-09-20 20:30:41', 'aprovado'),
(478, 185, 34, NULL, 500.00, 50.00, 25.00, 0.00, 0.00, NULL, NULL, '2025-09-20 20:30:41', '2025-09-20 20:30:41', 'aprovado'),
(479, 9, 59, NULL, 100.00, 8.00, 7.00, 1.00, 0.00, 'KC25092017333453271', '', '2025-09-20 17:24:00', '2025-09-20 20:33:38', 'aprovado'),
(480, 9, 34, NULL, 100.00, 10.00, 5.00, 0.00, 0.00, NULL, NULL, '2025-09-20 20:35:26', '2025-09-20 20:35:26', 'aprovado'),
(481, 9, 34, NULL, 100.00, 10.00, 5.00, 0.00, 0.00, NULL, NULL, '2025-09-20 20:36:01', '2025-09-20 20:36:01', 'aprovado'),
(482, 9, 34, NULL, 100.00, 10.00, 5.00, 0.00, 0.00, NULL, NULL, '2025-09-20 20:36:07', '2025-09-20 20:36:07', 'aprovado'),
(483, 185, 34, NULL, 100.00, 10.00, 5.00, 0.00, 0.00, NULL, NULL, '2025-09-20 20:36:46', '2025-09-20 20:36:46', 'aprovado'),
(484, 185, 34, NULL, 100.00, 10.00, 5.00, 0.00, 0.00, NULL, NULL, '2025-09-20 20:36:48', '2025-09-20 20:36:48', 'aprovado'),
(485, 185, 34, NULL, 100.00, 10.00, 5.00, 0.00, 0.00, NULL, NULL, '2025-09-20 20:37:18', '2025-09-20 20:37:18', 'aprovado'),
(486, 185, 34, NULL, 100.00, 10.00, 5.00, 0.00, 0.00, NULL, NULL, '2025-09-20 20:37:20', '2025-09-20 20:37:20', 'aprovado'),
(487, 185, 34, NULL, 100.00, 10.00, 5.00, 0.00, 0.00, NULL, NULL, '2025-09-20 20:37:44', '2025-09-20 20:37:44', 'aprovado'),
(488, 185, 34, NULL, 100.00, 10.00, 5.00, 0.00, 0.00, NULL, NULL, '2025-09-20 20:52:34', '2025-09-20 20:52:34', 'aprovado'),
(489, 9, 59, NULL, 100.00, 8.00, 7.00, 1.00, 0.00, 'KC25092017580789307', '', '2025-09-20 17:33:00', '2025-09-20 20:58:09', 'aprovado'),
(490, 9, 59, NULL, 120.00, 9.60, 8.40, 1.20, 0.00, 'KC25092018020382205', '', '2025-09-20 17:58:00', '2025-09-20 21:02:05', 'aprovado'),
(491, 162, 59, NULL, 10.00, 0.80, 0.70, 0.10, 0.00, 'KC25092018040669205', '', '2025-09-20 18:02:00', '2025-09-20 21:04:08', 'aprovado'),
(492, 9, 59, NULL, 30.00, 2.40, 2.10, 0.30, 0.00, 'KC25092018153868724', '', '2025-09-20 18:04:00', '2025-09-20 21:15:40', 'aprovado'),
(493, 9, 59, NULL, 10.00, 0.80, 0.70, 0.10, 0.00, 'KC25092018205562020', '', '2025-09-20 18:15:00', '2025-09-20 21:20:58', 'aprovado'),
(494, 9, 59, NULL, 5.00, 0.40, 0.35, 0.05, 0.00, 'KC25092018262598117', '', '2025-09-20 18:20:00', '2025-09-20 21:26:27', 'aprovado'),
(495, 9, 59, NULL, 10.00, 0.80, 0.70, 0.10, 0.00, 'KC25092019244321620', '', '2025-09-20 19:24:00', '2025-09-20 22:24:44', 'aprovado'),
(496, 9, 34, NULL, 10.00, 1.00, 0.50, 0.50, 0.00, 'KC25092019252505954', '', '2025-09-20 19:25:00', '2025-09-20 22:25:27', 'aprovado'),
(497, 9, 59, NULL, 10.00, 0.80, 0.70, 0.10, 0.00, 'KC25092019330204471', '', '2025-09-20 19:24:00', '2025-09-20 22:33:04', 'aprovado'),
(498, 9, 59, NULL, 100.00, 8.00, 7.00, 1.00, 0.00, 'KC25092019385359159', '', '2025-09-20 19:38:00', '2025-09-20 22:38:55', 'aprovado'),
(499, 9, 34, NULL, 20.00, 2.00, 1.00, 1.00, 0.00, 'KC25092019404559197', '', '2025-09-20 19:40:00', '2025-09-20 22:40:47', 'aprovado'),
(500, 9, 59, NULL, 20.00, 1.60, 1.40, 0.20, 0.00, 'KC25092019505029714', '', '2025-09-20 19:50:00', '2025-09-20 22:50:51', 'aprovado'),
(501, 9, 59, NULL, 10.00, 0.80, 0.70, 0.10, 0.00, 'KC25092019543428478', '', '2025-09-20 19:50:00', '2025-09-20 22:54:36', 'aprovado'),
(502, 9, 59, NULL, 10.00, 0.80, 0.70, 0.10, 0.00, 'KC25092020281827847', '', '2025-09-20 19:54:00', '2025-09-20 23:28:22', 'aprovado'),
(503, 9, 59, NULL, 5.00, 0.40, 0.35, 0.05, 0.00, 'KC25092020354955166', '', '2025-09-20 20:35:00', '2025-09-20 23:35:51', 'aprovado'),
(504, 9, 59, NULL, 20.00, 1.60, 1.40, 0.20, 0.00, 'KC25092020402709755', '', '2025-09-20 20:40:00', '2025-09-20 23:40:32', 'aprovado');

-- --------------------------------------------------------

--
-- Estrutura para tabela `transacoes_comissao`
--

CREATE TABLE `transacoes_comissao` (
  `id` int(11) NOT NULL,
  `tipo_usuario` enum('admin','loja') NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `loja_id` int(11) NOT NULL,
  `transacao_id` int(11) NOT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `valor_comissao` decimal(10,2) NOT NULL,
  `data_transacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pendente','aprovado','cancelado') DEFAULT 'pendente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `transacoes_saldo_usado`
--

CREATE TABLE `transacoes_saldo_usado` (
  `id` int(11) NOT NULL,
  `transacao_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `loja_id` int(11) NOT NULL,
  `valor_usado` decimal(10,2) NOT NULL,
  `data_uso` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `transacoes_status_historico`
--

CREATE TABLE `transacoes_status_historico` (
  `id` int(11) NOT NULL,
  `transacao_id` int(11) NOT NULL,
  `status_anterior` enum('pendente','aprovado','cancelado') NOT NULL,
  `status_novo` enum('pendente','aprovado','cancelado') NOT NULL,
  `observacao` text DEFAULT NULL,
  `data_alteracao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `senha_hash` varchar(255) DEFAULT NULL,
  `data_criacao` timestamp NULL DEFAULT current_timestamp(),
  `ultimo_login` timestamp NULL DEFAULT NULL,
  `status` enum('ativo','inativo','bloqueado') DEFAULT 'ativo',
  `tipo` enum('cliente','admin','loja','funcionario') DEFAULT 'cliente',
  `senat` enum('Sim','Não') DEFAULT 'Não',
  `tipo_cliente` enum('completo','visitante') DEFAULT 'completo',
  `loja_criadora_id` int(11) DEFAULT NULL,
  `google_id` varchar(50) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `provider` enum('local','google') DEFAULT 'local',
  `email_verified` tinyint(1) DEFAULT 0,
  `two_factor_enabled` tinyint(1) DEFAULT 0,
  `two_factor_code` varchar(6) DEFAULT NULL,
  `two_factor_expires` datetime DEFAULT NULL,
  `two_factor_verified` tinyint(1) DEFAULT 0,
  `tentativas_2fa` int(11) DEFAULT 0,
  `bloqueado_2fa_ate` timestamp NULL DEFAULT NULL,
  `ultimo_2fa_enviado` timestamp NULL DEFAULT NULL,
  `loja_vinculada_id` int(11) DEFAULT NULL,
  `subtipo_funcionario` enum('funcionario','gerente','coordenador','assistente','financeiro','vendedor') DEFAULT 'funcionario' COMMENT 'Campo apenas para organização interna - não afeta permissões',
  `mvp` enum('sim','nao') DEFAULT 'nao'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `telefone`, `cpf`, `senha_hash`, `data_criacao`, `ultimo_login`, `status`, `tipo`, `senat`, `tipo_cliente`, `loja_criadora_id`, `google_id`, `avatar_url`, `provider`, `email_verified`, `two_factor_enabled`, `two_factor_code`, `two_factor_expires`, `two_factor_verified`, `tentativas_2fa`, `bloqueado_2fa_ate`, `ultimo_2fa_enviado`, `loja_vinculada_id`, `subtipo_funcionario`, `mvp`) VALUES
(9, 'Kaua Matheus da Silva Lope', 'kauamatheus920@gmail.com', '38991045205', '15692134616', '$2y$10$ZBHPPEjv69ihoxjJatuJZefND4d0UNGpzK.UG1fji3BeETLymm7eu', '2025-05-05 19:45:04', '2025-09-16 09:19:43', 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(10, 'Frederico', 'repertoriofredericofagundes@gmail.com', NULL, NULL, '$2y$10$yGjHS8rJq49AuLeuVrZHkOUPSkzNLs79A6H52HwwY8DpzLA2A95Ay', '2025-05-05 21:45:46', '2025-09-15 18:30:09', 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(11, 'Kaua Lopés', 'kaua@klubecash.com', NULL, NULL, '$2y$10$3cp74UJto1IK9R4f8wx.su3HR.SdXKPLotS4OLck7BxMLOhuJMtHq', '2025-05-07 12:19:05', '2025-09-18 21:35:31', 'ativo', 'admin', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(55, 'Matheus', 'kauamathes123487654@gmail.com', '(38) 99104-5205', NULL, '$2y$10$VwSfpE6zvr72HI19RLFLF.Dw4VKMjbGajc5l6mN3jQiaoHK1GUR0u', '2025-05-25 19:17:34', '2025-09-20 22:25:14', 'ativo', 'loja', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(61, 'Frederico Fagundes', 'fredericofagundes0@gmail.com', NULL, NULL, '$2y$10$Lcszebxu3vPCg4dNkDhP7eAvk07mvjEvFLNz4pFYdMveo0skeNFWi', '2025-06-05 17:48:45', '2025-09-20 02:13:41', 'ativo', 'admin', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(63, 'KLUBE DIGITAL', 'acessoriafredericofagundes@gmail.com', '(34) 99335-7697', NULL, '$2y$10$VuDfT8bieSTLToSbmd3EzOVkmwNLYeC9itIfm2kxl3f54OpnZpd5O', '2025-06-07 16:11:42', '2025-09-20 02:12:05', 'ativo', 'loja', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'sim'),
(71, 'Roberto Magalhães Corrêa ', 'ropatosmg@gmail.com', '5534993171602', NULL, '$2y$10$77e0qthXH0AJkZFGJR0APu9fifxY/M8BvkNOGrHMBMBmAv7W3SohO', '2025-06-10 00:08:12', '2025-06-10 00:08:51', 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(72, 'Sabrina', 'sabrina290623@gmail.com', '(34) 99842-3591', NULL, '$2y$10$1FNgzRYI0AbiCYymdAgBlOWe2uIJn.PwU24.AUe3UP7pf5bA1ImJO', '2025-06-10 00:11:51', '2025-06-10 00:12:00', 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(73, 'Frederico Fagundes', 'klubecash@gmail.com', '(34) 99335-7697', NULL, '$2y$10$cM0f9co4abNHzxiOD0ZgjuZchVNk9o3v6mOadv2aByV.s339xdTPu', '2025-06-10 00:14:24', '2025-09-03 18:12:22', 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(74, 'Amanda rosa ', 'aricken31@gmail.com', '(34) 99975-8423', NULL, '$2y$10$aV.0Wj3E2dMRHSX7KqHa9u0.LsHiHDdBEpD/yOzCB.QC4uFcu72/K', '2025-06-10 00:15:41', NULL, 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(75, 'Felipe Vieira ribeiro', 'ribeirofilepe34@gmail.com', '(34) 99712-8998', NULL, '$2y$10$MpCAnHh7GN8ToE7b3FGzcurkrl8TA4Ffm69NECs0ePdMJcuvW0iNC', '2025-06-10 00:40:43', '2025-08-30 20:41:38', 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(76, 'Gabriela Steffany da Silva ', 'gabisteffany@icloud.com', '(34) 98700-3621', NULL, '$2y$10$eFewesljEaKuqWpeFRnuy.Xh/FJ4sXLz8thior8hzQUytyrDisYay', '2025-06-10 00:41:33', '2025-06-10 00:45:29', 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(77, 'Bruna Leal Ribeiro', 'brunna.leal00@gmail.com', '(34) 99982-8286', NULL, '$2y$10$Og4FZ3ealFiMAvj2gAIR0etd35frBRFNz/0CoefkAOqXkjOK/0ZLy', '2025-06-10 00:41:56', '2025-06-10 00:42:07', 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(78, 'Gabriele Soares Souza ', 'soaresgabriele25@gmail.com', '(34) 99960-8386', NULL, '$2y$10$BgfPzZTWZ4Qa412NtFZQQ.QAoO9k8Y5G.GFiaLvBIqX5rbUt99sfG', '2025-06-10 02:24:49', '2025-06-10 02:25:03', 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(79, 'Pedro Henrique Duarte ', 'pedrohduarte98@gmail.com', '5534998437197', NULL, '$2y$10$CSUkXDPCL6rdd2cMhEhPKO0dq.D7ioZ9ywNef8wf0CFcBDufwgBeu', '2025-06-10 05:22:24', '2025-06-10 05:22:59', 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(80, 'Pirapora', 'kaualopes@unipam.edu.br', '(38) 99104-5205', NULL, '$2y$10$VOJ.OE4rGXEWrq55slY41uz0POqQ2ZCph71mpaW9C3gIdoF38TXcm', '2025-06-10 18:44:22', NULL, 'ativo', 'loja', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(81, 'Lucas Fagundes da Silva ', 'lucasfagundes934@gmail.com', '(34) 99218-9099', NULL, '$2y$10$obpHzgu/lTbA9BLIWsz8yebeD3rroMp9cW.Xy/MxbW8A7mOom9ox2', '2025-06-11 19:29:20', '2025-06-13 00:57:44', 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(86, 'Jennifer aryane ', 'jenniferlimaxz@gmail.com', '(55) 98497-1703', NULL, '$2y$10$Qeai.iOuOCYSrTMmFm7b1OE4WeHvgzmem4SLeJGa20bvjJJGzhZYG', '2025-07-07 17:05:39', NULL, 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(87, 'Jennifer aryane ', 'jenniferlopesxz@gmail.com', '(55) 98497-1703', NULL, '$2y$10$FxTmg8XDk50WOKlUAZzaeOAF.sPVIgcZHyryCUlZMern1Hy363CFO', '2025-07-07 17:06:49', NULL, 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(88, 'Rafael Augusto Alves Silva ', 'rafaelaugustoalvessilva5@gmail.com', '(34) 99665-7725', NULL, '$2y$10$B8CcTlZLjn2swhyPXdjnQeq3sl5.j6nnyVbqkL9wwzkcM.ulaFBwW', '2025-07-07 18:28:49', '2025-07-07 22:36:48', 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(111, 'Ana Caroliny Ferreira De Almeida ', 'anacarolinyferreiradealmeida5@gmail.com', '(11) 97880-6283', NULL, '$2y$10$di3MoK7n.I9v3S3UN.xF6.qQX4w.BlqxfDl7cEGjCJElaAyNEYFM6', '2025-07-16 03:14:07', NULL, 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(118, 'Clarissa', 'clarissalopes296@gmail.com', '(38) 99104-5205', NULL, '$2y$10$g/2OVjHI54UuC4zbBiiNSuFk.3UIJtQbSG1hoEb/pxnIlNQwQk6UO', '2025-07-22 21:39:03', '2025-07-26 19:28:54', 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(121, 'Kaua', '', '38991045003', NULL, NULL, '2025-08-13 22:05:06', NULL, 'ativo', 'cliente', 'Não', 'visitante', 34, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(134, 'KAua', 'visitante_38991045004_loja_34@klubecash.local', '38991045004', NULL, NULL, '2025-08-14 01:50:58', NULL, 'ativo', 'cliente', 'Não', 'visitante', 34, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(135, 'Kaua Lopéscd', 'visitante_11450807392_loja_34@klubecash.local', '11450807392', NULL, NULL, '2025-08-14 02:04:38', NULL, 'ativo', 'cliente', 'Não', 'visitante', 34, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(137, 'Teste Corrigido 23:09:03', 'visitante_11233143249_loja_34@klubecash.local', '11233143249', NULL, NULL, '2025-08-14 02:09:03', NULL, 'ativo', 'cliente', 'Não', 'visitante', 34, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(138, 'João Teste', 'visitante_11987654321_loja_34@klubecash.local', '11987654321', NULL, NULL, '2025-08-14 02:18:32', NULL, 'ativo', 'cliente', 'Não', 'visitante', 34, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(139, 'Cecilia', 'visitante_34991191534_loja_34@klubecash.local', '34991191534', NULL, NULL, '2025-08-14 02:21:26', NULL, 'ativo', 'cliente', 'Não', 'visitante', 34, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(140, 'Frederico', 'visitante_34993357697_loja_34@klubecash.local', '34993357697', NULL, NULL, '2025-08-14 02:27:29', NULL, 'ativo', 'cliente', 'Não', 'visitante', 34, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(141, 'Jaqueline maria ', 'sousalima20189@gmail.com', '(34) 99771-3760', NULL, '$2y$10$t3FvhtIQs/Z8azhQl6WUbeubrf1Rj5J15B8Fh6KW4OKC2jHrQNRla', '2025-08-14 07:07:11', '2025-08-14 07:07:29', 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(142, 'Frederico Fagundes', 'visitante_34993357697_loja_38@klubecash.local', '34993357697', NULL, NULL, '2025-08-14 07:31:17', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(143, 'jean junior', 'visitante_34992708603_loja_38@klubecash.local', '34992708603', NULL, NULL, '2025-08-14 08:46:55', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(144, 'roberto magalhaes', 'visitante_34993171602_loja_38@klubecash.local', '34993171602', NULL, NULL, '2025-08-14 09:10:45', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(145, 'Frederico Fagundes', 'visitante_3497635735_loja_38@klubecash.local', '3497635735', NULL, NULL, '2025-08-14 13:54:43', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(146, 'Kamilla', 'visitante_34988247844_loja_38@klubecash.local', '34988247844', NULL, NULL, '2025-08-14 15:03:01', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(147, 'Fábio Eduardo', 'visitante_34992369765_loja_38@klubecash.local', '34992369765', NULL, NULL, '2025-08-14 15:13:32', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(148, 'Frederico', 'visitante_34993357698_loja_38@klubecash.local', '34993357698', NULL, NULL, '2025-08-14 17:11:46', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(149, 'giovanna moreira', 'visitante_34963466409_loja_38@klubecash.local', '34963466409', NULL, NULL, '2025-08-14 18:46:25', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(150, 'GUIUGAO', 'visitante_34996346409_loja_38@klubecash.local', '34996346409', NULL, NULL, '2025-08-14 18:49:50', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(151, 'Ana Livia', 'visitante_34998176771_loja_38@klubecash.local', '34998176771', NULL, NULL, '2025-08-14 19:47:25', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(152, 'Alessandra Regis', 'visitante_34991927053_loja_38@klubecash.local', '34991927053', NULL, NULL, '2025-08-14 19:50:17', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(153, 'Cleides felix', 'visitante_38998693037_loja_38@klubecash.local', '38998693037', NULL, NULL, '2025-08-14 19:53:57', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(154, 'Aurélia Cristina', 'visitante_34998721675_loja_38@klubecash.local', '34998721675', NULL, NULL, '2025-08-14 19:57:57', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(155, 'Bruna leal', 'visitante_34999828286_loja_38@klubecash.local', '34999828286', NULL, NULL, '2025-08-14 20:09:40', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(156, 'Vitória Filipa', 'visitante_55349972501_loja_38@klubecash.local', '55349972501', NULL, NULL, '2025-08-14 20:13:31', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(157, 'Pyetro swanson', 'visitante_34991251830_loja_38@klubecash.local', '34991251830', NULL, NULL, '2025-08-14 20:15:45', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(158, 'Carla Gonçalves', 'visitante_34998966741_loja_38@klubecash.local', '34998966741', NULL, NULL, '2025-08-15 01:02:50', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(159, 'Sync Holding', 'kaua@syncholding.com.br', '(34) 99800-2600', '04355521630', '$2y$10$W4Mw0j5/DhS.p0/I.D0he.aekBeq.O9.5xVoS8wntjF4L3U3P6OPW', '2025-08-15 13:52:55', '2025-09-20 23:51:32', 'ativo', 'loja', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'sim'),
(160, 'Cecilia', 'visitante_34991191534_loja_59@klubecash.local', '34991191534', NULL, NULL, '2025-08-15 14:47:55', NULL, 'ativo', 'cliente', 'Não', 'visitante', 59, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(161, 'Evaldo Gabriel', 'visitante_34991247963_loja_38@klubecash.local', '34991247963', NULL, NULL, '2025-08-15 17:02:42', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(162, 'Cecilia 3', 'visitante_34998002600_loja_59@klubecash.local', '34998002600', NULL, NULL, '2025-08-15 19:30:55', NULL, 'ativo', 'cliente', 'Não', 'visitante', 59, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(163, 'maria versiani', 'visitante_34997201631_loja_38@klubecash.local', '34997201631', NULL, NULL, '2025-08-16 16:53:41', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(164, 'Laisla Fagundes', 'visitante_55349963106_loja_38@klubecash.local', '55349963106', NULL, NULL, '2025-08-16 16:57:25', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(165, 'Laisla Fagundes', 'visitante_34996310606_loja_38@klubecash.local', '34996310606', NULL, NULL, '2025-08-16 16:58:42', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(166, 'Luh Duarte', 'visitante_34999908465_loja_38@klubecash.local', '34999908465', NULL, NULL, '2025-08-16 17:17:59', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(167, 'Ellen Monteiro', 'visitante_34992244799_loja_38@klubecash.local', '34992244799', NULL, NULL, '2025-08-18 16:53:57', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(168, 'Felipe Vieira', 'visitante_34997128998_loja_38@klubecash.local', '34997128998', NULL, NULL, '2025-08-21 20:03:48', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(169, 'Renato', 'visitante_34999975070_loja_38@klubecash.local', '34999975070', NULL, NULL, '2025-08-24 17:11:25', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(170, 'Hellen Mendes', 'visitante_34993354890_loja_38@klubecash.local', '34993354890', NULL, NULL, '2025-08-28 17:06:40', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(171, 'Hellen Mendes', 'visitante_34999354890_loja_38@klubecash.local', '34999354890', NULL, NULL, '2025-08-28 17:08:54', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(172, 'Ângela', 'visitante_34992172404_loja_38@klubecash.local', '34992172404', NULL, NULL, '2025-08-29 17:08:13', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(173, 'ELITE SEMIJOIAS MOZAR FRANCISCO LUIZ ME', 'elitesemijoiaspatosdeminas@gmail.com', '(34) 99217-2404', NULL, '$2y$10$ZuWSVnYfMCez78BDAjwgwe2pS4jGGI5TKjSS2qyloKQaArA5CazI6', '2025-08-29 17:22:01', NULL, 'ativo', 'loja', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'sim'),
(174, 'Vinicius Pais', 'visitante_11999841933_loja_34@klubecash.local', '11999841933', NULL, NULL, '2025-09-13 11:29:29', NULL, 'ativo', 'cliente', 'Não', 'visitante', 34, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(175, 'Digo.com', 'digovarejo@gmail.com', '(11) 97088-3167', NULL, '$2y$10$EfdYf7wQTFzcnydTwwVHD.z1FJRU4582k4v/oQVgwsEvpFRw3bNla', '2025-09-13 14:49:08', NULL, 'ativo', 'loja', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'sim'),
(177, 'RSF SONHO DE NOIVA EVENTOS E NEGOCIOS LTDA', 'cleacasamentos@gmail.com', '(85) 99632-4231', NULL, '$2y$10$cTaW4e9BBcO8OKGdOJ.WpeJN/g194QfJ259i3KuBP7i3.yxABtyia', '2025-09-14 19:47:52', '2025-09-15 01:46:46', 'ativo', 'loja', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(180, 'Ricardo da Silva Facundo', 'visitante_85982334146_loja_59@klubecash.local', '85982334146', NULL, NULL, '2025-09-15 01:37:26', NULL, 'ativo', 'cliente', 'Não', 'visitante', 59, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(181, 'maria joaquina', 'visitante_3499654789_loja_38@klubecash.local', '3499654789', NULL, NULL, '2025-09-15 18:32:26', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(183, 'Kaua Lopes tetse', 'visitante_33991045205_loja_59@klubecash.local', '33991045205', NULL, NULL, '2025-09-16 15:18:05', NULL, 'ativo', 'cliente', 'Não', 'visitante', 59, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(184, 'Emanuel Caetano', 'visitante_33987063966_loja_38@klubecash.local', '33987063966', NULL, NULL, '2025-09-19 18:35:46', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(185, 'Teste WhatsApp', 'teste@klubecash.com', '5538991045205', NULL, '$2y$10$CrbhTxuc9U.fwdTH2F0el.Tr8i6gzKE2Fg.q58tZAWX/gZe0h/ygG', '2025-09-20 20:10:30', NULL, 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(186, 'João Primeiro', 'joão.primeiro@teste.com', '5538991045201', NULL, '$2y$10$dmWTnNzPfPAwoZmHIc9eLulthgcNJ4PRs8e5/yDBqQ/FljbgoX4km', '2025-09-20 20:30:15', NULL, 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(187, 'Maria Regular', 'maria.regular@teste.com', '5538991045202', NULL, '$2y$10$ozcgNJjVPZGxCTDnUjto6..4A90UXc7P84zQ.4g/MA6ii1GJtRGJC', '2025-09-20 20:30:17', NULL, 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(188, 'Carlos VIP Silva', 'carlos.vip.silva@teste.com', '5538991045203', NULL, '$2y$10$b.Yk5L3aBI2aGGxXluJG4OOsrpYAh8Gk34oOddlss4Q52Yw/4RCau', '2025-09-20 20:30:20', NULL, 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(189, 'Ana Compradora', 'ana.compradora@teste.com', '5538991045204', NULL, '$2y$10$ZGiDUKvOoNGItJeT59V52Oj4FnAMWXMLP6ha6/QcIQ/MPpzm3lq8e', '2025-09-20 20:30:29', NULL, 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios_contato`
--

CREATE TABLE `usuarios_contato` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `celular` varchar(20) DEFAULT NULL,
  `email_alternativo` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios_contato`
--

INSERT INTO `usuarios_contato` (`id`, `usuario_id`, `telefone`, `celular`, `email_alternativo`) VALUES
(10, 9, '(38) 9842-23205', '(34) 99800-2600', 'kauanupix@gmail.com');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios_endereco`
--

CREATE TABLE `usuarios_endereco` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `logradouro` varchar(255) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(50) DEFAULT NULL,
  `principal` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios_endereco`
--

INSERT INTO `usuarios_endereco` (`id`, `usuario_id`, `cep`, `logradouro`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `principal`) VALUES
(5, 9, '38705-376', 'Francisco Braga da Mota', '146', 'Ap 101', 'Jd Panoramico', 'Patos de minas', 'MG', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `verificacao_2fa`
--

CREATE TABLE `verificacao_2fa` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `codigo` varchar(6) NOT NULL,
  `data_criacao` timestamp NULL DEFAULT current_timestamp(),
  `data_expiracao` timestamp NOT NULL,
  `usado` tinyint(1) DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `webhook_errors`
--

CREATE TABLE `webhook_errors` (
  `id` int(11) NOT NULL,
  `mp_payment_id` varchar(255) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `payload` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `resolved` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `whatsapp_cadastro_sessions`
--

CREATE TABLE `whatsapp_cadastro_sessions` (
  `id` int(11) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `whatsapp_cadastro_sessions`
--

INSERT INTO `whatsapp_cadastro_sessions` (`id`, `phone`, `user_id`, `state`, `data`, `created_at`, `updated_at`, `expires_at`) VALUES
(1, '34991191534', 139, 'aguardando_email', '{\"nome\":\"Cecilia\"}', '2025-08-16 14:20:17', '2025-08-16 14:29:09', '2025-08-16 11:39:09');

-- --------------------------------------------------------

--
-- Estrutura para tabela `whatsapp_logs`
--

CREATE TABLE `whatsapp_logs` (
  `id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `message_preview` text DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `message_id` varchar(100) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `simulation_mode` tinyint(1) NOT NULL DEFAULT 0,
  `additional_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_data`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `whatsapp_logs`
--

INSERT INTO `whatsapp_logs` (`id`, `type`, `phone`, `message_preview`, `success`, `message_id`, `error_message`, `simulation_mode`, `additional_data`, `ip_address`, `user_agent`, `created_at`) VALUES
(33, 'manual_send', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, 'sim_6855fbaa912a1', NULL, 1, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 21:24:10'),
(34, 'nova_transacao', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, 'sim_6855fbaa912a1', NULL, 1, '{\"valor_cashback\":2.5,\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 21:24:10'),
(35, 'manual_send', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, 'sim_6855fbab9162a', NULL, 1, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 21:24:11'),
(36, 'cashback_liberado', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, 'sim_6855fbab9162a', NULL, 1, '{\"valor_cashback\":2.5,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 21:24:11'),
(37, 'manual_send', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 0,25* da loja *Kaua Matheus da Silva Lop', 1, 'sim_6855fe566f66f', NULL, 1, '[]', '54.88.218.97', 'MercadoPago WebHook v1.0 payment', '2025-06-20 21:35:34'),
(38, 'cashback_liberado', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 0,25* da loja *Kaua Matheus da Silva Lop', 1, 'sim_6855fe566f66f', NULL, 1, '{\"valor_cashback\":\"0.25\",\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '54.88.218.97', 'MercadoPago WebHook v1.0 payment', '2025-06-20 21:35:34'),
(39, 'manual_send', '34999999999', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_685601fbd900d', NULL, 1, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 21:51:07'),
(40, 'manual_send', '34999999999', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_685601fc62f39', NULL, 1, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 21:51:08'),
(41, 'manual_send', '34999999999', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_6856020173177', NULL, 1, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 21:51:13'),
(42, 'manual_send', '34999999999', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_685602058864d', NULL, 1, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 21:51:17'),
(43, 'manual_send', '34999999999', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_685602067018c', NULL, 1, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 21:51:18'),
(44, 'manual_send', '34999999999', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_6856020713259', NULL, 1, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 21:51:19'),
(45, 'manual_send', '34999999999', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_68560209ce8f8', NULL, 1, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 21:51:21'),
(46, 'manual_send', '34999999999', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_6856020e4ab6f', NULL, 1, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 21:51:26'),
(47, 'manual_send', '34999999999', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_6856020ea2a20', NULL, 1, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 21:51:26'),
(48, 'manual_send', '34999999999', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_685602118bafa', NULL, 1, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 21:51:29'),
(49, 'manual_send', '34999999999', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_685602ec87568', NULL, 1, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 21:55:08'),
(50, 'manual_send', '34999999999', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_685602ee39506', NULL, 1, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 21:55:10'),
(51, 'manual_send', '34999999999', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_685605408ebc9', NULL, 1, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 22:05:04'),
(52, 'manual_send', '34999999999', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_685605ac8995a', NULL, 1, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 22:06:52'),
(53, 'manual_send', '34999999999', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_68560621afa95', NULL, 1, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 22:08:49'),
(54, 'manual_send', '34999999999', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_6856066f982cd', NULL, 1, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 22:10:07'),
(55, 'manual_send', '34999999999', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_685606c265f1c', NULL, 1, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 22:11:30'),
(56, 'manual_send', '34999999999', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro desconhecido', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 22:15:09'),
(57, 'manual_send', '34999999999', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro desconhecido', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 22:16:00'),
(58, 'manual_send', '34999999999', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro desconhecido', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 22:16:21'),
(59, 'manual_send', '34999999999', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro desconhecido', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 22:16:23'),
(60, 'manual_send', '34999999999', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro desconhecido', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 22:16:56'),
(61, 'manual_send', '34999999999', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro desconhecido', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 22:17:11'),
(62, 'manual_send', '34999999999', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro desconhecido', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 22:17:12'),
(63, 'manual_send', '34999999999', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro desconhecido', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 22:18:10'),
(64, 'manual_send', '34999999999', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro desconhecido', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 22:18:12'),
(65, 'manual_send', '38991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Número 38991045205 não possui WhatsApp', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 22:18:18'),
(66, 'manual_send', '38991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Número 38991045205 não possui WhatsApp', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 22:18:21'),
(67, 'manual_send', '38991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Número 38991045205 não possui WhatsApp', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 22:18:46'),
(68, 'manual_send', '38991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Número 38991045205 não possui WhatsApp', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 22:18:48'),
(69, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Número 5538991045205 não possui WhatsApp', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 22:18:51'),
(70, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Número 5538991045205 não possui WhatsApp', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 22:19:17'),
(71, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Número 5538991045205 não possui WhatsApp', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 22:19:19'),
(72, 'manual_send', '3891045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Número 3891045205 não possui WhatsApp', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 22:19:21'),
(73, 'manual_send', '3891045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Número 3891045205 não possui WhatsApp', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 22:19:22'),
(74, 'manual_send', '3891045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Número 3891045205 não possui WhatsApp', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 22:19:23'),
(75, 'manual_send', '3891045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Número 3891045205 não possui WhatsApp', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 22:19:26'),
(76, 'manual_send', '38991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Número 38991045205 não possui WhatsApp', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 22:21:54'),
(77, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Número 5538991045205 não possui WhatsApp', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-20 22:24:45'),
(78, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Número 5538991045205 não possui WhatsApp', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 22:26:08'),
(79, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Número 5538991045205 não possui WhatsApp', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 22:26:10'),
(80, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Número 5538991045205 não possui WhatsApp', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 22:26:12'),
(81, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 22:28:02'),
(82, 'manual_send', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 22:29:11'),
(83, 'nova_transacao', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":5.5,\"valor_usado\":2.3,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 22:29:11'),
(84, 'manual_send', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 8,75* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 22:29:15'),
(85, 'cashback_liberado', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 8,75* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '{\"valor_cashback\":8.75,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 22:29:15'),
(86, 'manual_send', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 22:31:27'),
(87, 'nova_transacao', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":5.5,\"valor_usado\":2.3,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 22:31:27'),
(88, 'manual_send', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 8,75* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 22:31:28'),
(89, 'cashback_liberado', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 8,75* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '{\"valor_cashback\":8.75,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 22:31:28'),
(90, 'manual_send', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 22:31:49'),
(91, 'nova_transacao', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 22:31:49'),
(92, 'manual_send', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 22:31:51'),
(93, 'cashback_liberado', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 22:31:51'),
(94, 'manual_send', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 0,25* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '[]', '18.215.140.160', 'MercadoPago WebHook v1.0 payment', '2025-06-20 22:33:14'),
(95, 'cashback_liberado', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 0,25* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '{\"valor_cashback\":\"0.25\",\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '18.215.140.160', 'MercadoPago WebHook v1.0 payment', '2025-06-20 22:33:14'),
(96, 'manual_send', '34993357697', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 0,25* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '[]', '18.213.114.129', 'MercadoPago WebHook v1.0 payment', '2025-06-20 22:36:08'),
(97, 'cashback_liberado', '34993357697', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 0,25* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '{\"valor_cashback\":\"0.25\",\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '18.213.114.129', 'MercadoPago WebHook v1.0 payment', '2025-06-20 22:36:08'),
(98, 'manual_send', '34991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 0,30* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '[]', '54.88.218.97', 'MercadoPago WebHook v1.0 payment', '2025-06-20 22:37:30'),
(99, 'cashback_liberado', '34991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 0,30* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '{\"valor_cashback\":\"0.30\",\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '54.88.218.97', 'MercadoPago WebHook v1.0 payment', '2025-06-20 22:37:30'),
(100, 'manual_send', '34991191534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 22:47:08'),
(101, 'nova_transacao', '34991191534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 22:47:08'),
(102, 'manual_send', '34991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 22:47:11'),
(103, 'cashback_liberado', '34991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 22:47:11'),
(104, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 22:47:14'),
(105, 'manual_send', '34991191534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 22:48:21'),
(106, 'nova_transacao', '34991191534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 22:48:21'),
(107, 'manual_send', '34991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 22:48:24'),
(108, 'cashback_liberado', '34991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 22:48:24'),
(109, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 23:48:14'),
(110, 'manual_send', '34991191534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 23:48:26'),
(111, 'nova_transacao', '34991191534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 23:48:26'),
(112, 'manual_send', '34991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 23:48:28'),
(113, 'cashback_liberado', '34991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 23:48:28'),
(114, 'manual_send', '34991191534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 23:48:31'),
(115, 'nova_transacao', '34991191534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 23:48:31'),
(116, 'manual_send', '34991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 23:48:33'),
(117, 'cashback_liberado', '34991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-20 23:48:33'),
(118, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:09:02'),
(119, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:09:35'),
(120, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:09:57'),
(121, 'manual_send', '(34)991191534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:10:02'),
(122, 'nova_transacao', '(34)991191534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:10:02'),
(123, 'manual_send', '(34)991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:10:04'),
(124, 'cashback_liberado', '(34)991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:10:04'),
(125, 'manual_send', '(34)991191534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:10:10'),
(126, 'nova_transacao', '(34)991191534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:10:10'),
(127, 'manual_send', '(34)991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:10:12'),
(128, 'cashback_liberado', '(34)991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:10:12'),
(129, 'manual_send', '(34)991191534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:10:14'),
(130, 'nova_transacao', '(34)991191534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:10:14'),
(131, 'manual_send', '(34)991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:10:17'),
(132, 'cashback_liberado', '(34)991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:10:17'),
(133, 'manual_send', '(34)991191534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:10:46'),
(134, 'nova_transacao', '(34)991191534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:10:46'),
(135, 'manual_send', '(34)991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:10:48'),
(136, 'cashback_liberado', '(34)991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:10:48'),
(137, 'manual_send', '+55(34)991191534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:11:11'),
(138, 'nova_transacao', '+55(34)991191534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:11:11'),
(139, 'manual_send', '+55(34)991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:11:13'),
(140, 'cashback_liberado', '+55(34)991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:11:13'),
(141, 'manual_send', '+55(34)99119153', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:11:28'),
(142, 'nova_transacao', '+55(34)99119153', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:11:28'),
(143, 'manual_send', '+55(34)99119153', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:11:30'),
(144, 'cashback_liberado', '+55(34)99119153', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:11:30'),
(145, 'manual_send', '+55(34)99119153', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:11:38'),
(146, 'nova_transacao', '+55(34)99119153', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:11:38'),
(147, 'manual_send', '+55(34)99119153', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:11:40'),
(148, 'cashback_liberado', '+55(34)99119153', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:11:40'),
(149, 'manual_send', '(34)99119-1534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:12:25'),
(150, 'nova_transacao', '(34)99119-1534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:12:25'),
(151, 'manual_send', '(34)99119-1534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:12:27'),
(152, 'cashback_liberado', '(34)99119-1534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:12:27'),
(153, 'manual_send', '(34)99119-1534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:12:32'),
(154, 'nova_transacao', '(34)99119-1534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:12:32'),
(155, 'manual_send', '(34)99119-1534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:12:34'),
(156, 'cashback_liberado', '(34)99119-1534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:12:34'),
(157, 'manual_send', '(34)99119-1534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 0, NULL, 'Erro HTTP: 400', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:19:48'),
(158, 'nova_transacao', '(34)99119-1534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 0, NULL, 'Erro HTTP: 400', 0, '{\"valor_cashback\":2.5,\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:19:48'),
(159, 'manual_send', '(34)99119-1534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro HTTP: 400', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:19:49'),
(160, 'cashback_liberado', '(34)99119-1534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro HTTP: 400', 0, '{\"valor_cashback\":2.5,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:19:49'),
(161, 'manual_send', '(34)99119-1534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 0, NULL, 'Erro HTTP: 400', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:20:20'),
(162, 'nova_transacao', '(34)99119-1534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 0, NULL, 'Erro HTTP: 400', 0, '{\"valor_cashback\":2.5,\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:20:20'),
(163, 'manual_send', '(34)99119-1534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro HTTP: 400', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:20:22');
INSERT INTO `whatsapp_logs` (`id`, `type`, `phone`, `message_preview`, `success`, `message_id`, `error_message`, `simulation_mode`, `additional_data`, `ip_address`, `user_agent`, `created_at`) VALUES
(164, 'cashback_liberado', '(34)99119-1534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro HTTP: 400', 0, '{\"valor_cashback\":2.5,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:20:22'),
(165, 'manual_send', '(34)99119-1534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 0, NULL, 'Erro HTTP: 400', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:20:29'),
(166, 'nova_transacao', '(34)99119-1534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 0, NULL, 'Erro HTTP: 400', 0, '{\"valor_cashback\":2.5,\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:20:29'),
(167, 'manual_send', '(34)99119-1534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro HTTP: 400', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:20:31'),
(168, 'cashback_liberado', '(34)99119-1534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro HTTP: 400', 0, '{\"valor_cashback\":2.5,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:20:31'),
(169, 'manual_send', '(34)99119-1534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 0, NULL, 'Erro HTTP: 400', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:20:59'),
(170, 'nova_transacao', '(34)99119-1534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 0, NULL, 'Erro HTTP: 400', 0, '{\"valor_cashback\":2.5,\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:20:59'),
(171, 'manual_send', '(34)99119-1534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro HTTP: 400', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:21:01'),
(172, 'cashback_liberado', '(34)99119-1534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro HTTP: 400', 0, '{\"valor_cashback\":2.5,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:21:01'),
(173, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-21 00:22:31'),
(174, 'manual_send', '(34)99119-1534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 0, NULL, 'Bot não está conectado ao WhatsApp', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:24:58'),
(175, 'nova_transacao', '(34)99119-1534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 0, NULL, 'Bot não está conectado ao WhatsApp', 0, '{\"valor_cashback\":2.5,\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:24:58'),
(176, 'manual_send', '(34)99119-1534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Bot não está conectado ao WhatsApp', 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:25:00'),
(177, 'cashback_liberado', '(34)99119-1534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Bot não está conectado ao WhatsApp', 0, '{\"valor_cashback\":2.5,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:25:00'),
(178, 'manual_send', '(34)99119-1534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:26:23'),
(179, 'nova_transacao', '(34)99119-1534', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:26:23'),
(180, 'manual_send', '(34)99119-1534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:26:25'),
(181, 'cashback_liberado', '(34)99119-1534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 2,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '{\"valor_cashback\":2.5,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:26:25'),
(182, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:c32:7891:4174:5fcb', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/119.0.0.0', '2025-06-21 00:26:39'),
(183, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:ac2e:8e52:6b99:218e', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-22 09:38:13'),
(184, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro HTTP: 404', 0, '[]', '2804:690:33ce:3000:3c13:18f6:8c25:a10b', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-22 17:57:26'),
(185, 'manual_send', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 5,00* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro HTTP: 404', 0, '[]', '18.215.140.160', 'MercadoPago WebHook v1.0 payment', '2025-07-09 18:04:36'),
(186, 'cashback_liberado', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 5,00* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro HTTP: 404', 0, '{\"valor_cashback\":\"5.00\",\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '18.215.140.160', 'MercadoPago WebHook v1.0 payment', '2025-07-09 18:04:36'),
(187, 'manual_send', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 5,00* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro HTTP: 404', 0, '[]', '54.88.218.97', 'MercadoPago WebHook v1.0 payment', '2025-07-17 20:31:33'),
(188, 'cashback_liberado', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 5,00* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro HTTP: 404', 0, '{\"valor_cashback\":\"5.00\",\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '54.88.218.97', 'MercadoPago WebHook v1.0 payment', '2025-07-17 20:31:33'),
(189, 'manual_send', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 4,60* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro HTTP: 404', 0, '[]', '18.215.140.160', 'MercadoPago WebHook v1.0 payment', '2025-07-17 20:48:38'),
(190, 'cashback_liberado', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 4,60* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro HTTP: 404', 0, '{\"valor_cashback\":\"4.60\",\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '18.215.140.160', 'MercadoPago WebHook v1.0 payment', '2025-07-17 20:48:38'),
(191, 'manual_send', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 5,00* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro HTTP: 404', 0, '[]', '18.213.114.129', 'MercadoPago WebHook v1.0 payment', '2025-07-22 13:26:29'),
(192, 'cashback_liberado', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 5,00* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro HTTP: 404', 0, '{\"valor_cashback\":\"5.00\",\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '18.213.114.129', 'MercadoPago WebHook v1.0 payment', '2025-07-22 13:26:29'),
(193, 'manual_send', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 0, NULL, 'Erro HTTP: 404', 0, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 17:15:03'),
(194, 'nova_transacao', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 0, NULL, 'Erro HTTP: 404', 0, '{\"valor_cashback\":5.5,\"valor_usado\":2.3,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 17:15:03'),
(195, 'manual_send', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 8,75* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro HTTP: 404', 0, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 17:15:06'),
(196, 'cashback_liberado', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 8,75* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro HTTP: 404', 0, '{\"valor_cashback\":8.75,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 17:15:06'),
(197, 'manual_send', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 8,75* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro HTTP: 404', 0, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 17:15:07'),
(198, 'cashback_liberado', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 8,75* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro HTTP: 404', 0, '{\"valor_cashback\":8.75,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 17:15:07'),
(199, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro HTTP: 404', 0, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 17:15:09'),
(200, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro HTTP: 404', 0, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 17:31:48'),
(201, 'manual_send', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 5,00* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro HTTP: 404', 0, '[]', '54.88.218.97', 'MercadoPago WebHook v1.0 payment', '2025-07-30 17:33:24'),
(202, 'cashback_liberado', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 5,00* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro HTTP: 404', 0, '{\"valor_cashback\":\"5.00\",\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '54.88.218.97', 'MercadoPago WebHook v1.0 payment', '2025-07-30 17:33:24'),
(203, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro HTTP: 404', 0, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 17:35:15'),
(204, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro HTTP: 404', 0, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 17:35:19'),
(205, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro HTTP: 404', 0, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 17:36:25'),
(206, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro HTTP: 404', 0, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 17:36:52'),
(207, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro HTTP: 404', 0, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 17:36:55'),
(208, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro HTTP: 404', 0, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 17:47:20'),
(209, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro HTTP: 404', 0, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 17:47:36'),
(210, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro HTTP: 404', 0, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 17:50:16'),
(211, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro HTTP: 404', 0, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 17:50:46'),
(212, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro HTTP: 404', 0, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 17:50:53'),
(213, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro HTTP: 404', 0, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 17:51:04'),
(214, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro HTTP: 404', 0, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 17:51:07'),
(215, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro HTTP: 404', 0, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 17:51:10'),
(216, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 17:51:15'),
(217, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 17:51:49'),
(218, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688a8b6e90cca', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 18:15:26'),
(219, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688a8b7048134', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 18:15:28'),
(220, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688a8b7916705', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 18:15:37'),
(221, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688a8bb9e3d15', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 18:16:42'),
(222, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688a8dbd2f504', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 18:25:17'),
(223, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688a8dc07c58d', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 18:25:20'),
(224, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688a935693d1d', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 18:49:10'),
(225, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688a94eb68d49', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 18:55:55'),
(226, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688a957f403a7', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 18:58:23'),
(227, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688a95897a39e', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 18:58:33'),
(228, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688a95a1563c4', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 18:58:57'),
(229, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688a961dc24ec', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 19:01:01'),
(230, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688aa54d56202', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 20:05:49'),
(231, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688aa55aece9d', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 20:06:02'),
(232, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688aa5f12c45a', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 20:08:33'),
(233, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688aa6f5f353c', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 20:12:53'),
(234, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688aa6ffaf8a5', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 20:13:03'),
(235, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688aa700cc21b', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 20:13:04'),
(236, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688aa701da090', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 20:13:05'),
(237, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688aa7206b894', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 20:13:36'),
(238, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688aa74ddbdb5', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 20:14:22'),
(239, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688aa755e595f', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 20:14:29'),
(240, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688aa766ca4bc', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 20:14:46'),
(241, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688aa783973a7', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 20:15:15'),
(242, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688aa868d1f9b', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 20:19:04'),
(243, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688aa8814fa17', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 20:19:29'),
(244, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688aaabe0eaaa', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 20:29:02'),
(245, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688aaacbcb45a', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 20:29:15'),
(246, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688aad9214487', NULL, 1, '[]', '138.0.64.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 20:41:06'),
(247, 'manual_send', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 0,50* da loja *Kaua Matheus da Silva Lop', 1, 'sim_688aae0278e30', NULL, 1, '[]', '18.213.114.129', 'MercadoPago WebHook v1.0 payment', '2025-07-30 20:42:58'),
(248, 'cashback_liberado', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 0,50* da loja *Kaua Matheus da Silva Lop', 1, 'sim_688aae0278e30', NULL, 1, '{\"valor_cashback\":\"0.50\",\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '18.213.114.129', 'MercadoPago WebHook v1.0 payment', '2025-07-30 20:42:58'),
(249, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688aae15535c6', NULL, 1, '[]', '138.0.64.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 20:43:17'),
(250, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688aae1e1ca28', NULL, 1, '[]', '138.0.64.200', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 20:43:26'),
(251, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688aae2c2f435', NULL, 1, '[]', 'unknown', 'unknown', '2025-07-30 20:43:41'),
(252, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688aae48c3b98', NULL, 1, '[]', 'unknown', 'unknown', '2025-07-30 20:44:09'),
(253, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688aae8bef6b1', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 20:45:16'),
(254, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688aaea77f474', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 20:45:43'),
(255, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688aaff0ecd92', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 20:51:12'),
(256, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688aaff773425', NULL, 1, '[]', '2804:690:33ce:3000:d03b:5988:b7e6:9fd9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-07-30 20:51:19'),
(257, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688cc147d410e', NULL, 1, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 10:29:43'),
(258, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688cc16c4d430', NULL, 1, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 10:30:20'),
(259, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688cc24b073d9', NULL, 1, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 10:34:03'),
(260, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688cc283c3651', NULL, 1, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 10:34:59'),
(261, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688cc4e42785a', NULL, 1, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 10:45:08'),
(262, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688cc4ee7e9d1', NULL, 1, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 10:45:18'),
(263, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688cc5adbef40', NULL, 1, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 10:48:29'),
(264, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688cc5aebffe8', NULL, 1, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 10:48:30'),
(265, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688cc5fa66ffc', NULL, 1, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 10:49:47'),
(266, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688cc678b6774', NULL, 1, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 10:51:52'),
(267, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688cc67be2423', NULL, 1, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 10:51:55'),
(268, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688cc67cdf9e0', NULL, 1, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 10:51:56'),
(269, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688cc685abccd', NULL, 1, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 10:52:05'),
(270, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688cc799c7584', NULL, 1, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 10:56:41'),
(271, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688cc7b2644df', NULL, 1, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 10:57:06'),
(272, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688cc8c3d3e19', NULL, 1, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 11:01:39'),
(273, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688cc8ffd303f', NULL, 1, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 11:02:39'),
(274, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688cc92948705', NULL, 1, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 11:03:21'),
(275, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688cc92a8f78b', NULL, 1, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 11:03:22'),
(276, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688cc9e32ea5c', NULL, 1, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 11:06:27'),
(277, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688cc9fbbc25c', NULL, 1, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 11:06:51'),
(278, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688cca0ca0648', NULL, 1, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 11:07:08'),
(279, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688cca11a5884', NULL, 1, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 11:07:13'),
(280, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688cca17669a3', NULL, 1, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 11:07:19'),
(281, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, 'sim_688cca209a1bb', NULL, 1, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 11:07:28'),
(282, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro HTTP: 400', 0, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 11:10:52'),
(283, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro HTTP: 400', 0, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 11:11:52'),
(284, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro HTTP: 400', 0, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 11:12:06'),
(285, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro HTTP: 400', 0, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 11:12:08'),
(286, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro HTTP: 400', 0, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 11:12:36'),
(287, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro HTTP: 400', 0, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 11:12:47'),
(288, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro HTTP: 400', 0, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 11:12:48'),
(289, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro HTTP: 400', 0, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 11:12:50'),
(290, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro HTTP: 400', 0, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 11:17:52'),
(291, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro HTTP: 400', 0, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 11:17:56'),
(292, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro HTTP: 400', 0, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 11:18:11'),
(293, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro HTTP: 400', 0, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 11:18:15'),
(294, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 11:18:19'),
(295, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 11:18:28'),
(296, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 11:18:53'),
(297, 'manual_send', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 0,25* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '[]', '54.88.218.97', 'MercadoPago WebHook v1.0 payment', '2025-08-01 11:20:08'),
(298, 'cashback_liberado', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 0,25* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '{\"valor_cashback\":\"0.25\",\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '54.88.218.97', 'MercadoPago WebHook v1.0 payment', '2025-08-01 11:20:08'),
(299, 'manual_send', '5538991045205', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 11:23:07');
INSERT INTO `whatsapp_logs` (`id`, `type`, `phone`, `message_preview`, `success`, `message_id`, `error_message`, `simulation_mode`, `additional_data`, `ip_address`, `user_agent`, `created_at`) VALUES
(300, 'manual_send', '5534991191534', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 11:23:13'),
(301, 'manual_send', '5534991191534', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 11:23:25'),
(302, 'manual_send', '5534991191534', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 11:31:14'),
(303, 'manual_send', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 250,00* da loja *Kaua Matheus da Silva L', 1, NULL, NULL, 0, '[]', '18.206.34.84', 'MercadoPago WebHook v1.0 payment', '2025-08-01 12:04:52'),
(304, 'cashback_liberado', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 250,00* da loja *Kaua Matheus da Silva L', 1, NULL, NULL, 0, '{\"valor_cashback\":\"250.00\",\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '18.206.34.84', 'MercadoPago WebHook v1.0 payment', '2025-08-01 12:04:52'),
(305, 'manual_send', '5534991191534', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, NULL, NULL, 0, '[]', '2804:690:33ce:3000:7d62:9ea3:4a6:c26', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36 OPR/120.0.0.0', '2025-08-01 13:24:23'),
(306, 'manual_send', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 0,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '[]', '18.206.34.84', 'MercadoPago WebHook v1.0 payment', '2025-08-01 16:21:29'),
(307, 'cashback_liberado', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 0,50* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '{\"valor_cashback\":\"0.50\",\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '18.206.34.84', 'MercadoPago WebHook v1.0 payment', '2025-08-01 16:21:29'),
(308, 'manual_send', '5534991191534', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro cURL: Connection timed out after 30000 milliseconds', 0, '[]', '45.162.191.201', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-04 08:49:04'),
(309, 'manual_send', '5534991191534', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro cURL: Failed to connect to 54.207.165.92 port 3002: Connection refused', 0, '[]', '45.162.191.201', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-04 08:53:22'),
(310, 'manual_send', '5534991191534', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro cURL: Failed to connect to 54.207.165.92 port 3002: Connection refused', 0, '[]', '45.162.191.201', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-04 08:53:26'),
(311, 'manual_send', '5534991191534', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro cURL: Failed to connect to 54.207.165.92 port 3002: Connection refused', 0, '[]', '45.162.191.201', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-04 08:55:04'),
(312, 'manual_send', '5534991191534', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro cURL: Failed to connect to 54.207.165.92 port 3002: Connection refused', 0, '[]', '45.162.191.201', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-04 08:55:18'),
(313, 'manual_send', '5534991191534', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro cURL: Failed to connect to 54.207.165.92 port 3002: Connection refused', 0, '[]', '45.162.191.201', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-04 09:02:51'),
(314, 'manual_send', '5534991191534', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Erro cURL: Failed to connect to 54.207.165.92 port 3002: Connection refused', 0, '[]', '45.162.191.201', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-04 09:02:58'),
(315, 'manual_send', '5534991191534', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Bot não está conectado ao WhatsApp', 0, '[]', '45.162.191.201', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-04 09:03:33'),
(316, 'manual_send', '5534991191534', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 0, NULL, 'Bot não está conectado ao WhatsApp', 0, '[]', '45.162.191.201', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-04 09:03:36'),
(317, 'manual_send', '5534991191534', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, NULL, NULL, 0, '[]', '45.162.191.201', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-04 09:03:51'),
(318, 'manual_send', '5534991191534', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, NULL, NULL, 0, '[]', '45.162.191.201', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-04 09:05:49'),
(319, 'manual_send', '5534991191534', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, NULL, NULL, 0, '[]', '45.162.191.201', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-04 09:14:20'),
(320, 'manual_send', '5534991191534', '🧪 *Teste Klube Cash WhatsApp*\n\nEsta é uma mensagem de teste do sistema de notificações.\n\nHorá', 1, NULL, NULL, 0, '[]', '45.162.191.201', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-04 15:46:40'),
(321, 'manual_send', '34991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 1,00* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '[]', '18.206.34.84', 'MercadoPago WebHook v1.0 payment', '2025-08-13 23:24:28'),
(322, 'cashback_liberado', '34991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 1,00* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '{\"valor_cashback\":\"1.00\",\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '18.206.34.84', 'MercadoPago WebHook v1.0 payment', '2025-08-13 23:24:28'),
(323, 'manual_send', '34993357697', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 1,00* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '[]', '18.213.114.129', 'MercadoPago WebHook v1.0 payment', '2025-08-13 23:30:52'),
(324, 'cashback_liberado', '34993357697', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 1,00* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '{\"valor_cashback\":\"1.00\",\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '18.213.114.129', 'MercadoPago WebHook v1.0 payment', '2025-08-13 23:30:52'),
(325, 'manual_send', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 5,00* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '[]', '18.215.140.160', 'MercadoPago WebHook v1.0 payment', '2025-08-15 09:11:08'),
(326, 'cashback_liberado', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 5,00* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '{\"valor_cashback\":\"5.00\",\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '18.215.140.160', 'MercadoPago WebHook v1.0 payment', '2025-08-15 09:11:08'),
(327, 'manual_send', '34991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 0,25* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '[]', '18.213.114.129', 'MercadoPago WebHook v1.0 payment', '2025-08-15 10:41:05'),
(328, 'cashback_liberado', '34991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 0,25* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '{\"valor_cashback\":\"0.25\",\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '18.213.114.129', 'MercadoPago WebHook v1.0 payment', '2025-08-15 10:41:05'),
(329, 'manual_send', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 0,50* da loja *Sync Holding* foi liberad', 1, NULL, NULL, 0, '[]', '18.215.140.160', 'MercadoPago WebHook v1.0 payment', '2025-08-15 11:46:52'),
(330, 'cashback_liberado', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 0,50* da loja *Sync Holding* foi liberad', 1, NULL, NULL, 0, '{\"valor_cashback\":\"0.50\",\"nome_loja\":\"Sync Holding\"}', '18.215.140.160', 'MercadoPago WebHook v1.0 payment', '2025-08-15 11:46:52'),
(331, 'manual_send', '34991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 0,25* da loja *Sync Holding* foi liberad', 1, NULL, NULL, 0, '[]', '54.88.218.97', 'MercadoPago WebHook v1.0 payment', '2025-08-15 11:48:52'),
(332, 'cashback_liberado', '34991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 0,25* da loja *Sync Holding* foi liberad', 1, NULL, NULL, 0, '{\"valor_cashback\":\"0.25\",\"nome_loja\":\"Sync Holding\"}', '54.88.218.97', 'MercadoPago WebHook v1.0 payment', '2025-08-15 11:48:52'),
(333, 'manual_send', '34991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 0,49* da loja *Sync Holding* foi liberad', 1, NULL, NULL, 0, '[]', '18.215.140.160', 'MercadoPago WebHook v1.0 payment', '2025-08-15 16:50:16'),
(334, 'cashback_liberado', '34991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 0,49* da loja *Sync Holding* foi liberad', 1, NULL, NULL, 0, '{\"valor_cashback\":\"0.49\",\"nome_loja\":\"Sync Holding\"}', '18.215.140.160', 'MercadoPago WebHook v1.0 payment', '2025-08-15 16:50:16'),
(335, 'manual_send', '34991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 0,25* da loja *Sync Holding* foi liberad', 1, NULL, NULL, 0, '[]', '18.213.114.129', 'MercadoPago WebHook v1.0 payment', '2025-08-15 17:18:06'),
(336, 'cashback_liberado', '34991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 0,25* da loja *Sync Holding* foi liberad', 1, NULL, NULL, 0, '{\"valor_cashback\":\"0.25\",\"nome_loja\":\"Sync Holding\"}', '18.213.114.129', 'MercadoPago WebHook v1.0 payment', '2025-08-15 17:18:06'),
(337, 'manual_send', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', 'unknown', 'unknown', '2025-08-28 03:30:18'),
(338, 'nova_transacao', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":null,\"valor_usado\":0,\"nome_loja\":null}', 'unknown', 'unknown', '2025-08-28 03:30:19'),
(339, 'manual_send', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', 'unknown', 'unknown', '2025-08-28 03:30:47'),
(340, 'nova_transacao', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":\"5\",\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', 'unknown', 'unknown', '2025-08-28 03:30:47'),
(341, 'manual_send', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', 'unknown', 'unknown', '2025-08-28 03:31:21'),
(342, 'nova_transacao', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":\"5\",\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', 'unknown', 'unknown', '2025-08-28 03:31:21'),
(343, 'manual_send', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', 'unknown', 'unknown', '2025-08-28 03:32:54'),
(344, 'nova_transacao', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":\"5\",\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', 'unknown', 'unknown', '2025-08-28 03:32:55'),
(345, 'manual_send', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', 'unknown', 'unknown', '2025-08-28 03:33:23'),
(346, 'nova_transacao', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":\"5\",\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', 'unknown', 'unknown', '2025-08-28 03:33:24'),
(347, 'manual_send', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', 'unknown', 'unknown', '2025-08-28 03:34:04'),
(348, 'nova_transacao', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":\"1.25\",\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', 'unknown', 'unknown', '2025-08-28 03:34:05'),
(349, 'manual_send', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', 'unknown', 'unknown', '2025-08-28 03:35:02'),
(350, 'nova_transacao', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":\"0.5\",\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', 'unknown', 'unknown', '2025-08-28 03:35:03'),
(351, 'manual_send', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', 'unknown', 'unknown', '2025-08-28 03:35:42'),
(352, 'nova_transacao', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":\"0.5\",\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', 'unknown', 'unknown', '2025-08-28 03:35:43'),
(353, 'manual_send', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', 'unknown', 'unknown', '2025-08-28 03:54:07'),
(354, 'nova_transacao', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":\"1.5\",\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', 'unknown', 'unknown', '2025-08-28 03:54:07'),
(355, 'manual_send', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', 'unknown', 'unknown', '2025-08-28 03:55:24'),
(356, 'nova_transacao', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":\"1.5\",\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', 'unknown', 'unknown', '2025-08-28 03:55:25'),
(357, 'manual_send', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', 'unknown', 'unknown', '2025-08-28 03:55:50'),
(358, 'nova_transacao', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":\"1.5\",\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', 'unknown', 'unknown', '2025-08-28 03:55:50'),
(359, 'manual_send', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', 'unknown', 'unknown', '2025-08-28 04:05:45'),
(360, 'nova_transacao', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":\"1.5\",\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', 'unknown', 'unknown', '2025-08-28 04:05:45'),
(361, 'manual_send', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', 'unknown', 'unknown', '2025-08-28 04:06:32'),
(362, 'nova_transacao', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":\"1.5\",\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', 'unknown', 'unknown', '2025-08-28 04:06:32'),
(363, 'manual_send', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', 'unknown', 'unknown', '2025-08-28 04:07:24'),
(364, 'nova_transacao', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":\"1\",\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', 'unknown', 'unknown', '2025-08-28 04:07:25'),
(365, 'manual_send', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '[]', 'unknown', 'unknown', '2025-08-28 04:13:09'),
(366, 'nova_transacao', '38991045205', '🔔 *Klube Cash - Nova Transação*\n\nNova transação registrada: Você tem um novo cashback de *R$', 1, NULL, NULL, 0, '{\"valor_cashback\":\"1\",\"valor_usado\":0,\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', 'unknown', 'unknown', '2025-08-28 04:13:09'),
(367, 'manual_send', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 5,00* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '[]', '54.88.218.97', 'MercadoPago WebHook v1.0 payment', '2025-08-29 16:30:18'),
(368, 'cashback_liberado', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 5,00* da loja *Kaua Matheus da Silva Lop', 1, NULL, NULL, 0, '{\"valor_cashback\":\"5.00\",\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '54.88.218.97', 'MercadoPago WebHook v1.0 payment', '2025-08-29 16:30:18'),
(369, 'manual_send', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 1,00* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro cURL: Operation timed out after 30001 milliseconds with 0 bytes received', 0, '[]', '18.213.114.129', 'MercadoPago WebHook v1.0 payment', '2025-09-12 23:20:38'),
(370, 'cashback_liberado', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 1,00* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro cURL: Operation timed out after 30001 milliseconds with 0 bytes received', 0, '{\"valor_cashback\":\"1.00\",\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '18.213.114.129', 'MercadoPago WebHook v1.0 payment', '2025-09-12 23:20:38'),
(371, 'manual_send', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 1,00* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro cURL: Operation timed out after 30001 milliseconds with 0 bytes received', 0, '[]', '18.206.34.84', 'MercadoPago WebHook v1.0 payment', '2025-09-12 23:21:08'),
(372, 'cashback_liberado', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 1,00* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro cURL: Operation timed out after 30001 milliseconds with 0 bytes received', 0, '{\"valor_cashback\":\"1.00\",\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '18.206.34.84', 'MercadoPago WebHook v1.0 payment', '2025-09-12 23:21:08'),
(373, 'manual_send', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 5,00* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro cURL: Operation timed out after 30000 milliseconds with 0 bytes received', 0, '[]', '18.213.114.129', 'MercadoPago WebHook v1.0 payment', '2025-09-13 08:03:00'),
(374, 'cashback_liberado', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 5,00* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro cURL: Operation timed out after 30000 milliseconds with 0 bytes received', 0, '{\"valor_cashback\":\"5.00\",\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '18.213.114.129', 'MercadoPago WebHook v1.0 payment', '2025-09-13 08:03:00'),
(375, 'manual_send', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 5,00* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro cURL: Operation timed out after 30000 milliseconds with 0 bytes received', 0, '[]', '54.88.218.97', 'MercadoPago WebHook v1.0 payment', '2025-09-13 08:03:30'),
(376, 'cashback_liberado', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 5,00* da loja *Kaua Matheus da Silva Lop', 0, NULL, 'Erro cURL: Operation timed out after 30000 milliseconds with 0 bytes received', 0, '{\"valor_cashback\":\"5.00\",\"nome_loja\":\"Kaua Matheus da Silva Lopes\"}', '54.88.218.97', 'MercadoPago WebHook v1.0 payment', '2025-09-13 08:03:30'),
(377, 'manual_send', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 5,00* da loja *Sync Holding* foi liberad', 0, NULL, 'Erro cURL: Connection timed out after 30000 milliseconds', 0, '[]', '18.206.34.84', 'MercadoPago WebHook v1.0 payment', '2025-09-16 12:26:54'),
(378, 'cashback_liberado', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 5,00* da loja *Sync Holding* foi liberad', 0, NULL, 'Erro cURL: Connection timed out after 30000 milliseconds', 0, '{\"valor_cashback\":\"5.00\",\"nome_loja\":\"Sync Holding\"}', '18.206.34.84', 'MercadoPago WebHook v1.0 payment', '2025-09-16 12:26:54'),
(379, 'manual_send', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 5,00* da loja *Sync Holding* foi liberad', 0, NULL, 'Erro cURL: Connection timed out after 30001 milliseconds', 0, '[]', '18.213.114.129', 'MercadoPago WebHook v1.0 payment', '2025-09-16 12:27:24'),
(380, 'cashback_liberado', '38991045205', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 5,00* da loja *Sync Holding* foi liberad', 0, NULL, 'Erro cURL: Connection timed out after 30001 milliseconds', 0, '{\"valor_cashback\":\"5.00\",\"nome_loja\":\"Sync Holding\"}', '18.213.114.129', 'MercadoPago WebHook v1.0 payment', '2025-09-16 12:27:24');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `admin_reserva_cashback`
--
ALTER TABLE `admin_reserva_cashback`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `admin_reserva_movimentacoes`
--
ALTER TABLE `admin_reserva_movimentacoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `admin_saldo`
--
ALTER TABLE `admin_saldo`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `admin_saldo_movimentacoes`
--
ALTER TABLE `admin_saldo_movimentacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transacao_id` (`transacao_id`);

--
-- Índices de tabela `api_keys`
--
ALTER TABLE `api_keys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key_hash` (`key_hash`),
  ADD UNIQUE KEY `key_prefix` (`key_prefix`),
  ADD KEY `partner_email` (`partner_email`),
  ADD KEY `is_active` (`is_active`);

--
-- Índices de tabela `api_logs`
--
ALTER TABLE `api_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `endpoint` (`endpoint`),
  ADD KEY `api_key_id` (`api_key_id`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `status_code` (`status_code`);

--
-- Índices de tabela `api_rate_limits`
--
ALTER TABLE `api_rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rate_limit` (`api_key_id`,`endpoint`,`window_type`,`window_start`);

--
-- Índices de tabela `cashback_movimentacoes`
--
ALTER TABLE `cashback_movimentacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loja_id` (`loja_id`),
  ADD KEY `transacao_origem_id` (`transacao_origem_id`),
  ADD KEY `transacao_uso_id` (`transacao_uso_id`),
  ADD KEY `idx_usuario_loja_data` (`usuario_id`,`loja_id`,`data_operacao`),
  ADD KEY `pagamento_id` (`pagamento_id`),
  ADD KEY `criado_por` (`criado_por`);

--
-- Índices de tabela `cashback_notificacoes`
--
ALTER TABLE `cashback_notificacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transacao_id` (`transacao_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_data_tentativa` (`data_tentativa`);

--
-- Índices de tabela `cashback_notification_retries`
--
ALTER TABLE `cashback_notification_retries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transaction_id` (`transaction_id`),
  ADD KEY `idx_status_next_retry` (`status`,`next_retry`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Índices de tabela `cashback_saldos`
--
ALTER TABLE `cashback_saldos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_usuario_loja` (`usuario_id`,`loja_id`),
  ADD KEY `loja_id` (`loja_id`);

--
-- Índices de tabela `comissoes_status_historico`
--
ALTER TABLE `comissoes_status_historico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `comissao_id` (`comissao_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `configuracoes_2fa`
--
ALTER TABLE `configuracoes_2fa`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `configuracoes_cashback`
--
ALTER TABLE `configuracoes_cashback`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `configuracoes_notificacao`
--
ALTER TABLE `configuracoes_notificacao`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `configuracoes_saldo`
--
ALTER TABLE `configuracoes_saldo`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `email_campaigns`
--
ALTER TABLE `email_campaigns`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `email_envios`
--
ALTER TABLE `email_envios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_campaign_email` (`campaign_id`,`email`);

--
-- Índices de tabela `email_templates`
--
ALTER TABLE `email_templates`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favorite` (`user_id`,`store_id`),
  ADD KEY `store_id` (`store_id`);

--
-- Índices de tabela `ip_block`
--
ALTER TABLE `ip_block`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ip` (`ip`),
  ADD KEY `block_expiry` (`block_expiry`);

--
-- Índices de tabela `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ip` (`ip`),
  ADD KEY `attempt_time` (`attempt_time`);

--
-- Índices de tabela `lojas`
--
ALTER TABLE `lojas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cnpj` (`cnpj`),
  ADD KEY `idx_lojas_email_senha` (`email`,`senha_hash`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_lojas_cashback_config` (`cashback_ativo`,`porcentagem_cliente`,`porcentagem_admin`);

--
-- Índices de tabela `lojas_contato`
--
ALTER TABLE `lojas_contato`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loja_id` (`loja_id`);

--
-- Índices de tabela `lojas_endereco`
--
ALTER TABLE `lojas_endereco`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loja_id` (`loja_id`);

--
-- Índices de tabela `lojas_favoritas`
--
ALTER TABLE `lojas_favoritas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favorite` (`usuario_id`,`loja_id`),
  ADD KEY `loja_id` (`loja_id`);

--
-- Índices de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `pagamentos_comissao`
--
ALTER TABLE `pagamentos_comissao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loja_id` (`loja_id`),
  ADD KEY `criado_por` (`criado_por`);

--
-- Índices de tabela `pagamentos_devolucoes`
--
ALTER TABLE `pagamentos_devolucoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pagamento_id` (`pagamento_id`),
  ADD KEY `solicitado_por` (`solicitado_por`),
  ADD KEY `aprovado_por` (`aprovado_por`);

--
-- Índices de tabela `pagamentos_transacoes`
--
ALTER TABLE `pagamentos_transacoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pagamento_transacao_unique` (`pagamento_id`,`transacao_id`),
  ADD KEY `transacao_id` (`transacao_id`);

--
-- Índices de tabela `pagamento_transacoes`
--
ALTER TABLE `pagamento_transacoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_payment_transaction` (`pagamento_id`,`transacao_id`);

--
-- Índices de tabela `recuperacao_senha`
--
ALTER TABLE `recuperacao_senha`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `sessoes`
--
ALTER TABLE `sessoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `store_balance_payments`
--
ALTER TABLE `store_balance_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loja_id` (`loja_id`);

--
-- Índices de tabela `transacoes_cashback`
--
ALTER TABLE `transacoes_cashback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `loja_id` (`loja_id`),
  ADD KEY `criado_por` (`criado_por`);

--
-- Índices de tabela `transacoes_comissao`
--
ALTER TABLE `transacoes_comissao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transacao_id` (`transacao_id`);

--
-- Índices de tabela `transacoes_saldo_usado`
--
ALTER TABLE `transacoes_saldo_usado`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transacao` (`transacao_id`),
  ADD KEY `idx_usuario_loja` (`usuario_id`,`loja_id`);

--
-- Índices de tabela `transacoes_status_historico`
--
ALTER TABLE `transacoes_status_historico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transacao_id` (`transacao_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `google_id` (`google_id`),
  ADD UNIQUE KEY `uk_cpf_unique` (`cpf`),
  ADD UNIQUE KEY `unique_email_not_null` (`email`),
  ADD KEY `idx_usuarios_google_id` (`google_id`),
  ADD KEY `idx_usuarios_provider` (`provider`),
  ADD KEY `idx_cpf` (`cpf`),
  ADD KEY `loja_vinculada_id` (`loja_vinculada_id`),
  ADD KEY `fk_usuarios_loja_criadora` (`loja_criadora_id`),
  ADD KEY `idx_usuarios_telefone` (`telefone`),
  ADD KEY `idx_usuarios_senat` (`senat`);

--
-- Índices de tabela `usuarios_contato`
--
ALTER TABLE `usuarios_contato`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `usuarios_endereco`
--
ALTER TABLE `usuarios_endereco`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `verificacao_2fa`
--
ALTER TABLE `verificacao_2fa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario_codigo` (`usuario_id`,`codigo`),
  ADD KEY `idx_expiracao` (`data_expiracao`);

--
-- Índices de tabela `webhook_errors`
--
ALTER TABLE `webhook_errors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mp_payment_id` (`mp_payment_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Índices de tabela `whatsapp_cadastro_sessions`
--
ALTER TABLE `whatsapp_cadastro_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- Índices de tabela `whatsapp_logs`
--
ALTER TABLE `whatsapp_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_success` (`success`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `admin_reserva_movimentacoes`
--
ALTER TABLE `admin_reserva_movimentacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de tabela `admin_saldo`
--
ALTER TABLE `admin_saldo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `admin_saldo_movimentacoes`
--
ALTER TABLE `admin_saldo_movimentacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT de tabela `api_keys`
--
ALTER TABLE `api_keys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `api_logs`
--
ALTER TABLE `api_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `api_rate_limits`
--
ALTER TABLE `api_rate_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `cashback_movimentacoes`
--
ALTER TABLE `cashback_movimentacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=230;

--
-- AUTO_INCREMENT de tabela `cashback_notificacoes`
--
ALTER TABLE `cashback_notificacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `cashback_notification_retries`
--
ALTER TABLE `cashback_notification_retries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de tabela `cashback_saldos`
--
ALTER TABLE `cashback_saldos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=200;

--
-- AUTO_INCREMENT de tabela `comissoes_status_historico`
--
ALTER TABLE `comissoes_status_historico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `configuracoes_2fa`
--
ALTER TABLE `configuracoes_2fa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `configuracoes_cashback`
--
ALTER TABLE `configuracoes_cashback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `configuracoes_notificacao`
--
ALTER TABLE `configuracoes_notificacao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `configuracoes_saldo`
--
ALTER TABLE `configuracoes_saldo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `email_campaigns`
--
ALTER TABLE `email_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `email_envios`
--
ALTER TABLE `email_envios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de tabela `email_templates`
--
ALTER TABLE `email_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ip_block`
--
ALTER TABLE `ip_block`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `lojas`
--
ALTER TABLE `lojas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT de tabela `lojas_contato`
--
ALTER TABLE `lojas_contato`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `lojas_endereco`
--
ALTER TABLE `lojas_endereco`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de tabela `lojas_favoritas`
--
ALTER TABLE `lojas_favoritas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=666;

--
-- AUTO_INCREMENT de tabela `pagamentos_comissao`
--
ALTER TABLE `pagamentos_comissao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1201;

--
-- AUTO_INCREMENT de tabela `pagamentos_devolucoes`
--
ALTER TABLE `pagamentos_devolucoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `pagamentos_transacoes`
--
ALTER TABLE `pagamentos_transacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=218;

--
-- AUTO_INCREMENT de tabela `pagamento_transacoes`
--
ALTER TABLE `pagamento_transacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `recuperacao_senha`
--
ALTER TABLE `recuperacao_senha`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT de tabela `store_balance_payments`
--
ALTER TABLE `store_balance_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de tabela `transacoes_cashback`
--
ALTER TABLE `transacoes_cashback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=505;

--
-- AUTO_INCREMENT de tabela `transacoes_comissao`
--
ALTER TABLE `transacoes_comissao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=303;

--
-- AUTO_INCREMENT de tabela `transacoes_saldo_usado`
--
ALTER TABLE `transacoes_saldo_usado`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de tabela `transacoes_status_historico`
--
ALTER TABLE `transacoes_status_historico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=190;

--
-- AUTO_INCREMENT de tabela `usuarios_contato`
--
ALTER TABLE `usuarios_contato`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `usuarios_endereco`
--
ALTER TABLE `usuarios_endereco`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `verificacao_2fa`
--
ALTER TABLE `verificacao_2fa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `webhook_errors`
--
ALTER TABLE `webhook_errors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de tabela `whatsapp_cadastro_sessions`
--
ALTER TABLE `whatsapp_cadastro_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `whatsapp_logs`
--
ALTER TABLE `whatsapp_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=381;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `api_logs`
--
ALTER TABLE `api_logs`
  ADD CONSTRAINT `api_logs_ibfk_1` FOREIGN KEY (`api_key_id`) REFERENCES `api_keys` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `api_rate_limits`
--
ALTER TABLE `api_rate_limits`
  ADD CONSTRAINT `api_rate_limits_ibfk_1` FOREIGN KEY (`api_key_id`) REFERENCES `api_keys` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `cashback_movimentacoes`
--
ALTER TABLE `cashback_movimentacoes`
  ADD CONSTRAINT `cashback_movimentacoes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cashback_movimentacoes_ibfk_2` FOREIGN KEY (`loja_id`) REFERENCES `lojas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cashback_movimentacoes_ibfk_3` FOREIGN KEY (`transacao_origem_id`) REFERENCES `transacoes_cashback` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cashback_movimentacoes_ibfk_4` FOREIGN KEY (`transacao_uso_id`) REFERENCES `transacoes_cashback` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cashback_movimentacoes_ibfk_5` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `cashback_notificacoes`
--
ALTER TABLE `cashback_notificacoes`
  ADD CONSTRAINT `cashback_notificacoes_ibfk_1` FOREIGN KEY (`transacao_id`) REFERENCES `transacoes_cashback` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `cashback_saldos`
--
ALTER TABLE `cashback_saldos`
  ADD CONSTRAINT `cashback_saldos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cashback_saldos_ibfk_2` FOREIGN KEY (`loja_id`) REFERENCES `lojas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `comissoes_status_historico`
--
ALTER TABLE `comissoes_status_historico`
  ADD CONSTRAINT `comissoes_status_historico_ibfk_1` FOREIGN KEY (`comissao_id`) REFERENCES `transacoes_comissao` (`id`),
  ADD CONSTRAINT `comissoes_status_historico_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `email_envios`
--
ALTER TABLE `email_envios`
  ADD CONSTRAINT `email_envios_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `email_campaigns` (`id`);

--
-- Restrições para tabelas `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`store_id`) REFERENCES `lojas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `lojas`
--
ALTER TABLE `lojas`
  ADD CONSTRAINT `lojas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `lojas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `lojas_contato`
--
ALTER TABLE `lojas_contato`
  ADD CONSTRAINT `lojas_contato_ibfk_1` FOREIGN KEY (`loja_id`) REFERENCES `lojas` (`id`);

--
-- Restrições para tabelas `lojas_endereco`
--
ALTER TABLE `lojas_endereco`
  ADD CONSTRAINT `lojas_endereco_ibfk_1` FOREIGN KEY (`loja_id`) REFERENCES `lojas` (`id`);

--
-- Restrições para tabelas `lojas_favoritas`
--
ALTER TABLE `lojas_favoritas`
  ADD CONSTRAINT `lojas_favoritas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `lojas_favoritas_ibfk_2` FOREIGN KEY (`loja_id`) REFERENCES `lojas` (`id`);

--
-- Restrições para tabelas `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD CONSTRAINT `notificacoes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `pagamentos_comissao`
--
ALTER TABLE `pagamentos_comissao`
  ADD CONSTRAINT `pagamentos_comissao_ibfk_1` FOREIGN KEY (`loja_id`) REFERENCES `lojas` (`id`),
  ADD CONSTRAINT `pagamentos_comissao_ibfk_2` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `pagamentos_devolucoes`
--
ALTER TABLE `pagamentos_devolucoes`
  ADD CONSTRAINT `pagamentos_devolucoes_ibfk_1` FOREIGN KEY (`pagamento_id`) REFERENCES `pagamentos_comissao` (`id`),
  ADD CONSTRAINT `pagamentos_devolucoes_ibfk_2` FOREIGN KEY (`solicitado_por`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `pagamentos_devolucoes_ibfk_3` FOREIGN KEY (`aprovado_por`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `pagamentos_transacoes`
--
ALTER TABLE `pagamentos_transacoes`
  ADD CONSTRAINT `pagamentos_transacoes_ibfk_1` FOREIGN KEY (`pagamento_id`) REFERENCES `pagamentos_comissao` (`id`),
  ADD CONSTRAINT `pagamentos_transacoes_ibfk_2` FOREIGN KEY (`transacao_id`) REFERENCES `transacoes_cashback` (`id`);

--
-- Restrições para tabelas `recuperacao_senha`
--
ALTER TABLE `recuperacao_senha`
  ADD CONSTRAINT `recuperacao_senha_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `sessoes`
--
ALTER TABLE `sessoes`
  ADD CONSTRAINT `sessoes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `store_balance_payments`
--
ALTER TABLE `store_balance_payments`
  ADD CONSTRAINT `store_balance_payments_ibfk_1` FOREIGN KEY (`loja_id`) REFERENCES `lojas` (`id`);

--
-- Restrições para tabelas `transacoes_cashback`
--
ALTER TABLE `transacoes_cashback`
  ADD CONSTRAINT `transacoes_cashback_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `transacoes_cashback_ibfk_2` FOREIGN KEY (`loja_id`) REFERENCES `lojas` (`id`),
  ADD CONSTRAINT `transacoes_cashback_ibfk_3` FOREIGN KEY (`criado_por`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `transacoes_comissao`
--
ALTER TABLE `transacoes_comissao`
  ADD CONSTRAINT `transacoes_comissao_ibfk_1` FOREIGN KEY (`transacao_id`) REFERENCES `transacoes_cashback` (`id`);

--
-- Restrições para tabelas `transacoes_saldo_usado`
--
ALTER TABLE `transacoes_saldo_usado`
  ADD CONSTRAINT `transacoes_saldo_usado_ibfk_1` FOREIGN KEY (`transacao_id`) REFERENCES `transacoes_cashback` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `transacoes_status_historico`
--
ALTER TABLE `transacoes_status_historico`
  ADD CONSTRAINT `transacoes_status_historico_ibfk_1` FOREIGN KEY (`transacao_id`) REFERENCES `transacoes_cashback` (`id`);

--
-- Restrições para tabelas `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_loja_criadora` FOREIGN KEY (`loja_criadora_id`) REFERENCES `lojas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`loja_vinculada_id`) REFERENCES `lojas` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `usuarios_contato`
--
ALTER TABLE `usuarios_contato`
  ADD CONSTRAINT `usuarios_contato_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `usuarios_endereco`
--
ALTER TABLE `usuarios_endereco`
  ADD CONSTRAINT `usuarios_endereco_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `verificacao_2fa`
--
ALTER TABLE `verificacao_2fa`
  ADD CONSTRAINT `verificacao_2fa_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
