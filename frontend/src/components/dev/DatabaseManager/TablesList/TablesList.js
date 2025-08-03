import React from 'react';
import TableCard from '../TableCard/TableCard';
import './TablesList.css';

/**
 * TablesList Component
 * 
 * Displays a list of database tables in a card layout with action buttons.
 * Shows an empty state when no tables are found.
 * 
 * @param {Object} props
 * @param {Array} props.tables - Array of table objects to display
 * @param {boolean} props.loading - Whether any operation is in progress
 * @param {Function} props.onDelete - Callback function when delete button is clicked
 * @param {Function} props.onView - Callback function when view button is clicked
 * @param {Function} props.onEdit - Callback function when edit button is clicked
 * @param {string} props.className - Additional CSS classes for the container
 */
const TablesList = ({ 
  tables = [], 
  loading = false, 
  onDelete, 
  onView, 
  onEdit, 
  className = "" 
}) => {
  /**
   * Render empty state when no tables exist
   */
  const renderEmptyState = () => (
    <div className="text-center py-5">
      <div className="text-muted">
        <span className="fs-1 d-block mb-3" role="img" aria-label="Empty folder">
          📂
        </span>
        <h4 className="mb-3">No tables found</h4>
        <p className="mb-0">Create your first table to get started!</p>
      </div>
    </div>
  );

  /**
   * Render the list of table cards
   */
  const renderTableCards = () => (
    <div className="row g-4">
      {tables.map((table) => (
        <TableCard
          key={table.table_name}
          table={table}
          onDelete={onDelete}
          onView={onView}
          onEdit={onEdit}
          loading={loading}
        />
      ))}
    </div>
  );

  return (
    <div className={`row ${className}`}>
      <div className="col">
        <div className="card shadow">
          {/* Card Header */}
          <div className="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 className="card-title mb-0">
              <span className="me-2" role="img" aria-label="Tables list">
                📋
              </span>
              Existing Tables
            </h5>
            {tables.length > 0 && (
              <span className="badge bg-primary" title={`${tables.length} table${tables.length !== 1 ? 's' : ''} found`}>
                {tables.length} table{tables.length !== 1 ? 's' : ''}
              </span>
            )}
          </div>

          {/* Card Body */}
          <div className="card-body">
            {tables.length === 0 ? renderEmptyState() : renderTableCards()}
          </div>
        </div>
      </div>
    </div>
  );
};

export default TablesList;
