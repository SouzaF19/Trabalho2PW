<?php
$classeCorpo = 'tela-auth';
require __DIR__ . '/partials/cabecalho.php';
?>
<main class="auth">
    <form class="cartao-auth" id="form-cadastro" novalidate>
        <h1>Cadastro</h1>

        <label class="campo">
            <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.6">
                <circle cx="12" cy="12" r="10.5"/>
                <circle cx="12" cy="10" r="3.5"/>
                <path d="M5.5 19c1.5-3 4-4.5 6.5-4.5s5 1.5 6.5 4.5"/>
            </svg>
            <input type="text" name="nome" placeholder="Seu nome" autocomplete="name" maxlength="100" required>
        </label>

        <label class="campo">
            <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.6">
                <rect x="2" y="5" width="20" height="14" rx="1"/>
                <path d="M2 6l10 7 10-7M2 18l7-6M22 18l-7-6"/>
            </svg>
            <input type="email" name="email" placeholder="seuemail@gmail.com" autocomplete="email" maxlength="150" required>
        </label>

        <label class="campo">
            <svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor">
                <circle cx="12" cy="12" r="11"/>
                <path d="M9 11V9a3 3 0 0 1 6 0v2h1v6H8v-6h1zm1.5 0h3V9a1.5 1.5 0 0 0-3 0v2z" fill="#fff"/>
            </svg>
            <input type="password" name="senha" placeholder="Senha (mínimo 4 caracteres)" autocomplete="new-password" minlength="4" required>
        </label>

        <p class="mensagem-erro" id="erro" hidden></p>

        <a class="link-auth" href="<?= urlBase() . '/login' ?>">Já tenho conta</a>

        <button class="btn-principal" type="submit">Cadastrar</button>
    </form>
</main>
<?php
$scripts = ['auth.js'];
require __DIR__ . '/partials/rodape.php';
