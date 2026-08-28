<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Roteador: decide qual codigo responde a cada (metodo HTTP + caminho).
 *
 * Uma rota e registrada com um padrao legivel:
 *
 *     $router->get('/api/paginas/{id}', [PaginaController::class, 'mostrar']);
 *
 * e o roteador traduz esse padrao para uma expressao regular:
 *
 *     #^/api/paginas/(?P<id>[^/]+)$#
 *
 * Quando a URL casa, o trecho capturado vira o parametro $params['id'].
 */
final class Router
{
    /** @var array<int, array{metodo:string, regex:string, acao:mixed}> */
    private array $rotas = [];

    public function get(string $padrao, mixed $acao): void    { $this->adicionar('GET', $padrao, $acao); }
    public function post(string $padrao, mixed $acao): void   { $this->adicionar('POST', $padrao, $acao); }
    public function put(string $padrao, mixed $acao): void    { $this->adicionar('PUT', $padrao, $acao); }
    public function delete(string $padrao, mixed $acao): void { $this->adicionar('DELETE', $padrao, $acao); }

    /**
     * @param mixed $acao  Uma Closure, ou o par [NomeDaClasse::class, 'metodo'].
     *                     Usamos o par porque assim a classe do controller so
     *                     e carregada (pelo autoload) se a rota realmente casar.
     */
    private function adicionar(string $metodo, string $padrao, mixed $acao): void
    {
        $this->rotas[] = [
            'metodo' => $metodo,
            'regex'  => $this->compilar($padrao),
            'acao'   => $acao,
        ];
    }

    /** Transforma '/api/paginas/{id}' em uma regex com grupo nomeado. */
    private function compilar(string $padrao): string
    {
        // preg_quote escapa qualquer caractere especial do caminho literal.
        // Depois trocamos os marcadores {nome} pelo grupo de captura.
        $escapado = preg_quote($padrao, '#');

        $regex = preg_replace(
            '#\\\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\\\}#',  // casa o "{id}" ja escapado
            '(?P<$1>[^/]+)',
            $escapado
        );

        return '#^' . $regex . '$#';
    }

    /** Encontra a rota e executa. Responde 404 ou 405 se nao houver rota. */
    public function despachar(Request $req): void
    {
        $caminhoExiste = false;

        foreach ($this->rotas as $rota) {
            if (!preg_match($rota['regex'], $req->caminho, $capturas)) {
                continue;
            }

            // O caminho bate, mas o verbo pode nao bater. Guardamos para
            // poder responder 405 (Method Not Allowed) em vez de 404.
            if ($rota['metodo'] !== $req->metodo) {
                $caminhoExiste = true;
                continue;
            }

            // preg_match devolve grupos numericos E nomeados; queremos so os nomeados.
            $params = array_filter($capturas, 'is_string', ARRAY_FILTER_USE_KEY);

            $this->executar($rota['acao'], $req, $params);
            return;
        }

        if ($caminhoExiste) {
            Response::erro('Metodo ' . $req->metodo . ' nao permitido para esta rota.', 405);
            return;
        }

        Response::erro('Rota nao encontrada: ' . $req->caminho, 404);
    }

    /** @param array<string,string> $params */
    private function executar(mixed $acao, Request $req, array $params): void
    {
        if (is_array($acao)) {
            [$classe, $metodo] = $acao;
            $acao = [new $classe(), $metodo];   // instancia so agora
        }

        $acao($req, $params);
    }
}
