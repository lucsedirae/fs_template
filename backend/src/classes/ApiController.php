<?php
/**
 * API Controller Class
 * 
 * Handles all API endpoint requests for the application including status checks,
 * database operations, and table management functionality.
 * 
 * This controller provides RESTful API endpoints for:
 * - System status and health checks
 * - Database connectivity testing
 * - Table creation, listing, and deletion
 * - Basic application information
 * 
 * @package    Backend\Classes
 * @author     Your Team
 * @version    1.0.0
 * @since      PHP 7.4
 * @requires   Database.php
 */

require_once __DIR__ . '/../Database.php';

class ApiController
{

    /**
     * Database instance for handling data operations
     * 
     * @var Database The database connection and operations handler
     */
    private $database;

    /**
     * ApiController constructor
     * 
     * Initializes the controller with a database connection instance.
     * 
     * @throws Exception If database connection cannot be established
     */
    public function __construct()
    {
        $this->database = new Database();
    }

    /**
     * Handle the root API endpoint
     * 
     * Returns basic API information including version and available endpoints.
     * This serves as a discovery endpoint for API consumers.
     * 
     * @return void Outputs JSON response with API information
     * 
     * @api GET /api
     * @api GET /
     * 
     * @response 200 {
     *   "message": "PHP Backend API",
     *   "version": "1.0.0",
     *   "available_endpoints": ["array of endpoint descriptions"]
     * }
     */
    public function handleRoot(): void
    {
        $response = [
            'message' => 'PHP Backend API',
            'version' => '1.0.0',
            'available_endpoints' => [
                'GET /api/hello - Hello world endpoint',
                'GET /api/status - Service status',
                'GET /api/db-test - Database connection test',
                'GET /api/tables - List all tables',
                'POST /api/tables - Create a new table',
                'DELETE /api/tables/{name} - Delete a table'
            ]
        ];

        http_response_code(200);
        echo json_encode($response, JSON_PRETTY_PRINT);
    }

    /**
     * Handle the hello endpoint
     * 
     * Returns a friendly greeting message along with server information.
     * Useful for testing basic connectivity and server response.
     * 
     * @return void Outputs JSON response with greeting and server info
     * 
     * @api GET /api/hello
     * 
     * @response 200 {
     *   "message": "Hello from PHP Backend!",
     *   "timestamp": "2024-01-01 12:00:00",
     *   "server": "PHP 8.2.0",
     *   "container": "hostname"
     * }
     */
    public function handleHello(): void
    {
        $response = [
            'message' => 'Hello from PHP Backend!',
            'timestamp' => date('Y-m-d H:i:s'),
            'server' => 'PHP ' . phpversion(),
            'container' => gethostname()
        ];

        http_response_code(200);
        echo json_encode($response, JSON_PRETTY_PRINT);
    }

    /**
     * Handle the status endpoint
     * 
     * Returns comprehensive status information about the backend service
     * including version, environment, and performance metrics.
     * 
     * @return void Outputs JSON response with status information
     * 
     * @api GET /api/status
     * 
     * @response 200 {
     *   "status": "running",
     *   "version": "1.0.0",
     *   "environment": "docker",
     *   "php_version": "8.2.0",
     *   "timestamp": "2024-01-01 12:00:00",
     *   "uptime": "0.123s"
     * }
     */
    public function handleStatus(): void
    {
        $response = [
            'status' => 'running',
            'version' => '1.0.0',
            'environment' => 'docker',
            'php_version' => phpversion(),
            'timestamp' => date('Y-m-d H:i:s'),
            'uptime' => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3) . 's'
        ];

