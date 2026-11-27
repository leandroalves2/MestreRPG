<div class="topo"><h1><?= e($titulo) ?></h1></div>

<form class="form" method="post" action="<?= e(url(['p' => $c ? 'campanha_editar' : 'campanha_nova'])) ?>" enctype="multipart/form-data">
    <?= csrf_campo() ?>
    <input type="hidden" name="id" value="<?= e($c['id'] ?? '') ?>">

    <label>Nome da campanha
        <input type="text" name="nome" required value="<?= e($c['nome'] ?? '') ?>" autofocus>
    </label>
    <div class="duas">
        <label>Sistema de RPG
            <input type="text" name="sistema" placeholder="D&D 5e, Tormenta20, Ordem Paranormal..." value="<?= e($c['sistema'] ?? '') ?>">
        </label>
        <label>Status
            <select name="status">
                <?php foreach (['Planejamento', 'Ativa', 'Pausada', 'Concluída'] as $s): ?>
                    <option <?= ($c['status'] ?? 'Ativa') === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>
    <label>Premissa / sinopse
        <textarea name="descricao" rows="6"><?= e($c['descricao'] ?? '') ?></textarea>
    </label>
    <label>Notas gerais do mestre
        <textarea name="notas" rows="8"><?= e($c['notas'] ?? '') ?></textarea>
    </label>
    <p class="dica">Dica: escreva [[Nome de um registro]] em qualquer texto para criar um link automático.</p>

    <?php if (!empty($c['imagens'])): ?>
        <div class="miniaturas">
            <?php foreach ($c['imagens'] as $arq): ?>
                <label class="mini">
                    <img src="<?= e(imagem_url($arq)) ?>" alt="">
                    <span><input type="checkbox" name="remover[]" value="<?= e($arq) ?>"> remover</span>
                </label>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($c['anexos'])): ?>
        <ul class="anexos-edicao">
            <?php foreach ($c['anexos'] as $a): ?>
                <li><label><input type="checkbox" name="remover_anexo[]" value="<?= e($a['arq']) ?>"> remover <?= e(Anexos::icone(Anexos::extensao($a['arq'])) . ' ' . $a['nome']) ?></label></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <label>Imagens e arquivos da campanha (a primeira imagem vira a capa)
        <input type="file" name="arquivos[]" multiple>
    </label>

    <div class="acoes">
        <button class="btn" type="submit">Salvar</button>
        <a class="btn btn-sec" href="<?= e($c ? url(['p' => 'campanha', 'id' => $c['id']]) : url(['p' => 'home'])) ?>">Cancelar</a>
    </div>
</form>

<?php if ($c): ?>
    <form method="post" action="<?= e(url(['p' => 'campanha_excluir'])) ?>" data-confirma="Excluir esta campanha e TODOS os seus registros e imagens? Isso não pode ser desfeito.">
        <?= csrf_campo() ?>
        <input type="hidden" name="id" value="<?= e($c['id']) ?>">
        <button class="btn btn-perigo" type="submit">Excluir campanha</button>
    </form>
<?php endif; ?>
