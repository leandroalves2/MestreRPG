<div class="topo"><h1>🌟 Panteões e domínios</h1></div>
<p class="sub">Resumo de todas as divindades: domínio, inspiração e raças patronas. Esta página se atualiza sozinha quando você edita um deus.</p>

<?php if (!$grupos): ?>
    <p class="vazio">Ainda não há divindades nesta campanha.</p>
<?php endif; ?>

<?php foreach ($grupos as $pasta => $lista): ?>
    <h2><?= e($pasta !== '' ? $pasta : 'Sem pasta') ?></h2>
    <div class="tabela-rolagem">
        <table class="tabela-panteao">
            <thead>
                <tr>
                    <th>Divindade</th>
                    <th>Domínio</th>
                    <th>Hierarquia</th>
                    <th>Inspiração</th>
                    <th>Imagem nas estátuas</th>
                    <th>Raças patronas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lista as $r): $f = $r['campos'] ?? []; ?>
                    <tr>
                        <td><a href="<?= e(url(['p' => 'reg_ver', 'id' => $r['id']])) ?>"><strong><?= e($r['titulo']) ?></strong></a></td>
                        <td><?= e((string) ($f['dominio'] ?? '')) ?></td>
                        <td><?= e((string) ($f['hierarquia'] ?? '')) ?></td>
                        <td><?= e(preg_replace('/\s*\(.*$/u', '', (string) ($f['mitologia'] ?? ''))) ?></td>
                        <td><?= e((string) ($f['estatuas'] ?? '')) ?></td>
                        <td class="texto"><?= render_texto((string) ($f['racas'] ?? ''), $c['id']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endforeach; ?>
