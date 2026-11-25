<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable|array $handler): void
    {
        $cleanPath = '/' . trim($path, '/');
        if ($cleanPath !== '/') {
            $cleanPath = rtrim($cleanPath, '/');
        }

        // Parametreleri regex desenine dönüştür: {id} -> (?P<id>[^/]+)
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $cleanPath);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method' => $method,
            'path' => $cleanPath,
            'pattern' => $pattern,
            'handler' => $handler
        ];
    }

    public function dispatch(Request $request): void
    {
        $reqMethod = $request->getMethod();
        $reqPath = $request->getPath();

        try {
            foreach ($this->routes as $route) {
                if ($route['method'] !== $reqMethod) {
                    continue;
                }

                if (preg_match($route['pattern'], $reqPath, $matches)) {
                    // Parametreleri filtrele (yalnızca string anahtarlar)
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                    $handler = $route['handler'];
                    if (is_array($handler)) {
                        [$controllerClass, $method] = $handler;
                        $controller = new $controllerClass();
                        $controller->$method($request, ...$params);
                        return;
                    }

                    if (is_callable($handler)) {
                        $handler($request, ...$params);
                        return;
                    }
                }
            }

            // 404 Bulunamadı
            $this->handleNotFound($request);
        } catch (\Throwable $e) {
            $this->handleServerError($request, $e);
        }
    }

    private function handleNotFound(Request $request): void
    {
        http_response_code(404);
        if ($request->isJson() || str_starts_with($request->getPath(), '/api')) {
            Response::json(['status' => 'error', 'message' => 'Uç nokta veya kaynak bulunamadı (404 Not Found)'], 404);
        }
        Response::html(View::render('errors/404', ['path' => $request->getPath()], 'layouts/auth'), 404);
    }

    private function handleServerError(Request $request, \Throwable $e): void
    {
        // Şartname: "Kullanıcıya teknik ayrıntı, dosya yolu, SQL benzeri iç bilgi veya yığın izi göstermeyin.
        // Hata ayrıntılarını sunucu tarafında kaydedin."
        error_log(sprintf(
            "[%s] Sunucu Hatası: %s in %s:%d\nYığın İzi:\n%s",
            date('Y-m-d H:i:s'),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        ));

        http_response_code(500);
        if ($request->isJson() || str_starts_with($request->getPath(), '/api')) {
            Response::json(['status' => 'error', 'message' => 'Sunucu işlemi sırasında beklenmeyen bir hata oluştu.'], 500);
        }
        Response::html(View::render('errors/500', [], 'layouts/auth'), 500);
    }
}
