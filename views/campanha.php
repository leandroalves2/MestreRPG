<?php $tipos = tipos(); $capa = $c['imagens'][0] ?? null; ?>
<section class="cabecalho">
    <?php if ($capa): ?><img class="cabecalho-img zoom" src="<?= e(imagem_url($capa)) ?>" alt="<?= e($c['nome']) ?>"><?php endif; ?>
    <div>
        <h1><?= e($c['nome']) ?></h1>
        <p class="sub"><?= e(trim(($c['sistema'] ?? '') . ' · ' . ($c['status'] ?? ''), ' ·')) ?> · <?= (int) $total ?> registros</p>
    </div>
    <div class="acoes">
        <a class="btn btn-sec" href="<?= e(url(['p' => 'campanha_editar', 'id' => $c['id']])) ?>">Editar campanha</a>
        <form method="post" action="<?= e(url(['p' => 'campanha_excluir'])) ?>" data-confirma="Excluir a campanha &quot;<?= e($c['nome']) ?>&quot; e TODOS os seus registros e imagens? Isso não pode ser desfeito.">
            <?= csrf_campo() ?>
            <input type="hidden" name="id" value="<?= e($c['id']) ?>">
            <button class="btn btn-perigo" type="submit">Excluir campanha</button>
        </form>
    </div>
</section>

<?php if ($aba === 'geral'): ?>
    <div class="colunas">
        <div>
            <?php if (!empty($c['descricao'])): ?>
                <h2>Premissa</h2>
                <div class="texto"><?= render_texto($c['descricao'], $c['id']) ?></div>
            <?php endif; ?>
            <?php if (!empty($c['notas'])): ?>
                <h2>Notas do mestre</h2>
                <div class="texto"><?= render_texto($c['notas'], $c['id']) ?></div>
            <?php endif; ?>
            <?php if (empty($c['descricao']) && empty($c['notas'])): ?>
                <p class="vazio">Ainda sem premissa ou notas. Use "Editar campanha" para escrever.</p>
            <?php endif; ?>

            <h2>Arquivos da campanha</h2>
            <?php $anexos = $c['anexos'] ?? []; $anxColecao = 'campanhas'; $anxId = $c['id']; include VIEWS . '/_anexos.php'; ?>
            <?php
            $dzAcao = 'anexar';
            $dzHidden = ['colecao' => 'campanhas', 'id' => $c['id']];
            $dzTitulo = '📎 Arraste arquivos aqui ou clique para anexar';
            $dzAjuda = 'PDFs, documentos, handouts, imagens... ficam guardados na pasta data/.';
            include VIEWS . '/_dropzone.php';
            ?>
        </div>
        <div>
            <h2>Criar novo</h2>
            <div class="atalhos">
                <?php foreach ($tipos as $k => $t): ?>
                    <a class="btn btn-sec" href="<?= e(url(['p' => 'reg_novo', 'c' => $c['id'], 'tipo' => $k])) ?>"><?= e($t['icone'] . ' ' . $t['novo']) ?></a>
                <?php endforeach; ?>
            </div>
            <h2>Atualizados recentemente</h2>
            <?php if (!$recentes): ?><p class="sub">Nada ainda.</p><?php endif; ?>
            <ul class="recentes">
                <?php foreach ($recentes as $r): ?>
                    <li>
                        <a href="<?= e(url(['p' => 'reg_ver', 'id' => $r['id']])) ?>"><?= e($tipos[$r['tipo']]['icone'] . ' ' . $r['titulo']) ?></a>
                        <small><?= e(dt($r['atualizado'])) ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

<?php elseif ($aba === 'galeria'): ?>
    <h2 class="sem-borda">🖼️ Galeria</h2>
    <?php if (!$galeria): ?>
        <p class="vazio">Nenhuma imagem ainda. Envie imagens nos registros ou na própria campanha.</p>
    <?php endif; ?>
    <div class="galeria">
        <?php foreach ($galeria as $g): ?>
            <figure>
                <img class="zoom" src="<?= e(imagem_url($g['arq'])) ?>" alt="<?= e($g['legenda']) ?>">
                <figcaption><a href="<?= e($g['href']) ?>"><?= e($g['legenda']) ?></a></figcaption>
            </figure>
        <?php endforeach; ?>
    </div>

<?php else: $t = $tipos[$aba]; ?>
    <div class="topo">
        <h2 class="sem-borda"><?= e($t['icone'] . ' ' . $t['rotulo']) ?></h2>
        <input type="search" class="filtro" data-filtro="#lista" placeholder="Filtrar...">
        <a class="btn" href="<?= e(url(['p' => 'reg_novo', 'c' => $c['id'], 'tipo' => $aba])) ?>">+ <?= e($t['novo']) ?></a>
    </div>

    <?php
    $dzAcao = 'lote';
    $dzHidden = ['campanha_id' => $c['id'], 'tipo' => $aba];
    $dzTitulo = '📥 Arraste arquivos aqui para criar vários em ' . $t['rotulo'] . ' de uma vez';
    $dzAjuda = 'Cada nome de arquivo vira um registro. Imagem vira a foto/mapa, PDF ou Word vira anexo (ficha), .txt/.md vira a descrição. Arquivos com o mesmo nome (Strahd.png + Strahd.pdf + Strahd.md) viram um único registro.';
    include VIEWS . '/_dropzone.php';
    ?>

    <?php if (!$grupos): ?><p class="vazio">Nada por aqui ainda.</p><?php endif; ?>
    <div id="lista" data-reordenar="<?= e(url(['p' => 'reordenar'])) ?>" data-csrf="<?= e(csrf_token()) ?>">
        <?php foreach ($grupos as $pasta => $itens): ?>
            <?php if ((string) $pasta !== ''): ?><h3 class="pasta-titulo">📁 <?= e($pasta) ?></h3><?php endif; ?>
            <div class="grade">
                <?php foreach ($itens as $r): $img = ($r['slots'][0] ?? null) ?: ($r['imagens'][0] ?? null); $nAnx = count($r['anexos'] ?? []); ?>
                    <a class="card" draggable="true" data-id="<?= e($r['id']) ?>" data-busca="<?= e(mb_strtolower($r['titulo'] . ' ' . implode(' ', $r['tags'] ?? []) . ' ' . subtitulo($r))) ?>" href="<?= e(url(['p' => 'reg_ver', 'id' => $r['id']])) ?>">
                        <div class="card-img">
                            <?php if ($img): ?><img draggable="false" src="<?= e(imagem_url($img)) ?>" alt=""><?php else: ?><span><?= e($t['icone']) ?></span><?php endif; ?>
                        </div>
                        <div class="card-corpo">
                            <h3><?= e($r['titulo']) ?></h3>
                            <p class="sub"><?= e(subtitulo($r)) ?></p>
                            <?php foreach ($t['cartao'] ?? [] as $cn => $ci): $cv = texto_cartao((string) ($r['campos'][$cn] ?? '')); if ($cv !== ''): ?>
                                <p class="sub cartao-linha"><?= e($ci . ' ' . $cv) ?></p>
                            <?php endif; endforeach; ?>
                            <?php if ($nAnx): ?><p class="sub">📎 <?= $nAnx ?> arquivo(s)</p><?php endif; ?>
                            <?php if (!empty($r['tags'])): ?>
                                <p class="tags"><?php foreach ($r['tags'] as $tg): ?><span><?= e($tg) ?></span><?php endforeach; ?></p>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
