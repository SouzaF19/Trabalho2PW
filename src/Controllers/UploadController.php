<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;

/**
 * Recebe as imagens que o usuario anexa na pagina (docs/api.md, secao "Upload").
 *
 * Diferente do resto da API, aqui o corpo nao e JSON: o navegador manda
 * multipart/form-data, entao o arquivo chega em $_FILES e nao em php://input.
 */
final class UploadController
{
    /** Extensao usada para cada tipo aceito. O nome do arquivo e sempre nosso. */
    private const EXTENSOES = [
        'image/png'  => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
    ];

    /** POST /api/upload */
    public function receber(Request $req, array $params): void
    {
        if (usuarioLogado() === null) {
            Response::erro('Voce precisa estar logado.', 401);
            return;
        }

        $arquivo = $_FILES['arquivo'] ?? null;

        // Se o arquivo passar do post_max_size do PHP, o $_FILES chega vazio.
        if ($arquivo === null) {
            Response::erro('Nenhum arquivo recebido (ou arquivo grande demais).');
            return;
        }

        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            Response::erro($this->mensagemDoErro((int) $arquivo['error']));
            return;
        }

        $cfg = Config::get('upload');

        if ($arquivo['size'] > $cfg['tamanhoMaximo']) {
            $limite = round($cfg['tamanhoMaximo'] / 1024 / 1024, 1);
            Response::erro("Imagem grande demais (maximo {$limite} MB).");
            return;
        }

        // Olhamos o conteudo do arquivo para descobrir o tipo. O $arquivo['type']
        // vem do navegador e pode ser mentira: um .php renomeado para .png
        // chegaria aqui dizendo que e imagem.
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $tipo  = (string) $finfo->file($arquivo['tmp_name']);

        if (!in_array($tipo, $cfg['tiposPermitidos'], true) || !isset(self::EXTENSOES[$tipo])) {
            Response::erro('Tipo de arquivo nao permitido. Use PNG, JPEG ou WebP.');
            return;
        }

        // O nome vem de random_bytes, nunca do cliente: assim ninguem escolhe
        // onde o arquivo cai nem sobrescreve o que ja existe.
        $nome    = bin2hex(random_bytes(16)) . '.' . self::EXTENSOES[$tipo];
        $destino = BASE_PATH . '/public/uploads/' . $nome;

        if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
            Response::erro('Nao foi possivel salvar a imagem.', 500);
            return;
        }

        Response::json(['url' => '/uploads/' . $nome]);
    }

    /** Traduz o codigo de erro do PHP numa mensagem que o usuario entende. */
    private function mensagemDoErro(int $codigo): string
    {
        return match ($codigo) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Imagem grande demais.',
            UPLOAD_ERR_PARTIAL                        => 'O envio foi interrompido. Tente de novo.',
            UPLOAD_ERR_NO_FILE                        => 'Nenhum arquivo foi escolhido.',
            default                                   => 'Falha ao enviar a imagem.',
        };
    }
}
