<?php
function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function url(array $p = []): string
{
    return '?' . http_build_query($p);
}

function redirecionar(array $p): void
{
    header('Location: ' . url($p));
    exit;
}

function flash(string $msg, string $tipo = 'ok'): void
{
    $_SESSION['flash'] = ['m' => $msg, 't' => $tipo];
}

function pegar_flash(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_validar(): void
{
    if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        flash('Os arquivos enviados excedem o limite do servidor. Inicie o sistema pelo iniciar.bat.', 'erro');
        redirecionar(['p' => 'home']);
    }
    $t = $_POST['_csrf'] ?? '';
    if (!is_string($t) || !hash_equals(csrf_token(), $t)) {
        http_response_code(400);
        exit('Token inválido. Volte e tente novamente.');
    }
}

function tipos(): array
{
    static $t = null;
    if ($t === null) {
        $t = require RAIZ . '/src/tipos.php';
    }
    return $t;
}

function campo(string $n, string $r, string $t = 'texto', array $x = []): array
{
    return array_merge(['n' => $n, 'r' => $r, 't' => $t], $x);
}

function dt(?string $iso): string
{
    return $iso ? date('d/m/Y H:i', strtotime($iso)) : '';
}

function data_br(?string $d): string
{
    $ts = $d ? strtotime($d) : false;
    return $ts ? date('d/m/Y', $ts) : '';
}

function imagem_url(string $arq): string
{
    return url(['p' => 'img', 'f' => $arq]);
}

function tags_lista(string $s): array
{
    $p = array_map('trim', explode(',', $s));
    return array_values(array_unique(array_filter($p, fn($x) => $x !== '')));
}

function indice_titulos(string $campanha): array
{
    static $cache = [];
    if (!isset($cache[$campanha])) {
        $idx = [];
        foreach (Store::todos('registros') as $r) {
            if ($r['campanha_id'] === $campanha) {
                $idx[mb_strtolower($r['titulo'])] = $r['id'];
            }
        }
        $cache[$campanha] = $idx;
    }
    return $cache[$campanha];
}

function formatar_inline(string $s, ?string $camp): string
{
    $s = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $s);
    $s = preg_replace('/(?<!\*)\*(?!\s)(.+?)(?<!\s)\*(?!\*)/u', '<em>$1</em>', $s);
    $s = preg_replace_callback('/\{\{(.+?)\}\}/u', function ($m) {
        $partes = array_map('trim', explode('|', html_entity_decode($m[1], ENT_QUOTES, 'UTF-8')));
        $livro = achar_livro($partes[0]);
        if (!$livro) {
            return '<span class="wiki-falta" title="Livro não encontrado na biblioteca">' . e($partes[0]) . '</span>';
        }
        $pag = isset($partes[1]) ? (int) preg_replace('/\D+/', '', $partes[1]) : 0;
        $params = ['p' => 'leitor', 'id' => $livro['id']];
        if ($pag > 0) {
            $params['pag'] = $pag;
        }
        if (!empty($partes[2])) {
            $params['q'] = $partes[2];
        }
        $rotulo = '📖 ' . $livro['titulo'] . ($pag > 0 ? ' p. ' . $pag : '');
        return '<a class="livro-link" target="_blank" href="' . e(url($params)) . '">' . e($rotulo) . '</a>';
    }, $s);
    return preg_replace_callback('/\[\[(.+?)\]\]/u', function ($m) use ($camp) {
        $nome = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
        $id = $camp ? (indice_titulos($camp)[mb_strtolower($nome)] ?? null) : null;
        if ($id) {
            return '<a class="wiki" href="' . e(url(['p' => 'reg_ver', 'id' => $id])) . '">' . $m[1] . '</a>';
        }
        return '<span class="wiki-falta" title="Nenhum registro com este título">' . $m[1] . '</span>';
    }, $s);
}

