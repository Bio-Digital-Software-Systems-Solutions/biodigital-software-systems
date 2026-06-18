.PHONY: setup phpstan phpcs phpmd pint pest test clear db db-docker quality fix help test-front test-coverage test-wcag test-all test-coverage-back test-e2e frontend-test backend-test docs schema-docs er-diagram class-diagram uml-diagram ts-uml-diagram use-case-diagrams convert-diagrams-png docs-full docs-serve docs-clean start stop docker-build docker-up docker-down docker-restart docker-logs docker-shell docker-mysql docker-redis docker-fresh docker-prod-build docker-prod-up robot-build robot-test robot-api robot-ui robot-e2e robot-smoke robot-critical robot-health robot-clean robot-report robot-tag robot-debug robot-rerun robot-shell jenkins-start jenkins-stop jenkins-restart jenkins-logs jenkins-shell jenkins-build jenkins-clean gitlab-start gitlab-stop gitlab-restart gitlab-logs gitlab-shell gitlab-runner-register gitlab-clean clean-event-media clean-event-media-preview ide-helper ide-helper-generate ide-helper-models ide-helper-meta psalm lint lint-fix format format-check knip quality-full rector rector-fix

# PHPStan static analysis (level 8)
phpstan:
	@echo "Running PHPStan static analysis..."
	@vendor/bin/phpstan analyse --memory-limit=2G

# Psalm static analysis
psalm:
	@echo "Running Psalm static analysis..."
	@vendor/bin/psalm

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
test-all: backend-test frontend-test
	@echo ""
	@echo "✅ All tests completed!"

# Frontend tests - Alias for test-front
frontend-test:
	@echo "🎨 Running frontend tests..."
	@npm test
	@echo "✅ Frontend tests completed!"

# Backend tests - Runs Pest tests
backend-test:
	@echo "🔧 Running backend tests..."
	@php artisan test
	@echo "✅ Backend tests completed!"

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

# Fix composer/artisan bootstrap issues (use when artisan fails during composer operations)
composer-fix:
	@echo "Fixing Composer/Artisan bootstrap issues..."
	@rm -rf bootstrap/cache/*.php 2>/dev/null || true
	@echo '*' > bootstrap/cache/.gitignore
	@echo '!.gitignore' >> bootstrap/cache/.gitignore
	@rm -rf vendor/
	@composer install --no-scripts
	@composer update laravel/framework --no-scripts -W
	@php artisan package:discover --ansi
	@echo ""
	@echo "✅ Composer/Artisan bootstrap fixed!"
	@echo "You can now run composer commands normally."

# Reset database with fresh migrations and seeders
db:
	@echo "Resetting database with fresh migrations and seeders..."
	@php artisan migrate:fresh --seed
	@echo "Database reset complete!"

db-docker:
	@echo "Running migrations inside Docker container..."
	@docker-compose exec -e XDEBUG_MODE=off -e TELESCOPE_ENABLED=false app php artisan migrate:fresh --seed --no-interaction
	@echo "Docker migrations and seeding complete!"

# Clean orphaned event media records (files that don't exist on disk)
clean-event-media:
	@echo "Cleaning orphaned event media records..."
	@php artisan events:clean-orphaned-media

# Preview orphaned event media (dry-run)
clean-event-media-preview:
	@echo "Previewing orphaned event media records (dry-run)..."
	@php artisan events:clean-orphaned-media --dry-run

# Generate API documentation
docs:
	@echo "Generating API documentation with PHPDocumentor..."
	@php tools/phpDocumentor.phar
	@echo "Documentation generated in docs/ directory!"

# Generate database schema documentation
schema-docs:
	@echo "Generating database schema documentation with SchemaSpy..."
	@if [ -f schemaspy.properties ]; then \
		echo "Note: Graphviz is recommended for diagram generation. Install with: brew install graphviz"; \
		java -jar tools/schemaspy-6.2.4.jar -configFile schemaspy.properties || true; \
		echo "Schema documentation generated in docs/database/ directory!"; \
		echo "Open docs/database/relationships.html in your browser to view the documentation."; \
	else \
		echo "Error: schemaspy.properties file not found."; \
		echo "Please copy schemaspy.properties.example to schemaspy.properties and configure it."; \
		echo "Example: cp schemaspy.properties.example schemaspy.properties"; \
		echo "Then edit schemaspy.properties with your database connection details."; \
		exit 1; \
	fi

# Generate Entity Relationship Diagram
er-diagram:
	@echo "Generating Entity Relationship Diagram..."
	@php artisan generate:erd --format=svg docs/entity-relationship-diagram.svg
	@echo "Entity Relationship Diagram generated: docs/entity-relationship-diagram.svg"
	@echo "Open docs/entity-relationship-diagram.svg in your browser to view the diagram."

# Generate Class Diagram using PlantUML
class-diagram:
	@echo "Generating Class Diagram..."
	@if command -v plantuml >/dev/null 2>&1; then \
		echo "PlantUML found, generating class diagram..."; \
		echo "Creating PlantUML class diagram from app/ directory..."; \
		echo "@startuml" > docs/class-diagram.puml; \
		echo "title ICC Munich - Class Diagram" >> docs/class-diagram.puml; \
		echo "!theme plain" >> docs/class-diagram.puml; \
		echo "" >> docs/class-diagram.puml; \
		grep -r "^class\|^interface\|^trait\|^abstract class" app/ --include="*.php" | \
		sed 's/.*class \([A-Za-z0-9_]*\).*/class \1/' | \
		sed 's/.*interface \([A-Za-z0-9_]*\).*/interface \1/' | \
		sed 's/.*trait \([A-Za-z0-9_]*\).*/class \1 <<trait>>/' | \
		sed 's/.*abstract class \([A-Za-z0-9_]*\).*/abstract class \1/' | \
		sort | uniq | head -50 >> docs/class-diagram.puml; \
		echo "" >> docs/class-diagram.puml; \
		echo "@enduml" >> docs/class-diagram.puml; \
		plantuml -tsvg docs/class-diagram.puml; \
		echo "Class diagram generated: docs/class-diagram.svg"; \
	else \
		echo "PlantUML not found. Install with: brew install plantuml"; \
		echo "Alternatively, generating simple class list..."; \
		grep -r "^class\|^interface\|^trait\|^abstract class" app/ --include="*.php" | \
		sed 's/.*:\s*//' | sort | uniq > docs/class-list.txt; \
		echo "Class list generated: docs/class-list.txt"; \
	fi

