<?php
namespace App\Support;

final class JsonHelper
{
    public static function read(string $path, array $default = []): array
    {
        if (!is_file($path) || filesize($path) === 0) {
            return $default;
        }
        $raw = file_get_contents($path);
        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $default;
        }
        return $data;
    }

    public static function write(string $path, array $data): void
    {
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
            throw new \RuntimeException("Cannot create dir: $dir");
        }

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($json === false) {
            throw new \RuntimeException('json_encode 실패: ' . json_last_error_msg());
        }

        // 임시파일 → rename 으로 원자적 저장까지 고려 가능
        $tmp = $path . '.tmp';
        file_put_contents($tmp, $json, LOCK_EX);
        rename($tmp, $path);
    }
}
