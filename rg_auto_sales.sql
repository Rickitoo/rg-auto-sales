-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 27-Mar-2026 às 01:01
-- Versão do servidor: 10.4.32-MariaDB
-- versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `rg_auto_sales`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `caminho`
--

DROP TABLE IF EXISTS `caminho`;
CREATE TABLE IF NOT EXISTS `caminho` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `carro_id` int(11) NOT NULL,
  `foto` varchar(255) NOT NULL,
  `ordem` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `carros`
--

DROP TABLE IF EXISTS `carros`;
CREATE TABLE IF NOT EXISTS `carros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `marca` varchar(50) NOT NULL,
  `modelo` varchar(50) NOT NULL,
  `ano` int(11) NOT NULL,
  `preco` decimal(10,2) NOT NULL,
  `descricao` text DEFAULT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `status` enum('disponivel','vendido') DEFAULT 'disponivel',
  `data_registo` timestamp NOT NULL DEFAULT current_timestamp(),
  `preco_venda` decimal(10,2) DEFAULT NULL,
  `comissao` decimal(10,2) DEFAULT NULL,
  `data_venda` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `carros`
--

INSERT INTO `carros` (`id`, `marca`, `modelo`, `ano`, `preco`, `descricao`, `imagem`, `status`, `data_registo`, `preco_venda`, `comissao`, `data_venda`) VALUES
(1, 'Toyota', 'Hilux 3.0D', 2002, 6500000.00, 'Carro aprovado via vendedor RG Auto Sales', NULL, 'disponivel', '2026-02-04 22:14:10', NULL, NULL, NULL),
(2, 'BMW', 'M4', 2022, 65444418.00, 'Carro aprovado via vendedor RG Auto Sales', NULL, 'disponivel', '2026-02-05 23:10:04', NULL, NULL, NULL),
(3, 'Mercedes-Benz', 'GLE', 2021, 3.00, '', 'uploads/carros/mercedes-benz-gle-2021-capa-20260318-014539-c99d87.jpg', 'disponivel', '2026-03-18 00:45:39', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `carros_fotos`
--

DROP TABLE IF EXISTS `carros_fotos`;
CREATE TABLE IF NOT EXISTS `carros_fotos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `carro_id` int(11) NOT NULL,
  `caminho` varchar(255) NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `ordem` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `carro_id` (`carro_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `carros_fotos`
--

INSERT INTO `carros_fotos` (`id`, `carro_id`, `caminho`, `criado_em`, `ordem`) VALUES
(1, 3, 'uploads/carros/mercedes-benz-gle-2021-g1-20260318-014539-3dda53.jpg', '2026-03-18 02:45:39', 0);

-- --------------------------------------------------------

--
-- Estrutura da tabela `clientes`
--

DROP TABLE IF EXISTS `clientes`;
CREATE TABLE IF NOT EXISTS `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `telefone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `sexo` varchar(10) NOT NULL,
  `data` date NOT NULL,
  `hora` time NOT NULL,
  `marca` varchar(50) NOT NULL,
  `modelo` varchar(50) NOT NULL,
  `ano` int(11) NOT NULL,
  `mensagem` text DEFAULT NULL,
  `data_registo` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('NOVO','CONTACTADO','AGENDADO','CONCLUIDO','CANCELADO') DEFAULT 'NOVO',
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `clientes`
--

INSERT INTO `clientes` (`id`, `nome`, `telefone`, `email`, `sexo`, `data`, `hora`, `marca`, `modelo`, `ano`, `mensagem`, `data_registo`, `status`) VALUES
(1, 'Arsenio', '841234445', 'Arsenio2@gmail.com', 'masculino', '2026-02-27', '23:43:00', 'Mercedes-Benz', 'GLE', 2024, '', '2026-02-03 19:42:20', 'CONCLUIDO'),
(2, 'Lara Gani', '+258850218251', 'Laragani1@gmail.com', 'femenino', '2026-02-11', '03:53:00', 'Ford', 'Raptor', 2021, '', '2026-02-03 21:53:46', 'CONCLUIDO'),
(3, 'Mamad', '845990030', 'Macgani2002@gmailcom', 'masculino', '2026-02-26', '05:21:00', 'Lamborghini', 'Urus', 2025, 'Tarde', '2026-02-09 22:16:15', 'CONCLUIDO'),
(4, 'orbita', '875990234', 'orbita55@gmail.com', 'femenino', '2026-02-19', '17:57:00', 'Porsche', 'Cayenne', 2025, '', '2026-02-15 21:57:33', 'NOVO'),
(5, 'Andre', '840075517', 'AndreB123@gmail.com', 'masculino', '2026-02-26', '03:19:00', 'Toyota', 'Hilux', 2018, '', '2026-02-15 22:16:23', 'CONCLUIDO'),
(6, 'Gabriel', '87444562', 'Gabi24@gmail.com', 'masculino', '2026-02-26', '04:51:00', 'Nissan', 'Patrol', 2024, '', '2026-02-21 21:52:14', 'CONCLUIDO'),
(7, 'Mica', '821123456', 'Micassl@gmail.com', 'masculino', '2026-02-24', '08:55:00', 'Lamborghini', 'Huracan', 2025, '', '2026-02-21 21:54:27', 'CONCLUIDO'),
(8, 'Brunex', '858688989', 'Brunex0024@gmail.com', 'masculino', '2026-03-11', '16:20:00', 'Mercedes-Benz', 'C-Class', 2021, '', '2026-02-28 00:21:25', 'NOVO'),
(9, 'Rayhan', '866593080', 'Rayhan123@gmail.com', 'masculino', '2026-03-14', '16:32:00', 'Volkswagen', 'Golf R', 2025, '', '2026-02-28 14:33:42', 'NOVO');

-- --------------------------------------------------------

--
-- Estrutura da tabela `config`
--

DROP TABLE IF EXISTS `config`;
CREATE TABLE IF NOT EXISTS `config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `percent_comissao` decimal(5,4) NOT NULL DEFAULT 0.0700,
  `minimo_comissao` decimal(12,2) NOT NULL DEFAULT 20000.00,
  `rg_share` decimal(5,4) NOT NULL DEFAULT 0.4000,
  `vendedor_share` decimal(5,4) NOT NULL DEFAULT 0.3000,
  `captador_share` decimal(5,4) NOT NULL DEFAULT 0.3000,
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `config`
--

INSERT INTO `config` (`id`, `percent_comissao`, `minimo_comissao`, `rg_share`, `vendedor_share`, `captador_share`, `atualizado_em`) VALUES
(1, 0.0700, 20000.00, 0.4000, 0.3000, 0.3000, '2026-02-06 23:26:49'),
(2, 0.0700, 50000.00, 0.4000, 0.3000, 0.3000, '2026-03-12 00:03:50'),
(3, 0.0700, 50000.00, 0.4000, 0.3000, 0.3000, '2026-03-12 00:04:18');

-- --------------------------------------------------------

--
-- Estrutura da tabela `custos`
--

DROP TABLE IF EXISTS `custos`;
CREATE TABLE IF NOT EXISTS `custos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data` date NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `valor` decimal(12,2) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `venda_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_data` (`data`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_custos_venda_id` (`venda_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `custos`
--

INSERT INTO `custos` (`id`, `data`, `categoria`, `descricao`, `valor`, `criado_em`, `venda_id`) VALUES
(1, '2026-02-21', 'transporte', 'TOW E GO', 40000.00, '2026-02-21 22:01:30', 13);

-- --------------------------------------------------------

--
-- Estrutura da tabela `leads`
--

DROP TABLE IF EXISTS `leads`;
CREATE TABLE IF NOT EXISTS `leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` enum('testdrive','venda') NOT NULL,
  `nome` varchar(120) NOT NULL,
  `telefone` varchar(30) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `mensagem` text DEFAULT NULL,
  `marca` varchar(80) DEFAULT NULL,
  `modelo` varchar(80) DEFAULT NULL,
  `ano` int(11) DEFAULT NULL,
  `carro_id` int(11) DEFAULT NULL,
  `origem` enum('site','ig','fb','wa','outro') NOT NULL DEFAULT 'site',
  `status` enum('novo','contactado','qualificado','agendado','negociacao','fechado','perdido') NOT NULL DEFAULT 'novo',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `notas` text DEFAULT NULL,
  `proximo_contacto` datetime DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `leads`
--

INSERT INTO `leads` (`id`, `tipo`, `nome`, `telefone`, `email`, `mensagem`, `marca`, `modelo`, `ano`, `carro_id`, `origem`, `status`, `criado_em`, `notas`, `proximo_contacto`, `atualizado_em`) VALUES
(1, 'testdrive', 'Beto', '824556784', 'betopiouuu7785@gmail.com', '', 'Ford', 'Ranger', 2020, NULL, 'site', 'fechado', '2026-02-28 14:48:16', NULL, NULL, '2026-03-01 01:40:57'),
(2, 'testdrive', 'Beto', '824556784', 'betopiouuu7785@gmail.com', '', 'Ford', 'Ranger', 2020, NULL, 'site', 'novo', '2026-02-28 14:48:49', NULL, NULL, '2026-02-28 23:53:59'),
(3, 'testdrive', 'Beto', '824556784', 'betopiouuu7785@gmail.com', '', 'Ford', 'Ranger', 2020, NULL, 'site', 'contactado', '2026-02-28 14:52:27', NULL, NULL, '2026-03-01 01:56:54'),
(4, 'testdrive', 'Destino', '852034721', 'Destino@gmail.com', '', 'Ferrari', 'F8 Tributo', 2022, NULL, 'site', 'negociacao', '2026-02-28 15:08:18', NULL, NULL, '2026-03-01 01:57:01'),
(5, 'testdrive', 'joao', '875663304', 'joao@gmail.com', '', 'Audi', 'A6', 2021, NULL, 'site', 'perdido', '2026-02-28 15:15:48', NULL, NULL, '2026-03-16 01:29:25'),
(6, 'testdrive', 'Mohammad', '845669877', 'MOH244@gmail.com', '', 'Ferrari', 'SF90', 2024, NULL, 'site', 'negociacao', '2026-02-28 23:01:53', NULL, NULL, '2026-03-01 01:56:49'),
(7, 'testdrive', 'Jaime', '8466338556', 'JaimeGuambe@gmail.com', '', 'Volkswagen', 'Tiguan', 2019, NULL, 'site', 'contactado', '2026-03-08 00:30:55', NULL, NULL, '2026-03-12 00:02:59'),
(8, 'testdrive', 'Muhammad', '8796635412', 'Muhammad123@gmail.com', '', 'Mercedes-Benz', 'GLE', 2025, NULL, 'site', 'novo', '2026-03-19 22:36:50', NULL, NULL, '2026-03-19 22:36:50');

-- --------------------------------------------------------

--
-- Estrutura da tabela `pessoas`
--

DROP TABLE IF EXISTS `pessoas`;
CREATE TABLE IF NOT EXISTS `pessoas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` enum('RG','VENDEDOR','CAPTADOR') NOT NULL,
  `nome` varchar(120) NOT NULL,
  `telefone` varchar(30) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_ativo` (`ativo`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `pessoas`
--

INSERT INTO `pessoas` (`id`, `tipo`, `nome`, `telefone`, `email`, `ativo`, `criado_em`) VALUES
(1, 'RG', 'RG Auto Sales', NULL, NULL, 1, '2026-02-06 23:26:49');

-- --------------------------------------------------------

--
-- Estrutura da tabela `vendas`
--

DROP TABLE IF EXISTS `vendas`;
CREATE TABLE IF NOT EXISTS `vendas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `marca` varchar(60) NOT NULL,
  `modelo` varchar(80) NOT NULL,
  `ano` varchar(10) NOT NULL,
  `valor_carro` decimal(12,2) NOT NULL,
  `comissao` decimal(12,2) NOT NULL,
  `status` enum('PENDENTE','PAGO','CANCELADO') NOT NULL DEFAULT 'PENDENTE',
  `forma_pagamento` varchar(30) NOT NULL,
  `data_venda` date NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `vendedor_id` int(11) DEFAULT NULL,
  `captador_id` int(11) DEFAULT NULL,
  `rg_valor` decimal(12,2) DEFAULT NULL,
  `vendedor_valor` decimal(12,2) DEFAULT NULL,
  `captador_valor` decimal(12,2) DEFAULT NULL,
  `valor_venda` decimal(12,2) NOT NULL DEFAULT 0.00,
  `valor_proprietario` decimal(12,2) NOT NULL DEFAULT 0.00,
  `perc_vendedor` decimal(5,2) NOT NULL DEFAULT 20.00,
  `perc_parceiro` decimal(5,2) NOT NULL DEFAULT 0.00,
  `perc_rg` decimal(5,2) NOT NULL DEFAULT 80.00,
  `total_custos` decimal(12,2) NOT NULL DEFAULT 0.00,
  `lucro` decimal(12,2) NOT NULL DEFAULT 0.00,
  `comissao_vendedor` decimal(12,2) NOT NULL DEFAULT 0.00,
  `comissao_parceiro` decimal(12,2) NOT NULL DEFAULT 0.00,
  `comissao_rg` decimal(12,2) NOT NULL DEFAULT 0.00,
  `lucro_minimo` decimal(12,2) NOT NULL DEFAULT 30000.00,
  `precisa_aprovacao` tinyint(1) NOT NULL DEFAULT 0,
  `aprovado_por` int(11) DEFAULT NULL,
  `aprovado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_data` (`data_venda`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `fk_vendas_vendedor` (`vendedor_id`),
  KEY `fk_vendas_captador` (`captador_id`),
  KEY `fk_vendas_aprovador` (`aprovado_por`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `vendas`
--

INSERT INTO `vendas` (`id`, `cliente_id`, `marca`, `modelo`, `ano`, `valor_carro`, `comissao`, `status`, `forma_pagamento`, `data_venda`, `criado_em`, `atualizado_em`, `vendedor_id`, `captador_id`, `rg_valor`, `vendedor_valor`, `captador_valor`, `valor_venda`, `valor_proprietario`, `perc_vendedor`, `perc_parceiro`, `perc_rg`, `total_custos`, `lucro`, `comissao_vendedor`, `comissao_parceiro`, `comissao_rg`, `lucro_minimo`, `precisa_aprovacao`, `aprovado_por`, `aprovado_em`) VALUES
(1, 2, 'Ford', 'Raptor', '2021', 450.00, 20000.00, 'PAGO', '', '2026-02-06', '2026-02-06 21:30:45', '2026-02-06 21:35:40', NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 20.00, 0.00, 80.00, 0.00, 0.00, 0.00, 0.00, 0.00, 30000.00, 0, NULL, NULL),
(5, 1, 'Mercedes-Benz', 'GLE', '2024', 15000000.00, 1050000.00, 'PAGO', '', '2026-02-09', '2026-02-09 19:43:40', '2026-02-09 20:02:35', NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, 20.00, 0.00, 80.00, 0.00, 0.00, 0.00, 0.00, 0.00, 30000.00, 0, NULL, NULL),
(6, 1, 'Mercedes-Benz', 'GLE', '2024', 15000000.00, 1050000.00, 'PAGO', '', '2026-02-09', '2026-02-09 20:23:05', '2026-02-09 20:23:28', 1, 1, NULL, NULL, NULL, 0.00, 0.00, 20.00, 0.00, 80.00, 0.00, 0.00, 0.00, 0.00, 0.00, 30000.00, 0, NULL, NULL),
(7, 3, 'Lamborghini', 'Urus', '2025', 5000000.00, 350000.00, 'CANCELADO', 'CASH', '2026-02-15', '2026-02-15 22:00:03', '2026-02-15 22:01:15', 1, 1, NULL, NULL, NULL, 0.00, 0.00, 20.00, 0.00, 80.00, 0.00, 0.00, 0.00, 0.00, 0.00, 30000.00, 0, NULL, NULL),
(8, 3, 'Lamborghini', 'Urus', '2025', 500000.00, 35000.00, 'PAGO', 'CASH', '2026-02-15', '2026-02-15 22:02:42', '2026-02-15 22:03:27', 1, 1, NULL, NULL, NULL, 0.00, 0.00, 20.00, 0.00, 80.00, 0.00, 0.00, 0.00, 0.00, 0.00, 30000.00, 0, NULL, NULL),
(9, 5, 'Toyota', 'Hilux', '2018', 0.00, 0.00, 'CANCELADO', 'CASH', '2026-02-15', '2026-02-15 22:29:13', '2026-02-15 22:31:17', 1, 1, NULL, NULL, NULL, 500000.00, 0.00, 20.00, 0.00, 80.00, 0.00, 0.00, 0.00, 0.00, 0.00, 30000.00, 0, NULL, NULL),
(10, 5, 'Toyota', 'Hilux', '2018', 0.00, 0.00, 'PAGO', 'CASH', '2026-02-15', '2026-02-15 22:31:45', '2026-02-15 22:33:17', 1, 1, NULL, NULL, NULL, 500000.00, 450000.00, 20.00, 0.00, 80.00, 0.00, 0.00, 0.00, 0.00, 0.00, 30000.00, 0, NULL, NULL),
(11, 5, 'Toyota', 'Hilux', '2018', 0.00, 0.00, 'PAGO', 'CASH', '2026-02-16', '2026-02-15 23:01:56', '2026-02-15 23:32:49', 1, 1, NULL, NULL, NULL, 500000.00, 450000.00, 15.00, 10.00, 75.00, 0.00, 50000.00, 7500.00, 5000.00, 37500.00, 30000.00, 0, NULL, NULL),
(12, 2, 'Ford', 'Raptor', '2023', 0.00, 0.00, 'PAGO', 'TRANSFERENCIA', '2026-02-16', '2026-02-15 23:59:36', '2026-02-16 00:00:06', NULL, NULL, NULL, NULL, NULL, 450000.00, 380000.00, 0.00, 0.00, 100.00, 0.00, 70000.00, 0.00, 0.00, 70000.00, 30000.00, 0, NULL, NULL),
(13, 6, 'Nissan', 'Patrol', '2024', 0.00, 0.00, 'PAGO', 'TRANSFERENCIA', '2026-02-21', '2026-02-21 21:58:41', '2026-02-21 22:01:55', 1, 1, NULL, NULL, NULL, 5000000.00, 4700000.00, 15.00, 10.00, 75.00, 40000.00, 260000.00, 39000.00, 26000.00, 195000.00, 30000.00, 0, NULL, NULL),
(14, 7, 'Lamborghini', 'Huracan', '2025', 0.00, 0.00, 'PAGO', 'CASH', '2026-02-21', '2026-02-21 22:08:31', '2026-02-21 22:09:38', NULL, NULL, NULL, NULL, NULL, 1200000.00, 0.00, 0.00, 0.00, 100.00, 0.00, 1200000.00, 0.00, 0.00, 1200000.00, 30000.00, 0, NULL, NULL),
(15, 8, 'Mercedes-Benz', 'C-Class', '2021', 0.00, 0.00, 'PENDENTE', 'TRANSFERENCIA', '2026-02-28', '2026-02-28 00:26:00', '2026-02-28 00:27:13', 1, NULL, NULL, NULL, NULL, 1800000.00, 1775000.00, 15.00, 0.00, 85.00, 0.00, 25000.00, 0.00, 0.00, 0.00, 30000.00, 1, NULL, NULL),
(16, 9, 'Volkswagen', 'Golf R', '2025', 0.00, 0.00, 'PAGO', 'CASH', '2026-02-28', '2026-02-28 15:18:17', '2026-02-28 15:19:24', 1, 1, NULL, NULL, NULL, 700000.00, 650000.00, 15.00, 10.00, 75.00, 0.00, 50000.00, 7500.00, 5000.00, 37500.00, 30000.00, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `vendas_fotos`
--

DROP TABLE IF EXISTS `vendas_fotos`;
CREATE TABLE IF NOT EXISTS `vendas_fotos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venda_id` int(11) NOT NULL,
  `arquivo` varchar(255) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `venda_id` (`venda_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `vendas_pedidos`
--

DROP TABLE IF EXISTS `vendas_pedidos`;
CREATE TABLE IF NOT EXISTS `vendas_pedidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `telefone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `cidade` varchar(60) DEFAULT NULL,
  `marca` varchar(50) NOT NULL,
  `modelo` varchar(50) NOT NULL,
  `ano` varchar(10) NOT NULL,
  `quilometragem` varchar(20) DEFAULT NULL,
  `cambio` varchar(20) DEFAULT NULL,
  `combustivel` varchar(20) DEFAULT NULL,
  `preco_pretendido` decimal(12,2) DEFAULT NULL,
  `estado` varchar(30) DEFAULT NULL,
  `mensagem` text DEFAULT NULL,
  `status` enum('Novo','Em análise','Aprovado','Recusado','Publicado') DEFAULT 'Novo',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `venda_custos`
--

DROP TABLE IF EXISTS `venda_custos`;
CREATE TABLE IF NOT EXISTS `venda_custos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venda_id` int(11) NOT NULL,
  `tipo` enum('anuncios','transporte','documentacao','outros') NOT NULL DEFAULT 'outros',
  `descricao` varchar(150) DEFAULT NULL,
  `valor` decimal(12,2) NOT NULL DEFAULT 0.00,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_custos_venda` (`venda_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `venda_parceiros`
--

DROP TABLE IF EXISTS `venda_parceiros`;
CREATE TABLE IF NOT EXISTS `venda_parceiros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venda_id` int(11) NOT NULL,
  `nome` varchar(120) NOT NULL,
  `telefone` varchar(30) DEFAULT NULL,
  `perc_parceiro` decimal(5,2) NOT NULL DEFAULT 0.00,
  `comissao_parceiro` decimal(12,2) NOT NULL DEFAULT 0.00,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_parceiro_venda` (`venda_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `vendedores`
--

DROP TABLE IF EXISTS `vendedores`;
CREATE TABLE IF NOT EXISTS `vendedores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `telefone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `marca` varchar(50) NOT NULL,
  `modelo` varchar(50) NOT NULL,
  `ano` int(11) NOT NULL,
  `preco` decimal(10,2) NOT NULL,
  `mensagem` text DEFAULT NULL,
  `status` enum('pendente','aprovado','rejeitado') DEFAULT 'pendente',
  `data_registo` timestamp NOT NULL DEFAULT current_timestamp(),
  `carro_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `vendedores`
--

INSERT INTO `vendedores` (`id`, `nome`, `telefone`, `email`, `marca`, `modelo`, `ano`, `preco`, `mensagem`, `status`, `data_registo`, `carro_id`) VALUES
(1, 'Mamad', '845990030', 'Macgani2002@gmailcom', 'Toyota', 'Hilux 3.0D', 2002, 6500000.00, 'Em bom estado', 'aprovado', '2026-02-03 20:06:42', NULL),
(2, 'Arda', '8744563321', 'arda87859@gmail.com', 'BMW', 'M4', 2022, 65444418.00, '168.000 KM', 'aprovado', '2026-02-05 23:08:24', NULL),
(3, 'Marcos', '835566478', 'Marcos245678@gmail.com', 'Porsche', 'Cayenne', 2018, 6700000.00, '', 'pendente', '2026-03-01 21:04:42', NULL),
(4, 'Goonçalo', '874556698', 'Gongas@gmail.com', 'Ford', 'Bronco', 2025, 13000000.00, 'Nampula', 'pendente', '2026-03-01 23:42:09', NULL),
(5, 'Chazli', '875666321', 'Chazli2@gmail.com', 'Lexus', 'is250', 2020, 700000.00, '', 'pendente', '2026-03-03 13:41:13', NULL),
(6, 'Chazli', '842034721', 'Chazli2@gmail.com', 'Lexus', 'is250', 2020, 700000.00, '', 'pendente', '2026-03-09 20:50:51', NULL),
(7, 'Chazli', '843648791', 'Chazli2@gmail.com', 'Lexus', 'is300', 2020, 8740000.00, '', 'pendente', '2026-03-19 22:38:20', NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `vendedores_fotos`
--

DROP TABLE IF EXISTS `vendedores_fotos`;
CREATE TABLE IF NOT EXISTS `vendedores_fotos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendedor_id` int(11) NOT NULL,
  `arquivo` varchar(255) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `vendedor_id` (`vendedor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `vendedores_fotos`
--

INSERT INTO `vendedores_fotos` (`id`, `vendedor_id`, `arquivo`, `criado_em`) VALUES
(1, 3, 'uploads/vendas/venda_3_6e83b875b01500ae.jpg', '2026-03-01 21:04:42'),
(2, 4, 'uploads/vendas/venda_4_17bff97a22025fd2.jpg', '2026-03-01 23:42:09'),
(3, 5, 'uploads/vendas/venda_5_5742ba4ef4b0b79d.jpg', '2026-03-03 13:41:13'),
(4, 6, 'uploads/vendas/venda_6_ba8767c350d50097.jpg', '2026-03-09 20:50:51'),
(5, 7, 'uploads/vendas/venda_7_a630dbc120e04d68.jpg', '2026-03-19 22:38:20');

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `carros_fotos`
--
ALTER TABLE `carros_fotos`
  ADD CONSTRAINT `carros_fotos_ibfk_1` FOREIGN KEY (`carro_id`) REFERENCES `carros` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `vendas`
--
ALTER TABLE `vendas`
  ADD CONSTRAINT `fk_vendas_aprovador` FOREIGN KEY (`aprovado_por`) REFERENCES `pessoas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_vendas_captador` FOREIGN KEY (`captador_id`) REFERENCES `pessoas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vendas_clientes` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_vendas_vendedor` FOREIGN KEY (`vendedor_id`) REFERENCES `pessoas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Limitadores para a tabela `vendas_fotos`
--
ALTER TABLE `vendas_fotos`
  ADD CONSTRAINT `vendas_fotos_ibfk_1` FOREIGN KEY (`venda_id`) REFERENCES `vendas_pedidos` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `venda_custos`
--
ALTER TABLE `venda_custos`
  ADD CONSTRAINT `fk_custos_venda` FOREIGN KEY (`venda_id`) REFERENCES `vendas` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `venda_parceiros`
--
ALTER TABLE `venda_parceiros`
  ADD CONSTRAINT `fk_parceiro_venda` FOREIGN KEY (`venda_id`) REFERENCES `vendas` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `vendedores_fotos`
--
ALTER TABLE `vendedores_fotos`
  ADD CONSTRAINT `vendedores_fotos_ibfk_1` FOREIGN KEY (`vendedor_id`) REFERENCES `vendedores` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
