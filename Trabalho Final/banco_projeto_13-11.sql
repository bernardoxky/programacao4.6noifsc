-- ============================================================
-- Trabalho Final - Programacao IV (IFSC Chapeco)
-- Sensor de proximidade: ESP8266 (ESP-12E) + HC-SR04 + LED RGB + LCD + buzzer
-- Grupo: Amandha Veiga, Bernardo Lamperti, Pedro Sudatti, Valentina Povala
--
-- Ordem de criacao: RGB -> mac -> sensor -> faixa -> leitura
-- ATENCAO: os DROP abaixo apagam os dados. Remova-os se ja houver dados a manter.
-- Os triggers usam DELIMITER: no phpMyAdmin, troque o campo "Delimitador" para $$
-- (ou rode o bloco dos triggers separado).
-- ============================================================

DROP TABLE IF EXISTS leitura;
DROP TABLE IF EXISTS faixa;
DROP TABLE IF EXISTS mac_has_ultrassonico;
DROP TABLE IF EXISTS sensor;
DROP TABLE IF EXISTS mac;
DROP TABLE IF EXISTS RGB;

-- ------------------------------------------------------------
-- Cores do LED RGB
-- cor = valor 0xRRGGBB em decimal (ex.: verde = 65280)
-- ------------------------------------------------------------
CREATE TABLE RGB (
  idRGB INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(15) NOT NULL,
  cor INTEGER UNSIGNED NOT NULL,
  pino INTEGER UNSIGNED NULL,
  PRIMARY KEY (idRGB)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Placas ESP8266 (nome = endereco MAC, ex.: AA:BB:CC:DD:EE:FF)
-- ------------------------------------------------------------
CREATE TABLE mac (
  idmac INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(20) NOT NULL,
  ativo BOOL NOT NULL DEFAULT 1,
  contador INTEGER UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (idmac),
  UNIQUE KEY mac_nome_unico (nome)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Sensores (ultrassonico) e configuracao padrao dos atuadores
-- display = texto padrao do LCD (16 colunas)
-- ------------------------------------------------------------
CREATE TABLE sensor (
  idsensor INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  RGB_idRGB INTEGER UNSIGNED NOT NULL,
  nome VARCHAR(25) NOT NULL,
  ativo BOOL NOT NULL DEFAULT 1,
  distancia FLOAT NULL,
  display VARCHAR(16) NULL,
  buzzer_tom INT NULL,
  buzzer_tempo INTEGER UNSIGNED NULL,
  contador INTEGER UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (idsensor),
  INDEX sensor_FKIndex1 (RGB_idRGB),
  CONSTRAINT fk_sensor_rgb FOREIGN KEY (RGB_idRGB) REFERENCES RGB (idRGB)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Faixas de alerta (tabela extra): programada pelo painel.
-- Intervalo [dist_min, dist_max) em cm -> cor, mensagem do display e buzzer
-- buzzer_tom = frequencia em Hz (0 = sem som); buzzer_tempo = ms
-- ------------------------------------------------------------
CREATE TABLE faixa (
  idfaixa INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  sensor_idsensor INTEGER UNSIGNED NOT NULL,
  RGB_idRGB INTEGER UNSIGNED NOT NULL,
  dist_min FLOAT NOT NULL,
  dist_max FLOAT NOT NULL,
  mensagem VARCHAR(16) NOT NULL,
  buzzer_tom INT NOT NULL DEFAULT 0,
  buzzer_tempo INTEGER UNSIGNED NOT NULL DEFAULT 0,
  ativo BOOL NOT NULL DEFAULT 1,
  PRIMARY KEY (idfaixa),
  INDEX faixa_FKIndex1 (sensor_idsensor),
  INDEX faixa_FKIndex2 (RGB_idRGB),
  CONSTRAINT fk_faixa_sensor FOREIGN KEY (sensor_idsensor) REFERENCES sensor (idsensor),
  CONSTRAINT fk_faixa_rgb FOREIGN KEY (RGB_idRGB) REFERENCES RGB (idRGB),
  CONSTRAINT ck_faixa_intervalo CHECK (dist_min < dist_max)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Leituras (substitui mac_has_ultrassonico): uma linha por medicao
-- valor = distancia em cm
-- ------------------------------------------------------------
CREATE TABLE leitura (
  idleitura INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  mac_idmac INTEGER UNSIGNED NOT NULL,
  sensor_idsensor INTEGER UNSIGNED NOT NULL,
  datahora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  valor INTEGER UNSIGNED NULL,
  PRIMARY KEY (idleitura),
  INDEX leitura_FKIndex1 (mac_idmac),
  INDEX leitura_FKIndex2 (sensor_idsensor),
  INDEX leitura_datahora (datahora),
  CONSTRAINT fk_leitura_mac FOREIGN KEY (mac_idmac) REFERENCES mac (idmac),
  CONSTRAINT fk_leitura_sensor FOREIGN KEY (sensor_idsensor) REFERENCES sensor (idsensor)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Triggers
-- ------------------------------------------------------------
DELIMITER $$

-- Recusa a leitura se a placa ou o sensor estiver inativo
CREATE TRIGGER leitura_bi BEFORE INSERT ON leitura
FOR EACH ROW
BEGIN
  IF IFNULL((SELECT ativo FROM mac WHERE idmac = NEW.mac_idmac), 0) <> 1 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'MAC inativo ou inexistente';
  END IF;
  IF IFNULL((SELECT ativo FROM sensor WHERE idsensor = NEW.sensor_idsensor), 0) <> 1 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Sensor inativo ou inexistente';
  END IF;
END$$

-- Atualiza os contadores a cada leitura gravada
CREATE TRIGGER leitura_ai AFTER INSERT ON leitura
FOR EACH ROW
BEGIN
  UPDATE mac    SET contador = contador + 1 WHERE idmac = NEW.mac_idmac;
  UPDATE sensor SET contador = contador + 1 WHERE idsensor = NEW.sensor_idsensor;
END$$

DELIMITER ;

-- ------------------------------------------------------------
-- Dados de exemplo para teste (pode apagar)
-- Troque o MAC pelo da sua placa (Serial: WiFi.macAddress())
-- ------------------------------------------------------------
INSERT INTO RGB (nome, cor) VALUES
  ('Verde', 65280),
  ('Amarelo', 16776960),
  ('Vermelho', 16711680);

INSERT INTO mac (nome, ativo) VALUES ('AA:BB:CC:DD:EE:FF', 1);

INSERT INTO sensor (RGB_idRGB, nome, ativo, display) VALUES
  (1, 'HC-SR04 frontal', 1, 'DISTANCIA OK');

INSERT INTO faixa (sensor_idsensor, RGB_idRGB, dist_min, dist_max, mensagem, buzzer_tom, buzzer_tempo) VALUES
  (1, 3,   0,  10, 'PERTO! PARE',  2000, 100),
  (1, 2,  10,  30, 'ATENCAO',      1000, 300),
  (1, 1,  30, 400, 'DISTANCIA OK',    0,   0);
