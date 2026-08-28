<?php
declare(strict_types=1);

/**
 * FRONT CONTROLLER
 *
 * Toda requisicao da aplicacao entra por este arquivo unico. O .htaccess
 * (Apache) reescreve qualquer URL que nao seja um arquivo real para ca.
 *
 * Por que uma porta de entrada so?
 *  - Um lugar so para iniciar sessao, carregar config, tratar erro.
 *  - As URLs param de espelhar a arvore de arquivos: /api/paginas/1 nao
 *    precisa existir como pasta. Voce decide o que cada rota significa.
 *  - Nao existe arquivo .php solto e esquecido acessivel pelo navegador.
 */

// Servidor embutido do PHP (php -S): se a URL aponta para um arquivo que
// existe de verdade (css, js, imagem), deixa o servidor entregar direto.
if (PHP_SAPI === 'cli-server') {
    $alvo = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($alvo)) {
        return false;
    }
}

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/src/autoload.php';

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;

try {
    Config::carregar(BASE_PATH . '/config/config.php');

    $router = new Router();
    require BASE_PATH . '/routes.php';

    $router->despachar(Request::capturar());

} catch (Throwable $e) {
    // Rede de seguranca: nenhuma excecao vaza como pagina de erro do PHP,
    // que quebraria o JSON.parse() do frontend e ainda exporia caminhos
    // do servidor.
    error_log((string) $e);

    Response::erro(
        Config::get('debug') === true
            ? $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine()
            : 'Erro interno do servidor.',
        500
    );
}
