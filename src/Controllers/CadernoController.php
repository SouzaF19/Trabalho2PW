<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Caderno;
use App\Models\Pagina;

/** Cadernos e suas paginas (docs/api.md, secao "Cadernos"). */
final class CadernoController
{
    /** GET /api/cadernos */
    public function listar(Request $req, array $params): void
    {
        $usuarioId = usuarioLogado();
        if ($usuarioId === null) {
            Response::erro('Voce precisa estar logado.', 401);
            return;
        }

        Response::json(Caderno::listar($usuarioId));
    }

    /** POST /api/cadernos */
    public function criar(Request $req, array $params): void
    {
        $usuarioId = usuarioLogado();
        if ($usuarioId === null) {
            Response::erro('Voce precisa estar logado.', 401);
            return;
        }

        $corpo     = $req->corpoJson();
        $titulo    = trim($corpo['titulo'] ?? '');
        $tipoFolha = $corpo['tipo_folha'] ?? 'pautada';

        if ($titulo === '') {
            Response::erro('O caderno precisa de um titulo.');
            return;
        }
        if (!in_array($tipoFolha, ['pautada', 'lisa', 'quadriculada'], true)) {
            Response::erro('Tipo de folha invalido.');
            return;
        }

        Response::json(['caderno' => Caderno::criar($usuarioId, $titulo, $tipoFolha)], 201);
    }

    /** PUT /api/cadernos/{id} - renomeia e troca o tipo de folha. */
    public function atualizar(Request $req, array $params): void
    {
        $usuarioId = usuarioLogado();
        if ($usuarioId === null) {
            Response::erro('Voce precisa estar logado.', 401);
            return;
        }

        $atual = Caderno::buscar((int) $params['id'], $usuarioId);
        if ($atual === null) {
            Response::erro('Caderno nao encontrado.', 404);
            return;
        }

        $corpo = $req->corpoJson();

        // Corpo vazio (ou JSON que nao deu para ler) responderia 200 sem
        // mudar nada, o que parece sucesso. Melhor dizer que nao deu.
        if ($corpo === []) {
            Response::erro('Mande ao menos "titulo" ou "tipo_folha".');
            return;
        }

        // O que nao vier no corpo fica como esta: da para so renomear,
        // ou so trocar a folha, sem precisar mandar os dois campos.
        $titulo    = trim($corpo['titulo'] ?? $atual['titulo']);
        $tipoFolha = $corpo['tipo_folha'] ?? $atual['tipo_folha'];

        if ($titulo === '') {
            Response::erro('O caderno precisa de um titulo.');
            return;
        }
        if (!in_array($tipoFolha, ['pautada', 'lisa', 'quadriculada'], true)) {
            Response::erro('Tipo de folha invalido.');
            return;
        }

        $caderno = Caderno::atualizar((int) $atual['id'], $usuarioId, $titulo, $tipoFolha);

        Response::json(['caderno' => $caderno]);
    }

    /** DELETE /api/cadernos/{id} */
    public function excluir(Request $req, array $params): void
    {
        $usuarioId = usuarioLogado();
        if ($usuarioId === null) {
            Response::erro('Voce precisa estar logado.', 401);
            return;
        }

        if (!Caderno::excluir((int) $params['id'], $usuarioId)) {
            Response::erro('Caderno nao encontrado.', 404);
            return;
        }

        Response::json(['ok' => true]);
    }

    /** GET /api/cadernos/{id}/paginas */
    public function listarPaginas(Request $req, array $params): void
    {
        $usuarioId = usuarioLogado();
        if ($usuarioId === null) {
            Response::erro('Voce precisa estar logado.', 401);
            return;
        }

        $caderno = Caderno::buscar((int) $params['id'], $usuarioId);
        if ($caderno === null) {
            Response::erro('Caderno nao encontrado.', 404);
            return;
        }

        Response::json(Pagina::listar((int) $caderno['id']));
    }

    /** POST /api/cadernos/{id}/paginas - cria uma pagina em branco no fim. */
    public function criarPagina(Request $req, array $params): void
    {
        $usuarioId = usuarioLogado();
        if ($usuarioId === null) {
            Response::erro('Voce precisa estar logado.', 401);
            return;
        }

        $caderno = Caderno::buscar((int) $params['id'], $usuarioId);
        if ($caderno === null) {
            Response::erro('Caderno nao encontrado.', 404);
            return;
        }

        Response::json(['pagina' => Pagina::criar((int) $caderno['id'])], 201);
    }
}
