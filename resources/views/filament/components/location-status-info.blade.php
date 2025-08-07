<div class="location-status-info space-y-6">
    <!-- Status Header -->
    <div class="status-header {{ $color }} rounded-lg p-4 border">
        <div class="flex items-center space-x-3">
            <span class="text-2xl">{{ $icon }}</span>
            <div>
                <h3 class="font-semibold text-lg">{{ ucfirst($status) }} Location</h3>
                <p class="text-sm opacity-90">{{ $record->name }}</p>
            </div>
        </div>
    </div>

    <!-- Location Details -->
    <div class="location-details bg-gray-50 rounded-lg p-4 border">
        <h4 class="font-semibold text-gray-800 mb-3">📍 Location Information</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <span class="font-medium text-gray-700">Type:</span>
                <span class="ml-2">{{ $record->location_type_label }}</span>
            </div>
            @if($record->unit_kerja)
            <div>
                <span class="font-medium text-gray-700">Unit:</span>
                <span class="ml-2">{{ $record->unit_kerja }}</span>
            </div>
            @endif
            <div>
                <span class="font-medium text-gray-700">Coordinates:</span>
                <span class="ml-2 font-mono text-xs">{{ number_format($record->latitude, 6) }}, {{ number_format($record->longitude, 6) }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-700">Radius:</span>
                <span class="ml-2">{{ $record->formatted_radius }}</span>
            </div>
            @if($record->created_at)
            <div>
                <span class="font-medium text-gray-700">Created:</span>
                <span class="ml-2">{{ $record->created_at->format('M j, Y') }}</span>
            </div>
            @endif
            @if($record->deleted_at)
            <div>
                <span class="font-medium text-gray-700 text-red-600">Deleted:</span>
                <span class="ml-2 text-red-600">{{ $record->deleted_at->format('M j, Y g:i A') }}</span>
            </div>
            @endif
        </div>
    </div>

    <!-- Status Explanation -->
    <div class="status-explanation bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start space-x-2">
            <span class="text-blue-600 mt-0.5">ℹ️</span>
            <div>
                <h4 class="font-semibold text-blue-800 mb-2">Status Explanation</h4>
                <p class="text-blue-700 text-sm">{{ $statusText }}</p>
            </div>
        </div>
    </div>

    <!-- Available Actions -->
    <div class="available-actions bg-green-50 border border-green-200 rounded-lg p-4">
        <div class="flex items-start space-x-2">
            <span class="text-green-600 mt-0.5">⚡</span>
            <div>
                <h4 class="font-semibold text-green-800 mb-2">Available Actions</h4>
                <p class="text-green-700 text-sm">{{ $actions }}</p>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    @php
        $assignedUsers = $record->users()->count();
        $attendanceRecords = $record->attendances()->count();
    @endphp
    
    @if($assignedUsers > 0 || $attendanceRecords > 0)
    <div class="location-statistics bg-gray-50 border border-gray-200 rounded-lg p-4">
        <h4 class="font-semibold text-gray-800 mb-3">📊 Usage Statistics</h4>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $assignedUsers }}</div>
                <div class="text-gray-600">Assigned Users</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-600">{{ $attendanceRecords }}</div>
                <div class="text-gray-600">Attendance Records</div>
            </div>
        </div>
        
        @if($status === 'deleted' && ($assignedUsers > 0 || $attendanceRecords > 0))
        <div class="mt-3 p-2 bg-yellow-100 rounded text-xs text-yellow-800">
            <strong>💡 Note:</strong> Historical data is preserved even for deleted locations to maintain data integrity.
        </div>
        @endif
    </div>
    @endif

    <!-- Troubleshooting Tips -->
    @if($status === 'deleted')
    <div class="troubleshooting-tips bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex items-start space-x-2">
            <span class="text-yellow-600 mt-0.5">🔧</span>
            <div>
                <h4 class="font-semibold text-yellow-800 mb-2">Troubleshooting</h4>
                <div class="text-yellow-700 text-sm space-y-1">
                    <p><strong>Issue:</strong> Seeing 404 errors when trying to toggle status?</p>
                    <p><strong>Solution:</strong> Deleted locations cannot be toggled. Use restore action first.</p>
                    <p><strong>Prevention:</strong> The toggle column is now disabled for deleted records to prevent this error.</p>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>