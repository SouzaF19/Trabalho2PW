-- Dados de teste. Rodar DEPOIS de schema.sql.
-- Usuario: teste@cadernos.local  /  senha: 123456
USE cadernos;

INSERT INTO usuario (id, nome, email, senha_hash) VALUES
  (1, 'Usuario de Teste', 'teste@cadernos.local',
   '$2y$10$wb6mLIb74sMnnz8w28y/SeBJkSSK2jJtJr8O/25TU0jvyVOP4VGxe');

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
