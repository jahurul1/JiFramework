# Contributing to JiFramework

Thank you for considering contributing to JiFramework! This document provides guidelines and instructions for contributing to this project.

## Code of Conduct

By participating in this project, you agree to abide by the [Code of Conduct](CODE_OF_CONDUCT.md).

## How Can I Contribute?

### Reporting Bugs

Before submitting a bug report:

1. Check the issue tracker to avoid duplicates
2. Collect information about the bug:
   - Steps to reproduce
   - Expected behavior
   - Actual behavior
   - Screenshots (if applicable)
   - Environment details (PHP version, OS, etc.)
3. Use the bug report template when creating an issue

### Suggesting Features

Feature suggestions are welcome! Please:

1. Check existing feature requests
2. Clearly describe the feature and its benefits
3. Provide examples of how it would be used

### Pull Requests

Follow these steps for submitting code:

1. Fork the repository
2. Create a new branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run tests (`php tests/run_tests.php`)
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a pull request

## Development Setup

1. Clone the repository
2. Run `composer install`
3. Copy `.env.example` to `.env` and configure
4. Run the tests to ensure everything is working

## Coding Standards

- Follow PSR-12 coding standards
- Use meaningful variable/method names
- Write clear, maintainable code
- Document your code with PHPDoc comments

## Testing

- All new features must include tests
- All bug fixes must include a test that demonstrates the bug is fixed
- Run the test suite before submitting a pull request:

```bash
php tests/run_tests.php
```

## Documentation

- Update documentation for any changes to public APIs
- Include code examples where appropriate
- Keep the language clear and accessible

## Git Commit Messages

- Use the present tense ("Add feature" not "Added feature")
- Use the imperative mood ("Move cursor to..." not "Moves cursor to...")
- Limit the first line to 72 characters
- Reference issues and pull requests after the first line

## Versioning

This project follows [Semantic Versioning](https://semver.org/).

## License

By contributing, you agree that your contributions will be licensed under the project's [MIT License](LICENSE). 