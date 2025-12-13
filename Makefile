.PHONY: phpstan phpcs phpmd pint pest test clear quality fix help test-front test-coverage test-wcag test-all test-coverage-back test-e2e docs schema-docs er-diagram class-diagram uml-diagram ts-uml-diagram use-case-diagrams convert-diagrams-png docs-full docs-serve docs-clean start stop docker-build docker-up docker-down docker-restart docker-logs docker-shell docker-mysql docker-redis docker-fresh docker-prod-build docker-prod-up

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
	@echo "🚀 Quick Start:"
	@echo "  make start              - Start application with all services"

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

# Start application with all services (main command)
start: docker-check
	@echo "Starting ICC Munich application..."
	@echo "Building and pulling images..."
	@docker-compose build --pull
	@docker-compose up -d
	@echo "Waiting for MySQL to be ready..."
	@docker-compose exec -T mysql sh -c "while ! mysqladmin ping -h localhost -u root -psecret --silent; do echo 'Waiting for MySQL...'; sleep 2; done"
	@echo "Resetting database with fresh migrations and seeders..."
	@docker-compose exec -T app php artisan migrate:fresh --seed --force
	@docker-compose restart queue
	@echo ""
	@echo "✅ All services started!"
	@echo ""
	@echo "📱 Application:  http://localhost:8000"
	@echo "📧 Mailhog:      http://localhost:8025"
	@echo "🗄️  phpMyAdmin:   http://localhost:8080"
	@echo "⚡ Vite HMR:     http://localhost:5173"
	@echo ""
	@echo "Use 'make docker-logs' to view logs"
	@echo "Use 'make stop' to stop all services"

# Stop all Docker containers
stop:
	@echo "Stopping ICC Munich application..."
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
	@echo "Application: http://localhost:8000"
	@echo "Mailhog:     http://localhost:8025"
	@echo "phpMyAdmin:  http://localhost:8080"
	@echo "Vite HMR:    http://localhost:5173"

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
	@docker-compose exec app php artisan migrate:fresh --seed
	@docker-compose exec app php artisan storage:link
	@echo ""
	@echo "Fresh installation complete!"
	@echo "Application: http://localhost:8000"

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
