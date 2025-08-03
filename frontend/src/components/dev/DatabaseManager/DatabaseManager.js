import React, { useState, useEffect, useCallback } from 'react';
import TableCard from './TableCard/TableCard';
import TableViewModal from './TableViewModal/TableViewModal';
import TableEditModal from './TableEditModal/TableEditModal';

const DatabaseManager = () => {
  const [tables, setTables] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);
  const [showCreateForm, setShowCreateForm] = useState(false);
  const [newTableName, setNewTableName] = useState('');
  const [columns, setColumns] = useState([
    { name: 'id', type: 'SERIAL', isPrimary: true, nullable: false }
  ]);

  // Modal state
  const [showViewModal, setShowViewModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [selectedTable, setSelectedTable] = useState(null);

  const API_BASE_URL = 'http://localhost:8080';

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

  // Fetch list of tables
  const fetchTables = useCallback(async () => {
    setLoading(true);
    setError(null);
    
    try {
      const response = await fetch(`${API_BASE_URL}/api/tables`);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const data = await response.json();
      setTables(data.tables || []);
    } catch (err) {
      setError(`Failed to fetch tables: ${err.message}`);
      console.error('API Error:', err);
    } finally {
      setLoading(false);
    }
  }, [API_BASE_URL]);

  // Load tables on component mount
  useEffect(() => {
    fetchTables();
  }, [fetchTables]);

  // Add a new column to the form
  const addColumn = () => {
    setColumns([...columns, { name: '', type: 'VARCHAR(255)', isPrimary: false, nullable: true }]);
  };

  // Remove a column from the form
  const removeColumn = (index) => {
    if (columns.length > 1) {
      setColumns(columns.filter((_, i) => i !== index));
    }
  };

  // Update column properties
  const updateColumn = (index, field, value) => {
    const updatedColumns = [...columns];
    updatedColumns[index][field] = value;
    setColumns(updatedColumns);
  };

  // Create a new table
  const createTable = async (e) => {
    e.preventDefault();
    
    if (!newTableName.trim()) {
      setError('Table name is required');
      return;
    }

    if (columns.length === 0) {
      setError('At least one column is required');
      return;
    }

    // Validate columns
    for (let i = 0; i < columns.length; i++) {
      if (!columns[i].name.trim()) {
        setError(`Column ${i + 1} name is required`);
        return;
      }
    }

    setLoading(true);
    setError(null);

    try {
      const response = await fetch(`${API_BASE_URL}/api/tables`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          tableName: newTableName.trim(),
          columns: columns
        })
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || `HTTP error! status: ${response.status}`);
      }

      setSuccess(`Table "${newTableName}" created successfully!`);
      setNewTableName('');
      setColumns([{ name: 'id', type: 'SERIAL', isPrimary: true, nullable: false }]);
      setShowCreateForm(false);
      fetchTables(); // Refresh table list
    } catch (err) {
      setError(`Failed to create table: ${err.message}`);
      console.error('Create table error:', err);
    } finally {
      setLoading(false);
    }
  };

  // Delete a table
  const deleteTable = async (tableName) => {
    if (!window.confirm(`Are you sure you want to delete table "${tableName}"? This action cannot be undone.`)) {
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const response = await fetch(`${API_BASE_URL}/api/tables/${tableName}`, {
        method: 'DELETE'
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || `HTTP error! status: ${response.status}`);
      }

      setSuccess(`Table "${tableName}" deleted successfully!`);
      fetchTables(); // Refresh table list
    } catch (err) {
      setError(`Failed to delete table: ${err.message}`);
      console.error('Delete table error:', err);
    } finally {
      setLoading(false);
    }
  };

  // Handle viewing table details - UPDATED IMPLEMENTATION
  const viewTable = (tableName) => {
    setSelectedTable(tableName);
    setShowViewModal(true);
  };

  // Handle editing table structure - NEW IMPLEMENTATION
  const editTable = (tableName) => {
    setSelectedTable(tableName);
    setShowEditModal(true);
  };

  // Handle closing the view modal
  const handleCloseViewModal = () => {
    setShowViewModal(false);
    setSelectedTable(null);
  };

  // Handle closing the edit modal
  const handleCloseEditModal = () => {
    setShowEditModal(false);
    setSelectedTable(null);
  };

  // Handle table updated from edit modal
  const handleTableUpdated = () => {
    // Refresh the tables list when a table structure is modified
    fetchTables();
  };

  const columnTypes = [
    'SERIAL',
    'INTEGER',
    'BIGINT',
    'VARCHAR(255)',
    'VARCHAR(100)',
    'VARCHAR(50)',
    'TEXT',
    'BOOLEAN',
    'DATE',
    'TIMESTAMP',
    'DECIMAL(10,2)',
    'DECIMAL(15,2)'
  ];

  return (
    <div className="container-fluid">
      {/* Header */}
      <div className="row mb-4">
        <div className="col">
          <div className="text-center py-4 border-bottom">
            <h2 className="mb-3">
              <span className="me-2">🗄️</span>
              Database Table Manager
            </h2>
            <p className="text-muted mb-0">Create and manage your database tables</p>
          </div>
        </div>
      </div>

      {/* Alert Messages */}
      {error && (
        <div className="row mb-4">
          <div className="col">
            <div className="alert alert-danger alert-dismissible fade show" role="alert">
              <strong>Error:</strong> {error}
              <button type="button" className="btn-close" onClick={() => setError(null)}></button>
            </div>
          </div>
        </div>
      )}

      {success && (
        <div className="row mb-4">
          <div className="col">
            <div className="alert alert-success alert-dismissible fade show" role="alert">
              <strong>Success:</strong> {success}
              <button type="button" className="btn-close" onClick={() => setSuccess(null)}></button>
            </div>
          </div>
        </div>
      )}

      {/* Control Buttons */}
      <div className="row mb-4">
        <div className="col">
          <div className="d-flex flex-wrap justify-content-center gap-3">
            <button 
              onClick={() => setShowCreateForm(!showCreateForm)}
              className="btn btn-primary"
              disabled={loading}
            >
              {showCreateForm ? (
                <>
                  <span className="me-2">❌</span>
                  Cancel
                </>
              ) : (
                <>
                  <span className="me-2">➕</span>
                  Create New Table
                </>
              )}
            </button>
            
            <button 
              onClick={fetchTables}
              className="btn btn-outline-primary"
              disabled={loading}
            >
              {loading ? (
                <>
                  <span 
                    className="spinner-border spinner-border-sm me-2" 
                    role="status" 
                    aria-hidden="true"
                  ></span>
                  Loading...
                </>
              ) : (
                <>
                  <span className="me-2">🔄</span>
                  Refresh Tables
                </>
              )}
            </button>
          </div>
        </div>
      </div>

      {/* Create Table Form */}
      {showCreateForm && (
        <div className="row mb-5">
          <div className="col">
            <div className="card shadow">
              <div className="card-header bg-primary text-white">
                <h5 className="card-title mb-0">
                  <span className="me-2">🆕</span>
                  Create New Table
                </h5>
              </div>
              <div className="card-body">
                <form onSubmit={createTable}>
                  {/* Table Name Input */}
                  <div className="mb-4">
                    <label htmlFor="tableName" className="form-label fw-bold">
                      Table Name
                    </label>
                    <input
                      type="text"
                      className="form-control"
                      id="tableName"
                      value={newTableName}
                      onChange={(e) => setNewTableName(e.target.value)}
                      placeholder="Enter table name"
                      disabled={loading}
                    />
                  </div>

                  {/* Columns Section */}
                  <div className="mb-4">
                    <h6 className="fw-bold mb-3">Columns</h6>
                    
                    {columns.map((column, index) => (
                      <div key={index} className="card mb-3 border-light">
                        <div className="card-body">
                          <div className="row g-3 align-items-center">
                            {/* Column Name */}
                            <div className="col-md-3">
                              <label className="form-label small">Column Name</label>
                              <input
                                type="text"
                                className="form-control"
                                placeholder="Column name"
                                value={column.name}
                                onChange={(e) => updateColumn(index, 'name', e.target.value)}
                                disabled={loading}
                              />
                            </div>
                            
                            {/* Column Type */}
                            <div className="col-md-3">
                              <label className="form-label small">Data Type</label>
                              <select
                                className="form-select"
                                value={column.type}
                                onChange={(e) => updateColumn(index, 'type', e.target.value)}
                                disabled={loading}
                              >
                                {columnTypes.map(type => (
                                  <option key={type} value={type}>{type}</option>
                                ))}
                              </select>
                            </div>

                            {/* Checkboxes */}
                            <div className="col-md-4">
                              <label className="form-label small">Options</label>
                              <div className="d-flex gap-3">
                                <div className="form-check">
                                  <input
                                    type="checkbox"
                                    className="form-check-input"
                                    id={`primary-${index}`}
                                    checked={column.isPrimary}
                                    onChange={(e) => updateColumn(index, 'isPrimary', e.target.checked)}
                                    disabled={loading}
                                  />
                                  <label className="form-check-label small" htmlFor={`primary-${index}`}>
                                    Primary Key
                                  </label>
                                </div>

                                <div className="form-check">
                                  <input
                                    type="checkbox"
                                    className="form-check-input"
                                    id={`notnull-${index}`}
                                    checked={!column.nullable}
                                    onChange={(e) => updateColumn(index, 'nullable', !e.target.checked)}
                                    disabled={loading}
                                  />
                                  <label className="form-check-label small" htmlFor={`notnull-${index}`}>
                                    Not Null
                                  </label>
                                </div>
                              </div>
                            </div>

                            {/* Remove Button */}
                            <div className="col-md-2">
                              {columns.length > 1 && (
                                <>
                                  <label className="form-label small">&nbsp;</label>
                                  <div>
                                    <button
                                      type="button"
                                      onClick={() => removeColumn(index)}
                                      className="btn btn-outline-danger btn-sm"
                                      disabled={loading}
                                    >
                                      Remove
                                    </button>
                                  </div>
                                </>
                              )}
                            </div>
                          </div>
                        </div>
                      </div>
                    ))}
                    
                    {/* Add Column Button */}
                    <button
                      type="button"
                      onClick={addColumn}
                      className="btn btn-outline-success"
                      disabled={loading}
                    >
                      <span className="me-2">➕</span>
                      Add Column
                    </button>
                  </div>

                  {/* Form Actions */}
                  <div className="text-center">
                    <button 
                      type="submit" 
                      disabled={loading} 
                      className="btn btn-success btn-lg"
                    >
                      {loading ? (
                        <>
                          <span className="spinner-border spinner-border-sm me-2" role="status"></span>
                          Creating...
                        </>
                      ) : (
                        <>
                          <span className="me-2">✅</span>
                          Create Table
                        </>
                      )}
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Tables List */}
      <div className="row">
        <div className="col">
          <div className="card shadow">
            <div className="card-header bg-light d-flex justify-content-between align-items-center">
              <h5 className="card-title mb-0">
                <span className="me-2">📋</span>
                Existing Tables
              </h5>
              {tables.length > 0 && (
                <span className="badge bg-primary">
                  {tables.length} table{tables.length !== 1 ? 's' : ''}
                </span>
              )}
            </div>
            <div className="card-body">
              {tables.length === 0 ? (
                <div className="text-center py-5">
                  <div className="text-muted">
                    <span className="fs-1 d-block mb-3">📂</span>
                    <h4 className="mb-3">No tables found</h4>
                    <p className="mb-0">Create your first table to get started!</p>
                  </div>
                </div>
              ) : (
                <div className="row g-4">
                  {tables.map((table) => (
                    <TableCard
                      key={table.table_name}
                      table={table}
                      onDelete={deleteTable}
                      onView={viewTable}
                      onEdit={editTable}
                      loading={loading}
                    />
                  ))}
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Table View Modal */}
      <TableViewModal
        show={showViewModal}
        onHide={handleCloseViewModal}
        tableName={selectedTable}
        apiBaseUrl={API_BASE_URL}
      />

      {/* Table Edit Modal */}
      <TableEditModal
        show={showEditModal}
        onHide={handleCloseEditModal}
        tableName={selectedTable}
        apiBaseUrl={API_BASE_URL}
        onTableUpdated={handleTableUpdated}
      />
    </div>
  );
};

export default DatabaseManager;