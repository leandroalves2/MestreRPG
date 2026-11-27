<div class="topo"><h1><?= e($def['icone'] . ' ' . $titulo) ?></h1></div>
<p class="sub">Campanha: <a href="<?= e(url(['p' => 'campanha', 'id' => $c['id']])) ?>"><?= e($c['nome']) ?></a></p>

<form class="form" method="post" action="<?= e(url(['p' => $reg ? 'reg_editar' : 'reg_novo'])) ?>" enctype="multipart/form-data">
    <?= csrf_campo() ?>
    <input type="hidden" name="id" value="<?= e($reg['id'] ?? '') ?>">
    <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
    <input type="hidden" name="campanha_id" value="<?= e($c['id']) ?>">

    <label>Título / nome
        <input type="text" name="titulo" required value="<?= e($reg['titulo'] ?? '') ?>" autofocus>
    </label>
    <label>Pasta (opcional, para agrupar. Ex.: "Capítulo 1", "Cidade de Vallaki")
        <input type="text" name="pasta" list="lista-pastas" value="<?= e($reg['pasta'] ?? '') ?>">
        <datalist id="lista-pastas">
            <?php foreach ($pastas as $pa): ?><option value="<?= e($pa) ?>"><?php endforeach; ?>
        </datalist>
    </label>
    <label>Tags (separadas por vírgula)
        <input type="text" name="tags" value="<?= e(implode(', ', $reg['tags'] ?? [])) ?>">
    </label>

    <?php foreach ($def['campos'] as $f):
        $valor = $reg['campos'][$f['n']] ?? ($f['padrao'] ?? ($f['t'] === 'pares' ? [] : ''));
        $nome = 'c[' . $f['n'] . ']';
    ?>
        <?php if ($f['t'] === 'area'): ?>
            <label><?= e($f['r']) ?>
                <textarea name="<?= e($nome) ?>" rows="<?= (int) ($f['linhas'] ?? 5) ?>"><?= e($valor) ?></textarea>
            </label>
        <?php elseif ($f['t'] === 'select'): ?>
            <label><?= e($f['r']) ?>
                <select name="<?= e($nome) ?>">
                    <?php foreach ($f['op'] as $o): ?>
                        <option value="<?= e($o) ?>" <?= $valor === $o ? 'selected' : '' ?>><?= e($o === '' ? '—' : $o) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php elseif ($f['t'] === 'pares'): ?>
            <fieldset class="pares">
                <legend><?= e($f['r']) ?></legend>
                <div class="pares-linhas">
                    <?php foreach ($valor as $par): ?>
                        <div class="par">
                            <input type="text" name="<?= e($nome) ?>[n][]" placeholder="Nome" value="<?= e($par['n']) ?>">
                            <input type="text" name="<?= e($nome) ?>[v][]" placeholder="Valor" value="<?= e($par['v']) ?>">
                            <button type="button" class="link rm-par" title="Remover">✕</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <template>
                    <div class="par">
                        <input type="text" name="<?= e($nome) ?>[n][]" placeholder="Nome">
                        <input type="text" name="<?= e($nome) ?>[v][]" placeholder="Valor">
                        <button type="button" class="link rm-par" title="Remover">✕</button>
                    </div>
                </template>
                <button type="button" class="btn btn-sec add-par">+ Adicionar linha</button>
            </fieldset>
        <?php else: ?>
            <label><?= e($f['r']) ?>
                <input type="<?= $f['t'] === 'numero' ? 'number' : ($f['t'] === 'data' ? 'date' : 'text') ?>" name="<?= e($nome) ?>" value="<?= e($valor) ?>">
            </label>
        <?php endif; ?>
    <?php endforeach; ?>

    <p class="dica">Dica: use [[Título de outro registro]] para linkar e {{Nome do livro|123}} para abrir um livro da biblioteca na página 123. Também funcionam **negrito**, *itálico*, listas com "- " e títulos com "## ".</p>

    <?php if (!empty($reg['imagens'])): ?>
        <div class="miniaturas">
            <?php foreach ($reg['imagens'] as $arq): ?>
                <label class="mini">
                    <img src="<?= e(imagem_url($arq)) ?>" alt="">
                    <span><input type="checkbox" name="remover[]" value="<?= e($arq) ?>"> remover</span>
                </label>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($reg['anexos'])): ?>
        <ul class="anexos-edicao">
            <?php foreach ($reg['anexos'] as $a): ?>
                <li><label><input type="checkbox" name="remover_anexo[]" value="<?= e($a['arq']) ?>"> remover <?= e(Anexos::icone(Anexos::extensao($a['arq'])) . ' ' . $a['nome']) ?></label></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <label>Adicionar imagens e arquivos (fichas em PDF, documentos...)
        <input type="file" name="arquivos[]" multiple>
    </label>

    <div class="acoes">
        <button class="btn" type="submit">Salvar</button>
        <a class="btn btn-sec" href="<?= e($reg ? url(['p' => 'reg_ver', 'id' => $reg['id']]) : url(['p' => 'campanha', 'id' => $c['id'], 'aba' => $tipo])) ?>">Cancelar</a>
    </div>
</form>
