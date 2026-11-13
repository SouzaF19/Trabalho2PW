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
 *            Opcional para desenvolvimento e demonstracao; nao envia e-mail.
 *   'smtp' - padrao: envia de verdade com PHPMailer incluido em lib/PHPMailer.
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
        $smtp = $cfg['smtp'] ?? [];
        foreach (['remetente' => $cfg['remetente'] ?? '', 'usuario' => $smtp['usuario'] ?? '', 'senha' => $smtp['senha'] ?? ''] as $campo => $valor) {
            if (trim((string) $valor) === '') {
                throw new RuntimeException("Configure email: {$campo} em config/config.php. Para Gmail, use uma senha de app.");
            }
        }
        if (!in_array($smtp['seguranca'] ?? '', ['tls', 'ssl'], true)) {
            throw new RuntimeException("Configure email.smtp.seguranca como 'tls' ou 'ssl' em config/config.php.");
        }
        if (filter_var($smtp['porta'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]) === false) {
            throw new RuntimeException('Configure email.smtp.porta entre 1 e 65535 em config/config.php.');
        }
        if (!filter_var($smtp['host'] ?? '', FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw new RuntimeException('Configure email.smtp.host com um hostname valido em config/config.php.');
        }
        $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mailer->isSMTP();
        $mailer->Host = $smtp['host'];
        $mailer->Port = (int) $smtp['porta'];
        $mailer->SMTPAuth = true;
        $mailer->Username = $smtp['usuario'];
        $mailer->Password = $smtp['senha'];
        $mailer->SMTPSecure = $smtp['seguranca'];
        $mailer->SMTPOptions = ['ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
        ]];
        $mailer->Timeout = 10;
        $mailer->Timelimit = 15;
        $mailer->SMTPDebug = 0;
        $mailer->CharSet = 'UTF-8';
        $mailer->setFrom($cfg['remetente'], $cfg['nomeRemetente']);
        $mailer->addAddress($para);
        $mailer->Subject = $assunto;
        $mailer->Body = $corpo;
        $mailer->isHTML(false);
        $mailer->send();

    }
}
