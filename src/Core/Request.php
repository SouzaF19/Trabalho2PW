<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Encapsula a requisicao HTTP que chegou.
 *
 * Por que uma classe em vez de mexer em $_SERVER/$_POST direto?
 *  - Um lugar so decide como o caminho da URL e limpo.
 *  - O resto do codigo fica testavel: da pra criar um Request na mao.
 */
final class Request
{
    private function __construct(
        public string $metodo,
        public string $caminho,
        public array  $query
    ) {}

    /** Monta o Request a partir das variaveis globais do PHP. */
    public static function capturar(): self
    {
        $metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Formularios HTML so sabem enviar GET e POST. A convencao para
        // simular os outros verbos e mandar POST com um campo _method=PUT.
        if ($metodo === 'POST' && isset($_POST['_method'])) {
            $metodo = strtoupper((string) $_POST['_method']);
        }

        return new self($metodo, self::caminhoLimpo(), $_GET);
    }

    /**
     * Extrai o caminho da rota: sem query string e sem o diretorio base.
     *
     *   php -S localhost:8000 -t public  ->  /api/paginas/1
     *   Apache em /cadernos/public/      ->  /api/paginas/1  (tambem)
     */
    private static function caminhoLimpo(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        // SCRIPT_NAME = /cadernos/public/index.php  ->  base = /cadernos/public
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $base   = rtrim(dirname($script), '/');

        if ($base !== '' && $base !== '.' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        return '/' . trim($uri, '/');
    }

    /** Le o corpo da requisicao como JSON. Retorna [] se vier vazio ou invalido. */
    public function corpoJson(): array
    {
        $bruto = file_get_contents('php://input');
        if ($bruto === false || $bruto === '') {
            return [];
        }

        $dados = json_decode($bruto, true);
        return is_array($dados) ? $dados : [];
    }
}
