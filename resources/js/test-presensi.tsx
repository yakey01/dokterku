import React from 'react';
import ReactDOM from 'react-dom/client';
import CreativeAttendanceDashboard from './components/dokter/Presensi';

const root = document.getElementById('presensi-test');

if (root) {
    ReactDOM.createRoot(root).render(
        <React.StrictMode>
            <CreativeAttendanceDashboard userData={{ name: 'Dr. Test User' }} />
        </React.StrictMode>
    );
}