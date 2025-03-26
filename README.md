# JiFramework

A lightweight and efficient PHP framework designed to simplify web application development. JiFramework emphasizes ease of use and flexibility, allowing you to build robust applications without unnecessary complexity or overhead.

## Features

- **Simplicity and Ease of Use**
  - Minimal setup with no complicated configurations required
  - Plain PHP for views, giving complete control over presentation layer

- **Flexibility and Modularity**
  - Component-based architecture
  - Easy integration with existing projects

- **Powerful Features**
  - Database Query Builder
  - Robust error handling
  - Security features (CSRF protection, encryption, rate limiting)

- **Performance**
  - Lightweight design
  - Optimized components

## Requirements

- PHP 7.4 or higher
- Composer (for dependency management)

## Installation

### Via Composer

```bash
composer require jahurul1/ji-framework
```

### Manual Download

Download the zip file and extract the files, then include the autoloader.

## Quick Start

```php
// Initialize the framework
require 'vendor/autoload.php';

// Create application instance
$app = new JiFramework\Core\App\App();

// Your application code here
```

## Database Operations

```php
// Get database connection via the application instance
$db = $app->db;

// Or create a new instance directly
$db = new JiFramework\Core\Database\QueryBuilder();

// Perform database operations
$users = $db->table('users')
            ->where('status', 'active')
            ->orderBy('name', 'ASC')
            ->get();
```

## Basic Usage

```php
// Using the App container
use JIFramework\Core\App\App;

$app = new App();
$db = $app->db;
$logger = $app->logger;
$session = $app->sessionManager;

// Or instantiate components directly
use JIFramework\Core\Database\QueryBuilder;

$db = new QueryBuilder();
$results = $db->table('users')->where('status', 'active')->get();
```

## Testing

The framework includes a comprehensive testing system. To run tests:

```bash
# Run all tests
php tests/run_tests.php

# Run with debug output (verbose)
php tests/run_tests.php debug

# Run with minimal output
php tests/run_tests.php quiet

# Show progress dots for each test
php tests/run_tests.php progress

# Run tests for a specific class
php tests/run_tests.php YourTestClass
```

All tests are located in the `tests/` directory and follow a standard structure.

## Documentation

For complete documentation, visit our official website at [jiframework.com](https://jiframework.com/).

The repository also includes sample documentation for select components in the [docs directory](docs/) to help you get started:

- **[App](docs/components/app.md)** - Understanding the application container
- **[QueryBuilder](docs/components/query-builder.md)** - Database interaction guide

For the most comprehensive and up-to-date documentation, always refer to the official website.

## Components

JiFramework includes the following components:

- **AccessControl** - Manage user access permissions and roles
- **App** - Application container [(docs)](docs/components/app.md)
- **Auth** - User authentication and management
- **Cache Manager** - Efficient caching layer
- **DateTimeHelper** - Simplify DateTime operations
- **Encryption** - Secure data encryption/decryption
- **EnvironmentHelper** - Manage environment-specific settings
- **ErrorPageHandler** - User-friendly error page handling
- **ExecutionTime** - Measure and manage execution timing
- **FileManager** - Handle file operations easily
- **HttpRequestHelper** - Simplified HTTP request handling
- **LanguageManager** - Multilingual application support
- **Logger** - Application logging system
- **PaginationHelper** - Easy pagination of results
- **QueryBuilder** - Simplified database queries [(docs)](docs/components/query-builder.md)
- **RateLimiter** - Limit requests for security and performance
- **SessionManager** - Secure session management
- **StringHelper** - String utilities and helpers
- **UrlHelper** - URL parsing and building
- **Validator** - Easy and secure input validation

For complete documentation on all components, visit [jiframework.com](https://jiframework.com/).

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on how to contribute to the project.

## License

[MIT License](LICENSE) 
