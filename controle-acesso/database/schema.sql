CREATE DATABASE IF NOT EXISTS controle_acesso CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE controle_acesso;

CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  login VARCHAR(30) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  cpf CHAR(11) NOT NULL UNIQUE,
  data_nascimento DATE NULL,
  ativo ENUM('S','N') NOT NULL DEFAULT 'S',
  periodo ENUM('manha','tarde','noite','dia_todo') NOT NULL DEFAULT 'dia_todo',
  dom TINYINT(1) NOT NULL DEFAULT 0, seg TINYINT(1) NOT NULL DEFAULT 0, ter TINYINT(1) NOT NULL DEFAULT 0,
  qua TINYINT(1) NOT NULL DEFAULT 0, qui TINYINT(1) NOT NULL DEFAULT 0, sex TINYINT(1) NOT NULL DEFAULT 0,
  sab TINYINT(1) NOT NULL DEFAULT 0,
  valor_mensalidade DECIMAL(10,2) NOT NULL DEFAULT 0,
  criado_em DATETIME NOT NULL
) ENGINE=InnoDB;

-- usuario_id NULL = chave pendente (enviada pelo ESP32 em modo cadastro)
-- ativo 0 = chave bloqueada no painel (acesso negado mesmo com cadastro em dia)
CREATE TABLE IF NOT EXISTS chaves (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uid VARCHAR(40) NOT NULL UNIQUE,
  usuario_id INT UNSIGNED NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em DATETIME NOT NULL,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- uma linha = mensalidade paga naquela competência (AAAA-MM)
CREATE TABLE IF NOT EXISTS pagamentos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  competencia CHAR(7) NOT NULL,
  valor DECIMAL(10,2) NOT NULL DEFAULT 0,
  pago_em DATETIME NOT NULL,
  admin_id INT UNSIGNED NULL,
  UNIQUE KEY uq_pag (usuario_id, competencia),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- catracas/controladoras (ESP32, ESP8266) que falam com a API; token é a chave da API
CREATE TABLE IF NOT EXISTS catracas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  tipo ENUM('esp32','esp8266','outro') NOT NULL DEFAULT 'esp32',
  token CHAR(32) NOT NULL UNIQUE,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  ultimo_contato DATETIME NULL,
  criado_em DATETIME NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS acessos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uid VARCHAR(40) NOT NULL,
  usuario_id INT UNSIGNED NULL,
  usuario_nome VARCHAR(100) NULL,
  catraca_id INT UNSIGNED NULL,
  autorizado TINYINT(1) NOT NULL,
  motivo VARCHAR(60) NOT NULL,
  data_hora DATETIME NOT NULL,
  INDEX (data_hora), INDEX (usuario_id),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  FOREIGN KEY (catraca_id) REFERENCES catracas(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- toda alteração feita por um administrador fica registrada aqui
CREATE TABLE IF NOT EXISTS auditoria (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id INT UNSIGNED NULL,
  admin_login VARCHAR(30) NULL,
  acao VARCHAR(40) NOT NULL,
  entidade VARCHAR(30) NOT NULL,
  entidade_id INT UNSIGNED NULL,
  detalhes TEXT NULL,
  ip VARCHAR(45) NULL,
  criado_em DATETIME NOT NULL,
  INDEX (criado_em),
  FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;
