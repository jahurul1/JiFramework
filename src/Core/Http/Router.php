<?php
namespace JiFramework\Core\Http;

use JiFramework\Config\Config;
use JiFramework\Exceptions\HttpException;

class Router
{
    /**
     * Registered routes.
     *
     * @var array
     */
    private array $routes = [];

    /**
     * The App instance, made available inside file-based handlers.
     *
     * @var \JiFramework\Core\App\App
     */
    private $app;

    /**
     * Active group prefix — set while inside a group() callback.
     *
     * @var string
     */
    private string $groupPrefix = '';

    /**
     * @param \JiFramework\Core\App\App $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    // =========================================================================
    // Route registration
    // =========================================================================

    /**
     * Register a GET route.
     *
     * @param string          $pattern  URL pattern, e.g. '/users/{id}'
     * @param callable|string $handler  Closure or path to a PHP file
     */
    public function get(string $pattern, $handler): static
    {
        return $this->addRoute('GET', $pattern, $handler);
    }

    /**
     * Register a POST route.
     */
    public function post(string $pattern, $handler): static
    {
        return $this->addRoute('POST', $pattern, $handler);
    }

    /**
     * Register a PUT route.
     */
    public function put(string $pattern, $handler): static
    {
        return $this->addRoute('PUT', $pattern, $handler);
    }

    /**
     * Register a DELETE route.
     */
    public function delete(string $pattern, $handler): static
    {
        return $this->addRoute('DELETE', $pattern, $handler);
    }

    /**
     * Register a PATCH route.
     */
    public function patch(string $pattern, $handler): static
    {
        return $this->addRoute('PATCH', $pattern, $handler);
    }

    /**
     * Register a route that responds to any HTTP method.
     */
    public function any(string $pattern, $handler): static
    {
        foreach (['GET', 'POST', 'PUT', 'DELETE', 'PATCH'] as $method) {
            $this->addRoute($method, $pattern, $handler);
        }
        return $this;
    }

    /**
     * Register a route that responds to multiple specified HTTP methods.
     *
     * @param string|array $methods  e.g. ['GET', 'POST']
     */
    public function match($methods, string $pattern, $handler): static
    {
        foreach ((array) $methods as $method) {
            $this->addRoute(strtoupper($method), $pattern, $handler);
        }
        return $this;
    }

    /**
     * Register a redirect route.
     * When the pattern is matched, the visitor is immediately redirected.
     *
     * @param string $from   URL pattern to match (supports {params}).
     * @param string $to     Destination URL. Use {param} placeholders to forward values.
     * @param int    $status HTTP redirect status code (301 or 302).
     */
    public function redirect(string $from, string $to, int $status = 302): static
    {
        return $this->any($from, function() use ($to, $status) {
            http_response_code($status);
            header('Location: ' . $to);
            exit;
        });
    }

    /**
     * Group routes under a common URL prefix.
     *
     * All routes registered inside the callback will be prefixed with $prefix.
     * Groups can be nested.
     *
     * @param string   $prefix   URL prefix, e.g. '/api/v1'
     * @param callable $callback function(Router $router): void
     */
    public function group(string $prefix, callable $callback): static
    {
        $previous          = $this->groupPrefix;
        $this->groupPrefix = $previous . '/' . trim($prefix, '/');

        $callback($this);

        $this->groupPrefix = $previous;

        return $this;
    }

    // =========================================================================
    // Dispatch
    // =========================================================================

