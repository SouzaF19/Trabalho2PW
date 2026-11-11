<?php
/**
 * Tela aberta pelo link de confirmacao do e-mail.
 *
 * Espera:
 *   $ok      bool    deu certo ou nao
 *   $nome    string  so quando $ok
 *   $motivo  string  so quando !$ok
 */
$classeCorpo = 'tela-confirmacao';
require __DIR__ . '/partials/cabecalho.php';
?>
<main class="confirmacao">
    <div class="cartao-confirmacao">
        <?php if ($ok): ?>
            <h1>Email verificado com sucesso!</h1>

            <!-- selo de verificado, como no prototipo -->
            <svg class="selo" viewBox="0 0 120 120" width="150" height="150" aria-hidden="true">
                <path fill="#3BA3DC" d="M60 4l10 11 15-5 5 15 15 5-5 15 11 10-11 10 5 15-15 5-5 15-15-5-10 11-10-11-15 5-5-15-15-5 5-15L4 60l11-10-5-15 15-5 5-15 15 5z"/>
                <path fill="none" stroke="#fff" stroke-width="11" stroke-linecap="round" stroke-linejoin="round" d="M38 61l15 15 29-31"/>
            </svg>

            <p>Você já pode entrar no AnotaAqui e criar seus cadernos!</p>
        <?php else: ?>
            <h1>Não deu para confirmar</h1>
            <p><?= escapar($motivo) ?></p>
        <?php endif; ?>

        <a class="btn-confirmacao" href="<?= urlBase() ?>/login">Ir para Login</a>
    </div>
</main>
<?php
$scripts = [];
require __DIR__ . '/partials/rodape.php';
