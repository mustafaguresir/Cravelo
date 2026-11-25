<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Enum\UserRole;
use App\Repository\Json\JsonUserRepository;
use App\Repository\UserRepositoryInterface;
use App\Service\AuthService;

final class AuthController extends Controller
{
    /**
     * Ülke alan koduna göre geçerli telefon numarası hane kuralları.
     *
     * @var array<string, array{label: string, min: int, max: int, strip_zero: bool, example: string}>
     */
    public const COUNTRY_PHONE_RULES = [
        '+90'  => ['label' => 'Türkiye', 'min' => 10, 'max' => 10, 'strip_zero' => true, 'example' => '555 123 45 67'],
        '+49'  => ['label' => 'Almanya', 'min' => 10, 'max' => 11, 'strip_zero' => true, 'example' => '151 2345 6789'],
        '+44'  => ['label' => 'Birleşik Krallık', 'min' => 10, 'max' => 10, 'strip_zero' => true, 'example' => '7700 900123'],
        '+1'   => ['label' => 'ABD / Kanada', 'min' => 10, 'max' => 10, 'strip_zero' => false, 'example' => '202 555 0143'],
        '+994' => ['label' => 'Azerbaycan', 'min' => 9, 'max' => 9, 'strip_zero' => true, 'example' => '50 123 45 67'],
        '+33'  => ['label' => 'Fransa', 'min' => 9, 'max' => 9, 'strip_zero' => true, 'example' => '6 12 34 56 78'],
        '+31'  => ['label' => 'Hollanda', 'min' => 9, 'max' => 9, 'strip_zero' => true, 'example' => '6 1234 5678'],
        '+39'  => ['label' => 'İtalya', 'min' => 9, 'max' => 10, 'strip_zero' => false, 'example' => '312 345 6789'],
        '+34'  => ['label' => 'İspanya', 'min' => 9, 'max' => 9, 'strip_zero' => false, 'example' => '612 34 56 78'],
        '+971' => ['label' => 'BAE', 'min' => 9, 'max' => 9, 'strip_zero' => true, 'example' => '50 123 4567'],
        '+966' => ['label' => 'Suudi Arabistan', 'min' => 9, 'max' => 9, 'strip_zero' => true, 'example' => '50 123 4567'],
        '+7'   => ['label' => 'Rusya', 'min' => 10, 'max' => 10, 'strip_zero' => false, 'example' => '912 345 67 89'],
    ];

    private AuthService $authService;

    public function __construct(
        ?AuthService $authService = null,
        ?UserRepositoryInterface $userRepo = null
    ) {
        $resolvedRepo = $userRepo ?? new JsonUserRepository();
        $this->authService = $authService ?? new AuthService($resolvedRepo);
    }

    public function showLogin(Request $request): void
    {
        if (Session::has('user')) {
            Response::redirect($request->url('/'), 303);
            return;
        }

        $html = View::render('auth/login', [
            'pageTitle' => 'Giriş Yap — Cravelo',
            'oldUsername' => Session::getOldInput('username'),
        ], 'layouts/auth');

        Session::clearOldInput();
        Response::html($html);
    }

    public function login(Request $request): void
    {
        if (Session::has('__csrf_token') && !Session::validateCsrfToken((string)$request->post('_csrf_token', ''))) {
            Session::setFlash('danger', 'Güvenlik doğrulaması (CSRF) başarısız oldu. Lütfen formu yenileyip tekrar deneyin.');
            Response::redirect($request->url('/login'), 303);
            return;
        }

        $username = trim((string)$request->post('username', ''));
        $password = (string)$request->post('password', '');

        if ($username === '' || $password === '') {
            Session::setOldInput(['username' => $username]);
            Session::setFlash('danger', 'Lütfen kullanıcı adı ve parolanızı eksiksiz giriniz.');
            Response::redirect($request->url('/login'), 303);
            return;
        }

        $user = $this->authService->login($username, $password);
        if ($user === null) {
            Session::setOldInput(['username' => $username]);
            Session::setFlash('danger', 'Hatalı kullanıcı adı veya parola!');
            Response::redirect($request->url('/login'), 303);
            return;
        }

        // Oturumu Başlat
        Session::set('user', [
            'id' => $user->id,
            'username' => $user->username,
            'role' => $user->role->value,
            'fullName' => $user->fullName,
            'phone' => $user->phone ?? ''
        ]);

        Session::setFlash('success', "Hoş geldiniz, {$user->fullName}! Başarıyla giriş yaptınız.");
        Response::redirect($request->url('/'), 303);
    }

    public function showRegister(Request $request): void
    {
        if (Session::has('user')) {
            Response::redirect($request->url('/'), 303);
            return;
        }

        $html = View::render('auth/register', [
            'pageTitle' => 'Yeni Müşteri Kaydı — Cravelo',
            'oldFullName' => Session::getOldInput('full_name'),
            'oldUsername' => Session::getOldInput('username'),
            'oldCountryCode' => Session::getOldInput('country_code', '+90'),
            'oldPhone' => Session::getOldInput('phone'),
        ], 'layouts/auth');

        Session::clearOldInput();
        Response::html($html);
    }

