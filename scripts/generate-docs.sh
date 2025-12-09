#!/bin/bash
set -e

echo "========================================="
echo "   ICC Munich Documentation Generator"
echo "========================================="

# Couleurs pour l'output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Vérifier qu'on est dans le bon dossier
if [ ! -f "composer.json" ] || [ ! -f "artisan" ]; then
    echo -e "${RED}Error: This script must be run from the Laravel project root directory${NC}"
    exit 1
fi

# Créer les dossiers de documentation
echo -e "${BLUE}[1/8]${NC} Creating documentation directories..."
mkdir -p docs/{api-php,api-typescript,database,uml,diagrams,use-cases}
mkdir -p tools

# Fonction pour télécharger les outils si nécessaire
download_tools() {
    echo -e "${BLUE}[2/8]${NC} Checking/downloading documentation tools..."

    # PHPDocumentor
    if [ ! -f "tools/phpDocumentor.phar" ]; then
        echo "  → Downloading PHPDocumentor..."
        curl -sL -o tools/phpDocumentor.phar https://github.com/phpDocumentor/phpDocumentor/releases/download/v3.4.3/phpDocumentor.phar
        chmod +x tools/phpDocumentor.phar
    fi

    # PlantUML
    if [ ! -f "tools/plantuml.jar" ]; then
        echo "  → Downloading PlantUML..."
        curl -sL -o tools/plantuml.jar https://github.com/plantuml/plantuml/releases/download/v1.2024.3/plantuml-1.2024.3.jar
    fi

    # SchemaSpy
    if [ ! -f "tools/schemaspy-6.2.4.jar" ]; then
        echo "  → Downloading SchemaSpy..."
        curl -sL -o tools/schemaspy-6.2.4.jar https://github.com/schemaspy/schemaspy/releases/download/v6.2.4/schemaspy-6.2.4.jar
    fi

    # MySQL Connector
    if [ ! -f "tools/mysql-connector-j-8.0.33.jar" ]; then
        echo "  → Downloading MySQL Connector..."
        curl -sL -o tools/mysql-connector-j-8.0.33.jar https://repo1.maven.org/maven2/com/mysql/mysql-connector-j/8.0.33/mysql-connector-j-8.0.33.jar
    fi
}

# Installer les dépendances
setup_dependencies() {
    echo -e "${BLUE}[3/8]${NC} Setting up dependencies..."

    # Vérifier et installer les dépendances Composer
    if [ ! -d "vendor" ] || [ "composer.json" -nt "vendor/autoload.php" ]; then
        echo "  → Installing PHP dependencies..."
        composer install --no-interaction --prefer-dist --optimize-autoloader
    fi

    # Vérifier et installer les dépendances Node
    if [ ! -d "node_modules" ] || [ "package.json" -nt "node_modules" ]; then
        echo "  → Installing Node.js dependencies..."
        npm ci --legacy-peer-deps
    fi

    # Vérifier tsuml2
    if ! command -v tsuml2 >/dev/null 2>&1; then
        echo "  → Installing tsuml2 globally..."
        npm install -g tsuml2 --legacy-peer-deps
    fi
}

# Génération PHP Documentation
generate_php_docs() {
    echo -e "${BLUE}[4/8]${NC} Generating PHP documentation with PHPDocumentor..."

    if [ -f "tools/phpDocumentor.phar" ]; then
        php tools/phpDocumentor.phar run -d app -t docs/api-php --title="ICC Munich - PHP API Documentation" || {
            echo -e "${YELLOW}  Warning: PHPDocumentor generation had issues${NC}"
        }
    else
        echo -e "${RED}  Error: PHPDocumentor not found${NC}"
        return 1
    fi
}

