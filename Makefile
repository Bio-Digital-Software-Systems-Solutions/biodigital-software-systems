.PHONY: phpstan phpcs phpmd pint pest test clear quality fix help

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
	@echo "  make phpstan       - Run PHPStan static analysis (level 10)"
	@echo "  make phpcs         - Run PHP_CodeSniffer (PSR-12 compliance)"
	@echo "  make phpmd         - Run PHP Mess Detector (complexity, design)"
	@echo "  make pint          - Run Laravel Pint (check code style)"
	@echo "  make pest          - Run Pest tests"
	@echo "  make test          - Run PHPUnit tests (legacy)"
	@echo "  make fix           - Auto-fix code style with Pint"
	@echo "  make clear         - Clear all Laravel caches"
	@echo "  make quality       - Run all quality checks"
	@echo "  make quality-fix   - Fix code style + run all checks"
	@echo "  make help          - Display this help message"
