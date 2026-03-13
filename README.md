# JiFramework

JiFramework is a lightweight, plug-and-play PHP framework built for developers who want to move fast without sacrificing structure.

Whether you're prototyping a side project or shipping a production application, JiFramework gives you a fully wired environment — database, authentication, caching, logging, validation, encryption, and more — all ready in two lines of code. No CLI tools to learn, no template engine to configure, no boilerplate to write. Just install, instantiate, and build.

Built on plain PHP with zero mandatory configuration, JiFramework stays out of your way while giving you the power to handle anything from simple REST APIs to full multi-language web applications.

**Two lines to start:**

```php
require __DIR__ . '/vendor/autoload.php';
$app = new JiFramework\Core\App\App();
```

That's it — database, auth, cache, logging, validation, and more are all ready.

---

## Why JiFramework?

- **Zero boilerplate** — no commands to run, no code generation steps
- **No template engine** — use plain PHP files as views
- **No CLI tools** — everything works out of the box
- **Lazy loading** — components are created only when accessed; unused services cost nothing
- **Optional router** — use URL routing or skip it entirely (plug-and-play mode)
- **Production ready** — structured logging, CSRF protection, IP/country blocking, rate limiting, encryption

---

## Requirements

- PHP 7.4 or higher
- Composer
- MySQL / MariaDB *(optional — only needed for database features)*

---

## Installation

```bash
composer require jahurul1/ji-framework
```

---

## Quick Start

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use JiFramework\Core\App\App;

$app = new App();

// Query the database
$users = $app->db->table('users')->where('active', '=', 1)->get();

// Log something
$app->logger->info('Page loaded', ['user_count' => count($users)]);

// Validate input
$v = $app->validator->make($_POST, [
    'email' => 'required|email',
    'name'  => 'required|min:2|max:100',
]);

if ($v->fails()) {
    $errors = $v->errors();
}
```

---

## Configuration

Copy the example config to your project root and fill in your values:

```bash
cp vendor/jahurul1/jiframework/jiconfig.example.php jiconfig.php
```

JiFramework auto-detects `jiconfig.php` — no manual path setup needed. You only need to define the keys you want to change; everything else falls back to sensible defaults.

```php
// jiconfig.php
return [
    'app_mode' => 'production',
    'timezone' => 'Asia/Dhaka',

    'database' => [
        'host'     => 'localhost',
        'database' => 'my_db',
        'username' => 'root',
        'password' => 'secret',
    ],

    'log_enabled'        => true,
    'rate_limit_enabled' => true,
    'router_enabled'     => true,
];
```

---

## Router Mode

Enable routing in `jiconfig.php` (`router_enabled => true`) and define routes in `index.php`:

```php
$app->router->get('/', 'pages/home.php');
$app->router->get('/users/{id}', 'pages/user.php');  // $id available in file

$app->router->get('/ping', function () {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok']);
});

$app->router->post('/login', 'pages/auth/login.php');

$app->router->group(['prefix' => '/admin'], function ($r) {
    $r->get('/dashboard', 'pages/admin/dashboard.php');
    $r->get('/users',     'pages/admin/users.php');
});

