<?php
class Uploads
{
    public static function valido($files): bool
    {
        return is_array($files) && isset($files['name']) && is_array($files['name']);
    }

    public static function enviados($files): int
    {
        if (!self::valido($files)) {
            return 0;
        }
        $n = 0;
        foreach ($files['error'] as $erro) {
            if ($erro === UPLOAD_ERR_OK) {
                $n++;
            }
        }
        return $n;
    }

    public static function dividir($files): array
    {
        $lista = [];
        if (!self::valido($files)) {
            return $lista;
        }
        foreach ($files['name'] as $i => $nome) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }
            $lista[] = [
                'name' => [$nome],
                'tmp_name' => [$files['tmp_name'][$i]],
                'error' => [UPLOAD_ERR_OK],
                'size' => [$files['size'][$i] ?? 0],
            ];
        }
        return $lista;
    }
}
