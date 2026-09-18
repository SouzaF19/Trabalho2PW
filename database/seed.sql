-- Dados de teste. Rodar DEPOIS de schema.sql.
-- Usuario: admin@admin.com  /  senha: admin123
USE cadernos;

INSERT INTO usuario (id, nome, email, senha_hash) VALUES
  (1, 'Administrador', 'admin@admin.com',
   '$2y$10$FhCLQTY3hIFEvIMK7a5woeMTuMogDDRHOYivPRFEI/sd2B1na34Ry');

INSERT INTO caderno (id, usuario_id, titulo, tipo_folha) VALUES
  (1, 1, 'Caderno de Calculo', 'pautada'),
  (2, 1, 'Rascunhos',          'lisa'),
  (3, 1, 'Geometria',          'quadriculada');

INSERT INTO pagina (id, caderno_id, ordem) VALUES
  (1, 1, 1),
  (2, 1, 2);

INSERT INTO elemento (id, pagina_id, tipo, x, y, largura, altura, z_index, dados) VALUES
  (10, 1, 'traco',  0,   0,   NULL, NULL, 0,
   '{"ferramenta":"caneta","cor":"#222222","espessura":3,"pontos":[50,50,60,70,80,90]}'),
  (11, 1, 'texto',  100, 200, NULL, NULL, 1,
   '{"conteudo":"oi","tamanho":16,"cor":"#000000"}'),
  (12, 1, 'imagem', 40,  300, 200,  150,  2,
   '{"url":"/uploads/abc123.png"}');
