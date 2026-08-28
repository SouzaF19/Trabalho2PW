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
    // Deixe false quando for apresentar/entregar.
    'debug' => true,

    'upload' => [
        'tamanhoMaximo' => 2 * 1024 * 1024,   // 2 MB
        'tiposPermitidos' => ['image/png', 'image/jpeg', 'image/webp'],
    ],
];
