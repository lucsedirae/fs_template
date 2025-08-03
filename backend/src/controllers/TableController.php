<?php
/**
 * Table Controller Class
 * 
 * Handles HTTP requests for table management operations.
 * Uses TableService for business logic and focuses on request/response handling.
 * 
 * @package    Backend\Controllers
 * @author     Your Team
 * @version    1.0.0
 * @since      PHP 7.4
 */

namespace Backend\Controllers;

use Backend\Services\TableService;
use Backend\Exceptions\BaseException;

class TableController extends BaseController
{
    /**
     * Table service instance
     * 
     * @var TableService
     */
    private $tableService;

    /**
     * TableController constructor
     * 
     * @param TableService|null $tableService Table service instance
     */
    public function __construct(?TableService $tableService = null)
    {
        parent::__construct();
        $this->tableService = $tableService ?? new TableService();
    }

    /**
     * Get list of all tables
     * 
     * @api GET /api/tables
     * 
     * @return void
     */
    public function index(): void
    {
        $this->executeAction(function () {
            $this->validateMethod(['GET']);

            $includeSystem = $this->getQueryParam('include_system', false);
            $tables = $this->tableService->getAllTables((bool) $includeSystem);

            $this->success([
                'tables' => $tables,
                'count' => count($tables)
            ], 'Tables retrieved successfully');
        });
    }

    /**
     * Create a new table
     * 
     * @api POST /api/tables
     * 
     * @bodyParam string tableName required The name of the table to create
     * @bodyParam array columns required Array of column definitions
     * 
     * @return void
     */
    public function create(): void
    {
        $this->executeAction(function () {
            $this->validateMethod(['POST']);
            $this->validateRequired($this->requestData, ['tableName', 'columns'], 'request body');

            $tableName = $this->requestData['tableName'];
            $columns = $this->requestData['columns'];

            $result = $this->tableService->createTable($tableName, $columns);

            $this->created($result, "Table '{$tableName}' created successfully");
        });
    }

    /**
     * Delete a table
     * 
     * @api DELETE /api/tables/{tableName}
     * 
     * @param string $tableName Table name to delete
     * 
     * @return void
     */
    public function delete(string $tableName): void
    {
        $this->executeAction(function () use ($tableName) {
            $this->validateMethod(['DELETE']);

            $cascade = $this->getQueryParam('cascade', false);
            $result = $this->tableService->dropTable($tableName, (bool) $cascade);

            $this->success($result, "Table '{$tableName}' deleted successfully");
        });
    }

    /**
     * Get table data with pagination
     * 
     * @api GET /api/tables/{tableName}/data
     * 
     * @param string $tableName Table name
     * 
     * @return void
     */
    public function getData(string $tableName): void
    {
        $this->executeAction(function () use ($tableName) {
            $this->validateMethod(['GET']);

            $pagination = $this->getPaginationParams(50, 1000);
            $result = $this->tableService->getTableData(
                $tableName,
                $pagination['limit'],
                $pagination['offset']
            );

            // Use paginated response format
            $this->paginated(
                $result['rows'],
                $result['total_rows'],
                $pagination['page'],
                $pagination['limit'],
                'Table data retrieved successfully'
            );
        });
    }

    /**
     * Get table schema information
     * 
     * @api GET /api/tables/{tableName}/schema
     * 
     * @param string $tableName Table name
     * 
     * @return void
     */
    public function getSchema(string $tableName): void
    {
        $this->executeAction(function () use ($tableName) {
            $this->validateMethod(['GET']);

            $schema = $this->tableService->getTableSchema($tableName);

            $this->success($schema, 'Table schema retrieved successfully');
        });
    }

    /**
     * Add a column to a table
     * 
     * @api POST /api/tables/{tableName}/columns
     * 
     * @param string $tableName Table name
     * 
     * @bodyParam string columnName required The name of the new column
     * @bodyParam string columnType required The data type of the new column
     * @bodyParam boolean isNullable optional Whether the column allows NULL values
     * @bodyParam string defaultValue optional Default value for the column
     * 
     * @return void
     */
    public function addColumn(string $tableName): void
    {
        $this->executeAction(function () use ($tableName) {
            $this->validateMethod(['POST']);
            $this->validateRequired($this->requestData, ['columnName', 'columnType'], 'request body');

            $columnName = $this->requestData['columnName'];
            $columnType = $this->requestData['columnType'];
            $isNullable = $this->requestData['isNullable'] ?? true;
            $defaultValue = $this->requestData['defaultValue'] ?? null;

            $result = $this->tableService->addColumn(
                $tableName,
                $columnName,
                $columnType,
                (bool) $isNullable,
                $defaultValue
            );

            $this->created($result, "Column '{$columnName}' added to table '{$tableName}' successfully");
        });
    }

