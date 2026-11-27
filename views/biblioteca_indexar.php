<div class="topo"><h1>Indexando: <?= e($livro['titulo']) ?></h1></div>
<p class="sub">O texto de cada página é lido aqui no navegador e guardado na pasta data, para a busca por palavra funcionar em todos os livros. Não feche esta aba até terminar.</p>

<div id="indexador"
     data-pdf="<?= e(url(['p' => 'livro_pdf', 'id' => $livro['id']])) ?>"
     data-envio="<?= e(url(['p' => 'biblioteca_indice'])) ?>"
     data-id="<?= e($livro['id']) ?>"
     data-csrf="<?= e(csrf_token()) ?>"
     data-fila="<?= e($fila) ?>"
     data-biblioteca="<?= e(url(['p' => 'biblioteca'])) ?>"
     data-indexar="<?= e(url(['p' => 'biblioteca_indexar'])) ?>">
    <progress id="progresso" value="0" max="1"></progress>
    <p id="status">Abrindo o PDF...</p>
    <p><a class="btn btn-sec" href="<?= e(url(['p' => 'biblioteca'])) ?>">Pular e voltar à biblioteca</a></p>
</div>
<script type="module" src="assets/indexar.js"></script>
