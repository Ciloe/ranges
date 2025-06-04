# Contributing to Ranges

Thank you for considering contributing to the Ranges library! This document provides guidelines and instructions to help you contribute effectively.

## Code of Conduct

By participating in this project, you are expected to uphold our Code of Conduct, which is to treat all contributors with respect and foster an inclusive environment.

## Getting Started

1. **Fork the repository** on GitHub
2. **Clone your fork** locally
3. **Install dependencies**: `composer install`
4. **Create a branch** for your changes: `git checkout -b feature/your-feature-name`

## Development Workflow

### Environment Requirements

- PHP 8.2 or higher
- Composer

### Development Tools

The project uses several tools to ensure code quality:

- **PHPStan**: Static analysis tool
- **PHPUnit**: Testing framework
- **PHP_CodeSniffer**: Code style checker
- **Easy Coding Standard**: Code style fixer

### Useful Commands

The following Composer scripts are available:

- `composer validate`: Validates composer.json
- `composer phpstan`: Runs static analysis
- `composer phpcs`: Runs PHP CodeSniffer
- `composer ecs`: Runs Easy Coding Standard
- `composer ecs-fix`: Runs Easy Coding Standard with auto-fix
- `composer phpunit`: Runs tests
- `composer ci`: Runs all quality checks in sequence

## Coding Standards

This project follows PSR-12 coding standards. You can automatically check and fix your code using:

```bash
composer ecs
composer ecs-fix
```

## Testing

All new features and bug fixes should include tests. Run the test suite with:

```bash
composer phpunit
```

## Pull Request Process

1. **Update your fork** to the latest upstream changes
2. **Run all checks** locally: `composer ci`
3. **Create a pull request** with a clear title and description
4. **Link any related issues** in your pull request description

Your pull request will be reviewed by the maintainers, who may request changes or provide feedback.

## Continuous Integration

The project uses GitHub Actions for continuous integration. When you submit a pull request, the following checks will run automatically:

- Validation of composer.json
- Static analysis with PHPStan
- Code style checks with PHP_CodeSniffer and Easy Coding Standard
- Tests with PHPUnit

All checks must pass before a pull request can be merged.

## Documentation

If you're adding new features or changing existing functionality, please update the relevant documentation:

- Update the README.md if necessary
- Update or add documentation in the doc/ directory
- Add or update PHPDoc comments in the code

## License

By contributing to this project, you agree that your contributions will be licensed under the project's [GPL-3.0-or-later license](LICENSE).

## Questions?

If you have any questions or need help, please open an issue on GitHub.

Thank you for contributing to Ranges!
