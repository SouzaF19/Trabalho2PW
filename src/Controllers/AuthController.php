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

        $id = Usuario::criar($nome, $email, $senha);

        // ja deixa logado
        entrar($id, $nome);

        Response::json(['ok' => true, 'usuario' => ['id' => $id, 'nome' => $nome]], 201);
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

        entrar((int) $usuario['id'], $usuario['nome']);

        Response::json([
            'ok'      => true,
            'usuario' => ['id' => (int) $usuario['id'], 'nome' => $usuario['nome']],
        ]);
    }

    /** POST /api/logout */
    public function sair(Request $req, array $params): void
    {
        sair();
        Response::json(['ok' => true]);
    }
}
