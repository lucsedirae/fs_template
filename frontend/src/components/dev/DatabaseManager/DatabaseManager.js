import React, { useState, useEffect, useCallback } from 'react';
import TableCard from './TableCard/TableCard';
import TableViewModal from './TableViewModal/TableViewModal';
import TableEditModal from './TableEditModal/TableEditModal';
import TableCreateForm from './TableCreateForm/TableCreateForm';

const DatabaseManager = () => {
  const [tables, setTables] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);
  const [showCreateForm, setShowCreateForm] = useState(false);

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

  /**
   * Handle table creation from the TableCreateForm component
   * @param {Object} formData - Object containing tableName and columns
   */
  const handleCreateTable = async (formData) => {
    const { tableName, columns } = formData;

    setLoading(true);
    setError(null);

    try {
      const response = await fetch(`${API_BASE_URL}/api/tables`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          tableName: tableName,
          columns: columns
        })
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || `HTTP error! status: ${response.status}`);
      }

      setSuccess(`Table "${tableName}" created successfully!`);
      setShowCreateForm(false);
      fetchTables(); // Refresh table list
    } catch (err) {
      setError(`Failed to create table: ${err.message}`);
      console.error('Create table error:', err);
    } finally {
      setLoading(false);
    }
  };

  /**
   * Handle cancellation of table creation
   */
  const handleCreateTableCancel = () => {
    setShowCreateForm(false);
  };

  /**
   * Delete a table
   * @param {string} tableName - Name of the table to delete
   */
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

  /**
   * Handle viewing table details
   * @param {string} tableName - Name of the table to view
   */
  const viewTable = (tableName) => {
    setSelectedTable(tableName);
    setShowViewModal(true);
  };

  /**
   * Handle editing table structure
   * @param {string} tableName - Name of the table to edit
   */
  const editTable = (tableName) => {
    setSelectedTable(tableName);
    setShowEditModal(true);
  };

  /**
   * Handle closing the view modal
   */
  const handleCloseViewModal = () => {
    setShowViewModal(false);
    setSelectedTable(null);
  };

  /**
   * Handle closing the edit modal
   */
  const handleCloseEditModal = () => {
    setShowEditModal(false);
    setSelectedTable(null);
  };

  /**
   * Handle table updated from edit modal
   */
  const handleTableUpdated = () => {
    // Refresh the tables list when a table structure is modified
    fetchTables();
  };

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
        <TableCreateForm
          loading={loading}
          onCreateTable={handleCreateTable}
          onCancel={handleCreateTableCancel}
        />
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