# Génération TypeScript Documentation
generate_typescript_docs() {
    echo -e "${BLUE}[5/8]${NC} Generating TypeScript documentation..."

    if [ -f "typedoc.json" ]; then
        npx typedoc || echo -e "${YELLOW}  Warning: TypeDoc generation had issues${NC}"
    else
        echo "  → Creating TypeDoc configuration..."
        npx typedoc --out docs/api-typescript resources/js --name "ICC Munich - TypeScript Documentation" || {
            echo -e "${YELLOW}  Warning: TypeScript docs generation had issues${NC}"
        }
    fi
}

# Génération des diagrammes UML
generate_uml_diagrams() {
    echo -e "${BLUE}[6/8]${NC} Generating UML diagrams..."

    # PHP UML avec bartlett/umlwriter
    echo "  → Generating comprehensive PHP UML diagram..."
    make uml-diagram >/dev/null 2>&1 || echo -e "${YELLOW}    Warning: PHP UML diagram generation failed${NC}"

    # TypeScript UML avec tsuml2
    echo "  → Generating TypeScript UML diagram..."
    make ts-uml-diagram >/dev/null 2>&1 || echo -e "${YELLOW}    Warning: TypeScript UML diagram generation failed${NC}"

    # PlantUML class diagram
    echo "  → Generating PlantUML class diagram..."
    make class-diagram >/dev/null 2>&1 || echo -e "${YELLOW}    Warning: Class diagram generation failed${NC}"

    # Entity Relationship diagram
    echo "  → Generating Entity Relationship diagram..."
    make er-diagram >/dev/null 2>&1 || echo -e "${YELLOW}    Warning: ER diagram generation failed${NC}"

    # Use Case diagrams
    echo "  → Generating Use Case diagrams..."
    make use-case-diagrams >/dev/null 2>&1 || echo -e "${YELLOW}    Warning: Use Case diagram generation failed${NC}"
}

# Génération de la documentation base de données
generate_database_docs() {
    echo -e "${BLUE}[7/8]${NC} Generating database documentation..."

    if [ -f "schemaspy.properties" ] || [ -f "schemaspy.properties.example" ]; then
        # Copier l'exemple si le fichier de config n'existe pas
        if [ ! -f "schemaspy.properties" ] && [ -f "schemaspy.properties.example" ]; then
            cp schemaspy.properties.example schemaspy.properties
            echo -e "${YELLOW}  → Created schemaspy.properties from example. Please configure database settings.${NC}"
        fi

        if [ -n "$DB_HOST" ] && [ -n "$DB_DATABASE" ]; then
            make schema-docs >/dev/null 2>&1 || echo -e "${YELLOW}    Warning: Database documentation generation failed${NC}"
        else
            echo -e "${YELLOW}  → Skipping database docs (no DB configuration found)${NC}"
        fi
    else
        echo -e "${YELLOW}  → Skipping database docs (no schemaspy.properties found)${NC}"
    fi
}

