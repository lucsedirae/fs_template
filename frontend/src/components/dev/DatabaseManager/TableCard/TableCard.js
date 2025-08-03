import React from 'react';
import './TableCard.css';

/**
 * TableCard Component
 * 
 * Displays a database table as a Bootstrap card with actions
 * 
 * @param {Object} props
 * @param {Object} props.table - Table object with table_name and table_type
 * @param {Function} props.onDelete - Callback function when delete button is clicked
 * @param {Function} props.onView - Optional callback for viewing table details
 * @param {Function} props.onEdit - Optional callback for editing table structure
 * @param {boolean} props.loading - Whether any operation is in progress
 * @param {string} props.className - Additional CSS classes for the card container
 */
const TableCard = ({ 
  table, 
  onDelete, 
  loading = false, 
  onView = null,
  onEdit = null,
  className = "" 
}) => {
  const handleDelete = () => {
    if (onDelete) {
      onDelete(table.table_name);
    }
  };

  const handleView = () => {
    if (onView) {
      onView(table.table_name);
    }
  };

  const handleEdit = () => {
    if (onEdit) {
      onEdit(table.table_name);
    }
  };

  return (
    <div className={`col-md-6 col-lg-4 ${className}`}>
      <div className="card h-100 border-primary border-opacity-25 shadow-sm">
        <div className="card-body d-flex flex-column">
          {/* Table Header */}
          <div className="d-flex align-items-center mb-3">
            <span className="fs-5 me-2" role="img" aria-label="Database table">
              🗃️
            </span>
            <h6 className="card-title text-primary mb-0 flex-grow-1">
              {table.table_name}
            </h6>
          </div>

          {/* Table Metadata */}
          <div className="mb-3">
            <span className="badge bg-light text-dark border">
              {table.table_type}
            </span>
          </div>

          {/* Table Description (if available) */}
          {table.description && (
            <p className="card-text text-muted small mb-3">
              {table.description}
            </p>
          )}

          {/* Action Buttons */}
          <div className="mt-auto">
            <div className="d-flex gap-2 flex-wrap">
              {onView && (
                <button
                  type="button"
                  onClick={handleView}
                  className="btn btn-outline-primary btn-sm flex-fill"
                  disabled={loading}
                  title="View table data"
                >
                  <span className="me-1" role="img" aria-label="View">👁️</span>
                  View
                </button>
              )}

              {onEdit && (
                <button
                  type="button"
                  onClick={handleEdit}
                  className="btn btn-outline-warning btn-sm flex-fill"
                  disabled={loading}
                  title="Edit table structure"
                >
                  <span className="me-1" role="img" aria-label="Edit">✏️</span>
                  Edit
                </button>
              )}
              
              <button
                type="button"
                onClick={handleDelete}
                className="btn btn-outline-danger btn-sm flex-fill"
                disabled={loading}
                title="Delete table"
              >
                {loading ? (
                  <>
                    <span 
                      className="spinner-border spinner-border-sm me-1" 
                      role="status" 
                      aria-hidden="true"
                    ></span>
                    Deleting...
                  </>
                ) : (
                  <>
                    <span className="me-1" role="img" aria-label="Delete">🗑️</span>
                    Delete
                  </>
                )}
              </button>
            </div>
          </div>
        </div>

        {/* Optional Footer for additional info */}
        {(table.row_count !== undefined || table.created_at) && (
          <div className="card-footer bg-transparent border-top-0">
            <div className="d-flex justify-content-between align-items-center">
              {table.row_count !== undefined && (
                <small className="text-muted">
                  {table.row_count} rows
                </small>
              )}
              {table.created_at && (
                <small className="text-muted">
                  Created: {new Date(table.created_at).toLocaleDateString()}
                </small>
              )}
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default TableCard;
