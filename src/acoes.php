<?php
function view(string $nome, array $v = []): void
{
    $lateral = null;
    if (!empty($v['c']['id'])) {
        $lateral = ['c' => $v['c'], 'regs' => registros_da_campanha($v['c']['id'])];
    }
    $cidBusca = $v['c']['id'] ?? '';
    $regAtual = $v['reg']['id'] ?? '';
    $abaAtual = $v['aba'] ?? ($v['reg']['tipo'] ?? ($v['tipo'] ?? ''));
    extract($v);
    ob_start();
    require VIEWS . '/' . $nome . '.php';
    $conteudo = ob_get_clean();
    require VIEWS . '/layout.php';
}

function nao_encontrado(string $msg = 'O que você procura não existe mais.'): void
{
    http_response_code(404);
    view('erro', ['titulo' => 'Não encontrado', 'msg' => $msg]);
    exit;
}

function registros_da_campanha(string $cid): array
{
    return array_values(array_filter(Store::todos('registros'), fn($r) => $r['campanha_id'] === $cid));
}

function tocar_campanha(string $cid): void
{
    $c = Store::ler('campanhas', $cid);
    if ($c) {
        Store::salvar('campanhas', $c);
    }
}

function apagar_arquivos(array $d): void
{
    foreach ($d['imagens'] ?? [] as $arq) {
        Imagens::remover($arq);
    }
    foreach ($d['slots'] ?? [] as $arq) {
        Imagens::remover((string) $arq);
    }
    foreach ($d['anexos'] ?? [] as $a) {
        Anexos::remover($a['arq']);
    }
}

function aplicar_uploads(array $d, array $post, $files): array
{
    $novasImg = Imagens::receber($files);
    $novosAnx = Anexos::receber($files);
    $d['imagens'] = array_merge(Imagens::manter($d['imagens'] ?? [], $post['remover'] ?? []), $novasImg);
    $d['anexos'] = array_merge(Anexos::manter($d['anexos'] ?? [], $post['remover_anexo'] ?? []), $novosAnx);
    $ignorados = Uploads::enviados($files) - count($novasImg) - count($novosAnx);
    return [$d, max(0, $ignorados)];
}

function mensagem_envio(string $base, int $ignorados): string
{
    if ($ignorados > 0) {
        return $base . ' ' . $ignorados . ' arquivo(s) ignorado(s): formato não aceito.';
    }
    return $base;
}

function act_home(): void
{
    $cont = [];
    foreach (Store::todos('registros') as $r) {
        $cont[$r['campanha_id']] = ($cont[$r['campanha_id']] ?? 0) + 1;
    }
    view('home', ['titulo' => 'Campanhas', 'campanhas' => Store::todos('campanhas'), 'cont' => $cont]);
}

function act_campanha_form(): void
{
    $c = null;
    if (!empty($_GET['id'])) {
        $c = Store::ler('campanhas', $_GET['id']) ?? nao_encontrado();
    }
    view('campanha_form', ['titulo' => $c ? 'Editar campanha' : 'Nova campanha', 'c' => $c]);
}

function act_campanha_salvar(): void
{
    $id = (string) ($_POST['id'] ?? '');
    $d = ['imagens' => [], 'anexos' => []];
    if ($id !== '') {
        $d = Store::ler('campanhas', $id) ?? nao_encontrado();
    }
    $nome = trim((string) ($_POST['nome'] ?? ''));
    if ($nome === '') {
        flash('Informe o nome da campanha.', 'erro');
        redirecionar($id !== '' ? ['p' => 'campanha_editar', 'id' => $id] : ['p' => 'campanha_nova']);
    }
    $d['nome'] = $nome;
    $d['sistema'] = trim((string) ($_POST['sistema'] ?? ''));
    $d['status'] = in_array($_POST['status'] ?? '', ['Ativa', 'Pausada', 'Concluída', 'Planejamento'], true) ? $_POST['status'] : 'Ativa';
    $d['descricao'] = trim((string) ($_POST['descricao'] ?? ''));
    $d['notas'] = trim((string) ($_POST['notas'] ?? ''));
    [$d, $ign] = aplicar_uploads($d, $_POST, $_FILES['arquivos'] ?? null);
    $d = Store::salvar('campanhas', $d);
    flash(mensagem_envio('Campanha salva.', $ign), $ign ? 'erro' : 'ok');
    redirecionar(['p' => 'campanha', 'id' => $d['id']]);
}

