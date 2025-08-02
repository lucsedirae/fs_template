#!/bin/bash

# Database Migration Runner with Enhanced Debugging
# This script runs all SQL files in the db/init directory against an existing database

set -e

echo "🚀 Running database migrations..."

# Database connection details
DB_HOST="localhost"
DB_PORT="5432"
DB_NAME="appdb"
DB_USER="appuser"
DB_PASSWORD="apppassword"

# Directory containing SQL scripts
SCRIPT_DIR="../db/init"

# Check if the directory exists
if [ ! -d "$SCRIPT_DIR" ]; then
    echo "❌ Error: Directory $SCRIPT_DIR does not exist"
    exit 1
fi

echo "📁 Checking directory: $SCRIPT_DIR"

# List all SQL files found
echo "📋 Found SQL files:"
file_count=0
for file in "$SCRIPT_DIR"/*.sql; do
    if [ -f "$file" ]; then
        echo "   - $(basename "$file")"
        file_count=$((file_count + 1))
    fi
done

if [ $file_count -eq 0 ]; then
    echo "📭 No SQL files found in $SCRIPT_DIR"
    exit 0
fi

echo "   Total: $file_count file(s)"
echo ""

# Function to execute SQL file with enhanced error reporting
execute_sql_file() {
    local file=$1
    local filename=$(basename "$file")
    
    echo "📄 Executing: $filename"
    echo "   File path: $file"
    echo "   File size: $(stat -c%s "$file") bytes"
    
    # Check if file is readable
    if [ ! -r "$file" ]; then
        echo "❌ Error: Cannot read file $filename"
        return 1
    fi
    
    # Show first few lines of the file for verification
    echo "   Preview (first 3 lines):"
    head -n 3 "$file" | sed 's/^/      /'
    
    # Execute the script with enhanced error reporting
    if docker-compose exec -T database psql -U "$DB_USER" -d "$DB_NAME" -f "/docker-entrypoint-initdb.d/$filename" 2>&1; then
        echo "✅ Successfully executed: $filename"
        return 0
    else
        local exit_code=$?
        echo "❌ Error executing: $filename"
        echo "   Exit code: $exit_code"
        
        # Try to get more detailed error information
        echo "   Running with verbose output for debugging:"
        docker-compose exec -T database psql -U "$DB_USER" -d "$DB_NAME" -v ON_ERROR_STOP=1 -f "/docker-entrypoint-initdb.d/$filename" 2>&1 || true
        
        return $exit_code
    fi
}

# Check if database container is running
echo "🔍 Checking database container status..."
if ! docker-compose ps database | grep -q "Up"; then
    echo "❌ Error: Database container is not running"
    echo "   Please run: docker-compose up -d database"
    exit 1
fi

echo "✅ Database container is running"

# Wait for database to be ready
echo "⏳ Waiting for database to be ready..."
timeout=30
while ! docker-compose exec -T database pg_isready -U "$DB_USER" -d "$DB_NAME" > /dev/null 2>&1; do
    timeout=$((timeout - 1))
    if [ $timeout -le 0 ]; then
        echo "❌ Error: Database did not become ready in time"
        echo "   Check database logs: docker-compose logs database"
        exit 1
    fi
    echo "   Waiting... ($timeout seconds remaining)"
    sleep 1
done

echo "✅ Database is ready"
echo ""

# Test database connection
echo "🔌 Testing database connection..."
if docker-compose exec -T database psql -U "$DB_USER" -d "$DB_NAME" -c "SELECT version();" > /dev/null 2>&1; then
    echo "✅ Database connection successful"
else
    echo "❌ Error: Cannot connect to database"
    exit 1
fi

echo ""
echo "🔄 Processing migration files:"
echo ""

# Process all SQL files in alphabetical order
success_count=0
error_count=0

for file in "$SCRIPT_DIR"/*.sql; do
    if [ -f "$file" ]; then
        if execute_sql_file "$file"; then
            success_count=$((success_count + 1))
        else
            error_count=$((error_count + 1))
            echo ""
            echo "⚠️  Continuing with remaining files..."
        fi
        echo ""
    fi
done

# Summary
echo "📊 Migration Summary:"
echo "   Total files processed: $((success_count + error_count))"
echo "   Successful: $success_count"
echo "   Failed: $error_count"

if [ $error_count -eq 0 ]; then
    echo ""
    echo "🎉 All migrations completed successfully!"
else
    echo ""
    echo "⚠️  Some migrations failed. Check the output above for details."
    exit 1
fi

echo ""
echo "🔍 Verifying tables in database:"
docker-compose exec -T database psql -U "$DB_USER" -d "$DB_NAME" -c "\dt" 2>/dev/null || echo "   Could not list tables"

echo ""
echo "🏁 Migration process completed!"