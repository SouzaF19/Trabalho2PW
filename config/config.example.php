<?php
/**
 * Copie este arquivo para config/config.php e ajuste os valores.
 * config/config.php esta no .gitignore e NUNCA vai para o repositorio.
 */
return [
    'banco' => [
        'host'  => '127.0.0.1',
        'porta' => 3306,
        'nome'  => 'cadernos',
        'usuario' => 'root',
        'senha'   => '',        // XAMPP vem com root sem senha por padrao
    ],

    // true = mostra a mensagem real do erro nas respostas da API.
    // Deixe false para apresentar/entregar; coloque true so enquanto programa.
    'debug' => false,

    'upload' => [
        'tamanhoMaximo' => 2 * 1024 * 1024,   // 2 MB
        'tiposPermitidos' => ['image/png', 'image/jpeg', 'image/webp'],
    ],

    'email' => [
        // 'log'  = nao envia nada; grava a mensagem em storage/emails.log.
        //          O sistema inteiro funciona assim, da para demonstrar sem SMTP.
        // 'smtp' = envia de verdade (depende do PHPMailer, ainda nao instalado).
        'modo' => 'log',

        'remetente'     => 'nao-responda@anotaaqui.local',
        'nomeRemetente' => 'AnotaAqui',

        // Horas que o link de confirmacao continua valendo
        'horasValidade' => 24,

        // So usado quando 'modo' for 'smtp'. A senha fica AQUI no config.php,
        // que esta no .gitignore - nunca no config.example.php.
        'smtp' => [
            'host'      => 'smtp.exemplo.com',
            'porta'     => 587,
            'usuario'   => '',
            'senha'     => '',
            'seguranca' => 'tls',   // 'tls' ou 'ssl'
        ],
    ],
];
