<?php $flash = pegar_flash(); $tp = tipos(); ?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo ?? 'Grimório') ?> · Grimório</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="barra">
    <a class="logo" href="<?= e(url(['p' => 'home'])) ?>">⚔ Grimório</a>
    <form class="busca" method="get" action="">
        <input type="hidden" name="p" value="busca">
        <?php if ($cidBusca !== ''): ?><input type="hidden" name="c" value="<?= e($cidBusca) ?>"><?php endif; ?>
        <input type="search" name="q" placeholder="<?= $cidBusca !== '' ? 'Buscar nesta campanha...' : 'Buscar em tudo...' ?>" value="<?= e($_GET['q'] ?? '') ?>">
    </form>
    <nav>
        <a href="<?= e(url(['p' => 'home'])) ?>">Campanhas</a>
        <a href="<?= e(url($cidBusca !== '' ? ['p' => 'biblioteca', 'c' => $cidBusca] : ['p' => 'biblioteca'])) ?>">📚 Biblioteca</a>
        <a href="<?= e(url(['p' => 'backup'])) ?>" title="Dados da campanha (sem os PDFs da biblioteca). Para incluir os PDFs: ?p=backup&amp;completo=1">Backup</a>
        <button type="button" id="abrir-dados" class="link">🎲 Dados</button>
    </nav>
</header>

<div class="corpo <?= $lateral ? 'com-lateral' : '' ?>">
    <?php if ($lateral): $lc = $lateral['c']; ?>
        <aside class="lateral" data-campanha="<?= e($lc['id']) ?>" data-reordenar="<?= e(url(['p' => 'reordenar'])) ?>" data-csrf="<?= e(csrf_token()) ?>">
            <a class="lateral-titulo" href="<?= e(url(['p' => 'campanha', 'id' => $lc['id']])) ?>">⚔ <?= e($lc['nome']) ?></a>
            <div class="lateral-todos">
                <button type="button" class="link" data-lateral-todos="abrir" title="Expandir todas as seções e pastas">＋ Expandir tudo</button>
                <button type="button" class="link" data-lateral-todos="fechar" title="Recolher todas as seções e pastas">－ Recolher tudo</button>
            </div>
            <a class="lateral-item <?= $abaAtual === 'geral' && $regAtual === '' ? 'ativa' : '' ?>" href="<?= e(url(['p' => 'campanha', 'id' => $lc['id']])) ?>">🏠 Visão geral</a>
            <?php foreach ($tp as $k => $t):
                $lista = registros_do_tipo($lateral['regs'], $k);
            ?>
                <details data-tipo="<?= e($k) ?>">
                    <summary>
                        <a class="rotulo" href="<?= e(url(['p' => 'campanha', 'id' => $lc['id'], 'aba' => $k])) ?>"><?= e($t['icone'] . ' ' . $t['rotulo']) ?></a>
                        <small><?= count($lista) ?></small>
                        <span class="mais" title="Expandir ou recolher"></span>
                    </summary>
                    <a class="lateral-novo" href="<?= e(url(['p' => 'reg_novo', 'c' => $lc['id'], 'tipo' => $k])) ?>">＋ <?= e($t['novo']) ?></a>
                    <?= html_lateral_no(arvore_pastas($lista), $k, '', 0, (string) $regAtual) ?>
                </details>
            <?php endforeach; ?>
            <a class="lateral-item <?= $abaAtual === 'panteoes' ? 'ativa' : '' ?>" href="<?= e(url(['p' => 'panteoes', 'c' => $lc['id']])) ?>">🌟 Panteões e domínios</a>
            <a class="lateral-item" href="<?= e(url(['p' => 'biblioteca', 'c' => $lc['id']])) ?>">📚 Biblioteca de regras</a>
            <a class="lateral-item <?= $abaAtual === 'galeria' ? 'ativa' : '' ?>" href="<?= e(url(['p' => 'campanha', 'id' => $lc['id'], 'aba' => 'galeria'])) ?>">🖼️ Galeria</a>
        </aside>
    <?php endif; ?>

    <main class="pagina">
        <?php if ($flash): ?>
            <div class="aviso aviso-<?= e($flash['t']) ?>"><?= e($flash['m']) ?></div>
        <?php endif; ?>
        <?= $conteudo ?>
    </main>
</div>

<aside id="painel-dados" class="dados" hidden>
    <div class="dados-topo"><strong>Rolador de dados</strong><button type="button" id="fechar-dados" class="link">✕</button></div>
    <div class="dados-linha">
        <label>Qtd <input type="number" id="dado-qtd" value="1" min="1" max="50"></label>
        <label>Mod <input type="number" id="dado-mod" value="0"></label>
    </div>
    <div class="dados-botoes">
        <?php foreach ([4, 6, 8, 10, 12, 20, 100] as $d): ?>
            <button type="button" data-dado="<?= $d ?>">d<?= $d ?></button>
        <?php endforeach; ?>
    </div>
    <ul id="dado-log"></ul>
</aside>

<div id="lightbox" hidden><img alt=""><p></p></div>
<script src="assets/app.js"></script>
</body>
</html>
