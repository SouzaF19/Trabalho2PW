<?php /* Fim de toda tela. Espera $scripts (lista de arquivos de assets/js). */ ?>
    <script src="<?= urlBase() ?>/assets/js/app.js"></script>
<?php foreach ($scripts as $script): ?>
    <script src="<?= urlBase() ?>/assets/js/<?= $script ?>"></script>
<?php endforeach; ?>
</body>
</html>
