<div class="topo">
    <h1>📚 Biblioteca de regras</h1>
</div>
<p class="sub">Livros de regras valem para todas as campanhas. Envie os PDFs, abra no leitor e pesquise por palavra ou por página.</p>

<form class="busca-livros" method="get" action="">
    <input type="hidden" name="p" value="biblioteca">
    <?php if (!empty($c['id'])): ?><input type="hidden" name="c" value="<?= e($c['id']) ?>"><?php endif; ?>
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar em todos os livros (ex.: bola de fogo, grapple, Strahd)...">
    <button class="btn" type="submit">Buscar</button>
</form>

<?php if ($q !== ''): ?>
    <h2>Resultados para "<?= e($q) ?>"</h2>
    <?php if (!$res): ?>
        <p class="vazio">Nada encontrado. Confira se os livros foram indexados (coluna "Busca" abaixo).</p>
    <?php else: ?>
        <p class="sub"><?= count($res) ?> página(s) encontrada(s)<?= count($res) >= 150 ? ' (mostrando as 150 primeiras)' : '' ?></p>
        <ul class="resultados-livros">
            <?php foreach ($res as $r): ?>
                <li>
                    <a class="livro-link" target="_blank" href="<?= e(url(['p' => 'leitor', 'id' => $r['livro']['id'], 'pag' => $r['pagina'], 'q' => $r['q']])) ?>">
                        <?= e($r['livro']['titulo']) ?> · p. <?= (int) $r['pagina'] ?>
                    </a>
                    <p><?= $r['html'] ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
<?php endif; ?>

<h2>Seus livros</h2>
<?php
$dzAcao = 'biblioteca_enviar';
$dzHidden = [];
$dzCampo = 'livros';
$dzTitulo = '📚 Arraste PDFs aqui ou clique para enviar';
$dzAjuda = 'Livro do Jogador, Guia do Mestre, Livro dos Monstros, suplementos... Depois do envio o texto é indexado automaticamente (leva alguns segundos por livro).';
include VIEWS . '/_dropzone.php';
?>

<?php if (!$livros): ?>
    <p class="vazio">Nenhum livro ainda.</p>
<?php else: ?>
    <div class="livros">
        <?php foreach ($livros as $l): ?>
            <div class="livro">
                <div class="livro-info">
                    <h3><?= e($l['titulo']) ?></h3>
                    <p class="sub">
                        <?= e($l['categoria'] ?: 'Sem categoria') ?> ·
                        <?= (int) $l['paginas'] ? (int) $l['paginas'] . ' páginas' : 'páginas desconhecidas' ?> ·
                        <?= e(tamanho_legivel((int) ($l['tam'] ?? 0))) ?> ·
                        Busca: <?= !empty($l['indexado']) ? '✅ pronta' : '⚠️ não indexado' ?>
                    </p>
                </div>
                <div class="livro-acoes">
                    <a class="btn" target="_blank" href="<?= e(url(['p' => 'leitor', 'id' => $l['id']])) ?>">Abrir</a>
                    <form method="get" action="" target="_blank" class="ir-pagina">
                        <input type="hidden" name="p" value="leitor">
                        <input type="hidden" name="id" value="<?= e($l['id']) ?>">
                        <input type="number" name="pag" min="1" placeholder="Página">
                        <button class="btn btn-sec" type="submit">Ir</button>
                    </form>
                    <a class="btn btn-sec" href="<?= e(url(['p' => 'biblioteca_indexar', 'id' => $l['id']])) ?>"><?= !empty($l['indexado']) ? 'Reindexar' : 'Indexar' ?></a>
                    <details class="livro-editar">
                        <summary>Editar</summary>
                        <form method="post" action="<?= e(url(['p' => 'biblioteca_editar'])) ?>" class="form">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="id" value="<?= e($l['id']) ?>">
                            <label>Título (é o nome usado nos links {{Título|página}})
                                <input type="text" name="titulo" value="<?= e($l['titulo']) ?>">
                            </label>
                            <label>Categoria
                                <input type="text" name="categoria" list="categorias-livros" value="<?= e($l['categoria'] ?? '') ?>">
                            </label>
                            <button class="btn" type="submit">Salvar</button>
                        </form>
                        <form method="post" action="<?= e(url(['p' => 'biblioteca_excluir'])) ?>" data-confirma="Remover &quot;<?= e($l['titulo']) ?>&quot; da biblioteca? O PDF enviado será apagado da pasta data.">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="id" value="<?= e($l['id']) ?>">
                            <button class="btn btn-perigo" type="submit">Remover livro</button>
                        </form>
                    </details>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <datalist id="categorias-livros">
        <option value="Livro do Jogador">
        <option value="Guia do Mestre">
        <option value="Livro dos Monstros">
        <option value="Aventura">
        <option value="Suplemento">
        <option value="Outro">
    </datalist>
<?php endif; ?>
