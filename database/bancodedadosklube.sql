-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 27/08/2025 às 15:03
-- Versão do servidor: 10.11.10-MariaDB-log
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
(1, 5.00, -5.00, 10.00, '2025-08-26 15:15:45');

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
(14, 26, 10.00, 'debito', 'Reembolso à loja Kaua Matheus da Silva Lopes - Pagamento ID #26', '2025-08-14 02:32:10'),
(15, 1173, 5.00, 'credito', 'Reserva de cashback - Pagamento #1173 aprovado - Total de clientes: 1', '2025-08-26 15:15:45');

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
(1, 289.59, 289.59, 0.00, '2025-08-26 15:15:45');

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

--
-- Despejando dados para a tabela `admin_saldo_movimentacoes`
--

INSERT INTO `admin_saldo_movimentacoes` (`id`, `transacao_id`, `valor`, `tipo`, `descricao`, `data_operacao`) VALUES
(67, 0, 5.00, 'credito', 'Comissão da transação #credito - Pagamento PIX #Comissão recebida - Transação 192 aprovado automaticamente', '2025-07-09 21:04:36'),
(68, 0, 5.00, 'credito', 'Comissão da transação #credito - Pagamento PIX #Comissão recebida - Transação 193 aprovado automaticamente', '2025-07-17 23:31:33'),
(69, 0, 4.60, 'credito', 'Comissão da transação #credito - Pagamento PIX #Comissão recebida - Transação 194 aprovado automaticamente', '2025-07-17 23:48:38'),
(70, 0, 5.00, 'credito', 'Comissão da transação #credito - Pagamento PIX #Comissão recebida - Transação 195 aprovado automaticamente', '2025-07-22 16:26:29'),
(71, 0, 5.00, 'credito', 'Comissão da transação #credito - Pagamento PIX #Comissão recebida - Transação 196 aprovado automaticamente', '2025-07-30 20:33:24'),
(72, 0, 0.50, 'credito', 'Comissão da transação #credito - Pagamento PIX #Comissão recebida - Transação 197 aprovado automaticamente', '2025-07-30 23:42:58'),
(73, 0, 0.25, 'credito', 'Comissão da transação #credito - Pagamento PIX #Comissão recebida - Transação 198 aprovado automaticamente', '2025-08-01 14:20:07'),
(74, 0, 250.00, 'credito', 'Comissão da transação #credito - Pagamento PIX #Comissão recebida - Transação 199 aprovado automaticamente', '2025-08-01 15:04:50'),
(75, 0, 0.50, 'credito', 'Comissão da transação #credito - Pagamento PIX #Comissão recebida - Transação 202 aprovado automaticamente', '2025-08-01 19:21:27'),
(76, 0, 1.00, 'credito', 'Comissão da transação #credito - Pagamento PIX #Comissão recebida - Transação 220 aprovado automaticamente', '2025-08-14 02:24:27'),
(77, 0, 1.00, 'credito', 'Comissão da transação #credito - Pagamento PIX #Comissão recebida - Transação 221 aprovado automaticamente', '2025-08-14 02:30:50'),
(78, 0, 5.00, 'credito', 'Comissão da transação #credito - Pagamento PIX #Comissão recebida - Transação 239 aprovado automaticamente', '2025-08-15 12:11:06'),
(79, 0, 0.25, 'credito', 'Comissão da transação #credito - Pagamento PIX #Comissão recebida - Transação 241 aprovado automaticamente', '2025-08-15 13:41:03'),
(80, 0, 0.50, 'credito', 'Comissão da transação #credito - Pagamento PIX #Comissão recebida - Transação 246 aprovado automaticamente', '2025-08-15 14:46:51'),
(81, 0, 0.25, 'credito', 'Comissão da transação #credito - Pagamento PIX #Comissão recebida - Transação 248 aprovado automaticamente', '2025-08-15 14:48:50'),
(82, 0, 0.49, 'credito', 'Comissão da transação #credito - Pagamento PIX #Comissão recebida - Transação 250 aprovado automaticamente', '2025-08-15 19:50:15'),
(83, 0, 0.25, 'credito', 'Comissão da transação #credito - Pagamento PIX #Comissão recebida - Transação 251 aprovado automaticamente', '2025-08-15 20:18:04'),
(84, 262, 5.00, 'credito', 'Comissão da transação #262 - Pagamento #1173 aprovado', '2025-08-26 15:15:45');

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
(105, 9, 34, NULL, 'credito', 5.00, 0.00, 5.00, 'Cashback liberado - Pagamento aprovado automaticamente', 192, NULL, '2025-07-09 21:04:36', 1143),
(106, 9, 34, NULL, 'credito', 5.00, 5.00, 10.00, 'Cashback liberado - Pagamento aprovado automaticamente', 193, NULL, '2025-07-17 23:31:33', 1144),
(107, 9, 34, NULL, 'uso', 10.00, 10.00, 0.00, 'Uso do saldo na compra - Código: KC25071720480081519 - Transação #194', NULL, 194, '2025-07-17 23:48:09', 26),
(108, 9, 34, NULL, 'credito', 4.60, 0.00, 4.60, 'Cashback liberado - Pagamento aprovado automaticamente', 194, NULL, '2025-07-17 23:48:38', 1145),
(109, 9, 34, NULL, 'credito', 5.00, 4.60, 9.60, 'Cashback liberado - Pagamento aprovado automaticamente', 195, NULL, '2025-07-22 16:26:29', 1146),
(110, 9, 34, NULL, 'credito', 5.00, 9.60, 14.60, 'Cashback liberado - Pagamento aprovado automaticamente', 196, NULL, '2025-07-30 20:33:24', 1147),
(111, 9, 34, NULL, 'credito', 0.50, 14.60, 15.10, 'Cashback liberado - Pagamento aprovado automaticamente', 197, NULL, '2025-07-30 23:42:58', 1148),
(112, 9, 34, NULL, 'credito', 0.25, 15.10, 15.35, 'Cashback liberado - Pagamento aprovado automaticamente', 198, NULL, '2025-08-01 14:20:07', 1149),
(113, 9, 34, NULL, 'credito', 250.00, 15.35, 265.35, 'Cashback liberado - Pagamento aprovado automaticamente', 199, NULL, '2025-08-01 15:04:50', 1150),
(114, 9, 34, NULL, 'credito', 0.50, 265.35, 265.85, 'Cashback liberado - Pagamento aprovado automaticamente', 202, NULL, '2025-08-01 19:21:27', 1152),
(115, 139, 34, NULL, 'credito', 1.00, 0.00, 1.00, 'Cashback liberado - Pagamento aprovado automaticamente', 220, NULL, '2025-08-14 02:24:27', 1154),
(116, 140, 34, NULL, 'credito', 1.00, 0.00, 1.00, 'Cashback liberado - Pagamento aprovado automaticamente', 221, NULL, '2025-08-14 02:30:50', 1155),
(117, 9, 34, NULL, 'credito', 5.00, 265.85, 270.85, 'Cashback liberado - Pagamento aprovado automaticamente', 239, NULL, '2025-08-15 12:11:06', 1156),
(118, 139, 34, NULL, 'credito', 0.25, 1.00, 1.25, 'Cashback liberado - Pagamento aprovado automaticamente', 241, NULL, '2025-08-15 13:41:03', 1157),
(119, 9, 59, NULL, 'credito', 0.50, 0.00, 0.50, 'Cashback liberado - Pagamento aprovado automaticamente', 246, NULL, '2025-08-15 14:46:51', 1165),
(120, 160, 59, NULL, 'credito', 0.25, 0.00, 0.25, 'Cashback liberado - Pagamento aprovado automaticamente', 248, NULL, '2025-08-15 14:48:50', 1166),
(121, 160, 59, NULL, 'uso', 0.25, 0.25, 0.00, 'Uso do saldo na compra - Código: KC25081516485354477 - Transação #250', NULL, 250, '2025-08-15 19:49:00', 27),
(122, 160, 59, NULL, 'credito', 0.49, 0.00, 0.49, 'Cashback liberado - Pagamento aprovado automaticamente', 250, NULL, '2025-08-15 19:50:15', 1167),
(123, 160, 59, NULL, 'uso', 5.00, 5.00, 0.00, 'Uso do saldo na compra - Código: KC25081517172115395 - Transação #251', NULL, 251, '2025-08-15 20:17:28', 27),
(124, 160, 59, NULL, 'credito', 0.25, 0.00, 0.25, 'Cashback liberado - Pagamento aprovado automaticamente', 251, NULL, '2025-08-15 20:18:04', 1168),
(125, 142, 38, NULL, 'credito', 5.00, 0.00, 5.00, 'Cashback da compra - Transação #262 (Pagamento #1173 aprovado)', 262, NULL, '2025-08-26 15:15:45', NULL),
(126, 142, 38, NULL, 'uso', 5.00, 5.00, 0.00, 'Uso do saldo na compra - Código: KC25082612164406653 - Transação #263', NULL, 263, '2025-08-26 15:16:56', 28);

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
(118, 9, 34, 270.85, 280.85, 10.00, '2025-07-09 21:04:36', '2025-08-15 12:11:06'),
(119, 139, 34, 1.25, 1.25, 0.00, '2025-08-14 02:24:27', '2025-08-15 13:41:03'),
(120, 140, 34, 1.00, 1.00, 0.00, '2025-08-14 02:30:50', '2025-08-14 02:30:50'),
(121, 9, 59, 0.50, 0.50, 0.00, '2025-08-15 14:46:51', '2025-08-15 14:46:51'),
(122, 160, 59, 0.25, 0.99, 5.25, '2025-08-15 14:48:50', '2025-08-15 20:18:04'),
(123, 142, 38, 0.00, 5.00, 5.00, '2025-08-26 15:15:45', '2025-08-26 15:16:56');

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
(1, 5.00, 5.00, 0.00, '2025-05-19 23:50:49');

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
  `data_aprovacao` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `lojas`
--

