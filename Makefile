.PHONY: phpstan phpcs phpmd pint pest test clear quality fix help test-front test-coverage test-wcag test-all test-coverage-back test-e2e

# PHPStan static analysis (level 10)
phpstan:
	@echo "Running PHPStan static analysis..."
	@vendor/bin/phpstan analyse --memory-limit=2G

# PHP_CodeSniffer - Check PSR-12 compliance
phpcs:
	@echo "Running PHP_CodeSniffer..."
	@vendor/bin/phpcs

# PHPMD - PHP Mess Detector
phpmd:
	@echo "Running PHP Mess Detector..."
	@vendor/bin/phpmd app,database,routes text phpmd.xml

# Laravel Pint - Code formatting (check only)
pint:
	@echo "Running Laravel Pint (check mode)..."
	@vendor/bin/pint --test

# Pest - Run tests with Pest
pest:
	@echo "Running Pest tests..."
	@vendor/bin/pest

# PHPUnit tests (legacy)
test:
	@echo "Running PHPUnit tests..."
	@php artisan test

# PHPUnit tests with coverage
test-coverage-back:
	@echo "Running backend tests with coverage..."
	@php artisan test --coverage

# PHPUnit E2E tests only
test-e2e:
	@echo "Running E2E tests..."
	@php artisan test tests/Feature/E2E

# Frontend tests - All tests
test-front:
	@echo "Running frontend tests..."
	@npm test

# Frontend tests - With coverage
test-coverage:
	@echo "Running frontend tests with coverage..."
	@npm run test:coverage

# Frontend tests - Accessibility (WCAG)
test-wcag:
	@echo "Running accessibility tests (WCAG 2.1 AA)..."
	@npm test -- WCAG.test

# Run all tests (backend + frontend)
test-all: test test-front
	@echo "All tests completed!"

# Fix code style automatically
fix:
	@echo "Fixing code style with Laravel Pint..."
	@vendor/bin/pint
	@echo "Code style fixed!"

# Clear all caches
clear:
	@echo "Clearing caches..."
	@php artisan cache:clear
	@php artisan config:clear
	@php artisan route:clear
	@php artisan view:clear

# Run all code quality checks
quality: phpstan phpcs phpmd pint pest
	@echo "All quality checks completed!"

# Run all checks and auto-fix what can be fixed
quality-fix: fix phpstan phpcs phpmd pest
	@echo "Code fixed and quality checks completed!"

# Display help
help:
	@echo "Available commands:"
	@echo ""
	@echo "📊 Code Quality:"
	@echo "  make phpstan            - Run PHPStan static analysis (level 10)"
	@echo "  make phpcs              - Run PHP_CodeSniffer (PSR-12 compliance)"
	@echo "  make phpmd              - Run PHP Mess Detector (complexity, design)"
	@echo "  make pint               - Run Laravel Pint (check code style)"
	@echo "  make fix                - Auto-fix code style with Pint"
	@echo "  make quality            - Run all quality checks"
	@echo "  make quality-fix        - Fix code style + run all checks"
	@echo ""
	@echo "🧪 Backend Tests:"
	@echo "  make pest               - Run Pest tests"
	@echo "  make test               - Run PHPUnit tests"
	@echo "  make test-coverage-back - Run backend tests with coverage"
	@echo "  make test-e2e           - Run E2E tests only"
	@echo ""
	@echo "🎨 Frontend Tests:"
	@echo "  make test-front         - Run all frontend tests"
	@echo "  make test-coverage      - Run frontend tests with coverage"
	@echo "  make test-wcag          - Run accessibility tests (WCAG 2.1 AA)"
	@echo ""
	@echo "🚀 All Tests:"
	@echo "  make test-all           - Run all tests (backend + frontend)"
	@echo ""
	@echo "🔧 Utilities:"
	@echo "  make clear              - Clear all Laravel caches"
	@echo "  make help               - Display this help message"
