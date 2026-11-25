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
        $phone = trim((string)$request->post('phone', ''));
        $password = (string)$request->post('password', '');
        $passwordConfirm = (string)$request->post('password_confirm', '');

        // Form Doğrulama (Validation)
        if ($fullName === '' || $username === '' || $password === '') {
            Session::setOldInput(['full_name' => $fullName, 'username' => $username, 'phone' => $phone]);
            Session::setFlash('danger', 'Lütfen zorunlu alanları (Ad Soyad, Kullanıcı Adı, Parola) doldurunuz.');
            Response::redirect($request->url('/register'), 303);
            return;
        }

        if (strlen($username) < 3) {
            Session::setOldInput(['full_name' => $fullName, 'username' => $username, 'phone' => $phone]);
            Session::setFlash('danger', 'Kullanıcı adı en az 3 karakterden oluşmalıdır.');
            Response::redirect($request->url('/register'), 303);
            return;
        }

        if (strlen($password) < 6) {
            Session::setOldInput(['full_name' => $fullName, 'username' => $username, 'phone' => $phone]);
            Session::setFlash('danger', 'Parola en az 6 karakter uzunluğunda olmalıdır.');
            Response::redirect($request->url('/register'), 303);
            return;
        }

        if ($password !== $passwordConfirm) {
            Session::setOldInput(['full_name' => $fullName, 'username' => $username, 'phone' => $phone]);
            Session::setFlash('danger', 'Girdiğiniz parolalar birbiriyle uyuşmuyor.');
            Response::redirect($request->url('/register'), 303);
            return;
        }

        try {
            // Şartname: Dışarıdan sadece müşteri rolü kaydedilebilir
            $this->authService->register($username, $password, $fullName, $phone, UserRole::CUSTOMER);
            Session::setFlash('success', 'Hesabınız başarıyla oluşturuldu! Şimdi giriş yapabilirsiniz.');
            Response::redirect($request->url('/login'), 303);
        } catch (\InvalidArgumentException $e) {
            Session::setOldInput(['full_name' => $fullName, 'username' => $username, 'phone' => $phone]);
            Session::setFlash('danger', $e->getMessage());
            Response::redirect($request->url('/register'), 303);
        }
    }

    public function logout(Request $request): void
    {
        Session::destroy();
        Session::setFlash('info', 'Oturumunuz güvenli biçimde sonlandırıldı.');
        Response::redirect($request->url('/login'), 303);
    }
}
