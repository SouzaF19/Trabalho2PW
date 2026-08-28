-- Cadernos Online - estrutura do banco
-- Rodar no phpMyAdmin ou: mysql -u root < database/schema.sql

CREATE DATABASE IF NOT EXISTS cadernos
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE cadernos;

DROP TABLE IF EXISTS elemento;
DROP TABLE IF EXISTS pagina;
DROP TABLE IF EXISTS caderno;
DROP TABLE IF EXISTS usuario;

CREATE TABLE usuario (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  nome       VARCHAR(100) NOT NULL,
  email      VARCHAR(150) UNIQUE NOT NULL,
  senha_hash VARCHAR(255) NOT NULL,
  criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE caderno (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  titulo     VARCHAR(150) NOT NULL,
  tipo_folha ENUM('pautada','lisa','quadriculada') NOT NULL DEFAULT 'pautada',
  criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_caderno_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pagina (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  caderno_id INT NOT NULL,
  ordem      INT NOT NULL,
  CONSTRAINT fk_pagina_caderno
    FOREIGN KEY (caderno_id) REFERENCES caderno(id) ON DELETE CASCADE,
  -- impede duas paginas com o mesmo numero dentro do mesmo caderno
  CONSTRAINT uq_pagina_ordem UNIQUE (caderno_id, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE elemento (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  pagina_id INT NOT NULL,
  tipo      ENUM('traco','texto','imagem') NOT NULL,
  x         FLOAT DEFAULT 0,
  y         FLOAT DEFAULT 0,
  largura   FLOAT NULL,
  altura    FLOAT NULL,
  z_index   INT NOT NULL DEFAULT 0,
  dados     JSON NOT NULL,
  CONSTRAINT fk_elemento_pagina
    FOREIGN KEY (pagina_id) REFERENCES pagina(id) ON DELETE CASCADE,
  -- a leitura mais comum e "todos os elementos da pagina X, em ordem de camada"
  INDEX idx_elemento_pagina_z (pagina_id, z_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
