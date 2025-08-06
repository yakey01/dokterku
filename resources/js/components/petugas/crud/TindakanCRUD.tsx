import React, { useState, useEffect } from 'react';
import CRUDTable from './shared/CRUDTable';

interface Tindakan {
  id: number;
  jenis_tindakan_id: number;
  pasien_id: number;
  tanggal_tindakan: string;
  shift_id: number;
  dokter_id?: number;
  paramedis_id?: number;
  non_paramedis_id?: number;
  tarif: number;
  jasa_dokter: number;
  jasa_paramedis: number;
  jasa_non_paramedis: number;
  catatan?: string;
  status: 'pending' | 'selesai' | 'batal';
  status_validasi: string;
  created_at: string;
  updated_at: string;
  jenis_tindakan: {
    id: number;
    nama: string;
    tarif: number;
  };
  pasien: {
    id: number;
    nama: string;
    no_rekam_medis: string;
  };
  dokter?: {
    id: number;
    nama_lengkap: string;
    spesialisasi: string;
  };
  paramedis?: {
    id: number;
    nama_lengkap: string;
    jabatan: string;
  };
  shift: {
    id: number;
    nama_shift: string;
  };
}

interface TindakanFilters {
  search: string;
  status: string;
  jenis_tindakan_id: string;
  dokter_id: string;
  tanggal_dari: string;
  tanggal_sampai: string;
}

interface TindakanFormData {
  jenis_tindakan_id: number;
  pasien_id: number;
  tanggal_tindakan: string;
  shift_id: number;
  dokter_id: number;
  paramedis_id: number;
  non_paramedis_id: number;
  tarif: number;
  jasa_dokter: number;
  jasa_paramedis: number;
  jasa_non_paramedis: number;
  catatan: string;
  status: 'pending' | 'selesai' | 'batal';
}

interface FormDataOptions {
  jenis_tindakan: Array<{
    id: number;
    nama: string;
    tarif: number;
    jasa_non_paramedis: number;
  }>;
  pasien: Array<{
    id: number;
    nama: string;
    no_rekam_medis: string;
  }>;
  dokter: Array<{
    id: number;
    nama_lengkap: string;
    spesialisasi: string;
  }>;
  paramedis: Array<{
    id: number;
    nama_lengkap: string;
    jabatan: string;
  }>;
  non_paramedis: Array<{
    id: number;
    nama_lengkap: string;
    jabatan: string;
  }>;
  shift_templates: Array<{
    id: number;
    nama_shift: string;
  }>;
  default_jaspel_percentage: number;
}

