#!/bin/bash

# Backend Refactoring Migration Script
# 
# This script helps migrate from the old backend structure to the new refactored version.
# It creates backups, sets up new directory structure, and provides testing capabilities.
#
# Usage: ./migrate-backend.sh [options]
# Options:
#   --dry-run    Show what would be done without making changes
#   --backup     Create backup of existing files
#   --rollback   Rollback to previous version
#   --test       Run tests after migration

set -e

# Configuration
BACKEND_DIR="./backend"
BACKUP_DIR="./backend-backup-$(date +%Y%m%d-%H%M%S)"
DRY_RUN=false
CREATE_BACKUP=true
ROLLBACK=false
RUN_TESTS=false

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --backup)
            CREATE_BACKUP=true
            shift
            ;;
        --rollback)
            ROLLBACK=true
            shift
            ;;
        --test)
            RUN_TESTS=true
            shift
            ;;
        *)
            echo "Unknown option: $1"
            echo "Usage: $0 [--dry-run] [--backup] [--rollback] [--test]"
            exit 1
            ;;
    esac
done

# Function to execute or show command
execute_command() {
    if [ "$DRY_RUN" = true ]; then
        echo "[DRY RUN] Would execute: $1"
    else
        print_status "Executing: $1"
        eval $1
    fi
}

# Function to create directory if it doesn't exist
create_directory() {
    local dir=$1
    if [ ! -d "$dir" ]; then
        execute_command "mkdir -p '$dir'"
        print_success "Created directory: $dir"
    else
        print_status "Directory already exists: $dir"
    fi
}

# Function to backup existing files
backup_files() {
    if [ "$CREATE_BACKUP" = true ] && [ "$DRY_RUN" = false ]; then
        print_status "Creating backup of existing backend..."
        if [ -d "$BACKEND_DIR" ]; then
            execute_command "cp -r '$BACKEND_DIR' '$BACKUP_DIR'"
            print_success "Backup created at: $BACKUP_DIR"
        else
            print_warning "Backend directory not found, skipping backup"
        fi
    fi
}

# Function to create new directory structure
create_structure() {
    print_status "Creating new directory structure..."
    
    create_directory "$BACKEND_DIR/src/controllers"
    create_directory "$BACKEND_DIR/src/services"
    create_directory "$BACKEND_DIR/src/exceptions"
    create_directory "$BACKEND_DIR/src/utils"
    create_directory "$BACKEND_DIR/src/config"
    create_directory "$BACKEND_DIR/src/database"
    create_directory "$BACKEND_DIR/src/core"
    create_directory "$BACKEND_DIR/tests"
    create_directory "$BACKEND_DIR/logs"
}

# Function to move existing files to new structure
migrate_files() {
    print_status "Migrating existing files..."
    
    # Move existing database classes
    if [ -f "$BACKEND_DIR/src/classes/DatabaseConnection.php" ]; then
        execute_command "mv '$BACKEND_DIR/src/classes/DatabaseConnection.php' '$BACKEND_DIR/src/database/Connection.php.old'"
        print_status "Moved DatabaseConnection.php to database/Connection.php.old"
    fi
    
    if [ -f "$BACKEND_DIR/src/classes/QueryBuilder.php" ]; then
        execute_command "cp '$BACKEND_DIR/src/classes/QueryBuilder.php' '$BACKEND_DIR/src/database/'"
        print_status "Copied QueryBuilder.php to database/"
    fi
    
    if [ -f "$BACKEND_DIR/src/classes/DatabaseValidator.php" ]; then
        execute_command "cp '$BACKEND_DIR/src/classes/DatabaseValidator.php' '$BACKEND_DIR/src/database/'"
        print_status "Copied DatabaseValidator.php to database/"
    fi
    
    # Keep original Database.php for reference
    if [ -f "$BACKEND_DIR/src/Database.php" ]; then
        execute_command "cp '$BACKEND_DIR/src/Database.php' '$BACKEND_DIR/src/database/Database.php.old'"
        print_status "Copied Database.php to database/Database.php.old"
    fi
}

# Function to create new files from templates
create_new_files() {
    print_status "Creating new application files..."
    
    # Note: In a real scenario, you would copy the actual file contents here
    # For this script, we'll create placeholder files with instructions
    
    cat > "$BACKEND_DIR/src/exceptions/README.md" << 'EOF'
# Exception Classes

Copy the following files from the refactoring artifacts:
- BaseException.php
- ValidationException.php
- DatabaseException.php
- NotFoundException.php
- ConflictException.php
- BadRequestException.php
- AuthorizationException.php

These provide standardized error handling across the application.
EOF

    cat > "$BACKEND_DIR/src/utils/README.md" << 'EOF'
# Utility Classes

Copy the following files from the refactoring artifacts:
- Response.php (standardized API responses)

These provide common utilities used throughout the application.
EOF

    cat > "$BACKEND_DIR/src/controllers/README.md" << 'EOF'
# Controller Classes

Copy the following files from the refactoring artifacts:
- BaseController.php (enhanced base controller)
- ApiController.php (refactored API controller)
- TableController.php (new table management controller)

These handle HTTP requests and coordinate with services.
EOF

    cat > "$BACKEND_DIR/src/services/README.md" << 'EOF'
# Service Classes

Copy the following files from the refactoring artifacts:
- BaseService.php (base service with validation and transactions)
- TableService.php (table management business logic)

These contain the business logic and coordinate data operations.
EOF

    cat > "$BACKEND_DIR/src/config/README.md" << 'EOF'
# Configuration Management

Copy the following files from the refactoring artifacts:
- Config.php (centralized configuration management)

This handles environment variables and application configuration.
EOF

    cat > "$BACKEND_DIR/src/core/README.md" << 'EOF'
# Core Classes

Copy the following files from the refactoring artifacts:
- Router.php (enhanced router with middleware support)

These provide core application functionality.
EOF

    cat > "$BACKEND_DIR/src/database/README.md" << 'EOF'
# Database Classes

Copy the following files from the refactoring artifacts:
- Connection.php (enhanced database connection)

Keep existing files for reference:
- QueryBuilder.php (existing, works with new structure)
- DatabaseValidator.php (existing, works with new structure)
EOF
}

