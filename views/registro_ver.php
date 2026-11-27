<?php
$campos = $reg['campos'];
$info = [];
$blocos = [];
$mestre = [];
$lore = [];
$cidade = [];
$temBotaoCidade = ($campos['pdf_cidade'] ?? '') === 'Sim';
$tabelas = [];
foreach ($def['campos'] as $f) {
    $v = $campos[$f['n']] ?? '';
    if ($v === '' || $v === []) {
        continue;
    }
    if ($f['t'] === 'area') {
        $aba = $f['aba'] ?? 'jogador';
        if ($aba === 'mestre') {
            $mestre[] = [$f['r'], $v];
        } elseif ($aba === 'cidade') {
            $cidade[] = [$f['r'], $v];
        } elseif ($aba === 'lore') {
            $lore[] = [$f['r'], $v];
        } else {
            $blocos[] = [$f['r'], $v];
        }
    } elseif ($f['t'] === 'pares') {
        $tabelas[] = [$f['r'], $v];
    } elseif (!in_array($f['n'], ['pv_atual', 'pv_max', 'pdf_cidade'], true)) {
        $iv = $f['t'] === 'data' ? data_br($v) : $v;
        $info[] = [$f['r'], is_string($iv) && str_contains($iv, '[[') ? formatar_inline(e($iv), $c['id']) : e($iv)];
    }
}
$pvMax = (int) ($campos['pv_max'] ?? 0);
$pvAtual = (int) ($campos['pv_atual'] ?? 0);
$pct = $pvMax > 0 ? max(0, min(100, (int) round($pvAtual / $pvMax * 100))) : 0;
?>
<p class="sub"><a href="<?= e(url(['p' => 'campanha', 'id' => $c['id']])) ?>"><?= e($c['nome']) ?></a> › <a href="<?= e(url(['p' => 'campanha', 'id' => $c['id'], 'aba' => $reg['tipo']])) ?>"><?= e($def['rotulo']) ?></a><?php if (!empty($reg['pasta'])): ?> › 📁 <?= e($reg['pasta']) ?><?php endif; ?></p>

<?php if ($reg['tipo'] === 'raca'): ?><i class="ficha-livro"></i><?php endif; ?>
<div class="topo">
    <h1><span class="ico"><?= e($def['icone']) ?></span> <?= e($reg['titulo']) ?></h1>
    <div class="acoes">
        <a class="btn" href="<?= e(url(['p' => 'reg_editar', 'id' => $reg['id']])) ?>">Editar</a>
        <button class="btn btn-sec" type="button" data-imprimir title="Abre a impressão do navegador. Escolha &quot;Salvar como PDF&quot; para gerar o arquivo.">🖨️ Imprimir ficha do jogador / PDF</button>
<?php if ($temBotaoCidade): ?>
        <button class="btn btn-sec" type="button" data-imprimir-cidade title="Imprime só a lore da cidade, resumida para o jogador. Entregue depois da criação do personagem.">🏰 Imprimir lore da cidade / PDF</button>
<?php endif; ?>
        <form method="post" action="<?= e(url(['p' => 'reg_excluir'])) ?>" data-confirma="Excluir &quot;<?= e($reg['titulo']) ?>&quot;?">
            <?= csrf_campo() ?>
            <input type="hidden" name="id" value="<?= e($reg['id']) ?>">
            <button class="btn btn-perigo" type="submit">Excluir</button>
        </form>
    </div>
</div>

<?php if (!empty($reg['tags'])): ?>
    <p class="tags"><?php foreach ($reg['tags'] as $tg): ?><span><?= e($tg) ?></span><?php endforeach; ?></p>
<?php endif; ?>

<?php if (!empty($def['slots'])): ?>
    <section class="slots">
        <?php foreach ($def['slots'] as $si => $sn): $sa = $reg['slots'][$si] ?? null; ?>
            <div class="slot<?= $sa ? '' : ' slot-sem' ?>">
                <h4><?= e($sn) ?></h4>
                <?php if ($sa): ?>
                    <div class="slot-img"><img class="zoom" src="<?= e(imagem_url($sa)) ?>" alt="<?= e($sn . ' — ' . $reg['titulo']) ?>"></div>
                    <div class="slot-acoes">
                        <form method="post" action="<?= e(url(['p' => 'slot_enviar'])) ?>" enctype="multipart/form-data">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="id" value="<?= e($reg['id']) ?>">
                            <input type="hidden" name="slot" value="<?= (int) $si ?>">
                            <label class="btn btn-sec btn-mini">Trocar<input type="file" name="arquivos[]" accept="image/*" data-auto></label>
                        </form>
                        <form method="post" action="<?= e(url(['p' => 'slot_remover'])) ?>" data-confirma="Remover <?= e(mb_strtolower($sn)) ?>?">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="id" value="<?= e($reg['id']) ?>">
                            <input type="hidden" name="slot" value="<?= (int) $si ?>">
                            <button class="btn btn-sec btn-mini" type="submit">Remover</button>
                        </form>
                    </div>
                <?php else: ?>
                    <form method="post" action="<?= e(url(['p' => 'slot_enviar'])) ?>" enctype="multipart/form-data">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="id" value="<?= e($reg['id']) ?>">
                        <input type="hidden" name="slot" value="<?= (int) $si ?>">
                        <label class="slot-vazio">
                            <span>＋ Adicionar <?= e(mb_strtolower($sn)) ?></span>
                            <small>Clique ou arraste uma imagem para cá</small>
                            <input type="file" name="arquivos[]" accept="image/*" data-auto>
                        </label>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php if (!empty($reg['imagens'])): ?>
    <div class="galeria pequena">
        <?php foreach ($reg['imagens'] as $arq): ?>
            <figure><img class="zoom" src="<?= e(imagem_url($arq)) ?>" alt="<?= e($reg['titulo']) ?>"></figure>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($pvMax > 0): ?>
    <div class="pv">
        <div class="pv-barra"><div style="width: <?= $pct ?>%"></div></div>
        <span>PV <?= $pvAtual ?> / <?= $pvMax ?></span>
    </div>