const TindakanCRUD: React.FC = () => {
  const [tindakan, setTindakan] = useState<Tindakan[]>([]);
  const [loading, setLoading] = useState(false);
  const [pagination, setPagination] = useState({
    current_page: 1,
    per_page: 15,
    total: 0,
    last_page: 1,
    has_more: false
  });

  const [filters, setFilters] = useState<TindakanFilters>({
    search: '',
    status: '',
    jenis_tindakan_id: '',
    dokter_id: '',
    tanggal_dari: '',
    tanggal_sampai: ''
  });

  const [sortBy, setSortBy] = useState('tanggal_tindakan');
  const [sortDir, setSortDir] = useState<'asc' | 'desc'>('desc');
  const [selectedRows, setSelectedRows] = useState<string[]>([]);

  // Modal states
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [showViewModal, setShowViewModal] = useState(false);
  const [currentTindakan, setCurrentTindakan] = useState<Tindakan | null>(null);
  
  // Form state
  const [formData, setFormData] = useState<TindakanFormData>({
    jenis_tindakan_id: 0,
    pasien_id: 0,
    tanggal_tindakan: new Date().toISOString().split('T')[0],
    shift_id: 0,
    dokter_id: 0,
    paramedis_id: 0,
    non_paramedis_id: 0,
    tarif: 0,
    jasa_dokter: 0,
    jasa_paramedis: 0,
    jasa_non_paramedis: 0,
    catatan: '',
    status: 'pending'
  });
  const [formErrors, setFormErrors] = useState<Partial<TindakanFormData>>({});
  const [submitting, setSubmitting] = useState(false);
  const [formOptions, setFormOptions] = useState<FormDataOptions | null>(null);

  // Fetch tindakan data
  const fetchTindakan = async () => {
    setLoading(true);
    try {
      const queryParams = new URLSearchParams({
        page: pagination.current_page.toString(),
        per_page: pagination.per_page.toString(),
        sort_by: sortBy,
        sort_dir: sortDir,
        ...Object.fromEntries(Object.entries(filters).filter(([_, v]) => v !== ''))
      });

      const response = await fetch(`/api/v2/petugas/tindakan?${queryParams}`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Accept': 'application/json'
        }
      });

      const result = await response.json();
      
      if (result.success) {
        setTindakan(result.data);
        setPagination(result.pagination);
      } else {
        console.error('Failed to fetch tindakan:', result.message);
      }
    } catch (error) {
      console.error('Error fetching tindakan:', error);
    } finally {
      setLoading(false);
    }
  };

  // Fetch form options
  const fetchFormOptions = async () => {
    try {
      const response = await fetch('/api/v2/petugas/tindakan/form-data', {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Accept': 'application/json'
        }
      });

      const result = await response.json();
      
      if (result.success) {
        setFormOptions(result.data);
      }
    } catch (error) {
      console.error('Error fetching form options:', error);
    }
  };

  // Load data on component mount and when dependencies change
  useEffect(() => {
    fetchTindakan();
  }, [pagination.current_page, pagination.per_page, sortBy, sortDir, filters]);

  useEffect(() => {
    fetchFormOptions();
  }, []);

  // Handle jenis tindakan change - auto calculate jasa
  const handleJenisTindakanChange = (jenisTindakanId: number) => {
    const selectedJenis = formOptions?.jenis_tindakan.find(j => j.id === jenisTindakanId);
    if (selectedJenis && formOptions) {
      const jasaPercentage = formOptions.default_jaspel_percentage / 100;
      const tarif = selectedJenis.tarif;
      const jasaDokter = Math.round(tarif * jasaPercentage);
      const jasaParamedis = Math.round(tarif * (jasaPercentage * 0.5));
      const jasaNonParamedis = selectedJenis.jasa_non_paramedis;

      setFormData({
        ...formData,
        jenis_tindakan_id: jenisTindakanId,
        tarif: tarif,
        jasa_dokter: jasaDokter,
        jasa_paramedis: jasaParamedis,
        jasa_non_paramedis: jasaNonParamedis
      });
    }
  };

  // Handle form submission
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    setFormErrors({});

    try {
      const url = showEditModal && currentTindakan
        ? `/api/v2/petugas/tindakan/${currentTindakan.id}`
        : '/api/v2/petugas/tindakan';

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
        fetchTindakan(); // Refresh data
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

  // Handle delete tindakan
  const handleDelete = async (tindakan: Tindakan) => {
    if (!confirm(`Apakah Anda yakin ingin menghapus tindakan "${tindakan.jenis_tindakan.nama}" untuk pasien "${tindakan.pasien.nama}"?`)) {
      return;
    }

    try {
      const response = await fetch(`/api/v2/petugas/tindakan/${tindakan.id}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
          'Accept': 'application/json'
        }
      });

      const result = await response.json();

      if (result.success) {
        fetchTindakan(); // Refresh data
      } else {
        console.error('Delete error:', result.message);
      }
    } catch (error) {
      console.error('Error deleting tindakan:', error);
    }
  };

  // Modal management
  const openCreateModal = () => {
    setFormData({
      jenis_tindakan_id: 0,
      pasien_id: 0,
      tanggal_tindakan: new Date().toISOString().split('T')[0],
      shift_id: 0,
      dokter_id: 0,
      paramedis_id: 0,
      non_paramedis_id: 0,
      tarif: 0,
      jasa_dokter: 0,
      jasa_paramedis: 0,
      jasa_non_paramedis: 0,
      catatan: '',
      status: 'pending'
    });
    setFormErrors({});
    setShowCreateModal(true);
  };

  const openEditModal = (tindakan: Tindakan) => {
    setCurrentTindakan(tindakan);
    setFormData({
      jenis_tindakan_id: tindakan.jenis_tindakan_id,
      pasien_id: tindakan.pasien_id,
      tanggal_tindakan: tindakan.tanggal_tindakan.split('T')[0],
      shift_id: tindakan.shift_id,
      dokter_id: tindakan.dokter_id || 0,
      paramedis_id: tindakan.paramedis_id || 0,
      non_paramedis_id: tindakan.non_paramedis_id || 0,
      tarif: tindakan.tarif,
      jasa_dokter: tindakan.jasa_dokter,
      jasa_paramedis: tindakan.jasa_paramedis,
      jasa_non_paramedis: tindakan.jasa_non_paramedis,
      catatan: tindakan.catatan || '',
      status: tindakan.status
    });
    setFormErrors({});
    setShowEditModal(true);
  };

  const openViewModal = (tindakan: Tindakan) => {
    setCurrentTindakan(tindakan);
    setShowViewModal(true);
  };

  const closeModal = () => {
    setShowCreateModal(false);
    setShowEditModal(false);
    setShowViewModal(false);
    setCurrentTindakan(null);
    setFormErrors({});
  };

  // Format currency
  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0
    }).format(amount);
  };

  // Table columns configuration
  const columns = [
    {
      key: 'tanggal_tindakan',
      title: 'Tanggal',
      dataIndex: 'tanggal_tindakan',
      sortable: true,
      width: '120px',
      render: (tanggal: string) => new Date(tanggal).toLocaleDateString('id-ID')
    },
    {
      key: 'jenis_tindakan',
      title: 'Jenis Tindakan',
      dataIndex: 'jenis_tindakan',
      sortable: false,
      render: (jenis: any, record: Tindakan) => (
        <div>
          <div className="font-medium">{jenis.nama}</div>
          <div className="text-sm text-gray-500">{formatCurrency(record.tarif)}</div>
        </div>
      )
    },
    {
      key: 'pasien',
      title: 'Pasien',
      dataIndex: 'pasien',
      sortable: false,
      render: (pasien: any) => (
        <div>
          <div className="font-medium">{pasien.nama}</div>
          <div className="text-sm text-gray-500">{pasien.no_rekam_medis}</div>
        </div>
      )
    },
    {
      key: 'dokter',
      title: 'Dokter',
      dataIndex: 'dokter',
      sortable: false,
      render: (dokter: any) => dokter ? (
        <div>
          <div className="font-medium">{dokter.nama_lengkap}</div>
          <div className="text-sm text-gray-500">{dokter.spesialisasi}</div>
        </div>
      ) : '-'
    },
    {
      key: 'shift',
      title: 'Shift',
      dataIndex: 'shift',
      sortable: false,
      render: (shift: any) => shift?.nama_shift || '-'
    },
    {
      key: 'status',
      title: 'Status',
      dataIndex: 'status',
      render: (status: string) => (
        <span className={`status-badge ${status}`}>
          {status === 'pending' ? 'Menunggu' : status === 'selesai' ? 'Selesai' : 'Batal'}
        </span>
      )
    },
    {
      key: 'status_validasi',
      title: 'Validasi',
      dataIndex: 'status_validasi',
      render: (status: string) => (
        <span className={`validation-badge ${status}`}>
          {status === 'pending' ? 'Menunggu' : status === 'approved' ? 'Disetujui' : 'Ditolak'}
        </span>
      )
    }
  ];

  return (
    <div className="tindakan-crud">
      {/* Header */}
      <div className="crud-header">
        <div>
          <h1>Manajemen Tindakan Medis</h1>
          <p>Kelola data tindakan dan prosedur medis</p>
        </div>
        <button className="primary-btn" onClick={openCreateModal}>
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Tambah Tindakan
        </button>
      </div>

      {/* Filters */}
      <div className="filters-section">
        <div className="filters-grid">
          <div className="filter-group">
            <input
              type="text"
              placeholder="Cari tindakan, pasien, atau dokter..."
              value={filters.search}
              onChange={(e) => setFilters({...filters, search: e.target.value})}
              className="search-input"
            />
          </div>
          
          <div className="filter-group">
            <select
              value={filters.status}
              onChange={(e) => setFilters({...filters, status: e.target.value})}
              className="filter-select"
            >
              <option value="">Semua Status</option>
              <option value="pending">Menunggu</option>
              <option value="selesai">Selesai</option>
              <option value="batal">Batal</option>
            </select>
          </div>

          <div className="filter-group">
            <select
              value={filters.jenis_tindakan_id}
              onChange={(e) => setFilters({...filters, jenis_tindakan_id: e.target.value})}
              className="filter-select"
            >
              <option value="">Semua Jenis Tindakan</option>
              {formOptions?.jenis_tindakan.map(jenis => (
                <option key={jenis.id} value={jenis.id}>{jenis.nama}</option>
              ))}
            </select>
          </div>

          <div className="filter-group">
            <select
              value={filters.dokter_id}
              onChange={(e) => setFilters({...filters, dokter_id: e.target.value})}
              className="filter-select"
            >
              <option value="">Semua Dokter</option>
              {formOptions?.dokter.map(dokter => (
                <option key={dokter.id} value={dokter.id}>{dokter.nama_lengkap}</option>
              ))}
            </select>
          </div>

          <div className="filter-group">
            <input
              type="date"
              placeholder="Tanggal dari"
              value={filters.tanggal_dari}
              onChange={(e) => setFilters({...filters, tanggal_dari: e.target.value})}
              className="filter-input"
            />
          </div>

          <div className="filter-group">
            <input
              type="date"
              placeholder="Tanggal sampai"
              value={filters.tanggal_sampai}
              onChange={(e) => setFilters({...filters, tanggal_sampai: e.target.value})}
              className="filter-input"
            />
          </div>
        </div>
      </div>

      {/* Table */}
      <CRUDTable
        columns={columns}
        data={tindakan}
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
              if (confirm(`Apakah Anda yakin ingin menghapus ${ids.length} tindakan terpilih?`)) {
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
      {(showCreateModal || showEditModal) && formOptions && (
        <div className="modal-overlay" onClick={closeModal}>
          <div className="modal-content large" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h2>{showCreateModal ? 'Tambah Tindakan Baru' : 'Edit Data Tindakan'}</h2>
              <button className="close-btn" onClick={closeModal}>×</button>
            </div>

            <form onSubmit={handleSubmit} className="modal-form">
              <div className="form-grid">
                <div className="form-group">
                  <label>Jenis Tindakan *</label>
                  <select
                    value={formData.jenis_tindakan_id}
                    onChange={(e) => handleJenisTindakanChange(parseInt(e.target.value))}
                    className={formErrors.jenis_tindakan_id ? 'error' : ''}
                    required
                  >
                    <option value={0}>Pilih Jenis Tindakan</option>
                    {formOptions.jenis_tindakan.map(jenis => (
                      <option key={jenis.id} value={jenis.id}>
                        {jenis.nama} - {formatCurrency(jenis.tarif)}
                      </option>
                    ))}
                  </select>
                  {formErrors.jenis_tindakan_id && <span className="error-text">{formErrors.jenis_tindakan_id}</span>}
                </div>

                <div className="form-group">
                  <label>Pasien *</label>
                  <select
                    value={formData.pasien_id}
                    onChange={(e) => setFormData({...formData, pasien_id: parseInt(e.target.value)})}
                    className={formErrors.pasien_id ? 'error' : ''}
                    required
                  >
                    <option value={0}>Pilih Pasien</option>
                    {formOptions.pasien.map(pasien => (
                      <option key={pasien.id} value={pasien.id}>
                        {pasien.nama} ({pasien.no_rekam_medis})
                      </option>
                    ))}
                  </select>
                  {formErrors.pasien_id && <span className="error-text">{formErrors.pasien_id}</span>}
                </div>

                <div className="form-group">
                  <label>Tanggal Tindakan *</label>
                  <input
                    type="date"
                    value={formData.tanggal_tindakan}
                    onChange={(e) => setFormData({...formData, tanggal_tindakan: e.target.value})}
                    className={formErrors.tanggal_tindakan ? 'error' : ''}
                    required
                  />
                  {formErrors.tanggal_tindakan && <span className="error-text">{formErrors.tanggal_tindakan}</span>}
                </div>

                <div className="form-group">
                  <label>Shift *</label>
                  <select
                    value={formData.shift_id}
                    onChange={(e) => setFormData({...formData, shift_id: parseInt(e.target.value)})}
                    className={formErrors.shift_id ? 'error' : ''}
                    required
                  >
                    <option value={0}>Pilih Shift</option>
                    {formOptions.shift_templates.map(shift => (
                      <option key={shift.id} value={shift.id}>{shift.nama_shift}</option>
                    ))}
                  </select>
                  {formErrors.shift_id && <span className="error-text">{formErrors.shift_id}</span>}
                </div>

                <div className="form-group">
                  <label>Dokter</label>
                  <select
                    value={formData.dokter_id}
                    onChange={(e) => setFormData({...formData, dokter_id: parseInt(e.target.value)})}
                    className={formErrors.dokter_id ? 'error' : ''}
                  >
                    <option value={0}>Pilih Dokter</option>
                    {formOptions.dokter.map(dokter => (
                      <option key={dokter.id} value={dokter.id}>
                        {dokter.nama_lengkap} - {dokter.spesialisasi}
                      </option>
                    ))}
                  </select>
                  {formErrors.dokter_id && <span className="error-text">{formErrors.dokter_id}</span>}
                </div>

                <div className="form-group">
                  <label>Paramedis</label>
                  <select
                    value={formData.paramedis_id}
                    onChange={(e) => setFormData({...formData, paramedis_id: parseInt(e.target.value)})}
                    className={formErrors.paramedis_id ? 'error' : ''}
                  >
                    <option value={0}>Pilih Paramedis</option>
                    {formOptions.paramedis.map(paramedis => (
                      <option key={paramedis.id} value={paramedis.id}>
                        {paramedis.nama_lengkap} - {paramedis.jabatan}
                      </option>
                    ))}
                  </select>
                  {formErrors.paramedis_id && <span className="error-text">{formErrors.paramedis_id}</span>}
                </div>

                <div className="form-group">
                  <label>Non-Paramedis</label>
                  <select
                    value={formData.non_paramedis_id}
                    onChange={(e) => setFormData({...formData, non_paramedis_id: parseInt(e.target.value)})}
                    className={formErrors.non_paramedis_id ? 'error' : ''}
                  >
                    <option value={0}>Pilih Non-Paramedis</option>
                    {formOptions.non_paramedis.map(nonParamedis => (
                      <option key={nonParamedis.id} value={nonParamedis.id}>
                        {nonParamedis.nama_lengkap} - {nonParamedis.jabatan}
                      </option>
                    ))}
                  </select>
                  {formErrors.non_paramedis_id && <span className="error-text">{formErrors.non_paramedis_id}</span>}
                </div>

                <div className="form-group">
                  <label>Status *</label>
                  <select
                    value={formData.status}
                    onChange={(e) => setFormData({...formData, status: e.target.value as 'pending' | 'selesai' | 'batal'})}
                    className={formErrors.status ? 'error' : ''}
                    required
                  >
                    <option value="pending">Menunggu</option>
                    <option value="selesai">Selesai</option>
                    <option value="batal">Batal</option>
                  </select>
                  {formErrors.status && <span className="error-text">{formErrors.status}</span>}
                </div>

                <div className="form-group">
                  <label>Tarif *</label>
                  <input
                    type="number"
                    value={formData.tarif}
                    onChange={(e) => setFormData({...formData, tarif: parseFloat(e.target.value) || 0})}
                    className={formErrors.tarif ? 'error' : ''}
                    required
                    min="0"
                    step="1000"
                  />
                  {formErrors.tarif && <span className="error-text">{formErrors.tarif}</span>}
                </div>

                <div className="form-group">
                  <label>Jasa Dokter *</label>
                  <input
                    type="number"
                    value={formData.jasa_dokter}
                    onChange={(e) => setFormData({...formData, jasa_dokter: parseFloat(e.target.value) || 0})}
                    className={formErrors.jasa_dokter ? 'error' : ''}
                    required
                    min="0"
                    step="1000"
                  />
                  {formErrors.jasa_dokter && <span className="error-text">{formErrors.jasa_dokter}</span>}
                </div>

                <div className="form-group">
                  <label>Jasa Paramedis *</label>
                  <input
                    type="number"
                    value={formData.jasa_paramedis}
                    onChange={(e) => setFormData({...formData, jasa_paramedis: parseFloat(e.target.value) || 0})}
                    className={formErrors.jasa_paramedis ? 'error' : ''}
                    required
                    min="0"
                    step="1000"
                  />
                  {formErrors.jasa_paramedis && <span className="error-text">{formErrors.jasa_paramedis}</span>}
                </div>

                <div className="form-group">
                  <label>Jasa Non-Paramedis *</label>
                  <input
                    type="number"
                    value={formData.jasa_non_paramedis}
                    onChange={(e) => setFormData({...formData, jasa_non_paramedis: parseFloat(e.target.value) || 0})}
                    className={formErrors.jasa_non_paramedis ? 'error' : ''}
                    required
                    min="0"
                    step="1000"
                  />
                  {formErrors.jasa_non_paramedis && <span className="error-text">{formErrors.jasa_non_paramedis}</span>}
                </div>

                <div className="form-group full-width">
                  <label>Catatan</label>
                  <textarea
                    value={formData.catatan}
                    onChange={(e) => setFormData({...formData, catatan: e.target.value})}
                    className={formErrors.catatan ? 'error' : ''}
                    rows={3}
                    placeholder="Catatan tambahan tindakan"
                  />
                  {formErrors.catatan && <span className="error-text">{formErrors.catatan}</span>}
                </div>
              </div>

              <div className="modal-actions">
                <button type="button" onClick={closeModal} className="secondary-btn">
                  Batal
                </button>
                <button type="submit" disabled={submitting} className="primary-btn">
                  {submitting ? 'Menyimpan...' : (showCreateModal ? 'Tambah Tindakan' : 'Update Tindakan')}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* View Modal */}
      {showViewModal && currentTindakan && (
        <div className="modal-overlay" onClick={closeModal}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <div className="modal-header">
              <h2>Detail Tindakan</h2>
              <button className="close-btn" onClick={closeModal}>×</button>
            </div>

            <div className="tindakan-details">
              <div className="detail-grid">
                <div className="detail-item">
                  <label>Jenis Tindakan</label>
                  <div className="detail-value">{currentTindakan.jenis_tindakan.nama}</div>
                </div>
                <div className="detail-item">
                  <label>Pasien</label>
                  <div className="detail-value">
                    {currentTindakan.pasien.nama} ({currentTindakan.pasien.no_rekam_medis})
                  </div>
                </div>
                <div className="detail-item">
                  <label>Tanggal Tindakan</label>
                  <div className="detail-value">{new Date(currentTindakan.tanggal_tindakan).toLocaleDateString('id-ID')}</div>
                </div>
                <div className="detail-item">
                  <label>Shift</label>
                  <div className="detail-value">{currentTindakan.shift.nama_shift}</div>
                </div>
                <div className="detail-item">
                  <label>Dokter</label>
                  <div className="detail-value">
                    {currentTindakan.dokter ? `${currentTindakan.dokter.nama_lengkap} - ${currentTindakan.dokter.spesialisasi}` : '-'}
                  </div>
                </div>
                <div className="detail-item">
                  <label>Paramedis</label>
                  <div className="detail-value">
                    {currentTindakan.paramedis ? `${currentTindakan.paramedis.nama_lengkap} - ${currentTindakan.paramedis.jabatan}` : '-'}
                  </div>
                </div>
                <div className="detail-item">
                  <label>Tarif</label>
                  <div className="detail-value">{formatCurrency(currentTindakan.tarif)}</div>
                </div>
                <div className="detail-item">
                  <label>Jasa Dokter</label>
                  <div className="detail-value">{formatCurrency(currentTindakan.jasa_dokter)}</div>
                </div>
                <div className="detail-item">
                  <label>Jasa Paramedis</label>
                  <div className="detail-value">{formatCurrency(currentTindakan.jasa_paramedis)}</div>
                </div>
                <div className="detail-item">
                  <label>Jasa Non-Paramedis</label>
                  <div className="detail-value">{formatCurrency(currentTindakan.jasa_non_paramedis)}</div>
                </div>
                <div className="detail-item">
                  <label>Status</label>
                  <div className="detail-value">
                    <span className={`status-badge ${currentTindakan.status}`}>
                      {currentTindakan.status === 'pending' ? 'Menunggu' : 
                       currentTindakan.status === 'selesai' ? 'Selesai' : 'Batal'}
                    </span>
                  </div>
                </div>
                <div className="detail-item">
                  <label>Status Validasi</label>
                  <div className="detail-value">
                    <span className={`validation-badge ${currentTindakan.status_validasi}`}>
                      {currentTindakan.status_validasi === 'pending' ? 'Menunggu Validasi' :
                       currentTindakan.status_validasi === 'approved' ? 'Disetujui' : 'Ditolak'}
                    </span>
                  </div>
                </div>
                {currentTindakan.catatan && (
                  <div className="detail-item full-width">
                    <label>Catatan</label>
                    <div className="detail-value">{currentTindakan.catatan}</div>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      )}

      <style jsx>{`
        .tindakan-crud {
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

        .status-badge.selesai {
          background: #dcfce7;
          color: #166534;
        }

        .status-badge.batal {
          background: #fee2e2;
          color: #dc2626;
        }

        .validation-badge {
          padding: 4px 12px;
          border-radius: 16px;
          font-size: 12px;
          font-weight: 600;
          text-transform: uppercase;
        }

        .validation-badge.pending {
          background: #fef3c7;
          color: #92400e;
        }

        .validation-badge.approved {
          background: #dcfce7;
          color: #166534;
        }

        .validation-badge.rejected {
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

        .modal-content.large {
          max-width: 900px;
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

        .tindakan-details {
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
          .tindakan-crud {
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

export default TindakanCRUD;