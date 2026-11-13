<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Usuario;

/** Cadastro, login e logout (docs/api.md, secao "Autenticacao"). */
final class AuthController
{
    /** POST /api/cadastro */
    public function cadastrar(Request $req, array $params): void
    {
        $corpo = $req->corpoJson();

        $nome  = trim($corpo['nome']  ?? '');
        $email = trim($corpo['email'] ?? '');
        $senha = $corpo['senha'] ?? '';

        if ($nome === '' || $email === '' || $senha === '') {
            Response::erro('Preencha nome, e-mail e senha.');
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::erro('E-mail invalido.');
            return;
        }
        if (strlen($senha) < 4) {
            Response::erro('A senha precisa ter pelo menos 4 caracteres.');
            return;
        }
        if (Usuario::buscarPorEmail($email) !== null) {
            Response::erro('Este e-mail ja esta cadastrado.', 409);
            return;
        }

        $token = Usuario::gerarToken();
        $id    = Usuario::criar($nome, $email, $senha, $token);

        try {
            $link = ConfirmacaoController::enviarLink($nome, $email, $token);
        } catch (\Throwable $e) {
            Response::json([
                'ok' => false,
                'conta_criada' => true,
                'email_enviado' => false,
                'erro' => 'Conta criada, mas nao foi possivel enviar o e-mail de confirmacao. ' .
                    'Nao se cadastre novamente: na tela de login, preencha seu e-mail e senha e use Reenviar e-mail antes de Entrar.',
            ], 503);
            return;
        }

        Response::json([
            'ok'      => true,
            'conta_criada' => true,
            'email_enviado' => true,
            'usuario' => ['id' => $id, 'nome' => $nome],
        ] + ConfirmacaoController::linkParaDemonstracao($link), 201);
    }

    /** POST /api/login */
    public function entrar(Request $req, array $params): void
    {
        $corpo = $req->corpoJson();

        $usuario = Usuario::buscarPorEmail(trim($corpo['email'] ?? ''));

        if ($usuario === null || !password_verify($corpo['senha'] ?? '', $usuario['senha_hash'])) {
            Response::erro('E-mail ou senha incorretos.', 401);
            return;
        }

        if ((int) $usuario['email_confirmado'] !== 1) {
            Response::json([
                'ok' => false,
                'codigo' => 'email_nao_confirmado',
                'erro' => 'Verifique seu e-mail para validar sua conta antes de entrar. Se necessario, use Reenviar e-mail com seu e-mail e senha.',
            ], 403);
            return;
        }

        entrar((int) $usuario['id'], $usuario['nome'], true);

        Response::json([
            'ok'      => true,
            'usuario' => [
                'id'               => (int) $usuario['id'],
                'nome'             => $usuario['nome'],
                'email_confirmado' => (int) $usuario['email_confirmado'] === 1,
            ],
        ]);
    }

    /** POST /api/logout */
    public function sair(Request $req, array $params): void
    {
        sair();
        Response::json(['ok' => true]);
    }
}
