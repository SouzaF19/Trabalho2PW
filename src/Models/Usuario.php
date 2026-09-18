<?php
declare(strict_types=1);

namespace App\Models;

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

    /** Cria o usuario e devolve o id. A senha vai para o banco em hash. */
    public static function criar(string $nome, string $email, string $senha): int
    {
        $pdo = Database::conexao();

        $sql = $pdo->prepare('INSERT INTO usuario (nome, email, senha_hash) VALUES (?, ?, ?)');
        $sql->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT)]);

        return (int) $pdo->lastInsertId();
    }
}
