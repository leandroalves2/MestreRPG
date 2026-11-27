<?php if (!empty($anexos)): ?>
    <ul class="anexos">
        <?php foreach ($anexos as $a):
            $ext = Anexos::extensao($a['arq']);
            $link = url(['p' => 'arq', 'f' => $a['arq'], 'n' => $a['nome']]);
        ?>
            <li>
                <div class="anexo-linha">
                    <span class="anexo-icone"><?= Anexos::icone($ext) ?></span>
                    <a href="<?= e($link) ?>" target="_blank"><?= e($a['nome']) ?></a>
                    <small><?= e(tamanho_legivel((int) ($a['tam'] ?? 0))) ?></small>
                    <a class="link-suave" href="<?= e($link . '&baixar=1') ?>">Baixar</a>
                    <form method="post" action="<?= e(url(['p' => 'anexo_excluir'])) ?>" data-confirma="Remover este arquivo?">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="colecao" value="<?= e($anxColecao) ?>">
                        <input type="hidden" name="id" value="<?= e($anxId) ?>">
                        <input type="hidden" name="arq" value="<?= e($a['arq']) ?>">
                        <button type="submit" class="link perigo-txt" title="Remover">✕</button>
                    </form>
                </div>
                <?php if ($ext === 'pdf'): ?>
                    <details class="pdf-box">
                        <summary>Visualizar PDF aqui</summary>
                        <iframe class="pdf" src="<?= e($link) ?>"></iframe>
                    </details>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