function act_campanha_excluir(): void
{
    $c = Store::ler('campanhas', $_POST['id'] ?? '') ?? nao_encontrado();
    foreach (registros_da_campanha($c['id']) as $r) {
        apagar_arquivos($r);
        Store::excluir('registros', $r['id']);
    }
    apagar_arquivos($c);
    Store::excluir('campanhas', $c['id']);
    flash('Campanha excluída.');
    redirecionar(['p' => 'home']);
}

function act_campanha(): void
{
    $c = Store::ler('campanhas', $_GET['id'] ?? '') ?? nao_encontrado();
    $tipos = tipos();
    $aba = (string) ($_GET['aba'] ?? 'geral');
    if (!in_array($aba, ['geral', 'galeria'], true) && !isset($tipos[$aba])) {
        $aba = 'geral';
    }
    $todos = registros_da_campanha($c['id']);
    $grupos = [];
    $galeria = [];
    if (isset($tipos[$aba])) {
        $grupos = agrupar_por_pasta(registros_do_tipo($todos, $aba));
    }
    if ($aba === 'galeria') {
        foreach ($c['imagens'] ?? [] as $arq) {
            $galeria[] = ['arq' => $arq, 'legenda' => $c['nome'], 'href' => url(['p' => 'campanha_editar', 'id' => $c['id']])];
        }
        foreach ($todos as $r) {
            foreach ($r['slots'] ?? [] as $arq) {
                $galeria[] = ['arq' => $arq, 'legenda' => $r['titulo'], 'href' => url(['p' => 'reg_ver', 'id' => $r['id']])];
            }
            foreach ($r['imagens'] ?? [] as $arq) {
                $galeria[] = ['arq' => $arq, 'legenda' => $r['titulo'], 'href' => url(['p' => 'reg_ver', 'id' => $r['id']])];
            }
        }
    }
    view('campanha', [
        'titulo' => $c['nome'],
        'c' => $c,
        'aba' => $aba,
        'total' => count($todos),
        'grupos' => $grupos,
        'galeria' => $galeria,
        'recentes' => array_slice($todos, 0, 8),
    ]);
}

function act_reg_form(): void
{
    $tipos = tipos();
    $reg = null;
    if (!empty($_GET['id'])) {
        $reg = Store::ler('registros', $_GET['id']) ?? nao_encontrado();
        $cid = $reg['campanha_id'];
        $tipo = $reg['tipo'];
    } else {
        $cid = (string) ($_GET['c'] ?? '');
        $tipo = (string) ($_GET['tipo'] ?? '');
    }
    $c = Store::ler('campanhas', $cid) ?? nao_encontrado();
    if (!isset($tipos[$tipo])) {
        nao_encontrado();
    }
    $pastas = [];
    foreach (registros_da_campanha($c['id']) as $r) {
        if (!empty($r['pasta'])) {
            $pastas[$r['pasta']] = true;
        }
    }
    $def = $tipos[$tipo];
    view('registro_form', [
        'titulo' => $reg ? 'Editar ' . $reg['titulo'] : $def['novo'],
        'c' => $c,
        'tipo' => $tipo,
        'def' => $def,
        'reg' => $reg,
        'pastas' => array_keys($pastas),
    ]);
}