# Conversion et organisation des fichiers
finalize_docs() {
    echo -e "${BLUE}[8/8]${NC} Finalizing documentation..."

    # Convertir les SVG en PNG
    echo "  → Converting SVG diagrams to PNG..."
    make convert-diagrams-png >/dev/null 2>&1 || echo -e "${YELLOW}    Warning: SVG to PNG conversion failed${NC}"

    # Copier les diagrammes dans le dossier diagrams
    echo "  → Organizing diagram files..."
    cp docs/*.svg docs/diagrams/ 2>/dev/null || true
    cp docs/*.png docs/diagrams/ 2>/dev/null || true
    cp docs/use-cases/svg/*.svg docs/diagrams/ 2>/dev/null || true
    cp docs/use-cases/png/*.png docs/diagrams/ 2>/dev/null || true

    # Générer l'index de documentation
    echo "  → Generating documentation index..."
    generate_index

    # Calculer les tailles
    local total_size=$(du -sh docs/ 2>/dev/null | cut -f1 || echo "unknown")
    echo -e "${GREEN}  ✓ Documentation generated successfully (${total_size})${NC}"
}

# Générer l'index HTML
generate_index() {
    cat > docs/index.html << 'EOF'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICC Munich - Documentation</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1100px;
            margin: 0 auto;
            padding: 40px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        .container {
            background: rgba(255,255,255,0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        h1 {
            color: #1a1a2e;
            margin-bottom: 10px;
            font-size: 2.5em;
            text-align: center;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .timestamp {
            color: #666;
            font-size: 14px;
            margin-bottom: 40px;
            text-align: center;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
            border-color: #667eea;
        }
        .card h2 {
            margin: 0 0 15px 0;
            font-size: 20px;
            color: #1a1a2e;
        }
        .card p {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .card a {
            color: #667eea;
            text-decoration: none;
            display: block;
            padding: 8px 0;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .card a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        .icon {
            font-size: 32px;
            margin-bottom: 15px;
            display: block;
        }
        .badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 15px;
        }
        .stats {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 25px;
            border-radius: 15px;
            margin: 30px 0;
            text-align: center;
            border: 1px solid #dee2e6;
        }
        .commands {
            background: #1a1a2e;
            color: #fff;
            padding: 30px;
            border-radius: 15px;
            margin-top: 40px;
        }
        .commands h2 {
            color: #fff;
            margin-top: 0;
        }
        .commands pre {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 10px;
            overflow-x: auto;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .file-link {
            background: rgba(102, 126, 234, 0.1);
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid rgba(102, 126, 234, 0.2);
            transition: all 0.3s ease;
        }
        .file-link:hover {
            background: rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📚 ICC Munich Documentation</h1>
        <p class="timestamp">Generated: TIMESTAMP_PLACEHOLDER</p>

        <div class="stats">
            <strong>🚀 Comprehensive Laravel + React Documentation Suite</strong><br>
            Auto-generated from source code with UML diagrams, API documentation, and database schema
        </div>

        <div class="grid">
            <div class="card">
                <span class="icon">🐘</span>
                <h2>PHP API Documentation</h2>
                <p>Complete Laravel backend API documentation generated with PHPDocumentor. Includes all controllers, models, services, and middleware.</p>
                <a href="api-php/index.html">→ Browse PHP Documentation</a>
                <span class="badge">PHPDocumentor</span>
            </div>

            <div class="card">
                <span class="icon">⚛️</span>
                <h2>TypeScript Documentation</h2>
                <p>React frontend components, hooks, types, and utilities documentation with full TypeScript support.</p>
                <a href="api-typescript/index.html">→ Browse TypeScript Docs</a>
                <span class="badge">TypeDoc</span>
            </div>

            <div class="card">
                <span class="icon">🗄️</span>
                <h2>Database Schema</h2>
                <p>Interactive database schema documentation with table relationships, constraints, and detailed views.</p>
                <a href="database/index.html">→ View Database Schema</a>
                <span class="badge">SchemaSpy</span>
            </div>

            <div class="card">
                <span class="icon">📊</span>
                <h2>Architecture Diagrams</h2>
                <p>Visual representation of application architecture, class relationships, system design, and use cases.</p>
                <div class="file-grid">
                    <a href="diagrams/uml-class-diagram.svg" class="file-link">PHP UML (Comprehensive)</a>
                    <a href="diagrams/typescript-uml-diagram.svg" class="file-link">TypeScript UML</a>
                    <a href="diagrams/class-diagram.svg" class="file-link">Class Overview</a>
                    <a href="diagrams/entity-relationship-diagram.svg" class="file-link">Database ER Diagram</a>
                    <a href="use-cases/svg/" class="file-link">Use Case Diagrams</a>
                </div>
                <span class="badge">Multiple Tools</span>
            </div>

            <div class="card">
                <span class="icon">🖼️</span>
                <h2>High-Resolution Images</h2>
                <p>PNG versions of all diagrams optimized for presentations, documentation, and printing.</p>
                <div class="file-grid">
                    <a href="diagrams/uml-class-diagram.png" class="file-link">PHP UML (PNG)</a>
                    <a href="diagrams/typescript-uml-diagram.png" class="file-link">TypeScript UML (PNG)</a>
                    <a href="diagrams/class-diagram.png" class="file-link">Class Overview (PNG)</a>
                    <a href="diagrams/entity-relationship-diagram.png" class="file-link">ER Diagram (PNG)</a>
                    <a href="use-cases/png/" class="file-link">Use Case Diagrams (PNG)</a>
                </div>
                <span class="badge">4K Resolution</span>
            </div>

            <div class="card">
                <span class="icon">🛠️</span>
                <h2>Development Tools</h2>
                <p>Makefile commands, CI/CD integration, and development workflow documentation.</p>
                <a href="#makefile-commands">→ Available Commands</a>
                <a href="https://github.com/anthropics/claude-code">→ Built with Claude Code</a>
                <span class="badge">Developer Tools</span>
            </div>
        </div>

        <div id="makefile-commands" class="commands">
            <h2>🔧 Available Makefile Commands</h2>
            <pre># Documentation Generation
make docs                    # Generate PHPDocumentor API documentation
make uml-diagram            # Generate PHP UML with bartlett/umlwriter
make ts-uml-diagram         # Generate TypeScript UML with tsuml2
make class-diagram          # Generate class diagram with PlantUML
make use-case-diagrams      # Generate Use Case diagrams from PlantUML templates
make er-diagram             # Generate Entity Relationship diagram
make schema-docs            # Generate database schema with SchemaSpy
make convert-diagrams-png   # Convert all SVG diagrams to PNG format

# Quality & Testing
make quality                # Run all code quality checks (PHPStan, PHPCS, PHPMD, Pint)
make test-all              # Run all tests (backend + frontend)
make test-coverage         # Run frontend tests with coverage
make test-e2e              # Run end-to-end tests

# Utilities
make clear                 # Clear all Laravel caches
make fix                   # Auto-fix code style issues
make help                  # Display all available commands

# Local documentation generation
./scripts/generate-docs.sh  # Generate all documentation locally</pre>
        </div>
    </div>
</body>
</html>
EOF

    # Remplacer le timestamp
    if command -v sed >/dev/null 2>&1; then
        sed -i.bak "s/TIMESTAMP_PLACEHOLDER/$(date '+%Y-%m-%d %H:%M:%S')/" docs/index.html && rm docs/index.html.bak
    fi
}

# Fonction principale
main() {
    echo -e "${GREEN}Starting documentation generation for ICC Munich...${NC}"

    download_tools
    setup_dependencies
    generate_php_docs
    generate_typescript_docs
    generate_uml_diagrams
    generate_database_docs
    finalize_docs

    echo ""
    echo -e "${GREEN}=========================================${NC}"
    echo -e "${GREEN}   Documentation Generated Successfully${NC}"
    echo -e "${GREEN}=========================================${NC}"
    echo ""
    echo -e "📁 Documentation location: ${BLUE}./docs/${NC}"
    echo -e "🌐 Open in browser: ${BLUE}./docs/index.html${NC}"
    echo -e "🚀 Serve locally: ${YELLOW}cd docs && python3 -m http.server 8080${NC}"
    echo ""
    echo -e "📊 Generated files:"
    echo -e "  • PHP API Documentation (PHPDocumentor)"
    echo -e "  • TypeScript Documentation (TypeDoc)"
    echo -e "  • UML Class Diagrams (SVG + PNG)"
    echo -e "  • Use Case Diagrams (SVG + PNG)"
    echo -e "  • Database Schema (SchemaSpy)"
    echo -e "  • Entity Relationship Diagrams"
    echo ""
}

# Gérer les signaux pour un arrêt propre
trap 'echo -e "\n${RED}Documentation generation interrupted${NC}"; exit 1' INT TERM

# Lancer la génération
main "$@"