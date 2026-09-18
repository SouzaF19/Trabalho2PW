<?php /* Inicio de toda tela. Espera $titulo e $classeCorpo. */ ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= escapar($titulo) ?> · AnotaAqui</title>

    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600&family=Caveat:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= urlBase() ?>/assets/css/app.css">

    <!-- o JS usa isso para montar as URLs da API -->
    <script>var BASE = "<?= urlBase() ?>";</script>
</head>
<body class="<?= $classeCorpo ?>">
