import React, { useState, useEffect } from 'react';

interface Column {
  key: string;
  title: string;
  dataIndex: string;
  render?: (value: any, record: any) => React.ReactNode;
  sortable?: boolean;
  width?: string;
}

interface Pagination {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
  has_more: boolean;
}

interface CRUDTableProps {
  columns: Column[];
  data: any[];
  loading: boolean;
  pagination: Pagination;
  onPageChange: (page: number) => void;
  onPerPageChange: (perPage: number) => void;
  onSort: (sortBy: string, sortDir: 'asc' | 'desc') => void;
  selectedRows?: string[];
  onSelectionChange?: (selectedRows: string[]) => void;
  actions?: {
    view?: (record: any) => void;
    edit?: (record: any) => void;
    delete?: (record: any) => void;
  };
  bulkActions?: Array<{
    key: string;
    label: string;
    action: (selectedRows: string[]) => void;
    icon?: React.ReactNode;
    danger?: boolean;
  }>;
}

const CRUDTable: React.FC<CRUDTableProps> = ({
  columns,
  data,
  loading,
  pagination,
  onPageChange,
  onPerPageChange,
  onSort,
  selectedRows = [],
  onSelectionChange,
  actions,
  bulkActions
}) => {
  const [sortBy, setSortBy] = useState<string>('');
  const [sortDir, setSortDir] = useState<'asc' | 'desc'>('desc');

  const handleSort = (column: string) => {
    const newSortDir = sortBy === column && sortDir === 'desc' ? 'asc' : 'desc';
    setSortBy(column);
    setSortDir(newSortDir);
    onSort(column, newSortDir);
  };

  const handleSelectAll = (checked: boolean) => {
    if (!onSelectionChange) return;
    
    if (checked) {
      const allIds = data.map(item => item.id.toString());
      onSelectionChange(allIds);
    } else {
      onSelectionChange([]);
    }
  };

  const handleSelectRow = (id: string, checked: boolean) => {
    if (!onSelectionChange) return;
    
    if (checked) {
      onSelectionChange([...selectedRows, id]);
    } else {
      onSelectionChange(selectedRows.filter(rowId => rowId !== id));
    }
  };

  const isAllSelected = selectedRows.length > 0 && selectedRows.length === data.length;
  const isIndeterminate = selectedRows.length > 0 && selectedRows.length < data.length;

  return (
    <div className="crud-table-container">
      {/* Bulk Actions */}
      {bulkActions && selectedRows.length > 0 && (
        <div className="bulk-actions-bar">
          <div className="selected-count">
            {selectedRows.length} item(s) dipilih
          </div>
          <div className="bulk-action-buttons">
            {bulkActions.map(action => (
              <button
                key={action.key}
                className={`bulk-action-btn ${action.danger ? 'danger' : ''}`}
                onClick={() => action.action(selectedRows)}
              >
                {action.icon}
                {action.label}
              </button>
            ))}
          </div>
        </div>
      )}

      {/* Table */}
      <div className="table-wrapper">
        <table className="crud-table">
          <thead>
            <tr>
              {onSelectionChange && (
                <th className="checkbox-column">
                  <input
                    type="checkbox"
                    checked={isAllSelected}
                    ref={input => {
                      if (input) input.indeterminate = isIndeterminate;
                    }}
                    onChange={(e) => handleSelectAll(e.target.checked)}
                  />
                </th>
              )}
              {columns.map(column => (
                <th
                  key={column.key}
                  className={`${column.sortable ? 'sortable' : ''} ${sortBy === column.dataIndex ? 'sorted' : ''}`}
                  style={{ width: column.width }}
                  onClick={() => column.sortable && handleSort(column.dataIndex)}
                >
                  <div className="th-content">
                    {column.title}
                    {column.sortable && (
                      <span className="sort-icon">
                        {sortBy === column.dataIndex ? (
                          sortDir === 'asc' ? '↑' : '↓'
                        ) : '↕'}
                      </span>
                    )}
                  </div>
                </th>
              ))}
              {actions && (
                <th className="actions-column">Aksi</th>
              )}
            </tr>
          </thead>
          <tbody>
            {loading ? (
              <tr>
                <td colSpan={columns.length + (onSelectionChange ? 1 : 0) + (actions ? 1 : 0)}>
                  <div className="loading-state">
                    <div className="loading-spinner"></div>
                    <span>Memuat data...</span>
                  </div>
                </td>
              </tr>
            ) : data.length === 0 ? (
              <tr>
                <td colSpan={columns.length + (onSelectionChange ? 1 : 0) + (actions ? 1 : 0)}>
                  <div className="empty-state">
                    <div className="empty-icon">📋</div>
                    <p>Tidak ada data</p>
                  </div>
                </td>
              </tr>
            ) : (
              data.map((record, index) => (
                <tr key={record.id || index} className={selectedRows.includes(record.id?.toString()) ? 'selected' : ''}>
                  {onSelectionChange && (
                    <td className="checkbox-column">
                      <input
                        type="checkbox"
                        checked={selectedRows.includes(record.id?.toString())}
                        onChange={(e) => handleSelectRow(record.id?.toString(), e.target.checked)}
                      />
                    </td>
                  )}
                  {columns.map(column => (
                    <td key={column.key}>
                      {column.render
                        ? column.render(record[column.dataIndex], record)
                        : record[column.dataIndex]
                      }
                    </td>
                  ))}
                  {actions && (
                    <td className="actions-column">
                      <div className="action-buttons">
                        {actions.view && (
                          <button
                            className="action-btn view"
                            onClick={() => actions.view!(record)}
                            title="Lihat Detail"
                          >
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                              <circle cx="12" cy="12" r="3"/>
                            </svg>
                          </button>
                        )}
                        {actions.edit && (
                          <button
                            className="action-btn edit"
                            onClick={() => actions.edit!(record)}
                            title="Edit"
                          >
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                          </button>
                        )}
                        {actions.delete && (
                          <button
                            className="action-btn delete"
                            onClick={() => actions.delete!(record)}
                            title="Hapus"
                          >
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                              <polyline points="3,6 5,6 21,6"/>
                              <path d="M19,6l-2,14a2,2,0,0,1-2,2H9a2,2,0,0,1-2-2L5,6"/>
                              <path d="M10,11v6"/>
                              <path d="M14,11v6"/>
                            </svg>
                          </button>
                        )}
                      </div>
                    </td>
                  )}
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {/* Pagination */}
      <div className="pagination-container">
        <div className="pagination-info">
          Menampilkan {data.length} dari {pagination.total} data
        </div>
        
        <div className="pagination-controls">
          <select
            value={pagination.per_page}
            onChange={(e) => onPerPageChange(parseInt(e.target.value))}
            className="per-page-select"
          >
            <option value={10}>10 per halaman</option>
            <option value={15}>15 per halaman</option>
            <option value={25}>25 per halaman</option>
            <option value={50}>50 per halaman</option>
          </select>

          <div className="page-buttons">
            <button
              disabled={pagination.current_page === 1}
              onClick={() => onPageChange(pagination.current_page - 1)}
              className="page-btn"
            >
              ‹ Sebelumnya
            </button>

            {/* Page numbers */}
            {Array.from({ length: Math.min(5, pagination.last_page) }, (_, i) => {
              const page = Math.max(1, pagination.current_page - 2) + i;
              if (page <= pagination.last_page) {
                return (
                  <button
                    key={page}
                    onClick={() => onPageChange(page)}
                    className={`page-btn ${page === pagination.current_page ? 'active' : ''}`}
                  >
                    {page}
                  </button>
                );
              }
              return null;
            })}

            <button
              disabled={!pagination.has_more}
              onClick={() => onPageChange(pagination.current_page + 1)}
              className="page-btn"
            >
              Selanjutnya ›
            </button>
          </div>
        </div>
      </div>

      <style jsx>{`
        .crud-table-container {
          background: white;
          border-radius: 12px;
          box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
          overflow: hidden;
        }

        .bulk-actions-bar {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 16px 20px;
          background: #f8fafc;
          border-bottom: 1px solid #e2e8f0;
        }

        .selected-count {
          font-size: 14px;
          font-weight: 600;
          color: #1e293b;
        }

        .bulk-action-buttons {
          display: flex;
          gap: 8px;
        }

        .bulk-action-btn {
          display: flex;
          align-items: center;
          gap: 6px;
          padding: 6px 12px;
          border: 1px solid #e2e8f0;
          border-radius: 6px;
          background: white;
          color: #64748b;
          font-size: 12px;
          cursor: pointer;
          transition: all 0.2s ease;
        }

        .bulk-action-btn:hover {
          background: #f1f5f9;
          border-color: #cbd5e1;
        }

        .bulk-action-btn.danger {
          color: #dc2626;
          border-color: #fecaca;
        }

        .bulk-action-btn.danger:hover {
          background: #fee2e2;
          border-color: #fca5a5;
        }

        .table-wrapper {
          overflow-x: auto;
        }

        .crud-table {
          width: 100%;
          border-collapse: collapse;
        }

        .crud-table th {
          background: #f8fafc;
          padding: 16px 20px;
          text-align: left;
          font-weight: 600;
          color: #1e293b;
          font-size: 13px;
          border-bottom: 1px solid #e2e8f0;
        }

        .crud-table th.sortable {
          cursor: pointer;
          user-select: none;
        }

        .crud-table th.sortable:hover {
          background: #f1f5f9;
        }

        .crud-table th.sorted {
          background: #e2e8f0;
        }

        .th-content {
          display: flex;
          align-items: center;
          justify-content: space-between;
        }

        .sort-icon {
          margin-left: 8px;
          font-size: 12px;
          opacity: 0.7;
        }

        .checkbox-column {
          width: 40px;
        }

        .actions-column {
          width: 120px;
        }

        .crud-table td {
          padding: 16px 20px;
          border-bottom: 1px solid #f1f5f9;
          font-size: 14px;
          color: #374151;
        }

        .crud-table tr:hover {
          background: #f8fafc;
        }

        .crud-table tr.selected {
          background: #eff6ff;
        }

        .action-buttons {
          display: flex;
          gap: 8px;
        }

        .action-btn {
          display: flex;
          align-items: center;
          justify-content: center;
          width: 32px;
          height: 32px;
          border: 1px solid #e2e8f0;
          border-radius: 6px;
          background: white;
          cursor: pointer;
          transition: all 0.2s ease;
        }

        .action-btn:hover {
          background: #f1f5f9;
          border-color: #cbd5e1;
        }

        .action-btn.view {
          color: #2563eb;
        }

        .action-btn.edit {
          color: #d97706;
        }

        .action-btn.delete {
          color: #dc2626;
        }

        .loading-state,
        .empty-state {
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          padding: 60px 20px;
          color: #64748b;
        }

        .loading-spinner {
          width: 32px;
          height: 32px;
          border: 3px solid #f1f5f9;
          border-top: 3px solid #3b82f6;
          border-radius: 50%;
          animation: spin 1s linear infinite;
          margin-bottom: 16px;
        }

        @keyframes spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
        }

        .empty-icon {
          font-size: 48px;
          margin-bottom: 16px;
          opacity: 0.5;
        }

        .pagination-container {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 16px 20px;
          background: #f8fafc;
          border-top: 1px solid #e2e8f0;
        }

        .pagination-info {
          font-size: 14px;
          color: #64748b;
        }

        .pagination-controls {
          display: flex;
          align-items: center;
          gap: 16px;
        }

        .per-page-select {
          padding: 6px 12px;
          border: 1px solid #e2e8f0;
          border-radius: 6px;
          background: white;
          font-size: 13px;
          color: #374151;
        }

        .page-buttons {
          display: flex;
          gap: 4px;
        }

        .page-btn {
          padding: 6px 12px;
          border: 1px solid #e2e8f0;
          border-radius: 6px;
          background: white;
          color: #374151;
          font-size: 13px;
          cursor: pointer;
          transition: all 0.2s ease;
        }

        .page-btn:hover:not(:disabled) {
          background: #f1f5f9;
          border-color: #cbd5e1;
        }

        .page-btn.active {
          background: #3b82f6;
          border-color: #3b82f6;
          color: white;
        }

        .page-btn:disabled {
          opacity: 0.5;
          cursor: not-allowed;
        }

        @media (max-width: 768px) {
          .crud-table th,
          .crud-table td {
            padding: 12px 16px;
            font-size: 13px;
          }

          .pagination-container {
            flex-direction: column;
            gap: 12px;
          }

          .pagination-controls {
            width: 100%;
            justify-content: space-between;
          }
        }
      `}</style>
    </div>
  );
};

export default CRUDTable;