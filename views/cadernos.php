<?php
$classeCorpo = 'tela-cadernos';
require __DIR__ . '/partials/cabecalho.php';
require __DIR__ . '/partials/topo.php';
?>
<main class="cadernos">
    <!-- Barra de acoes: criar um caderno novo -->
    <form class="novo-caderno" id="form-novo-caderno" novalidate>
        <input type="text" name="titulo" placeholder="Nome do novo caderno" maxlength="150" required>
        <select name="tipo_folha" title="Tipo de folha">
            <option value="pautada">Pautada</option>
            <option value="lisa">Lisa</option>
            <option value="quadriculada">Quadriculada</option>
        </select>
        <button class="btn-principal btn-pequeno" type="submit">+ Criar</button>
    </form>

    <p class="mensagem-erro" id="erro" hidden></p>

    <!-- Painel roxo com a grade de cadernos. O JS preenche a partir de GET /api/cadernos. -->
    <section class="painel-cadernos">
        <ul class="grade-cadernos" id="lista-cadernos"></ul>
        <p class="painel-vazio" id="painel-vazio" hidden>
            Você ainda não tem cadernos. Crie o primeiro acima!
        </p>
    </section>
</main>

<!-- Molde de um card; o JS clona para cada caderno -->
<template id="tpl-caderno">
    <li class="caderno">
        <a class="caderno-capa" href="#">
            <div class="miniatura"></div>
            <div class="caderno-nome"></div>
        </a>

        <!-- Aparece no lugar do nome quando se clica no lapis -->
        <form class="caderno-edicao" hidden>
            <input type="text" name="titulo" maxlength="150" required aria-label="Nome do caderno">
            <select name="tipo_folha" aria-label="Tipo de folha">
                <option value="pautada">Pautada</option>
                <option value="lisa">Lisa</option>
                <option value="quadriculada">Quadriculada</option>
            </select>
            <div class="caderno-edicao-acoes">
                <button type="submit">Salvar</button>
                <button type="button" class="cancelar">Cancelar</button>
            </div>
        </form>

        <button type="button" class="caderno-editar" title="Renomear ou trocar a folha">&#9998;</button>
        <button type="button" class="caderno-excluir" title="Excluir caderno">&times;</button>
    </li>
</template>
<?php
$scripts = ['cadernos.js'];
require __DIR__ . '/partials/rodape.php';
