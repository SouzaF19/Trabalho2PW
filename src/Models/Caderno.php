<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Consultas na tabela caderno.
 * Todas filtram pelo usuario_id: cada um so ve os proprios cadernos.
 */
final class Caderno
{
    public static function listar(int $usuarioId): array
    {
        $sql = Database::conexao()->prepare(
            'SELECT id, titulo, tipo_folha, criado_em FROM caderno
              WHERE usuario_id = ? ORDER BY id DESC'
        );
        $sql->execute([$usuarioId]);

        return $sql->fetchAll();
    }

    public static function buscar(int $id, int $usuarioId): ?array
    {
        $sql = Database::conexao()->prepare(
            'SELECT id, titulo, tipo_folha, criado_em FROM caderno
              WHERE id = ? AND usuario_id = ?'
        );
        $sql->execute([$id, $usuarioId]);

        return $sql->fetch() ?: null;
    }

    /** Cria o caderno ja com a primeira pagina em branco. Devolve o caderno. */
    public static function criar(int $usuarioId, string $titulo, string $tipoFolha): array
    {
        $pdo = Database::conexao();

        $sql = $pdo->prepare('INSERT INTO caderno (usuario_id, titulo, tipo_folha) VALUES (?, ?, ?)');
        $sql->execute([$usuarioId, $titulo, $tipoFolha]);
        $id = (int) $pdo->lastInsertId();

        Pagina::criar($id);

        return self::buscar($id, $usuarioId);
    }

    /** Devolve true se apagou. Paginas e elementos vao junto (ON DELETE CASCADE). */
    public static function excluir(int $id, int $usuarioId): bool
    {
        $sql = Database::conexao()->prepare('DELETE FROM caderno WHERE id = ? AND usuario_id = ?');
        $sql->execute([$id, $usuarioId]);

        return $sql->rowCount() > 0;
    }
}
