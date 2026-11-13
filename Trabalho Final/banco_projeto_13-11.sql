-- phpMyAdmin SQL Dump
-- Base: banco original do painel (banco `bancodefdef`), adaptada para o
-- Trabalho Final - Programacao IV: sensor de proximidade (ESP8266 + HC-SR04 +
-- LED RGB + LCD + buzzer + push button).
--
-- Mapa de como cada tabela da base atende o trabalho:
--   mac       -> placas (MAC como chave); ganhou `contador`
--   led       -> pinos do LED RGB (sem alteracao)
--   painel    -> faixa de distancia + mensagem do LCD, valida entre datainicio e datafim
--   combina   -> cor do LED (ledr/ledg/ledb) e buzzer de cada painel, por MAC
--   sensor    -> NOVA (tabela extra): sensor ultrassonico, `ativo` e `contador`
--   leitura   -> NOVA: uma linha por medicao (valor = distancia em cm)
--
-- ATENCAO: o bloco de DROP abaixo apaga as tabelas e os dados. Remova-o se
-- quiser importar sobre um banco que ja tem dados.
-- Os triggers (final do arquivo) usam DELIMITER: se o phpMyAdmin recusar,
-- troque o campo "Delimitador" para $$ ou rode o bloco separadamente.

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `leitura`;
DROP TABLE IF EXISTS `combina`;
DROP TABLE IF EXISTS `painel`;
DROP TABLE IF EXISTS `sensor`;
DROP TABLE IF EXISTS `led`;
DROP TABLE IF EXISTS `mac`;
SET FOREIGN_KEY_CHECKS = 1;

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
-- Estrutura para tabela `combina`
-- (novas colunas: buzzertom em Hz, 0 = sem som; buzzertempo em ms)
--

