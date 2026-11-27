<form class="dropzone" method="post" action="<?= e(url(['p' => $dzAcao])) ?>" enctype="multipart/form-data">
    <?= csrf_campo() ?>
    <?php foreach ($dzHidden as $dzNome => $dzValor): ?>
        <input type="hidden" name="<?= e($dzNome) ?>" value="<?= e($dzValor) ?>">
    <?php endforeach; ?>
    <input type="file" name="<?= e($dzCampo ?? 'arquivos') ?>[]" multiple>
    <p><strong><?= e($dzTitulo) ?></strong><br><span><?= e($dzAjuda) ?></span></p>
</form>
