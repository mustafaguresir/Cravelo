<?php
declare(strict_types=1);

namespace App\Storage;

use RuntimeException;

/**
 * Aşama 2 Şartname Kuralı:
 * "Eşzamanlı yazma ihtimali bulunan dosyalarda dosya kilidi (flock) kullanın.
 *  Veriyi doğrudan asıl dosyaya yazmak yerine geçici dosyaya yazıp ardından
 *  dosyayı güvenli biçimde (atomic rename) değiştirin."
 */
final class JsonStorageHelper
{
    /**
     * JSON dosyasını paylaşımlı kilit (LOCK_SH) altında güvenle okur.
     */
    public static function read(string $filePath): array
    {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            return [];
        }

        $lockFilePath = $filePath . '.lock';
        $fp = fopen($lockFilePath, 'c+');
        if ($fp === false) {
            throw new RuntimeException("Kilit dosyası açılamadı: {$lockFilePath}");
        }

        flock($fp, LOCK_SH);

        try {
            if (!file_exists($filePath)) {
                return [];
            }

            $readFp = fopen($filePath, 'rb');
            if ($readFp === false) {
                throw new RuntimeException("Dosya okumak için açılamadı: {$filePath}");
            }

            $contents = stream_get_contents($readFp);
            fclose($readFp);

            if ($contents === false || trim($contents) === '') {
                return [];
            }

            $decoded = json_decode($contents, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException("JSON ayrıştırma hatası ({$filePath}): " . json_last_error_msg());
            }

            return is_array($decoded) ? $decoded : [];
        } finally {
            if (is_resource($fp)) {
                flock($fp, LOCK_UN);
                fclose($fp);
            }
        }
    }

    /**
     * Tek bir LOCK_EX kilidi altında atomik oku-değiştir-yaz (read-modify-write) işlemi gerçekleştirir.
     * Eşzamanlı isteklerde kayıp güncelleme (lost update) ve çakışan ID üretimini engeller.
     *
     * @param callable(array<int|string, mixed>): array{data: array<int|string, mixed>, result?: mixed, write?: bool} $mutator
     */
    public static function update(string $filePath, callable $mutator): mixed
    {
        $dir = dirname($filePath);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException("Hedef klasör oluşturulamadı: {$dir}");
        }

        $lockFilePath = $filePath . '.lock';
        $fp = fopen($lockFilePath, 'c+');
        if ($fp === false) {
            throw new RuntimeException("Kilit dosyası açılamadı: {$lockFilePath}");
        }

        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            throw new RuntimeException("Dosya kilidi (flock LOCK_EX) alınamadı: {$lockFilePath}");
        }

        try {
            $records = [];
            if (file_exists($filePath)) {
                $readFp = fopen($filePath, 'rb');
                if ($readFp === false) {
                    throw new RuntimeException("Dosya okumak için açılamadı: {$filePath}");
                }
                $contents = stream_get_contents($readFp);
                fclose($readFp);

                if ($contents !== false && trim($contents) !== '') {
                    $decoded = json_decode($contents, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new RuntimeException("JSON ayrıştırma hatası ({$filePath}): " . json_last_error_msg());
                    }
                    if (is_array($decoded)) {
                        $records = $decoded;
                    }
                }
            }

            $mutation = $mutator($records);
            $shouldWrite = !array_key_exists('write', $mutation) || (bool)$mutation['write'];

            if ($shouldWrite) {
                $nextData = $mutation['data'];
                $jsonString = json_encode($nextData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

                $tempFilePath = tempnam($dir, 'crv_');
                if ($tempFilePath === false) {
                    throw new RuntimeException("Geçici dosya oluşturulamadı: {$dir}");
                }

                $tempFp = fopen($tempFilePath, 'wb');
                if ($tempFp === false) {
                    @unlink($tempFilePath);
                    throw new RuntimeException("Geçici dosya açılamadı: {$tempFilePath}");
                }

                fwrite($tempFp, $jsonString);
                fflush($tempFp);
                fclose($tempFp);

                self::atomicReplace($tempFilePath, $filePath);
            }

            return $mutation['result'] ?? true;
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    /**
     * Çoklu JSON dosyasını ilgilendiren kritik bölgelerde (örn. sipariş + kurye atama)
     * ortak bir LOCK_EX dosya kilidi altında işlem yürütür.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public static function withLock(string $lockFilePath, callable $callback): mixed
    {
        $dir = dirname($lockFilePath);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException("Kilit klasörü oluşturulamadı: {$dir}");
        }

        $fp = fopen($lockFilePath, 'c+');
        if ($fp === false) {
            throw new RuntimeException("Kilit dosyası açılamadı: {$lockFilePath}");
        }

        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            throw new RuntimeException("Dosya kilidi (flock LOCK_EX) alınamadı: {$lockFilePath}");
        }

        try {
            return $callback();
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    /**
     * Geçici dosyayı asıl dosya ile atomik olarak değiştirir.
     * Windows üzerinde mevcut dosya .bak yedeği alınarak güvenli takas yapılır; rename başarısız olursa .bak geri yüklenir.
     */
    private static function atomicReplace(string $tempFilePath, string $filePath): void
    {
        if (DIRECTORY_SEPARATOR === '\\' && file_exists($filePath)) {
            $backupPath = $filePath . '.bak';
            $hasBackup = @copy($filePath, $backupPath);
            @unlink($filePath);

            if (!@rename($tempFilePath, $filePath)) {
                @unlink($tempFilePath);
                if ($hasBackup && file_exists($backupPath)) {
                    @rename($backupPath, $filePath);
                }
                throw new RuntimeException("Atomik dosya değiştirme (rename) başarısız oldu: {$filePath}");
            }

            if ($hasBackup && file_exists($backupPath)) {
                @unlink($backupPath);
            }
            return;
        }

        if (!rename($tempFilePath, $filePath)) {
            @unlink($tempFilePath);
            throw new RuntimeException("Atomik dosya değiştirme (rename) başarısız oldu: {$filePath}");
        }
    }

    /**
     * Şartname Planı Bölüm 3.D:
     * flock(LOCK_EX) + tempnam + rename Atomik Yardımcı Metodu
     */
    public static function saveWithLock(string $filePath, array $data): bool
    {
        return self::write($filePath, $data);
    }

    /**
     * JSON dosyasını özel kilit (LOCK_EX), tempnam ve atomik rename ile kaydeder.
     */
    public static function write(string $filePath, array $data): bool
    {
        $dir = dirname($filePath);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException("Hedef klasör oluşturulamadı: {$dir}");
        }

        $lockFilePath = $filePath . '.lock';
        $fp = fopen($lockFilePath, 'c+');
        if ($fp === false) {
            throw new RuntimeException("Kilit dosyası açılamadı: {$lockFilePath}");
        }

        // 1. Hedef/Kilit dosyasında özel kilit al (Exclusive Lock - LOCK_EX)
        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            throw new RuntimeException("Dosya kilidi (flock LOCK_EX) alınamadı: {$lockFilePath}");
        }

        try {
            $jsonString = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            // 2. Geçici dosyaya yaz (tempnam)
            $tempFilePath = tempnam($dir, 'crv_');
            if ($tempFilePath === false) {
                throw new RuntimeException("Geçici dosya oluşturulamadı: {$dir}");
            }

            $tempFp = fopen($tempFilePath, 'wb');
            if ($tempFp === false) {
                @unlink($tempFilePath);
                throw new RuntimeException("Geçici dosya açılamadı: {$tempFilePath}");
            }

            // 3. Yaz ve tamponu boşalt
            fwrite($tempFp, $jsonString);
            fflush($tempFp);
            fclose($tempFp);

            // 4. Atomik yeniden adlandırma (Atomic Rename + Windows .bak koruması)
            self::atomicReplace($tempFilePath, $filePath);

            return true;
        } finally {
            // 5. Kilidi çöz ve kapat
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }
}