    /**
     * Remove a column from a table
     * 
     * @api DELETE /api/tables/{tableName}/columns/{columnName}
     * 
     * @param string $tableName Table name
     * @param string $columnName Column name to remove
     * 
     * @return void
     */
    public function removeColumn(string $tableName, string $columnName): void
    {
        $this->executeAction(function () use ($tableName, $columnName) {
            $this->validateMethod(['DELETE']);

            $cascade = $this->getQueryParam('cascade', false);
            $result = $this->tableService->dropColumn($tableName, $columnName, (bool) $cascade);

            $this->success($result, "Column '{$columnName}' removed from table '{$tableName}' successfully");
        });
    }

    /**
     * Check if table exists
     * 
     * @api HEAD /api/tables/{tableName}
     * @api GET /api/tables/{tableName}/exists
     * 
     * @param string $tableName Table name to check
     * 
     * @return void
     */
    public function exists(string $tableName): void
    {
        $this->executeAction(function () use ($tableName) {
            $this->validateMethod(['HEAD', 'GET']);

            $exists = $this->tableService->tableExists($tableName);

            if ($this->method === 'HEAD') {
                // HEAD request - return status code only
                http_response_code($exists ? 200 : 404);
                exit();
            }

            // GET request - return JSON response
            $this->success([
                'table_name' => $tableName,
                'exists' => $exists
            ], $exists ? 'Table exists' : 'Table does not exist');
        });
    }

    /**
     * Get table statistics
     * 
     * @api GET /api/tables/{tableName}/stats
     * 
     * @param string $tableName Table name
     * 
     * @return void
     */
    public function getStats(string $tableName): void
    {
        $this->executeAction(function () use ($tableName) {
            $this->validateMethod(['GET']);

            // Get basic schema info
            $schema = $this->tableService->getTableSchema($tableName);

            // Calculate additional statistics
            $stats = [
                'table_name' => $tableName,
                'row_count' => $schema['row_count'],
                'column_count' => count($schema['columns']),
                'primary_keys' => array_filter($schema['columns'], function ($col) {
                    return $col['is_primary_key'] ?? false;
                }),
                'nullable_columns' => array_filter($schema['columns'], function ($col) {
                    return $col['is_nullable'] === 'YES';
                }),
                'data_types' => array_count_values(array_column($schema['columns'], 'data_type'))
            ];

            $this->success($stats, 'Table statistics retrieved successfully');
        });
    }

    /**
     * Truncate table (remove all data)
     * 
     * @api POST /api/tables/{tableName}/truncate
     * 
     * @param string $tableName Table name
     * 
     * @return void
     */
    public function truncate(string $tableName): void
    {
        $this->executeAction(function () use ($tableName) {
            $this->validateMethod(['POST']);

            // Validate table exists
            if (!$this->tableService->tableExists($tableName)) {
                $this->notFound("Table '{$tableName}' does not exist");
                return;
            }

            try {
                // Execute TRUNCATE
                $sanitizedTableName = '"' . str_replace('"', '""', $tableName) . '"';
                $sql = "TRUNCATE TABLE {$sanitizedTableName}";

                $this->tableService->getConnection()->execute($sql);

                $this->success([
                    'table_name' => $tableName,
                    'sql' => $sql
                ], "Table '{$tableName}' truncated successfully");

            } catch (\Throwable $e) {
                $this->error('Failed to truncate table: ' . $e->getMessage(), 500);
            }
        });
    }

    /**
     * Get table health status
     * 
     * @api GET /api/tables/health
     * 
     * @return void
     */
    public function health(): void
    {
        $this->executeAction(function () {
            $this->validateMethod(['GET']);

            try {
                $tables = $this->tableService->getAllTables();
                $totalTables = count($tables);

                // Basic health check
                $checks = [
                    'database_connection' => [
                        'healthy' => true,
                        'message' => 'Database connection is working'
                    ],
                    'table_access' => [
                        'healthy' => $totalTables >= 0,
                        'message' => "Can access {$totalTables} tables"
                    ]
                ];

                $this->success([
                    'total_tables' => $totalTables,
                    'checks' => $checks,
                    'timestamp' => date('Y-m-d H:i:s')
                ], 'Table service health check completed');

            } catch (\Throwable $e) {
                $this->error('Health check failed: ' . $e->getMessage(), 503);
            }
        });
    }
}
