<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Envio de e-mail da aplicacao.
 *
 * Tem dois modos, escolhidos em config/config.php:
 *
 *   'log'  - nao envia nada; escreve a mensagem em storage/emails.log.
 *            E o modo padrao: o sistema funciona de ponta a ponta sem
 *            depender de servidor de e-mail, o que serve para desenvolver
 *            e para demonstrar.
 *   'smtp' - envia de verdade, por um servidor SMTP. Depende do PHPMailer.
 */
final class Email
{
    public static function enviar(string $para, string $assunto, string $corpo): void
    {
        $cfg = Config::get('email');

        match ($cfg['modo']) {
            'log'   => self::gravarNoLog($para, $assunto, $corpo, $cfg),
            'smtp'  => self::enviarPorSmtp($para, $assunto, $corpo, $cfg),
            default => throw new RuntimeException(
                "Modo de e-mail desconhecido: '{$cfg['modo']}'. Use 'log' ou 'smtp'."
            ),
        };
    }

    /**
     * Guarda a mensagem num arquivo, em vez de enviar.
     *
     * storage/ fica FORA de public/, entao o arquivo nao e acessivel
     * pelo navegador - ele tem links de confirmacao dentro.
     */
    private static function gravarNoLog(string $para, string $assunto, string $corpo, array $cfg): void
    {
        $pasta = BASE_PATH . '/storage';

        if (!is_dir($pasta) && !mkdir($pasta, 0775, true) && !is_dir($pasta)) {
            throw new RuntimeException('Nao foi possivel criar a pasta storage/.');
        }

        $mensagem = sprintf(
            "=== %s ===\nDe:      %s <%s>\nPara:    %s\nAssunto: %s\n\n%s\n\n",
            date('d/m/Y H:i:s'),
            $cfg['nomeRemetente'],
            $cfg['remetente'],
            $para,
            $assunto,
            $corpo
        );

        if (file_put_contents($pasta . '/emails.log', $mensagem, FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException('Nao foi possivel escrever em storage/emails.log.');
        }
    }

    private static function enviarPorSmtp(string $para, string $assunto, string $corpo, array $cfg): void
    {
        // O PHPMailer ainda nao foi adicionado ao projeto. Enquanto isso,
        // falhar com uma mensagem clara e melhor do que fingir que enviou.
        throw new RuntimeException(
            'Envio por SMTP ainda nao esta disponivel: falta instalar o PHPMailer. ' .
            "Deixe 'modo' => 'log' em config/config.php."
        );
    }
}
