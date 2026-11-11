-- Confirmacao de e-mail: colunas novas na tabela usuario.
--
-- Rode este arquivo se voce JA tem o banco criado e nao quer perder os dados:
--   C:/xampp/mysql/bin/mysql.exe -u root < database/atualizacoes/001-confirmacao-email.sql
--
-- Quem for criar o banco do zero nao precisa: o schema.sql ja vem com as colunas.

USE cadernos;

ALTER TABLE usuario
  ADD COLUMN email_confirmado  TINYINT(1)  NOT NULL DEFAULT 0,
  ADD COLUMN token_confirmacao VARCHAR(64) NULL,
  ADD COLUMN token_expira_em   DATETIME    NULL,
  -- a busca pelo token acontece em toda confirmacao
  ADD INDEX idx_usuario_token (token_confirmacao);

-- Quem ja estava cadastrado antes desta mudanca continua podendo usar o sistema:
-- seria injusto exigir confirmacao de um e-mail que nunca foi enviado.
UPDATE usuario SET email_confirmado = 1;
