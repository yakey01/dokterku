@php
    $data = $this->getData();
    $user = $data['user'];
@endphp

<div class="fi-widget fi-sidebar-profile-widget p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
    {{-- Profile Header --}}
    <div class="flex items-center gap-3 mb-4">
        @if($data['avatar'])
            <img 
                src="{{ $data['avatar'] }}" 
                alt="{{ $data['name'] }}" 
                class="w-12 h-12 rounded-full border-2 border-gray-200 dark:border-gray-600"
            >
        @else
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center border-2 border-blue-200 dark:border-blue-700">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                </svg>
            </div>
        @endif
        
        <div class="flex-1 min-w-0">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                {{ $data['name'] }}
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                {{ $data['email'] }}
            </p>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 mt-1">
                {{ ucfirst($data['role']) }}
            </span>
        </div>
    </div>
    
    {{-- Quick Stats --}}
    <div class="grid grid-cols-2 gap-2 mb-4">
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-2 text-center">
            <div class="text-lg font-bold text-green-600 dark:text-green-400">847</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Logins</div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-2 text-center">
            <div class="text-lg font-bold text-blue-600 dark:text-blue-400">95%</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Security</div>
        </div>
    </div>
    
    {{-- Quick Actions --}}
    <div class="space-y-2">
        <a 
            href="{{ \App\Filament\Resources\AdminProfileResource::getUrl('edit', ['record' => Auth::id()]) }}"
            class="flex items-center gap-2 w-full p-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Edit Profile
        </a>
        
        <a 
            href="{{ \App\Filament\Resources\AdminSecurityResource::getUrl('index') }}"
            class="flex items-center gap-2 w-full p-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            Security & Sessions
        </a>
        
        <form action="{{ filament()->getLogoutUrl() }}" method="post" class="w-full">
            @csrf
            <button 
                type="submit"
                class="flex items-center gap-2 w-full p-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Logout
            </button>
        </form>
    </div>
    
    {{-- Last Login Info --}}
    <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-600">
        <div class="text-xs text-gray-500 dark:text-gray-400">
            Last login: {{ $data['last_login']->diffForHumans() }}
        </div>
    </div>
</div>