$app->router->dispatch();
```

---

## Components

| Component | Access | Description |
|-----------|--------|-------------|
| **QueryBuilder** | `$app->db` | Fluent SQL builder — SELECT, INSERT, UPDATE, DELETE, JOINs, aggregates, transactions |
| **Model** | extend `Model` | Active Record-style base class for database tables |
| **Auth** | `$app->auth` | User & admin authentication, remember-me tokens |
| **Session** | `$app->sessionManager` | Session management, flash messages, CSRF tokens |
| **Validator** | `$app->validator` | Fluent input validation with 20+ built-in rules |
| **Router** | `$app->router` | Optional URL router — file or closure handlers, route groups, params |
| **Cache** | `$app->cache` | File or SQLite cache driver with TTL, increment/decrement |
| **Logger** | `$app->logger` | PSR-3 structured logger with log rotation and level filtering |
| **Encryption** | `$app->encryption` | AES-256-GCM encryption, password hashing, secure random tokens |
| **RateLimiter** | `$app->rateLimiter` | Request rate limiting with IP ban support |
| **AccessControl** | `$app->accessControl` | IP and country blocking |
| **HttpClient** | `$app->http` | HTTP client for external API requests |
| **FileManager** | `$app->fileManager` | File read/write, upload handling, directory utilities |
| **Str** | `$app->str` | String utilities — slugify, mask, case conversion, truncate, and more |
| **Paginator** | `$app->paginator` | Pagination with QueryBuilder integration |
| **DateTimeHelper** | `$app->dateTime` | Date/time arithmetic, formatting, timezone conversion |
| **ExecutionTimer** | `$app->executionTimer` | Measure code execution time |
| **Request** | `$app->request` | Client IP detection, request headers, bearer token, AJAX/HTTPS/CLI detection |
| **Url** | `$app->url` | URL parsing, building, query param manipulation |
| **Localization** | `$app->language` | Multi-language support via JSON files |
| **ErrorHandler** | automatic | Structured error handling, custom error pages, named HTTP exceptions |
| **Exceptions** | `$app->abort()` | `HttpException`, `NotFoundException`, `ForbiddenException`, `ValidationException`, and more |

---

## Code Examples

### Database — QueryBuilder

```php
// Select with conditions
$users = $app->db->table('users')
    ->where('active', '=', 1)
    ->where('age', '>', 18)
    ->orderBy('name', 'ASC')
    ->limit(10)
    ->get();

// Insert and get ID
$id = $app->db->table('users')->insertGetId([
    'name'  => 'Alice',
    'email' => 'alice@example.com',
]);

// Update
$app->db->table('users')->where('id', '=', $id)->update(['active' => 0]);

// Paginate
$result = $app->db->table('posts')->orderBy('id', 'DESC')->paginate(15, 1);
// $result->data, $result->totalItems, $result->totalPages
```

### Model

```php
class Post extends JiFramework\Core\Database\Model
{
    protected static string $table      = 'posts';
    protected static string $primaryKey = 'id';
}

Post::all();
Post::find(1);
Post::where('published', '=', 1)->orderBy('created_at', 'DESC')->get();
Post::create(['title' => 'Hello', 'body' => '...']);
Post::update(['title' => 'Updated'], 1);
Post::destroy(1);
```

### Validator

```php
$v = $app->validator->make($_POST, [
    'name'             => 'required|min:2|max:100|alpha',
    'email'            => 'required|email',
    'password'         => 'required|min:8',
    'password_confirm' => 'required|confirmed:password',
    'age'              => 'nullable|integer|min:18',
]);

if ($v->fails()) {
    $errors      = $v->errors();          // all errors
    $firstEmail  = $v->first('email');    // first error for a field
}

$v->throw(); // throws ValidationException on failure
```

### Session & CSRF

```php
$session = $app->sessionManager;

$session->set('user_id', 42);
$session->get('user_id');
$session->has('user_id');
$session->delete('user_id');

// Flash messages
$session->flashSuccess('Profile saved.');
$session->flashError('Something went wrong.');
$messages = $session->getFlashMessages();

// CSRF
$token = $session->generateCsrfToken();
$session->verifyCsrfToken($token); // true/false
```

### Logger

```php
$app->logger->info('User logged in', ['user_id' => 5]);
$app->logger->warning('Slow query detected', ['ms' => 450]);
$app->logger->error('Payment failed', ['order_id' => 99]);
// Also: debug(), notice(), critical(), alert(), emergency()
```

### Encryption

```php
$enc = $app->encryption;

$key        = $enc->generateKey();          // 64-char hex key
$ciphertext = $enc->encrypt('secret', $key);
$plaintext  = $enc->decrypt($ciphertext, $key);

$hash = $enc->hashPassword('mypassword');
$enc->verifyPassword('mypassword', $hash);  // true

$token = $enc->randomBytes(32); // cryptographically secure token
```

---

## Testing

```bash
# Run all tests (Unit + Feature + Database)
php vendor/bin/phpunit

# Run a specific suite
php vendor/bin/phpunit --testsuite Unit
php vendor/bin/phpunit --testsuite Feature
php vendor/bin/phpunit --testsuite Database

# Run network tests (requires internet connection)
php vendor/bin/phpunit --group network
```

The test suite contains **327 tests** and **499 assertions** covering all framework components.

---

## Documentation

Full documentation is available at **[jiframework.com](https://jiframework.com)**.

---

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

---

## License

[MIT License](LICENSE) — © 2025 Jahurul Islam
