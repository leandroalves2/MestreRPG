<?php
class Imagens
{
    private const EXT = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    private const MIME = [
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
    ];

    public static function caminho($arq): ?string
    {
        if (!is_string($arq) || !preg_match('/^[a-f0-9]{12}\.(jpg|png|gif|webp)$/', $arq)) {
            return null;
        }
        $c = Store::dir('imagens') . '/' . $arq;
        return is_file($c) ? $c : null;
    }

    public static function mime(string $arq): string
    {
        return self::MIME[pathinfo($arq, PATHINFO_EXTENSION)] ?? 'application/octet-stream';
    }

    public static function receber($files): array
    {
        $salvos = [];
        if (!is_array($files) || !isset($files['name']) || !is_array($files['name'])) {
            return $salvos;
        }
        foreach ($files['name'] as $i => $nome) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }
            $tmp = $files['tmp_name'][$i];
            if (!is_uploaded_file($tmp)) {
                continue;
            }
            $info = @getimagesize($tmp);
            $ext = $info ? (self::EXT[$info['mime']] ?? null) : null;
            if (!$ext) {
                continue;
            }
            $arq = bin2hex(random_bytes(6)) . '.' . $ext;
            if (move_uploaded_file($tmp, Store::dir('imagens') . '/' . $arq)) {
                $salvos[] = $arq;
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
        $mantidas = [];
        foreach ($atuais as $arq) {
            if (in_array($arq, $remover, true)) {
                self::remover($arq);
            } else {
                $mantidas[] = $arq;
            }
        }
        return $mantidas;
    }
}
