# Cadernos Online

Sistema web de cadernos virtuais: texto digitado, imagens e desenho à mão livre.
Trabalho II — Programação Web.

- **Backend:** PHP 8 puro (front controller + roteador próprio), MySQL/MariaDB via PDO
- **Frontend:** HTML/CSS/JS puro + [Konva.js](https://konvajs.org/) + Pointer Events

O Konva e as fontes são servidos pelo próprio projeto (`public/assets/`), então
a aplicação abre sem depender de internet.

## O que o editor faz

| Ferramenta | O que faz |
|---|---|
| Texto | clique na folha para escrever; duplo clique edita |
| Anexar imagem | envia a imagem e coloca na folha (arraste e redimensione no modo Mover) |
| Caneta, Lápis, Borracha | desenho à mão livre, com cor e espessura |
| Mover | arrasta textos e imagens; nas imagens aparecem as alças de redimensionar |

No modo **Mover**, clicar num texto ou imagem o seleciona: a tecla `Delete`
(ou o botão "Excluir seleção") remove o elemento.

Atalhos: `Ctrl+S` salva, `Ctrl+Z` desfaz, `Delete` remove o elemento
selecionado e `Esc` solta a seleção.

Desfazer, Limpar página, Baixar PNG, Salvar e navegação entre páginas ficam
na lateral. O "Baixar PNG" salva a folha como imagem, com as pautas e tudo
que está escrito e desenhado.
A barra acima da folha cria e exclui páginas.

## Como rodar

### 1. Configuração

```bash
cp config/config.example.php config/config.php
```

Ajuste usuário/senha do banco se necessário. Esse arquivo **não** vai para o Git.

O `debug` vem como `false` (é assim que se entrega). Coloque `true` enquanto
estiver programando para ver a mensagem real dos erros da API.

### 2. Banco

Importe `database/schema.sql` no phpMyAdmin (ou pela linha de comando):

```bash
C:/xampp/mysql/bin/mysql.exe -u root < database/schema.sql
```

E, opcionalmente, os dados de teste:

```bash
C:/xampp/mysql/bin/mysql.exe -u root < database/seed.sql
```

Usuário de teste: `admin@admin.com` / senha `admin123`.

### 3. E-mail de confirmação

O cadastro gera um link de confirmação. **Não é preciso configurar nada para
isso funcionar:** o modo padrão é `log`, que em vez de enviar grava a mensagem
em `storage/emails.log` — com o link dentro.

Com `debug` ligado, o link também aparece direto na tela, no aviso
"Confirme seu e-mail" → botão **Reenviar e-mail**. Dá para confirmar a conta
na hora, sem abrir arquivo nenhum.

Não confirmar **não impede o login**: o usuário entra e vê um aviso até
abrir o link.

> Para enviar e-mail de verdade falta instalar o PHPMailer e preencher os
> dados de SMTP em `config/config.php`. Enquanto isso não acontece, deixe
> `'modo' => 'log'`.

#### Já tem o banco criado?

Rode a atualização para ganhar as colunas de confirmação sem perder dados:

```bash
C:/xampp/mysql/bin/mysql.exe -u root < database/atualizacoes/001-confirmacao-email.sql
```

### 4. Servidor

```bash
C:/xampp/php/php.exe -S localhost:8000 -t public public/index.php
```

O terceiro argumento (`public/index.php`) é o *script roteador*: sem ele o
servidor embutido responde 404 para qualquer URL que não seja um arquivo real.

Teste: <http://localhost:8000/api/ping>

> O projeto fica fora do `htdocs`, entao o Apache nao o serve automaticamente.
> O `public/.htaccess` ja esta pronto caso precisem publicar no Apache do
> laboratorio: basta apontar o DocumentRoot (ou um alias) para `public/`.

## Estrutura

```
public/     única pasta exposta na web — front controller, assets (css/js/fontes), uploads
src/        Core (Router, Request, Response, Database, Config), funcoes.php (sessao, telas)
            Controllers, Models, Middleware
views/      telas em PHP (login, cadastro, cadernos, editor) + partials/
config/     config.example.php (versionado) e config.php (local, ignorado)
database/   schema.sql, seed.sql, atualizacoes/ (mudancas para quem ja tem banco)
storage/    emails.log, gerado pelo modo "log" de e-mail (ignorado pelo Git)
docs/       api.md — o contrato entre backend e editor
routes.php  mapa de rotas
```
