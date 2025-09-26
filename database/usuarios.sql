-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 26/09/2025 às 20:30
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
(9, 'Kaua Matheus da Silva Lope', 'kauamatheus920@gmail.com', '38991045205', '15692134616', '$2y$10$ZBHPPEjv69ihoxjJatuJZefND4d0UNGpzK.UG1fji3BeETLymm7eu', '2025-05-05 19:45:04', '2025-09-26 20:24:51', 'ativo', 'cliente', 'Sim', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(10, 'Frederico', 'repertoriofredericofagundes@gmail.com', NULL, NULL, '$2y$10$yGjHS8rJq49AuLeuVrZHkOUPSkzNLs79A6H52HwwY8DpzLA2A95Ay', '2025-05-05 21:45:46', '2025-09-15 18:30:09', 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(11, 'Kaua Lopés', 'kaua@klubecash.com', NULL, NULL, '$2y$10$3cp74UJto1IK9R4f8wx.su3HR.SdXKPLotS4OLck7BxMLOhuJMtHq', '2025-05-07 12:19:05', '2025-09-26 14:42:27', 'ativo', 'admin', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(55, 'Matheus', 'kauamathes123487654@gmail.com', '34991191534', NULL, '$2y$10$VwSfpE6zvr72HI19RLFLF.Dw4VKMjbGajc5l6mN3jQiaoHK1GUR0u', '2025-05-25 19:17:34', '2025-09-26 10:14:16', 'ativo', 'loja', 'Sim', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'sim'),
(61, 'Frederico Fagundes', 'fredericofagundes0@gmail.com', NULL, NULL, '$2y$10$Lcszebxu3vPCg4dNkDhP7eAvk07mvjEvFLNz4pFYdMveo0skeNFWi', '2025-06-05 17:48:45', '2025-09-25 19:38:01', 'ativo', 'admin', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(63, 'KLUBE DIGITAL', 'acessoriafredericofagundes@gmail.com', '(34) 99335-7697', NULL, '$2y$10$VuDfT8bieSTLToSbmd3EzOVkmwNLYeC9itIfm2kxl3f54OpnZpd5O', '2025-06-07 16:11:42', '2025-09-25 19:36:19', 'ativo', 'loja', 'Sim', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'sim'),
(71, 'Roberto Magalhães Corrêa ', 'ropatosmg@gmail.com', '5534993171602', NULL, '$2y$10$77e0qthXH0AJkZFGJR0APu9fifxY/M8BvkNOGrHMBMBmAv7W3SohO', '2025-06-10 00:08:12', '2025-06-10 00:08:51', 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(72, 'Sabrina', 'sabrina290623@gmail.com', '(34) 99842-3591', NULL, '$2y$10$1FNgzRYI0AbiCYymdAgBlOWe2uIJn.PwU24.AUe3UP7pf5bA1ImJO', '2025-06-10 00:11:51', '2025-06-10 00:12:00', 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(73, 'Frederico Fagundes', 'klubecash@gmail.com', '(34) 99335-7697', NULL, '$2y$10$cM0f9co4abNHzxiOD0ZgjuZchVNk9o3v6mOadv2aByV.s339xdTPu', '2025-06-10 00:14:24', '2025-09-25 19:36:08', 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(74, 'Amanda rosa ', 'aricken31@gmail.com', '(34) 99975-8423', NULL, '$2y$10$aV.0Wj3E2dMRHSX7KqHa9u0.LsHiHDdBEpD/yOzCB.QC4uFcu72/K', '2025-06-10 00:15:41', NULL, 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(75, 'Felipe Vieira ribeiro', 'ribeirofilepe34@gmail.com', '(34) 99712-8998', NULL, '$2y$10$MpCAnHh7GN8ToE7b3FGzcurkrl8TA4Ffm69NECs0ePdMJcuvW0iNC', '2025-06-10 00:40:43', '2025-08-30 20:41:38', 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(76, 'Gabriela Steffany da Silva ', 'gabisteffany@icloud.com', '(34) 98700-3621', NULL, '$2y$10$eFewesljEaKuqWpeFRnuy.Xh/FJ4sXLz8thior8hzQUytyrDisYay', '2025-06-10 00:41:33', '2025-06-10 00:45:29', 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(77, 'Bruna Leal Ribeiro', 'brunna.leal00@gmail.com', '(34) 99982-8286', NULL, '$2y$10$Og4FZ3ealFiMAvj2gAIR0etd35frBRFNz/0CoefkAOqXkjOK/0ZLy', '2025-06-10 00:41:56', '2025-06-10 00:42:07', 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(78, 'Gabriele Soares Souza ', 'soaresgabriele25@gmail.com', '(34) 99960-8386', NULL, '$2y$10$BgfPzZTWZ4Qa412NtFZQQ.QAoO9k8Y5G.GFiaLvBIqX5rbUt99sfG', '2025-06-10 02:24:49', '2025-06-10 02:25:03', 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(79, 'Pedro Henrique Duarte ', 'pedrohduarte98@gmail.com', '5534998437197', NULL, '$2y$10$CSUkXDPCL6rdd2cMhEhPKO0dq.D7ioZ9ywNef8wf0CFcBDufwgBeu', '2025-06-10 05:22:24', '2025-06-10 05:22:59', 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(80, 'Pirapora', 'kaualopes@unipam.edu.br', NULL, NULL, '$2y$10$VOJ.OE4rGXEWrq55slY41uz0POqQ2ZCph71mpaW9C3gIdoF38TXcm', '2025-06-10 18:44:22', NULL, 'ativo', 'loja', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(81, 'Lucas Fagundes da Silva ', 'lucasfagundes934@gmail.com', '(34) 99218-9099', NULL, '$2y$10$obpHzgu/lTbA9BLIWsz8yebeD3rroMp9cW.Xy/MxbW8A7mOom9ox2', '2025-06-11 19:29:20', '2025-06-13 00:57:44', 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(86, 'Jennifer aryane ', 'jenniferlimaxz@gmail.com', '(55) 98497-1703', NULL, '$2y$10$Qeai.iOuOCYSrTMmFm7b1OE4WeHvgzmem4SLeJGa20bvjJJGzhZYG', '2025-07-07 17:05:39', NULL, 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(87, 'Jennifer aryane ', 'jenniferlopesxz@gmail.com', '(55) 98497-1703', NULL, '$2y$10$FxTmg8XDk50WOKlUAZzaeOAF.sPVIgcZHyryCUlZMern1Hy363CFO', '2025-07-07 17:06:49', NULL, 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(88, 'Rafael Augusto Alves Silva ', 'rafaelaugustoalvessilva5@gmail.com', '(34) 99665-7725', NULL, '$2y$10$B8CcTlZLjn2swhyPXdjnQeq3sl5.j6nnyVbqkL9wwzkcM.ulaFBwW', '2025-07-07 18:28:49', '2025-07-07 22:36:48', 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(111, 'Ana Caroliny Ferreira De Almeida ', 'anacarolinyferreiradealmeida5@gmail.com', '(11) 97880-6283', NULL, '$2y$10$di3MoK7n.I9v3S3UN.xF6.qQX4w.BlqxfDl7cEGjCJElaAyNEYFM6', '2025-07-16 03:14:07', NULL, 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
(118, 'Clarissa', 'clarissalopes296@gmail.com', NULL, NULL, '$2y$10$g/2OVjHI54UuC4zbBiiNSuFk.3UIJtQbSG1hoEb/pxnIlNQwQk6UO', '2025-07-22 21:39:03', '2025-07-26 19:28:54', 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 'nao'),
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
(145, 'Frederico Fagundes', 'visitante_3497635735_loja_38@klubecash.local', '34997635735', NULL, NULL, '2025-08-14 13:54:43', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
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
(159, 'Sync Holding', 'kaua@syncholding.com.br', '(34) 99800-2600', '04355521630', '$2y$10$W4Mw0j5/DhS.p0/I.D0he.aekBeq.O9.5xVoS8wntjF4L3U3P6OPW', '2025-08-15 13:52:55', '2025-09-26 18:58:10', 'ativo', 'loja', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'sim'),
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
(177, 'RSF SONHO DE NOIVA EVENTOS E NEGOCIOS LTDA', 'cleacasamentos@gmail.com', '(85) 99632-4231', NULL, '$2y$10$cTaW4e9BBcO8OKGdOJ.WpeJN/g194QfJ259i3KuBP7i3.yxABtyia', '2025-09-14 19:47:52', '2025-09-24 23:35:31', 'ativo', 'loja', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(180, 'Ricardo da Silva Facundo', 'visitante_85982334146_loja_59@klubecash.local', '85982334146', NULL, NULL, '2025-09-15 01:37:26', NULL, 'ativo', 'cliente', 'Não', 'visitante', 59, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(181, 'maria joaquina', 'visitante_3499654789_loja_38@klubecash.local', '34999654789', NULL, NULL, '2025-09-15 18:32:26', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(184, 'Emanuel Caetano', 'visitante_33987063966_loja_38@klubecash.local', '33987063966', NULL, NULL, '2025-09-19 18:35:46', NULL, 'ativo', 'cliente', 'Não', 'visitante', 38, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(185, 'Teste WhatsApp', 'teste@klubecash.com', NULL, NULL, '$2y$10$CrbhTxuc9U.fwdTH2F0el.Tr8i6gzKE2Fg.q58tZAWX/gZe0h/ygG', '2025-09-20 20:10:30', NULL, 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(186, 'João Primeiro', 'joão.primeiro@teste.com', '5538991045201', NULL, '$2y$10$dmWTnNzPfPAwoZmHIc9eLulthgcNJ4PRs8e5/yDBqQ/FljbgoX4km', '2025-09-20 20:30:15', NULL, 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(187, 'Maria Regular', 'maria.regular@teste.com', '5538991045202', NULL, '$2y$10$ozcgNJjVPZGxCTDnUjto6..4A90UXc7P84zQ.4g/MA6ii1GJtRGJC', '2025-09-20 20:30:17', NULL, 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(188, 'Carlos VIP Silva', 'carlos.vip.silva@teste.com', '5538991045203', NULL, '$2y$10$b.Yk5L3aBI2aGGxXluJG4OOsrpYAh8Gk34oOddlss4Q52Yw/4RCau', '2025-09-20 20:30:20', NULL, 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(189, 'Ana Compradora', 'ana.compradora@teste.com', '5538991045204', NULL, '$2y$10$ZGiDUKvOoNGItJeT59V52Oj4FnAMWXMLP6ha6/QcIQ/MPpzm3lq8e', '2025-09-20 20:30:29', NULL, 'ativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(190, 'Желаете найти новый доход? Тест Т-Банка — ваш возможность. Вступайте https://tinyurl.com/pXi6DHBS TH', 'cthrinereynoldqoq29677y@acolm.org', '87278384256', NULL, '$2y$10$KQ0IH.nZE.HRqHi0prJrCuoQLLUzjnKV8yWJeFdvN96G1/2Q0pmc2', '2025-09-21 19:29:19', '2025-09-21 19:29:20', 'inativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(191, 'Ищете дополнительный заработок? Тест Т-Банка — ваш возможность. Присоединяйтесь https://tinyurl.com/', 'grafalovskiy00@bk.ru', '88842835549', NULL, '$2y$10$F4iUCTPEa/lkH6pnfN1RCej3V6BdGwE9V/gPymFYW/eTaP0qWtxKe', '2025-09-21 19:49:01', '2025-09-21 19:49:03', 'inativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(192, 'er', 'visitante_5534998002600_loja_59@klubecash.local', '5534998002600', NULL, NULL, '2025-09-22 01:24:50', NULL, 'ativo', 'cliente', 'Não', 'visitante', 59, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(193, 'Класс! Вам достался потрясающий приз ждёт вас! Изучите подробности по ссылке https://tinyurl.com/phA', 'veldgrube.00@mail.ru', '86756898182', NULL, '$2y$10$FAFdUdSMlo.Buv5rUx4JU.go17HPyYkNHHWLTibaHgPS8AiNlzoIG', '2025-09-23 11:24:18', '2025-09-23 11:24:19', 'inativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(194, 'Супер! Вы получили эксклюзивный сюрприз готов для вас! Изучите все подробности по ссылке https://tin', 'kateebartonmmp26936t@52sk2.org', '89714695939', NULL, '$2y$10$CVF8v3ondOv9BY0nWYQryeJdVUnUHLeUlfFcT9fg1gevw7u4on8sG', '2025-09-23 11:24:18', '2025-09-23 11:24:20', 'inativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(195, 'Kaua teste', 'visitante_3891045205_loja_59@klubecash.local', NULL, NULL, NULL, '2025-09-24 06:52:01', NULL, 'ativo', 'cliente', 'Não', 'visitante', 59, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao'),
(196, 'Вам перевод 170998 руб. забрать тут  https://tinyurl.com/bvDqJbKs NFDAW47442NFDAW', '6c2ini1uwox@lchaoge.com', '86315518115', NULL, '$2y$10$h3k/dd7XuwhV6MXl5o7KMOlDZepHMqL45w3THS7T1OGMZhNwiWT4S', '2025-09-25 22:01:38', '2025-09-25 22:01:40', 'inativo', 'cliente', 'Não', 'completo', NULL, NULL, NULL, 'local', 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, 'funcionario', 'nao');

--
-- Índices para tabelas despejadas
--

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
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=197;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_loja_criadora` FOREIGN KEY (`loja_criadora_id`) REFERENCES `lojas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`loja_vinculada_id`) REFERENCES `lojas` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
