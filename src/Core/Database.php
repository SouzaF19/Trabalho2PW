<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Ponto unico de acesso ao banco.
 *
 * Abrir conexao custa caro, entao guardamos a instancia em uma propriedade
 * estatica: a primeira chamada conecta, as seguintes reaproveitam.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function conexao(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $cfg = Config::get('banco');

        // O charset no DSN e obrigatorio. Sem ele o PHP e o MySQL podem
        // conversar em latin1 e seus acentos viram lixo.
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $cfg['host'],
            $cfg['porta'],
            $cfg['nome']
        );

        try {
            self::$pdo = new PDO($dsn, $cfg['usuario'], $cfg['senha'], [
                // Erro de SQL vira excecao, em vez de um "false" silencioso
                // que voce so descobre tres telas depois.
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

                // fetch() devolve ['nome' => 'x'] em vez de duplicar tudo
                // em chaves numericas tambem.
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

                // O MAIS IMPORTANTE. Com false, o PHP manda o SQL e os dados
                // ao servidor em pacotes SEPARADOS. O dado nunca e concatenado
                // no comando, entao nao ha como um valor virar SQL executavel.
                // Com true (padrao), o PHP monta a string aqui e escapa "na mao".
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('Falha ao conectar no banco: ' . $e->getMessage(), 0, $e);
        }

        return self::$pdo;
    }
}
