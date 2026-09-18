<?php /* Barra de cima das telas logadas: logo, nome do usuario e botao de sair. */ ?>
<header class="topo">
    <a class="logo" href="<?= urlBase() ?>/cadernos">
        <span class="logo-anota">Anota</span><span class="logo-aqui">Aqui</span>
    </a>

    <div class="topo-usuario">
        <span><?= escapar(nomeUsuarioLogado()) ?></span>
        <button type="button" class="btn-icone" id="btn-sair" title="Sair da conta">
            <svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.6">
                <circle cx="12" cy="12" r="10.5"/>
                <circle cx="12" cy="10" r="3.5"/>
                <path d="M5.5 19c1.5-3 4-4.5 6.5-4.5s5 1.5 6.5 4.5"/>
            </svg>
        </button>
    </div>
</header>
