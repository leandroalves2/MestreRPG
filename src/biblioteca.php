<?php
function normalizar_busca(string $s): string
{
    static $de = ['á', 'à', 'â', 'ã', 'ä', 'é', 'è', 'ê', 'ë', 'í', 'ì', 'î', 'ï', 'ó', 'ò', 'ô', 'õ', 'ö', 'ú', 'ù', 'û', 'ü', 'ç', 'ñ'];
    static $para = ['a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'c', 'n'];
    return str_replace($de, $para, mb_strtolower($s));
}

function achar_livro(string $nome): ?array
{
    static $todos = null;
    if ($todos === null) {
        $todos = Store::todos('livros');
    }
    $n = normalizar_busca(trim($nome));
    if ($n === '') {
        return null;
    }
    foreach ($todos as $l) {
        if (normalizar_busca($l['titulo']) === $n) {
            return $l;
        }
    }
    foreach ($todos as $l) {
        if (str_contains(normalizar_busca($l['titulo']), $n)) {
            return $l;
        }
    }
    return null;
}

function livro_caminho(array $l): ?string
{
    $arq = $l['arq'] ?? '';
    if (!is_string($arq) || !preg_match('/^[a-f0-9]{12}\.pdf$/', $arq)) {
        return null;
    }
    $c = Store::dir('biblioteca') . '/' . $arq;
    return is_file($c) ? $c : null;
}

function livro_indice_caminho(string $id): string
{
    return Store::dir('biblioteca') . '/' . $id . '.indice.json';
}

function categoria_sugerida(string $nome): string
{
    $n = normalizar_busca($nome);
    if (preg_match('/monstro|monster|bestiar/', $n)) {
        return 'Livro dos Monstros';
    }
    if (preg_match('/mestre|master|dmg|dungeon/', $n)) {
        return 'Guia do Mestre';
    }
    if (preg_match('/jogador|player|phb/', $n)) {
        return 'Livro do Jogador';
    }
    return '';
}

function destacar_trecho(string $trecho, array $termos): string
{
    $norm = normalizar_busca($trecho);
    $tam = mb_strlen($trecho);
    if (mb_strlen($norm) !== $tam) {
        return e($trecho);
    }
    $marcas = array_fill(0, $tam, false);
    foreach ($termos as $t) {
        $len = mb_strlen($t);
        $off = 0;
        while ($len > 0 && ($p = mb_strpos($norm, $t, $off)) !== false) {
            for ($k = $p; $k < $p + $len; $k++) {
                $marcas[$k] = true;
            }
            $off = $p + $len;
        }
    }
    $out = '';
    $aberto = false;
    foreach (mb_str_split($trecho) as $i => $ch) {
        if ($marcas[$i] && !$aberto) {
            $out .= '<mark>';
            $aberto = true;
        } elseif (!$marcas[$i] && $aberto) {
            $out .= '</mark>';
            $aberto = false;
        }
        $out .= e($ch);
    }
    return $aberto ? $out . '</mark>' : $out;
}

function buscar_nos_livros(string $q, array $livros, int $limite = 150): array
{
    $termos = array_values(array_filter(preg_split('/\s+/u', normalizar_busca(trim($q))), fn($t) => $t !== ''));
    if (!$termos) {
        return [];
    }
    $relevantes = array_values(array_filter($termos, fn($t) => mb_strlen($t) >= 3)) ?: $termos;
    $frase = implode(' ', $termos);
    $res = [];
    foreach ($livros as $l) {
        $f = livro_indice_caminho($l['id']);
        if (!is_file($f)) {
            continue;
        }
        $idx = json_decode((string) file_get_contents($f), true);
        foreach ($idx['paginas'] ?? [] as $i => $texto) {
            $orig = trim((string) preg_replace('/\s+/u', ' ', (string) $texto));
            $n = normalizar_busca($orig);
            $pos = null;
            foreach ($relevantes as $t) {
                $p = mb_strpos($n, $t);
                if ($p === false) {
                    continue 2;
                }
                $pos = $pos ?? $p;
            }
            $contigua = count($termos) > 1 && str_contains($n, $frase);
            if ($contigua) {
                $pos = mb_strpos($n, $frase);
            }
            $trecho = mb_substr($orig, max(0, $pos - 70), 220);
            $res[] = [
                'livro' => $l,
                'pagina' => $i + 1,
                'html' => destacar_trecho($trecho, $contigua ? [$frase] : $relevantes),
                'q' => $contigua ? $frase : $relevantes[0],
            ];
            if (count($res) >= $limite) {
                break 2;
            }
        }
    }
    return $res;
}

function act_biblioteca(): void
{
    $livros = Store::todos('livros');
    usort($livros, fn($a, $b) => strnatcasecmp($a['titulo'], $b['titulo']));
    $q = trim((string) ($_GET['q'] ?? ''));
    $v = [
        'titulo' => 'Biblioteca de regras',
        'livros' => $livros,
        'q' => $q,
        'res' => $q !== '' ? buscar_nos_livros($q, $livros) : [],
    ];
    $c = Store::ler('campanhas', $_GET['c'] ?? '');
    if ($c) {
        $v['c'] = $c;
    }
    view('biblioteca', $v);
}

function act_biblioteca_enviar(): void
{
    $novos = [];
    foreach (Uploads::dividir($_FILES['livros'] ?? null) as $f) {
        $nome = (string) $f['name'][0];
        $tmp = $f['tmp_name'][0];
        if (Anexos::extensao($nome) !== 'pdf' || !is_uploaded_file($tmp)) {
            continue;
        }
        if (!str_contains((string) file_get_contents($tmp, false, null, 0, 1024), '%PDF-')) {
            continue;
        }
        $arq = bin2hex(random_bytes(6)) . '.pdf';
        if (!move_uploaded_file($tmp, Store::dir('biblioteca') . '/' . $arq)) {
            continue;
        }
        $l = Store::salvar('livros', [
            'titulo' => titulo_de_arquivo($nome),
            'categoria' => categoria_sugerida($nome),
            'arq' => $arq,
            'nome_original' => Anexos::limparNome($nome),
            'tam' => (int) $f['size'][0],
            'paginas' => 0,
            'indexado' => false,
        ]);
        $novos[] = $l['id'];
    }
    if (!$novos) {
        flash('Nenhum PDF válido foi enviado.', 'erro');
        redirecionar(['p' => 'biblioteca']);
    }
    flash(count($novos) . ' livro(s) adicionado(s). Indexando o texto para a busca...');
    $primeiro = array_shift($novos);
    redirecionar(['p' => 'biblioteca_indexar', 'id' => $primeiro, 'fila' => implode(',', $novos)]);
}

function act_biblioteca_editar(): void
{
    $l = Store::ler('livros', $_POST['id'] ?? '') ?? nao_encontrado();
    $titulo = mb_substr(trim((string) ($_POST['titulo'] ?? '')), 0, 120);
    if ($titulo !== '') {
        $l['titulo'] = $titulo;
    }
    $l['categoria'] = mb_substr(trim((string) ($_POST['categoria'] ?? '')), 0, 60);
    Store::salvar('livros', $l);
    flash('Livro atualizado.');
    redirecionar(['p' => 'biblioteca']);
}

function act_biblioteca_excluir(): void
{
    $l = Store::ler('livros', $_POST['id'] ?? '') ?? nao_encontrado();
    $c = livro_caminho($l);
    if ($c) {
        unlink($c);
    }
    $i = livro_indice_caminho($l['id']);
    if (is_file($i)) {
        unlink($i);
    }
    Store::excluir('livros', $l['id']);
    flash('Livro removido da biblioteca.');
    redirecionar(['p' => 'biblioteca']);
}

function act_biblioteca_indexar(): void
{
    $l = Store::ler('livros', $_GET['id'] ?? '') ?? nao_encontrado();
    view('biblioteca_indexar', [
        'titulo' => 'Indexando ' . $l['titulo'],
        'livro' => $l,
        'fila' => preg_replace('/[^a-f0-9,]/', '', (string) ($_GET['fila'] ?? '')),
    ]);
}

function act_biblioteca_indice(): void
{
    header('Content-Type: application/json; charset=utf-8');
    $l = Store::ler('livros', $_POST['id'] ?? '');
    $paginas = json_decode((string) ($_POST['paginas'] ?? ''), true);
    if (!$l || !is_array($paginas) || count($paginas) > 6000) {
        http_response_code(400);
        echo json_encode(['ok' => false]);
        exit;
    }
    $limpas = array_map(fn($t) => mb_substr((string) $t, 0, 40000), array_values($paginas));
    file_put_contents(
        livro_indice_caminho($l['id']),
        json_encode(['paginas' => $limpas], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );
    $l['paginas'] = count($limpas);
    $l['indexado'] = true;
    Store::salvar('livros', $l);
    flash('"' . $l['titulo'] . '" indexado: ' . count($limpas) . ' páginas.');
    echo json_encode(['ok' => true, 'paginas' => count($limpas)]);
    exit;
}

function act_leitor(): void
{
    $l = Store::ler('livros', $_GET['id'] ?? '') ?? nao_encontrado();
    $pag = max(0, (int) ($_GET['pag'] ?? 0));
    $q = trim((string) ($_GET['q'] ?? ''));
    require VIEWS . '/leitor.php';
    exit;
}

function act_livro_pdf(): void
{
    session_write_close();
    $l = Store::ler('livros', $_GET['id'] ?? '');
    $c = $l ? livro_caminho($l) : null;
    if (!$c) {
        http_response_code(404);
        exit;
    }
    $tam = filesize($c);
    $ini = 0;
    $fim = $tam - 1;
    header('Content-Type: application/pdf');
    header('Accept-Ranges: bytes');
    header('Content-Disposition: inline; filename="' . preg_replace('/[^\x20-\x7e]/', '_', $l['nome_original'] ?? 'livro.pdf') . '"');
    header('X-Content-Type-Options: nosniff');
    if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $m)) {
        if ($m[1] !== '') {
            $ini = (int) $m[1];
            if ($m[2] !== '') {
                $fim = min((int) $m[2], $tam - 1);
            }
        } elseif ($m[2] !== '') {
            $ini = max(0, $tam - (int) $m[2]);
        }
        if ($ini > $fim || $ini >= $tam) {
            http_response_code(416);
            header('Content-Range: bytes */' . $tam);
            exit;
        }
        http_response_code(206);
        header('Content-Range: bytes ' . $ini . '-' . $fim . '/' . $tam);
    }
    header('Content-Length: ' . ($fim - $ini + 1));
    $h = fopen($c, 'rb');
    fseek($h, $ini);
    $resta = $fim - $ini + 1;
    while ($resta > 0 && !feof($h)) {
        $bloco = fread($h, min(1048576, $resta));
        if ($bloco === false || $bloco === '') {
            break;
        }
        echo $bloco;
        $resta -= strlen($bloco);
        flush();
    }
    fclose($h);
    exit;
}
