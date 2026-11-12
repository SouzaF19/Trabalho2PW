<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Email;
use App\Core\Request;
use App\Core\Response;
use App\Models\Usuario;

/**
 * Confirmacao do e-mail do usuario.
 *
 * No cadastro o sistema gera um token e manda um link. Abrir esse link
 * marca o e-mail como confirmado.
 */
final class ConfirmacaoController
{
    /** GET /confirmar/{token} - a tela que o usuario abre pelo link do e-mail. */
    public function confirmar(Request $req, array $params): void
    {
        $usuario = Usuario::buscarPorToken((string) $params['token']);

        if ($usuario === null) {
            renderizar('confirma_email', [
                'titulo' => 'Link inválido',
                'ok'     => false,
                'motivo' => 'Este link não existe ou já foi usado.',
            ]);
            return;
        }

        if (Usuario::tokenExpirou($usuario)) {
            renderizar('confirma_email', [
                'titulo' => 'Link expirado',
                'ok'     => false,
                'motivo' => 'Este link passou da validade. Entre na sua conta e peça um novo.',
            ]);
            return;
        }

        Usuario::confirmar((int) $usuario['id']);

        // Se for o proprio usuario logado, a sessao precisa saber que agora
        // esta confirmado - senao o aviso continuaria aparecendo.
        if (usuarioLogado() === (int) $usuario['id']) {
            $_SESSION['usuario_confirmado'] = true;
        }

        renderizar('confirma_email', [
            'titulo' => 'E-mail confirmado',
            'ok'     => true,
            'nome'   => $usuario['nome'],
        ]);
    }

    /** POST /api/reenviar-confirmacao - gera um link novo para quem esta logado. */
    public function reenviar(Request $req, array $params): void
    {
        $id = usuarioLogado();

        if ($id === null) {
            Response::erro('Voce precisa estar logado.', 401);
            return;
        }

        $usuario = Usuario::buscarPorId($id);

        if ($usuario === null || (int) $usuario['email_confirmado'] === 1) {
            Response::erro('Este e-mail ja esta confirmado.');
            return;
        }

        $token = Usuario::renovarToken($id);

        try {
            $link = self::enviarLink($usuario['nome'], $usuario['email'], $token);
        } catch (\Throwable $e) {
            error_log('Falha ao reenviar o e-mail de confirmacao: ' . $e->getMessage());
            Response::erro('Nao conseguimos enviar o e-mail agora. Tente de novo em instantes.', 500);
            return;
        }

        Response::json(['ok' => true] + self::linkParaDemonstracao($link));
    }

    /**
     * Monta a mensagem e entrega para a classe Email.
     * Devolve o link gerado, que o cadastro tambem usa.
     */
    public static function enviarLink(string $nome, string $email, string $token): string
    {
        $link  = self::urlCompleta('/confirmar/' . $token);
        $horas = Config::get('email')['horasValidade'] ?? 24;

        $corpo = <<<TEXTO
            Ola, {$nome}!

            Para confirmar seu e-mail no AnotaAqui, abra o endereco abaixo:

            {$link}

            O link vale por {$horas} horas.
            Se nao foi voce quem se cadastrou, pode ignorar esta mensagem.
            TEXTO;

        Email::enviar($email, 'Confirme seu e-mail no AnotaAqui', $corpo);

        return $link;
    }

    /**
     * No modo 'log' nenhum e-mail sai de verdade, entao devolvemos o link
     * na resposta: sem isso ninguem conseguiria confirmar a conta, porque
     * a mensagem so existe dentro de storage/emails.log.
     *
     * O que decide e o modo, nao o 'debug': quando o modo for 'smtp' o
     * e-mail chega de verdade, e ai o link nunca aparece na resposta.
     */
    public static function linkParaDemonstracao(string $link): array
    {
        return Config::get('email')['modo'] === 'log'
            ? ['link_confirmacao' => $link]
            : [];
    }

    /** Transforma um caminho da aplicacao numa URL que da para colar no navegador. */
    private static function urlCompleta(string $caminho): string
    {
        $protocolo = (($_SERVER['HTTPS'] ?? '') === 'on') ? 'https' : 'http';
        $host      = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $protocolo . '://' . $host . urlBase() . $caminho;
    }
}
