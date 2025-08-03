<?php
/**
 * Simple Migration Test Script
 * 
 * Tests basic functionality after migration
 */

// Include the new index.php to test autoloading
$errors = [];

// Test 1: Check if new directories exist
$requiredDirs = [
    'src/controllers',
    'src/services', 
    'src/exceptions',
    'src/utils',
    'src/config',
    'src/database',
    'src/core'
];

foreach ($requiredDirs as $dir) {
    if (!is_dir(__DIR__ . '/' . $dir)) {
        $errors[] = "Missing directory: $dir";
    }
}

// Test 2: Check if README files were created
$readmeFiles = [
    'src/controllers/README.md',
    'src/services/README.md',
    'src/exceptions/README.md',
    'src/utils/README.md'
];

foreach ($readmeFiles as $file) {
    if (!file_exists(__DIR__ . '/' . $file)) {
        $errors[] = "Missing README: $file";
    }
}

// Test 3: Check if .env.example exists
if (!file_exists(__DIR__ . '/.env.example')) {
    $errors[] = "Missing .env.example file";
}

// Output results
if (empty($errors)) {
    echo "✅ Migration structure tests passed!\n";
    echo "Next steps:\n";
    echo "1. Copy the new class files from the refactoring artifacts\n";
    echo "2. Update your index.php with the new version\n";
    echo "3. Test the API endpoints\n";
    echo "4. Copy .env.example to .env and configure\n";
} else {
    echo "❌ Migration tests failed:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    exit(1);
}
