<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/** Carrega config/config.php uma vez e serve os valores para o resto do sistema. */
final class Config
{
    private static array $valores = [];

    public static function carregar(string $caminho): void
    {
        if (!is_file($caminho)) {
            throw new RuntimeException(
                "Arquivo de configuracao nao encontrado: $caminho\n" .
                'Copie config/config.example.php para config/config.php.'
            );
        }

        // O arquivo termina com "return [...]", entao require devolve o array.
        self::$valores = require $caminho;
    }

    public static function get(string $chave, mixed $padrao = null): mixed
    {
        return self::$valores[$chave] ?? $padrao;
    }
}
