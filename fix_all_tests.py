#!/usr/bin/env python3
"""
Script to add CreatesPermissions trait and setUp() method to all test files
"""

import os
import re

# List of test files to fix
test_files = [
    "tests/Feature/Security/InputValidationTest.php",
    "tests/Feature/Security/AdvancedSecurityTest.php",
    "tests/Feature/Controllers/ArticleControllerTest.php",
    "tests/Feature/Controllers/ChatControllerTest.php",
    "tests/Feature/E2E/UserRegistrationFlowTest.php",
    "tests/Feature/E2E/EventParticipationFlowTest.php",
    "tests/Feature/E2E/ArticlePublicationFlowTest.php",
    "tests/Feature/E2E/BookRentalFlowTest.php",
    "tests/Feature/Performance/DatabaseQueryOptimizationTest.php",
    "tests/Feature/Performance/CachingTest.php",
    "tests/Feature/Performance/LoadTestingTest.php",
    "tests/Unit/Models/ArticleModelTest.php",
]

def add_permissions_trait(filepath):
    """Add CreatesPermissions trait and setUp() method to a test file"""

    if not os.path.exists(filepath):
        print(f"❌ File not found: {filepath}")
        return False

    with open(filepath, 'r') as f:
        content = f.read()

    # Check if already has CreatesPermissions
    if 'CreatesPermissions' in content:
        print(f"✓ {filepath} already has CreatesPermissions")
        return True

    # Add use statement for CreatesPermissions
    content = re.sub(
        r'(use Tests\\TestCase;)',
        r'\1\nuse Tests\\CreatesPermissions;',
        content
    )

    # Add trait to class
    content = re.sub(
        r'(\s+use RefreshDatabase;)',
        r'\1, CreatesPermissions;',
        content
    )

    # Remove the original "use RefreshDatabase;" if we added ", CreatesPermissions"
    content = re.sub(
        r'(\s+use RefreshDatabase;), CreatesPermissions;\s+use RefreshDatabase;',
        r'\1, CreatesPermissions;',
        content
    )

    # Add setUp() method after "use RefreshDatabase, CreatesPermissions;"
    setup_method = '''
    protected function setUp(): void
    {
        parent::setUp();
        $this->setupPermissions();
    }'''

    # Find the pattern and add setUp if not exists
    if 'protected function setUp' not in content:
        content = re.sub(
            r'(\s+use RefreshDatabase, CreatesPermissions;)',
            r'\1' + setup_method,
            content
        )

    # Write back
    with open(filepath, 'w') as f:
        f.write(content)

    print(f"✅ Fixed: {filepath}")
    return True

def main():
    print("🔧 Fixing test files...\n")

    success_count = 0
    fail_count = 0

    for filepath in test_files:
        if add_permissions_trait(filepath):
            success_count += 1
        else:
            fail_count += 1

    print(f"\n📊 Summary:")
    print(f"   ✅ Success: {success_count}")
    print(f"   ❌ Failed: {fail_count}")
    print(f"   📁 Total: {len(test_files)}")

if __name__ == "__main__":
    main()
