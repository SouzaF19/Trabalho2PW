<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Config;
use App\Core\Database;

/** Consultas na tabela usuario. */
final class Usuario
{
    public static function buscarPorEmail(string $email): ?array
    {
        $sql = Database::conexao()->prepare('SELECT * FROM usuario WHERE email = ?');
        $sql->execute([$email]);

        return $sql->fetch() ?: null;
    }

    public static function buscarPorId(int $id): ?array
    {
        $sql = Database::conexao()->prepare('SELECT * FROM usuario WHERE id = ?');
        $sql->execute([$id]);

        return $sql->fetch() ?: null;
    }

    /**
     * Cria o usuario e devolve o id. A senha vai para o banco em hash.
     * O e-mail nasce nao confirmado, com um token gerado por gerarToken().
     */
    public static function criar(string $nome, string $email, string $senha, string $token): int
    {
        $pdo = Database::conexao();

        $sql = $pdo->prepare(
            'INSERT INTO usuario (nome, email, senha_hash, token_confirmacao, token_expira_em)
             VALUES (?, ?, ?, ?, ?)'
        );
        $sql->execute([
            $nome,
            $email,
            password_hash($senha, PASSWORD_DEFAULT),
            $token,
            self::validadeDoToken(),
        ]);

        return (int) $pdo->lastInsertId();
    }

    /** Token aleatorio de 64 caracteres, impossivel de adivinhar. */
    public static function gerarToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Busca pelo token de confirmacao. Devolve null se o token nao existir;
     * quem chama precisa conferir tambem se ja expirou (ver tokenExpirou()).
     */
    public static function buscarPorToken(string $token): ?array
    {
        $sql = Database::conexao()->prepare(
            'SELECT * FROM usuario WHERE token_confirmacao = ?'
        );
        $sql->execute([$token]);

        return $sql->fetch() ?: null;
    }

    public static function tokenExpirou(array $usuario): bool
    {
        return $usuario['token_expira_em'] !== null
            && strtotime($usuario['token_expira_em']) < time();
    }

    /** Marca o e-mail como confirmado e descarta o token, que nao serve mais. */
    public static function confirmar(int $id): void
    {
        $sql = Database::conexao()->prepare(
            'UPDATE usuario
                SET email_confirmado = 1, token_confirmacao = NULL, token_expira_em = NULL
              WHERE id = ?'
        );
        $sql->execute([$id]);
    }

    /** Troca o token por um novo (usado no "reenviar e-mail") e devolve ele. */
    public static function renovarToken(int $id): string
    {
        $token = self::gerarToken();

        $sql = Database::conexao()->prepare(
            'UPDATE usuario SET token_confirmacao = ?, token_expira_em = ? WHERE id = ?'
        );
        $sql->execute([$token, self::validadeDoToken(), $id]);

        return $token;
    }

    /** Data/hora em que um token gerado agora deixa de valer. */
    private static function validadeDoToken(): string
    {
        $horas = Config::get('email')['horasValidade'] ?? 24;

        return date('Y-m-d H:i:s', time() + $horas * 3600);
    }
}
