<?php
declare(strict_types=1);

/**
 * Autoload PSR-4 manual (sem Composer).
 *
 * Traduz um nome de classe em caminho de arquivo:
 *   App\Core\Router               ->  src/Core/Router.php
 *   App\Controllers\PaginaController -> src/Controllers/PaginaController.php
 *
 * O PHP so chama esta funcao quando encontra uma classe que ainda nao
 * conhece. Ou seja: nada e carregado antes da hora.
 */
spl_autoload_register(function (string $classe): void {
    $prefixo = 'App\\';
    $baseDir = __DIR__ . '/';

    // A classe pertence ao nosso namespace? Se nao, deixa outro autoloader tentar.
    if (!str_starts_with($classe, $prefixo)) {
        return;
    }

    $relativa = substr($classe, strlen($prefixo));   // ex.: Core\Router
    $arquivo  = $baseDir . str_replace('\\', '/', $relativa) . '.php';

    if (is_file($arquivo)) {
        require $arquivo;
    }
});