<?php endif; ?>

<?php
$temJogador = $info || $tabelas || $blocos;
$abasLista = [];
if ($lore) {
    $abasLista['lore'] = '📜 A lore';
}
if ($temJogador || !$abasLista) {
    $abasLista['jogador'] = '📖 Ficha do jogador';
}
if ($temBotaoCidade) {
    $abasLista['cidade'] = '🏰 PDF da cidade';
}
if ($mestre) {
    $abasLista['mestre'] = '🔒 Notas do mestre';
}
$abaInicial = array_key_first($abasLista);
?>
<?php if (count($abasLista) > 1): ?>
    <div class="abas-reg nao-imprimir" role="tablist">
        <?php foreach ($abasLista as $chave => $rotuloAba): ?>
            <button type="button" class="aba-btn<?= $chave === $abaInicial ? ' ativa' : '' ?>" data-aba-alvo="<?= e($chave) ?>"><?= e($rotuloAba) ?></button>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($lore): ?>
<section class="aba-painel aba-lore nao-imprimir" data-aba="lore"<?= $abaInicial !== 'lore' ? ' hidden' : '' ?>>
    <?php foreach ($lore as [$r, $v]): ?>
        <h2><?= e($r) ?></h2>
        <div class="texto"><?= render_texto($v, $c['id']) ?></div>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<?php if ($temBotaoCidade): ?>
<section class="aba-painel aba-cidade nao-imprimir" data-aba="cidade"<?= $abaInicial !== 'cidade' ? ' hidden' : '' ?>>
    <h1 class="so-cidade-impressao"><?= e($reg['titulo']) ?> · a cidade</h1>
<?php if ($cidade): ?>
    <?php foreach ($cidade as [$r, $v]): ?>
        <div class="texto"><?= render_texto($v, $c['id']) ?></div>
    <?php endforeach; ?>
<?php else: ?>
    <p class="vazio">Ainda sem conteúdo. O botão já funciona; o texto será escrito depois.</p>
<?php endif; ?>
</section>
<?php endif; ?>

<section class="aba-painel" data-aba="jogador"<?= $abaInicial !== 'jogador' ? ' hidden' : '' ?>>
<?php if ($info): ?>
    <dl class="info">
        <?php foreach ($info as [$r, $v]): ?>
            <div><dt><?= e($r) ?></dt><dd><?= $v ?></dd></div>
        <?php endforeach; ?>
    </dl>
<?php endif; ?>

<?php foreach ($tabelas as [$r, $lista]): ?>
    <h2><?= e($r) ?></h2>
    <div class="atributos">
        <?php foreach ($lista as $par): ?>
            <div class="atributo"><span><?= e($par['n']) ?></span><strong><?= e($par['v']) ?></strong></div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<?php foreach ($blocos as [$r, $v]): ?>
    <h2><?= e($r) ?></h2>
    <div class="texto"><?= render_texto($v, $c['id']) ?></div>
<?php endforeach; ?>

<?php if (!$info && !$tabelas && !$blocos && !$mestre && !$lore): ?>
    <p class="vazio">Sem conteúdo ainda. Clique em Editar para preencher.</p>
<?php endif; ?>
</section>

<?php if ($mestre): ?>
<section class="aba-painel aba-mestre nao-imprimir" data-aba="mestre"<?= $abaInicial !== 'mestre' ? ' hidden' : '' ?>>
    <?php foreach ($mestre as [$r, $v]): ?>
        <h2><?= e($r) ?></h2>
        <div class="texto"><?= render_texto($v, $c['id']) ?></div>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<div class="nao-imprimir">
<h2>Arquivos anexados</h2>
<?php $anexos = $reg['anexos'] ?? []; $anxColecao = 'registros'; $anxId = $reg['id']; include VIEWS . '/_anexos.php'; ?>
<?php
$dzAcao = 'anexar';
$dzHidden = ['colecao' => 'registros', 'id' => $reg['id']];
$dzTitulo = '📎 Arraste arquivos aqui ou clique para anexar';
$dzAjuda = 'Fichas em PDF, documentos, imagens extras... (imagens entram na galeria deste registro).';
include VIEWS . '/_dropzone.php';
?>
</div>

<?php if ($mencoes): ?>
<div class="nao-imprimir">
    <h2>Mencionado em</h2>
    <ul class="recentes">
        <?php foreach ($mencoes as $m): ?>
            <li><a href="<?= e(url(['p' => 'reg_ver', 'id' => $m['id']])) ?>"><?= e(tipos()[$m['tipo']]['icone'] . ' ' . $m['titulo']) ?></a></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<p class="sub rodape nao-imprimir">Criado em <?= e(dt($reg['criado'] ?? null)) ?> · atualizado em <?= e(dt($reg['atualizado'] ?? null)) ?></p>
