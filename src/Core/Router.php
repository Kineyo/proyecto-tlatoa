<?php
declare(strict_types=1);

namespace Tlatoa\Core;

/**
 * Router: Enrutador Front Controller nativo y ligero.
 * Compatible al 100% con subdirectorios de XAMPP (/tlatoa/) y servidores de producción en raíz.
 */
class Router
{
    /** @var array<string, array<int, array{pattern: string, regex: string, handler: callable|array}>> */
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    private static ?string $basePath = null;

    public function __construct()
    {
        self::initBasePath();
    }

    /**
     * Detecta dinámicamente la ruta base (ej. '/tlatoa' en XAMPP o '' en producción).
     */
    public static function initBasePath(): string
    {
        if (self::$basePath !== null) {
            return self::$basePath;
        }

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = dirname($scriptName);
        $base = str_replace('\\', '/', $dir);

        // Si es la raíz del servidor, dejamos cadena vacía
        self::$basePath = ($base === '/' || $base === '.') ? '' : rtrim($base, '/');
        return self::$basePath;
    }

    /**
     * Genera una URL absoluta al sitio garantizando portabilidad XAMPP/Producción.
     */
    public static function url(string $path = ''): string
    {
        $base = self::initBasePath();
        $cleanPath = '/' . ltrim($path, '/');
        return $base . $cleanPath;
    }

    /**
     * Registra una ruta GET. Soporta parámetros {slug}, {id}, etc.
     * @param callable|array $handler
     */
    public function get(string $path, callable|array $handler): self
    {
        $this->addRoute('GET', $path, $handler);
        return $this;
    }

    /**
     * Registra una ruta POST.
     * @param callable|array $handler
     */
    public function post(string $path, callable|array $handler): self
    {
        $this->addRoute('POST', $path, $handler);
        return $this;
    }

    private function addRoute(string $method, string $path, callable|array $handler): void
    {
        $clean = '/' . trim($path, '/');
        if ($clean !== '/') {
            $clean = rtrim($clean, '/');
        }

        // Convierte {slug} en grupo regex nombrado (?P<slug>[a-zA-Z0-9\-_]+)
        $regex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9\-_]+)', $clean);
        $regex = '#^' . $regex . '$#u';

        $this->routes[$method][] = [
            'pattern' => $clean,
            'regex' => $regex,
            'handler' => $handler,
        ];
    }

    /**
     * Despacha la petición actual según el REQUEST_URI.
     */
    public function dispatch(?string $requestUri = null, ?string $requestMethod = null): void
    {
        $method = strtoupper($requestMethod ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $rawUri = $requestUri ?? ($_SERVER['REQUEST_URI'] ?? '/');

        // Extraer solo el path sin query string
        $path = parse_url($rawUri, PHP_URL_PATH) ?? '/';

        // Remover el basePath de XAMPP si está presente
        $base = self::initBasePath();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }

        // Si se accede con el nombre del script (ej. /app.php/politicos/... o /index.php/politicos/...)
        if (str_starts_with($path, '/app.php')) {
            $path = substr($path, strlen('/app.php'));
        } elseif (str_starts_with($path, '/index.php')) {
            $path = substr($path, strlen('/index.php'));
        }

        $path = '/' . trim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        $routes = $this->routes[$method] ?? [];
        foreach ($routes as $route) {
            if (preg_match($route['regex'], $path, $matches)) {
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }

                $this->executeHandler($route['handler'], $params);
                return;
            }
        }

        // Si ninguna ruta coincide: 404
        $this->sendNotFound($path);
    }

    /**
     * Ejecuta el handler (Closure o [Clase, Método]).
     */
    private function executeHandler(callable|array $handler, array $params): void
    {
        if (is_callable($handler)) {
            call_user_func_array($handler, [$params]);
            return;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $instance = is_object($class) ? $class : new $class();
            call_user_func_array([$instance, $method], [$params]);
            return;
        }

        throw new \InvalidArgumentException('Handler no válido para la ruta.');
    }

    /**
     * Respuesta 404 estandarizada y limpia.
     */
    private function sendNotFound(string $path): void
    {
        http_response_code(404);
        $homeUrl = self::url('/');
        echo <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Página no encontrada | Proyecto Tlatoa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #F2F2F7; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card-404 { background: #fff; border-radius: 20px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); text-align: center; max-width: 480px; }
    </style>
</head>
<body>
    <div class="card-404">
        <h1 class="display-4 fw-bold text-danger mb-3">404</h1>
        <h2 class="h5 fw-bold mb-2">Página no encontrada</h2>
        <p class="text-secondary small mb-4">La ruta solicitada <code>{$path}</code> no existe o fue movida en la nueva arquitectura de URLs.</p>
        <a href="{$homeUrl}" class="btn btn-primary rounded-pill px-4">Volver al Observatorio</a>
    </div>
</body>
</html>
HTML;
    }
}
