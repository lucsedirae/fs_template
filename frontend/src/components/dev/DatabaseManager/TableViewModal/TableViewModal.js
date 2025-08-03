import React, { useState, useEffect, useCallback } from 'react';
import './TableViewModal.css';

/**
 * TableViewModal Component
 * 
 * A modal component that displays table data with pagination
 * 
 * @param {Object} props
 * @param {boolean} props.show - Whether to show the modal
 * @param {Function} props.onHide - Callback when modal is closed
 * @param {string} props.tableName - Name of the table to display
 * @param {string} props.apiBaseUrl - Base URL for API calls
 */
const TableViewModal = ({ show, onHide, tableName, apiBaseUrl }) => {
  const [tableData, setTableData] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [currentPage, setCurrentPage] = useState(1);
  const [perPage] = useState(50); // Fixed for now, could be made configurable

  // Fetch table data - wrapped in useCallback to prevent infinite re-renders
  const fetchTableData = useCallback(async (page = 1) => {
    setLoading(true);
    setError(null);

    try {
      const offset = (page - 1) * perPage;
      const response = await fetch(
        `${apiBaseUrl}/api/tables/${tableName}/data?limit=${perPage}&offset=${offset}`
      );

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();

      if (data.status === 'error') {
        throw new Error(data.message);
      }

      setTableData(data);
      setCurrentPage(page);
    } catch (err) {
      setError(`Failed to fetch table data: ${err.message}`);
      console.error('Fetch table data error:', err);
    } finally {
      setLoading(false);
    }
  }, [apiBaseUrl, tableName, perPage]);

  // Reset state when modal opens/closes or table changes
  useEffect(() => {
    if (show && tableName) {
      setCurrentPage(1);
      setError(null);
      fetchTableData(1);
    } else {
      setTableData(null);
      setError(null);
    }
  }, [show, tableName, fetchTableData]);

  // Handle pagination
  const handlePageChange = (page) => {
    if (page !== currentPage && page >= 1) {
      fetchTableData(page);
    }
  };

  // Calculate pagination info
  const totalPages = tableData ? Math.ceil(tableData.total_rows / perPage) : 0;
  const startRow = tableData ? (currentPage - 1) * perPage + 1 : 0;
  const endRow = tableData ? Math.min(currentPage * perPage, tableData.total_rows) : 0;

  // Render table content
  const renderTableContent = () => {
    if (loading) {
      return (
        <div className="text-center py-5">
          <div className="spinner-border text-primary" role="status">
            <span className="visually-hidden">Loading...</span>
          </div>
          <p className="mt-3 text-muted">Loading table data...</p>
        </div>
      );
    }

    if (error) {
      return (
        <div className="alert alert-danger" role="alert">
          <strong>Error:</strong> {error}
        </div>
      );
    }

    if (!tableData || !tableData.rows) {
      return (
        <div className="text-center py-5 text-muted">
          <span className="fs-1 d-block mb-3">📊</span>
          <p>No data available</p>
        </div>
      );
    }

    if (tableData.rows.length === 0) {
      return (
        <div className="text-center py-5 text-muted">
          <span className="fs-1 d-block mb-3">📭</span>
          <h5>Table is empty</h5>
          <p>This table contains no data yet.</p>
        </div>
      );
    }

    return (
      <>
        {/* Table Stats */}
        <div className="d-flex justify-content-between align-items-center mb-3">
          <div className="text-muted">
            Showing {startRow}-{endRow} of {tableData.total_rows} rows
          </div>
          <div className="text-muted small">
            Page {currentPage} of {totalPages}
          </div>
        </div>

        {/* Table */}
        <div className="table-responsive" style={{ maxHeight: '400px', overflowY: 'auto' }}>
          <table className="table table-sm table-striped table-hover">
            <thead className="table-dark sticky-top">
              <tr>
                {tableData.columns.map((column) => (
                  <th key={column.column_name} scope="col">
                    <div>
                      {column.column_name}
                      <small className="d-block text-light opacity-75">
                        {column.data_type}
                        {column.is_nullable === 'NO' && (
                          <span className="badge bg-warning ms-1" title="Not Null">!</span>
                        )}
                      </small>
                    </div>
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {tableData.rows.map((row, index) => (
                <tr key={index}>
                  {tableData.columns.map((column) => (
                    <td key={column.column_name}>
                      {row[column.column_name] !== null ? (
                        <span>{String(row[column.column_name])}</span>
                      ) : (
                        <span className="text-muted fst-italic">NULL</span>
                      )}
                    </td>
                  ))}
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {/* Pagination */}
        {totalPages > 1 && (
          <nav aria-label="Table pagination" className="mt-3">
            <ul className="pagination pagination-sm justify-content-center">
              <li className={`page-item ${currentPage === 1 ? 'disabled' : ''}`}>
                <button
                  className="page-link"
                  onClick={() => handlePageChange(currentPage - 1)}
                  disabled={currentPage === 1 || loading}
                >
                  Previous
                </button>
              </li>

              {/* Page numbers */}
              {[...Array(Math.min(5, totalPages))].map((_, index) => {
                let pageNum;
                if (totalPages <= 5) {
                  pageNum = index + 1;
                } else if (currentPage <= 3) {
                  pageNum = index + 1;
                } else if (currentPage >= totalPages - 2) {
                  pageNum = totalPages - 4 + index;
                } else {
                  pageNum = currentPage - 2 + index;
                }

                return (
                  <li
                    key={pageNum}
                    className={`page-item ${currentPage === pageNum ? 'active' : ''}`}
                  >
                    <button
                      className="page-link"
                      onClick={() => handlePageChange(pageNum)}
                      disabled={loading}
                    >
                      {pageNum}
                    </button>
                  </li>
                );
              })}

              <li className={`page-item ${currentPage === totalPages ? 'disabled' : ''}`}>
                <button
                  className="page-link"
                  onClick={() => handlePageChange(currentPage + 1)}
                  disabled={currentPage === totalPages || loading}
                >
                  Next
                </button>
              </li>
            </ul>
          </nav>
        )}
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
        aria-labelledby="tableViewModalLabel"
        aria-hidden="false"
      >
        <div className="modal-dialog modal-xl">
          <div className="modal-content">
            {/* Modal Header */}
            <div className="modal-header bg-primary text-white">
              <h5 className="modal-title" id="tableViewModalLabel">
                <span className="me-2">🗃️</span>
                Table: {tableName}
              </h5>
              <button
                type="button"
                className="btn-close btn-close-white"
                onClick={onHide}
                aria-label="Close"
              ></button>
            </div>

            {/* Modal Body */}
            <div className="modal-body">
              {renderTableContent()}
            </div>

            {/* Modal Footer */}
            <div className="modal-footer">
              <div className="d-flex justify-content-between w-100 align-items-center">
                <div className="text-muted small">
                  {tableData && (
                    <>
                      <strong>{tableData.table_name}</strong> - 
                      {tableData.columns?.length || 0} columns, 
                      {tableData.total_rows || 0} total rows
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

export default TableViewModal;