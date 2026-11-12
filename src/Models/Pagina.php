<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Consultas na tabela pagina. */
final class Pagina
{
    public static function listar(int $cadernoId): array
    {
        $sql = Database::conexao()->prepare(
            'SELECT id, ordem FROM pagina WHERE caderno_id = ? ORDER BY ordem'
        );
        $sql->execute([$cadernoId]);

        return $sql->fetchAll();
    }

    /** Cria a proxima pagina do caderno e devolve {id, ordem}. */
    public static function criar(int $cadernoId): array
    {
        $pdo = Database::conexao();

        // ordem = maior ordem que ja existe + 1
        $sql = $pdo->prepare('SELECT COALESCE(MAX(ordem), 0) + 1 FROM pagina WHERE caderno_id = ?');
        $sql->execute([$cadernoId]);
        $ordem = (int) $sql->fetchColumn();

        $sql = $pdo->prepare('INSERT INTO pagina (caderno_id, ordem) VALUES (?, ?)');
        $sql->execute([$cadernoId, $ordem]);

        return ['id' => (int) $pdo->lastInsertId(), 'ordem' => $ordem];
    }

    public static function contar(int $cadernoId): int
    {
        $sql = Database::conexao()->prepare('SELECT COUNT(*) FROM pagina WHERE caderno_id = ?');
        $sql->execute([$cadernoId]);

        return (int) $sql->fetchColumn();
    }

    /**
     * Apaga a pagina e renumera as que sobraram, para a ordem continuar
     * 1, 2, 3... sem buraco. Os elementos somem junto (ON DELETE CASCADE).
     *
     * Vai tudo numa transacao: apagar sem renumerar deixaria o caderno
     * com "Pagina 1, Pagina 3".
     */
    public static function excluir(int $id, int $cadernoId): void
    {
        $pdo = Database::conexao();

        $pdo->beginTransaction();

        $pdo->prepare('DELETE FROM pagina WHERE id = ?')->execute([$id]);

        $sql = $pdo->prepare(
            'SELECT id FROM pagina WHERE caderno_id = ? ORDER BY ordem'
        );
        $sql->execute([$cadernoId]);

        $renumerar = $pdo->prepare('UPDATE pagina SET ordem = ? WHERE id = ?');
        foreach ($sql->fetchAll() as $posicao => $pagina) {
            $renumerar->execute([$posicao + 1, $pagina['id']]);
        }

        $pdo->commit();
    }

    /**
     * Busca a pagina junto com o tipo_folha e o dono do caderno
     * (o usuario_id serve para conferir se a pagina e de quem esta logado).
     */
    public static function buscar(int $id): ?array
    {
        $sql = Database::conexao()->prepare(
            'SELECT p.id, p.ordem, p.caderno_id, c.tipo_folha, c.usuario_id
               FROM pagina p
               JOIN caderno c ON c.id = p.caderno_id
              WHERE p.id = ?'
        );
        $sql->execute([$id]);

        return $sql->fetch() ?: null;
    }
}
