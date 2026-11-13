CREATE TABLE mac (
  idmac INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(20) NULL,
  ativo BOOL NULL,
  contador INTEGER UNSIGNED NULL DEFAULT 0,
  PRIMARY KEY(idmac)
);

CREATE TABLE mac_has_ultrassonico (
  mac_idmac INTEGER UNSIGNED NOT NULL,
  sensor_idsensor INTEGER UNSIGNED NOT NULL,
  datahora DATETIME NULL,
  valor INTEGER UNSIGNED NULL,
  PRIMARY KEY(mac_idmac, sensor_idsensor),
  INDEX mac_has_ultrassonico_FKIndex1(mac_idmac),
  INDEX mac_has_ultrassonico_FKIndex2(sensor_idsensor)
);

CREATE TABLE RGB (
  idRGB INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  cor INTEGER UNSIGNED NULL,
  pino INTEGER UNSIGNED NULL,
  nome VARCHAR(15) NULL,
  PRIMARY KEY(idRGB)
);

CREATE TABLE sensor (
  idsensor INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  RGB_idRGB INTEGER UNSIGNED NOT NULL,
  distancia FLOAT NULL,
  display VARCHAR(16) NULL,
  buzzer_tom INT NULL,
  buzzer_tempo INTEGER UNSIGNED NULL,
  nome VARCHAR(25) NULL,
  contador INTEGER UNSIGNED NULL DEFAULT 0,
  PRIMARY KEY(idsensor),
  INDEX sensor_FKIndex1(RGB_idRGB)
);


