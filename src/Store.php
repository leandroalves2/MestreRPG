<?php
class Store
{
    public static function dir(string $colecao): string
    {
        $d = DADOS . '/' . $colecao;
        if (!is_dir($d)) {
            mkdir($d, 0777, true);
        }
        return $d;
    }

    public static function idValido($id): bool
    {
        return is_string($id) && preg_match('/^[a-f0-9]{12}$/', $id) === 1;
    }

    public static function ler(string $colecao, $id): ?array
    {
        if (!self::idValido($id)) {
            return null;
        }
        $f = self::dir($colecao) . '/' . $id . '.json';
        if (!is_file($f)) {
            return null;
        }
        $d = json_decode((string) file_get_contents($f), true);
        return is_array($d) ? $d : null;
    }

    public static function todos(string $colecao): array
    {
        $lista = [];
        foreach (glob(self::dir($colecao) . '/*.json') ?: [] as $f) {
            $d = json_decode((string) file_get_contents($f), true);
            if (is_array($d) && isset($d['id'])) {
                $lista[] = $d;
            }
        }
        usort($lista, fn($a, $b) => strcmp($b['atualizado'] ?? '', $a['atualizado'] ?? ''));
        return $lista;
    }

    public static function salvar(string $colecao, array $d, bool $tocar = true): array
    {
        $agora = date('c');
        if (empty($d['id']) || !self::idValido($d['id'])) {
            $d['id'] = bin2hex(random_bytes(6));
            $d['criado'] = $agora;
        }
        if ($tocar || empty($d['atualizado'])) {
            $d['atualizado'] = $agora;
        }
        $json = json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents(self::dir($colecao) . '/' . $d['id'] . '.json', $json, LOCK_EX);
        return $d;
    }

    public static function excluir(string $colecao, $id): void
    {
        if (self::idValido($id)) {
            $f = self::dir($colecao) . '/' . $id . '.json';
            if (is_file($f)) {
                unlink($f);
            }
        }
    }
}
