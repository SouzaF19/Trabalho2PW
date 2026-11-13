<?php
declare(strict_types=1);

/**
 * Funcoes de apoio usadas pelos controllers e pelas views.
 * (sessao, redirecionamento, telas HTML)
 */

// --- Sessao / login -------------------------------------------------------

/** Guarda apenas um usuario confirmado na sessao depois do login. */
function entrar(int $usuarioId, string $nome, bool $emailConfirmado = true): void
{
    $_SESSION['usuario_id']         = $usuarioId;
    $_SESSION['usuario_nome']       = $nome;
    $_SESSION['usuario_confirmado'] = $emailConfirmado;
}

/** false enquanto o usuario nao abriu o link de confirmacao do e-mail. */
function usuarioConfirmado(): bool
{
    return usuarioLogado() !== null;
}

function sair(): void
{
    session_destroy();
}

/** Id do usuario logado, ou null se ninguem estiver logado. */
function usuarioLogado(): ?int
{
    if (!isset($_SESSION['usuario_id'])) {
        return null;
    }

    // Revalida tambem sessoes criadas antes do bloqueio de confirmacao.
    $usuario = \App\Models\Usuario::buscarPorId((int) $_SESSION['usuario_id']);
    if ($usuario === null || (int) $usuario['email_confirmado'] !== 1) {
        unset($_SESSION['usuario_id'], $_SESSION['usuario_nome'], $_SESSION['usuario_confirmado']);
        return null;
    }

    $_SESSION['usuario_confirmado'] = true;
    return (int) $usuario['id'];
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