    public function register(Request $request): void
    {
        if (Session::has('__csrf_token') && !Session::validateCsrfToken((string)$request->post('_csrf_token', ''))) {
            Session::setFlash('danger', 'Güvenlik doğrulaması (CSRF) başarısız oldu. Lütfen formu yenileyip tekrar deneyin.');
            Response::redirect($request->url('/register'), 303);
            return;
        }

        $fullName = trim((string)$request->post('full_name', ''));
        $username = trim((string)$request->post('username', ''));
        $countryCode = trim((string)$request->post('country_code', '+90'));
        if ($countryCode === '') {
            $countryCode = '+90';
        }
        $phone = trim((string)$request->post('phone', ''));
        $password = (string)$request->post('password', '');
        $passwordConfirm = (string)$request->post('password_confirm', '');

        $oldInput = [
            'full_name' => $fullName,
            'username' => $username,
            'country_code' => $countryCode,
            'phone' => $phone,
        ];

        // Form Doğrulama (Validation)
        if ($fullName === '' || $username === '' || $password === '') {
            Session::setOldInput($oldInput);
            Session::setFlash('danger', 'Lütfen zorunlu alanları (Ad Soyad, Kullanıcı Adı, Parola) doldurunuz.');
            Response::redirect($request->url('/register'), 303);
            return;
        }

        if (strlen($username) < 3) {
            Session::setOldInput($oldInput);
            Session::setFlash('danger', 'Kullanıcı adı en az 3 karakterden oluşmalıdır.');
            Response::redirect($request->url('/register'), 303);
            return;
        }

        if (strlen($password) < 6) {
            Session::setOldInput($oldInput);
            Session::setFlash('danger', 'Parola en az 6 karakter uzunluğunda olmalıdır.');
            Response::redirect($request->url('/register'), 303);
            return;
        }

        if ($password !== $passwordConfirm) {
            Session::setOldInput($oldInput);
            Session::setFlash('danger', 'Girdiğiniz parolalar birbiriyle uyuşmuyor.');
            Response::redirect($request->url('/register'), 303);
            return;
        }

        // Ülke koduna göre telefon numarası doğrulaması
        $phoneCheck = self::validateAndNormalizePhone($countryCode, $phone);
        if (!$phoneCheck['valid']) {
            Session::setOldInput($oldInput);
            Session::setFlash('danger', $phoneCheck['error']);
            Response::redirect($request->url('/register'), 303);
            return;
        }
        $normalizedPhone = $phoneCheck['phone'];

        try {
            // Şartname: Dışarıdan sadece müşteri rolü kaydedilebilir
            $this->authService->register($username, $password, $fullName, $normalizedPhone, UserRole::CUSTOMER);
            Session::setFlash('success', 'Hesabınız başarıyla oluşturuldu! Şimdi giriş yapabilirsiniz.');
            Response::redirect($request->url('/login'), 303);
        } catch (\InvalidArgumentException $e) {
            Session::setOldInput($oldInput);
            Session::setFlash('danger', $e->getMessage());
            Response::redirect($request->url('/register'), 303);
        }
    }

    /**
     * Seçilen ülke alan koduna göre telefon numarasını temizler ve hane sınırını doğrular.
     *
     * @return array{valid: bool, phone: string, error: string}
     */
    public static function validateAndNormalizePhone(string $countryCode, string $rawPhone): array
    {
        if (!isset(self::COUNTRY_PHONE_RULES[$countryCode])) {
            return [
                'valid' => false,
                'phone' => '',
                'error' => 'Geçersiz ülke telefon kodu seçildi. Lütfen listeden geçerli bir ülke kodu seçiniz.',
            ];
        }

        $rule = self::COUNTRY_PHONE_RULES[$countryCode];
        $digits = preg_replace('/\D+/', '', $rawPhone) ?? '';
        $codeDigits = ltrim($countryCode, '+');

        // Kullanıcı alan kodunu numaranın başına da yapıştırdıysa ayıkla
        if (str_starts_with($digits, $codeDigits) && strlen($digits) > $rule['max']) {
            $digits = substr($digits, strlen($codeDigits));
        }

        // Yerel başlangıç 0'ı (örn. TR 0555...) girildiyse uluslararası format için temizle
        if ($rule['strip_zero'] && str_starts_with($digits, '0')) {
            $digits = ltrim($digits, '0');
        }

        $len = strlen($digits);
        if ($len === 0) {
            return [
                'valid' => false,
                'phone' => '',
                'error' => 'Lütfen geçerli bir telefon numarası giriniz.',
            ];
        }

        if ($len < $rule['min'] || $len > $rule['max']) {
            $rangeLabel = ($rule['min'] === $rule['max'])
                ? (string)$rule['max']
                : ($rule['min'] . '-' . $rule['max']);
            return [
                'valid' => false,
                'phone' => '',
                'error' => "{$rule['label']} ({$countryCode}) için telefon numarası {$rangeLabel} haneli olmalıdır (Örn: {$rule['example']}).",
            ];
        }

        return [
            'valid' => true,
            'phone' => $countryCode . ' ' . $digits,
            'error' => '',
        ];
    }

    public function logout(Request $request): void
    {
        Session::destroy();
        Session::setFlash('info', 'Oturumunuz güvenli biçimde sonlandırıldı.');
        Response::redirect($request->url('/login'), 303);
    }
}
