# Cadernos Online

Sistema web de cadernos virtuais: texto digitado, imagens e desenho à mão livre.
Trabalho II — Programação Web.

- **Backend:** PHP 8 puro (front controller + roteador próprio), MySQL/MariaDB via PDO
- **Frontend:** HTML/CSS/JS puro + [Konva.js](https://konvajs.org/) + Pointer Events

## Como rodar

### 1. Configuração

```bash
cp config/config.example.php config/config.php
```

Ajuste usuário/senha do banco se necessário. Esse arquivo **não** vai para o Git.

### 2. Banco

Importe `database/schema.sql` no phpMyAdmin (ou pela linha de comando):

```bash
C:/xampp/mysql/bin/mysql.exe -u root < database/schema.sql
```

E, opcionalmente, os dados de teste:

```bash
C:/xampp/mysql/bin/mysql.exe -u root < database/seed.sql
```

Usuário de teste: `teste@cadernos.local` / senha `123456`.

### 3. Servidor

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
public/     única pasta exposta na web — front controller, assets, uploads
src/        Core (Router, Request, Response, Database, Config)
            Controllers, Models, Middleware
views/      templates PHP
config/     config.example.php (versionado) e config.php (local, ignorado)
database/   schema.sql, seed.sql
docs/       api.md — o contrato entre backend e editor
routes.php  mapa de rotas
```

**Regra de ouro:** só `public/` é servida pela web. `src/`, `config/` e
`database/` ficam fora do alcance do navegador.

## Documentação

- [`docs/api.md`](docs/api.md) — contrato da API
