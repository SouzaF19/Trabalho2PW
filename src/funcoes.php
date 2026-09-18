<?php
declare(strict_types=1);

/**
 * Funcoes de apoio usadas pelos controllers e pelas views.
 * (sessao, redirecionamento, telas HTML)
 */

// --- Sessao / login -------------------------------------------------------

/** Guarda o usuario na sessao depois do login ou cadastro. */
function entrar(int $usuarioId, string $nome): void
{
    $_SESSION['usuario_id']   = $usuarioId;
    $_SESSION['usuario_nome'] = $nome;
}

function sair(): void
{
    session_destroy();
}

/** Id do usuario logado, ou null se ninguem estiver logado. */
function usuarioLogado(): ?int
{
    return isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;
}

function nomeUsuarioLogado(): string
{
    return $_SESSION['usuario_nome'] ?? '';
}

// --- Telas ----------------------------------------------------------------

/**
 * Caminho onde a aplicacao esta montada.
 * No php -S e '' ; no Apache pode ser '/cadernos/public'.
 */
function urlBase(): string
{
    $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $base = rtrim($base, '/');

    return $base === '.' ? '' : $base;
}

function redirecionar(string $caminho): void
{
    header('Location: ' . urlBase() . $caminho);
}

/** Mostra um arquivo da pasta views/. As chaves de $dados viram variaveis. */
function renderizar(string $tela, array $dados = []): void
{
    extract($dados);
    require BASE_PATH . "/views/$tela.php";
}

/** Escapa texto antes de colocar no HTML (evita XSS). */
function escapar(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}
