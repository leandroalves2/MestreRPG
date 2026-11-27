<?php
function achar_registro(string $cid, string $tipo, string $titulo): ?array
{
    $alvo = mb_strtolower($titulo);
    foreach (registros_da_campanha($cid) as $r) {
        if ($r['tipo'] === $tipo && mb_strtolower($r['titulo']) === $alvo) {
            return $r;
        }
    }
    return null;
}

function mesclar_campos(string $tipo, array $atuais, array $novos, bool $acrescentar): array
{
    foreach (tipos()[$tipo]['campos'] as $f) {
        $n = $f['n'];
        if (!array_key_exists($n, $novos)) {
            $atuais[$n] = $atuais[$n] ?? ($f['t'] === 'pares' ? [] : '');
            continue;
        }
        $v = $novos[$n];
        if ($f['t'] === 'pares') {
            $lista = [];
            foreach ((array) $v as $k => $par) {
                if (is_array($par)) {
                    $nome = trim((string) ($par['n'] ?? $par[0] ?? ''));
                    $val = trim((string) ($par['v'] ?? $par[1] ?? ''));
                } else {
                    $nome = trim((string) $k);
                    $val = trim((string) $par);
                }
                if ($nome !== '' || $val !== '') {
                    $lista[] = ['n' => $nome, 'v' => $val];
                }
            }
            $atuais[$n] = $lista;
        } else {
            $v = is_scalar($v) ? trim((string) $v) : '';
            if ($acrescentar && $f['t'] === 'area' && !empty($atuais[$n]) && $v !== '') {
                $v = $atuais[$n] . "\n\n" . $v;
            }
            $atuais[$n] = $v;
        }
    }
    return $atuais;
}

function resolver_campanha($ref): array
{
    $dados = is_array($ref) ? $ref : ['nome' => (string) $ref];
    $id = (string) ($dados['id'] ?? '');
    $nome = trim((string) ($dados['nome'] ?? ''));
    $alvo = null;
    foreach (Store::todos('campanhas') as $c) {
        if (($id !== '' && $c['id'] === $id) || ($id === '' && $nome !== '' && mb_strtolower($c['nome']) === mb_strtolower($nome))) {
            $alvo = $c;
            break;
        }
    }
    if (!$alvo) {
        if (is_array($ref) && $nome !== '') {
            $alvo = ['nome' => $nome, 'sistema' => '', 'status' => 'Ativa', 'descricao' => '', 'notas' => '', 'imagens' => [], 'anexos' => []];
        } else {
            throw new RuntimeException('Campanha não encontrada: ' . ($nome !== '' ? $nome : $id));
        }
    } elseif (!is_array($ref)) {
        return $alvo;
    }
    foreach (['sistema', 'status', 'descricao', 'notas'] as $k) {
        if (!array_key_exists($k, $dados)) {
            continue;
        }
        $v = trim((string) $dados[$k]);
        if ($k === 'status' && !in_array($v, ['Ativa', 'Pausada', 'Concluída', 'Planejamento'], true)) {
            $v = 'Ativa';
        }
        if (!empty($dados['acrescentar']) && in_array($k, ['descricao', 'notas'], true) && !empty($alvo[$k]) && $v !== '') {
            $v = $alvo[$k] . "\n\n" . $v;
        }
        $alvo[$k] = $v;
    }
    return Store::salvar('campanhas', $alvo);
}

function aplicar_pacote($pkg): array
{
    if (!is_array($pkg)) {
        throw new RuntimeException('O arquivo não é um JSON válido.');
    }
    $camp = resolver_campanha($pkg['campanha'] ?? null);
    $tipos = tipos();
    $novos = 0;
    $atualizados = 0;
    $avisos = [];
    foreach ((array) ($pkg['registros'] ?? []) as $i => $r) {
        $tipo = (string) ($r['tipo'] ?? '');
        $titulo = trim((string) ($r['titulo'] ?? ''));
        if (!isset($tipos[$tipo]) || $titulo === '') {
            $avisos[] = 'registro #' . ($i + 1) . ' ignorado (tipo ou título inválido)';
            continue;
        }
        $existente = achar_registro($camp['id'], $tipo, $titulo);
        if (!$existente && !empty($r['renomear_de'])) {
            $anterior = achar_registro($camp['id'], $tipo, trim((string) $r['renomear_de']));
            if ($anterior) {
                $existente = $anterior;
                $existente['titulo'] = $titulo;
            }
        }
        $d = $existente ?? [
            'tipo' => $tipo,
            'campanha_id' => $camp['id'],
            'titulo' => $titulo,
            'pasta' => '',
            'tags' => [],
            'campos' => coletar_campos($tipo, []),
            'imagens' => [],
            'anexos' => [],
        ];
        if (isset($r['pasta'])) {
            $d['pasta'] = mb_substr(trim((string) $r['pasta']), 0, 80);
        }
        if (isset($r['ordem']) && is_numeric($r['ordem']) && !isset($d['ordem'])) {
            $d['ordem'] = (int) $r['ordem'];
        }
        if (isset($r['tags']) && is_array($r['tags'])) {
            $d['tags'] = tags_lista(implode(',', array_map('strval', $r['tags'])));
        }
        if (isset($r['campos']) && is_array($r['campos'])) {
            $d['campos'] = mesclar_campos($tipo, $d['campos'] ?? [], $r['campos'], !empty($r['acrescentar']));
        }
        Store::salvar('registros', $d);
        $existente ? $atualizados++ : $novos++;
    }
    return [$novos, $atualizados, $avisos];
}

function importar_pacotes(): void
{
    $dir = Store::dir('importar');
    $arquivos = glob($dir . '/*.json') ?: [];
    if (!$arquivos) {
        return;
    }
    sort($arquivos);
    $totalNovos = 0;
    $totalAtual = 0;
    $problemas = [];
    foreach ($arquivos as $f) {
        if (time() - filemtime($f) < 2) {
            continue;
        }
        $trab = $f . '.processando';
        if (!@rename($f, $trab)) {
            continue;
        }
        $nome = basename($f);
        try {
            $json = (string) file_get_contents($trab);
            $json = (string) preg_replace('/^\xEF\xBB\xBF/', '', $json);
            [$n, $a, $avisos] = aplicar_pacote(json_decode($json, true));
            $totalNovos += $n;
            $totalAtual += $a;
            foreach ($avisos as $av) {
                $problemas[] = $nome . ': ' . $av;
            }
            rename($trab, Store::dir('importar/feitos') . '/' . date('Ymd-His') . '-' . $nome);
        } catch (Throwable $ex) {
            $problemas[] = $nome . ': ' . $ex->getMessage();
            rename($trab, Store::dir('importar/erros') . '/' . date('Ymd-His') . '-' . $nome);
        }
    }
    if ($totalNovos || $totalAtual) {
        $msg = 'Importação concluída: ' . $totalNovos . ' novo(s), ' . $totalAtual . ' atualizado(s).';
        flash($msg . ($problemas ? ' Avisos: ' . implode('; ', $problemas) : ''), $problemas ? 'erro' : 'ok');
    } elseif ($problemas) {
        flash('Falha na importação: ' . implode('; ', $problemas), 'erro');
    }
}
