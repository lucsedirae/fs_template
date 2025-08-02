import React, { useState, useEffect, useCallback } from 'react';
import './DatabaseManager.css';

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
    <div className="database-manager">
      <div className="database-manager-header">
        <h2>🗄️ Database Table Manager</h2>
        <p>Create and manage your database tables</p>
      </div>

      {error && (
        <div className="message error-message">
          <strong>Error:</strong> {error}
        </div>
      )}

      {success && (
        <div className="message success-message">
          <strong>Success:</strong> {success}
        </div>
      )}

      <div className="controls">
        <button 
          onClick={() => setShowCreateForm(!showCreateForm)}
          className="create-table-button"
          disabled={loading}
        >
          {showCreateForm ? 'Cancel' : 'Create New Table'}
        </button>
        
        <button 
          onClick={fetchTables}
          className="refresh-button"
          disabled={loading}
        >
          {loading ? 'Loading...' : 'Refresh Tables'}
        </button>
      </div>

      {showCreateForm && (
        <div className="create-table-form">
          <h3>Create New Table</h3>
          <form onSubmit={createTable}>
            <div className="form-group">
              <label htmlFor="tableName">Table Name:</label>
              <input
                type="text"
                id="tableName"
                value={newTableName}
                onChange={(e) => setNewTableName(e.target.value)}
                placeholder="Enter table name"
                disabled={loading}
              />
            </div>

            <div className="columns-section">
              <h4>Columns:</h4>
              {columns.map((column, index) => (
                <div key={index} className="column-row">
                  <input
                    type="text"
                    placeholder="Column name"
                    value={column.name}
                    onChange={(e) => updateColumn(index, 'name', e.target.value)}
                    disabled={loading}
                  />
                  
                  <select
                    value={column.type}
                    onChange={(e) => updateColumn(index, 'type', e.target.value)}
                    disabled={loading}
                  >
                    {columnTypes.map(type => (
                      <option key={type} value={type}>{type}</option>
                    ))}
                  </select>

                  <label className="checkbox-label">
                    <input
                      type="checkbox"
                      checked={column.isPrimary}
                      onChange={(e) => updateColumn(index, 'isPrimary', e.target.checked)}
                      disabled={loading}
                    />
                    Primary Key
                  </label>

                  <label className="checkbox-label">
                    <input
                      type="checkbox"
                      checked={!column.nullable}
                      onChange={(e) => updateColumn(index, 'nullable', !e.target.checked)}
                      disabled={loading}
                    />
                    Not Null
                  </label>

                  {columns.length > 1 && (
                    <button
                      type="button"
                      onClick={() => removeColumn(index)}
                      className="remove-column-button"
                      disabled={loading}
                    >
                      Remove
                    </button>
                  )}
                </div>
              ))}
              
              <button
                type="button"
                onClick={addColumn}
                className="add-column-button"
                disabled={loading}
              >
                Add Column
              </button>
            </div>

            <div className="form-actions">
              <button type="submit" disabled={loading} className="submit-button">
                {loading ? 'Creating...' : 'Create Table'}
              </button>
            </div>
          </form>
        </div>
      )}

      <div className="tables-list">
        <h3>Existing Tables</h3>
        {tables.length === 0 ? (
          <p className="no-tables">No tables found. Create your first table!</p>
        ) : (
          <div className="tables-grid">
            {tables.map((table) => (
              <div key={table.table_name} className="table-card">
                <h4>{table.table_name}</h4>
                <p>Type: {table.table_type}</p>
                <div className="table-actions">
                  <button
                    onClick={() => deleteTable(table.table_name)}
                    className="delete-button"
                    disabled={loading}
                  >
                    Delete
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default DatabaseManager;