CREATE TABLE `combina` (
  `idcombina` int(10) UNSIGNED NOT NULL,
  `painel_idpainel` int(10) UNSIGNED NOT NULL,
  `led_idled` int(10) UNSIGNED NOT NULL,
  `mac_idmac` varchar(17) NOT NULL,
  `ledr` int(10) UNSIGNED DEFAULT NULL,
  `ledb` int(10) UNSIGNED DEFAULT NULL,
  `ledg` int(10) UNSIGNED DEFAULT NULL,
  `buzzertom` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `buzzertempo` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `combina`
--

INSERT INTO `combina` (`idcombina`, `painel_idpainel`, `led_idled`, `mac_idmac`, `ledr`, `ledb`, `ledg`, `buzzertom`, `buzzertempo`) VALUES
(1, 2, 2, '5C:CF:7F:67:A2:49', 255, 0, 0, 0, 0),
(2, 1, 1, '5C:CF:7F:67:A2:49', 255, 0, 255, 0, 0),
(3, 4, 3, '5C:CF:7F:67:A2:49', 255, 0, 0, 2000, 100),
(4, 5, 3, '5C:CF:7F:67:A2:49', 255, 0, 255, 1000, 300),
(5, 6, 3, '5C:CF:7F:67:A2:49', 0, 0, 255, 0, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `led`
--

CREATE TABLE `led` (
  `idled` int(10) UNSIGNED NOT NULL,
  `nome` varchar(35) DEFAULT NULL,
  `pinor` int(10) UNSIGNED DEFAULT NULL,
  `pinog` int(10) UNSIGNED DEFAULT NULL,
  `pinob` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `led`
-- (linha 3: LED do projeto; ajuste os pinos aos GPIO reais da montagem)
--

INSERT INTO `led` (`idled`, `nome`, `pinor`, `pinog`, `pinob`) VALUES
(1, 'amandha', 0, 0, 255),
(2, 'amandha dia 24/09', 0, 0, 255),
(3, 'LED RGB proximidade', 13, 15, 16);

-- --------------------------------------------------------

--
-- Estrutura para tabela `mac`
-- (nova coluna: contador, atualizada pelo trigger a cada leitura)
--

CREATE TABLE `mac` (
  `idmac` varchar(17) NOT NULL,
  `nome` varchar(35) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT NULL,
  `contador` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `mac`
--

INSERT INTO `mac` (`idmac`, `nome`, `ativo`, `contador`) VALUES
('5C:CF:7F:67:A2:49', 'amandha', 1, 0),
('E8:DB:84:98:B5:DA', 'amandha dia 24', 1, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `painel`
-- (novas colunas: sensor, faixa de distancia em cm [distmin, distmax) e mensagem do LCD de 16 colunas)
--

CREATE TABLE `painel` (
  `idpainel` int(10) UNSIGNED NOT NULL,
  `nome` varchar(50) DEFAULT NULL,
  `datainicio` datetime DEFAULT NULL,
  `datafim` datetime DEFAULT NULL,
  `sensor_idsensor` int(10) UNSIGNED DEFAULT NULL,
  `distmin` float DEFAULT NULL,
  `distmax` float DEFAULT NULL,
  `mensagem` varchar(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `painel`
--

INSERT INTO `painel` (`idpainel`, `nome`, `datainicio`, `datafim`, `sensor_idsensor`, `distmin`, `distmax`, `mensagem`) VALUES
(1, 'amandha', '2026-09-18 14:18:36', '2026-09-23 23:59:59', NULL, NULL, NULL, NULL),
(2, 'amandha dia 24/09', '2026-09-24 00:00:00', '2026-09-24 23:59:59', NULL, NULL, NULL, NULL),
(3, 'amandha', '2026-09-18 14:18:36', '2026-09-23 23:59:59', NULL, NULL, NULL, NULL),
(4, 'Proximidade - perto', '2026-11-13 00:00:00', '2026-12-31 23:59:59', 1, 0, 10, 'PERTO! PARE'),
(5, 'Proximidade - atencao', '2026-11-13 00:00:00', '2026-12-31 23:59:59', 1, 10, 30, 'ATENCAO'),
(6, 'Proximidade - livre', '2026-11-13 00:00:00', '2026-12-31 23:59:59', 1, 30, 400, 'DISTANCIA OK');

-- --------------------------------------------------------

--
-- Estrutura para tabela `sensor` (NOVA - tabela extra)
--

CREATE TABLE `sensor` (
  `idsensor` int(10) UNSIGNED NOT NULL,
  `nome` varchar(35) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `contador` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `sensor`
--

INSERT INTO `sensor` (`idsensor`, `nome`, `ativo`, `contador`) VALUES
(1, 'HC-SR04 frontal', 1, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `leitura` (NOVA)
-- valor = distancia medida em cm
--

CREATE TABLE `leitura` (
  `idleitura` int(10) UNSIGNED NOT NULL,
  `mac_idmac` varchar(17) NOT NULL,
  `sensor_idsensor` int(10) UNSIGNED NOT NULL,
  `datahora` datetime NOT NULL DEFAULT current_timestamp(),
  `valor` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `combina`
--
ALTER TABLE `combina`
  ADD PRIMARY KEY (`idcombina`),
  ADD KEY `combina_FKIndex1` (`mac_idmac`),
  ADD KEY `combina_FKIndex2` (`led_idled`),
  ADD KEY `combina_FKIndex3` (`painel_idpainel`);

--
-- Índices de tabela `led`
--
ALTER TABLE `led`
  ADD PRIMARY KEY (`idled`);

--
-- Índices de tabela `mac`
--
ALTER TABLE `mac`
  ADD PRIMARY KEY (`idmac`);

--
-- Índices de tabela `painel`
--
ALTER TABLE `painel`
  ADD PRIMARY KEY (`idpainel`),
  ADD KEY `painel_FKIndex1` (`sensor_idsensor`);

--
-- Índices de tabela `sensor`
--
ALTER TABLE `sensor`
  ADD PRIMARY KEY (`idsensor`);

--
-- Índices de tabela `leitura`
--
ALTER TABLE `leitura`
  ADD PRIMARY KEY (`idleitura`),
  ADD KEY `leitura_FKIndex1` (`mac_idmac`),
  ADD KEY `leitura_FKIndex2` (`sensor_idsensor`),
  ADD KEY `leitura_datahora` (`datahora`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `combina`
--
ALTER TABLE `combina`
  MODIFY `idcombina` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `led`
--
ALTER TABLE `led`
  MODIFY `idled` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `painel`
--
ALTER TABLE `painel`
  MODIFY `idpainel` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `sensor`
--
ALTER TABLE `sensor`
  MODIFY `idsensor` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `leitura`
--
ALTER TABLE `leitura`
  MODIFY `idleitura` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `combina`
--
ALTER TABLE `combina`
  ADD CONSTRAINT `combina_ibfk_1` FOREIGN KEY (`mac_idmac`) REFERENCES `mac` (`idmac`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `combina_ibfk_2` FOREIGN KEY (`led_idled`) REFERENCES `led` (`idled`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `combina_ibfk_3` FOREIGN KEY (`painel_idpainel`) REFERENCES `painel` (`idpainel`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Restrições para tabelas `painel`
--
ALTER TABLE `painel`
  ADD CONSTRAINT `painel_ibfk_1` FOREIGN KEY (`sensor_idsensor`) REFERENCES `sensor` (`idsensor`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Restrições para tabelas `leitura`
--
ALTER TABLE `leitura`
  ADD CONSTRAINT `leitura_ibfk_1` FOREIGN KEY (`mac_idmac`) REFERENCES `mac` (`idmac`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `leitura_ibfk_2` FOREIGN KEY (`sensor_idsensor`) REFERENCES `sensor` (`idsensor`) ON DELETE NO ACTION ON UPDATE NO ACTION;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

--
-- Triggers
--

DELIMITER $$

-- Recusa a leitura se a placa ou o sensor estiver inativo (ou nao existir)
CREATE TRIGGER `leitura_bi` BEFORE INSERT ON `leitura`
FOR EACH ROW
BEGIN
  IF IFNULL((SELECT `ativo` FROM `mac` WHERE `idmac` = NEW.`mac_idmac`), 0) <> 1 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MAC inativo ou inexistente';
  END IF;
  IF IFNULL((SELECT `ativo` FROM `sensor` WHERE `idsensor` = NEW.`sensor_idsensor`), 0) <> 1 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Sensor inativo ou inexistente';
  END IF;
END$$

-- Atualiza os contadores da placa e do sensor a cada leitura gravada
CREATE TRIGGER `leitura_ai` AFTER INSERT ON `leitura`
FOR EACH ROW
BEGIN
  UPDATE `mac` SET `contador` = `contador` + 1 WHERE `idmac` = NEW.`mac_idmac`;
  UPDATE `sensor` SET `contador` = `contador` + 1 WHERE `idsensor` = NEW.`sensor_idsensor`;
END$$

DELIMITER ;

-- ============================================================
-- TESTE RAPIDO DOS TRIGGERS (rode na aba SQL, um bloco por vez)
-- ============================================================
--
-- 1) Estado antes (contadores em 0):
--    SELECT idmac AS id, ativo, contador FROM mac WHERE idmac = '5C:CF:7F:67:A2:49'
--    UNION ALL SELECT idsensor, ativo, contador FROM sensor WHERE idsensor = 1;
--
-- 2) Leitura valida (deve gravar; contadores vao para 1):
--    INSERT INTO leitura (mac_idmac, sensor_idsensor, valor)
--    VALUES ('5C:CF:7F:67:A2:49', 1, 25);
--    SELECT * FROM leitura;
--
-- 3) MAC inativo (deve dar erro "MAC inativo ou inexistente"):
--    UPDATE mac SET ativo = 0 WHERE idmac = '5C:CF:7F:67:A2:49';
--    INSERT INTO leitura (mac_idmac, sensor_idsensor, valor)
--    VALUES ('5C:CF:7F:67:A2:49', 1, 25);
--    UPDATE mac SET ativo = 1 WHERE idmac = '5C:CF:7F:67:A2:49';
--
-- 4) Sensor inativo (deve dar erro "Sensor inativo ou inexistente"):
--    UPDATE sensor SET ativo = 0 WHERE idsensor = 1;
--    INSERT INTO leitura (mac_idmac, sensor_idsensor, valor)
--    VALUES ('5C:CF:7F:67:A2:49', 1, 25);
--    UPDATE sensor SET ativo = 1 WHERE idsensor = 1;
--
-- 5) Limpar os testes:
--    DELETE FROM leitura;
--    UPDATE mac SET contador = 0;
--    UPDATE sensor SET contador = 0;
