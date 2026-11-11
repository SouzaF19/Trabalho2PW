<?php /* Inicio de toda tela. Espera $titulo e $classeCorpo. */ ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= escapar($titulo) ?> · AnotaAqui</title>

    <!-- Fontes e estilo saem do proprio servidor: a aplicacao abre sem internet -->
    <link rel="stylesheet" href="<?= urlBase() ?>/assets/css/fontes.css">
    <link rel="stylesheet" href="<?= urlBase() ?>/assets/css/app.css">

    <!-- o JS usa isso para montar as URLs da API -->
    <script>var BASE = "<?= urlBase() ?>";</script>
</head>
<body class="<?= $classeCorpo ?>">