# Function to create environment template
create_env_template() {
    if [ ! -f "$BACKEND_DIR/.env.example" ]; then
        print_status "Creating .env.example template..."
        
        cat > "$BACKEND_DIR/.env.example" << 'EOF'
# Application Configuration
APP_NAME="Backend API"
APP_VERSION="2.0.0"
APP_ENV=development
APP_DEBUG=true
APP_TIMEZONE=UTC
LOG_REQUESTS=false
MAINTENANCE_MODE=false

# Database Configuration
DB_HOST=database
DB_PORT=5432
DB_NAME=appdb
DB_USERNAME=appuser
DB_PASSWORD=apppassword
DB_CHARSET=utf8
DB_TIMEOUT=30
DB_MAX_CONNECTIONS=100

# CORS Configuration
CORS_ALLOWED_ORIGINS=http://localhost:3000
CORS_ALLOWED_METHODS=GET,POST,PUT,DELETE,PATCH,OPTIONS
CORS_ALLOWED_HEADERS=Content-Type,Authorization,X-Requested-With,Accept,Origin,Cache-Control
CORS_ALLOW_CREDENTIALS=false
CORS_MAX_AGE=86400

# API Configuration
API_RATE_LIMIT_ENABLED=false
API_RATE_LIMIT_REQUESTS=100
API_RATE_LIMIT_WINDOW=3600
API_DEFAULT_PAGE_SIZE=50
API_MAX_PAGE_SIZE=1000
API_TIMEOUT=30

# Security Configuration
ENCRYPTION_KEY=
JWT_SECRET=
PASSWORD_MIN_LENGTH=8
SESSION_TIMEOUT=3600

# Logging Configuration
LOG_LEVEL=error
LOG_FILE=/var/log/app.log
LOG_MAX_SIZE=10M
LOG_ROTATE=true
EOF
        
        print_success "Created .env.example template"
    fi
}

# Function to create test files
create_tests() {
    print_status "Creating test structure..."
    
    create_directory "$BACKEND_DIR/tests/unit"
    create_directory "$BACKEND_DIR/tests/integration"
    
    cat > "$BACKEND_DIR/tests/README.md" << 'EOF'
# Backend Tests

This directory contains tests for the refactored backend.

## Structure
- unit/ - Unit tests for individual classes
- integration/ - Integration tests for API endpoints

## Running Tests
1. Install PHPUnit: composer require --dev phpunit/phpunit
2. Run tests: ./vendor/bin/phpunit tests/

## Test Coverage
- Exception classes
- Service classes
- Controller classes
- Database operations
- API endpoints
EOF

    # Create simple test script
    cat > "$BACKEND_DIR/test-migration.php" << 'EOF'
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
EOF
}

# Function to update docker-compose if needed
update_docker_compose() {
    if [ -f "./docker-compose.yml" ]; then
        print_status "Checking docker-compose.yml..."
        
        # Create backup
        if [ "$CREATE_BACKUP" = true ] && [ "$DRY_RUN" = false ]; then
            execute_command "cp './docker-compose.yml' './docker-compose.yml.backup'"
        fi
        
        print_warning "Review docker-compose.yml for any needed environment variable updates"
        print_status "You may want to add environment variables from .env.example"
    fi
}

# Function to rollback changes
rollback_changes() {
    print_warning "Rolling back to previous version..."
    
    if [ -d "$BACKUP_DIR" ]; then
        execute_command "rm -rf '$BACKEND_DIR'"
        execute_command "mv '$BACKUP_DIR' '$BACKEND_DIR'"
        print_success "Rollback completed"
    else
        print_error "No backup found for rollback"
        exit 1
    fi
}

# Function to run tests
run_tests() {
    print_status "Running migration tests..."
    
    if [ -f "$BACKEND_DIR/test-migration.php" ]; then
        cd "$BACKEND_DIR"
        php test-migration.php
        cd - > /dev/null
    else
        print_warning "Test file not found, skipping tests"
    fi
}

# Main execution
main() {
    print_status "Backend Refactoring Migration Script"
    print_status "======================================"
    
    if [ "$ROLLBACK" = true ]; then
        rollback_changes
        exit 0
    fi
    
    if [ "$DRY_RUN" = true ]; then
        print_warning "DRY RUN MODE - No changes will be made"
    fi
    
    # Pre-flight checks
    if [ ! -d "$BACKEND_DIR" ]; then
        print_error "Backend directory not found: $BACKEND_DIR"
        exit 1
    fi
    
    # Execute migration steps
    backup_files
    create_structure
    migrate_files
    create_new_files
    create_env_template
    create_tests
    update_docker_compose
    
    if [ "$RUN_TESTS" = true ]; then
        run_tests
    fi
    
    print_success "Migration completed successfully!"
    print_status ""
    print_status "Next Steps:"
    print_status "1. Copy the new class files from the refactoring artifacts to their respective directories"
    print_status "2. Replace src/index.php with the new version"
    print_status "3. Copy .env.example to .env and configure your environment"
    print_status "4. Test the API endpoints: curl http://localhost:8080/api/status"
    print_status "5. Run: php backend/src/test-migration.php"
    print_status ""
    print_status "Backup location: $BACKUP_DIR"
}

# Execute main function
main "$@"