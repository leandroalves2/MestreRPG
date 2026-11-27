<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($l['titulo']) ?> · Leitor</title>
<link rel="stylesheet" href="pdfjs/pdf_viewer.css">
<link rel="stylesheet" href="assets/leitor.css">
</head>
<body id="leitor"
      data-pdf="<?= e(url(['p' => 'livro_pdf', 'id' => $l['id']])) ?>"
      data-id="<?= e($l['id']) ?>"
      data-pag="<?= (int) $pag ?>"
      data-q="<?= e($q) ?>">
<header class="leitor-barra">
    <a class="leitor-voltar" href="<?= e(url(['p' => 'biblioteca'])) ?>" title="Biblioteca">📚</a>
    <button type="button" id="btn-miniaturas" title="Miniaturas das páginas">▦</button>
    <button type="button" id="btn-indice" title="Índice do livro" hidden>☰</button>
    <strong class="leitor-titulo"><?= e($l['titulo']) ?></strong>
    <div class="leitor-grupo">
        <button type="button" id="btn-ant" title="Página anterior">‹</button>
        <input type="number" id="pag-atual" min="1" value="1" title="Ir para a página">
        <span>/ <span id="pag-total">…</span></span>
        <button type="button" id="btn-prox" title="Próxima página">›</button>
    </div>
    <div class="leitor-grupo">
        <button type="button" id="zoom-menos" title="Diminuir">−</button>
        <select id="zoom">
            <option value="page-width">Largura</option>
            <option value="page-fit">Página inteira</option>
            <option value="0.75">75%</option>
            <option value="1">100%</option>
            <option value="1.25">125%</option>
            <option value="1.5">150%</option>
            <option value="2">200%</option>
        </select>
        <button type="button" id="zoom-mais" title="Aumentar">+</button>
    </div>
    <div class="leitor-grupo leitor-busca">
        <input type="search" id="busca" placeholder="Buscar neste livro (Ctrl+F)" value="<?= e($q) ?>">
        <button type="button" id="busca-ant" title="Anterior">↑</button>
        <button type="button" id="busca-prox" title="Próximo">↓</button>
        <span id="busca-info"></span>
    </div>
</header>
<div class="leitor-corpo">
    <nav id="painel-miniaturas" hidden></nav>
    <nav id="painel-indice" hidden></nav>
    <div class="leitor-area">
        <div id="container"><div id="viewer" class="pdfViewer"></div></div>
        <p id="leitor-msg">Carregando o livro...</p>
    </div>
</div>
<script type="module" src="assets/leitor.js"></script>
</body>
</html>
