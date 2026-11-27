<div class="topo">
    <h1>Suas campanhas</h1>
    <a class="btn" href="<?= e(url(['p' => 'campanha_nova'])) ?>">+ Nova campanha</a>
</div>

<?php if (!$campanhas): ?>
    <div class="vazio">
        <p>Nenhuma campanha ainda. Crie a primeira para começar a guardar personagens, locais, sessões e imagens.</p>
    </div>
<?php endif; ?>

<div class="grade">
    <?php foreach ($campanhas as $c): $capa = $c['imagens'][0] ?? null; ?>
        <div class="card-wrap">
        <a class="card" href="<?= e(url(['p' => 'campanha', 'id' => $c['id']])) ?>">
            <div class="card-img">
                <?php if ($capa): ?><img src="<?= e(imagem_url($capa)) ?>" alt=""><?php else: ?><span>⚔</span><?php endif; ?>
            </div>
            <div class="card-corpo">
                <h3><?= e($c['nome']) ?></h3>
                <p class="sub"><?= e(trim(($c['sistema'] ?? '') . ' · ' . ($c['status'] ?? ''), ' ·')) ?></p>
                <p class="sub"><?= (int) ($cont[$c['id']] ?? 0) ?> registros · atualizada em <?= e(dt($c['atualizado'] ?? null)) ?></p>
            </div>
        </a>
        <form method="post" action="<?= e(url(['p' => 'campanha_excluir'])) ?>" data-confirma="Excluir a campanha &quot;<?= e($c['nome']) ?>&quot; e TODOS os seus registros e imagens? Isso não pode ser desfeito.">
            <?= csrf_campo() ?>
            <input type="hidden" name="id" value="<?= e($c['id']) ?>">
            <button class="btn btn-perigo btn-mini" type="submit">Excluir</button>
        </form>
        </div>
    <?php endforeach; ?>
</div>
