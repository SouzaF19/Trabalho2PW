<?php
declare(strict_types=1);

namespace App\Core;

/** Escreve a resposta HTTP. Toda a API responde por aqui. */
final class Response
{
    /**
     * @param mixed $dados  Qualquer coisa serializavel (array, objeto, escalar).
     * @param int   $status Codigo HTTP: 200 ok, 201 criado, 400 pedido ruim,
     *                      401 nao autenticado, 404 nao achou, 500 erro nosso.
     */
    public static function json(mixed $dados, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            $dados,
            // UNESCAPED_UNICODE: acento vira "á", nao "\u00e1"
            // UNESCAPED_SLASHES: url vira "/uploads/x.png", nao "\/uploads\/x.png"
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    /** Atalho para respostas de erro, sempre no mesmo formato. */
    public static function erro(string $mensagem, int $status = 400): void
    {
        self::json(['ok' => false, 'erro' => $mensagem], $status);
    }
}
