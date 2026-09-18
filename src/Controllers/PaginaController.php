<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Elemento;
use App\Models\Pagina;

/**
 * Leitura e gravacao de uma pagina (docs/api.md, secao "Paginas").
 */
final class PaginaController
{
    /** GET /api/paginas/{id} */
    public function mostrar(Request $req, array $params): void
    {
        $pagina = $this->buscarPaginaDoUsuario((int) $params['id']);
        if ($pagina === null) {
            return;
        }

        Response::json([
            'id'         => (int) $pagina['id'],
            'ordem'      => (int) $pagina['ordem'],
            'tipo_folha' => $pagina['tipo_folha'],
            'elementos'  => Elemento::listar((int) $pagina['id']),
        ]);
    }

    /** PUT /api/paginas/{id} - substitui todos os elementos da pagina. */
    public function salvar(Request $req, array $params): void
    {
        $pagina = $this->buscarPaginaDoUsuario((int) $params['id']);
        if ($pagina === null) {
            return;
        }

        $corpo = $req->corpoJson();

        if (!isset($corpo['elementos']) || !is_array($corpo['elementos'])) {
            Response::erro('Campo "elementos" ausente ou invalido.');
            return;
        }

        // validacao basica de cada elemento
        foreach ($corpo['elementos'] as $e) {
            if (!in_array($e['tipo'] ?? '', Elemento::TIPOS, true) || !isset($e['dados'])) {
                Response::erro('Elemento com tipo ou dados invalidos.');
                return;
            }
        }

        Elemento::substituir((int) $pagina['id'], $corpo['elementos']);

        Response::json([
            'ok'        => true,
            'pagina_id' => (int) $pagina['id'],
            'recebidos' => count($corpo['elementos']),
        ]);
    }

    /**
     * Busca a pagina e confere se e do usuario logado.
     * Se nao for, ja responde o erro e devolve null.
     */
    private function buscarPaginaDoUsuario(int $id): ?array
    {
        $usuarioId = usuarioLogado();
        if ($usuarioId === null) {
            Response::erro('Voce precisa estar logado.', 401);
            return null;
        }

        $pagina = Pagina::buscar($id);
        if ($pagina === null || (int) $pagina['usuario_id'] !== $usuarioId) {
            Response::erro('Pagina nao encontrada.', 404);
            return null;
        }

        return $pagina;
    }
}
