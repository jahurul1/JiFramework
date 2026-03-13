# Contributing to JiFramework

Thank you for your interest in contributing! Here's how to get started.

## Requirements

- PHP 7.4 or higher
- Composer

## Setup

```bash
git clone https://github.com/your-username/JiFramework.git
cd JiFramework
composer install
```

## Running Tests

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

All tests must pass before submitting a pull request.

## How to Contribute

1. Fork the repository
2. Create a branch: `git checkout -b feature/your-feature-name`
3. Make your changes
4. Add or update tests to cover your changes
5. Run the full test suite and confirm all tests pass
6. Commit with a clear message: `git commit -m "Add: short description"`
7. Push your branch and open a Pull Request

## Code Style

- Follow PSR-12 coding standards
- Use meaningful method and variable names
- Keep methods small and focused
- Add PHPDoc comments to public methods

## Reporting Bugs

Open an issue on GitHub with:
- PHP version
- Steps to reproduce
- Expected vs actual behaviour

## Security Issues

Do **not** open a public issue for security vulnerabilities.
Email directly: **info@jahurul.in**

## License

By contributing, you agree that your contributions will be licensed under the [MIT License](LICENSE).