INSERT INTO `lojas` (`id`, `usuario_id`, `nome_fantasia`, `razao_social`, `cnpj`, `email`, `senha_hash`, `telefone`, `categoria`, `porcentagem_cashback`, `descricao`, `website`, `logo`, `status`, `observacao`, `data_cadastro`, `data_aprovacao`) VALUES
(34, 55, 'Kaua Matheus da Silva Lopes', 'Kaua Matheus da Silva Lopes', '59826857000108', 'kaua@syncholding.com.br', NULL, '(38) 99104-5205', 'Serviços', 10.00, 'Criador de Sites', 'https://syncholding.com.br', NULL, 'aprovado', NULL, '2025-05-25 19:17:34', '2025-05-25 19:17:49'),
(38, 63, 'KLUBE DIGITAL', 'Klube Digital Estratégia e Performance Ltda.', '18431312000115', 'acessoriafredericofagundes@gmail.com', NULL, '(34) 99335-7697', 'Serviços', 10.00, '', '', NULL, 'aprovado', NULL, '2025-06-07 16:11:42', '2025-06-08 19:36:33'),
(59, 159, 'Sync Holding', 'Kaua Matheus da Silva Lopes', '59826857000109', 'kauamathes123487654@gmail.com', NULL, '(34) 99800-2600', 'Serviços', 10.00, '', 'https://syncholding.com.br', NULL, 'aprovado', NULL, '2025-08-15 13:52:55', '2025-08-15 13:53:38');

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
(29, 59, '38705-376', 'Rua Francisco Braga da Mota', '146', 'Ap 101', 'jardim panoramico', 'Patos de Minas', 'MG');

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
(434, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,50 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-06-16 12:18:01', 0, NULL, ''),
(436, 9, 'Cashback Liberado!', 'Seu cashback de R$ 0,50 da loja Kaua Matheus da Silva Lopes foi liberado e está disponível para uso!', 'success', '2025-06-16 12:19:06', 0, NULL, ''),
(437, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,50 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-06-16 12:19:49', 0, NULL, ''),
(438, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,48 pendente da loja Kaua Matheus da Silva Lopes. Você usou R$ 0,50 do seu saldo nesta compra.', 'info', '2025-06-16 12:20:05', 0, NULL, ''),
(439, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,50 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-06-16 12:22:19', 0, NULL, ''),
(441, 9, 'Cashback Liberado!', 'Seu cashback de R$ 0,50 da loja Kaua Matheus da Silva Lopes foi liberado e está disponível para uso!', 'success', '2025-06-16 12:22:46', 0, NULL, ''),
(442, 9, 'Cashback Liberado!', 'Seu cashback de R$ 0,50 da loja Kaua Matheus da Silva Lopes foi liberado e está disponível para uso!', 'success', '2025-06-16 12:22:46', 0, NULL, ''),
(443, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,45 pendente da loja Kaua Matheus da Silva Lopes. Você usou R$ 1,00 do seu saldo nesta compra.', 'info', '2025-06-16 12:23:12', 0, NULL, ''),
(445, 9, 'Cashback Liberado!', 'Seu cashback de R$ 0,48 da loja Kaua Matheus da Silva Lopes foi liberado e está disponível para uso!', 'success', '2025-06-16 12:23:36', 0, NULL, ''),
(446, 9, 'Cashback Liberado!', 'Seu cashback de R$ 0,45 da loja Kaua Matheus da Silva Lopes foi liberado e está disponível para uso!', 'success', '2025-06-16 12:23:36', 0, NULL, ''),
(447, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,45 pendente da loja Kaua Matheus da Silva Lopes. Você usou R$ 0,93 do seu saldo nesta compra.', 'info', '2025-06-17 10:02:58', 0, NULL, ''),
(448, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,25 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-06-21 00:06:32', 0, NULL, ''),
(450, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 2,50 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-06-21 00:13:42', 0, NULL, ''),
(452, 9, 'Cashback Liberado!', 'Seu cashback de R$ 0,45 da loja Kaua Matheus da Silva Lopes foi liberado e está disponível para uso!', 'success', '2025-06-21 00:15:37', 0, NULL, ''),
(453, 9, 'Cashback Liberado!', 'Seu cashback de R$ 2,50 da loja Kaua Matheus da Silva Lopes foi liberado e está disponível para uso!', 'success', '2025-06-21 00:15:37', 0, NULL, ''),
(454, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,25 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-06-21 00:34:11', 0, NULL, ''),
(456, 9, 'Cashback Liberado!', 'Seu cashback de R$ 0,25 da loja Kaua Matheus da Silva Lopes foi liberado e está disponível para uso!', 'success', '2025-06-21 00:35:34', 0, NULL, ''),
(457, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,25 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-06-21 01:32:34', 0, NULL, ''),
(459, 9, 'Cashback Liberado!', 'Seu cashback de R$ 0,25 da loja Kaua Matheus da Silva Lopes foi liberado e está disponível para uso!', 'success', '2025-06-21 01:33:13', 0, NULL, ''),
(460, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,25 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-06-21 01:34:36', 0, NULL, ''),
(462, 9, 'Cashback Liberado!', 'Seu cashback de R$ 0,25 da loja Kaua Matheus da Silva Lopes foi liberado e está disponível para uso!', 'success', '2025-06-21 01:36:06', 0, NULL, ''),
(463, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,30 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-06-21 01:37:02', 0, NULL, ''),
(465, 9, 'Cashback Liberado!', 'Seu cashback de R$ 0,30 da loja Kaua Matheus da Silva Lopes foi liberado e está disponível para uso!', 'success', '2025-06-21 01:37:29', 0, NULL, ''),
(466, 73, 'Nova transação registrada', 'Você tem um novo cashback de R$ 5,00 pendente da loja KLUBE DIGITAL', 'info', '2025-07-01 17:52:20', 0, NULL, ''),
(467, 73, 'Nova transação registrada', 'Você tem um novo cashback de R$ 5,00 pendente da loja KLUBE DIGITAL', 'info', '2025-07-01 18:07:20', 0, NULL, ''),
(469, 73, 'Nova transação registrada', 'Você tem um novo cashback de R$ 5,00 pendente da loja KLUBE DIGITAL', 'info', '2025-07-07 15:27:08', 0, NULL, ''),
(470, 73, 'Nova transação registrada', 'Você tem um novo cashback de R$ 5,00 pendente da loja KLUBE DIGITAL', 'info', '2025-07-07 15:27:22', 0, NULL, ''),
(472, 73, 'Nova transação registrada', 'Você tem um novo cashback de R$ 500,00 pendente da loja KLUBE DIGITAL', 'info', '2025-07-07 16:45:45', 0, NULL, ''),
(473, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 5,00 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-07-09 20:54:21', 0, NULL, ''),
(475, 9, 'Cashback Liberado!', 'Seu cashback de R$ 5,00 da loja Kaua Matheus da Silva Lopes foi liberado e está disponível para uso!', 'success', '2025-07-09 21:04:36', 0, NULL, ''),
(476, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 5,00 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-07-17 23:28:48', 0, NULL, ''),
(478, 9, 'Cashback Liberado!', 'Seu cashback de R$ 5,00 da loja Kaua Matheus da Silva Lopes foi liberado e está disponível para uso!', 'success', '2025-07-17 23:31:33', 0, NULL, ''),
(479, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 4,60 pendente da loja Kaua Matheus da Silva Lopes. Você usou R$ 10,00 do seu saldo nesta compra.', 'info', '2025-07-17 23:48:09', 0, NULL, ''),
(481, 9, 'Cashback Liberado!', 'Seu cashback de R$ 4,60 da loja Kaua Matheus da Silva Lopes foi liberado e está disponível para uso!', 'success', '2025-07-17 23:48:38', 0, NULL, ''),
(482, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 5,00 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-07-18 00:08:52', 0, NULL, ''),
(484, 9, 'Cashback Liberado!', 'Seu cashback de R$ 5,00 da loja Kaua Matheus da Silva Lopes foi liberado e está disponível para uso!', 'success', '2025-07-22 16:26:29', 0, NULL, ''),
(485, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 5,00 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-07-30 20:32:39', 0, NULL, ''),
(487, 9, 'Cashback Liberado!', 'Seu cashback de R$ 5,00 da loja Kaua Matheus da Silva Lopes foi liberado e está disponível para uso!', 'success', '2025-07-30 20:33:24', 0, NULL, ''),
(488, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,50 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-07-30 23:41:37', 0, NULL, ''),
(490, 9, 'Cashback Liberado!', 'Seu cashback de R$ 0,50 da loja Kaua Matheus da Silva Lopes foi liberado e está disponível para uso!', 'success', '2025-07-30 23:42:58', 0, NULL, ''),
(491, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,25 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-08-01 14:19:21', 0, NULL, ''),
(493, 9, 'Cashback Liberado!', 'Seu cashback de R$ 0,25 da loja Kaua Matheus da Silva Lopes foi liberado e está disponível para uso!', 'success', '2025-08-01 14:20:07', 0, NULL, ''),
(494, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 250,00 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-08-01 15:04:00', 0, NULL, ''),
(496, 9, 'Cashback Liberado!', 'Seu cashback de R$ 250,00 da loja Kaua Matheus da Silva Lopes foi liberado e está disponível para uso!', 'success', '2025-08-01 15:04:50', 0, NULL, ''),
(497, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,50 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-08-01 15:21:21', 0, NULL, ''),
(498, 73, 'Nova transação registrada', 'Você tem um novo cashback de R$ 5,00 pendente da loja KLUBE DIGITAL', 'info', '2025-08-01 18:28:49', 0, NULL, ''),
(500, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,50 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-08-01 19:08:50', 0, NULL, ''),
(502, 9, 'Cashback Liberado!', 'Seu cashback de R$ 0,50 da loja Kaua Matheus da Silva Lopes foi liberado e está disponível para uso!', 'success', '2025-08-01 19:21:27', 0, NULL, ''),
(503, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,50 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-08-01 21:44:39', 0, NULL, ''),
(504, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,50 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-08-01 21:45:00', 0, NULL, ''),
(505, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 1,00 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-08-01 22:01:57', 0, NULL, ''),
(506, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 1,00 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-08-01 22:03:52', 0, NULL, ''),
(507, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,50 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-08-01 22:15:34', 0, NULL, ''),
(508, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,50 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-08-01 22:19:53', 0, NULL, ''),
(509, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 1,50 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-08-01 22:21:52', 0, NULL, ''),
(510, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,50 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-08-02 02:58:51', 0, NULL, ''),
(511, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 2,50 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-08-02 03:10:32', 0, NULL, ''),
(512, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 1,25 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-08-02 03:16:19', 0, NULL, ''),
(513, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,50 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-08-02 03:19:13', 0, NULL, ''),
(514, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 1.000,00 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-08-02 03:19:40', 0, NULL, ''),
(515, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,25 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-08-02 03:28:17', 0, NULL, ''),
(516, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,50 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-08-04 18:43:15', 0, NULL, ''),
(517, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,50 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-08-13 21:41:49', 0, NULL, ''),
(518, 138, 'Nova transação registrada', 'Você tem um novo cashback de R$ 270,00 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-08-14 02:21:00', 0, NULL, ''),
(519, 139, 'Nova transação registrada', 'Você tem um novo cashback de R$ 100,00 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-08-14 02:21:38', 0, NULL, ''),
(521, 139, 'Nova transação registrada', 'Você tem um novo cashback de R$ 1,00 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-08-14 02:23:21', 0, NULL, ''),
(523, 139, 'Cashback Liberado!', 'Seu cashback de R$ 1,00 da loja Kaua Matheus da Silva Lopes foi liberado e está disponível para uso!', 'success', '2025-08-14 02:24:27', 0, NULL, ''),
(524, 140, 'Nova transação registrada', 'Você tem um novo cashback de R$ 1,00 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-08-14 02:27:53', 0, NULL, ''),
(526, 140, 'Cashback Liberado!', 'Seu cashback de R$ 1,00 da loja Kaua Matheus da Silva Lopes foi liberado e está disponível para uso!', 'success', '2025-08-14 02:30:50', 0, NULL, ''),
(527, 55, 'Pagamento de saldo recebido', 'Você recebeu um pagamento de R$ 10,00 referente ao saldo de cashback usado pelos clientes.', 'success', '2025-08-14 02:32:10', 0, NULL, ''),
(528, 142, 'Nova transação registrada', 'Você tem um novo cashback de R$ 5,00 pendente da loja KLUBE DIGITAL', 'info', '2025-08-14 07:31:36', 0, NULL, ''),
(529, 143, 'Nova transação registrada', 'Você tem um novo cashback de R$ 5,00 pendente da loja KLUBE DIGITAL', 'info', '2025-08-14 08:47:17', 0, NULL, ''),
(530, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 30,00 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-08-14 11:09:31', 0, NULL, ''),
(531, 145, 'Nova transação registrada', 'Você tem um novo cashback de R$ 10,00 pendente da loja KLUBE DIGITAL', 'info', '2025-08-14 13:54:59', 0, NULL, ''),
(532, 146, 'Nova transação registrada', 'Você tem um novo cashback de R$ 2,50 pendente da loja KLUBE DIGITAL', 'info', '2025-08-14 15:03:35', 0, NULL, ''),
(533, 147, 'Nova transação registrada', 'Você tem um novo cashback de R$ 2,50 pendente da loja KLUBE DIGITAL', 'info', '2025-08-14 15:13:54', 0, NULL, ''),
(534, 148, 'Nova transação registrada', 'Você tem um novo cashback de R$ 5,00 pendente da loja KLUBE DIGITAL', 'info', '2025-08-14 17:12:01', 0, NULL, ''),
(535, 148, 'Status da transação atualizado', 'Sua transação de cashback na loja KLUBE DIGITAL foi aprovada.', 'success', '2025-08-14 18:42:19', 0, NULL, ''),
(536, 149, 'Nova transação registrada', 'Você tem um novo cashback de R$ 5,00 pendente da loja KLUBE DIGITAL', 'info', '2025-08-14 18:48:43', 0, NULL, ''),
(537, 150, 'Nova transação registrada', 'Você tem um novo cashback de R$ 500,00 pendente da loja KLUBE DIGITAL', 'info', '2025-08-14 18:50:14', 0, NULL, ''),
(538, 151, 'Nova transação registrada', 'Você tem um novo cashback de R$ 5,00 pendente da loja KLUBE DIGITAL', 'info', '2025-08-14 19:47:47', 0, NULL, ''),
(539, 152, 'Nova transação registrada', 'Você tem um novo cashback de R$ 5,00 pendente da loja KLUBE DIGITAL', 'info', '2025-08-14 19:50:35', 0, NULL, ''),
(540, 153, 'Nova transação registrada', 'Você tem um novo cashback de R$ 5,00 pendente da loja KLUBE DIGITAL', 'info', '2025-08-14 19:54:17', 0, NULL, ''),
(541, 154, 'Nova transação registrada', 'Você tem um novo cashback de R$ 5,00 pendente da loja KLUBE DIGITAL', 'info', '2025-08-14 19:58:21', 0, NULL, ''),
(542, 155, 'Nova transação registrada', 'Você tem um novo cashback de R$ 25,00 pendente da loja KLUBE DIGITAL', 'info', '2025-08-14 20:10:00', 0, NULL, ''),
(543, 156, 'Nova transação registrada', 'Você tem um novo cashback de R$ 5,00 pendente da loja KLUBE DIGITAL', 'info', '2025-08-14 20:13:42', 0, NULL, ''),
(544, 157, 'Nova transação registrada', 'Você tem um novo cashback de R$ 5,00 pendente da loja KLUBE DIGITAL', 'info', '2025-08-14 20:15:59', 0, NULL, ''),
(545, 158, 'Nova transação registrada', 'Você tem um novo cashback de R$ 25,00 pendente da loja KLUBE DIGITAL', 'info', '2025-08-15 01:03:05', 0, NULL, ''),
(546, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 5,00 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-08-15 12:09:41', 0, NULL, ''),
(548, 9, 'Cashback Liberado!', 'Seu cashback de R$ 5,00 da loja Kaua Matheus da Silva Lopes foi liberado e está disponível para uso!', 'success', '2025-08-15 12:11:06', 0, NULL, ''),
(549, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,25 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-08-15 13:40:04', 0, NULL, ''),
(550, 139, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,25 pendente da loja Kaua Matheus da Silva Lopes', 'info', '2025-08-15 13:40:30', 0, NULL, ''),
(552, 139, 'Cashback Liberado!', 'Seu cashback de R$ 0,25 da loja Kaua Matheus da Silva Lopes foi liberado e está disponível para uso!', 'success', '2025-08-15 13:41:03', 0, NULL, ''),
(553, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,25 pendente da loja Sync Holding', 'info', '2025-08-15 13:54:22', 0, NULL, ''),
(556, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,50 pendente da loja Sync Holding', 'info', '2025-08-15 14:00:04', 0, NULL, ''),
(558, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,50 pendente da loja Sync Holding', 'info', '2025-08-15 14:12:13', 0, NULL, ''),
(561, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,25 pendente da loja Sync Holding', 'info', '2025-08-15 14:19:21', 0, NULL, ''),
(563, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,50 pendente da loja Sync Holding', 'info', '2025-08-15 14:34:37', 0, NULL, ''),
(564, 9, 'Nova transação registrada', 'Você tem um novo cashback de R$ 5,00 pendente da loja Sync Holding', 'info', '2025-08-15 14:45:55', 0, NULL, ''),
(567, 9, 'Cashback Liberado!', 'Seu cashback de R$ 0,50 da loja Sync Holding foi liberado e está disponível para uso!', 'success', '2025-08-15 14:46:51', 0, NULL, ''),
(568, 160, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,25 pendente da loja Sync Holding', 'info', '2025-08-15 14:48:14', 0, NULL, ''),
(570, 160, 'Cashback Liberado!', 'Seu cashback de R$ 0,25 da loja Sync Holding foi liberado e está disponível para uso!', 'success', '2025-08-15 14:48:50', 0, NULL, ''),
(571, 161, 'Nova transação registrada', 'Você tem um novo cashback de R$ 5,45 pendente da loja KLUBE DIGITAL', 'info', '2025-08-15 17:03:01', 0, NULL, ''),
(572, 160, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,49 pendente da loja Sync Holding. Você usou R$ 0,25 do seu saldo nesta compra.', 'info', '2025-08-15 19:49:00', 0, NULL, ''),
(574, 160, 'Cashback Liberado!', 'Seu cashback de R$ 0,49 da loja Sync Holding foi liberado e está disponível para uso!', 'success', '2025-08-15 19:50:15', 0, NULL, ''),
(575, 160, 'Nova transação registrada', 'Você tem um novo cashback de R$ 0,25 pendente da loja Sync Holding. Você usou R$ 5,00 do seu saldo nesta compra.', 'info', '2025-08-15 20:17:28', 0, NULL, ''),
(577, 160, 'Cashback Liberado!', 'Seu cashback de R$ 0,25 da loja Sync Holding foi liberado e está disponível para uso!', 'success', '2025-08-15 20:18:04', 0, NULL, ''),
(578, 142, 'Nova transação registrada', 'Você tem um novo cashback de R$ 50,00 pendente da loja KLUBE DIGITAL', 'info', '2025-08-16 12:23:18', 0, NULL, ''),
(579, 142, 'Nova transação registrada', 'Você tem um novo cashback de R$ 500,00 pendente da loja KLUBE DIGITAL', 'info', '2025-08-16 12:28:33', 0, NULL, ''),
(583, 163, 'Nova transação registrada', 'Você tem um novo cashback de R$ 50,00 pendente da loja KLUBE DIGITAL', 'info', '2025-08-16 16:54:08', 0, NULL, ''),
(584, 164, 'Nova transação registrada', 'Você tem um novo cashback de R$ 50,00 pendente da loja KLUBE DIGITAL', 'info', '2025-08-16 16:57:57', 0, NULL, ''),
(585, 165, 'Nova transação registrada', 'Você tem um novo cashback de R$ 50,00 pendente da loja KLUBE DIGITAL', 'info', '2025-08-16 16:59:00', 0, NULL, ''),
(586, 166, 'Nova transação registrada', 'Você tem um novo cashback de R$ 25,00 pendente da loja KLUBE DIGITAL', 'info', '2025-08-16 17:18:18', 0, NULL, ''),
(587, 142, 'Nova transação registrada', 'Você tem um novo cashback de R$ 10,00 pendente da loja KLUBE DIGITAL', 'info', '2025-08-17 17:59:50', 0, NULL, ''),
(589, 167, 'Nova transação registrada', 'Você tem um novo cashback de R$ 10,00 pendente da loja KLUBE DIGITAL', 'info', '2025-08-18 16:54:39', 0, NULL, ''),
(590, 168, 'Nova transação registrada', 'Você tem um novo cashback de R$ 10,00 pendente da loja KLUBE DIGITAL', 'info', '2025-08-21 20:04:00', 0, NULL, ''),
(591, 169, 'Nova transação registrada', 'Você tem um novo cashback de R$ 2,50 pendente da loja KLUBE DIGITAL', 'info', '2025-08-24 17:11:59', 0, NULL, ''),
(592, 142, 'Nova transação registrada', 'Você tem um novo cashback de R$ 5,00 pendente da loja KLUBE DIGITAL', 'info', '2025-08-26 15:02:28', 0, NULL, ''),
(594, 142, 'Cashback disponível!', 'Seu cashback de R$ 5,00 da loja KLUBE DIGITAL está disponível.', 'success', '2025-08-26 15:15:45', 0, NULL, ''),
(595, 63, 'Pagamento aprovado', 'Seu pagamento de comissão no valor de R$ 10,00 foi aprovado.', 'success', '2025-08-26 15:15:45', 0, NULL, ''),
(596, 142, 'Nova transação registrada', 'Você tem um novo cashback de R$ 4,75 pendente da loja KLUBE DIGITAL. Você usou R$ 5,00 do seu saldo nesta compra.', 'info', '2025-08-26 15:16:56', 0, NULL, '');

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
(1143, 34, NULL, 10.00, 'pix_mercadopago', '', '', ' - Device ID: device_24181a1d4c5921807f5260aa709e5bcd', 'Pagamento PIX aprovado automaticamente via Mercado Pago - ID MP: 117508948823', '2025-07-09 21:04:01', '2025-07-09 21:04:36', 'aprovado', NULL, NULL, NULL, NULL, '117508948823', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c33346622520400005303986540510.005802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter1175089488236304218C', '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', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(1144, 34, NULL, 10.00, 'pix_mercadopago', '', '', ' - Device ID: device_4d2cb0bb3d19ba0297797d4d012dce11', 'Pagamento PIX aprovado automaticamente via Mercado Pago - ID MP: 118981319912', '2025-07-17 23:29:07', '2025-07-17 23:31:33', 'aprovado', NULL, NULL, NULL, NULL, '118981319912', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c33346622520400005303986540510.005802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter1189813199126304AD77', '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', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(1145, 34, NULL, 9.20, 'pix_mercadopago', '', '', ' - Device ID: device_9af32689c072383299b3e38800e8dd54', 'Pagamento PIX aprovado automaticamente via Mercado Pago - ID MP: 118477443891', '2025-07-17 23:48:20', '2025-07-17 23:48:38', 'aprovado', NULL, NULL, NULL, NULL, '118477443891', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c3334662252040000530398654049.205802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter11847744389163040F21', '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', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(1146, 34, NULL, 10.00, 'pix_mercadopago', '', '', ' - Device ID: device_3929f2841934c2e8f1e267a39b090aff', 'Pagamento PIX aprovado automaticamente via Mercado Pago - ID MP: 119506155656', '2025-07-22 16:21:43', '2025-07-22 16:26:29', 'aprovado', NULL, NULL, NULL, NULL, '119506155656', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c33346622520400005303986540510.005802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter1195061556566304BC68', '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', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(1147, 34, NULL, 10.00, 'pix_mercadopago', '', '', ' - Device ID: device_c4cfaef620b2ea1994e87e3ab7f73e69', 'Pagamento PIX aprovado automaticamente via Mercado Pago - ID MP: 120409409758', '2025-07-30 20:32:53', '2025-07-30 20:33:24', 'aprovado', NULL, NULL, NULL, NULL, '120409409758', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c33346622520400005303986540510.005802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter1204094097586304D2D5', 'iVBORw0KGgoAAAANSUhEUgAABWQAAAVkAQAAAAB79iscAAANhUlEQVR4Xu3XS5pcuQqF0ZiB5z/LO4Oo77JBIFBmdVJ2HNe/G2k9AK0TPb/eD8r/Xv3kk4P2XtDeC9p7QXsvaO8F7b2gvRe094L2XtDeC9p7QXsvaO8F7b2gvRe094L2XtDeC9p7QXsvaO8F7b2gvRe094L2XtDeC9p7QXsvaO8F7b2gvRe094L2XtDeC9p7QXsvaO8F7b2gvZeqffX8UoVKcttK7F+vyz8xr61yQBbXyTGvxm69dy2/KEO7d6BFixYtWlWgRasKtGhVgRatKj5Vm+exzZ561WSnL9gAlrxoxTm09kbQ1qBV0KJV0KJV0KJV0KJV0KJVHq7NISdtKrwnV3YbvfXtHLC1ja+ytHcbHi3aaFvLY1lMQYs2kiVoI2jRKmjRKmjRKmg/VetPtHFb8u2sy6GWrGslvk4j2i1o+4UHrZ1tyTq0aNHOMrSrxNdo82IL2n7hQWtnW7IOLdrfo/WyLx7LD2oDYkr70jql3b4OUwZjLb8vQ7vfvg5TBmMtvy9Du9++DlMGYy2/L0O7374OUwZjLb8vQ7vfvg5TBmMtvy9Du9++DlMGYy2/L0O7374OUwZjLb8vQ7vfvg5TBmMtvy9Du9++DlMGYy2/L0O7374OUwZjLb8v+0zt2Aalruw2puc2n2hnvt20Y162nRgWtGgVtGgVtGgVtGgVtGgVtGiVJ2tbfnnt7/4zGWh/6s9koP2pP5OB9qf+TAban/ozGWh/6s9koP2pP5OB9qf+TAban/ozGWh/6s9kPF57Tvxfb++Ks1f9n6BdZJ3fbsXtzDssOf7LoEWroEWroEWroEWroEWroEWrPFkbrePZX1XW3B4ribqW8X22zddi1Bqr1FsL2hm0aBW0aBW0aBW0aBW0aBW0j9RuFPs3Ke3ZZmzFXhJ19daKG6+9di7Zd2Xw6EdbghatghatghatghatghatgvZjtDk7t/4FlvYZ+ceKN88o2Z7Nknyj/Q71NoMWrYIWrYIWrYIWrYIWrYIWrfJk7a9V65ev8XbWTVRNfprlC3LW+9m2yq0HrQUtWgUtWgUtWgUtWgUtWgXto7WWHBKoxqtQy/yMHNA+6DTASttqFPvZWqJdt2jjibpCixbtaYCVttUo9rO1RLtu0cYTdYUWLdrTACttq1HsZ2uJdt3+17WZ2mWJrhPeO+rM44v/VtKejKD9NwrayGhF20vQokWLFi3aWYIWLdoP09aG1p8vWqK/nsVFk9WObcqZvP0EI2jRKmjRKmjRKmjRKmjRKmjRKs/VjrIgt7fHs1ncHgtFG7XWqstti0/JoEWroEWroEWroEWroEWroEWrPFfrNfHiMGZdQtO9Fef2/BNER31taxtTLGhzigXtG60F7RutBe0brQXtG60F7RutBe37adp19MXZvKgz87YVT22uxpcO3kZGiza25S6OvjibF2i3oEWroEWroEWroEWroP1z2uxPRcZLbLUp8vak+LIkL/w2vvT8BWjRKmjRKmjRKmjRKmjRKmjRKs/V7udFVt35LTb9VBKpU7avH1/afpvtI2vQRtDmGi1aBS1aBS1aBS1aBS1a5WnafCKfzUlRUuss26qVnI3bZ5xv51ehbSXfeN5o0aK1oEWroEWroEWroEWrfLQ236mP5W1u851I7bVMRf36Ezm2+VANWrQKWrQKWrQKWrQKWrQKWrTKk7XR1Rp827IVN7cnAa/69WXIumjv+lUjo82gjaBFq6BFq6BFq6BFq6BFqzxL6w3Zv6Xit5Ic3N7xOkt4fGttbV47y3lJRptbq7OgRaugRaugRaugRaugRaugfZLW39ke89r4jNqfgBPltX9LxEdVQL84fcYqzrX3WdDqDC1anaFFqzO0aHWGFq3O0KLVGdonaLPsS8/2WP2qHNxK4iKL20XdxjfXX6l+2lqiRetBi1ZBi1ZBi1ZBi1ZBi1Z5lrai3mt6etoXRImvMltbvnj+tEhetFu09SW0Clq0Clq0Clq0Clq0Clq0ysO1Xpuo7WyQw336yFGXozLZtr1bfwe0aHWBFq0u0KLVBVq0ukCLVhdo0eri79C+12OZ1rW92Irz1pNkS5O1s/lurfPiunufu/IC7XEUWrSlt9Z5cd29z115gfY4Ci3a0lvrvLju3ueuvEB7HIUWbemtdV5cd+9zV16gPY5C+ye11ZPGbN3cPil5UTK+z7Kh8o2c0jpOFrTjRQvaWdEoWZfvtJLxogXtrGiUrMt3Wsl40YJ2VjRK1uU7rWS8aEE7Kxol6/KdVjJetKCdFY2SdflOKxkvWtDOikbJunynlYwXLWhnRaNkXb7TSsaLFrSzolGyLt9pJeNFC9pMvm3jZokf2DtBrorTB9k22lpd+9wsRutnU4HWzzOzq5X4AVq0Clq0Clq0Clq0Clq0yp/UxvlYbS/W/i35mG/zRVvFp506zr1bMdrxYq7QnlZo945zL9rI+cVcoT2t0O4d5160kfOLuUJ7WqHdO869aCPnF3OF9rT6VK3/yWza8eK8qMmvyouGivG+zZx+EbQZtBG0EbRo9zq0o2uiVn+/qEEbQRtBi3avQzu6Jmr194satHVXuupZXsx32vS6silzQDN6bFQzjpJ9h3YMQFvP0E4KWgUtWgUtWgUtWgUtWuWPa31SvN0Aodo/aIzTmSfacoqvMhuv/gRbrwctWgUtWgUtWgUtWgUtWgUtWuXh2nxsUDIxbvAsCdjc52LL9u74leq8tUS7n52KLWgzaHvxG+0BgDbmrSXa/exUbEGbQduL32gPALQxby3R7menYsvfr7VYea68YVu1Z70xvsBPM60timvH6VviAi1atGjrBVq0aNHWC7Ro0aKtF3+HtpXV5DtbWt16SVPS0z7NV8HzsyTntgVtTEGb63xn1KJFq6BFq6BFq6BFq6BFq3ygttbGi/5nm56Tvpq5UQJai/PrT7ytbZ3axb5D69OGZ1uNW7Ro0aoOLVrVoUWrOrRoVYcWrer+nNYvtzJfN16Q/TaTZ9nxOrgjQzs/owZtrNpQtGjRokXryX5fxRlaBS1aBS1aBe1Hai05ZLxomdD6YtS1i9OZT2m/Q2RN33xotyneNs/Q1jML2rXNrOlo0XrqKdptirfNM7T1zIJ2bTNrOlq0nnqKdpvibfPsv6XNcd5qg7/gndNebG35hiWm1IfmbR2KNuehjWQrWrRoPfUNC1q0Clq0Clq0CtqP1p6fCHye+RN5m2fb295x6j39DjbPVufxa4l2dZx60doFWrS6QItWF2jR6gItWl2gRauLj9QmagPU/hjS6rKkneXQVudF25njs2OrQ4s2ghatghatghatghatghat8lztOirJ4/otcVG/yhJP5LOtzc/agIEqA/aLfW9HJXmMFq2CFq2CFq2CFq2CFq2CFq3yaVofErV+Nse122zzs6BUvCXbto4sHsZRkuscgnYbunVkMVq0KkaLVsVo0aoYLVoVo0WrYrSfoa2X+cQcnF/g2bZp9PL5GavxNX6MVowWbRmVQYs2bvcdWrTaoUWrHVq02qFFq91ztN6aq3i2ek7Q6Mht7c26/IwYte63C9vG0Bq0FrRoFbRoFbRoFbRoFbRoFbQP1tbaHJeezBiyySJ+kb3zx6hvRPH4lvoTrKXtlDHpjRYtWrRofYsWrbZo0WqLFq22aD9a22QNUOvyLAYnuT1bvyUAPmVraz/BqLOgRaugRaugRaugRaugRaugRas8V2sRQ4lxY/BrR0VaW73atKeS8xstaCcFrcULt5nnSWj7Gy1oJwWtxQu3medJaPsbLWgnBa3FC7eZ50lo+xstaCcFrcULt5nnSX9Ym5Na66nfS2Llt7Oj1bWO2mY5/TbRhhZtBC3auF1LtD7vy47aZkFrxfPtUwdaizej/aqjtlnQWvF8+9SB1uLNaL/qqG2Wv1xr/dvWz2KmH8TM3DZF/XBry1WkDm29loRa0GbQRrKobf0MLVqdoUWrM7RodYYWrc7QotXZZ2lPSVQ9ew13xW+8OiBvt4/MnHprCdr5ItpT0EZd9GZOvWjRotWLaNHqRbRo9SJatHrx92vXhG1SDPaS2qqSrM+S84Wttg/Pjlxlb7tFizaCFq2CFq2CFq2CFq2CFq3yZG2exzafqKttuq/m9OzIbf4EObQa2+TtNbTjDO0bbXTkFm1ubRhatGj/v0aLFu3a2jC0aD9d2/rzwlfzNmfWAVFXtXGbZ9XdeludF+carW69F60FLVoFLVoFLVoFLVoFLVrlmdrsyrfPq6yzVcw7vBhtczvejbNVvJZo/dYfsKBFq6BFq6BFq6BFq6BFq6B9qnb743U2acqyt33BIFtHFHudJfB+mxd+m2u0aBW0aBW0aBW0aBW0aBW0aJUHatv2bMy6QI1n47Z2bKvaO9s8GwjtutItWgtatApatApatApatApatMrTtC35RGzL5Z6k+Nana1XPbDt/Fk90nMlobevT0SrreitDixatb9Gi1RYtWm3RotUW7UdrPz9o7wXtvaC9F7T3gvZe0N4L2ntBey9o7wXtvaC9F7T3gvZe0N4L2ntBey9o7wXtvaC9F7T3gvZe0N4L2ntBey9o7wXtvaC9F7T3gvZe0N4L2ntBey9o7wXtvaC9F7T3gvZe0N4L2nt5mPYfZcVpl5xroPAAAAAASUVORK5CYII=', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(1148, 34, NULL, 1.00, 'pix_mercadopago', '', '', ' - Device ID: device_9270c6b3d24be049d0ae315db59fa5f3', 'Pagamento PIX aprovado automaticamente via Mercado Pago - ID MP: 119921101137', '2025-07-30 23:41:46', '2025-07-30 23:42:58', 'aprovado', NULL, NULL, NULL, NULL, '119921101137', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c3334662252040000530398654041.005802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter11992110113763048085', 'iVBORw0KGgoAAAANSUhEUgAABWQAAAVkAQAAAAB79iscAAANlklEQVR4Xu3XS3YkOa5FUZtBzn+WOQO9FfjwAiBd9RpipSzr3IYHSRDgNvXi+XpR/n7myW8O2ntBey9o7wXtvaC9F7T3gvZe0N4L2ntBey9o7wXtvaC9F7T3gvZe0N4L2ntBey9o7wXtvaC9F7T3gvZe0N4L2ntBey9o7wXtvaC9F7T3gvZe0N4L2ntBey9o7wXtvaC9F7T3gvZeqvaZ+SvOovqXN5Sq2qxQfyy50pUYqsl5Vqsj+RBatBm0aHPeWh6v2b9RRYvWq2jRehUtWq+iRevV36bVedtuT+iKbfML6tloG5+Wicah/cBAW1doT+dti7Zs0aJFi7Zu0aJFi7Zu0f5Krfr7tX1me6de0Sh9n12xgm21aoXRdmCs5ffX/AwtWj9Di9bP0KL1M7Ro/QwtWj9Di9bPfr22UU5X+rgEjLanypQx5eMbEbRoPWjRetCi9aBF60GL1oMWreffpdU7scp7sVLVzvJeRI+dPuNZ8zIaHwdo0XrQovWgRetBi9aDFq0HLVrPv0s7toeGNkTQ1hHbjwCtPkxR0I6O2D5oD+e5RYsW7erV6sMUBe3oiO2D9nCeW7Ro0a5erT5MUdCOjtg+aNf5SNP+F392Btqf+tkZaH/qZ2eg/amfnYH2p352Btqf+tkZaH/qZ2eg/amfnYH2p352Btqf+tkZr9d+E/tvXsa2cWaT9D9LS07fqrtnPeBn2p6DFq0HLVoPWrQetGg9aNF60KL1vFnbUNFvsnbltDpH2ufwker98H31zIJWQavsz6LNgnrR5pXT6hy0yv4s2iyoF21eOa3OQavsz6LNgnrR5pXT6hy0yv7sb9aOpKe25rORv+qZOqOgd0ZBHZLtrx3+VHU3svfrnQhatB60aD1o0XrQovWgRetBi9bzT2pba00+YbGrKtSt0rQ6Pn3VifwJv5a2K101aL2AFq0X0KL1Alq0XkCL1gto0XoB7a/UfkWxvvNxiL2dV+SJwvhcS07WSczLy3pjE0RhLT1oM2jRetCi9aBF60GL1oMWrQftS7TRonEnhdK+oEao9mIMEEUfrns5oH4GWgWtBy1aD1q0HrRoPWjRetCi9bxZ26A6i9UT/ds7qR2FUdWVutUUS/LGB62q1mhLVVfqVlMsaNF60KL1oEXrQYvWgxatB+3v0FpO5ExsdcXOdkBkfNWe7bXUjisRtApanWdX7VfhQYs2E1u0aH2LFq1v0aL1LVq0vv0N2o8ZM8cqUOPH0s62Adk7oLU6ghatBy1aD1q0HrRoPWjRetCi9bxXq/7YypPVYRxXtnwYpWrEtHlPGVW0NR9GqRpBm626d1YoH0apGkGbrbp3VigfRqkaQZutundWKB9GqRpBm626d1YoH0apGkGbrbp3VigfRqkaQZutundWKB9GqRpBm626d1YoH0apGkGbrbp3VigfRqka+fdr6/QcHCsrtOlaxT1b5RdsHc95G915OUapqitxr+/QRk7b6EaL1oMWrQctWg9atB60aD1of6E2KOk5z2w/6hjTx7fEPMv+UH0tq2MKWk1Du5a2m0PQfp0eqq+hRYsWbX0NLVq0aOtraH+H1vLh7W3muDwU+xTLavScvz4LaLfH0KJFG0GL1oMWrQctWg9atJ43a1tOz2pSjLN7lpxZH8sMdwW07VZok9GizaBF60GL1oMWrQctWg9atJ6Xa+sNm57GWGV1DI5khy7PG3+yofZv+TR+HMiD1oMWrQctWg9atB60aD1o0XrQvkNrdzMaUlcZ3asZj+kjn/otpynjtUgdtZYetCVoP/a3q2jRZnSvBm2eraUHbQnaj/3tKlq0Gd2rQZtna+lBW/K/qtU70aqzjB47XdYT2mpKrJT9q8aVVcqgbVtNiZWCFq0HLVoPWrQetGg9aNF60P5erSKKjUtFnW7ZeVXWvjTu6YqGWkEd7dNibUGL1oMWrQctWg9atB60aD1o0Xreq7XUG3/HdBUGQNV6uXWML/hP26/6B6qnFrRoPWjRetCi9aBF60GL1oMWree9WnUFSl2nH0tbqaOmfZ/OLLU6jPtnoEWbQYs2z7TehqBdZxa05x/LaTraPNN6G4J2nVnQnn8sp+lo80zrbQjadWb5n9GOd0ZBVU2Kg/ZO/VLbZket2pnlVGjQSkaLNi+vpe1KRgHt3tbGq7Aur6XtSkYB7d7WxquwLq+l7UpGAe3e1sarsC6vpe1KRgHt3tbGq7Aur6XtSkYB7d7WxquwLq+l7UpGAe3e1sarsC6vpe1KRgHt3tbGq7Aur6XtSkYB7d7WxquwLq9lnjeyoiux1Tv7VqltJ4C2p3dbG9raYwW1oT11PWgz37yLdt8qtQ3tqetBm/nmXbT7VqltaE9dD9rMN++i3bdKbUN76np+nzZetK42WON0tZ4N6N7bX/Rsr413M6vRrvQdWrS+Q4vWd2jR+g4tWt+hRes7tGh99x5tlNsTo6EOyWfrvXxxfKQyeP+Pd/uXruV/6KpX7Azt4V209QytBy1aD1q0HrRoPWjRetD+d7TrKMd9da2m7zN1ZfSeB7SoWnv1pWjzCtoMWrR5ZS111LrauDhDO3vRqppX0GbQos0ra6mj1tXGxRna2YtW1byCNtO1WayzMrpVTzXpVM0rWsUbz5957eu/6UV7quYVrdDOpj/RrXqKtgRtS63mFa3QzqY/0a16irYEbUut5hWt0M6mP9Gteoq2BG1LreYVrdDOpj/RrXr6a7Q62wbnvbrKnN7WABXG2bY9naFFm9u+87djXJ6hnWfb9nSGFm1u+87fjnF5hnaebdvTGVq0ue07fzvG5RnaebZtT2do0ea27/ztGJdnb9BqZqy+DpQTVB2atxeiw9KerFGvBS1aD1q0HrRoPWjRetCi9aBF63m5Vk+MbR/THtN2oPLKcI8vEL5udaagRZvVvkOL1ndo0foOLVrfoUXrO7RoffcebZ1kadvVMKE1uqxvzsn169uV8ZGR8VUWtA9aC9oHrQXtg9aC9kFrQfugtaB90Fperh2xxvp2auvbra0a1WsrXRlbaRu+Vi1o0XrQovWgRetBi9aDFq0HLVrPm7VKdsXgfHGb1HK6F4V9fOW1gv3rovnHQGtBi9aDFq0HLVoPWrQetGg9aN+vbZ46U11fa6tx7d5JW78lEwXF3h0/tap1TEDrQYvWgxatBy1aD1q0HrRoPWjfpNWNeNaS0Hqmx0TOKx8/Y7tiye33bWjRZtCi9aBF60GL1oMWrQctWs+btaOojC9oP8p426Le6rHCKa1tu4cWbVa1PgEiaI9Bi9aDFq0HLVoPWrQetL9LGxezK6o6y20tpPHEO72ttlhlVYXY6i+CFq0HLVoPWrQetGg9aNF60KL1vFermbVV42pXUcQ9XdEUfb0Kpz9Gm1I/187Q6gpaD1q0HrRoPWjRetCi9aBF63m5VquPb9crmXFmm1g1T42M7S8SK01B285sEyu0sUOL1ndo0foOLVrfoUXrO7Roffce7ejazjK1oB8rSPvR+NU/w7b6s+gvchqANlfbY5l6xbZoM7WAFi1atLWAFi1atLWAFu0/r7UImjMHuaLs3v7Y9qKebdttcjvTdvWuJVq0kWhBi9aDFq0HLVoPWrQetGg9L9Guo3bWttskvbPfiwyeRR/ULmtUrFoVre5F0Cpo0eYRWrR+hBatH6FF60do0frRS7S6Zqsha9ua9IycjLFt31Iva0q+hhbtbEOroF1L23nQPmgtaB+0FrQPWgvaB63lHdqvfuOkGO+MmSfe6U/QLuuh6G2Px5W4V3df2zto0VoRLVovokXrRbRovYgWrRfRovXiS7SZSm5v163Iz/aieFHdZeqNqEOfZkFrU9Ci9Slo0foUtGh9Clq0PgUtWp+C9sXacXfMPF/Jaj4UqZfbdnSc39Dj+itZ0D5oLWgftBa0D1oL2getBe2D1oL2QWt5vbbNjFjXKOSk7VtG9ivDWOeNtlxF0I7sV9CiRYu2XkGLFi3aegUtWrSv0J4yZBFtR+HrG9n4vnpvHxW9I2jRetCi9aBF60GL1oMWrQctWs97tXajps2MKzob5Cdk1ZOpvbmtly3tM0bb6lhLtKug3tyiRYv2QYs2g3Zt0aJF+6BFm/nl2pMss023M8vfGyAGWPR9dtZ4NXtvK/8JWrQetGg9aNF60KL1oEXrQYvW83LteGJ7uw2J7dB+l3H59CeInL4K7XdBi9aDFq0HLVoPWrQetGg9aF+trV05PTpG8rLcmhxpA7Z57UmdrctrOWVo54BtHlq0f4IWrQctWg9atB60aD1of7PWuv5erZk4az/bgHZP2/rhOhtVgdCiRYu2gtCiRYu2gtCiRYu2gv412rGNMbXBt7WQ36Jnz5+rXnmsmhm9cYZW1SygXUu0aA/nuVV/BO3K6I0ztKpmAe1aokV7OM+t+iNoV0ZvnKFVNQto1zLvKm3mMOadRRkZbruXWaWsjm+2tAHrTPUyo1/Tdls9aC1oH7QWtA9aC9oHrQXtg9aC9kFrQfv8Y9rfH7T3gvZe0N4L2ntBey9o7wXtvaC9F7T3gvZe0N4L2ntBey9o7wXtvaC9F7T3gvZe0N4L2ntBey9o7wXtvaC9F7T3gvZe0N4L2ntBey9o7wXtvaC9F7T3gvZe0N4L2ntBey9o7wXtvbxM+3+LNLNAyCxu6gAAAABJRU5ErkJggg==', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(1149, 34, NULL, 0.50, 'pix_mercadopago', '', '', ' - Device ID: device_2c0cfc02c6de8ddee56ed0924f96b0ab', 'Pagamento PIX aprovado automaticamente via Mercado Pago - ID MP: 120107345073', '2025-08-01 14:19:30', '2025-08-01 14:20:07', 'aprovado', NULL, NULL, NULL, NULL, '120107345073', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c3334662252040000530398654040.505802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter120107345073630432FC', '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', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(1150, 34, NULL, 500.00, 'pix_mercadopago', '', '', ' - Device ID: device_9d2d768904f504a9abbf6fe0e9751dde', 'Pagamento PIX aprovado automaticamente via Mercado Pago - ID MP: 120112327973', '2025-08-01 15:04:10', '2025-08-01 15:04:50', 'aprovado', NULL, NULL, NULL, NULL, '120112327973', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c333466225204000053039865406500.005802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter1201123279736304E9C7', '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', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(1151, 38, NULL, 10.00, 'pix_mercadopago', '', '', ' - Device ID: device_ecdaadb5c17b3cc34df49d1d597be6eb', ' - PIX rejeitado/cancelado no MP: cancelled', '2025-08-01 18:30:16', NULL, 'pix_aguardando', NULL, NULL, NULL, NULL, '120138425375', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c33346622520400005303986540510.005802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter1201384253756304F3E1', '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', 'cancelled', NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `pagamentos_comissao` (`id`, `loja_id`, `criado_por`, `valor_total`, `metodo_pagamento`, `numero_referencia`, `comprovante`, `observacao`, `observacao_admin`, `data_registro`, `data_aprovacao`, `status`, `pix_charge_id`, `pix_qr_code`, `pix_qr_code_image`, `pix_paid_at`, `mp_payment_id`, `mp_qr_code`, `mp_qr_code_base64`, `mp_status`, `openpix_charge_id`, `openpix_qr_code`, `openpix_qr_code_image`, `openpix_correlation_id`, `openpix_status`, `openpix_paid_at`) VALUES
(1152, 34, NULL, 1.00, 'pix_mercadopago', '', '', ' - Device ID: device_1efbf2a595de323be32643bc09644950', 'Pagamento PIX aprovado automaticamente via Mercado Pago - ID MP: 120661271112', '2025-08-01 19:17:18', '2025-08-01 19:21:27', 'aprovado', NULL, NULL, NULL, NULL, '120661271112', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c3334662252040000530398654041.005802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter1206612711126304975F', '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', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(1153, 34, NULL, 200.00, 'pix_mercadopago', '', '', ' - Device ID: device_a6c5ccb389793456547cbfe5a696a96c', ' - PIX rejeitado/cancelado no MP: cancelled', '2025-08-14 02:22:51', NULL, 'pix_aguardando', NULL, NULL, NULL, NULL, '122201386314', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c333466225204000053039865406200.005802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter1222013863146304DD51', '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', 'cancelled', NULL, NULL, NULL, NULL, NULL, NULL),
(1154, 34, NULL, 2.00, 'pix_mercadopago', '', '', ' - Device ID: device_a4f14bf6232fd8f9ec00f222dacd5b5f', 'Pagamento PIX aprovado automaticamente via Mercado Pago - ID MP: 122201152572', '2025-08-14 02:23:56', '2025-08-14 02:24:27', 'aprovado', NULL, NULL, NULL, NULL, '122201152572', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c3334662252040000530398654042.005802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter12220115257263049997', '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', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(1155, 34, NULL, 2.00, 'pix_mercadopago', '', '', ' - Device ID: device_e8d135d12a2a01c5599a252de1b2a8d2', 'Pagamento PIX aprovado automaticamente via Mercado Pago - ID MP: 122200655362', '2025-08-14 02:30:12', '2025-08-14 02:30:50', 'aprovado', NULL, NULL, NULL, NULL, '122200655362', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c3334662252040000530398654042.005802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter12220065536263047EB9', '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', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(1156, 34, NULL, 10.00, 'pix_mercadopago', '', '', ' - Device ID: device_4ee37850b0eb1974c39ba8063bca99d1', 'Pagamento PIX aprovado automaticamente via Mercado Pago - ID MP: 122430205382', '2025-08-15 12:10:06', '2025-08-15 12:11:06', 'aprovado', NULL, NULL, NULL, NULL, '122430205382', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c33346622520400005303986540510.005802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter1224302053826304F06C', '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', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(1157, 34, NULL, 0.50, 'pix_mercadopago', '', '', ' - Device ID: device_2a0be59746addd8a3b877c278100fb12', 'Pagamento PIX aprovado automaticamente via Mercado Pago - ID MP: 122439469480', '2025-08-15 13:40:40', '2025-08-15 13:41:03', 'aprovado', NULL, NULL, NULL, NULL, '122439469480', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c3334662252040000530398654040.505802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter1224394694806304F328', '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', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(1158, 59, NULL, 0.50, 'pix_mercadopago', '', '', '', NULL, '2025-08-15 13:58:06', NULL, 'pendente', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(1159, 34, NULL, 0.50, 'pix_mercadopago', '', '', ' - Device ID: device_5dffdd4f9701eba9ad50029192f77c93', ' - PIX rejeitado/cancelado no MP: cancelled', '2025-08-15 13:58:55', NULL, 'pix_aguardando', NULL, NULL, NULL, NULL, '122441932956', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c3334662252040000530398654040.505802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter12244193295663041979', 'iVBORw0KGgoAAAANSUhEUgAABWQAAAVkAQAAAAB79iscAAAOFUlEQVR4Xu3XUXZkKQ6E4dxB73+XvYOaYwUiQCLdnjOmy7fmj4csQEJ812/1+vWg/P2qJz85aO8F7b2gvRe094L2XtDeC9p7QXsvaO8F7b2gvRe094L2XtDeC9p7QXsvaO8F7b2gvRe094L2XtDeC9p7QXsvaO8F7b2gvRe094L2XtDeC9p7QXsvaO8F7b2gvRe094L2XtDeC9p7WbWvmr/G2aj+pQtLtdyN6viJeJs/Y6gn59laLfFDaNHmvLk8tsW/o4oWrapo0aqKFq2qaNGqihatqj9NWwC59ROmlBfXQpxt19ZRWfW2ad8w0KLNoEWroEWroEWroEWroEWrPFzr+3ubss7MPldP3xfH52/phXLtwJjLz9sUtGgVtGgVtGgVtGgVtGgVtGiVH6+NcWk89+XKzeVaNFjmrLzy4/Fo0aKdK7Ros4oWLVq0XqFFm1W0f6zW76yFzCob47b4sdNnvA7bN4w5fi6PbWMI2o+07RvGHD+Xx7YxBO1H2vYNY46fy2PbGIL2I237hjHHz+WxbQxB+5G2fcOY4+fy2DaGoP1I275hzPFzeWwbQ9B+pG3fMOb4uTy2jSFoP9K2bxhz/Fwe28YQtB9p2zeMOX4uj21jCNqPtO0bxhw/l8e2MeSnasv2cCGH5GOlz82fAMoqt2520J6aR4tHJaqtcutmB+2pebR4VKLaKrdudtCemkeLRyWqrXLrZgftqXm0eFSi2iq3bnbQnppHi0clqq1y62YH7al5tHhUotoqt2520J6aR4tHJaqtcutmB+2pebR4VKLaKrdudtCemkeLRyWqrXLrZudP0ZZ0xb/z0xlov+unM9B+109noP2un85A+10/nYH2u346A+13/XQG2u/66Qy03/XTGWi/66czHq/9JPGfxcx65km/5jt5NlZ512fjIM9GYduegxatghatghatghatghatghat8mTthhr3/96HeLutzrE2V+0jt2274bMIWget059Fm1vfRZstp9U5aJ3+LNrc+i7abDmtzkHr9GfR5tZ30WbLaXUOWqc/+5O1JemJl0+rmDTOtq9yX0m7Ub4lWny3/anWXUm/X1bt7Yz7StoNtHm/rNrbGfeVtBto835Ztbcz7itpN9Dm/bJqb2fcV9JuoM37ZdXezrivpN1Am/fLqr2dcV9Ju4E275dVezvjvpJ2A23eL6v2dsZ9Je0G2rxfVu3tjPtK2g20eb+s2tsZ95W0G/+b1lcdF9ZtJJpzO9YJKNXRUr7Kr5nsH7dE0KJV0KJV0KJV0KJV0KJV0KJV/gTt+o6N+QWj4NXWsnq2b/Zkn/jG2PraJpiFuVTQZtCiVdCiVdCiVdCiVdCiVdA+RDuueNzmbjHqV+v7p2+OQg5Y+/Ld9TPQokX7EbRoFbRoFbRoFbRoFbR/qna7P9ZxNbblndOznpLXfHfdbtfMK5Nn1esxAe1M227X0DYeWrRo0Y4qWrSqokWrKlq0qv4+7dob2cg+W1te80VnM46qPVvaaykoLSNo0Spo0Spo0Spo0Spo0Spo0SrP1RbeWsis23xnNP39sdqMPnOhfal55U8V1RK0/QwtWrQKWrQKWrQKWrQKWrTKI7W+tXnapG36GFHezkLrc3PEf6Vcje1WRYs2gxatghatghatghatghat8mRtjlvvx9nf58L5nWjOH+e0bTdi1NY8x0af1y6iPW7bjRi1Nc+x0ee1i2iP23YjRm3Nc2z0ee0i2uO23YhRW/McG31eu4j2uG03YtTWPMdGn9cuoj1u240YtTXPsdHntYtoj9t2I0ZtzXNs9HntItrjtt2IUVvzHBt9XruI9rhtN2LU1jzHRp/XLqI9btuNGLU1z7HR57WLT9COjnjWvX1mKcSN9ax/2thGSyQf8t0y4N3fYS5jl20dhRYt2o81WrRol/tZPRXQqjquofUZ2rw2l7HLto5Cixbtx/pbtJHyjgfnt5SZ693XrvXnZtbOaPZH+jOygBbt6Y1lClq0c5dBO7N2RjNatGpGi1bNaNGqGe1P027xY+tqy+hztndOo9ZtqZ4KOXQEbRTQolUBLVoV0KJVAS1aFdCiVQHt07Wlo0FPha1lRcW2p6H6t5SWEbRoFbRoFbRoFbRoFbRoFbRolcdro9eyzeOZo3mLm8dB+dLXHPBmylrofyC05wGZz6egXZu3oH2hjaB9oY2gfaGNoH2hjaB9oY38q9rxzlpUrwFlSCuUr8q3PXTeXgaMuDkzSxm0aBW0aBW0aBW0aBW0aBW0aJUna50TLwunG96OUn97VLevWgvRvH2BCyNoI2jRKmjRKmjRKmjRKmjRKmgfrI2MyznO010oq3Ex3243IoXnbzY+sz5eghatghatghatghatghatghat8lztJ7fKT1Rfq6ysimIYy40obF+/GvtnoEWbQYs2z7xGm6MyrYAWrQpo0aqAFq0KaH+gdnTEzBIDotpfXD+jfHPecHWdEqtToYyPoEWroEWroEWroEWroEWroEWrPFfrZ08ZLbHKt0fyW9zSrgXlBPD29O52De35Gtq3t15oM5+8ixatghatghatghatgva3aP1sGexxDRXVVzsrd/cXlfZaeTczL0bLvkOLVju0aLVDi1Y7tGi1Q4tWO7RotXuOdpS3J8qF9SzdvusXy0c6hee7b9/dv3Qu/+HWaEGrvHkXrc/QZtCiVdCiVdCiVdCiVdD+O9p5lON+7dqYXp5wNVvWuxvqdHcdcLpbghatghatghatghatghatghat8mTtm3ERd62nf43B5ftaS64G6rV/+Od37UZbgnbs0KLVDi1a7dCi1Q4tWu3QotXuYVpP91kbnH1rS2Z9O5tbS4zfztr2dIYWbW73HdomQ+sztPWsbU9naNHmdt+hbTK0PkNbz9r2dIYWbW7X3TjSJM9c8dkyYs+vAy+/av3IGOUbOWWsytZ3I2jRKmjRKmjRKmjRKmjRKmjRKk/W5pDzi691G/HMsS2obCnu8gVv3x1nDlq0Wd13x1to0aJFixatV2jRokW7rp6gzcfOL+bKhTWmZEbfJhuF7QvKu+2rImjRKmjRKmjRKmjRKmjRKmjRKs/V+p1ZTJkHRzW17UVP6YpRj5btjbVvuzaa0aJVM1q0akaLVs1o0aoZLVo1o0Wr5j9DG0O2W+tZxJP+izOPL9XyQeXJtSWCtp95fKmifav46pnHlyrat4qvnnl8qaJ9q/jqmceXKtq3iq+eeXypon2r+OqZx5cq2reKr555fKmifav46pnHlyrat4qvnnl8qf5/aVtvJPFrcw4ub6/NOaDNy6wTI/6+7Utn1et1CFq0GoIWrYagRashaNFqCFq0GoIWrYY8ROuO4ll/tj5DfW0926qtJcevH9S/fgQtWgUtWgUtWgUtWgUtWgUtWuXJ2lJ0+hd465S3R9/2VWvhlO1a60OLNqteo0WroEWroEWroEWroEWrPEsbsWftLS9GX1bdPM467/T2OOivuTC2/ougRaugRaugRaugRaugRaugRas8XluuRm95bFO4rzT7bC0UXpniswzahoqg3bJOii3amTbFZxm0DRVBu2WdFFu0M22KzzJoGyqCdss6KbZoZ9oUn2XQNlQE7ZZ1Umx/uHYbXAr71SXrB53e3gAj5Usj5VuyOm/MpYIWrYIWrYIWrYIWrYIWrYIWrfIgbQ52R7tVnvAXfNX4a2+J7fYHWueVAWjRKmjRKmjRKmjRKmjRKmjRKn+CNjaeObINGdXsc8rd9mx5I3jlbp55O+/OZezQotUOLVrt0KLVDi1a7dCi1Q4tWu2eo3XHmv7iOimzeqKwPlHvDk+2rKscNVZbFe0ooEWrAlq0KqBFqwJatCqgRasC2gdrI+uFmOlbp/uR9IxCPlbunnirzFOy2avZPJeKr6JV0KJV0KJV0KJV0KJV0KJV0D5G62dPb49sH7RC4yx/iqcUPMrb8i1zHdt196u9jRZtFNGiVREtWhXRolURLVoV0aJV8SHazDjNd9Yv2F6021MaPs9O19b4g7JlBG1OQTuXSrk4TtHu19agRaugRaugRaugRaug/c3a1ltk0fJmVW6sd1/7F/hGbNPtvH0cLdoMWrQKWrQKWrQKWrQKWrTK47XbzFJdp28Ux1NG/C1Z9ZTiXpMFtI6njKCNq5mxRfsRtGjrFLSuokVb30G7BW02O54ygjauZsYW7Ue+RXtKtI9V3PLM7VsKYKzibvm+xK99nlzulqBFq6BFq6BFq6BFq6BFq6BFqzxXm4yZmJkZLTZ6uhURezK+6+0n17a+/cm5RDviu95+cg0tWrQKWrQKWrQKWrQKWrTKb9f6PLdt+hYX1mpOKYD1bOsb8df/2t910KJV0KJV0KJV0KJV0KJV0KJVHq71pLbN+IahY0BP+Ug3rx+5jR85fRXaLWgjjYcWLVpvy120Y/VCizbjG2gzaNEqaH+k1rdy+rhRqnlmoyev7hWwbMu7PpvNc4kW7QhatApatApatApatApatMofoo2Zo2XLispn1+p44tjXzkrVILRo0S4rtGjRol1XaNGiRbuu0P452rIdY9YLS9Z3srp+Wvlcf5A9vpvb1oI2q2gzaNEqaNEqaNEqaNEqaNEqT9aWbDM9JDarLHmOZ6w33lbjoTLFfxa0WzwDbWSZsbd5ixattmjRaosWrbZo0WqLFq22P0j784P2XtDeC9p7QXsvaO8F7b2gvRe094L2XtDeC9p7QXsvaO8F7b2gvRe094L2XtDeC9p7QXsvaO8F7b2gvRe094L2XtDeC9p7QXsvaO8F7b2gvRe094L2XtDeC9p7QXsvaO8F7b2gvZeHaf8D+icoAecUjGMAAAAASUVORK5CYII=', 'cancelled', NULL, NULL, NULL, NULL, NULL, NULL),
(1160, 59, NULL, 1.00, 'pix_mercadopago', '', '', '', NULL, '2025-08-15 14:00:10', NULL, 'pendente', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(1161, 59, NULL, 1.00, 'pix_mercadopago', '', '', '', NULL, '2025-08-15 14:12:18', NULL, 'pendente', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(1162, 34, NULL, 60.00, 'pix_mercadopago', '', '', ' - Device ID: device_eb4df56b689db414f6e6e99754a28825', ' - PIX rejeitado/cancelado no MP: cancelled', '2025-08-15 14:17:31', NULL, 'pix_aguardando', NULL, NULL, NULL, NULL, '122443683366', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c33346622520400005303986540560.005802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter12244368336663040B4F', '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', 'cancelled', NULL, NULL, NULL, NULL, NULL, NULL),
(1163, 59, NULL, 0.50, 'pix_mercadopago', '', '', ' - CPF usado: 04355521630 - Device: device_0e91297457151911f52f048aae3a7b88', ' - PIX rejeitado/cancelado no MP: cancelled', '2025-08-15 14:19:26', NULL, 'pix_aguardando', NULL, NULL, NULL, NULL, '121920378573', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c3334662252040000530398654040.505802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter12192037857363046F17', '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', 'cancelled', NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `pagamentos_comissao` (`id`, `loja_id`, `criado_por`, `valor_total`, `metodo_pagamento`, `numero_referencia`, `comprovante`, `observacao`, `observacao_admin`, `data_registro`, `data_aprovacao`, `status`, `pix_charge_id`, `pix_qr_code`, `pix_qr_code_image`, `pix_paid_at`, `mp_payment_id`, `mp_qr_code`, `mp_qr_code_base64`, `mp_status`, `openpix_charge_id`, `openpix_qr_code`, `openpix_qr_code_image`, `openpix_correlation_id`, `openpix_status`, `openpix_paid_at`) VALUES
(1164, 59, NULL, 10.00, 'pix_mercadopago', '', '', ' - CPF usado: 04355521630 - Device: device_e085f281302de93b365c3f579caa7037', ' - PIX rejeitado/cancelado no MP: cancelled', '2025-08-15 14:46:01', NULL, 'pix_aguardando', NULL, NULL, NULL, NULL, '121918088399', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c33346622520400005303986540510.005802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter12191808839963049A71', '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', 'cancelled', NULL, NULL, NULL, NULL, NULL, NULL),
(1165, 59, NULL, 1.00, 'pix_mercadopago', '', '', ' - CPF usado: 04355521630 - Device: device_4184382409ae9c48d592295864c60c28', 'Pagamento PIX aprovado automaticamente via Mercado Pago - ID MP: 122448314624', '2025-08-15 14:46:24', '2025-08-15 14:46:51', 'aprovado', NULL, NULL, NULL, NULL, '122448314624', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c3334662252040000530398654041.005802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter1224483146246304F024', 'iVBORw0KGgoAAAANSUhEUgAABWQAAAVkAQAAAAB79iscAAAN2UlEQVR4Xu3XUZJjOQqFYe+g97/L3kFOmAMCgeyOmCjN5O34z4NLEiB9N9/q9fOg/P3qJ785aO8F7b2gvRe094L2XtDeC9p7QXsvaO8F7b2gvRe094L2XtDeC9p7QXsvaO8F7b2gvRe094L2XtDeC9p7QXsvaO8F7b2gvRe094L2XtDeC9p7QXsvaO8F7b2gvRe094L2XtDeC9p7qdpXz19+5tW/NFCqWfC+v9ePJbZZ9eZtot4chZp4CC3aCFq0cd9aHtvsX6+iRasqWrSqokWrKlq0qv42bZ5v2/FEnsU2jaMlflo1t0P7gYF2tKBt59sWbdmiRYsWbd2iRYsWbd2i/ZXanN/b5p3tndzmVflBp2+ZhTZ2YKzl9zadoUWrM7RodYYWrc7QotUZWrQ6Q4tWZ79ea9el8fTz8hezeYxZS8gy4yq08+J1HdrVjhatghatghatghatghat8u/S5ju+sr5sieq6bks+dvqM1xjz6ydj9a3lsc0vQftOG/PrJ2P1reWxzS9B+04b8+snY/Wt5bHNL0H7Thvz6ydj9a3lsc0vQftOG/PrJ2P1reWxzS9B+04b8+snY/Wt5bHNL0H7Thvz6ydj9a3lsc0vQftOG/PrJ2P1reWxzS9B+04b8+snY/Wt5bHNL0H7Thvz6ydj9a3lsc0v+a3atj0M6JK2PTV/AeRq22ZzBu2p2Qy+Qjvb0KI9AtCijaBd22zOoD01m8FXaGcbWrRHANr/WtsyFf+bn8lA+6d+JgPtn/qZDLR/6mcy0P6pn8lA+6d+JgPtn/qZDLR/6mcy0P6pn8lA+6d+JuPx2i+x/yxG6lncVN/ZznJ29MWZF7btOWjRKmjRKmjRKmjRKmjRKmjRKk/Wbiif/3u/xLYb4DTmSW2s2keevvl8ZkGbQZuZz6KNbc6ijRa0ypjIMwvaDNrMfBZtbHMWbbSgVcZEnlnQZtB+TEDr6Gs3/rXOtq+ys/pOFtpE+5Zo8Yw/Vd21zHl/J6rj7Yidoa27ljnv70R1vB2xM7R11zLn/Z2ojrcjdoa27lrmvL8T1fF2xM7Q1l3LnPd3ojrejtgZ2rprmfP+TlTH2xE7Q1t3LXPe34nqeDtiZ2jrrmXO+ztRHW9H7Axt3bXMeX8nquPtiJ2hrbuWOe/vRHW8HbGzu1q/KUZb8sybLdtneHXrO1VrIV/byJ/wa4n2XK0FtK+h8DVaBS1aBS1aBS1aBS1aBe1v0FbFxHshVzaR1XynJa+PeHP7vsTH9auwlmj3oEWroEWroEWroEWroEWroH2M1pLXtVVOWQJVvyWfaNu4qlLyq05j+Rloo88ytmij97xCu6pjDG2sss8ytmij97xCu6pjDG2sss8ytmij97xCu6pjDG2sss8ytmij97z6Xdrzsx9erNUYy9vrLXnpa7XkdnujXrB90Kruuwjad8Z2ewMt2ghatApatApatApatAra36D1G9pjVsjrYustrx2wPTu+ama8FtrW4kFrQYtWQYtWQYtWQYtWQYtWQftgbd7e0ii5tX+r7NtPG8vZBq3VFrQfftpYzqJFixatB60Po0WroEWroEWr/C5tzvs2Pds2+1rLyIersurJv0OsfLtV0dZ8uCqrHrRoFbRoFbRoFbRoFbRoFbS/S5vJi33Vnk33h3d8IjxtW8+2Ztt4NQsZtBa0aBW0aBW0aBW0aBW0aBW0j9badZvndGct5ERr/qlfUFss86F6QX5VtKyxtbRd6ajzUUWLVlW0aFVFi1ZVtGhVRYtWVbRoVf192mjLt+vFm6w2W19s6zvbF9S+SBpPX4/W+2KL9gct2ghatApatApatApatMqTtVtqmyV4dWV9mfZBkdPXj+qpEJd60KJV0KJV0KJV0KJV0KJV0KJVHq6tHYaqvdrmT832Le2DWk6o9i3jegtatApatApatApatApatApatMrjtdZraVOpiDPvi7QPyrHWkjK/Jf42tZBj9aq1VND2FrSnZ9Gi/UEbt6BFq1vQotUtaNHqFrS/S1vfydEPj2VzFmqf367V+fvmV7WWVYqgta3fjlZBi1ZBi1ZBi1ZBi1ZBi1Z5mnZr89WmaLdn6lnK4ltqX7bMC9oXZMGDFq2CFq2CFq2CFq2CFq2CFq3yXK2lzv9de/30BNhaciy3g9e+Obc/S7s97kGLVkGLVkGLVkGLVkGLVkGLVnmuNqcclVMffs7PZjVka93HarUZ52egRRtBizbOcj0uQYsWrQUtWgUtWgXtE7TtiVbIau2zbbb81G/2bUxk9eNYNrcPWs1rabsI2lX9OJbNaH2bLT9o0aJdhdW8lraLoF3Vj2PZjNa32fKDFi3aVVjNa2m7CNpV/TiWzf82rZ9vlEy2nLf57Jba1wDt+07vbmNo60zrQ3uaeh14bYsWrbZo0WqLFq22aNFqixattv9rrc/b1HZxu90Lr2Vs0Dm7v6iM19q7kTVoLfsOLVrt0KLVDi1a7dCi1Q4tWu3QotXuOVovb0+0gXFJPOFnud0+MtN49dIP7+5fupb/MFVb8gwtWgUtWgUtWgUtWgUtWgUtWuX/p11Hcd3Prs3bv1w3Z2Ns8CJZrbNzhXbMos2cXoyCn6Hts3OFdsyizZxejIKfoe2zc4V2zKLNnF6Mgp+h7bNzhXbMos2cXoyCn/0ObQzstynZdSo0Y03eZy32xqt+xpdZC9qPL6L1nYq9/53sOhW+vIjWdyr2/ney61T48iJa36nY+9/JrlPhy4tofadi738nu06FLy+i9Z2Kvf+d7DoVvryI1ncq9v53sutU+PIiWt+p2Pvfya5T4cuLaH2nYu9/J7tOhS8vovWdLrG0M9vUF7e364vG2745L/CWeTa2pzO0aGO779AO2elsbE9naNHGdt+hHbLT2dieztCije2+Qztkp7OxPZ2hRRvbffcw7ekdX/3sWktsW/XLR9pVWYhb4kLfVkv8HdCijaBFq6BFq6BFq6BFq6BFqzxeuw2cviAvya1lvzhubrdsP+2rTl9agzauqiu0aPstaNGiLQC0aNGirQC0aB+gzfkTag2UVeafvqp9/Wt8QbtvzPqTazmn0KLtAy+0aNG+gxatghatghatgvYJ2nr2qhfni/XtvM4m2kcmL1vaNrXt07JqQYtWQYtWQYtWQYtWQYtWQYtWebI2Lvn44rhpy6nPC6258baC/WsT44+B1oIWrYIWrYIWrYIWrYIWrYL26drmqXfm1M/aJi9ma3Nq7b7WbIWW6Ks/tZprvwEtWgUtWgUtWgUtWgUtWgUtWuVJ2uzwZ7eL8+z0QdlSz7bqaLHkH2iO1Vm0aBW0aBW0aBW0aBW0aBW0aJUna1sxs40OWaS9bWlfVQunbGOjDy3aqOYaLVoFLVoFLVoFLVoFLVrlqVpvjKnxY81xk3eG8cQ7vZ1jvopqFnybfxG0aBW0aBW0aBW0aBW0aBW0aJXnavPOOprXxbd8xGe1TdTCxz9G3FI/187QRgvaCFq0Clq0Clq0Clq0Clq0ynO1+7kuHk+0lkg7s42vNk9NGttfJJLVNbGWaNF6BgVtCVq0Clq0Clq0Clq0Ctrfqq2A6PD5D5e0qhds9dH4s7fYtv1ZYjsuQGur02OR2mJbtJFRQHu8AK2tTo9Faott0UZGAe3xArS2Oj0WqS22RRsZBbTHC9Da6vRYpLbYFm1kFP4v2tpr27jTs13i1RhrfePFfHbbfvk7bNs1u5Zo0XrQolXQolXQolXQolXQolUeqK0XW/LinN9Wvt3GzuScaH+WD3+gVkXrBbRoVUCLVgW0aFVAi1YFtGhVQPtgbW2zld3ZpqJQE57WUmctk9eezG1egBZtH0N7nrWgfaG1oH2htaB9obWgfaG1oH09Rfuzd5zm2zvtTptot8S2TmzN+ZA3b497i/fV3c/hbbRo0aJFi3a+jRYtWrRo0c63n6CN+Gm8M7YbuXn8ikluMu/LbLP7u2upbGNo0Ub8FK3O0KLVGVq0OkOLVmdo0eoM7S/Ujo7tYm/Jt7dVM+asb60vV7PPL4tqvQAtWrRo0eYKLVq073/QxgotWrTvf/6V2u3OUciL46a8/ZxsiavylvaRNXmGFu1aedC2oLXRiG/RvoMWLVq09Sq0aNE+QnuKtacsz6oxC99k7fva2LjKZlvQolXQolXQolXQolXQolXQolWeqxWixO581am8rt5uK+trnkjO5rY2W7bPaGNrYi3RrkLM5hat/Vtb0Cpo0Spo0Spo0Spo0Spof5E2z2ObnjPPYtVY5S3elLfY2carmbNb+R20aBW0aBW0aBW0aBW0aBW0aJWHa9sT9SdSJ2yb3/KPaZ+Wxt54/Cq034J2QNGiRevTaNEqaNEqaNEqaB+ozam4/WT0W2wV1by5NldA2bYn82w1r2WXoY2gRaugRaugRaugRaugRaugfaDWpvLZyNjGs3mWhYS2z61nrZogtGjRrha0aNGiXVu0aFcLWrRo/33atvVr6oC2XnjVb8lnz5+bs+mxaqTN+hnarFrQolXQolXQolXQolXQolXQPlrbst3Z8PXO1Gaa+3VAZfV0y3bBOst6uWNvy21dfXzHghatghatghatghatghatghatcl37+4P2XtDeC9p7QXsvaO8F7b2gvRe094L2XtDeC9p7QXsvaO8F7b2gvRe094L2XtDeC9p7QXsvaO8F7b2gvRe094L2XtDeC9p7QXsvaO8F7b2gvRe094L2XtDeC9p7QXsvaO8F7b2gvZeHaf8DwLMcxzd6tUwAAAAASUVORK5CYII=', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(1166, 59, NULL, 0.50, 'pix_mercadopago', '', '', ' - CPF usado: 04355521630 - Device: device_b4973bece2017c1b0baf049b8d50e853', 'Pagamento PIX aprovado automaticamente via Mercado Pago - ID MP: 122448476668', '2025-08-15 14:48:33', '2025-08-15 14:48:50', 'aprovado', NULL, NULL, NULL, NULL, '122448476668', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c3334662252040000530398654040.505802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter1224484766686304F3A6', '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', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(1167, 59, NULL, 0.98, 'pix_mercadopago', '', '', ' - CPF usado: 04355521630 - Device: device_5ab37990b44c0db924efbbf4c68c536a', 'Pagamento PIX aprovado automaticamente via Mercado Pago - ID MP: 121952033987', '2025-08-15 19:49:46', '2025-08-15 19:50:15', 'aprovado', NULL, NULL, NULL, NULL, '121952033987', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c3334662252040000530398654040.985802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter12195203398763047769', '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', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(1168, 59, NULL, 0.50, 'pix_mercadopago', '', '', ' - CPF usado: 04355521630 - Device: device_54cddc94b51b6cbd23fcd46345aa4be5', 'Pagamento PIX aprovado automaticamente via Mercado Pago - ID MP: 121956141447', '2025-08-15 20:17:39', '2025-08-15 20:18:04', 'aprovado', NULL, NULL, NULL, NULL, '121956141447', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c3334662252040000530398654040.505802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter1219561414476304C3E8', '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', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(1169, 34, NULL, 540.00, 'pix_mercadopago', '', '', ' - CPF usado: 00000000191 - Device: device_b4517a234b6e3df893974fbd20fec88a', ' - PIX rejeitado/cancelado no MP: cancelled', '2025-08-16 16:50:37', NULL, 'pix_aguardando', NULL, NULL, NULL, NULL, '122582550944', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c333466225204000053039865406540.005802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter12258255094463041BDE', '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', 'cancelled', NULL, NULL, NULL, NULL, NULL, NULL),
(1170, 38, NULL, 1260.90, 'pix_mercadopago', '', '', ' - CPF usado: 00000000191 - Device: device_8069ba46302488298c315f1fa46488ff', ' - PIX rejeitado/cancelado no MP: cancelled', '2025-08-16 16:51:38', NULL, 'pix_aguardando', NULL, NULL, NULL, NULL, '122052346269', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c3334662252040000530398654071260.905802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter12205234626963041A41', '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', 'cancelled', NULL, NULL, NULL, NULL, NULL, NULL),
(1171, 34, NULL, 1.00, 'pix_mercadopago', '', '', ' - CPF usado: 00000000191 - Device: device_d5851507d936f1e451fc43eab3284c11', ' - PIX rejeitado/cancelado no MP: cancelled', '2025-08-16 16:54:04', NULL, 'pix_aguardando', NULL, NULL, NULL, NULL, '122581821908', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c3334662252040000530398654041.005802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter1225818219086304CD00', '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', 'cancelled', NULL, NULL, NULL, NULL, NULL, NULL),
(1172, 38, NULL, 120.00, 'pix_mercadopago', '', '', ' - CPF usado: 00000000191 - Device: device_ec3e69548a4e158380ee6367c35f5abb', ' - PIX rejeitado/cancelado no MP: cancelled', '2025-08-18 14:45:56', NULL, 'pix_aguardando', NULL, NULL, NULL, NULL, '122784242864', '00020126580014br.gov.bcb.pix0136bd1b5d08-558b-45a0-aa2d-2d0c333466225204000053039865406120.005802BR5915SYNCLABSDIGITAL6009Sao Paulo62250521mpqrinter12278424286463042886', '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', 'cancelled', NULL, NULL, NULL, NULL, NULL, NULL),
(1173, 38, NULL, 10.00, 'pix_mercadopago', '', '', '', '', '2025-08-26 15:15:17', '2025-08-26 15:15:45', 'aprovado', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'pending', NULL, NULL, NULL, NULL, NULL, NULL);

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
(141, 1143, 192),
(142, 1144, 193),
(143, 1145, 194),
(144, 1146, 195),
(145, 1147, 196),
(146, 1148, 197),
(147, 1149, 198),
(148, 1150, 199),
(149, 1151, 201),
(150, 1152, 202),
(151, 1153, 219),
(152, 1154, 220),
(153, 1155, 221),
(154, 1156, 239),
(155, 1157, 241),
(156, 1158, 242),
(157, 1159, 240),
(158, 1160, 243),
(159, 1161, 244),
(160, 1162, 224),
(161, 1163, 245),
(162, 1164, 247),
(163, 1165, 246),
(164, 1166, 248),
(165, 1167, 250),
(166, 1168, 251),
(167, 1169, 218),
(176, 1170, 232),
(177, 1170, 233),
(175, 1170, 234),
(174, 1170, 235),
(173, 1170, 236),
(172, 1170, 237),
(171, 1170, 238),
(170, 1170, 249),
(169, 1170, 252),
(168, 1170, 253),
(178, 1171, 217),
(180, 1172, 256),
(179, 1172, 258),
(181, 1173, 262);

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

--
-- Despejando dados para a tabela `recuperacao_senha`
--

INSERT INTO `recuperacao_senha` (`id`, `usuario_id`, `token`, `data_expiracao`, `usado`) VALUES
(25, 80, '6afae38d7583c083e98cb22ed8a903e57edd7d95a7d7f86ce753f2f694d0ae98', '2025-06-16 11:21:14', 0),
(54, 9, '7642c57ece91d249531459b943aa8da03db3804a6ef7211793f5f1115ad1092f', '2025-07-19 00:31:04', 1);

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

--
-- Despejando dados para a tabela `store_balance_payments`
--

INSERT INTO `store_balance_payments` (`id`, `loja_id`, `valor_total`, `metodo_pagamento`, `numero_referencia`, `comprovante`, `observacao`, `status`, `data_criacao`, `data_processamento`) VALUES
(25, 34, 10.00, 'reembolso_saldo', NULL, NULL, 'Reembolso de saldo usado pelo cliente - Transação #194', 'pendente', '2025-07-17 23:48:09', NULL),
(26, 34, 10.00, 'pix', '', '', '', 'aprovado', '2025-08-14 02:32:10', NULL),
(27, 59, 5.25, 'reembolso_saldo', NULL, NULL, 'Reembolso de saldo usado pelo cliente - Transação #250\nReembolso adicional - Transação #251', 'pendente', '2025-08-15 19:49:00', NULL),
(28, 38, 5.00, 'reembolso_saldo', NULL, NULL, 'Reembolso de saldo usado pelo cliente - Transação #263', 'pendente', '2025-08-26 15:16:56', NULL);

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
(192, 9, 34, NULL, 100.00, 10.00, 5.00, 5.00, 0.00, 'KC25070917541770902', '', '2025-07-09 17:53:00', '2025-07-26 13:42:38', 'aprovado'),
(193, 9, 34, NULL, 100.00, 10.00, 5.00, 5.00, 0.00, 'KC25071720284294161', '', '2025-07-17 20:28:00', '2025-07-26 13:42:38', 'aprovado'),
(194, 9, 34, NULL, 102.00, 9.20, 4.60, 4.60, 0.00, 'KC25071720480081519', ' (Usado R$ 10,00 do saldo)', '2025-07-17 20:47:00', '2025-07-26 13:42:38', 'aprovado'),
(195, 9, 34, NULL, 100.00, 10.00, 5.00, 5.00, 0.00, 'KC25071721084736070', '', '2025-07-17 21:08:00', '2025-07-26 13:42:38', 'aprovado'),
(196, 9, 34, NULL, 100.00, 10.00, 5.00, 5.00, 0.00, 'KC25073017323560083', '', '2025-07-30 17:32:00', '2025-07-30 20:32:39', 'aprovado'),
(197, 9, 34, NULL, 10.00, 1.00, 0.50, 0.50, 0.00, 'KC25073020413377930', '', '2025-07-30 20:41:00', '2025-07-30 23:41:37', 'aprovado'),
(198, 9, 34, NULL, 5.00, 0.50, 0.25, 0.25, 0.00, 'KC25080111191663079', '', '2025-08-01 11:19:00', '2025-08-01 14:19:21', 'aprovado'),
(199, 9, 34, NULL, 5000.00, 500.00, 250.00, 250.00, 0.00, 'KC25080112035199685', '', '2025-08-01 12:03:00', '2025-08-01 15:04:00', 'aprovado'),
(200, 9, 34, NULL, 10.00, 1.00, 0.50, 0.50, 0.00, 'KC25080112211671978', '', '2025-08-01 12:21:00', '2025-08-01 15:21:21', 'pendente'),
(201, 73, 38, NULL, 100.00, 10.00, 5.00, 5.00, 0.00, 'KC25080115283138898', '', '2025-08-01 15:28:00', '2025-08-01 18:28:49', 'pagamento_pendente'),
(202, 9, 34, NULL, 10.00, 1.00, 0.50, 0.50, 0.00, 'KC25080116084757082', '', '2025-08-01 16:08:00', '2025-08-01 19:08:50', 'aprovado'),
(203, 9, 34, NULL, 10.00, 1.00, 0.50, 0.50, 0.00, 'KC25080118443436603', '', '2025-08-01 18:44:00', '2025-08-01 21:44:39', 'pendente'),
(204, 9, 34, NULL, 10.00, 1.00, 0.50, 0.50, 0.00, 'KC25080118445885345', '', '2025-08-01 18:44:00', '2025-08-01 21:45:00', 'pendente'),
(205, 9, 34, NULL, 20.00, 2.00, 1.00, 1.00, 0.00, 'KC25080119015593804', '', '2025-08-01 18:45:00', '2025-08-01 22:01:57', 'pendente'),
(206, 9, 34, NULL, 20.00, 2.00, 1.00, 1.00, 0.00, 'KC25080119034885790', '', '2025-08-01 19:01:00', '2025-08-01 22:03:52', 'pendente'),
(207, 9, 34, NULL, 10.00, 1.00, 0.50, 0.50, 0.00, 'KC25080119153106415', '', '2025-08-01 19:15:00', '2025-08-01 22:15:34', 'pendente'),
(208, 9, 34, NULL, 10.00, 1.00, 0.50, 0.50, 0.00, 'KC25080119195167600', '', '2025-08-01 19:15:00', '2025-08-01 22:19:53', 'pendente'),
(209, 9, 34, NULL, 30.00, 3.00, 1.50, 1.50, 0.00, 'KC25080119214961246', '', '2025-08-01 19:21:00', '2025-08-01 22:21:52', 'pendente'),
(210, 9, 34, NULL, 10.00, 1.00, 0.50, 0.50, 0.00, 'KC25080123584912803', '', '2025-08-01 23:58:00', '2025-08-02 02:58:51', 'pendente'),
(211, 9, 34, NULL, 50.00, 5.00, 2.50, 2.50, 0.00, 'KC25080200103044239', '', '2025-08-02 00:10:00', '2025-08-02 03:10:32', 'pendente'),
(212, 9, 34, NULL, 25.00, 2.50, 1.25, 1.25, 0.00, 'KC25080200161638625', '', '2025-08-02 00:16:00', '2025-08-02 03:16:19', 'pendente'),
(213, 9, 34, NULL, 10.00, 1.00, 0.50, 0.50, 0.00, 'KC25080200191063950', '', '2025-08-02 00:18:00', '2025-08-02 03:19:13', 'pendente'),
(214, 9, 34, NULL, 20000.00, 2000.00, 1000.00, 1000.00, 0.00, 'KC25080200193702684', '', '2025-08-02 00:19:00', '2025-08-02 03:19:40', 'pendente'),
(215, 9, 34, NULL, 5.00, 0.50, 0.25, 0.25, 0.00, 'KC25080200281529416', '', '2025-08-02 00:27:00', '2025-08-02 03:28:17', 'pendente'),
(216, 9, 34, NULL, 10.00, 1.00, 0.50, 0.50, 0.00, 'KC25080415430830625', '', '2025-08-04 15:42:00', '2025-08-04 18:43:15', 'pendente'),
(217, 9, 34, NULL, 10.00, 1.00, 0.50, 0.50, 0.00, 'KC25081318413077351', '', '2025-08-13 18:38:00', '2025-08-13 21:41:49', 'pagamento_pendente'),
(218, 138, 34, NULL, 5400.00, 540.00, 270.00, 270.00, 0.00, 'KC25081323205774971', '', '2025-08-13 23:20:00', '2025-08-14 02:21:00', 'pagamento_pendente'),
(219, 139, 34, NULL, 2000.00, 200.00, 100.00, 100.00, 0.00, 'KC25081323213343615', '', '2025-08-13 23:21:00', '2025-08-14 02:21:38', 'pagamento_pendente'),
(220, 139, 34, NULL, 20.00, 2.00, 1.00, 1.00, 0.00, 'KC25081323231909622', '', '2025-08-13 23:23:00', '2025-08-14 02:23:21', 'aprovado'),
(221, 140, 34, NULL, 20.00, 2.00, 1.00, 1.00, 0.00, 'KC25081323274710951', '', '2025-08-13 23:26:00', '2025-08-14 02:27:53', 'aprovado'),
(222, 142, 38, NULL, 100.00, 10.00, 5.00, 5.00, 0.00, 'KC25081404312754208', '', '2025-08-14 04:30:00', '2025-08-14 07:31:35', 'pendente'),
(223, 143, 38, NULL, 100.00, 10.00, 5.00, 5.00, 0.00, 'KC25081405470668632', '', '2025-08-14 05:22:00', '2025-08-14 08:47:17', 'pendente'),
(224, 9, 34, NULL, 600.00, 60.00, 30.00, 30.00, 0.00, 'KC25081408090415086', '', '2025-08-14 08:08:00', '2025-08-14 11:09:31', 'pagamento_pendente'),
(225, 145, 38, NULL, 200.00, 20.00, 10.00, 10.00, 0.00, 'KC25081410545236384', '', '2025-08-14 10:54:00', '2025-08-14 13:54:59', 'pendente'),
(226, 146, 38, NULL, 50.00, 5.00, 2.50, 2.50, 0.00, 'KC25081412031644054', '', '2025-08-14 12:02:00', '2025-08-14 15:03:35', 'pendente'),
(227, 147, 38, NULL, 50.00, 5.00, 2.50, 2.50, 0.00, 'KC25081412134463918', '', '2025-08-14 12:13:00', '2025-08-14 15:13:53', 'pendente'),
(228, 148, 38, NULL, 100.00, 10.00, 5.00, 5.00, 0.00, 'KC25081414115619115', '', '2025-08-14 14:11:00', '2025-08-14 17:12:00', 'aprovado'),
(229, 149, 38, NULL, 100.00, 10.00, 5.00, 5.00, 0.00, 'KC25081415463444478', '', '2025-08-14 15:45:00', '2025-08-14 18:48:43', 'pendente'),
(230, 150, 38, NULL, 10000.00, 1000.00, 500.00, 500.00, 0.00, 'KC25081415500249701', '', '2025-08-14 15:48:00', '2025-08-14 18:50:14', 'pendente'),
(231, 151, 38, NULL, 100.00, 10.00, 5.00, 5.00, 0.00, 'KC25081416473608862', '', '2025-08-14 16:46:00', '2025-08-14 19:47:46', 'pendente'),
(232, 152, 38, NULL, 100.00, 10.00, 5.00, 5.00, 0.00, 'KC25081416502993490', '', '2025-08-14 16:49:00', '2025-08-14 19:50:35', 'pagamento_pendente'),
(233, 153, 38, NULL, 100.00, 10.00, 5.00, 5.00, 0.00, 'KC25081416540406963', '', '2025-08-14 16:49:00', '2025-08-14 19:54:17', 'pagamento_pendente'),
(234, 154, 38, NULL, 100.00, 10.00, 5.00, 5.00, 0.00, 'KC25081416581268709', '', '2025-08-14 16:57:00', '2025-08-14 19:58:21', 'pagamento_pendente'),
(235, 155, 38, NULL, 500.00, 50.00, 25.00, 25.00, 0.00, 'KC25081417095038034', '', '2025-08-14 17:08:00', '2025-08-14 20:10:00', 'pagamento_pendente'),
(236, 156, 38, NULL, 100.00, 10.00, 5.00, 5.00, 0.00, 'KC25081417133820176', '', '2025-08-14 17:13:00', '2025-08-14 20:13:42', 'pagamento_pendente'),
(237, 157, 38, NULL, 100.00, 10.00, 5.00, 5.00, 0.00, 'KC25081417155673807', '', '2025-08-14 17:14:00', '2025-08-14 20:15:59', 'pagamento_pendente'),
(238, 158, 38, NULL, 500.00, 50.00, 25.00, 25.00, 0.00, 'KC25081422030001099', '', '2025-08-14 22:02:00', '2025-08-15 01:03:05', 'pagamento_pendente'),
(239, 9, 34, NULL, 100.00, 10.00, 5.00, 5.00, 0.00, 'KC25081509093933563', '', '2025-08-13 23:47:00', '2025-08-15 12:09:41', 'aprovado'),
(240, 9, 34, NULL, 5.00, 0.50, 0.25, 0.25, 0.00, 'KC25081510395757894', '', '2025-08-15 10:39:00', '2025-08-15 13:40:04', 'pagamento_pendente'),
(241, 139, 34, NULL, 5.00, 0.50, 0.25, 0.25, 0.00, 'KC25081510402678077', '', '2025-08-15 10:40:00', '2025-08-15 13:40:30', 'aprovado'),
(242, 9, 59, NULL, 5.00, 0.50, 0.25, 0.25, 0.00, 'KC25081510541862835', '', '2025-08-15 10:54:00', '2025-08-15 13:54:22', 'pagamento_pendente'),
(243, 9, 59, NULL, 10.00, 1.00, 0.50, 0.50, 0.00, 'KC25081511000184527', '', '2025-08-15 10:59:00', '2025-08-15 14:00:04', 'pagamento_pendente'),
(244, 9, 59, NULL, 10.00, 1.00, 0.50, 0.50, 0.00, 'KC25081511120934081', '', '2025-08-15 11:11:00', '2025-08-15 14:12:13', 'pagamento_pendente'),
(245, 9, 59, NULL, 5.00, 0.50, 0.25, 0.25, 0.00, 'KC25081511191700538', '', '2025-08-15 11:19:00', '2025-08-15 14:19:21', 'pagamento_pendente'),
(246, 9, 59, NULL, 10.00, 1.00, 0.50, 0.50, 0.00, 'KC25081511343353925', '', '2025-08-15 11:28:00', '2025-08-15 14:34:37', 'aprovado'),
(247, 9, 59, NULL, 100.00, 10.00, 5.00, 5.00, 0.00, 'KC25081511454878350', '', '2025-08-15 11:34:00', '2025-08-15 14:45:54', 'pagamento_pendente'),
(248, 160, 59, NULL, 5.00, 0.50, 0.25, 0.25, 0.00, 'KC25081511480994238', '', '2025-08-15 11:47:00', '2025-08-15 14:48:14', 'aprovado'),
(249, 161, 38, NULL, 109.00, 10.90, 5.45, 5.45, 0.00, 'KC25081514025500130', '', '2025-08-15 14:01:00', '2025-08-15 17:03:01', 'pagamento_pendente'),
(250, 160, 59, NULL, 10.00, 0.98, 0.49, 0.49, 0.00, 'KC25081516485354477', ' (Usado R$ 0,25 do saldo)', '2025-08-15 16:45:00', '2025-08-15 19:48:59', 'aprovado'),
(251, 160, 59, NULL, 10.00, 0.50, 0.25, 0.25, 0.00, 'KC25081517172115395', ' (Usado R$ 5,00 do saldo)', '2025-08-15 17:15:00', '2025-08-15 20:17:28', 'aprovado'),
(252, 142, 38, NULL, 999.99, 100.00, 50.00, 50.00, 0.00, 'KC25081609231091832', '', '2025-08-16 09:22:00', '2025-08-16 12:23:18', 'pagamento_pendente'),
(253, 142, 38, NULL, 10000.00, 1000.00, 500.00, 500.00, 0.00, 'KC25081609275948811', '', '2025-08-16 09:27:00', '2025-08-16 12:28:33', 'pagamento_pendente'),
(254, 163, 38, NULL, 1000.00, 100.00, 50.00, 50.00, 0.00, 'KC25081613535720763', '', '2025-08-16 13:53:00', '2025-08-16 16:54:08', 'pendente'),
(255, 164, 38, NULL, 1000.00, 100.00, 50.00, 50.00, 0.00, 'KC25081613573581975', '', '2025-08-16 13:56:00', '2025-08-16 16:57:56', 'pendente'),
(256, 165, 38, NULL, 1000.00, 100.00, 50.00, 50.00, 0.00, 'KC25081613585527133', '', '2025-08-16 13:57:00', '2025-08-16 16:59:00', 'pagamento_pendente'),
(257, 166, 38, NULL, 500.00, 50.00, 25.00, 25.00, 0.00, 'KC25081614181161080', '', '2025-08-16 14:17:00', '2025-08-16 17:18:18', 'pendente'),
(258, 142, 38, NULL, 200.00, 20.00, 10.00, 10.00, 0.00, 'KC25081714594230393', '', '2025-08-17 14:59:00', '2025-08-17 17:59:50', 'pagamento_pendente'),
(259, 167, 38, NULL, 200.00, 20.00, 10.00, 10.00, 0.00, 'KC25081813542148406', '', '2025-08-18 13:53:00', '2025-08-18 16:54:39', 'pendente'),
(260, 168, 38, NULL, 200.00, 20.00, 10.00, 10.00, 0.00, 'KC25082117035568747', '', '2025-08-21 17:03:00', '2025-08-21 20:03:59', 'pendente'),
(261, 169, 38, NULL, 50.00, 5.00, 2.50, 2.50, 0.00, 'KC25082414114712092', '', '2025-08-24 14:04:00', '2025-08-24 17:11:59', 'pendente'),
(262, 142, 38, NULL, 100.00, 10.00, 5.00, 5.00, 0.00, 'KC25082612022213876', '', '2025-08-26 12:02:00', '2025-08-26 15:02:28', 'aprovado'),
(263, 142, 38, NULL, 100.00, 9.50, 4.75, 4.75, 0.00, 'KC25082612164406653', ' (Usado R$ 5,00 do saldo)', '2025-08-26 12:16:00', '2025-08-26 15:16:55', 'pendente');

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

--
-- Despejando dados para a tabela `transacoes_comissao`
--

INSERT INTO `transacoes_comissao` (`id`, `tipo_usuario`, `usuario_id`, `loja_id`, `transacao_id`, `valor_total`, `valor_comissao`, `data_transacao`, `status`) VALUES
(172, 'admin', 1, 34, 192, 100.00, 5.00, '2025-07-09 17:53:00', 'pendente'),
(173, 'admin', 1, 34, 193, 100.00, 5.00, '2025-07-17 20:28:00', 'pendente'),
(174, 'admin', 1, 34, 194, 92.00, 4.60, '2025-07-17 20:47:00', 'pendente'),
(175, 'admin', 1, 34, 195, 100.00, 5.00, '2025-07-17 21:08:00', 'pendente'),
(176, 'admin', 1, 34, 196, 100.00, 5.00, '2025-07-30 17:32:00', 'pendente'),
(177, 'admin', 1, 34, 197, 10.00, 0.50, '2025-07-30 20:41:00', 'pendente'),
(178, 'admin', 1, 34, 198, 5.00, 0.25, '2025-08-01 11:19:00', 'pendente'),
(179, 'admin', 1, 34, 199, 5000.00, 250.00, '2025-08-01 12:03:00', 'pendente'),
(180, 'admin', 1, 34, 200, 10.00, 0.50, '2025-08-01 12:21:00', 'pendente'),
(181, 'admin', 1, 38, 201, 100.00, 5.00, '2025-08-01 15:28:00', 'pendente'),
(182, 'admin', 1, 34, 202, 10.00, 0.50, '2025-08-01 16:08:00', 'pendente'),
(183, 'admin', 1, 34, 203, 10.00, 0.50, '2025-08-01 18:44:00', 'pendente'),
(184, 'admin', 1, 34, 204, 10.00, 0.50, '2025-08-01 18:44:00', 'pendente'),
(185, 'admin', 1, 34, 205, 20.00, 1.00, '2025-08-01 18:45:00', 'pendente'),
(186, 'admin', 1, 34, 206, 20.00, 1.00, '2025-08-01 19:01:00', 'pendente'),
(187, 'admin', 1, 34, 207, 10.00, 0.50, '2025-08-01 19:15:00', 'pendente'),
(188, 'admin', 1, 34, 208, 10.00, 0.50, '2025-08-01 19:15:00', 'pendente'),
(189, 'admin', 1, 34, 209, 30.00, 1.50, '2025-08-01 19:21:00', 'pendente'),
(190, 'admin', 1, 34, 210, 10.00, 0.50, '2025-08-01 23:58:00', 'pendente'),
(191, 'admin', 1, 34, 211, 50.00, 2.50, '2025-08-02 00:10:00', 'pendente'),
(192, 'admin', 1, 34, 212, 25.00, 1.25, '2025-08-02 00:16:00', 'pendente'),
(193, 'admin', 1, 34, 213, 10.00, 0.50, '2025-08-02 00:18:00', 'pendente'),
(194, 'admin', 1, 34, 214, 20000.00, 1000.00, '2025-08-02 00:19:00', 'pendente'),
(195, 'admin', 1, 34, 215, 5.00, 0.25, '2025-08-02 00:27:00', 'pendente'),
(196, 'admin', 1, 34, 216, 10.00, 0.50, '2025-08-04 15:42:00', 'pendente'),
(197, 'admin', 1, 34, 217, 10.00, 0.50, '2025-08-13 18:38:00', 'pendente'),
(198, 'admin', 1, 34, 218, 5400.00, 270.00, '2025-08-13 23:20:00', 'pendente'),
(199, 'admin', 1, 34, 219, 2000.00, 100.00, '2025-08-13 23:21:00', 'pendente'),
(200, 'admin', 1, 34, 220, 20.00, 1.00, '2025-08-13 23:23:00', 'pendente'),
(201, 'admin', 1, 34, 221, 20.00, 1.00, '2025-08-13 23:26:00', 'pendente'),
(202, 'admin', 1, 38, 222, 100.00, 5.00, '2025-08-14 04:30:00', 'pendente'),
(203, 'admin', 1, 38, 223, 100.00, 5.00, '2025-08-14 05:22:00', 'pendente'),
(204, 'admin', 1, 34, 224, 600.00, 30.00, '2025-08-14 08:08:00', 'pendente'),
(205, 'admin', 1, 38, 225, 200.00, 10.00, '2025-08-14 10:54:00', 'pendente'),
(206, 'admin', 1, 38, 226, 50.00, 2.50, '2025-08-14 12:02:00', 'pendente'),
(207, 'admin', 1, 38, 227, 50.00, 2.50, '2025-08-14 12:13:00', 'pendente'),
(208, 'admin', 1, 38, 228, 100.00, 5.00, '2025-08-14 14:11:00', 'aprovado'),
(209, 'admin', 1, 38, 229, 100.00, 5.00, '2025-08-14 15:45:00', 'pendente'),
(210, 'admin', 1, 38, 230, 10000.00, 500.00, '2025-08-14 15:48:00', 'pendente'),
(211, 'admin', 1, 38, 231, 100.00, 5.00, '2025-08-14 16:46:00', 'pendente'),
(212, 'admin', 1, 38, 232, 100.00, 5.00, '2025-08-14 16:49:00', 'pendente'),
(213, 'admin', 1, 38, 233, 100.00, 5.00, '2025-08-14 16:49:00', 'pendente'),
(214, 'admin', 1, 38, 234, 100.00, 5.00, '2025-08-14 16:57:00', 'pendente'),
(215, 'admin', 1, 38, 235, 500.00, 25.00, '2025-08-14 17:08:00', 'pendente'),
(216, 'admin', 1, 38, 236, 100.00, 5.00, '2025-08-14 17:13:00', 'pendente'),
(217, 'admin', 1, 38, 237, 100.00, 5.00, '2025-08-14 17:14:00', 'pendente'),
(218, 'admin', 1, 38, 238, 500.00, 25.00, '2025-08-14 22:02:00', 'pendente'),
(219, 'admin', 1, 34, 239, 100.00, 5.00, '2025-08-13 23:47:00', 'pendente'),
(220, 'admin', 1, 34, 240, 5.00, 0.25, '2025-08-15 10:39:00', 'pendente'),
(221, 'admin', 1, 34, 241, 5.00, 0.25, '2025-08-15 10:40:00', 'pendente'),
(222, 'admin', 1, 59, 242, 5.00, 0.25, '2025-08-15 10:54:00', 'pendente'),
(223, 'admin', 1, 59, 243, 10.00, 0.50, '2025-08-15 10:59:00', 'pendente'),
(224, 'admin', 1, 59, 244, 10.00, 0.50, '2025-08-15 11:11:00', 'pendente'),
(225, 'admin', 1, 59, 245, 5.00, 0.25, '2025-08-15 11:19:00', 'pendente'),
(226, 'admin', 1, 59, 246, 10.00, 0.50, '2025-08-15 11:28:00', 'pendente'),
(227, 'admin', 1, 59, 247, 100.00, 5.00, '2025-08-15 11:34:00', 'pendente'),
(228, 'admin', 1, 59, 248, 5.00, 0.25, '2025-08-15 11:47:00', 'pendente'),
(229, 'admin', 1, 38, 249, 109.00, 5.45, '2025-08-15 14:01:00', 'pendente'),
(230, 'admin', 1, 59, 250, 9.75, 0.49, '2025-08-15 16:45:00', 'pendente'),
(231, 'admin', 1, 59, 251, 5.00, 0.25, '2025-08-15 17:15:00', 'pendente'),
(232, 'admin', 1, 38, 252, 999.99, 50.00, '2025-08-16 09:22:00', 'pendente'),
(233, 'admin', 1, 38, 253, 10000.00, 500.00, '2025-08-16 09:27:00', 'pendente'),
(234, 'admin', 1, 38, 254, 1000.00, 50.00, '2025-08-16 13:53:00', 'pendente'),
(235, 'admin', 1, 38, 255, 1000.00, 50.00, '2025-08-16 13:56:00', 'pendente'),
(236, 'admin', 1, 38, 256, 1000.00, 50.00, '2025-08-16 13:57:00', 'pendente'),
(237, 'admin', 1, 38, 257, 500.00, 25.00, '2025-08-16 14:17:00', 'pendente'),
(238, 'admin', 1, 38, 258, 200.00, 10.00, '2025-08-17 14:59:00', 'pendente'),
(239, 'admin', 1, 38, 259, 200.00, 10.00, '2025-08-18 13:53:00', 'pendente'),
(240, 'admin', 1, 38, 260, 200.00, 10.00, '2025-08-21 17:03:00', 'pendente'),
(241, 'admin', 1, 38, 261, 50.00, 2.50, '2025-08-24 14:04:00', 'pendente'),
(242, 'admin', 1, 38, 262, 100.00, 5.00, '2025-08-26 12:02:00', 'aprovado'),
(243, 'admin', 1, 38, 263, 95.00, 4.75, '2025-08-26 12:16:00', 'pendente');

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

--
-- Despejando dados para a tabela `transacoes_saldo_usado`
--

INSERT INTO `transacoes_saldo_usado` (`id`, `transacao_id`, `usuario_id`, `loja_id`, `valor_usado`, `data_uso`) VALUES
(25, 194, 9, 34, 10.00, '2025-07-17 23:48:09'),
(26, 250, 160, 59, 0.25, '2025-08-15 19:49:00'),
(27, 251, 160, 59, 5.00, '2025-08-15 20:17:28'),
(28, 263, 142, 38, 5.00, '2025-08-26 15:16:56');

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

--
-- Despejando dados para a tabela `transacoes_status_historico`
--

INSERT INTO `transacoes_status_historico` (`id`, `transacao_id`, `status_anterior`, `status_novo`, `observacao`, `data_alteracao`) VALUES
(2, 228, 'pendente', 'aprovado', '', '2025-08-14 18:42:19');

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

INSERT INTO `usuarios` (`id`, `nome`, `email`, `telefone`, `cpf`, `senha_hash`, `data_criacao`, `ultimo_login`, `status`, `tipo`, `tipo_cliente`, `loja_criadora_id`, `google_id`, `avatar_url`, `provider`, `email_verified`, `two_factor_enabled`, `two_factor_code`, `two_factor_expires`, `two_factor_verified`, `tentativas_2fa`, `bloqueado_2fa_ate`, `ultimo_2fa_enviado`, `loja_vinculada_id`, `subtipo_funcionario`, `mvp`) VALUES
(9, 'Kaua Matheus da Silva Lope', 'kauamatheus920@gmail.com', '38991045205', '15692134616', '$2y$10$qcI8rS6auQPYCbd6vhpeaeW2YL0erp84ZtgJhqWkKpjeqb6iXGzEi', '2025-05-05 19:45:04', '2025-08-15 15:13:41', 'ativo', 'cliente', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(10, 'Frederico', 'repertoriofredericofagundes@gmail.com', NULL, NULL, '$2y$10$yGjHS8rJq49AuLeuVrZHkOUPSkzNLs79A6H52HwwY8DpzLA2A95Ay', '2025-05-05 21:45:46', '2025-05-05 21:46:41', 'ativo', 'cliente', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(11, 'Kaua Lopés', 'kauanupix@gmail.com', NULL, NULL, '$2y$10$PHgXAUK2k/I0Cyi0.FdIjOE3gCmHhdHRx822btvWD4c4ZvuRhdauW', '2025-05-07 12:19:05', '2025-08-27 14:19:52', 'ativo', 'admin', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(55, 'Kaua Matheus da Silva Lopes', 'kaua@syncholding.com.br', '(38) 99104-5205', NULL, '$2y$10$VwSfpE6zvr72HI19RLFLF.Dw4VKMjbGajc5l6mN3jQiaoHK1GUR0u', '2025-05-25 19:17:34', '2025-08-27 13:00:10', 'ativo', 'loja', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'sim'),
(57, 'Kaua Matheus', 'kauamatheus9208750@gmail.com', '(38) 99104-5205', NULL, '$2y$10$W0eB1al5yMQVFCLnOdEMDu26vRzRKmK3e5kWFpIpKtyXe9ZSAusWS', '2025-05-30 20:57:07', '2025-07-15 01:02:21', 'ativo', 'funcionario', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, 34, 'gerente', 'nao'),
(60, 'lkjdretlvssss www.yandex.ru', 'john@protdskeit.ru', '+74957965766', NULL, '$2y$10$yYb1YpgvzBUEh6x1Jisr7uWPHbuaBCNHY2b3J5Kb.xtNHxzWb4X2m', '2025-06-02 22:10:05', NULL, 'bloqueado', 'cliente', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(61, 'Frederico Fagundes', 'fredericofagundes0@gmail.com', NULL, NULL, '$2y$10$Lcszebxu3vPCg4dNkDhP7eAvk07mvjEvFLNz4pFYdMveo0skeNFWi', '2025-06-05 17:48:45', '2025-08-26 15:04:43', 'ativo', 'admin', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(62, 'NAEWTRER1485703NEYRTHYT', 'eyaenduo@ronaldofmail.com', '82393754362', NULL, '$2y$10$tPUpy8SI/0jr.xgvv4dOiuVxOahVPdu/rh1RdEMmlGAMtiRJpFh2u', '2025-06-07 01:52:05', '2025-06-07 01:52:08', 'bloqueado', 'cliente', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(63, 'KLUBE DIGITAL', 'acessoriafredericofagundes@gmail.com', '(34) 99335-7697', NULL, '$2y$10$VuDfT8bieSTLToSbmd3EzOVkmwNLYeC9itIfm2kxl3f54OpnZpd5O', '2025-06-07 16:11:42', '2025-08-26 16:46:31', 'ativo', 'loja', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(69, 'Вам перевод 177475 руб. получить тут  www.tinyurl.com/n16PO5Sz MTGJNF4261TUJE', 'volnaya.yana00@mail.ru', '89159446518', NULL, '$2y$10$Cs3mycj4RLTJijxSy1Rb9.i9bBaiiWBaVvWH2O2YiLTHRZKf5hUJ.', '2025-06-09 11:00:27', '2025-06-09 11:00:30', 'bloqueado', 'cliente', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(70, '1Вам перевод 189333 руб. получить тут  www.tinyurl.com/61L0cUD5 SVWVE4261TUJE', 'rwkrlmzcbhjhv@tjmucih.com', '84846677328', NULL, '$2y$10$hrE6cKv8WPgeXNrrhM.4C.gZJUgRrDp/fXSX4EmcFwPhDFdzLg5La', '2025-06-09 15:21:58', '2025-06-09 15:22:01', 'bloqueado', 'cliente', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(71, 'Roberto Magalhães Corrêa ', 'ropatosmg@gmail.com', '5534993171602', NULL, '$2y$10$77e0qthXH0AJkZFGJR0APu9fifxY/M8BvkNOGrHMBMBmAv7W3SohO', '2025-06-10 00:08:12', '2025-06-10 00:08:51', 'ativo', 'cliente', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(72, 'Sabrina', 'sabrina290623@gmail.com', '(34) 99842-3591', NULL, '$2y$10$1FNgzRYI0AbiCYymdAgBlOWe2uIJn.PwU24.AUe3UP7pf5bA1ImJO', '2025-06-10 00:11:51', '2025-06-10 00:12:00', 'ativo', 'cliente', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(73, 'Frederico Fagundes', 'klubecash@gmail.com', '(34) 99335-7697', NULL, '$2y$10$cM0f9co4abNHzxiOD0ZgjuZchVNk9o3v6mOadv2aByV.s339xdTPu', '2025-06-10 00:14:24', '2025-08-16 12:25:10', 'ativo', 'cliente', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(74, 'Amanda rosa ', 'aricken31@gmail.com', '(34) 99975-8423', NULL, '$2y$10$aV.0Wj3E2dMRHSX7KqHa9u0.LsHiHDdBEpD/yOzCB.QC4uFcu72/K', '2025-06-10 00:15:41', NULL, 'ativo', 'cliente', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(75, 'Felipe Vieira ribeiro', 'ribeirofilepe34@gmail.com', '(34) 99712-8998', NULL, '$2y$10$MpCAnHh7GN8ToE7b3FGzcurkrl8TA4Ffm69NECs0ePdMJcuvW0iNC', '2025-06-10 00:40:43', '2025-08-20 01:01:35', 'ativo', 'cliente', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(76, 'Gabriela Steffany da Silva ', 'gabisteffany@icloud.com', '(34) 98700-3621', NULL, '$2y$10$eFewesljEaKuqWpeFRnuy.Xh/FJ4sXLz8thior8hzQUytyrDisYay', '2025-06-10 00:41:33', '2025-06-10 00:45:29', 'ativo', 'cliente', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(77, 'Bruna Leal Ribeiro', 'brunna.leal00@gmail.com', '(34) 99982-8286', NULL, '$2y$10$Og4FZ3ealFiMAvj2gAIR0etd35frBRFNz/0CoefkAOqXkjOK/0ZLy', '2025-06-10 00:41:56', '2025-06-10 00:42:07', 'ativo', 'cliente', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(78, 'Gabriele Soares Souza ', 'soaresgabriele25@gmail.com', '(34) 99960-8386', NULL, '$2y$10$BgfPzZTWZ4Qa412NtFZQQ.QAoO9k8Y5G.GFiaLvBIqX5rbUt99sfG', '2025-06-10 02:24:49', '2025-06-10 02:25:03', 'ativo', 'cliente', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(79, 'Pedro Henrique Duarte ', 'pedrohduarte98@gmail.com', '5534998437197', NULL, '$2y$10$CSUkXDPCL6rdd2cMhEhPKO0dq.D7ioZ9ywNef8wf0CFcBDufwgBeu', '2025-06-10 05:22:24', '2025-06-10 05:22:59', 'ativo', 'cliente', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(80, 'Pirapora', 'kaualopes@unipam.edu.br', '(38) 99104-5205', NULL, '$2y$10$VOJ.OE4rGXEWrq55slY41uz0POqQ2ZCph71mpaW9C3gIdoF38TXcm', '2025-06-10 18:44:22', NULL, 'ativo', 'loja', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(81, 'Lucas Fagundes da Silva ', 'lucasfagundes934@gmail.com', '(34) 99218-9099', NULL, '$2y$10$obpHzgu/lTbA9BLIWsz8yebeD3rroMp9cW.Xy/MxbW8A7mOom9ox2', '2025-06-11 19:29:20', '2025-06-13 00:57:44', 'ativo', 'cliente', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(83, 'sadfdasf', 'kaua@synchosdfading.com.br', '38991045205', NULL, '$2y$10$mMiqzDsntv80NvzwKJz4QOLOtkeYOOfEu8dDUt5Y1gDMCmmLxUyJe', '2025-06-19 19:21:30', '2025-07-30 18:46:53', 'ativo', 'funcionario', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, 34, 'vendedor', 'nao'),
(84, 'Поздравляем! Персональный эксклюзивный вознаграждение в ожидании! Посмотрите информацию по ссылке ww', 'pstump00@inbox.ru', '85358863555', NULL, '$2y$10$rYiX1Fidl5IrP/RNlnfjweO2WHrQV6xchU2bH.zxIl5IVPRutvJVi', '2025-06-27 17:06:11', '2025-06-27 17:06:13', 'inativo', 'cliente', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(85, 'teste app 01', 'cdf@gmail.com', '38991045205', NULL, '$2a$10$EzTgTK3gm5zjRbzT2xtzueEMNg9JSxsopZwa7gMNFMdqhRbyqRVXS', '2025-07-02 17:54:10', '2025-07-02 17:54:57', 'ativo', 'cliente', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(86, 'Jennifer aryane ', 'jenniferlimaxz@gmail.com', '(55) 98497-1703', NULL, '$2y$10$Qeai.iOuOCYSrTMmFm7b1OE4WeHvgzmem4SLeJGa20bvjJJGzhZYG', '2025-07-07 17:05:39', NULL, 'ativo', 'cliente', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(87, 'Jennifer aryane ', 'jenniferlopesxz@gmail.com', '(55) 98497-1703', NULL, '$2y$10$FxTmg8XDk50WOKlUAZzaeOAF.sPVIgcZHyryCUlZMern1Hy363CFO', '2025-07-07 17:06:49', NULL, 'ativo', 'cliente', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(88, 'Rafael Augusto Alves Silva ', 'rafaelaugustoalvessilva5@gmail.com', '(34) 99665-7725', NULL, '$2y$10$B8CcTlZLjn2swhyPXdjnQeq3sl5.j6nnyVbqkL9wwzkcM.ulaFBwW', '2025-07-07 18:28:49', '2025-07-07 22:36:48', 'ativo', 'cliente', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(110, 'Kaua Lopés', 'kauanupiDx@gmail.com', '38991045205', NULL, '$2y$10$vpeOB3IQJYaja819ci7XxOM1SYMl7YhPoeYTz6jIyB5i9VJlSOTrG', '2025-07-14 21:28:43', '2025-07-26 19:27:40', 'ativo', 'funcionario', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, 34, 'financeiro', 'nao'),
(111, 'Ana Caroliny Ferreira De Almeida ', 'anacarolinyferreiradealmeida5@gmail.com', '(11) 97880-6283', NULL, '$2y$10$di3MoK7n.I9v3S3UN.xF6.qQX4w.BlqxfDl7cEGjCJElaAyNEYFM6', '2025-07-16 03:14:07', NULL, 'ativo', 'cliente', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(118, 'Clarissa', 'clarissalopes296@gmail.com', '(38) 99104-5205', NULL, '$2y$10$g/2OVjHI54UuC4zbBiiNSuFk.3UIJtQbSG1hoEb/pxnIlNQwQk6UO', '2025-07-22 21:39:03', '2025-07-26 19:28:54', 'ativo', 'cliente', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(119, 'Mfuehudwj hiwjswdwidjwidji jdiwjswihdfeufhiwj ijdiwjwihdiwkdoq jiwjdwidjwifjei jwdodkwofjiehiehgiejd', 'nomin.momin+102e0@mail.ru', '83442221124', NULL, '$2y$10$/PwMdW2hSvUGZSN39geuE.BdK8BP5bboXQhC7f7jToP48sD1Bjkra', '2025-07-28 10:26:06', NULL, 'inativo', 'cliente', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(120, 'Вам перевод 166164 руб. получить тут  https://tinyurl.com/a76Z4D3C JUYEGRT9142JUYEGRT', 'batuzovv94@mail.ru', '83775482374', NULL, '$2y$10$1ArZ6XvAPanuPZlrt8agVukGtFdq663SR49DxIcqLB2edNAma590K', '2025-08-02 11:01:58', '2025-08-02 11:01:59', 'inativo', 'cliente', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(121, 'Kaua', '', '38991045003', NULL, NULL, '2025-08-13 22:05:06', NULL, 'ativo', 'cliente', 'visitante', 34, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(134, 'KAua', 'visitante_38991045004_loja_34@klubecash.local', '38991045004', NULL, NULL, '2025-08-14 01:50:58', NULL, 'ativo', 'cliente', 'visitante', 34, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(135, 'Kaua Lopéscd', 'visitante_11450807392_loja_34@klubecash.local', '11450807392', NULL, NULL, '2025-08-14 02:04:38', NULL, 'ativo', 'cliente', 'visitante', 34, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(137, 'Teste Corrigido 23:09:03', 'visitante_11233143249_loja_34@klubecash.local', '11233143249', NULL, NULL, '2025-08-14 02:09:03', NULL, 'ativo', 'cliente', 'visitante', 34, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(138, 'João Teste', 'visitante_11987654321_loja_34@klubecash.local', '11987654321', NULL, NULL, '2025-08-14 02:18:32', NULL, 'ativo', 'cliente', 'visitante', 34, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(139, 'Cecilia', 'visitante_34991191534_loja_34@klubecash.local', '34991191534', NULL, NULL, '2025-08-14 02:21:26', NULL, 'ativo', 'cliente', 'visitante', 34, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(140, 'Frederico', 'visitante_34993357697_loja_34@klubecash.local', '34993357697', NULL, NULL, '2025-08-14 02:27:29', NULL, 'ativo', 'cliente', 'visitante', 34, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(141, 'Jaqueline maria ', 'sousalima20189@gmail.com', '(34) 99771-3760', NULL, '$2y$10$t3FvhtIQs/Z8azhQl6WUbeubrf1Rj5J15B8Fh6KW4OKC2jHrQNRla', '2025-08-14 07:07:11', '2025-08-14 07:07:29', 'ativo', 'cliente', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(142, 'Frederico Fagundes', 'visitante_34993357697_loja_38@klubecash.local', '34993357697', NULL, NULL, '2025-08-14 07:31:17', NULL, 'ativo', 'cliente', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(143, 'jean junior', 'visitante_34992708603_loja_38@klubecash.local', '34992708603', NULL, NULL, '2025-08-14 08:46:55', NULL, 'ativo', 'cliente', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(144, 'roberto magalhaes', 'visitante_34993171602_loja_38@klubecash.local', '34993171602', NULL, NULL, '2025-08-14 09:10:45', NULL, 'ativo', 'cliente', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(145, 'Frederico Fagundes', 'visitante_3497635735_loja_38@klubecash.local', '3497635735', NULL, NULL, '2025-08-14 13:54:43', NULL, 'ativo', 'cliente', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(146, 'Kamilla', 'visitante_34988247844_loja_38@klubecash.local', '34988247844', NULL, NULL, '2025-08-14 15:03:01', NULL, 'ativo', 'cliente', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(147, 'Fábio Eduardo', 'visitante_34992369765_loja_38@klubecash.local', '34992369765', NULL, NULL, '2025-08-14 15:13:32', NULL, 'ativo', 'cliente', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(148, 'Frederico', 'visitante_34993357698_loja_38@klubecash.local', '34993357698', NULL, NULL, '2025-08-14 17:11:46', NULL, 'ativo', 'cliente', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(149, 'giovanna moreira', 'visitante_34963466409_loja_38@klubecash.local', '34963466409', NULL, NULL, '2025-08-14 18:46:25', NULL, 'ativo', 'cliente', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(150, 'GUIUGAO', 'visitante_34996346409_loja_38@klubecash.local', '34996346409', NULL, NULL, '2025-08-14 18:49:50', NULL, 'ativo', 'cliente', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(151, 'Ana Livia', 'visitante_34998176771_loja_38@klubecash.local', '34998176771', NULL, NULL, '2025-08-14 19:47:25', NULL, 'ativo', 'cliente', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(152, 'Alessandra Regis', 'visitante_34991927053_loja_38@klubecash.local', '34991927053', NULL, NULL, '2025-08-14 19:50:17', NULL, 'ativo', 'cliente', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(153, 'Cleides felix', 'visitante_38998693037_loja_38@klubecash.local', '38998693037', NULL, NULL, '2025-08-14 19:53:57', NULL, 'ativo', 'cliente', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(154, 'Aurélia Cristina', 'visitante_34998721675_loja_38@klubecash.local', '34998721675', NULL, NULL, '2025-08-14 19:57:57', NULL, 'ativo', 'cliente', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(155, 'Bruna leal', 'visitante_34999828286_loja_38@klubecash.local', '34999828286', NULL, NULL, '2025-08-14 20:09:40', NULL, 'ativo', 'cliente', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(156, 'Vitória Filipa', 'visitante_55349972501_loja_38@klubecash.local', '55349972501', NULL, NULL, '2025-08-14 20:13:31', NULL, 'ativo', 'cliente', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(157, 'Pyetro swanson', 'visitante_34991251830_loja_38@klubecash.local', '34991251830', NULL, NULL, '2025-08-14 20:15:45', NULL, 'ativo', 'cliente', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(158, 'Carla Gonçalves', 'visitante_34998966741_loja_38@klubecash.local', '34998966741', NULL, NULL, '2025-08-15 01:02:50', NULL, 'ativo', 'cliente', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(159, 'Sync Holding', 'kauamathes123487654@gmail.com', '(34) 99800-2600', '04355521630', '$2y$10$W4Mw0j5/DhS.p0/I.D0he.aekBeq.O9.5xVoS8wntjF4L3U3P6OPW', '2025-08-15 13:52:55', '2025-08-16 13:56:00', 'ativo', 'loja', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'sim'),
(160, 'Cecilia', 'visitante_34991191534_loja_59@klubecash.local', '34991191534', NULL, NULL, '2025-08-15 14:47:55', NULL, 'ativo', 'cliente', 'visitante', 59, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(161, 'Evaldo Gabriel', 'visitante_34991247963_loja_38@klubecash.local', '34991247963', NULL, NULL, '2025-08-15 17:02:42', NULL, 'ativo', 'cliente', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(162, 'Cecilia 3', 'visitante_34998002600_loja_59@klubecash.local', '34998002600', NULL, NULL, '2025-08-15 19:30:55', NULL, 'ativo', 'cliente', 'visitante', 59, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(163, 'maria versiani', 'visitante_34997201631_loja_38@klubecash.local', '34997201631', NULL, NULL, '2025-08-16 16:53:41', NULL, 'ativo', 'cliente', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(164, 'Laisla Fagundes', 'visitante_55349963106_loja_38@klubecash.local', '55349963106', NULL, NULL, '2025-08-16 16:57:25', NULL, 'ativo', 'cliente', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(165, 'Laisla Fagundes', 'visitante_34996310606_loja_38@klubecash.local', '34996310606', NULL, NULL, '2025-08-16 16:58:42', NULL, 'ativo', 'cliente', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(166, 'Luh Duarte', 'visitante_34999908465_loja_38@klubecash.local', '34999908465', NULL, NULL, '2025-08-16 17:17:59', NULL, 'ativo', 'cliente', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(167, 'Ellen Monteiro', 'visitante_34992244799_loja_38@klubecash.local', '34992244799', NULL, NULL, '2025-08-18 16:53:57', NULL, 'ativo', 'cliente', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(168, 'Felipe Vieira', 'visitante_34997128998_loja_38@klubecash.local', '34997128998', NULL, NULL, '2025-08-21 20:03:48', NULL, 'ativo', 'cliente', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(169, 'Renato', 'visitante_34999975070_loja_38@klubecash.local', '34999975070', NULL, NULL, '2025-08-24 17:11:25', NULL, 'ativo', 'cliente', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao');

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
(9, 57, '', '(38) 99104-5205', 'kauamatheus920875@gmail.com'),
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
(5, 9, '38705-376', 'Francisco Braga da Mota', '146', 'Ap 101', 'Jd Panoramico', 'Patos de minas', 'MG', 1),
(7, 57, '38705-376', 'Francisco Braga da Mota', '146', 'Ap 101', 'Jardim Panoramico', 'Patos de Minas', 'MG', 1);

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

--
-- Despejando dados para a tabela `webhook_errors`
--

INSERT INTO `webhook_errors` (`id`, `mp_payment_id`, `error_message`, `payload`, `created_at`, `resolved`) VALUES
(1, '115850564752', 'There is no active transaction', '{\"action\":\"payment.updated\",\"api_version\":\"v1\",\"data\":{\"id\":\"115850564752\"},\"date_created\":\"2025-06-21T00:14:47Z\",\"id\":122337205178,\"live_mode\":true,\"type\":\"payment\",\"user_id\":\"2320640278\"}', '2025-06-21 00:15:37', 0),
(2, '115852454888', 'There is no active transaction', '{\"action\":\"payment.updated\",\"api_version\":\"v1\",\"data\":{\"id\":\"115852454888\"},\"date_created\":\"2025-06-21T00:34:35Z\",\"id\":122262197161,\"live_mode\":true,\"type\":\"payment\",\"user_id\":\"2320640278\"}', '2025-06-21 00:35:34', 0),
(3, '115370613449', 'There is no active transaction', '{\"action\":\"payment.updated\",\"api_version\":\"v1\",\"data\":{\"id\":\"115370613449\"},\"date_created\":\"2025-06-21T01:32:49Z\",\"id\":122339137730,\"live_mode\":true,\"type\":\"payment\",\"user_id\":\"2320640278\"}', '2025-06-21 01:33:14', 0),
(4, '115858654164', 'There is no active transaction', '{\"action\":\"payment.updated\",\"api_version\":\"v1\",\"data\":{\"id\":\"115858654164\"},\"date_created\":\"2025-06-21T01:34:44Z\",\"id\":122263682489,\"live_mode\":true,\"type\":\"payment\",\"user_id\":\"2320640278\"}', '2025-06-21 01:36:08', 0),
(5, '115371728685', 'There is no active transaction', '{\"action\":\"payment.updated\",\"api_version\":\"v1\",\"data\":{\"id\":\"115371728685\"},\"date_created\":\"2025-06-21T01:37:09Z\",\"id\":122263713091,\"live_mode\":true,\"type\":\"payment\",\"user_id\":\"2320640278\"}', '2025-06-21 01:37:30', 0),
(6, '117508948823', 'There is no active transaction', '{\"action\":\"payment.updated\",\"api_version\":\"v1\",\"data\":{\"id\":\"117508948823\"},\"date_created\":\"2025-07-09T21:04:09Z\",\"id\":122765115239,\"live_mode\":true,\"type\":\"payment\",\"user_id\":\"2320640278\"}', '2025-07-09 21:04:36', 0),
(7, '118981319912', 'There is no active transaction', '{\"action\":\"payment.updated\",\"api_version\":\"v1\",\"data\":{\"id\":\"118981319912\"},\"date_created\":\"2025-07-17T23:29:09Z\",\"id\":122991089651,\"live_mode\":true,\"type\":\"payment\",\"user_id\":\"2320640278\"}', '2025-07-17 23:31:33', 0),
(8, '118477443891', 'There is no active transaction', '{\"action\":\"payment.updated\",\"api_version\":\"v1\",\"data\":{\"id\":\"118477443891\"},\"date_created\":\"2025-07-17T23:48:24Z\",\"id\":122991610891,\"live_mode\":true,\"type\":\"payment\",\"user_id\":\"2320640278\"}', '2025-07-17 23:48:38', 0),
(9, '119506155656', 'There is no active transaction', '{\"action\":\"payment.updated\",\"api_version\":\"v1\",\"data\":{\"id\":\"119506155656\"},\"date_created\":\"2025-07-22T16:21:52Z\",\"id\":123195852216,\"live_mode\":true,\"type\":\"payment\",\"user_id\":\"2320640278\"}', '2025-07-22 16:26:29', 0),
(10, '120409409758', 'There is no active transaction', '{\"action\":\"payment.updated\",\"api_version\":\"v1\",\"data\":{\"id\":\"120409409758\"},\"date_created\":\"2025-07-30T20:32:55Z\",\"id\":123412679432,\"live_mode\":true,\"type\":\"payment\",\"user_id\":\"2320640278\"}', '2025-07-30 20:33:24', 0),
(11, '119921101137', 'There is no active transaction', '{\"action\":\"payment.updated\",\"api_version\":\"v1\",\"data\":{\"id\":\"119921101137\"},\"date_created\":\"2025-07-30T23:41:52Z\",\"id\":123336906369,\"live_mode\":true,\"type\":\"payment\",\"user_id\":\"2320640278\"}', '2025-07-30 23:42:58', 0),
(12, '120107345073', 'There is no active transaction', '{\"action\":\"payment.updated\",\"api_version\":\"v1\",\"data\":{\"id\":\"120107345073\"},\"date_created\":\"2025-08-01T14:19:32Z\",\"id\":123458987796,\"live_mode\":true,\"type\":\"payment\",\"user_id\":\"2320640278\"}', '2025-08-01 14:20:08', 0),
(13, '120112327973', 'There is no active transaction', '{\"action\":\"payment.updated\",\"api_version\":\"v1\",\"data\":{\"id\":\"120112327973\"},\"date_created\":\"2025-08-01T15:04:17Z\",\"id\":123378873449,\"live_mode\":true,\"type\":\"payment\",\"user_id\":\"2320640278\"}', '2025-08-01 15:04:52', 0),
(14, '120661271112', 'There is no active transaction', '{\"action\":\"payment.updated\",\"api_version\":\"v1\",\"data\":{\"id\":\"120661271112\"},\"date_created\":\"2025-08-01T19:20:38Z\",\"id\":123466628670,\"live_mode\":true,\"type\":\"payment\",\"user_id\":\"2320640278\"}', '2025-08-01 19:21:29', 0),
(15, '122201152572', 'There is no active transaction', '{\"action\":\"payment.updated\",\"api_version\":\"v1\",\"data\":{\"id\":\"122201152572\"},\"date_created\":\"2025-08-14T02:23:59Z\",\"id\":123734489711,\"live_mode\":true,\"type\":\"payment\",\"user_id\":\"2320640278\"}', '2025-08-14 02:24:28', 0),
(16, '122200655362', 'There is no active transaction', '{\"action\":\"payment.updated\",\"api_version\":\"v1\",\"data\":{\"id\":\"122200655362\"},\"date_created\":\"2025-08-14T02:30:17Z\",\"id\":123817628936,\"live_mode\":true,\"type\":\"payment\",\"user_id\":\"2320640278\"}', '2025-08-14 02:30:52', 0),
(17, '122430205382', 'There is no active transaction', '{\"action\":\"payment.updated\",\"api_version\":\"v1\",\"data\":{\"id\":\"122430205382\"},\"date_created\":\"2025-08-15T12:10:09Z\",\"id\":123852997600,\"live_mode\":true,\"type\":\"payment\",\"user_id\":\"2320640278\"}', '2025-08-15 12:11:08', 0),
(18, '122439469480', 'There is no active transaction', '{\"action\":\"payment.updated\",\"api_version\":\"v1\",\"data\":{\"id\":\"122439469480\"},\"date_created\":\"2025-08-15T13:40:48Z\",\"id\":123854590562,\"live_mode\":true,\"type\":\"payment\",\"user_id\":\"2320640278\"}', '2025-08-15 13:41:05', 0),
(19, '122448314624', 'There is no active transaction', '{\"action\":\"payment.updated\",\"api_version\":\"v1\",\"data\":{\"id\":\"122448314624\"},\"date_created\":\"2025-08-15T14:46:26Z\",\"id\":123855909284,\"live_mode\":true,\"type\":\"payment\",\"user_id\":\"2320640278\"}', '2025-08-15 14:46:52', 0),
(20, '122448476668', 'There is no active transaction', '{\"action\":\"payment.updated\",\"api_version\":\"v1\",\"data\":{\"id\":\"122448476668\"},\"date_created\":\"2025-08-15T14:48:36Z\",\"id\":123772655989,\"live_mode\":true,\"type\":\"payment\",\"user_id\":\"2320640278\"}', '2025-08-15 14:48:52', 0),
(21, '121952033987', 'There is no active transaction', '{\"action\":\"payment.updated\",\"api_version\":\"v1\",\"data\":{\"id\":\"121952033987\"},\"date_created\":\"2025-08-15T19:49:48Z\",\"id\":123780130799,\"live_mode\":true,\"type\":\"payment\",\"user_id\":\"2320640278\"}', '2025-08-15 19:50:16', 0),
(22, '121956141447', 'There is no active transaction', '{\"action\":\"payment.updated\",\"api_version\":\"v1\",\"data\":{\"id\":\"121956141447\"},\"date_created\":\"2025-08-15T20:17:42Z\",\"id\":123864204850,\"live_mode\":true,\"type\":\"payment\",\"user_id\":\"2320640278\"}', '2025-08-15 20:18:06', 0);

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
(336, 'cashback_liberado', '34991191534', '🎉 *Klube Cash - Cashback Liberado!*\n\nSeu cashback de *R$ 0,25* da loja *Sync Holding* foi liberad', 1, NULL, NULL, 0, '{\"valor_cashback\":\"0.25\",\"nome_loja\":\"Sync Holding\"}', '18.213.114.129', 'MercadoPago WebHook v1.0 payment', '2025-08-15 17:18:06');

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
  ADD KEY `usuario_id` (`usuario_id`);

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
  ADD KEY `idx_usuarios_telefone` (`telefone`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `admin_saldo`
--
ALTER TABLE `admin_saldo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `admin_saldo_movimentacoes`
--
ALTER TABLE `admin_saldo_movimentacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- AUTO_INCREMENT de tabela `cashback_notificacoes`
--
ALTER TABLE `cashback_notificacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `cashback_saldos`
--
ALTER TABLE `cashback_saldos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=124;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT de tabela `lojas_contato`
--
ALTER TABLE `lojas_contato`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `lojas_endereco`
--
ALTER TABLE `lojas_endereco`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de tabela `lojas_favoritas`
--
ALTER TABLE `lojas_favoritas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=597;

--
-- AUTO_INCREMENT de tabela `pagamentos_comissao`
--
ALTER TABLE `pagamentos_comissao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1174;

--
-- AUTO_INCREMENT de tabela `pagamentos_devolucoes`
--
ALTER TABLE `pagamentos_devolucoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `pagamentos_transacoes`
--
ALTER TABLE `pagamentos_transacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=182;

--
-- AUTO_INCREMENT de tabela `pagamento_transacoes`
--
ALTER TABLE `pagamento_transacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `recuperacao_senha`
--
ALTER TABLE `recuperacao_senha`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT de tabela `store_balance_payments`
--
ALTER TABLE `store_balance_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de tabela `transacoes_cashback`
--
ALTER TABLE `transacoes_cashback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=264;

--
-- AUTO_INCREMENT de tabela `transacoes_comissao`
--
ALTER TABLE `transacoes_comissao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=244;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=170;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=337;

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
