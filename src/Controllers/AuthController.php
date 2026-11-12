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

        // A conta ja existe neste ponto. Se o envio falhar (servidor de
        // e-mail fora do ar, por exemplo), o cadastro nao pode virar erro:
        // o usuario ficaria com conta criada e a impressao de que nao deu
        // certo. Ele entra e reenvia o e-mail pelo aviso na tela.
        $extras = [];

        try {
            $link   = ConfirmacaoController::enviarLink($nome, $email, $token);
            $extras = ConfirmacaoController::linkParaDemonstracao($link);
        } catch (\Throwable $e) {
            error_log('Falha ao enviar o e-mail de confirmacao: ' . $e->getMessage());
            $extras = ['aviso' => 'Sua conta foi criada, mas nao conseguimos enviar o e-mail de confirmacao. Use "Reenviar e-mail" mais tarde.'];
        }

        // Ja deixa logado. O e-mail ainda nao esta confirmado: as telas
        // mostram um aviso ate o usuario abrir o link.
        entrar($id, $nome, false);

        Response::json([
            'ok'      => true,
            'usuario' => ['id' => $id, 'nome' => $nome],
        ] + $extras, 201);
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

        // Quem ainda nao confirmou o e-mail entra assim mesmo, mas ve um
        // aviso nas telas. Bloquear o login deixaria a pessoa de fora caso
        // o e-mail demorasse ou caisse no spam.
        entrar((int) $usuario['id'], $usuario['nome'], (int) $usuario['email_confirmado'] === 1);

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
