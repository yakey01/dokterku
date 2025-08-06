import React, { useState, useEffect } from 'react';
import CRUDTable from './shared/CRUDTable';

interface Patient {
  id: number;
  nama: string;
  no_rekam_medis: string;
  tanggal_lahir: string;
  jenis_kelamin: 'L' | 'P';
  alamat?: string;
  no_telepon?: string;
  status: 'pending' | 'verified' | 'rejected';
  created_at: string;
  updated_at: string;
}

interface PatientFilters {
  search: string;
  jenis_kelamin: string;
  status: string;
  tanggal_lahir_dari: string;
  tanggal_lahir_sampai: string;
}

interface PatientFormData {
  nama: string;
  no_rekam_medis: string;
  tanggal_lahir: string;
  jenis_kelamin: 'L' | 'P';
  alamat: string;
  no_telepon: string;
}

const PasienCRUD: React.FC = () => {
  const [patients, setPatients] = useState<Patient[]>([]);
  const [loading, setLoading] = useState(false);
  const [pagination, setPagination] = useState({
    current_page: 1,
    per_page: 15,
    total: 0,
    last_page: 1,
    has_more: false
  });

  const [filters, setFilters] = useState<PatientFilters>({
    search: '',
    jenis_kelamin: '',
    status: '',
    tanggal_lahir_dari: '',
    tanggal_lahir_sampai: ''
  });

  const [sortBy, setSortBy] = useState('created_at');
  const [sortDir, setSortDir] = useState<'asc' | 'desc'>('desc');
  const [selectedRows, setSelectedRows] = useState<string[]>([]);

  // Modal states
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [showViewModal, setShowViewModal] = useState(false);
  const [currentPatient, setCurrentPatient] = useState<Patient | null>(null);
  
  // Form state
  const [formData, setFormData] = useState<PatientFormData>({
    nama: '',
    no_rekam_medis: '',
    tanggal_lahir: '',
    jenis_kelamin: 'L',
    alamat: '',
    no_telepon: ''
  });
  const [formErrors, setFormErrors] = useState<Partial<PatientFormData>>({});
  const [submitting, setSubmitting] = useState(false);

  // Fetch patients data
  const fetchPatients = async () => {
    setLoading(true);
    try {
      const queryParams = new URLSearchParams({
        page: pagination.current_page.toString(),
        per_page: pagination.per_page.toString(),
        sort_by: sortBy,
        sort_dir: sortDir,
        ...Object.fromEntries(Object.entries(filters).filter(([_, v]) => v !== ''))
      });

      const response = await fetch(`/api/v2/petugas/patients?${queryParams}`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Accept': 'application/json'
        }
      });

      const result = await response.json();
      
      if (result.success) {
        setPatients(result.data);
        setPagination(result.pagination);
      } else {
        console.error('Failed to fetch patients:', result.message);
      }
    } catch (error) {
      console.error('Error fetching patients:', error);
    } finally {
      setLoading(false);
    }
  };

  // Load data on component mount and when dependencies change
  useEffect(() => {
    fetchPatients();
  }, [pagination.current_page, pagination.per_page, sortBy, sortDir, filters]);

  // Handle form submission
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    setFormErrors({});

    try {
      const url = showEditModal && currentPatient
        ? `/api/v2/petugas/patients/${currentPatient.id}`
        : '/api/v2/petugas/patients';

      const method = showEditModal ? 'PUT' : 'POST';

      const response = await fetch(url, {
        method,
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Accept': 'application/json'
        },
        body: JSON.stringify(formData)
      });

      const result = await response.json();

      if (result.success) {
        fetchPatients(); // Refresh data
        closeModal();
      } else {
        if (result.errors) {
          setFormErrors(result.errors);
        } else {
          console.error('Form submission error:', result.message);
        }
      }
    } catch (error) {
      console.error('Error submitting form:', error);
    } finally {
      setSubmitting(false);
    }
  };

  // Handle delete patient
  const handleDelete = async (patient: Patient) => {
    if (!confirm(`Apakah Anda yakin ingin menghapus data pasien "${patient.nama}"?`)) {
      return;
    }

    try {
      const response = await fetch(`/api/v2/petugas/patients/${patient.id}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Accept': 'application/json'
        }
      });

      const result = await response.json();

      if (result.success) {
        fetchPatients(); // Refresh data
      } else {
        console.error('Delete error:', result.message);
      }
    } catch (error) {
      console.error('Error deleting patient:', error);
    }
  };

  // Modal management
  const openCreateModal = () => {
    setFormData({
      nama: '',
      no_rekam_medis: '',
      tanggal_lahir: '',
      jenis_kelamin: 'L',
      alamat: '',
      no_telepon: ''
    });
    setFormErrors({});
    setShowCreateModal(true);
  };

  const openEditModal = (patient: Patient) => {
    setCurrentPatient(patient);
    setFormData({
      nama: patient.nama,
      no_rekam_medis: patient.no_rekam_medis,
      tanggal_lahir: patient.tanggal_lahir,
      jenis_kelamin: patient.jenis_kelamin,
      alamat: patient.alamat || '',
      no_telepon: patient.no_telepon || ''
    });
    setFormErrors({});
    setShowEditModal(true);
  };

  const openViewModal = (patient: Patient) => {
    setCurrentPatient(patient);
    setShowViewModal(true);
  };

  const closeModal = () => {
    setShowCreateModal(false);
    setShowEditModal(false);
    setShowViewModal(false);
    setCurrentPatient(null);
    setFormData({
      nama: '',
      no_rekam_medis: '',
      tanggal_lahir: '',
      jenis_kelamin: 'L',
      alamat: '',
      no_telepon: ''
    });
    setFormErrors({});
  };

  // Table columns configuration
  const columns = [
    {
      key: 'no_rekam_medis',
      title: 'No. Rekam Medis',
      dataIndex: 'no_rekam_medis',
      sortable: true,
      width: '150px'
    },
    {
      key: 'nama',
      title: 'Nama Pasien',
      dataIndex: 'nama',
      sortable: true,
      render: (nama: string, record: Patient) => (
        <div>
          <div className="font-medium">{nama}</div>
          <div className="text-sm text-gray-500">{record.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan'}</div>
        </div>
      )
    },
    {
      key: 'tanggal_lahir',
      title: 'Tanggal Lahir',
      dataIndex: 'tanggal_lahir',
      sortable: true,
      render: (tanggal: string) => new Date(tanggal).toLocaleDateString('id-ID')
    },
    {
      key: 'no_telepon',
      title: 'No. Telepon',
      dataIndex: 'no_telepon',
      render: (phone: string) => phone || '-'
    },
    {
      key: 'status',
      title: 'Status',
      dataIndex: 'status',
      render: (status: string) => (
        <span className={`status-badge ${status}`}>
          {status === 'pending' ? 'Menunggu' : status === 'verified' ? 'Terverifikasi' : 'Ditolak'}
        </span>
      )
    },
    {
      key: 'created_at',
      title: 'Dibuat',
      dataIndex: 'created_at',
      sortable: true,
      render: (date: string) => new Date(date).toLocaleDateString('id-ID')
    }
  ];

  return (
    <div className="pasien-crud">
      {/* Header */}
      <div className="crud-header">
        <div>
          <h1>Manajemen Data Pasien</h1>
          <p>Kelola data pasien klinik</p>
        </div>
        <button className="primary-btn" onClick={openCreateModal}>
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Tambah Pasien
        </button>
      </div>

      {/* Filters */}
      <div className="filters-section">
        <div className="filters-grid">
          <div className="filter-group">
            <input
              type="text"
              placeholder="Cari nama, no. rekam medis, atau telepon..."
              value={filters.search}
              onChange={(e) => setFilters({...filters, search: e.target.value})}
              className="search-input"
            />
          </div>
          
          <div className="filter-group">
            <select
              value={filters.jenis_kelamin}
              onChange={(e) => setFilters({...filters, jenis_kelamin: e.target.value})}
              className="filter-select"
            >
              <option value="">Semua Jenis Kelamin</option>
              <option value="L">Laki-laki</option>
              <option value="P">Perempuan</option>
            </select>
          </div>

          <div className="filter-group">
            <select
              value={filters.status}
              onChange={(e) => setFilters({...filters, status: e.target.value})}
              className="filter-select"
            >
              <option value="">Semua Status</option>
              <option value="pending">Menunggu Verifikasi</option>
              <option value="verified">Terverifikasi</option>
              <option value="rejected">Ditolak</option>
            </select>
          </div>

          <div className="filter-group">
            <input
              type="date"
              placeholder="Tanggal lahir dari"
              value={filters.tanggal_lahir_dari}
              onChange={(e) => setFilters({...filters, tanggal_lahir_dari: e.target.value})}
              className="filter-input"
            />
          </div>

          <div className="filter-group">
            <input
              type="date"
              placeholder="Tanggal lahir sampai"
              value={filters.tanggal_lahir_sampai}
              onChange={(e) => setFilters({...filters, tanggal_lahir_sampai: e.target.value})}
              className="filter-input"
            />
          </div>
        </div>
      </div>

      {/* Table */}
      <CRUDTable
        columns={columns}
        data={patients}
        loading={loading}
        pagination={pagination}
        onPageChange={(page) => setPagination({...pagination, current_page: page})}
        onPerPageChange={(perPage) => setPagination({...pagination, per_page: perPage, current_page: 1})}
        onSort={(sortBy, sortDir) => {
          setSortBy(sortBy);
          setSortDir(sortDir);
        }}
        selectedRows={selectedRows}
        onSelectionChange={setSelectedRows}
        actions={{
          view: openViewModal,
          edit: openEditModal,
          delete: handleDelete
        }}
        bulkActions={[
          {
            key: 'delete',
            label: 'Hapus Terpilih',
            action: (ids) => {
              if (confirm(`Apakah Anda yakin ingin menghapus ${ids.length} pasien terpilih?`)) {
                // Implement bulk delete
                console.log('Bulk delete:', ids);
              }
            },
            danger: true,
            icon: (
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <polyline points="3,6 5,6 21,6"/>
                <path d="M19,6l-2,14a2,2,0,0,1-2,2H9a2,2,0,0,1-2-2L5,6"/>
              </svg>
            )
          }
        ]}
      />

      {/* Create/Edit Modal */}
      {(showCreateModal || showEditModal) && (
        <div className="modal-overlay" onClick={closeModal}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h2>{showCreateModal ? 'Tambah Pasien Baru' : 'Edit Data Pasien'}</h2>
              <button className="close-btn" onClick={closeModal}>×</button>
            </div>

            <form onSubmit={handleSubmit} className="modal-form">
              <div className="form-grid">
                <div className="form-group">
                  <label>Nama Lengkap *</label>
                  <input
                    type="text"
                    value={formData.nama}
                    onChange={(e) => setFormData({...formData, nama: e.target.value})}
                    className={formErrors.nama ? 'error' : ''}
                    required
                  />
                  {formErrors.nama && <span className="error-text">{formErrors.nama}</span>}
                </div>

                <div className="form-group">
                  <label>No. Rekam Medis</label>
                  <input
                    type="text"
                    value={formData.no_rekam_medis}
                    onChange={(e) => setFormData({...formData, no_rekam_medis: e.target.value})}
                    className={formErrors.no_rekam_medis ? 'error' : ''}
                    placeholder="Kosongkan untuk generate otomatis"
                  />
                  {formErrors.no_rekam_medis && <span className="error-text">{formErrors.no_rekam_medis}</span>}
                </div>

                <div className="form-group">
                  <label>Tanggal Lahir *</label>
                  <input
                    type="date"
                    value={formData.tanggal_lahir}
                    onChange={(e) => setFormData({...formData, tanggal_lahir: e.target.value})}
                    className={formErrors.tanggal_lahir ? 'error' : ''}
                    required
                  />
                  {formErrors.tanggal_lahir && <span className="error-text">{formErrors.tanggal_lahir}</span>}
                </div>

                <div className="form-group">
                  <label>Jenis Kelamin *</label>
                  <select
                    value={formData.jenis_kelamin}
                    onChange={(e) => setFormData({...formData, jenis_kelamin: e.target.value as 'L' | 'P'})}
                    className={formErrors.jenis_kelamin ? 'error' : ''}
                    required
                  >
                    <option value="L">Laki-laki</option>
                    <option value="P">Perempuan</option>
                  </select>
                  {formErrors.jenis_kelamin && <span className="error-text">{formErrors.jenis_kelamin}</span>}
                </div>

                <div className="form-group full-width">
                  <label>No. Telepon</label>
                  <input
                    type="tel"
                    value={formData.no_telepon}
                    onChange={(e) => setFormData({...formData, no_telepon: e.target.value})}
                    className={formErrors.no_telepon ? 'error' : ''}
                    placeholder="Contoh: 08123456789"
                  />
                  {formErrors.no_telepon && <span className="error-text">{formErrors.no_telepon}</span>}
                </div>

                <div className="form-group full-width">
                  <label>Alamat</label>
                  <textarea
                    value={formData.alamat}
                    onChange={(e) => setFormData({...formData, alamat: e.target.value})}
                    className={formErrors.alamat ? 'error' : ''}
                    rows={3}
                    placeholder="Alamat lengkap pasien"
                  />
                  {formErrors.alamat && <span className="error-text">{formErrors.alamat}</span>}
                </div>
              </div>

              <div className="modal-actions">
                <button type="button" onClick={closeModal} className="secondary-btn">
                  Batal
                </button>
                <button type="submit" disabled={submitting} className="primary-btn">
                  {submitting ? 'Menyimpan...' : (showCreateModal ? 'Tambah Pasien' : 'Update Pasien')}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* View Modal */}
      {showViewModal && currentPatient && (
        <div className="modal-overlay" onClick={closeModal}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h2>Detail Pasien</h2>
              <button className="close-btn" onClick={closeModal}>×</button>
            </div>

            <div className="patient-details">
              <div className="detail-grid">
                <div className="detail-item">
                  <label>Nama Lengkap</label>
                  <div className="detail-value">{currentPatient.nama}</div>
                </div>
                <div className="detail-item">
                  <label>No. Rekam Medis</label>
                  <div className="detail-value">{currentPatient.no_rekam_medis}</div>
                </div>
                <div className="detail-item">
                  <label>Tanggal Lahir</label>
                  <div className="detail-value">{new Date(currentPatient.tanggal_lahir).toLocaleDateString('id-ID')}</div>
                </div>
                <div className="detail-item">
                  <label>Jenis Kelamin</label>
                  <div className="detail-value">{currentPatient.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan'}</div>
                </div>
                <div className="detail-item">
                  <label>No. Telepon</label>
                  <div className="detail-value">{currentPatient.no_telepon || '-'}</div>
                </div>
                <div className="detail-item">
                  <label>Status</label>
                  <div className="detail-value">
                    <span className={`status-badge ${currentPatient.status}`}>
                      {currentPatient.status === 'pending' ? 'Menunggu Verifikasi' : 
                       currentPatient.status === 'verified' ? 'Terverifikasi' : 'Ditolak'}
                    </span>
                  </div>
                </div>
                <div className="detail-item full-width">
                  <label>Alamat</label>
                  <div className="detail-value">{currentPatient.alamat || '-'}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      <style jsx>{`
        .pasien-crud {
          padding: 24px;
          background: #f8fafc;
          min-height: 100vh;
        }

        .crud-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 24px;
        }

        .crud-header h1 {
          font-size: 24px;
          font-weight: 700;
          color: #1e293b;
          margin: 0 0 4px 0;
        }

        .crud-header p {
          color: #64748b;
          margin: 0;
        }

        .primary-btn {
          display: flex;
          align-items: center;
          gap: 8px;
          padding: 12px 20px;
          background: #3b82f6;
          color: white;
          border: none;
          border-radius: 8px;
          font-weight: 600;
          cursor: pointer;
          transition: all 0.2s ease;
        }

        .primary-btn:hover {
          background: #2563eb;
          transform: translateY(-1px);
        }

        .secondary-btn {
          padding: 12px 20px;
          background: white;
          color: #374151;
          border: 1px solid #e2e8f0;
          border-radius: 8px;
          font-weight: 600;
          cursor: pointer;
          transition: all 0.2s ease;
        }

        .secondary-btn:hover {
          background: #f1f5f9;
          border-color: #cbd5e1;
        }

        .filters-section {
          background: white;
          padding: 20px;
          border-radius: 12px;
          box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
          margin-bottom: 24px;
        }

        .filters-grid {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
          gap: 16px;
        }

        .filter-group {
          display: flex;
          flex-direction: column;
        }

        .search-input,
        .filter-select,
        .filter-input {
          padding: 10px 16px;
          border: 1px solid #e2e8f0;
          border-radius: 8px;
          font-size: 14px;
          transition: all 0.2s ease;
        }

        .search-input:focus,
        .filter-select:focus,
        .filter-input:focus {
          outline: none;
          border-color: #3b82f6;
          box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .status-badge {
          padding: 4px 12px;
          border-radius: 16px;
          font-size: 12px;
          font-weight: 600;
          text-transform: uppercase;
        }

        .status-badge.pending {
          background: #fef3c7;
          color: #92400e;
        }

        .status-badge.verified {
          background: #dcfce7;
          color: #166534;
        }

        .status-badge.rejected {
          background: #fee2e2;
          color: #dc2626;
        }

        .modal-overlay {
          position: fixed;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background: rgba(0, 0, 0, 0.5);
          display: flex;
          align-items: center;
          justify-content: center;
          z-index: 1000;
          padding: 20px;
        }

        .modal-content {
          background: white;
          border-radius: 12px;
          width: 100%;
          max-width: 600px;
          max-height: 90vh;
          overflow-y: auto;
          box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .modal-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 20px 24px;
          border-bottom: 1px solid #e2e8f0;
        }

        .modal-header h2 {
          font-size: 20px;
          font-weight: 700;
          color: #1e293b;
          margin: 0;
        }

        .close-btn {
          width: 32px;
          height: 32px;
          border: none;
          background: #f1f5f9;
          border-radius: 6px;
          font-size: 18px;
          cursor: pointer;
          display: flex;
          align-items: center;
          justify-content: center;
          transition: all 0.2s ease;
        }

        .close-btn:hover {
          background: #e2e8f0;
        }

        .modal-form {
          padding: 24px;
        }

        .form-grid {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 20px;
          margin-bottom: 24px;
        }

        .form-group {
          display: flex;
          flex-direction: column;
        }

        .form-group.full-width {
          grid-column: 1 / -1;
        }

        .form-group label {
          font-weight: 600;
          color: #374151;
          margin-bottom: 6px;
          font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
          padding: 12px 16px;
          border: 1px solid #e2e8f0;
          border-radius: 8px;
          font-size: 14px;
          transition: all 0.2s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
          outline: none;
          border-color: #3b82f6;
          box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-group input.error,
        .form-group select.error,
        .form-group textarea.error {
          border-color: #dc2626;
        }

        .error-text {
          color: #dc2626;
          font-size: 12px;
          margin-top: 4px;
        }

        .modal-actions {
          display: flex;
          justify-content: flex-end;
          gap: 12px;
          padding-top: 20px;
          border-top: 1px solid #e2e8f0;
        }

        .patient-details {
          padding: 24px;
        }

        .detail-grid {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 20px;
        }

        .detail-item {
          display: flex;
          flex-direction: column;
        }

        .detail-item.full-width {
          grid-column: 1 / -1;
        }

        .detail-item label {
          font-weight: 600;
          color: #64748b;
          font-size: 12px;
          text-transform: uppercase;
          letter-spacing: 0.5px;
          margin-bottom: 6px;
        }

        .detail-value {
          color: #1e293b;
          font-size: 14px;
          font-weight: 500;
        }

        @media (max-width: 768px) {
          .pasien-crud {
            padding: 16px;
          }

          .crud-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
          }

          .filters-grid {
            grid-template-columns: 1fr;
          }

          .form-grid {
            grid-template-columns: 1fr;
          }

          .detail-grid {
            grid-template-columns: 1fr;
          }

          .modal-content {
            margin: 0;
            border-radius: 0;
            max-height: 100vh;
          }
        }
      `}</style>
    </div>
  );
};

export default PasienCRUD;