    /**
     * Match the current request against registered routes and run the handler.
     * Call this once, after all routes are defined.
     */
    public function dispatch(): void
    {
        $method = $this->getRequestMethod();
        $uri    = $this->getCurrentUri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['regex'], $uri, $matches)) {
                array_shift($matches); // remove full-match capture

                // Build named params array: ['id' => '42', ...]
                $params = [];
                foreach ($route['params'] as $i => $name) {
                    $params[$name] = isset($matches[$i]) ? urldecode($matches[$i]) : null;
                }

                $this->runHandler($route['handler'], $params);
                return;
            }
        }

        // No route matched
        throw new HttpException(404, 'Page Not Found');
    }

    // =========================================================================
    // Internal helpers
    // =========================================================================

    /**
     * Store a route entry, prepending any active group prefix.
     */
    private function addRoute(string $method, string $pattern, $handler): static
    {
        // Prepend group prefix and normalise slashes
        $fullPattern = $this->groupPrefix . '/' . ltrim($pattern, '/');
        $fullPattern = '/' . trim($fullPattern, '/');

        $this->routes[] = [
            'method'  => $method,
            'pattern' => $fullPattern,
            'handler' => $handler,
            'regex'   => $this->patternToRegex($fullPattern),
            'params'  => $this->extractParamNames($fullPattern),
        ];

        return $this;
    }

    /**
     * Convert a URL pattern to a regex.
     *
     * Static segments are escaped with preg_quote() so characters like '.' and '+'
     * in the pattern are treated as literals, not regex operators.
     *
     * '/api/v1.0/users/{id}' → '#^/api/v1\.0/users/([^/]+)$#'
     */
    private function patternToRegex(string $pattern): string
    {
        // Split on {param} placeholders, escape static parts, rejoin
        $parts  = preg_split('/(\{[a-zA-Z_][a-zA-Z0-9_]*\})/', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE);
        $regex  = '';

        foreach ($parts as $part) {
            if (preg_match('/^\{[a-zA-Z_][a-zA-Z0-9_]*\}$/', $part)) {
                $regex .= '([^/]+)';      // named parameter capture group
            } else {
                $regex .= preg_quote($part, '#'); // escape static segment
            }
        }

        return '#^' . $regex . '$#';
    }

    /**
     * Extract param names from a pattern.
     * '/users/{id}/posts/{slug}' → ['id', 'slug']
     */
    private function extractParamNames(string $pattern): array
    {
        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $pattern, $matches);
        return $matches[1];
    }

    /**
     * Run the matched route handler.
     *
     * Closure:   called with URL params as positional arguments ($id, $slug, ...).
     * File path: file is required with $app and URL params extracted as variables.
     */
    private function runHandler($handler, array $params): void
    {
        if (is_callable($handler)) {
            call_user_func_array($handler, array_values($params));
            return;
        }

        if (is_string($handler)) {
            $filePath = $this->resolveFilePath($handler);

            if (!file_exists($filePath)) {
                throw new HttpException(404, 'Page not found');
            }

            // Make $app and URL params available inside the included file
            $app = $this->app;
            extract($params); // e.g. $id, $slug, etc.
            require $filePath;
            return;
        }

        throw new HttpException(500, 'Invalid route handler');
    }

    /**
     * Resolve a relative file path to an absolute path.
     * Relative paths are resolved from Config::$basePath.
     */
    private function resolveFilePath(string $path): string
    {
        // Already absolute (Unix: starts with /, Windows: has drive letter)
        if ($path[0] === '/' || (strlen($path) > 1 && $path[1] === ':')) {
            return $path;
        }

        return rtrim(Config::$basePath, '/\\') . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
    }

    /**
     * Get the HTTP method, with support for _method override.
     * Allows PUT/DELETE/PATCH from HTML forms via a hidden _method field.
     */
    private function getRequestMethod(): string
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if ($method === 'POST' && !empty($_POST['_method'])) {
            $override = strtoupper($_POST['_method']);
            if (in_array($override, ['PUT', 'DELETE', 'PATCH'], true)) {
                return $override;
            }
        }

        return $method;
    }

    /**
     * Get the current URI, stripped of query string and base path.
     */
    private function getCurrentUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        // Strip subdirectory base path (e.g. '/JiFramework' for localhost/JiFramework/)
        $basePath = Config::$routerBasePath;
        if ($basePath !== '' && strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }

        // Normalize: ensure a leading slash, remove trailing slash (except root)
        $uri = '/' . trim($uri, '/');

        return $uri;
    }
}
