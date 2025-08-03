import React, { useState } from 'react';
import './TableCreateForm.css';

/**
 * TableCreateForm Component
 * 
 * A form component for creating new database tables with customizable columns.
 * Handles table name input, column management, and form validation.
 * 
 * @param {Object} props
 * @param {boolean} props.loading - Whether any operation is in progress
 * @param {Function} props.onCreateTable - Callback function when form is submitted
 * @param {Function} props.onCancel - Callback function when form is cancelled
 * @param {string} props.className - Additional CSS classes for the form container
 */
const TableCreateForm = ({ 
  loading = false, 
  onCreateTable, 
  onCancel, 
  className = "" 
}) => {
  // Form state
  const [tableName, setTableName] = useState('');
  const [columns, setColumns] = useState([
    { name: 'id', type: 'SERIAL', isPrimary: true, nullable: false }
  ]);

  // Available column types
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

  /**
   * Add a new column to the form
   */
  const addColumn = () => {
    setColumns([...columns, { 
      name: '', 
      type: 'VARCHAR(255)', 
      isPrimary: false, 
      nullable: true 
    }]);
  };

  /**
   * Remove a column from the form
   * @param {number} index - Index of the column to remove
   */
  const removeColumn = (index) => {
    if (columns.length > 1) {
      setColumns(columns.filter((_, i) => i !== index));
    }
  };

  /**
   * Update column properties
   * @param {number} index - Index of the column to update
   * @param {string} field - Field name to update
   * @param {*} value - New value for the field
   */
  const updateColumn = (index, field, value) => {
    const updatedColumns = [...columns];
    updatedColumns[index][field] = value;
    setColumns(updatedColumns);
  };

  /**
   * Reset form to initial state
   */
  const resetForm = () => {
    setTableName('');
    setColumns([{ name: 'id', type: 'SERIAL', isPrimary: true, nullable: false }]);
  };

  /**
   * Handle form submission
   * @param {Event} e - Form submit event
   */
  const handleSubmit = (e) => {
    e.preventDefault();

    if (!tableName.trim()) {
      return;
    }

    if (columns.length === 0) {
      return;
    }

    // Validate columns
    for (let i = 0; i < columns.length; i++) {
      if (!columns[i].name.trim()) {
        return;
      }
    }

    // Call the parent callback with form data
    if (onCreateTable) {
      onCreateTable({
        tableName: tableName.trim(),
        columns: columns
      });
    }

    // Reset form after successful submission
    resetForm();
  };

  /**
   * Handle cancel action
   */
  const handleCancel = () => {
    resetForm();
    if (onCancel) {
      onCancel();
    }
  };

  return (
    <div className={`row mb-5 ${className}`}>
      <div className="col">
        <div className="card shadow">
          <div className="card-header bg-primary text-white">
            <h5 className="card-title mb-0">
              <span className="me-2">🆕</span>
              Create New Table
            </h5>
          </div>
          <div className="card-body">
            <form onSubmit={handleSubmit}>
              {/* Table Name Input */}
              <div className="mb-4">
                <label htmlFor="tableName" className="form-label fw-bold">
                  Table Name
                </label>
                <input
                  type="text"
                  className="form-control"
                  id="tableName"
                  value={tableName}
                  onChange={(e) => setTableName(e.target.value)}
                  placeholder="Enter table name"
                  disabled={loading}
                  required
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
                            required
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
                                  title="Remove column"
                                >
                                  <span className="me-1">🗑️</span>
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
              <div className="d-flex justify-content-center gap-3">
                <button 
                  type="submit" 
                  disabled={loading || !tableName.trim()} 
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

                <button
                  type="button"
                  onClick={handleCancel}
                  className="btn btn-outline-secondary btn-lg"
                  disabled={loading}
                >
                  <span className="me-2">❌</span>
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  );
};

export default TableCreateForm;