function act_reg_salvar(): void
{
    $tipos = tipos();
    $id = (string) ($_POST['id'] ?? '');
    if ($id !== '') {
        $d = Store::ler('registros', $id) ?? nao_encontrado();
    } else {
        $tipo = (string) ($_POST['tipo'] ?? '');
        $cid = (string) ($_POST['campanha_id'] ?? '');
        Store::ler('campanhas', $cid) ?? nao_encontrado();
        if (!isset($tipos[$tipo])) {
            nao_encontrado();
        }
        $d = ['tipo' => $tipo, 'campanha_id' => $cid, 'imagens' => [], 'anexos' => []];
    }
    $titulo = trim((string) ($_POST['titulo'] ?? ''));
    if ($titulo === '') {
        flash('Informe o título.', 'erro');
        redirecionar($id !== '' ? ['p' => 'reg_editar', 'id' => $id] : ['p' => 'reg_novo', 'c' => $d['campanha_id'], 'tipo' => $d['tipo']]);
    }
    $d['titulo'] = $titulo;
    $d['pasta'] = mb_substr(trim((string) ($_POST['pasta'] ?? '')), 0, 80);
    $d['tags'] = tags_lista((string) ($_POST['tags'] ?? ''));
    $d['campos'] = coletar_campos($d['tipo'], is_array($_POST['c'] ?? null) ? $_POST['c'] : []);
    [$d, $ign] = aplicar_uploads($d, $_POST, $_FILES['arquivos'] ?? null);
    $d = Store::salvar('registros', $d);
    tocar_campanha($d['campanha_id']);
    flash(mensagem_envio('Salvo.', $ign), $ign ? 'erro' : 'ok');
    redirecionar(['p' => 'reg_ver', 'id' => $d['id']]);
}

function act_reg_ver(): void
{
    $reg = Store::ler('registros', $_GET['id'] ?? '') ?? nao_encontrado();
    $c = Store::ler('campanhas', $reg['campanha_id']) ?? nao_encontrado();
    $alvo = '[[' . mb_strtolower($reg['titulo']) . ']]';
    $mencoes = [];
    foreach (registros_da_campanha($c['id']) as $r) {
        if ($r['id'] !== $reg['id'] && str_contains(mb_strtolower(texto_plano($r['campos'])), $alvo)) {
            $mencoes[] = $r;
        }
    }
    view('registro_ver', [
        'titulo' => $reg['titulo'],
        'c' => $c,
        'reg' => $reg,
        'def' => tipos()[$reg['tipo']],
        'mencoes' => $mencoes,
    ]);
}

function act_reg_excluir(): void
{
    $reg = Store::ler('registros', $_POST['id'] ?? '') ?? nao_encontrado();
    apagar_arquivos($reg);
    Store::excluir('registros', $reg['id']);
    tocar_campanha($reg['campanha_id']);
    flash('Registro excluído.');
    redirecionar(['p' => 'campanha', 'id' => $reg['campanha_id'], 'aba' => $reg['tipo']]);
}

function act_anexar(): void
{
    $col = ($_POST['colecao'] ?? '') === 'campanhas' ? 'campanhas' : 'registros';
    $d = Store::ler($col, $_POST['id'] ?? '') ?? nao_encontrado();
    [$d, $ign] = aplicar_uploads($d, [], $_FILES['arquivos'] ?? null);
    $d = Store::salvar($col, $d);
    if ($col === 'registros') {
        tocar_campanha($d['campanha_id']);
    }
    flash(mensagem_envio('Arquivos anexados.', $ign), $ign ? 'erro' : 'ok');
    redirecionar($col === 'campanhas' ? ['p' => 'campanha', 'id' => $d['id']] : ['p' => 'reg_ver', 'id' => $d['id']]);
}

function act_anexo_excluir(): void
{
    $col = ($_POST['colecao'] ?? '') === 'campanhas' ? 'campanhas' : 'registros';
    $d = Store::ler($col, $_POST['id'] ?? '') ?? nao_encontrado();
    $d['anexos'] = Anexos::manter($d['anexos'] ?? [], [(string) ($_POST['arq'] ?? '')]);
    $d = Store::salvar($col, $d);
    flash('Arquivo removido.');
    redirecionar($col === 'campanhas' ? ['p' => 'campanha', 'id' => $d['id']] : ['p' => 'reg_ver', 'id' => $d['id']]);
}

