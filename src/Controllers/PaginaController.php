<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

/**
 * MOCK (marco 1).
 *
 * Devolve a pagina de exemplo do docs/api.md sem tocar no banco, para que a
 * frente do editor possa comecar em paralelo a do backend.
 *
 * Quando o Model Pagina existir, so o corpo de mostrar() muda: a assinatura
 * da rota e o formato do JSON continuam identicos. Esse e o valor do contrato
 * de API definido antes do codigo.
 */
final class PaginaController
{
    /** GET /api/paginas/{id} */
    public function mostrar(Request $req, array $params): void
    {
        $id = (int) $params['id'];

        if ($id !== 1) {
            Response::erro('Pagina nao encontrada.', 404);
            return;
        }

        Response::json([
            'id'         => 1,
            'ordem'      => 1,
            'tipo_folha' => 'pautada',
            'elementos'  => [
                [
                    'id'      => 10,
                    'tipo'    => 'traco',
                    'z_index' => 0,
                    'dados'   => [
                        'ferramenta' => 'caneta',
                        'cor'        => '#222222',
                        'espessura'  => 3,
                        'pontos'     => [50, 50, 60, 70, 80, 90],
                    ],
                ],
                [
                    'id'      => 11,
                    'tipo'    => 'texto',
                    'x'       => 100,
                    'y'       => 200,
                    'z_index' => 1,
                    'dados'   => ['conteudo' => 'oi', 'tamanho' => 16, 'cor' => '#000000'],
                ],
                [
                    'id'      => 12,
                    'tipo'    => 'imagem',
                    'x'       => 40,
                    'y'       => 300,
                    'largura' => 200,
                    'altura'  => 150,
                    'z_index' => 2,
                    'dados'   => ['url' => '/uploads/abc123.png'],
                ],
            ],
        ]);
    }

    /** PUT /api/paginas/{id} - mock: so confirma o que recebeu. */
    public function salvar(Request $req, array $params): void
    {
        $corpo = $req->corpoJson();

        if (!isset($corpo['elementos']) || !is_array($corpo['elementos'])) {
            Response::erro('Campo "elementos" ausente ou invalido.', 400);
            return;
        }

        Response::json([
            'ok'        => true,
            'pagina_id' => (int) $params['id'],
            'recebidos' => count($corpo['elementos']),
        ]);
    }
}
