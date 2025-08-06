<div class="space-y-6">
    <!-- Work Location Info -->
    <div class="bg-gray-50 rounded-lg p-4 border">
        <h3 class="text-lg font-semibold text-gray-900 mb-3">📍 Work Location Details</h3>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="font-medium text-gray-600">Name:</span>
                <span class="ml-2">{{ $preview['work_location']['name'] }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-600">Type:</span>
                <span class="ml-2">{{ ucfirst(str_replace('_', ' ', $preview['work_location']['location_type'])) }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-600">Unit Kerja:</span>
                <span class="ml-2">{{ $preview['work_location']['unit_kerja'] ?? 'Not Set' }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-600">Status:</span>
                <span class="ml-2">
                    @if($preview['work_location']['is_active'])
                        <span class="text-green-600 font-medium">✅ Active</span>
                    @else
                        <span class="text-red-600 font-medium">❌ Inactive</span>
                    @endif
                </span>
            </div>
        </div>
    </div>

    <!-- Dependencies Analysis -->
    <div class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900">🔍 Dependency Analysis</h3>
        
        @if($preview['dependencies']['can_delete'])
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center mb-2">
                    <span class="text-green-600 text-xl mr-2">✅</span>
                    <span class="text-green-800 font-semibold">Safe to Delete</span>
                </div>
                <p class="text-green-700 text-sm">No blocking dependencies found. This location can be safely deleted.</p>
            </div>
        @else
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-center mb-2">
                    <span class="text-red-600 text-xl mr-2">⛔</span>
                    <span class="text-red-800 font-semibold">Cannot Delete</span>
                </div>
                <p class="text-red-700 text-sm mb-3">This location has blocking dependencies that prevent deletion:</p>
                <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                    @foreach($preview['dependencies']['blocking_dependencies'] as $dependency)
                        <li>{{ $dependency }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(!empty($preview['dependencies']['warnings']))
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-center mb-2">
                    <span class="text-yellow-600 text-xl mr-2">⚠️</span>
                    <span class="text-yellow-800 font-semibold">Warnings</span>
                </div>
                <ul class="list-disc list-inside text-sm text-yellow-700 space-y-1">
                    @foreach($preview['dependencies']['warnings'] as $warning)
                        <li>{{ $warning }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <!-- Dependency Details -->
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
            <h4 class="font-semibold text-blue-900 mb-3">👥 Assigned Users</h4>
            <div class="text-2xl font-bold text-blue-600 mb-1">{{ $preview['dependencies']['assigned_users_count'] }}</div>
            <p class="text-sm text-blue-700">
                @if($preview['dependencies']['assigned_users_count'] > 0)
                    Users currently assigned to this location
                @else
                    No users currently assigned
                @endif
            </p>
            
            @if($preview['dependencies']['assigned_users_count'] > 0)
                <div class="mt-3 space-y-2">
                    @foreach($preview['dependencies']['assigned_users'] as $user)
                        <div class="text-xs bg-blue-100 rounded p-2">
                            <div class="font-medium">{{ $user['name'] }}</div>
                            <div class="text-blue-600">{{ $user['role'] }} • {{ $user['email'] }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
            <h4 class="font-semibold text-purple-900 mb-3">📋 Historical Data</h4>
            <div class="space-y-3">
                <div>
                    <div class="text-lg font-bold text-purple-600">{{ $preview['dependencies']['assignment_histories_count'] }}</div>
                    <p class="text-sm text-purple-700">Assignment histories</p>
                </div>
                <div>
                    <div class="text-lg font-bold text-purple-600">{{ $preview['dependencies']['attendances_count'] }}</div>
                    <p class="text-sm text-purple-700">Attendance records</p>
                </div>
                <div>
                    <div class="text-lg font-bold text-purple-600">{{ $preview['dependencies']['location_validations_count'] }}</div>
                    <p class="text-sm text-purple-700">Location validations</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recommendations -->
    @if(!empty($preview['recommendations']))
        <div class="space-y-3">
            <h3 class="text-lg font-semibold text-gray-900">💡 Recommendations</h3>
            @foreach($preview['recommendations'] as $recommendation)
                <div class="flex items-start space-x-3 p-3 rounded-lg border
                    @if($recommendation['type'] === 'error') bg-red-50 border-red-200
                    @elseif($recommendation['type'] === 'warning') bg-yellow-50 border-yellow-200
                    @else bg-green-50 border-green-200 @endif">
                    <span class="text-lg mt-0.5">
                        @if($recommendation['type'] === 'error') ❌
                        @elseif($recommendation['type'] === 'warning') ⚠️
                        @else ✅ @endif
                    </span>
                    <div>
                        <p class="text-sm font-medium
                            @if($recommendation['type'] === 'error') text-red-800
                            @elseif($recommendation['type'] === 'warning') text-yellow-800
                            @else text-green-800 @endif">
                            {{ $recommendation['message'] }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Alternative Locations -->
    @if(!empty($preview['alternative_locations']) && $preview['dependencies']['assigned_users_count'] > 0)
        <div class="space-y-3">
            <h3 class="text-lg font-semibold text-gray-900">🔄 Alternative Locations for User Reassignment</h3>
            <div class="space-y-2">
                @foreach($preview['alternative_locations'] as $location)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2">
                                <span class="font-medium text-gray-900">{{ $location['name'] }}</span>
                                @if($location['same_unit_kerja'])
                                    <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Same Unit</span>
                                @endif
                            </div>
                            <div class="text-sm text-gray-600 mt-1">
                                {{ ucfirst(str_replace('_', ' ', $location['location_type'])) }} • 
                                {{ $location['unit_kerja'] }} • 
                                {{ $location['current_users'] }} users ({{ $location['utilization_percentage'] }}%)
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="text-right">
                                <div class="text-sm font-medium text-gray-900">Score: {{ $location['recommendation_score'] }}</div>
                                <div class="text-xs text-gray-500">
                                    @if($location['capacity_status'] === 'optimal') 
                                        <span class="text-green-600">✅ Optimal</span>
                                    @elseif($location['capacity_status'] === 'low_utilization') 
                                        <span class="text-blue-600">📉 Low Usage</span>
                                    @elseif($location['capacity_status'] === 'high_utilization') 
                                        <span class="text-yellow-600">📈 High Usage</span>
                                    @else 
                                        <span class="text-red-600">🔴 Over Capacity</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="text-xs text-gray-500 mt-2">
                * Users will be automatically assigned to the highest-scoring alternative location
            </p>
        </div>
    @endif

    <!-- Impact Assessment -->
    <div class="bg-gray-50 rounded-lg p-4 border">
        <h3 class="text-lg font-semibold text-gray-900 mb-3">📊 Impact Assessment</h3>
        <div class="grid grid-cols-3 gap-4 text-center">
            <div class="p-3 bg-white rounded border">
                <div class="text-lg font-bold 
                    @if($preview['estimated_impact']['severity'] === 'low') text-green-600
                    @elseif($preview['estimated_impact']['severity'] === 'medium') text-yellow-600
                    @elseif($preview['estimated_impact']['severity'] === 'high') text-orange-600
                    @else text-red-600 @endif">
                    {{ ucfirst($preview['estimated_impact']['severity']) }}
                </div>
                <div class="text-sm text-gray-600">Impact Severity</div>
            </div>
            <div class="p-3 bg-white rounded border">
                <div class="text-lg font-bold text-blue-600">{{ $preview['estimated_impact']['users_affected'] }}</div>
                <div class="text-sm text-gray-600">Users Affected</div>
            </div>
            <div class="p-3 bg-white rounded border">
                <div class="text-lg font-bold text-purple-600">{{ $preview['estimated_impact']['data_preserved'] }}</div>
                <div class="text-sm text-gray-600">Records Preserved</div>
            </div>
        </div>
    </div>

    @if($preview['dependencies']['can_delete'])
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center mb-2">
                <span class="text-blue-600 text-xl mr-2">ℹ️</span>
                <span class="text-blue-800 font-semibold">Next Steps</span>
            </div>
            <p class="text-blue-700 text-sm">
                Close this preview and use the "Safe Delete" action to proceed with the deletion. 
                The system will automatically handle user reassignments and preserve historical data.
            </p>
        </div>
    @endif
</div>