function render_texto(?string $t, ?string $camp = null): string
{
    $t = trim((string) $t);
    if ($t === '') {
        return '';
    }
    $out = '';
    $lista = false;
    $cit = false;
    foreach (preg_split('/\R/', e($t)) as $l) {
        if (preg_match('/^\s*&gt;\s?(.*)$/u', $l, $m)) {
            if ($lista) {
                $out .= '</ul>';
                $lista = false;
            }
            if (!$cit) {
                $out .= '<blockquote>';
                $cit = true;
            }
            $cls = str_starts_with(trim($m[1]), '—') ? ' class="autor"' : '';
            $out .= '<p' . $cls . '>' . formatar_inline($m[1], $camp) . '</p>';
            continue;
        }
        if ($cit) {
            $out .= '</blockquote>';
            $cit = false;
        }
        if (preg_match('/^\s*[-*]\s+(.*)$/u', $l, $m)) {
            if (!$lista) {
                $out .= '<ul>';
                $lista = true;
            }
            $out .= '<li>' . formatar_inline($m[1], $camp) . '</li>';
            continue;
        }
        if ($lista) {
            $out .= '</ul>';
            $lista = false;
        }
        if (preg_match('/^(#{1,3})\s+(.*)$/u', $l, $m)) {
            $n = strlen($m[1]) + 2;
            $out .= "<h$n>" . formatar_inline($m[2], $camp) . "</h$n>";
        } elseif (trim($l) !== '') {
            $out .= '<p>' . formatar_inline($l, $camp) . '</p>';
        }
    }
    if ($lista) {
        $out .= '</ul>';
    }
    if ($cit) {
        $out .= '</blockquote>';
    }
    return $out;
}

function texto_plano(array $campos): string
{
    $partes = [];
    array_walk_recursive($campos, function ($v) use (&$partes) {
        if (is_string($v)) {
            $partes[] = $v;
        }
    });
    return implode(' ', $partes);
}

function subtitulo(array $r): string
{
    $def = tipos()[$r['tipo']] ?? null;
    if (!$def) {
        return '';
    }
    $p = [];
    foreach ($def['subtitulo'] ?? [] as $n) {
        $v = $r['campos'][$n] ?? '';
        if ($v === '') {
            continue;
        }
        foreach ($def['campos'] as $f) {
            if ($f['n'] === $n) {
                $v = $f['t'] === 'data' ? data_br($v) : ($f['pre'] ?? '') . $v;
            }
        }
        $p[] = is_string($v) ? texto_cartao($v) : $v;
    }
    return implode(' · ', $p);
}

function coletar_campos(string $tipo, array $in): array
{
    $out = [];
    foreach (tipos()[$tipo]['campos'] as $f) {
        $v = $in[$f['n']] ?? null;
        if ($f['t'] === 'pares') {
            $lista = [];
            $ns = is_array($v['n'] ?? null) ? $v['n'] : [];
            $vs = is_array($v['v'] ?? null) ? $v['v'] : [];
            foreach ($ns as $i => $n) {
                $n = trim((string) $n);
                $val = trim((string) ($vs[$i] ?? ''));
                if ($n !== '' || $val !== '') {
                    $lista[] = ['n' => $n, 'v' => $val];
                }
            }
            $out[$f['n']] = $lista;
        } else {
            $out[$f['n']] = is_string($v) ? trim($v) : '';
        }
    }
    return $out;
}

function tamanho_legivel(int $b): string
{
    if ($b >= 1048576) {
        return number_format($b / 1048576, 1, ',', '.') . ' MB';
    }
    if ($b >= 1024) {
        return number_format($b / 1024, 0, ',', '.') . ' KB';
    }
    return $b . ' B';
}

function titulo_de_arquivo(string $nome): string
{
    $t = trim((string) preg_replace('/_+/', ' ', pathinfo($nome, PATHINFO_FILENAME)));
    return $t !== '' ? mb_substr($t, 0, 120) : 'Sem título';
}

function ler_texto(string $caminho): ?string
{
    if (!is_file($caminho) || filesize($caminho) > 512000) {
        return null;
    }
    $t = (string) file_get_contents($caminho);
    $t = preg_replace('/^\xEF\xBB\xBF/', '', $t);
    if (!mb_check_encoding($t, 'UTF-8')) {
        $t = mb_convert_encoding($t, 'UTF-8', 'Windows-1252');
    }
    return trim($t);
}

function registros_do_tipo(array $regs, string $tipo): array
{
    $def = tipos()[$tipo];
    $lista = array_values(array_filter($regs, fn($r) => $r['tipo'] === $tipo));
    $porNumero = ($def['ordem'] ?? '') === 'numero';
    usort($lista, function ($a, $b) use ($porNumero) {
        $oa = isset($a['ordem']) ? (int) $a['ordem'] : null;
        $ob = isset($b['ordem']) ? (int) $b['ordem'] : null;
        if ($oa !== null && $ob !== null && $oa !== $ob) {
            return $oa <=> $ob;
        }
        if ($oa !== null && $ob === null) {
            return -1;
        }
        if ($oa === null && $ob !== null) {
            return 1;
        }
        if ($porNumero) {
            return (int) ($b['campos']['numero'] ?? 0) <=> (int) ($a['campos']['numero'] ?? 0);
        }
        return strnatcasecmp($a['titulo'], $b['titulo']);
    });
    return $lista;
}

