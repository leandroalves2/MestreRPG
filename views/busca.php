<div class="topo"><h1>Busca<?= $q !== '' ? ': ' . e($q) : '' ?></h1></div>

<?php if ($q === ''): ?>
    <p class="vazio">Digite algo na barra de busca acima.</p>
<?php elseif (!$res): ?>
    <p class="vazio">Nenhum resultado para "<?= e($q) ?>".</p>
<?php else: ?>
    <p class="sub"><?= count($res) ?> resultado(s)</p>
    <ul class="resultados">
        <?php foreach ($res as $r): ?>
            <li>
                <a href="<?= e(url(['p' => 'reg_ver', 'id' => $r['id']])) ?>"><?= e(tipos()[$r['tipo']]['icone'] . ' ' . $r['titulo']) ?></a>
                <small><?= e(tipos()[$r['tipo']]['singular']) ?> · <?= e($nomes[$r['campanha_id']] ?? '') ?></small>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
