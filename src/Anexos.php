<?php
class Anexos
{
    private const PERMITIDAS = [
        'pdf', 'doc', 'docx', 'odt', 'rtf', 'txt', 'md', 'csv',
        'xls', 'xlsx', 'ods', 'ppt', 'pptx', 'json', 'zip',
    ];

    private const INLINE = ['pdf', 'txt', 'md', 'csv'];

    public static function caminho($arq): ?string
    {
        if (!is_string($arq) || !preg_match('/^[a-f0-9]{12}\.[a-z0-9]{2,5}$/', $arq)) {
            return null;
        }
        $c = Store::dir('arquivos') . '/' . $arq;
        return is_file($c) ? $c : null;
    }

    public static function extensao(string $nome): string
    {
        return strtolower(pathinfo($nome, PATHINFO_EXTENSION));
    }

    public static function inline(string $arq): bool
    {
        return in_array(self::extensao($arq), self::INLINE, true);
    }

    public static function mime(string $arq): string
    {
        $ext = self::extensao($arq);
        if ($ext === 'pdf') {
            return 'application/pdf';
        }
        if (in_array($ext, ['txt', 'md', 'csv'], true)) {
            return 'text/plain; charset=utf-8';
        }
        return 'application/octet-stream';
    }

    public static function icone(string $ext): string
    {
        return match ($ext) {
            'pdf' => '📕',
            'doc', 'docx', 'odt', 'rtf' => '📘',
            'xls', 'xlsx', 'ods', 'csv' => '📗',
            'ppt', 'pptx' => '📙',
            'zip' => '🗜️',
            default => '📄',
        };
    }

    public static function limparNome(string $nome): string
    {
        $nome = basename(str_replace('\\', '/', $nome));
        $nome = preg_replace('/[\x00-\x1f"\/\\\\]/u', '_', $nome);
        $nome = trim((string) $nome);
        return $nome === '' ? 'arquivo' : mb_substr($nome, 0, 120);
    }

    public static function receber($files): array
    {
        $salvos = [];
        if (!Uploads::valido($files)) {
            return $salvos;
        }
        foreach ($files['name'] as $i => $nome) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }
            $tmp = $files['tmp_name'][$i];
            $ext = self::extensao((string) $nome);
            if (!is_uploaded_file($tmp) || !in_array($ext, self::PERMITIDAS, true) || @getimagesize($tmp)) {
                continue;
            }
            $arq = bin2hex(random_bytes(6)) . '.' . $ext;
            if (move_uploaded_file($tmp, Store::dir('arquivos') . '/' . $arq)) {
                $salvos[] = [
                    'arq' => $arq,
                    'nome' => self::limparNome((string) $nome),
                    'tam' => (int) ($files['size'][$i] ?? 0),
                ];
            }
        }
        return $salvos;
    }

    public static function remover(string $arq): void
    {
        $c = self::caminho($arq);
        if ($c) {
            unlink($c);
        }
    }

    public static function manter(array $atuais, $remover): array
    {
        $remover = is_array($remover) ? $remover : [];
        $mantidos = [];
        foreach ($atuais as $a) {
            if (in_array($a['arq'], $remover, true)) {
                self::remover($a['arq']);
            } else {
                $mantidos[] = $a;
            }
        }
        return $mantidos;
    }
}
