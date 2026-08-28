# Contrato da API — Cadernos Online

> Este documento é o acordo entre a frente de **backend** (Pessoa A) e a de
> **editor** (Pessoa B). Qualquer mudança aqui precisa ser combinada pelos dois.

## Regras gerais

- Todas as respostas são `application/json; charset=utf-8`.
- O corpo das requisições `POST`/`PUT` também é JSON (exceto `/api/upload`).
- Erros seguem sempre o mesmo formato:

```json
{ "ok": false, "erro": "mensagem legível" }
```

### Status HTTP usados

| Código | Quando |
|---|---|
| 200 | Deu certo |
| 201 | Recurso criado (`POST /api/cadernos`) |
| 400 | Dado que o cliente mandou está faltando ou é inválido |
| 401 | Não está logado, ou o recurso é de outro usuário |
| 404 | Rota ou recurso não existe |
| 405 | A rota existe, mas não com esse verbo |
| 500 | Erro nosso (bug, banco fora do ar) |

---

## Autenticação

Sessão por cookie do PHP (`PHPSESSID`). O frontend não guarda token; basta
enviar as requisições com `credentials: 'same-origin'` no `fetch`.

### `POST /api/login`

```json
{ "email": "teste@cadernos.local", "senha": "123456" }
```

**200**
```json
{ "ok": true, "usuario": { "id": 1, "nome": "Usuario de Teste" } }
```
**401** — e-mail ou senha incorretos.

### `POST /api/logout`

Sem corpo. **200** `{ "ok": true }`

### `POST /api/cadastro`

Tela `/cadastro`. Cria o usuário e **já deixa a sessão aberta**, para ele cair
direto na lista de cadernos sem precisar logar em seguida.

```json
{ "nome": "Antonio", "email": "antonio@exemplo.com", "senha": "123456" }
```

**201**
```json
{ "ok": true, "usuario": { "id": 2, "nome": "Antonio" } }
```

| Erro | Quando |
|---|---|
| **400** | falta campo, e-mail malformado, ou senha com menos de 6 caracteres |
| **409** | e-mail já cadastrado (a coluna `email` é `UNIQUE`) |

A senha vai para o banco com `password_hash()`. A resposta nunca devolve
`senha_hash`.

---

## Cadernos

### `GET /api/cadernos`

Lista os cadernos **do usuário logado**.

```json
[
  { "id": 1, "titulo": "Caderno de Calculo", "tipo_folha": "pautada",
    "criado_em": "2026-08-28 15:00:00" }
]
```

### `POST /api/cadernos`

```json
{ "titulo": "Novo caderno", "tipo_folha": "quadriculada" }
```

`tipo_folha` ∈ `pautada` | `lisa` | `quadriculada`.

**201** → `{ "caderno": { "id": 4, "titulo": "...", "tipo_folha": "..." } }`

### `DELETE /api/cadernos/{id}`

**200** `{ "ok": true }` — apaga páginas e elementos em cascata.

---

## Páginas

### `GET /api/paginas/{id}`

Devolve a página **completa**, com todos os elementos, pronta para o Konva
renderizar sem transformação.

```json
{
  "id": 1,
  "ordem": 1,
  "tipo_folha": "pautada",
  "elementos": [
    {
      "id": 10,
      "tipo": "traco",
      "z_index": 0,
      "dados": {
        "ferramenta": "caneta",
        "cor": "#222222",
        "espessura": 3,
        "pontos": [50, 50, 60, 70, 80, 90]
      }
    },
    {
      "id": 11,
      "tipo": "texto",
      "x": 100, "y": 200,
      "z_index": 1,
      "dados": { "conteudo": "oi", "tamanho": 16, "cor": "#000000" }
    },
    {
      "id": 12,
      "tipo": "imagem",
      "x": 40, "y": 300, "largura": 200, "altura": 150,
      "z_index": 2,
      "dados": { "url": "/uploads/abc123.png" }
    }
  ]
}
```

#### Campos de um elemento

| Campo | Tipo | Vale para | Observação |
|---|---|---|---|
| `id` | int | todos | `null` para elementos ainda não salvos |
| `tipo` | `traco`\|`texto`\|`imagem` | todos | |
| `x`, `y` | float | texto, imagem | posição do canto superior esquerdo |
| `largura`, `altura` | float | imagem | |
| `z_index` | int | todos | ordem de empilhamento, menor embaixo |
| `dados` | objeto | todos | formato depende do `tipo` (abaixo) |

#### `dados` por tipo

**traco** — `pontos` é um array **plano** `[x1,y1, x2,y2, ...]`, exatamente o
formato que `Konva.Line` consome. As coordenadas são absolutas na folha, por
isso o traço não usa `x`/`y`.

```json
{ "ferramenta": "lapis|caneta|borracha", "cor": "#RRGGBB",
  "espessura": 3, "pontos": [50,50, 60,70] }
```

**texto**
```json
{ "conteudo": "texto digitado", "tamanho": 16, "cor": "#RRGGBB" }
```

**imagem**
```json
{ "url": "/uploads/abc123.png" }
```

### `PUT /api/paginas/{id}`

Salva o estado inteiro da página. O backend **substitui** todos os elementos
daquela página (apaga os antigos, insere os novos, dentro de uma transação).

```json
{ "elementos": [ /* mesmo formato acima, sem o campo id */ ] }
```

**200** `{ "ok": true, "pagina_id": 1, "recebidos": 12 }`

> **Decisão:** substituição em bloco, não diff. É mais simples de implementar
> corretamente, e o volume de dados de uma página é pequeno. Se ficar lento,
> aí sim vale otimizar.

---

## Upload

### `POST /api/upload`

`multipart/form-data` com o campo `arquivo`.

- Tipos aceitos: `image/png`, `image/jpeg`, `image/webp`
- Tamanho máximo: 2 MB
- O nome do arquivo é gerado pelo servidor (nunca o nome enviado pelo cliente)

**200** → `{ "url": "/uploads/abc123.png" }`
**400** → arquivo ausente, grande demais ou de tipo não permitido.

---

## Rotas já implementadas (marco 1)

| Rota | Estado |
|---|---|
| `GET /api/ping` | ✅ real |
| `GET /api/paginas/{id}` | 🟡 **mock** — devolve o JSON fixo acima para `id=1`, 404 para o resto |
| `PUT /api/paginas/{id}` | 🟡 **mock** — valida o corpo e devolve a contagem |
| resto | ⬜ a fazer |
