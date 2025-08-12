{{-- Global Black Elegant Sidebar for ALL Petugas Pages --}}
<style>
    /* Force Black Elegant Sidebar on ALL Petugas Panel Pages */
    .fi-sidebar,
    .fi-sidebar-nav,
    aside[x-data],
    aside.fi-sidebar {
        background: #0f0f0f !important;
        background-image: linear-gradient(180deg, #0a0a0a 0%, #0f0f0f 100%) !important;
        border-right: 1px solid rgba(255, 255, 255, 0.08) !important;
        box-shadow: 
            4px 0 24px rgba(0, 0, 0, 0.4),
            inset -1px 0 0 rgba(255, 255, 255, 0.05) !important;
    }

    /* Sidebar Header - Minimalist Design */
    .fi-sidebar-header,
    .fi-sidebar header {
        background: #0f0f0f !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
        padding: 1.25rem !important;
    }

    .fi-sidebar-header h2,
    .fi-sidebar-header span,
    .fi-sidebar-header a,
    .fi-sidebar header h2,
    .fi-sidebar header span,
    .fi-sidebar header a,
    .fi-logo {
        color: #ffffff !important;
        font-weight: 600 !important;
        letter-spacing: 0.01em !important;
    }

    /* Navigation Groups - Minimalist Headers */
    .fi-sidebar-group,
    nav.fi-sidebar-nav > ul > li {
        border-top: 1px solid rgba(255, 255, 255, 0.05) !important;
        margin-top: 0.25rem !important;
        padding-top: 0.25rem !important;
    }

    .fi-sidebar-group-button,
    .fi-sidebar-group > button,
    .fi-sidebar-group-label {
        color: rgba(255, 255, 255, 0.7) !important;
        font-size: 0.75rem !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        padding: 0.5rem 0.75rem !important;
        background: transparent !important;
        cursor: default !important; /* Make non-clickable */
        pointer-events: none !important; /* Disable click events */
    }
    
    /* Hide collapsible arrows/chevrons */
    .fi-sidebar-group-button svg.fi-sidebar-group-trigger-icon,
    .fi-sidebar-group-button svg:last-child,
    .fi-sidebar-group > button > svg:last-child,
    .fi-sidebar-group-collapse-button {
        display: none !important;
    }

    /* Group Icons - Consistent with Items */
    .fi-sidebar-group-button svg,
    .fi-sidebar-group > button svg,
    .fi-sidebar-group-icon svg {
        color: rgba(255, 255, 255, 0.6) !important;
        width: 0.875rem !important;
        height: 0.875rem !important;
        margin-right: 0.5rem !important;
    }

    .fi-sidebar-group-button:hover,
    .fi-sidebar-group > button:hover {
        background: rgba(255, 255, 255, 0.05) !important;
        color: rgba(255, 255, 255, 0.9) !important;
    }

    .fi-sidebar-group-button:hover svg,
    .fi-sidebar-group > button:hover svg {
        color: rgba(255, 255, 255, 0.8) !important;
    }

    /* Navigation Items - Minimalist Design */
    .fi-sidebar-item a,
    .fi-sidebar-item button,
    .fi-sidebar-item-button,
    nav.fi-sidebar-nav a {
        color: rgba(255, 255, 255, 0.85) !important;
        font-size: 0.875rem !important;
        font-weight: 400 !important;
        padding: 0.5rem 0.75rem !important;
        margin: 0.0625rem 0.25rem !important;
        border-radius: 0.375rem !important;
        background: transparent !important;
        transition: all 0.2s ease !important;
    }

    /* Hover Effects - Subtle and Clean */
    .fi-sidebar-item a:hover,
    .fi-sidebar-item button:hover,
    .fi-sidebar-item-button:hover,
    nav.fi-sidebar-nav a:hover {
        background: rgba(255, 255, 255, 0.08) !important;
        color: #ffffff !important;
        transform: translateX(2px) !important;
    }

    /* Active State - Minimalist Indicator */
    .fi-sidebar-item-active a,
    .fi-sidebar-item-active button,
    .fi-active a,
    .fi-active button,
    nav.fi-sidebar-nav a[aria-current="page"] {
        background: rgba(59, 130, 246, 0.15) !important;
        color: #93c5fd !important;
        font-weight: 500 !important;
        border-left: 2px solid #3b82f6 !important;
        padding-left: calc(0.75rem - 2px) !important;
    }

    /* Icons - Minimalist Size */
    .fi-sidebar-item-icon,
    .fi-sidebar-item svg,
    nav.fi-sidebar-nav svg {
        color: rgba(255, 255, 255, 0.7) !important;
        width: 1rem !important;
        height: 1rem !important;
    }

    .fi-sidebar-item a:hover svg,
    .fi-sidebar-item button:hover svg,
    nav.fi-sidebar-nav a:hover svg {
        color: rgba(255, 255, 255, 0.9) !important;
    }

    .fi-sidebar-item-active svg,
    .fi-active svg,
    nav.fi-sidebar-nav a[aria-current="page"] svg {
        color: #93c5fd !important;
    }

    /* Badge and Count Indicators - Minimalist */
    .fi-sidebar-item-badge,
    .fi-sidebar-item .fi-badge {
        background: rgba(59, 130, 246, 0.2) !important;
        color: #93c5fd !important;
        font-weight: 500 !important;
        font-size: 0.75rem !important;
        padding: 0.125rem 0.375rem !important;
    }

    /* Scrollbar Styling - Minimalist */
    .fi-sidebar::-webkit-scrollbar,
    aside.fi-sidebar::-webkit-scrollbar {
        width: 4px !important;
    }

    .fi-sidebar::-webkit-scrollbar-track,
    aside.fi-sidebar::-webkit-scrollbar-track {
        background: transparent !important;
    }

    .fi-sidebar::-webkit-scrollbar-thumb,
    aside.fi-sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.1) !important;
        border-radius: 2px !important;
    }

    .fi-sidebar::-webkit-scrollbar-thumb:hover,
    aside.fi-sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.15) !important;
    }

    /* Specific Menu Group Styles - Removed for cleaner look */

    /* Ensure all text is white and readable */
    .fi-sidebar * {
        color: rgba(255, 255, 255, 0.95) !important;
    }

    /* Fix any potential light mode remnants */
    .fi-sidebar .text-gray-700,
    .fi-sidebar .text-gray-600,
    .fi-sidebar .text-gray-500,
    .fi-sidebar .text-gray-400 {
        color: rgba(255, 255, 255, 0.9) !important;
    }

    /* Dashboard specific - ensure white background */
    .fi-main,
    .fi-page,
    main.fi-main {
        background: #ffffff !important;
    }

    /* GLOBAL BLACK TEXT - Maximum Readability */
    .fi-main *,
    .fi-page *,
    main.fi-main *,
    .fi-body *,
    .fi-content * {
        color: #000000 !important;
    }

    /* Exceptions for specific elements that should keep their colors */
    .fi-sidebar *,
    .fi-btn-primary,
    .fi-btn-primary *,
    .fi-tab-button[aria-selected="true"],
    .fi-tab-button[aria-selected="true"] *,
    .fi-badge-color-primary,
    .fi-badge-color-success,
    .fi-breadcrumb-item a,
    .fi-notification,
    .fi-alert {
        color: revert !important;
    }

    /* Top bar white */
    .fi-topbar {
        background: #ffffff !important;
        border-bottom: 1px solid #e5e7eb !important;
    }

    /* Remove any dark mode classes */
    .dark .fi-sidebar {
        background: #0f0f0f !important;
    }

    /* Force override any theme switcher */
    .fi-theme-switcher {
        display: none !important;
    }

    /* =================================== */
    /* READABILITY IMPROVEMENTS FOR TABLES */
    /* =================================== */

    /* Table Text - Increase font size for better readability */
    .fi-table-cell,
    .fi-table-content .fi-ta-text,
    .fi-table td {
        font-size: 0.875rem !important;
        line-height: 1.5 !important;
        color: #000000 !important;
    }

    /* Table Headers - Improve contrast and readability */
    .fi-table-header-cell,
    .fi-table-header .fi-ta-text,
    .fi-table th {
        font-weight: 600 !important;
        font-size: 0.875rem !important;
        color: #000000 !important;
        background: #f9fafb !important;
        border-bottom: 2px solid #e5e7eb !important;
        padding: 0.75rem !important;
    }

    /* Table Rows - Better spacing and hover effects */
    .fi-table-row,
    .fi-table tbody tr {
        border-bottom: 1px solid #f3f4f6 !important;
    }

    .fi-table-row:hover,
    .fi-table tbody tr:hover {
        background: #f9fafb !important;
        transition: background-color 0.15s ease !important;
    }

    /* Table Cells - Consistent padding */
    .fi-table-cell,
    .fi-table td {
        padding: 0.75rem !important;
        vertical-align: middle !important;
    }

    /* Search Input - Larger and more readable */
    .fi-search-input,
    .fi-table-search-field input,
    .fi-search .fi-input input {
        font-size: 0.875rem !important;
        padding: 0.5rem 0.75rem !important;
        border-radius: 0.5rem !important;
        border: 2px solid #e5e7eb !important;
        background: #ffffff !important;
    }

    .fi-search-input:focus,
    .fi-table-search-field input:focus,
    .fi-search .fi-input input:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        outline: none !important;
    }

    /* Buttons - Improve size and readability */
    .fi-btn,
    .fi-button,
    .fi-table-actions .fi-btn {
        font-size: 0.875rem !important;
        padding: 0.5rem 1rem !important;
        font-weight: 500 !important;
        border-radius: 0.5rem !important;
        line-height: 1.4 !important;
    }

    .fi-btn-primary {
        background: #3b82f6 !important;
        color: #ffffff !important;
    }

    .fi-btn-primary:hover {
        background: #2563eb !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3) !important;
    }

    /* Action Buttons in Tables */
    .fi-table-actions .fi-btn {
        padding: 0.375rem 0.75rem !important;
        font-size: 0.8125rem !important;
        min-height: 2rem !important;
    }

    /* Pagination - Larger controls */
    .fi-pagination-item,
    .fi-pagination .fi-btn {
        font-size: 0.875rem !important;
        min-width: 2.5rem !important;
        min-height: 2.5rem !important;
        padding: 0.5rem !important;
        border-radius: 0.375rem !important;
    }

    .fi-pagination-item:hover,
    .fi-pagination .fi-btn:hover {
        background: #f3f4f6 !important;
        color: #374151 !important;
    }

    /* Form Inputs - Better readability */
    .fi-input,
    .fi-select,
    .fi-textarea {
        font-size: 0.875rem !important;
        padding: 0.625rem 0.75rem !important;
        line-height: 1.5 !important;
        border-radius: 0.5rem !important;
        border: 2px solid #e5e7eb !important;
    }

    .fi-input:focus,
    .fi-select:focus,
    .fi-textarea:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        outline: none !important;
    }

    /* Labels - Better contrast */
    .fi-label,
    .fi-form-field-label,
    .fi-field-label {
        font-weight: 600 !important;
        color: #000000 !important;
        font-size: 0.875rem !important;
        margin-bottom: 0.375rem !important;
    }

    /* Cards - Better spacing and contrast */
    .fi-card,
    .fi-widget {
        background: #ffffff !important;
        border: 1px solid #e5e7eb !important;
        border-radius: 0.75rem !important;
        padding: 1.5rem !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
    }

    .fi-card:hover,
    .fi-widget:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
        transition: box-shadow 0.2s ease !important;
    }

    /* Page Headers - Improve readability */
    .fi-page-heading,
    .fi-header-heading {
        font-size: 1.5rem !important;
        font-weight: 700 !important;
        color: #000000 !important;
        line-height: 1.3 !important;
    }

    /* Breadcrumbs - Better visibility */
    .fi-breadcrumbs,
    .fi-breadcrumb {
        font-size: 0.875rem !important;
        color: #6b7280 !important;
    }

    .fi-breadcrumb-item a {
        color: #3b82f6 !important;
        text-decoration: none !important;
    }

    .fi-breadcrumb-item a:hover {
        color: #2563eb !important;
        text-decoration: underline !important;
    }

    /* Stats Widgets - Better readability */
    .fi-stats-card .fi-stats-card-value {
        font-size: 1.875rem !important;
        font-weight: 700 !important;
        color: #000000 !important;
    }

    .fi-stats-card .fi-stats-card-label {
        font-size: 0.875rem !important;
        color: #6b7280 !important;
        font-weight: 500 !important;
    }

    /* Modal Improvements */
    .fi-modal .fi-modal-content {
        font-size: 0.875rem !important;
        line-height: 1.6 !important;
        color: #000000 !important;
    }

    .fi-modal-heading {
        font-size: 1.25rem !important;
        font-weight: 600 !important;
        color: #000000 !important;
    }

    /* Notification Improvements */
    .fi-notification {
        font-size: 0.875rem !important;
        line-height: 1.5 !important;
        border-radius: 0.5rem !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
    }

    /* Loading States - Better visibility */
    .fi-loading {
        font-size: 0.875rem !important;
        color: #6b7280 !important;
    }

    /* ======================================= */
    /* ADVANCED SEARCH & FILTER IMPROVEMENTS  */
    /* ======================================= */

    /* Search Tabs - Advanced Search, Saved Search, Import Data */
    .fi-table-search-tabs,
    .fi-tabs,
    .fi-tab-item {
        font-size: 0.875rem !important;
        font-weight: 500 !important;
    }

    .fi-tab-button,
    .fi-tabs-item button {
        font-size: 0.875rem !important;
        padding: 0.625rem 1rem !important;
        color: #000000 !important;
        border-radius: 0.5rem !important;
        font-weight: 500 !important;
        transition: all 0.2s ease !important;
    }

    .fi-tab-button:hover,
    .fi-tabs-item button:hover {
        background: #f3f4f6 !important;
        color: #000000 !important;
    }

    .fi-tab-button[aria-selected="true"],
    .fi-tabs-item button[aria-selected="true"] {
        background: #3b82f6 !important;
        color: #ffffff !important;
        font-weight: 600 !important;
    }

    /* Advanced Search Icon Buttons */
    .fi-icon-btn,
    .fi-icon-button {
        font-size: 0.875rem !important;
        padding: 0.5rem !important;
        min-width: 2.25rem !important;
        min-height: 2.25rem !important;
        border-radius: 0.5rem !important;
    }

    .fi-icon-btn svg,
    .fi-icon-button svg {
        width: 1.125rem !important;
        height: 1.125rem !important;
    }

    /* Search Toggles and Dropdowns */
    .fi-dropdown,
    .fi-dropdown-panel {
        font-size: 0.875rem !important;
        border-radius: 0.75rem !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
    }

    .fi-dropdown-list-item {
        font-size: 0.875rem !important;
        padding: 0.625rem 1rem !important;
        color: #000000 !important;
    }

    .fi-dropdown-list-item:hover {
        background: #f3f4f6 !important;
        color: #000000 !important;
    }

    /* Filter Badges and Pills */
    .fi-badge,
    .fi-badge-size-md {
        font-size: 0.8125rem !important;
        padding: 0.375rem 0.75rem !important;
        font-weight: 500 !important;
        border-radius: 0.5rem !important;
        line-height: 1.2 !important;
    }

    .fi-badge-color-primary {
        background: #dbeafe !important;
        color: #1d4ed8 !important;
    }

    .fi-badge-color-success {
        background: #dcfce7 !important;
        color: #166534 !important;
    }

    /* Search Field Groups */
    .fi-fieldset,
    .fi-field-group {
        border-radius: 0.75rem !important;
        border: 1px solid #e5e7eb !important;
        background: #ffffff !important;
        padding: 1rem !important;
    }

    .fi-fieldset-header,
    .fi-field-group-header {
        font-size: 0.9375rem !important;
        font-weight: 600 !important;
        color: #000000 !important;
        margin-bottom: 0.75rem !important;
    }

    /* Search Results Counter */
    .fi-table-summary,
    .fi-table-results-summary {
        font-size: 0.875rem !important;
        color: #6b7280 !important;
        font-weight: 500 !important;
        padding: 0.5rem 0 !important;
    }

    /* Bulk Actions Bar */
    .fi-bulk-actions,
    .fi-table-actions-container {
        background: #f8fafc !important;
        border-radius: 0.75rem !important;
        padding: 0.75rem 1rem !important;
        border: 1px solid #e2e8f0 !important;
    }

    .fi-bulk-actions-label {
        font-size: 0.875rem !important;
        font-weight: 500 !important;
        color: #000000 !important;
    }

    /* Export/Import Buttons */
    .fi-btn-outlined,
    .fi-btn-secondary {
        border: 2px solid #e5e7eb !important;
        background: #ffffff !important;
        color: #000000 !important;
        font-weight: 500 !important;
    }

    .fi-btn-outlined:hover,
    .fi-btn-secondary:hover {
        border-color: #3b82f6 !important;
        background: #f8fafc !important;
        color: #000000 !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2) !important;
    }

    /* Filter Indicators */
    .fi-filters-indicator,
    .fi-active-filters {
        background: #fef3c7 !important;
        color: #92400e !important;
        font-size: 0.8125rem !important;
        padding: 0.375rem 0.75rem !important;
        border-radius: 0.5rem !important;
        font-weight: 500 !important;
    }

    /* Search Loading States */
    .fi-table-loading,
    .fi-loading-indicator {
        font-size: 0.875rem !important;
        color: #6b7280 !important;
        padding: 2rem !important;
        text-align: center !important;
    }

    .fi-table-empty,
    .fi-empty-state {
        font-size: 0.9375rem !important;
        color: #6b7280 !important;
        padding: 3rem 1rem !important;
        text-align: center !important;
        line-height: 1.6 !important;
    }

    /* Search Keyboard Shortcuts */
    .fi-keyboard-shortcut,
    .fi-shortcut-key {
        font-size: 0.75rem !important;
        font-weight: 600 !important;
        color: #6b7280 !important;
        background: #f3f4f6 !important;
        border: 1px solid #d1d5db !important;
        border-radius: 0.25rem !important;
        padding: 0.125rem 0.375rem !important;
    }

    /* Search Suggestions */
    .fi-search-suggestions,
    .fi-autocomplete-list {
        background: #ffffff !important;
        border-radius: 0.75rem !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
        border: 1px solid #e5e7eb !important;
    }

    .fi-search-suggestion-item,
    .fi-autocomplete-item {
        font-size: 0.875rem !important;
        padding: 0.75rem 1rem !important;
        color: #000000 !important;
        border-bottom: 1px solid #f3f4f6 !important;
    }

    .fi-search-suggestion-item:hover,
    .fi-autocomplete-item:hover {
        background: #f8fafc !important;
        color: #000000 !important;
    }

    /* Responsive improvements for mobile */
    @media (max-width: 768px) {
        .fi-table-cell,
        .fi-table-header-cell {
            font-size: 0.8125rem !important;
            padding: 0.5rem !important;
        }
        
        .fi-btn {
            padding: 0.375rem 0.75rem !important;
            font-size: 0.8125rem !important;
        }
        
        .fi-page-heading {
            font-size: 1.25rem !important;
        }

        .fi-tab-button,
        .fi-tabs-item button {
            padding: 0.5rem 0.75rem !important;
            font-size: 0.8125rem !important;
        }

        .fi-search-suggestion-item,
        .fi-autocomplete-item {
            padding: 0.5rem 0.75rem !important;
            font-size: 0.8125rem !important;
        }
    }
</style>