import React, { useState, useEffect } from 'react';

const PetugasWorkerDashboard: React.FC = () => {
  const [currentTime, setCurrentTime] = useState(new Date());

  useEffect(() => {
    console.log('🎯 PetugasWorkerDashboard mounted successfully!');
    
    const timer = setInterval(() => {
      setCurrentTime(new Date());
    }, 1000);

    return () => clearInterval(timer);
  }, []);

  console.log('🔄 PetugasWorkerDashboard rendering...');

  // Inline styles untuk memastikan tidak ada masalah CSS
  const styles = {
    dashboard: {
      minHeight: '100vh',
      background: '#0f0f23',
      color: 'white',
      fontFamily: 'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif',
      padding: 0,
      margin: 0,
    },
    header: {
      display: 'flex',
      justifyContent: 'space-between',
      alignItems: 'center',
      padding: '24px 32px',
      borderBottom: '1px solid rgba(255, 255, 255, 0.1)',
      background: '#1a1a2e',
    },
    title: {
      display: 'flex',
      alignItems: 'center',
      gap: '16px',
    },
    titleIcon: {
      width: '48px',
      height: '48px',
      background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
      borderRadius: '12px',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
    },
    h1: {
      fontSize: '28px',
      fontWeight: '700',
      margin: 0,
      color: '#ffffff',
    },
    controls: {
      display: 'flex',
      gap: '8px',
    },
    button: {
      padding: '8px 16px',
      background: 'transparent',
      border: '1px solid rgba(255, 255, 255, 0.2)',
      color: '#ffffff',
      borderRadius: '8px',
      fontSize: '14px',
      cursor: 'pointer',
      transition: 'all 0.2s ease',
    },
    activeButton: {
      background: '#667eea',
      borderColor: '#667eea',
    },
    statsGrid: {
      display: 'grid',
      gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))',
      gap: '16px',
      padding: '32px',
      maxWidth: '1400px',
      margin: '0 auto',
    },
    statCard: {
      background: '#1a1a2e',
      borderRadius: '12px',
      padding: '20px',
      border: '1px solid rgba(255, 255, 255, 0.1)',
      textAlign: 'center' as const,
    },
    statValue: {
      fontSize: '24px',
      fontWeight: '700',
      color: '#ffffff',
      marginBottom: '4px',
    },
    statLabel: {
      fontSize: '12px',
      color: '#a1a1aa',
      textTransform: 'uppercase' as const,
      letterSpacing: '0.5px',
    },
    kanbanBoard: {
      display: 'grid',
      gridTemplateColumns: 'repeat(auto-fit, minmax(320px, 1fr))',
      gap: '24px',
      padding: '32px',
      maxWidth: '1400px',
      margin: '0 auto',
    },
    column: {
      background: '#1a1a2e',
      borderRadius: '16px',
      padding: '20px',
      border: '1px solid rgba(255, 255, 255, 0.1)',
    },
    columnHeader: {
      display: 'flex',
      justifyContent: 'space-between',
      alignItems: 'center',
      marginBottom: '20px',
      paddingBottom: '12px',
      borderBottom: '1px solid rgba(255, 255, 255, 0.1)',
    },
    columnTitle: {
      fontSize: '16px',
      fontWeight: '600',
      color: '#ffffff',
    },
    taskCount: {
      background: 'rgba(255, 255, 255, 0.1)',
      color: '#ffffff',
      padding: '4px 8px',
      borderRadius: '12px',
      fontSize: '12px',
      fontWeight: '500',
    },
    taskCard: {
      background: '#232342',
      borderRadius: '12px',
      padding: '16px',
      border: '1px solid rgba(255, 255, 255, 0.08)',
      marginBottom: '16px',
      transition: 'all 0.2s ease',
      cursor: 'pointer',
    },
    taskHeader: {
      display: 'flex',
      justifyContent: 'space-between',
      alignItems: 'flex-start',
      marginBottom: '12px',
    },
    taskTitle: {
      fontSize: '14px',
      fontWeight: '600',
      color: '#ffffff',
      margin: 0,
      lineHeight: '1.4',
    },
    priorityBadge: {
      padding: '4px 8px',
      borderRadius: '6px',
      fontSize: '11px',
      fontWeight: '500',
      textTransform: 'uppercase' as const,
      letterSpacing: '0.5px',
    },
    priorityHigh: {
      background: 'rgba(239, 68, 68, 0.2)',
      color: '#fca5a5',
      border: '1px solid rgba(239, 68, 68, 0.3)',
    },
    priorityMedium: {
      background: 'rgba(245, 158, 11, 0.2)',
      color: '#fbbf24',
      border: '1px solid rgba(245, 158, 11, 0.3)',
    },
    priorityLow: {
      background: 'rgba(16, 185, 129, 0.2)',
      color: '#6ee7b7',
      border: '1px solid rgba(16, 185, 129, 0.3)',
    },
    taskDescription: {
      fontSize: '12px',
      color: '#a1a1aa',
      marginBottom: '12px',
      lineHeight: '1.5',
    },
    progressBar: {
      width: '100%',
      height: '6px',
      background: 'rgba(255, 255, 255, 0.1)',
      borderRadius: '3px',
      overflow: 'hidden',
      marginBottom: '4px',
    },
    progressFill: {
      height: '100%',
      background: 'linear-gradient(90deg, #667eea 0%, #764ba2 100%)',
      borderRadius: '3px',
      transition: 'width 0.3s ease',
    },
    progressText: {
      fontSize: '11px',
      color: '#a1a1aa',
      marginBottom: '12px',
    },
    taskFooter: {
      display: 'flex',
      justifyContent: 'space-between',
      alignItems: 'center',
    },
    taskAssignee: {
      display: 'flex',
      alignItems: 'center',
      gap: '8px',
    },
    avatar: {
      width: '24px',
      height: '24px',
      background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
      borderRadius: '50%',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      fontSize: '10px',
      fontWeight: '600',
      color: 'white',
    },
    assigneeName: {
      fontSize: '11px',
      color: '#a1a1aa',
    },
    taskDate: {
      fontSize: '11px',
      color: '#a1a1aa',
    },
  };

  return (
    <div style={styles.dashboard}>
      {/* Header */}
      <div style={styles.header}>
        <div style={styles.title}>
          <div style={styles.titleIcon}>
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <rect x="3" y="3" width="7" height="9" rx="1"/>
              <rect x="14" y="3" width="7" height="5" rx="1"/>
              <rect x="14" y="12" width="7" height="9" rx="1"/>
              <rect x="3" y="16" width="7" height="5" rx="1"/>
            </svg>
          </div>
          <div>
            <h1 style={styles.h1}>Petugas Dashboard</h1>
            <div style={{ fontSize: '14px', color: '#a1a1aa', marginTop: '4px' }}>
              {currentTime.toLocaleDateString('id-ID', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
              })} • {currentTime.toLocaleTimeString('id-ID', { 
                hour: '2-digit', 
                minute: '2-digit' 
              })}
            </div>
          </div>
        </div>
        <div style={styles.controls}>
          <button style={{...styles.button, ...styles.activeButton}}>Kanban</button>
          <button style={styles.button}>List</button>
        </div>
      </div>

      {/* Stats Overview */}
      <div style={styles.statsGrid}>
        <div style={styles.statCard}>
          <div style={styles.statValue}>24</div>
          <div style={styles.statLabel}>Total Tasks</div>
        </div>
        <div style={styles.statCard}>
          <div style={styles.statValue}>8</div>
          <div style={styles.statLabel}>In Progress</div>
        </div>
        <div style={styles.statCard}>
          <div style={styles.statValue}>12</div>
          <div style={styles.statLabel}>Completed</div>
        </div>
        <div style={styles.statCard}>
          <div style={styles.statValue}>4</div>
          <div style={styles.statLabel}>Pending</div>
        </div>
      </div>

      {/* Kanban Board */}
      <div style={styles.kanbanBoard}>
        {/* To Do Column */}
        <div style={styles.column}>
          <div style={styles.columnHeader}>
            <span style={styles.columnTitle}>📝 To Do</span>
            <span style={styles.taskCount}>2</span>
          </div>
          
          <div style={styles.taskCard}>
            <div style={styles.taskHeader}>
              <h3 style={styles.taskTitle}>Input Data Pasien Baru</h3>
              <span style={{...styles.priorityBadge, ...styles.priorityHigh}}>High</span>
            </div>
            <p style={styles.taskDescription}>
              Menginput data pasien baru yang datang untuk pemeriksaan rutin
            </p>
            <div style={styles.progressBar}>
              <div style={{...styles.progressFill, width: '0%'}}></div>
            </div>
            <div style={styles.progressText}>0% Complete</div>
            <div style={styles.taskFooter}>
              <div style={styles.taskAssignee}>
                <div style={styles.avatar}>AN</div>
                <span style={styles.assigneeName}>Ani Petugas</span>
              </div>
              <span style={styles.taskDate}>Aug 4</span>
            </div>
          </div>

          <div style={styles.taskCard}>
            <div style={styles.taskHeader}>
              <h3 style={styles.taskTitle}>Verifikasi Dokumen</h3>
              <span style={{...styles.priorityBadge, ...styles.priorityMedium}}>Medium</span>
            </div>
            <p style={styles.taskDescription}>
              Memverifikasi kelengkapan dokumen pasien sebelum tindakan
            </p>
            <div style={styles.progressBar}>
              <div style={{...styles.progressFill, width: '25%'}}></div>
            </div>
            <div style={styles.progressText}>25% Complete</div>
            <div style={styles.taskFooter}>
              <div style={styles.taskAssignee}>
                <div style={styles.avatar}>BS</div>
                <span style={styles.assigneeName}>Budi Staff</span>
              </div>
              <span style={styles.taskDate}>Aug 5</span>
            </div>
          </div>
        </div>

        {/* In Progress Column */}
        <div style={styles.column}>
          <div style={styles.columnHeader}>
            <span style={styles.columnTitle}>⚡ In Progress</span>
            <span style={styles.taskCount}>2</span>
          </div>
          
          <div style={styles.taskCard}>
            <div style={styles.taskHeader}>
              <h3 style={styles.taskTitle}>Koordinasi Jadwal Dokter</h3>
              <span style={{...styles.priorityBadge, ...styles.priorityHigh}}>High</span>
            </div>
            <p style={styles.taskDescription}>
              Mengatur jadwal praktek dokter dan koordinasi dengan pasien
            </p>
            <div style={styles.progressBar}>
              <div style={{...styles.progressFill, width: '60%'}}></div>
            </div>
            <div style={styles.progressText}>60% Complete</div>
            <div style={styles.taskFooter}>
              <div style={styles.taskAssignee}>
                <div style={styles.avatar}>CS</div>
                <span style={styles.assigneeName}>Citra Sari</span>
              </div>
              <span style={styles.taskDate}>Aug 4</span>
            </div>
          </div>

          <div style={styles.taskCard}>
            <div style={styles.taskHeader}>
              <h3 style={styles.taskTitle}>Update Database Tindakan</h3>
              <span style={{...styles.priorityBadge, ...styles.priorityMedium}}>Medium</span>
            </div>
            <p style={styles.taskDescription}>
              Memperbarui database dengan tindakan medis yang telah dilakukan
            </p>
            <div style={styles.progressBar}>
              <div style={{...styles.progressFill, width: '80%'}}></div>
            </div>
            <div style={styles.progressText}>80% Complete</div>
            <div style={styles.taskFooter}>
              <div style={styles.taskAssignee}>
                <div style={styles.avatar}>DS</div>
                <span style={styles.assigneeName}>Deni Susanto</span>
              </div>
              <span style={styles.taskDate}>Aug 3</span>
            </div>
          </div>
        </div>

        {/* Done Column */}
        <div style={styles.column}>
          <div style={styles.columnHeader}>
            <span style={styles.columnTitle}>✅ Done</span>
            <span style={styles.taskCount}>2</span>
          </div>
          
          <div style={styles.taskCard}>
            <div style={styles.taskHeader}>
              <h3 style={styles.taskTitle}>Laporan Harian Pasien</h3>
              <span style={{...styles.priorityBadge, ...styles.priorityLow}}>Low</span>
            </div>
            <p style={styles.taskDescription}>
              Menyusun laporan harian jumlah pasien dan tindakan
            </p>
            <div style={styles.progressBar}>
              <div style={{...styles.progressFill, width: '100%'}}></div>
            </div>
            <div style={styles.progressText}>100% Complete</div>
            <div style={styles.taskFooter}>
              <div style={styles.taskAssignee}>
                <div style={styles.avatar}>EP</div>
                <span style={styles.assigneeName}>Eka Putri</span>
              </div>
              <span style={styles.taskDate}>Aug 2</span>
            </div>
          </div>

          <div style={styles.taskCard}>
            <div style={styles.taskHeader}>
              <h3 style={styles.taskTitle}>Arsip Dokumen Medis</h3>
              <span style={{...styles.priorityBadge, ...styles.priorityLow}}>Low</span>
            </div>
            <p style={styles.taskDescription}>
              Mengarsipkan dokumen medis pasien ke sistem digital
            </p>
            <div style={styles.progressBar}>
              <div style={{...styles.progressFill, width: '100%'}}></div>
            </div>
            <div style={styles.progressText}>100% Complete</div>
            <div style={styles.taskFooter}>
              <div style={styles.taskAssignee}>
                <div style={styles.avatar}>FH</div>
                <span style={styles.assigneeName}>Fajar Hidayat</span>
              </div>
              <span style={styles.taskDate}>Aug 1</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default PetugasWorkerDashboard;