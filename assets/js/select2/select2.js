/**
 * SELECT2 initialization and common functionality
 * For use in customers, suppliers, mixed_accounts and transactions pages
 */

// Wait for jQuery to be defined before executing code
(function(factory) {
    if (typeof jQuery === 'undefined') {
        // Handle the case when jQuery isn't loaded yet
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof jQuery !== 'undefined') {
                factory(jQuery);
            } else {
                console.error('SELECT2 initialization failed: jQuery is not loaded');
            }
        });
    } else {
        // jQuery is already available
        factory(jQuery);
    }
})(function($) {
    // Now $ is safely jQuery
    $(document).ready(function() {
        // Initialize SELECT2 for all select elements with select2-filter class
        $('.select2-filter').select2({
            width: '100%',
            placeholder: 'هەموو',
            allowClear: true,
            dir: 'rtl'
        });
        
        // Initialize SELECT2 for modal dropdowns
        initializeModalSelect2();
        
        // When a modal is shown, refresh the SELECT2 instances
        $('.modal').on('shown.bs.modal', function() {
            $(this).find('select.select2-hidden-accessible').select2('destroy').select2({
                width: '100%',
                dir: 'rtl',
                dropdownParent: $(this)
            });
        });
        
        // Apply filter when SELECT2 value changes
        $('.select2-filter').on('change', function() {
            // Check if we're on the transactions page and the custom function exists
            if (typeof applySelect2Filters === 'function') {
                applySelect2Filters();
            } else {
                // Fall back to client-side filtering for other pages
                applyClientSideSelect2Filters();
            }
        });
        
        // Reset all filters including SELECT2
        $('#reset_filters').on('click', function() {
            // Check if we're on the transactions page and the custom function exists
            if (typeof resetAllFilters === 'function') {
                resetAllFilters();
            } else {
                // Fall back to client-side filtering for other pages
                resetClientSideFilters();
            }
        });
    });

    /**
     * Initialize SELECT2 for modal dropdowns
     */
    function initializeModalSelect2() {
        // For each modal with select elements, initialize SELECT2
        $('.modal').each(function() {
            const modalId = '#' + $(this).attr('id');
            $(this).find('select').select2({
                width: '100%',
                placeholder: 'هەڵبژاردن...',
                dir: 'rtl',
                dropdownParent: $(modalId)
            });
        });
    }

    /**
     * Apply filters based on SELECT2 selection (client-side filtering)
     */
    function applyClientSideSelect2Filters() {
        const table = document.querySelector('.table');
        if (!table) return;
        
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            let visible = true;
            
            // Check all active filters
            $('.select2-filter').each(function() {
                const filterColumn = $(this).data('column');
                const filterValue = $(this).val() || '';
                
                if (filterValue && row.cells[filterColumn]) {
                    const cellValue = row.cells[filterColumn].textContent.trim();
                    if (cellValue !== filterValue) {
                        visible = false;
                    }
                }
            });
            
            row.style.display = visible ? '' : 'none';
        });
        
        // Sync values with original filters if they exist
        syncFiltersWithOriginal();
    }

    /**
     * Reset all filters to default values (client-side)
     */
    function resetClientSideFilters() {
        // Reset SELECT2 filters
        $('.select2-filter').val(null).trigger('change');
        
        // Reset date filters if they exist
        $('#filter_date_from, #filter_date_to').val('');
        
        // Reset hidden original filters
        $('#filter_type, #filter_account_type').val('');
        
        // Show all rows
        const table = document.querySelector('.table');
        if (table) {
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                row.style.display = '';
            });
        }
    }

    /**
     * Sync SELECT2 filters with original hidden fields
     * Specific to transactions page but safe to call on other pages
     */
    function syncFiltersWithOriginal() {
        // For transaction type
        const transactionTypeFilter = $('#filter_transaction_type');
        if (transactionTypeFilter.length > 0) {
            const translatedValue = transactionTypeFilter.val() || '';
            let originalValue = '';
            
            // Map the translated values to backend values
            switch(translatedValue) {
                case 'نەقد':
                    originalValue = 'cash';
                    break;
                case 'قەرز':
                    originalValue = 'credit';
                    break;
                case 'پێشەکی':
                    originalValue = 'advance';
                    break;
                case 'قەرز دانەوە':
                    originalValue = 'payment';
                    break;
                case 'قەرز وەرگرتنەوە':
                    originalValue = 'collection';
                    break;
                case 'گەڕاندنەوەی پێشەکی':
                    originalValue = 'advance_refund';
                    break;
                case 'پێشەکی وەرگرتنەوە':
                    originalValue = 'advance_collection';
                    break;
                default:
                    originalValue = '';
            }
            
            // Set the original filter value
            $('#filter_type').val(originalValue);
        }
        
        // For account type
        const accountTypeFilter = $('#filter_account_type_select2');
        if (accountTypeFilter.length > 0) {
            const translatedValue = accountTypeFilter.val() || '';
            let originalValue = '';
            
            // Map the translated values to backend values
            switch(translatedValue) {
                case 'کڕیار':
                    originalValue = 'customer';
                    break;
                case 'دابینکەر':
                    originalValue = 'supplier';
                    break;
                case 'هەژماری تێکەڵ':
                    originalValue = 'mixed';
                    break;
                default:
                    originalValue = '';
            }
            
            // Set the original filter value
            $('#filter_account_type').val(originalValue);
        }
    }

    /**
     * Load unique values into SELECT2 filters
     * This is called after table data is loaded
     * @param {string} filterId - The ID of the filter to populate
     * @param {number} columnIndex - The index of the column to get values from
     */
    function loadUniqueFilterValues(filterId, columnIndex) {
        const table = document.querySelector('.table');
        if (!table) return;
        
        const rows = table.querySelectorAll('tbody tr');
        const uniqueValues = new Set();
        
        rows.forEach(row => {
            const cell = row.cells[columnIndex];
            if (cell) {
                let value;
                
                // Special handling for account name filter - use data attribute
                if (filterId === '#filter_account_name') {
                    value = cell.getAttribute('data-account-name');
                    if (!value) {
                        value = cell.textContent.trim();
                    }
                } else {
                    value = cell.textContent.trim();
                }
                
                if (value) uniqueValues.add(value);
            }
        });
        
        const sortedValues = Array.from(uniqueValues).sort();
        const filterElement = $(filterId);
        
        // Clear existing options except the first one
        filterElement.find('option:not(:first)').remove();
        
        sortedValues.forEach(value => {
            const option = new Option(value, value);
            filterElement.append(option);
        });
        
        filterElement.trigger('change');
    }

    /**
     * Observe table for changes and load filter values
     * Call this function after including this script
     * @param {Object} filterConfig - Configuration for filters {filterId: columnIndex}
     */
    function setupTableObserver(filterConfig) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    // Check if data has been loaded
                    const tableBody = document.querySelector('.table tbody');
                    if (tableBody && tableBody.children.length > 0) {
                        // Load values for each configured filter
                        for (const [filterId, columnIndex] of Object.entries(filterConfig)) {
                            loadUniqueFilterValues(filterId, columnIndex);
                        }
                        observer.disconnect();
                    }
                }
            });
        });
        
        const tableBody = document.querySelector('.table tbody');
        if (tableBody) {
            observer.observe(tableBody, { childList: true });
        }
    }

    // Expose functions to global scope
    window.setupTableObserver = setupTableObserver;
    window.loadUniqueFilterValues = loadUniqueFilterValues;
    // Fallback for resetAllFilters if not defined
    if (typeof window.resetAllFilters !== 'function') {
        window.resetAllFilters = function() {
            // Reset all select2 filters
            $('.select2-filter').val(null).trigger('change');
            // Reset text filters in table headers
            $('.table thead input[type="text"]').val('');
        };
    }
    if (typeof resetAllFilters === 'function') {
        window.resetAllFilters = resetAllFilters;
    }
    
    /**
     * Function to refresh the SELECT2 filters after adding a new item
     * Call this function after a new item is added to the table
     * @param {Object} filterConfig - Configuration for filters {filterId: columnIndex}
     */
    window.refreshFilters = function(filterConfig) {
        // Wait a small amount of time for the table to be updated
        setTimeout(function() {
            // Load values for each configured filter
            for (const [filterId, columnIndex] of Object.entries(filterConfig)) {
                loadUniqueFilterValues(filterId, columnIndex);
            }
        }, 500);
    };
});