        http_response_code(200);
        echo json_encode($response, JSON_PRETTY_PRINT);
    }

    /**
     * Handle the database test endpoint
     * 
     * Tests the database connection and returns connection status.
     * Useful for health checks and troubleshooting database connectivity.
     * 
     * @return void Outputs JSON response with database test results
     * 
     * @api GET /api/db-test
     * 
     * @response 200 {
     *   "status": "success",
     *   "message": "Database connection successful",
     *   "postgres_version": "PostgreSQL 15.x"
     * }
     * 
     * @response 500 {
     *   "status": "error",
     *   "message": "Failed to connect to database"
     * }
     */
    public function handleDatabaseTest(): void
    {
        $result = $this->database->testConnection();

        if ($result['status'] === 'success') {
            http_response_code(200);
        } else {
            http_response_code(500);
        }

        echo json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Handle the get all tables endpoint
     * 
     * Retrieves a list of all tables in the database with their metadata.
     * Returns table names, types, and count information.
     * 
     * @return void Outputs JSON response with tables list
     * 
     * @api GET /api/tables
     * 
     * @response 200 {
     *   "status": "success",
     *   "tables": [
     *     {
     *       "table_name": "users",
     *       "table_type": "BASE TABLE"
     *     }
     *   ],
     *   "count": 1
     * }
     * 
     * @response 500 {
     *   "status": "error",
     *   "message": "Failed to retrieve tables"
     * }
     */
    public function handleGetTables(): void
    {
        $result = $this->database->getTables();

        if ($result['status'] === 'success') {
            http_response_code(200);
        } else {
            http_response_code(500);
        }

        echo json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Handle the create table endpoint
     * 
     * Creates a new database table with the specified name and column definitions.
     * Validates input data and returns creation status.
     * 
     * @return void Outputs JSON response with creation result
     * 
     * @api POST /api/tables
     * 
     * @bodyParam string tableName required The name of the table to create
     * @bodyParam array columns required Array of column definitions
     * @bodyParam string columns[].name required Column name
     * @bodyParam string columns[].type required Column data type
     * @bodyParam boolean columns[].isPrimary optional Whether column is primary key
     * @bodyParam boolean columns[].nullable optional Whether column allows NULL values
     * 
     * @request {
     *   "tableName": "users",
     *   "columns": [
     *     {
     *       "name": "id",
     *       "type": "SERIAL",
     *       "isPrimary": true,
     *       "nullable": false
     *     },
     *     {
     *       "name": "email",
     *       "type": "VARCHAR(255)",
     *       "isPrimary": false,
     *       "nullable": false
     *     }
     *   ]
     * }
     * 
     * @response 201 {
     *   "status": "success",
     *   "message": "Table 'users' created successfully",
     *   "sql": "CREATE TABLE..."
     * }
     * 
     * @response 400 {
     *   "status": "error",
     *   "message": "Missing required fields: tableName and columns"
     * }
     * 
     * @response 400 {
     *   "status": "error",
     *   "message": "Table 'users' already exists"
     * }
     */
    public function handleCreateTable(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['tableName']) || !isset($input['columns'])) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing required fields: tableName and columns'
            ]);
            return;
        }

        $result = $this->database->createTable($input['tableName'], $input['columns']);

        if ($result['status'] === 'success') {
            http_response_code(201);
        } else {
            http_response_code(400);
        }

        echo json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Handle the delete table endpoint
     * 
     * Deletes the specified table from the database if it exists.
     * This operation is irreversible and will remove all data.
     * 
     * @param string $tableName The name of the table to delete
     * 
     * @return void Outputs JSON response with deletion result
     * 
     * @api DELETE /api/tables/{tableName}
     * 
     * @urlParam string tableName required The name of the table to delete
     * 
     * @response 200 {
     *   "status": "success",
     *   "message": "Table 'users' deleted successfully"
     * }
     * 
     * @response 400 {
     *   "status": "error",
     *   "message": "Table 'users' does not exist"
     * }
     * 
     * @response 400 {
     *   "status": "error",
     *   "message": "Invalid table name"
     * }
     */
    public function handleDeleteTable(string $tableName): void
    {
        $result = $this->database->dropTable($tableName);

        if ($result['status'] === 'success') {
            http_response_code(200);
        } else {
            http_response_code(400);
        }

        echo json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Handle method not allowed responses
     * 
     * Returns an error response when a valid endpoint is accessed with
     * an unsupported HTTP method.
     * 
     * @return void Outputs JSON error response
     * 
     * @response 405 {
     *   "error": "Method not allowed"
     * }
     */
    public function handleMethodNotAllowed(): void
    {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }

    /**
     * Get the database instance
     * 
     * Returns the current database instance for use in other methods or testing.
     * 
     * @return Database The database connection instance
     */
    public function getDatabase(): Database
    {
        return $this->database;
    }

    /**
     * Handle the get table data endpoint
     * 
     * Retrieves data from a specific table with pagination support.
     * 
     * @param string $tableName The name of the table to retrieve data from
     * 
     * @return void Outputs JSON response with table data
     * 
     * @api GET /api/tables/{tableName}/data
     * 
     * @queryParam int limit optional Number of rows to return (default: 100, max: 1000)
     * @queryParam int offset optional Number of rows to skip (default: 0)
     * 
     * @response 200 {
     *   "status": "success",
     *   "table_name": "users",
     *   "columns": [
     *     {
     *       "column_name": "id",
     *       "data_type": "integer",
     *       "is_nullable": "NO",
     *       "column_default": "nextval('users_id_seq'::regclass)"
     *     }
     *   ],
     *   "rows": [
     *     {
     *       "id": 1,
     *       "username": "admin"
     *     }
     *   ],
     *   "total_rows": 10,
     *   "current_page": 1,
     *   "per_page": 100,
     *   "has_more": false
     * }
     * 
     * @response 400 {
     *   "status": "error",
     *   "message": "Table 'users' does not exist"
     * }
     */
    public function handleGetTableData(string $tableName): void
    {
        // Get query parameters for pagination
        $limit = isset($_GET['limit']) ? min((int) $_GET['limit'], 1000) : 100;
        $offset = isset($_GET['offset']) ? max((int) $_GET['offset'], 0) : 0;

        // Ensure limit is at least 1
        $limit = max($limit, 1);

        $result = $this->database->getTableData($tableName, $limit, $offset);

        if ($result['status'] === 'success') {
            http_response_code(200);
        } else {
            http_response_code(400);
        }

        echo json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Handle the get table schema endpoint
     * 
     * Retrieves detailed schema information for a specific table.
     * 
     * @param string $tableName The name of the table to get schema for
     * 
     * @return void Outputs JSON response with table schema
     * 
     * @api GET /api/tables/{tableName}/schema
     * 
     * @response 200 {
     *   "status": "success",
     *   "table_name": "users",
     *   "columns": [
     *     {
     *       "column_name": "id",
     *       "data_type": "integer",
     *       "is_nullable": "NO",
     *       "column_default": "nextval('users_id_seq'::regclass)",
     *       "is_primary_key": true,
     *       "ordinal_position": 1
     *     }
     *   ],
     *   "row_count": 10
     * }
     * 
     * @response 400 {
     *   "status": "error",
     *   "message": "Table 'users' does not exist"
     * }
     */
    public function handleGetTableSchema(string $tableName): void
    {
        $result = $this->database->getTableSchema($tableName);

        if ($result['status'] === 'success') {
            http_response_code(200);
        } else {
            http_response_code(400);
        }

        echo json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Handle the add column endpoint
     * 
     * Adds a new column to an existing table.
     * 
     * @param string $tableName The name of the table to modify
     * 
     * @return void Outputs JSON response with operation result
     * 
     * @api POST /api/tables/{tableName}/columns
     * 
     * @bodyParam string columnName required The name of the new column
     * @bodyParam string columnType required The data type of the new column
     * @bodyParam boolean isNullable optional Whether the column allows NULL values (default: true)
     * @bodyParam string defaultValue optional Default value for the column
     * 
     * @request {
     *   "columnName": "email",
     *   "columnType": "VARCHAR(255)",
     *   "isNullable": false,
     *   "defaultValue": null
     * }
     * 
     * @response 201 {
     *   "status": "success",
     *   "message": "Column 'email' added to table 'users' successfully",
     *   "sql": "ALTER TABLE..."
     * }
     * 
     * @response 400 {
     *   "status": "error",
     *   "message": "Column 'email' already exists in table 'users'"
     * }
     */
    public function handleAddColumn(string $tableName): void
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['columnName']) || !isset($input['columnType'])) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing required fields: columnName and columnType'
            ]);
            return;
        }

        $columnName = $input['columnName'];
        $columnType = $input['columnType'];
        $isNullable = $input['isNullable'] ?? true;
        $defaultValue = $input['defaultValue'] ?? null;

        $result = $this->database->addColumn($tableName, $columnName, $columnType, $isNullable, $defaultValue);

        if ($result['status'] === 'success') {
            http_response_code(201);
        } else {
            http_response_code(400);
        }

        echo json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Handle the drop column endpoint
     * 
     * Removes a column from an existing table.
     * 
     * @param string $tableName The name of the table to modify
     * @param string $columnName The name of the column to drop
     * 
     * @return void Outputs JSON response with operation result
     * 
     * @api DELETE /api/tables/{tableName}/columns/{columnName}
     * 
     * @urlParam string tableName required The name of the table
     * @urlParam string columnName required The name of the column to drop
     * 
     * @response 200 {
     *   "status": "success",
     *   "message": "Column 'email' dropped from table 'users' successfully",
     *   "sql": "ALTER TABLE..."
     * }
     * 
     * @response 400 {
     *   "status": "error",
     *   "message": "Column 'email' does not exist in table 'users'"
     * }
     */
    public function handleDropColumn(string $tableName, string $columnName): void
    {
        $result = $this->database->dropColumn($tableName, $columnName);

        if ($result['status'] === 'success') {
            http_response_code(200);
        } else {
            http_response_code(400);
        }

        echo json_encode($result, JSON_PRETTY_PRINT);
    }
}