# Generate UML Class Diagram using bartlett/umlwriter
uml-diagram:
	@echo "Generating UML Class Diagram with bartlett/umlwriter..."
	@vendor/bin/umlwriter diagram:class \
		--output=docs/uml-class-diagram.svg \
		--format=svg \
		--generator=graphviz \
		--hide-private \
		--without-constants \
		app/
	@echo "UML Class Diagram generated: docs/uml-class-diagram.svg"
	@echo "Open docs/uml-class-diagram.svg in your browser to view the diagram."

# Generate TypeScript UML Class Diagram using tsuml2
ts-uml-diagram:
	@echo "Generating TypeScript UML Class Diagram with tsuml2..."
	@tsuml2 --glob "resources/js/**/*.{ts,tsx}" --outFile docs/typescript-uml-diagram.svg
	@echo "TypeScript UML Class Diagram generated: docs/typescript-uml-diagram.svg"
	@echo "Open docs/typescript-uml-diagram.svg in your browser to view the diagram."

# Generate Use Case Diagrams using PlantUML
use-case-diagrams:
	@echo "Generating Use Case Diagrams with PlantUML..."
	@if command -v plantuml >/dev/null 2>&1; then \
		echo "PlantUML found, generating use case diagrams..."; \
		mkdir -p docs/use-cases/svg docs/use-cases/png; \
		for puml_file in docs/use-cases/*.puml; do \
			if [ -f "$$puml_file" ]; then \
				filename=$$(basename "$$puml_file" .puml); \
				echo "Generating SVG for $$filename..."; \
				plantuml -tsvg -o svg "$$puml_file"; \
				echo "Generated: docs/use-cases/svg/$$filename.svg"; \
			fi; \
		done; \
		if command -v rsvg-convert >/dev/null 2>&1; then \
			echo "Converting use case diagrams to PNG..."; \
			for svg_file in docs/use-cases/svg/*.svg; do \
				if [ -f "$$svg_file" ]; then \
					filename=$$(basename "$$svg_file" .svg); \
					rsvg-convert -f png -w 2000 -o "docs/use-cases/png/$$filename.png" "$$svg_file"; \
					echo "Generated: docs/use-cases/png/$$filename.png"; \
				fi; \
			done; \
		else \
			echo "Warning: rsvg-convert not found. PNG conversion skipped."; \
			echo "Install with: brew install librsvg"; \
		fi; \
		echo "Use case diagrams generated successfully!"; \
		echo "SVG files: docs/use-cases/svg/"; \
		echo "PNG files: docs/use-cases/png/"; \
	else \
		echo "PlantUML not found. Install with: brew install plantuml"; \
		exit 1; \
	fi

# Convert all SVG diagrams to PNG format
convert-diagrams-png:
	@echo "Converting SVG diagrams to PNG format..."
	@if command -v rsvg-convert >/dev/null 2>&1; then \
		echo "Converting diagrams using rsvg-convert..."; \
		rsvg-convert -f png -o docs/class-diagram.png docs/class-diagram.svg 2>/dev/null || echo "Skipping class-diagram.svg (may not exist)"; \
		rsvg-convert -f png -o docs/entity-relationship-diagram.png docs/entity-relationship-diagram.svg 2>/dev/null || echo "Skipping entity-relationship-diagram.svg (may not exist)"; \
		rsvg-convert -f png -w 4000 -o docs/typescript-uml-diagram.png docs/typescript-uml-diagram.svg 2>/dev/null || echo "Skipping typescript-uml-diagram.svg (may not exist)"; \
		rsvg-convert -f png -w 4000 -o docs/uml-class-diagram.png docs/uml-class-diagram.svg 2>/dev/null || echo "Skipping uml-class-diagram.svg (may not exist)"; \
		echo "PNG diagrams generated in docs/ directory!"; \
	else \
		echo "rsvg-convert not found. Install with: brew install librsvg"; \
		exit 1; \
	fi

# IDE Helper - Generate all helpers (PHPDoc, models, meta)
ide-helper: ide-helper-generate ide-helper-models ide-helper-meta
	@echo "All IDE helpers generated!"

# IDE Helper - PHPDoc generation for Laravel Facades
ide-helper-generate:
	@echo "Generating PHPDoc for Laravel Facades..."
	@php artisan ide-helper:generate

# IDE Helper - PHPDocs for models (write to model files)
ide-helper-models:
	@echo "Generating PHPDocs for models..."
	@php artisan ide-helper:models -RW --no-interaction

# IDE Helper - PhpStorm Meta file
ide-helper-meta:
	@echo "Generating PhpStorm Meta file..."
	@php artisan ide-helper:meta

# ESLint - Lint TypeScript/React code
lint:
	@echo "Running ESLint..."
	@npx eslint resources/js

# ESLint - Lint and auto-fix
lint-fix:
	@echo "Running ESLint with auto-fix..."
	@npx eslint resources/js --fix

# Prettier - Check code formatting
format-check:
	@echo "Checking code formatting with Prettier..."
	@npx prettier --check resources/js

# Prettier - Format code
format:
	@echo "Formatting code with Prettier..."
	@npx prettier --write resources/js

# Knip - Detect unused code and dependencies
knip:
	@echo "Running Knip (dead code detection)..."
	@npx knip

# Rector - Dry-run (show changes without applying)
rector:
	@echo "Running Rector (dry-run)..."
	@vendor/bin/rector process --dry-run

# Rector - Apply refactorings
rector-fix:
	@echo "Running Rector (applying refactorings)..."
	@vendor/bin/rector process

# Run all code quality checks (backend)
quality: phpstan phpcs phpmd pint pest
	@echo "All quality checks completed!"

# Run full quality checks (backend + frontend)
quality-full: phpstan psalm phpcs phpmd pint lint format-check pest
	@echo "All backend + frontend quality checks completed!"

# Run all checks and auto-fix what can be fixed
quality-fix: fix phpstan phpcs phpmd pest
	@echo "Code fixed and quality checks completed!"

# Display help
help:
	@echo "Available commands:"
	@echo ""
	@echo "📊 Code Quality (Backend):"
	@echo "  make phpstan            - Run PHPStan static analysis (level 8)"
	@echo "  make psalm              - Run Psalm static analysis"
	@echo "  make phpcs              - Run PHP_CodeSniffer (PSR-12 compliance)"
	@echo "  make phpmd              - Run PHP Mess Detector (complexity, design)"
	@echo "  make pint               - Run Laravel Pint (check code style)"
	@echo "  make fix                - Auto-fix code style with Pint"
	@echo "  make rector             - Run Rector dry-run (show changes)"
	@echo "  make rector-fix         - Run Rector (apply refactorings)"
	@echo ""
	@echo "📊 Code Quality (Frontend):"
	@echo "  make lint               - Run ESLint on TypeScript/React code"
	@echo "  make lint-fix           - Run ESLint with auto-fix"
	@echo "  make format-check       - Check formatting with Prettier"
	@echo "  make format             - Auto-format code with Prettier"
	@echo "  make knip               - Detect unused code and dependencies"
	@echo ""
	@echo "📊 Combined Quality:"
	@echo "  make quality            - Run all backend quality checks"
	@echo "  make quality-full       - Run all backend + frontend quality checks"
	@echo "  make quality-fix        - Fix code style + run all checks"
	@echo ""
	@echo "🧪 Backend Tests:"
	@echo "  make backend-test       - Run all backend tests (Pest/PHPUnit)"
	@echo "  make pest               - Run Pest tests"
	@echo "  make test               - Run PHPUnit tests"
	@echo "  make test-coverage-back - Run backend tests with coverage"
	@echo "  make test-e2e           - Run E2E tests only"
	@echo ""
	@echo "🎨 Frontend Tests:"
	@echo "  make frontend-test      - Run all frontend tests"
	@echo "  make test-front         - Run all frontend tests (alias)"
	@echo "  make test-coverage      - Run frontend tests with coverage"
	@echo "  make test-wcag          - Run accessibility tests (WCAG 2.1 AA)"
	@echo ""
	@echo "🚀 All Tests:"
	@echo "  make test-all           - Run all tests (backend + frontend)"
	@echo ""
	@echo "🔧 Utilities:"
	@echo "  make clear              - Clear all Laravel caches"
	@echo "  make db                 - Reset database with fresh migrations and seeders"
	@echo "  make clean-event-media  - Clean orphaned event media records"
	@echo "  make clean-event-media-preview - Preview orphaned records (dry-run)"
	@echo "  make composer-fix       - Fix Composer/Artisan bootstrap issues"
	@echo "  make ide-helper         - Generate all IDE helpers (facades, models, meta)"
	@echo "  make ide-helper-generate - Generate PHPDoc for Laravel Facades"
	@echo "  make ide-helper-models  - Generate PHPDocs for models"
	@echo "  make ide-helper-meta    - Generate PhpStorm Meta file"
	@echo "  make docs               - Generate API documentation with PHPDocumentor"
	@echo "  make schema-docs        - Generate database schema documentation with SchemaSpy"
	@echo "  make er-diagram         - Generate Entity Relationship Diagram (SVG)"
	@echo "  make class-diagram      - Generate Class Diagram using PlantUML or class list"
	@echo "  make uml-diagram        - Generate UML Class Diagram using bartlett/umlwriter"
	@echo "  make ts-uml-diagram     - Generate TypeScript UML Class Diagram using tsuml2"
	@echo "  make use-case-diagrams  - Generate Use Case Diagrams from PlantUML templates"
	@echo "  make convert-diagrams-png - Convert all SVG diagrams to PNG format"
	@echo "  make docs-full          - Generate complete documentation suite"
	@echo "  make docs-serve         - Serve documentation locally on port 8080"
	@echo "  make docs-clean         - Clean all generated documentation"
	@echo "  make help               - Display this help message"
	@echo ""
	@echo "🐳 Docker Commands:"
	@echo "  make docker-build       - Build Docker containers"
	@echo "  make docker-up          - Start Docker containers"
	@echo "  make docker-down        - Stop Docker containers"
	@echo "  make docker-restart     - Restart Docker containers"
	@echo "  make docker-logs        - View Docker logs"
	@echo "  make docker-shell       - Shell into PHP container"
	@echo "  make docker-mysql       - Shell into MySQL container"
	@echo "  make docker-redis       - Shell into Redis container"
	@echo "  make docker-fresh       - Fresh install (build, migrate, seed)"
	@echo "  make docker-test        - Run tests in Docker"
	@echo "  make docker-clean       - Clean all Docker resources"
	@echo "  make docker-prod-build  - Build production Docker image"
	@echo "  make docker-prod-up     - Start production containers"
	@echo ""
	@echo "🤖 Robot Framework Tests:"
	@echo "  make robot-build        - Build Robot Framework container"
	@echo "  make robot-test         - Run all Robot Framework tests"
	@echo "  make robot-api          - Run API tests only"
	@echo "  make robot-ui           - Run UI tests only"
	@echo "  make robot-e2e          - Run E2E tests only"
	@echo "  make robot-smoke        - Run smoke tests only"
	@echo "  make robot-critical     - Run critical tests only"
	@echo "  make robot-tag TAG=name - Run tests with specific tag"
	@echo "  make robot-debug        - Run tests in debug mode"
	@echo "  make robot-rerun        - Run tests with rerun on failure"
	@echo "  make robot-report       - Generate metrics report"
	@echo "  make robot-clean        - Clean test results"
	@echo "  make robot-shell        - Shell into Robot container"
	@echo ""
	@echo "🔧 Jenkins CI/CD:"
	@echo "  make jenkins-start      - Start Jenkins CI/CD server"
	@echo "  make jenkins-stop       - Stop Jenkins server"
	@echo "  make jenkins-restart    - Restart Jenkins server"
	@echo "  make jenkins-logs       - View Jenkins logs"
	@echo "  make jenkins-shell      - Shell into Jenkins container"
	@echo "  make jenkins-build      - Build Jenkins container"
	@echo "  make jenkins-clean      - Clean all Jenkins data"
	@echo ""
	@echo "🦊 GitLab CI/CD:"
	@echo "  make gitlab-start       - Start GitLab server (takes 3-5 min)"
	@echo "  make gitlab-stop        - Stop GitLab server"
	@echo "  make gitlab-restart     - Restart GitLab server"
	@echo "  make gitlab-logs        - View GitLab logs"
	@echo "  make gitlab-shell       - Shell into GitLab container"
	@echo "  make gitlab-runner-register - Register GitLab Runner"
	@echo "  make gitlab-clean       - Clean all GitLab data"
	@echo ""
	@echo "🚀 Quick Start:"
	@echo "  make setup              - Setup project from scratch (clean, build, install, migrate, seed)"
	@echo "  make start              - Start application with all services"
	@echo "  make jenkins-start      - Start Jenkins CI/CD server"
	@echo "  make gitlab-start       - Start GitLab CI/CD server"

# Generate complete documentation suite
docs-full:
	@echo "Generating complete documentation suite..."
	@chmod +x scripts/generate-docs.sh
	@./scripts/generate-docs.sh

# Serve documentation locally
docs-serve:
	@echo "Starting documentation server at http://localhost:8080"
	@echo "Press Ctrl+C to stop the server"
	@if [ -d "docs" ]; then \
		cd docs && python3 -m http.server 8080; \
	else \
		echo "Error: docs directory not found. Run 'make docs-full' first."; \
		exit 1; \
	fi

# Clean all generated documentation
docs-clean:
	@echo "Cleaning generated documentation..."
	@rm -rf docs/
	@echo "Documentation cleaned successfully!"

# ==========================================
# Docker Commands
# ==========================================

# Docker compose command detection
DOCKER_COMPOSE := $(shell command -v docker-compose 2>/dev/null || echo "docker compose")

# Detect OS
UNAME_S := $(shell uname -s 2>/dev/null || echo "Windows")

# Check and install docker-compose if needed
docker-check:
	@if command -v docker-compose &> /dev/null; then \
		echo "✅ docker-compose found"; \
	elif docker compose version &> /dev/null 2>&1; then \
		echo "✅ docker compose plugin found"; \
	else \
		echo "⚠️  docker-compose not found. Installing..."; \
		if [ "$(UNAME_S)" = "Darwin" ]; then \
			echo "📦 Installing via Homebrew (macOS)..."; \
			brew install docker-compose; \
		elif [ "$(UNAME_S)" = "Linux" ]; then \
			echo "📦 Installing via apt (Ubuntu/Debian)..."; \
			sudo apt-get update && sudo apt-get install -y docker-compose-plugin || \
			(sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$$(uname -s)-$$(uname -m)" -o /usr/local/bin/docker-compose && \
			sudo chmod +x /usr/local/bin/docker-compose); \
		else \
			echo "❌ Please install Docker Desktop for Windows from: https://docs.docker.com/desktop/install/windows-install/"; \
			echo "   Docker Desktop includes docker-compose."; \
			exit 1; \
		fi; \
	fi
	@# Check if Docker daemon is running
	@if ! docker info &> /dev/null; then \
		echo "⚠️  Docker daemon is not running."; \
		if [ "$(UNAME_S)" = "Darwin" ]; then \
			if command -v colima &> /dev/null; then \
				echo "🚀 Starting Colima..."; \
				colima start; \
			elif [ -d "/Applications/Docker.app" ]; then \
				echo "🚀 Starting Docker Desktop..."; \
				open -a Docker; \
				echo "⏳ Waiting for Docker to start (please wait)..."; \
				for i in 1 2 3 4 5 6 7 8 9 10; do echo "."; docker info > /dev/null 2>&1 && break; done; \
			else \
				echo "❌ Please start Docker Desktop or install Colima (brew install colima)"; \
				exit 1; \
			fi; \
		elif [ "$(UNAME_S)" = "Linux" ]; then \
			echo "🚀 Starting Docker service..."; \
			sudo systemctl start docker; \
		else \
			echo "❌ Please start Docker Desktop"; \
			exit 1; \
		fi; \
	else \
		echo "✅ Docker daemon is running"; \
	fi

# Terminal colors
GREEN := \033[0;32m
RESET := \033[0m

# Setup project from scratch (clean, build, install, migrate, seed, link, start node)
setup: docker-check docker-clean docker-build
	cp .env.docker .env
	docker-compose up -d mysql redis
	@echo "Waiting for MySQL to be ready..."
	@docker-compose exec -T mysql sh -c "while ! mysqladmin ping -h localhost -u root -psecret --silent; do echo 'Waiting for MySQL...'; sleep 2; done"
	@echo "Installing dependencies (app + nginx only, workers stay down to avoid load spikes)..."
	docker-compose up -d app nginx
	docker-compose exec -e XDEBUG_MODE=off app composer install
	docker-compose exec app php artisan key:generate
	docker-compose exec -e XDEBUG_MODE=off -e TELESCOPE_ENABLED=false app php artisan migrate --seed
	docker-compose exec app php artisan storage:link
	@echo "Starting remaining services (queue, scheduler, ...)..."
	docker-compose up -d
	@echo "Starting Vite dev server in watch mode (HMR)..."
	docker-compose up -d node
	@echo ""
	@echo "${GREEN}Setup complete!${RESET}"
	@echo "${GREEN}Vite tourne en mode watch : modifiez un fichier et rechargez — aucun rebuild nécessaire.${RESET}"
	@echo "⚡ Vite HMR:     http://vite.bio-digital-software-systems.localhost"
	@echo ""

# Start application with all services (main command)
start: docker-check
	@echo "Starting  application..."
	@echo "Building and pulling images..."
	@docker-compose build --pull
	@docker-compose up -d
	@echo "Waiting for MySQL to be ready..."
	@docker-compose exec -T mysql sh -c "while ! mysqladmin ping -h localhost -u root -psecret --silent; do echo 'Waiting for MySQL...'; sleep 2; done"
	@echo "Resetting database with fresh migrations and seeders..."
	@docker-compose exec -T -e XDEBUG_MODE=off -e TELESCOPE_ENABLED=false app php artisan migrate:fresh --seed --force
	@docker-compose restart queue
	@echo "Starting Vite dev server in watch mode (HMR)..."
	@docker-compose up -d node
	@echo ""
	@echo "✅ All services started!"
	@echo "✅ Vite tourne en mode watch : modifiez un fichier et rechargez — aucun rebuild nécessaire."
	@echo ""
	@echo "📱 Application:  http://bio-digital-software-systems.localhost"
	@echo "📧 Mailhog:      http://mail.bio-digital-software-systems.localhost"
	@echo "🗄️  phpMyAdmin:   http://pma.bio-digital-software-systems.localhost"
	@echo "⚡ Vite HMR:     http://vite.bio-digital-software-systems.localhost"
	@echo ""
	@echo "ℹ️  Tous les services sont routés via Traefik (port 80) — aucun port host n'est exposé pour mysql/redis/node."
	@echo "   Assurez-vous d'avoir Traefik en cours d'exécution avec le réseau 'traefik-public'."
	@echo ""
	@echo "Use 'make docker-logs' to view logs"
	@echo "Use 'make stop' to stop all services"

# Stop all Docker containers
stop:
	@echo "Stopping application..."
	@docker-compose down
	@echo ""
	@echo "✅ All services stopped!"

# Build Docker containers
docker-build:
	@echo "Building Docker containers..."
	@docker-compose build

# Start Docker containers
docker-up:
	@echo "Starting Docker containers..."
	@docker-compose up -d
	@echo ""
	@echo "Services are starting..."
	@echo "Application: http://bio-digital-software-systems.localhost"
	@echo "Mailhog:     http://mail.bio-digital-software-systems.localhost"
	@echo "phpMyAdmin:  http://pma.bio-digital-software-systems.localhost"
	@echo "Vite HMR:    http://vite.bio-digital-software-systems.localhost"

# Stop Docker containers
docker-down:
	@echo "Stopping Docker containers..."
	@docker-compose down

# Restart Docker containers
docker-restart:
	@echo "Restarting Docker containers..."
	@docker-compose restart

# View Docker logs
docker-logs:
	@docker-compose logs -f

# Shell into PHP container
docker-shell:
	@docker-compose exec app sh

# Shell into MySQL container
docker-mysql:
	@docker-compose exec mysql mysql -u root -psecret icc_munich

# Shell into Redis container
docker-redis:
	@docker-compose exec redis redis-cli

# Fresh install with Docker (builds, starts, migrates, seeds)
docker-fresh:
	@echo "Starting fresh Docker installation..."
	@docker-compose down -v
	@docker-compose build
	@docker-compose up -d
	@echo "Waiting for MySQL to be ready..."
	@docker-compose exec -T mysql sh -c "while ! mysqladmin ping -h localhost -u root -psecret --silent; do echo 'Waiting for MySQL...'; sleep 2; done"
	@docker-compose exec app php artisan key:generate --force
	@docker-compose exec -e XDEBUG_MODE=off -e TELESCOPE_ENABLED=false app php artisan migrate:fresh --seed
	@docker-compose exec app php artisan storage:link
	@echo ""
	@echo "Fresh installation complete!"
	@echo "Application: http://bio-digital-software-systems.localhost"

# Build production Docker image
docker-prod-build:
	@echo "Building production Docker image..."
	@docker-compose -f docker-compose.prod.yml build

# Start production Docker containers
docker-prod-up:
	@echo "Starting production Docker containers..."
	@docker-compose -f docker-compose.prod.yml up -d
	@echo ""
	@echo "Production services started!"
	@echo "Application: http://localhost"

# Run artisan commands in Docker
docker-artisan:
	@docker-compose exec app php artisan $(filter-out $@,$(MAKECMDGOALS))

# Run composer commands in Docker
docker-composer:
	@docker-compose exec app composer $(filter-out $@,$(MAKECMDGOALS))

# Run npm commands in Docker
docker-npm:
	@docker-compose exec node npm $(filter-out $@,$(MAKECMDGOALS))

# Run tests in Docker
docker-test:
	@echo "Running tests in Docker..."
	@docker-compose exec app php artisan test

# Clear all Docker volumes and containers
docker-clean:
	@echo "Cleaning all Docker resources..."
	@docker-compose down -v --remove-orphans
	@docker system prune -f
	@echo "Docker cleaned!"

# ==========================================
# Robot Framework Test Automation
# ==========================================

# Build Robot Framework container
robot-build:
	@echo "Building Robot Framework container..."
	@docker-compose --profile testing build robot
	@echo "Robot Framework container built successfully!"

# Run all Robot Framework tests
robot-test: robot-build
	@echo "Running all Robot Framework tests..."
	@mkdir -p robot-results
	@docker-compose --profile testing run --rm robot sh -c "robot --outputdir /robot/results --loglevel INFO --variable BASE_URL:http://nginx --variable API_URL:http://nginx/api /robot/tests"
	@echo ""
	@echo "✅ Robot Framework tests completed!"
	@echo "📊 Results available in: robot-results/"
	@echo "📄 Open robot-results/report.html to view the report"

# Run only API tests
robot-api: robot-build
	@echo "Running Robot Framework API tests..."
	@mkdir -p robot-results
	@docker-compose --profile testing run --rm robot sh -c "robot --outputdir /robot/results --loglevel INFO --include api --variable BASE_URL:http://nginx --variable API_URL:http://nginx/api /robot/tests/api"
	@echo ""
	@echo "✅ API tests completed!"
	@echo "📊 Results: robot-results/report.html"

# Run only UI tests
robot-ui: robot-build
	@echo "Running Robot Framework UI tests..."
	@mkdir -p robot-results
	@docker-compose --profile testing run --rm robot sh -c "robot --outputdir /robot/results --loglevel INFO --include ui --variable BASE_URL:http://nginx --variable BROWSER:chromium --variable HEADLESS:true /robot/tests/ui"
	@echo ""
	@echo "✅ UI tests completed!"
	@echo "📊 Results: robot-results/report.html"

# Run only E2E tests
robot-e2e: robot-build
	@echo "Running Robot Framework E2E tests..."
	@mkdir -p robot-results
	@docker-compose --profile testing run --rm robot sh -c "robot --outputdir /robot/results --loglevel INFO --include e2e --variable BASE_URL:http://nginx --variable API_URL:http://nginx/api --variable BROWSER:chromium --variable HEADLESS:true /robot/tests/e2e"
	@echo ""
	@echo "✅ E2E tests completed!"
	@echo "📊 Results: robot-results/report.html"

# Run smoke tests only (quick validation)
robot-smoke: robot-build
	@echo "Running Robot Framework smoke tests..."
	@mkdir -p robot-results
	@docker-compose --profile testing run --rm robot sh -c "robot --outputdir /robot/results --loglevel INFO --include smoke --variable BASE_URL:http://nginx --variable API_URL:http://nginx/api /robot/tests"
	@echo ""
	@echo "✅ Smoke tests completed!"
	@echo "📊 Results: robot-results/report.html"

# Run critical tests only
robot-critical: robot-build
	@echo "Running Robot Framework critical tests..."
	@mkdir -p robot-results
	@docker-compose --profile testing run --rm robot sh -c "robot --outputdir /robot/results --loglevel INFO --include critical --variable BASE_URL:http://nginx --variable API_URL:http://nginx/api /robot/tests"
	@echo ""
	@echo "✅ Critical tests completed!"
	@echo "📊 Results: robot-results/report.html"

# Run health check tests only
robot-health: robot-build
	@echo "Running Robot Framework health check tests..."
	@mkdir -p robot-results
	@docker-compose --profile testing run --rm robot sh -c "robot --outputdir /robot/results --loglevel INFO --include health --variable BASE_URL:http://nginx --variable API_URL:http://nginx/api /robot/tests/api/health.robot"
	@echo ""
	@echo "✅ Health check tests completed!"
	@echo "📊 Results: robot-results/report.html"

# Clean Robot Framework results
robot-clean:
	@echo "Cleaning Robot Framework results..."
	@rm -rf robot-results/*
	@echo "Robot Framework results cleaned!"

# Generate metrics report from Robot results
robot-report:
	@echo "Generating Robot Framework metrics report..."
	@if [ -f "robot-results/output.xml" ]; then \
		docker-compose --profile testing run --rm robot sh -c "robotmetrics --inputpath /robot/results/output.xml --output /robot/results"; \
		echo "📊 Metrics report generated: robot-results/metrics.html"; \
	else \
		echo "❌ No output.xml found. Run tests first with 'make robot-test'"; \
	fi

# Run Robot tests with specific tag
robot-tag: robot-build
	@echo "Running Robot Framework tests with tag: $(TAG)"
	@if [ -z "$(TAG)" ]; then \
		echo "❌ Please specify TAG. Example: make robot-tag TAG=smoke"; \
		exit 1; \
	fi
	@mkdir -p robot-results
	@docker-compose --profile testing run --rm robot sh -c "robot --outputdir /robot/results --loglevel INFO --include $(TAG) --variable BASE_URL:http://nginx --variable API_URL:http://nginx/api /robot/tests"
	@echo ""
	@echo "✅ Tests with tag '$(TAG)' completed!"

# Run Robot tests in debug mode (verbose output)
robot-debug: robot-build
	@echo "Running Robot Framework tests in debug mode..."
	@mkdir -p robot-results
	@docker-compose --profile testing run --rm robot sh -c "robot --outputdir /robot/results --loglevel DEBUG --debugfile /robot/results/debug.log --variable BASE_URL:http://nginx --variable API_URL:http://nginx/api /robot/tests"
	@echo ""
	@echo "✅ Debug tests completed!"
	@echo "📄 Debug log: robot-results/debug.log"

# Run Robot tests with rerun on failure
robot-rerun: robot-build
	@echo "Running Robot Framework tests with rerun on failure..."
	@mkdir -p robot-results
	@docker-compose --profile testing run --rm robot sh -c "\
		robot --outputdir /robot/results --loglevel INFO \
			--variable BASE_URL:http://nginx --variable API_URL:http://nginx/api \
			/robot/tests || \
		robot --outputdir /robot/results --rerunfailed /robot/results/output.xml \
			--output rerun.xml --loglevel INFO \
			--variable BASE_URL:http://nginx --variable API_URL:http://nginx/api \
			/robot/tests && \
		rebot --outputdir /robot/results --merge \
			/robot/results/output.xml /robot/results/rerun.xml"
	@echo ""
	@echo "✅ Tests with rerun completed!"

# Shell into Robot Framework container
robot-shell: robot-build
	@echo "Opening shell in Robot Framework container..."
	@docker-compose --profile testing run --rm robot sh

# ==========================================
# Jenkins CI/CD Commands
# ==========================================

# Build Jenkins container
jenkins-build:
	@echo "Building Jenkins CI/CD container..."
	@docker-compose -f docker-compose.jenkins.yml build
	@echo "✅ Jenkins container built successfully!"

# Start Jenkins CI/CD server
jenkins-start: docker-check
	@echo "Starting Jenkins CI/CD server..."
	@docker-compose -f docker-compose.jenkins.yml up -d
	@echo ""
	@echo "⏳ Waiting for Jenkins to start (this may take 2-3 minutes)..."
	@for i in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20; do \
		if curl -s http://localhost:8081/login > /dev/null 2>&1; then \
			echo ""; \
			break; \
		fi; \
		echo -n "."; \
		sleep 10; \
	done
	@echo ""
	@echo "✅ Jenkins CI/CD server started!"
	@echo ""
	@echo "🔗 Jenkins UI:    http://localhost:8081"
	@echo "👤 Username:      admin"
	@echo "🔑 Password:      admin123"
	@echo ""
	@echo "📂 Project workspace is mounted at: /var/jenkins_home/workspace/icc-munich"
	@echo ""
	@echo "Use 'make jenkins-logs' to view logs"
	@echo "Use 'make jenkins-stop' to stop Jenkins"

# Stop Jenkins CI/CD server
jenkins-stop:
	@echo "Stopping Jenkins CI/CD server..."
	@docker-compose -f docker-compose.jenkins.yml down
	@echo "✅ Jenkins stopped!"

# Restart Jenkins
jenkins-restart:
	@echo "Restarting Jenkins CI/CD server..."
	@docker-compose -f docker-compose.jenkins.yml restart
	@echo "✅ Jenkins restarted!"

# View Jenkins logs
jenkins-logs:
	@docker-compose -f docker-compose.jenkins.yml logs -f jenkins

# Shell into Jenkins container
jenkins-shell:
	@docker-compose -f docker-compose.jenkins.yml exec jenkins bash

# Clean Jenkins data (WARNING: removes all jobs and configurations)
jenkins-clean:
	@echo "⚠️  WARNING: This will remove all Jenkins data, jobs, and configurations!"
	@read -p "Are you sure? (y/N): " confirm && [ "$$confirm" = "y" ] || exit 1
	@docker-compose -f docker-compose.jenkins.yml down -v
	@echo "✅ Jenkins data cleaned!"

# ==========================================
# GitLab CI/CD Commands
# ==========================================

# Start GitLab CI/CD server
gitlab-start: docker-check
	@echo "Starting GitLab CI/CD server..."
	@echo "⚠️  Note: GitLab requires 4GB+ RAM and takes 3-5 minutes to start"
	@docker-compose -f docker-compose.gitlab.yml up -d
	@echo ""
	@echo "⏳ GitLab is starting (this takes 3-5 minutes)..."
	@echo "You can monitor progress with: make gitlab-logs"
	@echo ""
	@echo "🦊 GitLab will be available at:"
	@echo "   🔗 URL:       http://localhost:8929"
	@echo "   👤 Username:  root"
	@echo "   🔑 Password:  GitLab123!"
	@echo ""
	@echo "📂 Project source is mounted at: /home/git/data/icc-munich"
	@echo ""
	@echo "Use 'make gitlab-logs' to monitor startup"
	@echo "Use 'make gitlab-stop' to stop GitLab"

# Stop GitLab CI/CD server
gitlab-stop:
	@echo "Stopping GitLab CI/CD server..."
	@docker-compose -f docker-compose.gitlab.yml down
	@echo "✅ GitLab stopped!"

# Restart GitLab
gitlab-restart:
	@echo "Restarting GitLab CI/CD server..."
	@docker-compose -f docker-compose.gitlab.yml restart
	@echo "✅ GitLab restarted!"

# View GitLab logs
gitlab-logs:
	@docker-compose -f docker-compose.gitlab.yml logs -f gitlab

# Shell into GitLab container
gitlab-shell:
	@docker-compose -f docker-compose.gitlab.yml exec gitlab bash

# Register GitLab Runner
gitlab-runner-register:
	@echo "Registering GitLab Runner..."
	@echo "First, get your registration token from GitLab:"
	@echo "  1. Go to http://localhost:8929/admin/runners"
	@echo "  2. Click 'Register an instance runner'"
	@echo "  3. Copy the registration token"
	@echo ""
	@read -p "Enter the registration token: " TOKEN && \
	docker-compose -f docker-compose.gitlab.yml --profile runner up -d gitlab-runner && \
	docker-compose -f docker-compose.gitlab.yml exec gitlab-runner gitlab-runner register \
		--non-interactive \
		--url "http://gitlab:8929" \
		--registration-token "$$TOKEN" \
		--executor "docker" \
		--docker-image "docker:latest" \
		--description "icc-munich-runner" \
		--docker-privileged \
		--docker-volumes "/var/run/docker.sock:/var/run/docker.sock"
	@echo "✅ GitLab Runner registered!"

# Clean GitLab data (WARNING: removes all data)
gitlab-clean:
	@echo "⚠️  WARNING: This will remove ALL GitLab data including repositories, users, and CI/CD configurations!"
	@read -p "Are you sure? (y/N): " confirm && [ "$$confirm" = "y" ] || exit 1
	@docker-compose -f docker-compose.gitlab.yml --profile runner down -v
	@echo "✅ GitLab data cleaned!"
