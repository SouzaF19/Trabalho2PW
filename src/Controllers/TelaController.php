<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Models\Caderno;

/**
 * Rotas que devolvem HTML (as telas).
 * O conteudo (lista de cadernos, desenhos) e buscado depois pelo JS na API.
 */
final class TelaController
{
    /** GET / */
    public function inicio(Request $req, array $params): void
    {
        redirecionar(usuarioLogado() === null ? '/login' : '/cadernos');
    }

    /** GET /login */
    public function login(Request $req, array $params): void
    {
        if (usuarioLogado() !== null) {
            redirecionar('/cadernos');
            return;
        }

        renderizar('login', ['titulo' => 'Login']);
    }

    /** GET /cadastro */
    public function cadastro(Request $req, array $params): void
    {
        if (usuarioLogado() !== null) {
            redirecionar('/cadernos');
            return;
        }

        renderizar('cadastro', ['titulo' => 'Cadastro']);
    }

    /** GET /cadernos */
    public function cadernos(Request $req, array $params): void
    {
        if (usuarioLogado() === null) {
            redirecionar('/login');
            return;
        }

        renderizar('cadernos', ['titulo' => 'Meus cadernos']);
    }

    /** GET /cadernos/{id} - o editor */
    public function editor(Request $req, array $params): void
    {
        $usuarioId = usuarioLogado();
        if ($usuarioId === null) {
            redirecionar('/login');
            return;
        }

        $caderno = Caderno::buscar((int) $params['id'], $usuarioId);
        if ($caderno === null) {
            redirecionar('/cadernos');
            return;
        }

        renderizar('editor', ['titulo' => $caderno['titulo'], 'caderno' => $caderno]);
    }
}
