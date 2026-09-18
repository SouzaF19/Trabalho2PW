<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Consultas na tabela elemento (tracos, textos e imagens de uma pagina).
 * A coluna `dados` guarda um JSON com o que muda de acordo com o tipo.
 */
final class Elemento
{
    public const TIPOS = ['traco', 'texto', 'imagem'];

    /** Elementos da pagina no formato do docs/api.md, do fundo para o topo. */
    public static function listar(int $paginaId): array
    {
        $sql = Database::conexao()->prepare(
            'SELECT id, tipo, x, y, largura, altura, z_index, dados
               FROM elemento WHERE pagina_id = ? ORDER BY z_index, id'
        );
        $sql->execute([$paginaId]);

        $elementos = [];
        foreach ($sql->fetchAll() as $linha) {
            $linha['id']      = (int) $linha['id'];
            $linha['x']       = (float) $linha['x'];
            $linha['y']       = (float) $linha['y'];
            $linha['z_index'] = (int) $linha['z_index'];
            // o banco devolve o JSON como texto; aqui vira array de novo
            $linha['dados']   = json_decode($linha['dados'], true);

            $elementos[] = $linha;
        }

        return $elementos;
    }

    /**
     * Apaga todos os elementos da pagina e grava os novos.
     * Fica numa transacao: ou salva tudo, ou nao muda nada.
     */
    public static function substituir(int $paginaId, array $elementos): void
    {
        $pdo = Database::conexao();

        $pdo->beginTransaction();

        $pdo->prepare('DELETE FROM elemento WHERE pagina_id = ?')->execute([$paginaId]);

        $sql = $pdo->prepare(
            'INSERT INTO elemento (pagina_id, tipo, x, y, largura, altura, z_index, dados)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        foreach ($elementos as $i => $e) {
            $sql->execute([
                $paginaId,
                $e['tipo'],
                $e['x'] ?? 0,
                $e['y'] ?? 0,
                $e['largura'] ?? null,
                $e['altura'] ?? null,
                $e['z_index'] ?? $i,
                json_encode($e['dados']),
            ]);
        }

        $pdo->commit();
    }
}
