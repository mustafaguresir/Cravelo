<?php
declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Storage\JsonStorageHelper;
use RuntimeException;

/**
 * Gereksinim Eşleştirmesi:
 * - Aşama 2 Kuralı: JSON ile veri saklama, flock(LOCK_EX) ve atomik temp dosya değişimi
 * - Sınır Durumu: Bozuk JSON ayrıştırma hatası yönetimi
 */
final class JsonStorageFlockTest extends TestCase
{
    private string $testFile;

    protected function setUp(): void
    {
        $this->testFile = sys_get_temp_dir() . '/cravelo_test_' . uniqid() . '.json';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testFile)) {
            @unlink($this->testFile);
        }
        if (file_exists($this->testFile . '.lock')) {
            @unlink($this->testFile . '.lock');
        }
    }

    public function testAtomicWriteAndReadWithFlock(): void
    {
        $sampleData = [
            ['id' => 1, 'name' => 'Pizza', 'price' => 250.5],
            ['id' => 2, 'name' => 'Burger', 'price' => 240.0]
        ];

        $written = JsonStorageHelper::write($this->testFile, $sampleData);
        $this->assertTrue($written);
        $this->assertFileExists($this->testFile);

        $readData = JsonStorageHelper::read($this->testFile);
        $this->assertCount(2, $readData);
        $this->assertSame('Pizza', $readData[0]['name']);
        $this->assertSame(240.0, (float)$readData[1]['price']);
    }

    public function testCorruptedJsonThrowsRuntimeException(): void
    {
        file_put_contents($this->testFile, "{ bozuk_json_formati: eksik_parantez ");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("JSON ayrıştırma hatası");

        JsonStorageHelper::read($this->testFile);
    }

    public function testFlockExclusiveLockRejectsConcurrentNonBlockingLock(): void
    {
        // Dosyayı oluştur
        file_put_contents($this->testFile, json_encode([['id' => 1]]));

        // 1. Tanıtıcı özel kilit alır (LOCK_EX)
        $fp1 = fopen($this->testFile, 'c+');
        $this->assertIsResource($fp1);
        $lock1 = flock($fp1, LOCK_EX);
        $this->assertTrue($lock1, "İlk tutamaç LOCK_EX kilidi alabilmelidir.");

        // 2. Tanıtıcı eşzamanlı olarak kilit almayı dener (LOCK_EX | LOCK_NB)
        $fp2 = fopen($this->testFile, 'c+');
        $this->assertIsResource($fp2);
        $lock2 = flock($fp2, LOCK_EX | LOCK_NB);
        $this->assertFalse($lock2, "Dosya LOCK_EX ile kilitliyken ikinci tutamağın LOCK_EX | LOCK_NB çağrısı engellenmelidir.");

        // 1. Tanıtıcı kilidi serbest bırakır (LOCK_UN)
        flock($fp1, LOCK_UN);
        fclose($fp1);

        // Kilit çözüldükten sonra 2. Tanıtıcı kilit alabilmelidir
        $lock2After = flock($fp2, LOCK_EX | LOCK_NB);
        $this->assertTrue($lock2After, "İlk kilit serbest bırakıldığında ikinci tutamaç kilidi alabilmelidir.");

        flock($fp2, LOCK_UN);
        fclose($fp2);
    }

    public function testAtomicWritePreservesOriginalFileIfWriteFails(): void
    {
        $originalData = [['id' => 10, 'name' => 'Orijinal Güvenli Veri']];
        JsonStorageHelper::write($this->testFile, $originalData);

        // JSON olarak serileştirilemeyen geçersiz girdi (kaynak tanıtıcısı / resource)
        $invalidResource = fopen('php://memory', 'r');
        $corruptAttempt = ['resource' => $invalidResource];

        try {
            JsonStorageHelper::write($this->testFile, $corruptAttempt);
            $this->fail("Geçersiz JSON verisinde hata fırlatılmalıdır.");
        } catch (\Throwable $e) {
            // Beklenen hata
        } finally {
            if (is_resource($invalidResource)) {
                fclose($invalidResource);
            }
        }

        // Hata oluşmasına rağmen orijinal dosya kesinlikle bozulmamış olmalıdır (Atomiklik Güvencesi)
        $persisted = JsonStorageHelper::read($this->testFile);
        $this->assertCount(1, $persisted);
        $this->assertSame('Orijinal Güvenli Veri', $persisted[0]['name']);
    }
}