function act_lote(): void
{
    $tipos = tipos();
    $tipo = (string) ($_POST['tipo'] ?? '');
    $cid = (string) ($_POST['campanha_id'] ?? '');
    Store::ler('campanhas', $cid) ?? nao_encontrado();
    if (!isset($tipos[$tipo])) {
        nao_encontrado();
    }
    $def = $tipos[$tipo];
    $grupos = [];
    foreach (Uploads::dividir($_FILES['arquivos'] ?? null) as $f) {
        $grupos[mb_strtolower(titulo_de_arquivo($f['name'][0]))][] = $f;
    }
    $criados = 0;
    $ignorados = 0;
    foreach ($grupos as $arquivos) {
        $d = [
            'tipo' => $tipo,
            'campanha_id' => $cid,
            'titulo' => titulo_de_arquivo($arquivos[0]['name'][0]),
            'pasta' => '',
            'tags' => [],
            'campos' => coletar_campos($tipo, []),
            'imagens' => [],
            'anexos' => [],
        ];
        $aproveitado = false;
        foreach ($arquivos as $f) {
            $ext = Anexos::extensao($f['name'][0]);
            if (in_array($ext, ['txt', 'md'], true) && isset($def['principal'])) {
                $texto = ler_texto($f['tmp_name'][0]);
                if ($texto !== null) {
                    $d['campos'][$def['principal']] = $texto;
                    $aproveitado = true;
                    continue;
                }
            }
            $img = Imagens::receber($f);
            $anx = Anexos::receber($f);
            if ($img || $anx) {
                $d['imagens'] = array_merge($d['imagens'], $img);
                $d['anexos'] = array_merge($d['anexos'], $anx);
                $aproveitado = true;
            } else {
                $ignorados++;
            }
        }
        if ($aproveitado) {
            Store::salvar('registros', $d);
            $criados++;
        }
    }
    if ($criados > 0) {
        tocar_campanha($cid);
    }
    $msg = $criados . ' registro(s) criado(s).';
    flash(mensagem_envio($msg, $ignorados), $ignorados || !$criados ? 'erro' : 'ok');
    redirecionar(['p' => 'campanha', 'id' => $cid, 'aba' => $tipo]);
}

function act_busca(): void
{
    $q = trim((string) ($_GET['q'] ?? ''));
    $cid = (string) ($_GET['c'] ?? '');
    $nomes = [];
    foreach (Store::todos('campanhas') as $c) {
        $nomes[$c['id']] = $c['nome'];
    }
    $res = [];
    if ($q !== '') {
        foreach (Store::todos('registros') as $r) {
            if ($cid !== '' && $r['campanha_id'] !== $cid) {
                continue;
            }
            $anexosNomes = implode(' ', array_column($r['anexos'] ?? [], 'nome'));
            $palheiro = $r['titulo'] . ' ' . ($r['pasta'] ?? '') . ' ' . implode(' ', $r['tags'] ?? []) . ' ' . $anexosNomes . ' ' . texto_plano($r['campos']);
            if (mb_stripos($palheiro, $q) !== false) {
                $res[] = $r;
            }
        }
    }
    view('busca', ['titulo' => 'Busca', 'q' => $q, 'cid' => $cid, 'res' => $res, 'nomes' => $nomes]);
}

function act_panteoes(): void
{
    $c = Store::ler('campanhas', $_GET['c'] ?? '') ?? nao_encontrado();
    $grupos = agrupar_por_pasta(registros_do_tipo(registros_da_campanha($c['id']), 'divindade'));
    view('panteoes', ['titulo' => 'Panteões e domínios', 'c' => $c, 'aba' => 'panteoes', 'grupos' => $grupos]);
}

function act_img(): void
{
    session_write_close();
    $f = (string) ($_GET['f'] ?? '');
    $c = Imagens::caminho($f);
    if (!$c) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: ' . Imagens::mime($f));
    header('Content-Length: ' . filesize($c));
    header('Cache-Control: private, max-age=86400');
    header('X-Content-Type-Options: nosniff');
    readfile($c);
    exit;
}