function agrupar_por_pasta(array $lista): array
{
    $g = [];
    foreach ($lista as $r) {
        $g[(string) ($r['pasta'] ?? '')][] = $r;
    }
    $min = [];
    foreach ($g as $pasta => $itens) {
        $os = array_filter(array_map(fn($r) => isset($r['ordem']) ? (int) $r['ordem'] : null, $itens), fn($o) => $o !== null);
        $min[(string) $pasta] = $os ? min($os) : null;
    }
    uksort($g, function ($a, $b) use ($min) {
        $a = (string) $a;
        $b = (string) $b;
        if ($a === '' || $b === '') {
            return $a === '' ? ($b === '' ? 0 : -1) : 1;
        }
        if ($min[$a] !== null && $min[$b] !== null && $min[$a] !== $min[$b]) {
            return $min[$a] <=> $min[$b];
        }
        return strnatcasecmp($a, $b);
    });
    return $g;
}

function arvore_pastas(array $lista): array
{
    $raiz = ['itens' => [], 'filhos' => []];
    foreach ($lista as $r) {
        $caminho = trim((string) ($r['pasta'] ?? ''));
        $no = &$raiz;
        if ($caminho !== '') {
            foreach (array_map('trim', explode('/', $caminho)) as $parte) {
                if ($parte === '') {
                    continue;
                }
                if (!isset($no['filhos'][$parte])) {
                    $no['filhos'][$parte] = ['itens' => [], 'filhos' => []];
                }
                $no = &$no['filhos'][$parte];
            }
        }
        $no['itens'][] = $r;
        unset($no);
    }
    return $raiz;
}

function no_ordem_minima(array $no): ?int
{
    $m = null;
    foreach ($no['itens'] as $r) {
        if (isset($r['ordem'])) {
            $o = (int) $r['ordem'];
            $m = $m === null ? $o : min($m, $o);
        }
    }
    foreach ($no['filhos'] as $f) {
        $o = no_ordem_minima($f);
        if ($o !== null) {
            $m = $m === null ? $o : min($m, $o);
        }
    }
    return $m;
}

function no_total(array $no): int
{
    $n = count($no['itens']);
    foreach ($no['filhos'] as $f) {
        $n += no_total($f);
    }
    return $n;
}

function no_contem(array $no, string $id): bool
{
    foreach ($no['itens'] as $r) {
        if ($r['id'] === $id) {
            return true;
        }
    }
    foreach ($no['filhos'] as $f) {
        if (no_contem($f, $id)) {
            return true;
        }
    }
    return false;
}

function html_lateral_no(array $no, string $tipo, string $caminho, int $nivel, string $regAtual): string
{
    $h = '';
    foreach ($no['itens'] as $r) {
        $h .= '<a class="lateral-reg ' . ($r['id'] === $regAtual ? 'ativa' : '') . '" data-id="' . e($r['id']) . '" draggable="true" style="--n:' . $nivel . '" href="'
            . e(url(['p' => 'reg_ver', 'id' => $r['id']])) . '">' . e($r['titulo']) . '</a>';
    }
    $filhos = $no['filhos'];
    $min = [];
    foreach ($filhos as $nome => $f) {
        $min[$nome] = no_ordem_minima($f);
    }
    uksort($filhos, function ($a, $b) use ($min) {
        if ($min[$a] !== null && $min[$b] !== null && $min[$a] !== $min[$b]) {
            return $min[$a] <=> $min[$b];
        }
        return strnatcasecmp($a, $b);
    });
    foreach ($filhos as $nome => $f) {
        $cam = $caminho === '' ? $nome : $caminho . ' / ' . $nome;
        $h .= '<details class="lateral-sub" data-pasta="' . e($tipo . '|' . $cam) . '" style="--n:' . $nivel . '">'
            . '<summary><span class="rotulo">📁 ' . e($nome) . '</span><small>' . no_total($f) . '</small><span class="mais"></span></summary>'
            . html_lateral_no($f, $tipo, $cam, $nivel + 1, $regAtual)
            . '</details>';
    }
    return $h;
}

function texto_cartao(string $t): string
{
    $t = preg_replace('/\[\[(.+?)\]\]/u', '$1', $t);
    $t = preg_replace('/\{\{([^|}]*)\|([^|}]*)\|?([^}]*)\}\}/u', '$1', $t);
    return trim((string) $t);
}
