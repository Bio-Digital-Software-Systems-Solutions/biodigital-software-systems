#!/bin/bash

# Script to add CreatesPermissions trait and setUp() method to test files

FILES=(
    "tests/Feature/Security/AuthorizationTest.php"
    "tests/Feature/Security/InputValidationTest.php"
    "tests/Feature/Security/AdvancedSecurityTest.php"
    "tests/Feature/Controllers/ArticleControllerTest.php"
    "tests/Feature/Controllers/ChatControllerTest.php"
    "tests/Feature/E2E/UserRegistrationFlowTest.php"
    "tests/Feature/E2E/EventParticipationFlowTest.php"
    "tests/Feature/E2E/ArticlePublicationFlowTest.php"
    "tests/Feature/E2E/BookRentalFlowTest.php"
    "tests/Feature/Performance/DatabaseQueryOptimizationTest.php"
    "tests/Feature/Performance/CachingTest.php"
    "tests/Feature/Performance/LoadTestingTest.php"
    "tests/Unit/Models/ArticleModelTest.php"
)

for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "Processing $file..."

        # Check if file already has CreatesPermissions
        if grep -q "CreatesPermissions" "$file"; then
            echo "  ✓ Already has CreatesPermissions trait"
            continue
        fi

        # Add use statement after Tests\TestCase
        sed -i.bak '/^use Tests\\TestCase;$/a\
use Tests\\CreatesPermissions;
' "$file"

        # Add trait to class
        sed -i.bak2 's/use RefreshDatabase;/use RefreshDatabase, CreatesPermissions;/' "$file"

        # Add setUp method after class declaration with trait
        sed -i.bak3 '/use RefreshDatabase, CreatesPermissions;/a\
\
    protected function setUp(): void\
    {\
        parent::setUp();\
        $this->setupPermissions();\
    }
' "$file"

        # Remove backup files
        rm -f "${file}.bak" "${file}.bak2" "${file}.bak3"

        echo "  ✓ Added CreatesPermissions trait and setUp() method"
    else
        echo "  ✗ File not found: $file"
    fi
done

echo "Done!"
