import React, { useState, useEffect, useCallback } from 'react';
import './TableEditModal.css';

/**
 * TableEditModal Component
 * 
 * A modal component for editing table structure (adding/removing columns)
 * 
 * @param {Object} props
 * @param {boolean} props.show - Whether to show the modal
 * @param {Function} props.onHide - Callback when modal is closed
 * @param {string} props.tableName - Name of the table to edit
 * @param {string} props.apiBaseUrl - Base URL for API calls
 * @param {Function} props.onTableUpdated - Callback when table is successfully updated
 */
const TableEditModal = ({ show, onHide, tableName, apiBaseUrl, onTableUpdated }) => {
  const [tableSchema, setTableSchema] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);
  
  // New column form state
  const [showAddColumnForm, setShowAddColumnForm] = useState(false);
  const [newColumn, setNewColumn] = useState({
    columnName: '',
    columnType: 'VARCHAR(255)',
    isNullable: true,
    defaultValue: ''
  });

  // Available column types
  const columnTypes = [
    'SERIAL',
    'INTEGER',
    'BIGINT',
    'SMALLINT',
    'VARCHAR(50)',
    'VARCHAR(100)',
    'VARCHAR(255)',
    'TEXT',
    'BOOLEAN',
    'DATE',
    'TIMESTAMP',
    'TIMESTAMP WITH TIME ZONE',
    'DECIMAL(10,2)',
    'DECIMAL(15,2)',
    'NUMERIC',
    'REAL',
    'DOUBLE PRECISION'
  ];

  // Clear messages after 5 seconds
  useEffect(() => {
    if (error || success) {
      const timer = setTimeout(() => {
        setError(null);
        setSuccess(null);
      }, 5000);
      return () => clearTimeout(timer);
    }
  }, [error, success]);

  // Fetch table schema
  const fetchTableSchema = useCallback(async () => {
    if (!tableName) return;

    setLoading(true);
    setError(null);

    try {
      const response = await fetch(`${apiBaseUrl}/api/tables/${tableName}/schema`);

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();

      if (data.status === 'error') {
        throw new Error(data.message);
      }

      setTableSchema(data);
    } catch (err) {
      setError(`Failed to fetch table schema: ${err.message}`);
      console.error('Fetch table schema error:', err);
    } finally {
      setLoading(false);
    }
  }, [apiBaseUrl, tableName]);

  // Load schema when modal opens
  useEffect(() => {
    if (show && tableName) {
      setError(null);
      setSuccess(null);
      setShowAddColumnForm(false);
      fetchTableSchema();
    } else {
      setTableSchema(null);
      setError(null);
      setSuccess(null);
    }
  }, [show, tableName, fetchTableSchema]);

  // Add new column
  const handleAddColumn = async (e) => {
    e.preventDefault();

    if (!newColumn.columnName.trim()) {
      setError('Column name is required');
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const requestBody = {
        columnName: newColumn.columnName.trim(),
        columnType: newColumn.columnType,
        isNullable: newColumn.isNullable,
        defaultValue: newColumn.defaultValue.trim() || null
      };

      const response = await fetch(`${apiBaseUrl}/api/tables/${tableName}/columns`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestBody)
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || `HTTP error! status: ${response.status}`);
      }

      setSuccess(`Column "${newColumn.columnName}" added successfully!`);
      setNewColumn({
        columnName: '',
        columnType: 'VARCHAR(255)',
        isNullable: true,
        defaultValue: ''
      });
      setShowAddColumnForm(false);
      
      // Refresh schema and notify parent
      fetchTableSchema();
      if (onTableUpdated) {
        onTableUpdated();
      }
    } catch (err) {
      setError(`Failed to add column: ${err.message}`);
      console.error('Add column error:', err);
    } finally {
      setLoading(false);
    }
  };

  // Drop column
  const handleDropColumn = async (columnName) => {
    if (!window.confirm(`Are you sure you want to delete column "${columnName}"? This will permanently remove all data in this column.`)) {
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const response = await fetch(`${apiBaseUrl}/api/tables/${tableName}/columns/${columnName}`, {
        method: 'DELETE'
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || `HTTP error! status: ${response.status}`);
      }

      setSuccess(`Column "${columnName}" deleted successfully!`);
      
      // Refresh schema and notify parent
      fetchTableSchema();
      if (onTableUpdated) {
        onTableUpdated();
      }
    } catch (err) {
      setError(`Failed to delete column: ${err.message}`);
      console.error('Delete column error:', err);
    } finally {
      setLoading(false);
    }
  };

  // Update new column field
  const updateNewColumn = (field, value) => {
    setNewColumn(prev => ({
      ...prev,
      [field]: value
    }));
  };

  // Render table schema content
  const renderSchemaContent = () => {
    if (loading && !tableSchema) {
      return (
        <div className="text-center py-5">
          <div className="spinner-border text-primary" role="status">
            <span className="visually-hidden">Loading...</span>
          </div>
          <p className="mt-3 text-muted">Loading table schema...</p>
        </div>
      );
    }

    if (!tableSchema || !tableSchema.columns) {
      return (
        <div className="text-center py-5 text-muted">
          <span className="fs-1 d-block mb-3">⚠️</span>
          <p>No schema information available</p>
        </div>
      );
    }

    return (
      <>
        {/* Table Info */}
        <div className="row mb-4">
          <div className="col">
            <div className="card bg-light">
              <div className="card-body">
                <h6 className="card-title mb-2">Table Information</h6>
                <div className="row">
                  <div className="col-md-4">
                    <strong>Table Name:</strong> {tableSchema.table_name}
                  </div>
                  <div className="col-md-4">
                    <strong>Columns:</strong> {tableSchema.columns.length}
                  </div>
                  <div className="col-md-4">
                    <strong>Rows:</strong> {tableSchema.row_count}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Columns List */}
        <div className="row mb-4">
          <div className="col">
            <h6 className="mb-3">Current Columns</h6>
            <div className="table-responsive">
              <table className="table table-sm table-striped">
                <thead className="table-dark">
                  <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Nullable</th>
                    <th>Default</th>
                    <th>Primary Key</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {tableSchema.columns.map((column) => (
                    <tr key={column.column_name}>
                      <td>
                        <code>{column.column_name}</code>
                      </td>
                      <td>
                        <span className="badge bg-secondary">
                          {column.data_type}
                          {column.character_maximum_length && `(${column.character_maximum_length})`}
                        </span>
                      </td>
                      <td>
                        {column.is_nullable === 'YES' ? (
                          <span className="badge bg-success">Yes</span>
                        ) : (
                          <span className="badge bg-warning">No</span>
                        )}
                      </td>
                      <td>
                        {column.column_default ? (
                          <code className="small">{column.column_default}</code>
                        ) : (
                          <span className="text-muted">None</span>
                        )}
                      </td>
                      <td>
                        {column.is_primary_key ? (
                          <span className="badge bg-primary">Yes</span>
                        ) : (
                          <span className="text-muted">No</span>
                        )}
                      </td>
                      <td>
                        {/* Only allow dropping non-primary key columns */}
                        {!column.is_primary_key && tableSchema.columns.length > 1 && (
                          <button
                            type="button"
                            onClick={() => handleDropColumn(column.column_name)}
                            className="btn btn-outline-danger btn-sm"
                            disabled={loading}
                            title="Delete column"
                          >
                            🗑️
                          </button>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>

        {/* Add Column Section */}
        <div className="row">
          <div className="col">
            {!showAddColumnForm ? (
              <div className="text-center">
                <button
                  type="button"
                  onClick={() => setShowAddColumnForm(true)}
                  className="btn btn-success"
                  disabled={loading}
                >
                  <span className="me-2">➕</span>
                  Add New Column
                </button>
              </div>
            ) : (
              <div className="card border-success">
                <div className="card-header bg-success text-white">
                  <h6 className="card-title mb-0">Add New Column</h6>
                </div>
                <div className="card-body">
                  <form onSubmit={handleAddColumn}>
                    <div className="row g-3">
                      {/* Column Name */}
                      <div className="col-md-6">
                        <label className="form-label">Column Name</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter column name"
                          value={newColumn.columnName}
                          onChange={(e) => updateNewColumn('columnName', e.target.value)}
                          disabled={loading}
                          required
                        />
                      </div>

                      {/* Column Type */}
                      <div className="col-md-6">
                        <label className="form-label">Data Type</label>
                        <select
                          className="form-select"
                          value={newColumn.columnType}
                          onChange={(e) => updateNewColumn('columnType', e.target.value)}
                          disabled={loading}
                        >
                          {columnTypes.map(type => (
                            <option key={type} value={type}>{type}</option>
                          ))}
                        </select>
                      </div>

                      {/* Default Value */}
                      <div className="col-md-6">
                        <label className="form-label">Default Value (optional)</label>
                        <input
                          type="text"
                          className="form-control"
                          placeholder="Enter default value"
                          value={newColumn.defaultValue}
                          onChange={(e) => updateNewColumn('defaultValue', e.target.value)}
                          disabled={loading}
                        />
                      </div>

                      {/* Nullable Checkbox */}
                      <div className="col-md-6">
                        <label className="form-label">Options</label>
                        <div className="form-check">
                          <input
                            type="checkbox"
                            className="form-check-input"
                            id="allowNull"
                            checked={newColumn.isNullable}
                            onChange={(e) => updateNewColumn('isNullable', e.target.checked)}
                            disabled={loading}
                          />
                          <label className="form-check-label" htmlFor="allowNull">
                            Allow NULL values
                          </label>
                        </div>
                      </div>
                    </div>

                    {/* Form Actions */}
                    <div className="d-flex gap-2 mt-3">
                      <button 
                        type="submit" 
                        disabled={loading} 
                        className="btn btn-success"
                      >
                        {loading ? (
                          <>
                            <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                            Adding...
                          </>
                        ) : (
                          <>
                            <span className="me-2">✅</span>
                            Add Column
                          </>
                        )}
                      </button>
                      <button
                        type="button"
                        onClick={() => setShowAddColumnForm(false)}
                        className="btn btn-outline-secondary"
                        disabled={loading}
                      >
                        Cancel
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            )}
          </div>
        </div>
      </>
    );
  };

  if (!show) return null;

  return (
    <>
      {/* Modal backdrop */}
      <div 
        className="modal-backdrop fade show" 
        onClick={onHide}
        style={{ zIndex: 1040 }}
      ></div>

      {/* Modal */}
      <div 
        className="modal fade show d-block" 
        tabIndex="-1" 
        style={{ zIndex: 1050 }}
        aria-labelledby="tableEditModalLabel"
        aria-hidden="false"
      >
        <div className="modal-dialog modal-xl">
          <div className="modal-content">
            {/* Modal Header */}
            <div className="modal-header bg-warning text-dark">
              <h5 className="modal-title" id="tableEditModalLabel">
                <span className="me-2">✏️</span>
                Edit Table: {tableName}
              </h5>
              <button
                type="button"
                className="btn-close"
                onClick={onHide}
                aria-label="Close"
              ></button>
            </div>

            {/* Modal Body */}
            <div className="modal-body">
              {/* Alert Messages */}
              {error && (
                <div className="alert alert-danger alert-dismissible fade show" role="alert">
                  <strong>Error:</strong> {error}
                  <button type="button" className="btn-close" onClick={() => setError(null)}></button>
                </div>
              )}

              {success && (
                <div className="alert alert-success alert-dismissible fade show" role="alert">
                  <strong>Success:</strong> {success}
                  <button type="button" className="btn-close" onClick={() => setSuccess(null)}></button>
                </div>
              )}

              {renderSchemaContent()}
            </div>

            {/* Modal Footer */}
            <div className="modal-footer">
              <div className="d-flex justify-content-between w-100 align-items-center">
                <div className="text-muted small">
                  {tableSchema && (
                    <>
                      <strong>{tableSchema.table_name}</strong> - 
                      {tableSchema.columns?.length || 0} columns, 
                      {tableSchema.row_count || 0} rows
                    </>
                  )}
                </div>
                <div>
                  <button 
                    type="button" 
                    className="btn btn-secondary" 
                    onClick={onHide}
                  >
                    Close
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
};

export default TableEditModal;
