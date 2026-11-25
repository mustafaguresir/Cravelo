<?php
declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Controller\AuthController;
use App\Model\User;
use App\Enum\UserRole;
use App\Service\AuthService;
use App\Repository\InMemory\InMemoryUserRepository;
use InvalidArgumentException;
use RuntimeException;

/**
 * Gereksinim Eşleştirmesi:
 * - FR-13: Kullanıcı kaydı, girişi ve password_hash
 * - NFR-02 / Aşama 2 Güvenlik: IDOR (Insecure Direct Object Reference) ve Sahiplik kontrolü
 * - Sınır Durumları: Kısa kullanıcı adı / geçersiz parola
 */
final class SecurityAndAuthTest extends TestCase
{
    private AuthService $authService;
    private InMemoryUserRepository $userRepo;

    protected function setUp(): void
    {
        $this->userRepo = new InMemoryUserRepository();
        $this->authService = new AuthService($this->userRepo);
    }

    public function testUserPasswordHashingAndVerification(): void
    {
        $user = $this->authService->register('test_user', 'guvenliSifre123', 'Test Kullanıcı', '05559998877', UserRole::CUSTOMER);

        $this->assertNotEmpty($user->passwordHash);
        $this->assertNotSame('guvenliSifre123', $user->passwordHash);
        $this->assertTrue(password_verify('guvenliSifre123', $user->passwordHash));

        // Başarılı giriş
        $loggedIn = $this->authService->login('test_user', 'guvenliSifre123');
        $this->assertNotNull($loggedIn);
        $this->assertSame('test_user', $loggedIn->username);

        // Hatalı parola ile giriş
        $failed = $this->authService->login('test_user', 'yanlisSifre');
        $this->assertNull($failed);
    }

    public function testUsernameShorterThanThreeCharsThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Kullanıcı adı en az 3 karakter olmalıdır.");

        // 2 karakterli kullanıcı adı + geçerli 8 karakterli parola (Bağımsız Kullanıcı Adı Sınır Testi)
        $this->authService->register('ab', 'guvenliSifre123', 'Kısa Ad', '05551112233');
    }

    public function testUsernameWithThreeCharsIsAccepted(): void
    {
        // 3 karakterli tam sınır kullanıcı adı kabul edilmelidir
        $user = $this->authService->register('ali', 'guvenliSifre123', 'Ali Kaya', '05551112233');
        $this->assertSame('ali', $user->username);
    }

    public function testPasswordShorterThanSixCharsThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Parola en az 6 karakter olmalıdır.");

        // Geçerli kullanıcı adı + 5 karakterli kısa parola (Bağımsız Parola Sınır Testi)
        $this->authService->register('gecerliKullanici', '12345', 'Kısa Şifre', '05551112233');
    }

    public function testPasswordWithSixCharsIsAccepted(): void
    {
        // 6 karakterli tam sınır parola kabul edilmelidir
        $user = $this->authService->register('mehmet', '123456', 'Mehmet Demir', '05551112233');
        $this->assertTrue(password_verify('123456', $user->passwordHash));
    }

    public function testIdorProtectionPreventsUnauthorizedOrderAccess(): void
    {
        $customerA = new User(1, 'musteri_a', 'hash', 'Müşteri A', '0555', UserRole::CUSTOMER);
        $customerBOrderId = 2; // Bu sipariş Müşteri B'ye ait (customerId = 2)

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Yetkisiz erişim denemesi engellendi! (Sahiplik ve IDOR güvenlik kontrolü)");

        // Müşteri A, müşteri B'nin siparişine erişmeye çalışıyor
        $this->authService->assertCanAccessOrder($customerA, $customerBOrderId);
    }

    public function testAdminRoleCanAccessAnyOrder(): void
    {
        $admin = new User(99, 'admin_user', 'hash', 'Sistem Yöneticisi', '0555', UserRole::ADMIN);

        // İstisna fırlatılmamalıdır
        $this->authService->assertCanAccessOrder($admin, 5, 3, 2);
        $this->assertTrue(true);
    }

    /**
     * Şartname Madde 4 & Güvenlik Kuralı:
     * Dışarıdan veya formdan Admin rolüyle kayıt olma denemesi kod seviyesinde engellenmelidir.
     */
    public function testCannotRegisterAdminUser(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Yönetici (Admin) hesabı sonradan eklenemez");

        $this->authService->register('yeni_admin', 'sifre123456', 'Sahte Admin', '05551112233', UserRole::ADMIN);
    }

    /**
     * Şartname Madde 4:
     * Dışarıdan yalnızca müşteri hesabı açılabilir, kurye veya restoran rolü de doğrudan kayıtla açılamaz.
     */
    public function testNonCustomerRegistrationThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Dışarıdan yalnızca müşteri (customer) hesabı açılabilir");

        $this->authService->register('yeni_kurye', 'sifre123456', 'Sahte Kurye', '05551112233', UserRole::COURIER);
    }

    public function testCountryAwarePhoneValidationAcceptsValidNumbers(): void
    {
        $trFormatted = AuthController::validateAndNormalizePhone('+90', '555 123 45 67');
        $this->assertTrue($trFormatted['valid']);
        $this->assertSame('+90 5551234567', $trFormatted['phone']);

        $trWithZero = AuthController::validateAndNormalizePhone('+90', '05551234567');
        $this->assertTrue($trWithZero['valid']);
        $this->assertSame('+90 5551234567', $trWithZero['phone']);

        $deMobile = AuthController::validateAndNormalizePhone('+49', '0151 2345 6789');
        $this->assertTrue($deMobile['valid']);
        $this->assertSame('+49 15123456789', $deMobile['phone']);

        $azMobile = AuthController::validateAndNormalizePhone('+994', '50 123 45 67');
        $this->assertTrue($azMobile['valid']);
        $this->assertSame('+994 501234567', $azMobile['phone']);
    }

    public function testCountryAwarePhoneValidationRejectsInvalidLengthsAndCodes(): void
    {
        $tooLongTr = AuthController::validateAndNormalizePhone('+90', '555123456789999');
        $this->assertFalse($tooLongTr['valid']);
        $this->assertStringContainsString('10 haneli', $tooLongTr['error']);

        $tooShortTr = AuthController::validateAndNormalizePhone('+90', '55512');
        $this->assertFalse($tooShortTr['valid']);

        $tooLongAz = AuthController::validateAndNormalizePhone('+994', '5012345678');
        $this->assertFalse($tooLongAz['valid']);
        $this->assertStringContainsString('9 haneli', $tooLongAz['error']);

        $invalidCode = AuthController::validateAndNormalizePhone('+999', '1234567890');
        $this->assertFalse($invalidCode['valid']);
    }
}

