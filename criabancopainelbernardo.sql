-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 18/09/2026 às 20:14
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `bancodefdef`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `combinabernardo`
--

CREATE TABLE `combinabernardo` (
  `idcombinabernardo` int(10) UNSIGNED NOT NULL,
  `painelbernardo_idpainelbernardo` int(10) UNSIGNED NOT NULL,
  `ledbernardo_idledbernardo` int(10) UNSIGNED NOT NULL,
  `macbernardo_idmacbernardo` varchar(17) NOT NULL,
  `ledr` int(10) UNSIGNED DEFAULT NULL,
  `ledb` int(10) UNSIGNED DEFAULT NULL,
  `ledg` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `combinabernardo`
--

INSERT INTO `combinabernardo` (`idcombinabernardo`, `painelbernardo_idpainelbernardo`, `ledbernardo_idledbernardo`, `macbernardo_idmacbernardo`, `ledr`, `ledb`, `ledg`) VALUES
(1, 2, 2, '5C:CF:7F:67:A2:49', 255, 0, 0),
(2, 1, 1, '5C:CF:7F:67:A2:49', 255, 0, 255);

-- --------------------------------------------------------

--
-- Estrutura para tabela `ledbernardo`
--

CREATE TABLE `ledbernardo` (
  `idledbernardo` int(10) UNSIGNED NOT NULL,
  `nome` varchar(35) DEFAULT NULL,
  `pinor` int(10) UNSIGNED DEFAULT NULL,
  `pinog` int(10) UNSIGNED DEFAULT NULL,
  `pinob` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `ledbernardo`
--

INSERT INTO `ledbernardo` (`idledbernardo`, `nome`, `pinor`, `pinog`, `pinob`) VALUES
(1, 'bernardo e amandha', 0, 0, 255),
(2, 'bernardo e amandha dia 24/09', 0, 0, 255);

-- --------------------------------------------------------

--
-- Estrutura para tabela `macbernardo`
--

CREATE TABLE `macbernardo` (
  `idmacbernardo` varchar(17) NOT NULL,
  `nome` varchar(35) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `macbernardo`
--

INSERT INTO `macbernardo` (`idmacbernardo`, `nome`, `ativo`) VALUES
('5C:CF:7F:67:A2:49', 'bernardo e amandha', 1),
('E8:DB:84:98:B5:DA', 'bernardo e amandha dia 24', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `painelbernardo`
--

CREATE TABLE `painelbernardo` (
  `idpainelbernardo` int(10) UNSIGNED NOT NULL,
  `nome` varchar(50) DEFAULT NULL,
  `datainicio` datetime DEFAULT NULL,
  `datafim` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `painelbernardo`
--

INSERT INTO `painelbernardo` (`idpainelbernardo`, `nome`, `datainicio`, `datafim`) VALUES
(1, 'bernardo e amandha', '2026-09-18 14:18:36', '2026-09-23 23:59:59'),
(2, 'bernardo e amandha dia 24/09', '2026-09-24 00:00:00', '2026-09-24 23:59:59'),
(3, 'bernardo e amandha', '2026-09-18 14:18:36', '2026-09-23 23:59:59');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `combinabernardo`
--
ALTER TABLE `combinabernardo`
  ADD PRIMARY KEY (`idcombinabernardo`),
  ADD KEY `combinbernardo_FKIndex1` (`macbernardo_idmacbernardo`),
  ADD KEY `combinbernardo_FKIndex2` (`ledbernardo_idledbernardo`),
  ADD KEY `combinbernardo_FKIndex3` (`painelbernardo_idpainelbernardo`);

--
-- Índices de tabela `ledbernardo`
--
ALTER TABLE `ledbernardo`
  ADD PRIMARY KEY (`idledbernardo`);

--
-- Índices de tabela `macbernardo`
--
ALTER TABLE `macbernardo`
  ADD PRIMARY KEY (`idmacbernardo`);

--
-- Índices de tabela `painelbernardo`
--
ALTER TABLE `painelbernardo`
  ADD PRIMARY KEY (`idpainelbernardo`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `combinabernardo`
--
ALTER TABLE `combinabernardo`
  MODIFY `idcombinabernardo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `ledbernardo`
--
ALTER TABLE `ledbernardo`
  MODIFY `idledbernardo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `painelbernardo`
--
ALTER TABLE `painelbernardo`
  MODIFY `idpainelbernardo` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `combinabernardo`
--
ALTER TABLE `combinabernardo`
  ADD CONSTRAINT `combinabernardo_ibfk_1` FOREIGN KEY (`macbernardo_idmacbernardo`) REFERENCES `macbernardo` (`idmacbernardo`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `combinabernardo_ibfk_2` FOREIGN KEY (`ledbernardo_idledbernardo`) REFERENCES `ledbernardo` (`idledbernardo`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `combinabernardo_ibfk_3` FOREIGN KEY (`painelbernardo_idpainelbernardo`) REFERENCES `painelbernardo` (`idpainelbernardo`) ON DELETE NO ACTION ON UPDATE NO ACTION;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