function act_arq(): void
{
    session_write_close();
    $f = (string) ($_GET['f'] ?? '');
    $c = Anexos::caminho($f);
    if (!$c) {
        http_response_code(404);
        exit;
    }
    $nome = Anexos::limparNome((string) ($_GET['n'] ?? $f));
    $ascii = preg_replace('/[^\x20-\x7e]/', '_', $nome);
    $modo = Anexos::inline($f) && empty($_GET['baixar']) ? 'inline' : 'attachment';
    header('Content-Type: ' . Anexos::mime($f));
    header('Content-Disposition: ' . $modo . '; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($nome));
    header('Content-Length: ' . filesize($c));
    header('X-Content-Type-Options: nosniff');
    readfile($c);
    exit;
}

function act_backup(): void
{
    if (!class_exists('ZipArchive')) {
        flash('A extensão zip do PHP não está ativa. Copie a pasta data manualmente.', 'erro');
        redirecionar(['p' => 'home']);
    }
    Store::dir('campanhas');
    Store::dir('registros');
    Store::dir('imagens');
    Store::dir('arquivos');
    $completo = !empty($_GET['completo']);
    $tmp = tempnam(sys_get_temp_dir(), 'rpg');
    $zip = new ZipArchive();
    $zip->open($tmp, ZipArchive::OVERWRITE);
    $base = realpath(DADOS);
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $arq) {
        if ($arq->isFile()) {
            $rel = str_replace('\\', '/', substr($arq->getPathname(), strlen($base) + 1));
            if (!$completo && str_starts_with($rel, 'biblioteca/')) {
                continue;
            }
            $zip->addFile($arq->getPathname(), 'data/' . $rel);
        }
    }
    $zip->close();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="rpg-backup-' . date('Ymd-His') . '.zip"');
    header('Content-Length: ' . filesize($tmp));
    readfile($tmp);
    unlink($tmp);
    exit;
}

function act_reordenar(): void
{
    $ids = $_POST['ids'] ?? [];
    $ok = 0;
    if (is_array($ids)) {
        $base = null;
        foreach ($ids as $id) {
            $r = Store::ler('registros', $id);
            if ($r && isset($r['ordem'])) {
                $base = $base === null ? (int) $r['ordem'] : min($base, (int) $r['ordem']);
            }
        }
        $base = $base ?? 1;
        foreach (array_values($ids) as $i => $id) {
            $r = Store::ler('registros', $id);
            if (!$r) {
                continue;
            }
            $r['ordem'] = $base + $i;
            Store::salvar('registros', $r, false);
            $ok++;
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'n' => $ok]);
}

function slot_alvo(): array
{
    $reg = Store::ler('registros', $_POST['id'] ?? '') ?? nao_encontrado();
    $slots = tipos()[$reg['tipo']]['slots'] ?? [];
    $i = (int) ($_POST['slot'] ?? -1);
    if (!isset($slots[$i])) {
        nao_encontrado();
    }
    return [$reg, $i, $slots[$i]];
}

function act_slot_enviar(): void
{
    [$reg, $i, $nome] = slot_alvo();
    $novas = Imagens::receber($_FILES['arquivos'] ?? null);
    if ($novas) {
        if (!empty($reg['slots'][$i])) {
            Imagens::remover((string) $reg['slots'][$i]);
        }
        $reg['slots'][$i] = $novas[0];
        foreach (array_slice($novas, 1) as $sobra) {
            Imagens::remover($sobra);
        }
        Store::salvar('registros', $reg);
        flash($nome . ' salva.');
    } else {
        flash('Envie uma imagem (jpg, png, gif ou webp).', 'erro');
    }
    redirecionar(['p' => 'reg_ver', 'id' => $reg['id']]);
}

function act_slot_remover(): void
{
    [$reg, $i, $nome] = slot_alvo();
    if (!empty($reg['slots'][$i])) {
        Imagens::remover((string) $reg['slots'][$i]);
        unset($reg['slots'][$i]);
        Store::salvar('registros', $reg);
        flash($nome . ' removida.');
    }
    redirecionar(['p' => 'reg_ver', 'id' => $reg['id']]);
}
