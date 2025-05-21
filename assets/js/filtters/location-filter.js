/**
 * Location filter functionality for customers, suppliers, and mixed accounts pages
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize location filter change event
    const locationFilter = document.getElementById('filter_location');
    if (locationFilter) {
        locationFilter.addEventListener('change', function() {
            applyLocationFilter();
        });
    }
    
    // Set up a MutationObserver to detect when table content changes
    setupTableContentObserver();
});

/**
 * Apply location filter to the table
 */
function applyLocationFilter() {
    const locationFilter = document.getElementById('filter_location');
    if (!locationFilter) return;
    
    const locationValue = locationFilter.value;
    const table = document.querySelector('.table');
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        // If no filter is selected, show all rows
        if (!locationValue) {
            row.style.display = '';
            return;
        }
        
        // Location is in column 6 (index 6)
        const locationCell = row.cells[6];
        if (locationCell) {
            const cellValue = locationCell.textContent.trim();
            // If the cell value matches the filter value, show the row
            if (cellValue === locationValue) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });
}

/**
 * Reset location filter
 */
function resetLocationFilter() {
    const locationFilter = document.getElementById('filter_location');
    if (locationFilter) {
        locationFilter.value = '';
        // If using select2, trigger the change event
        if (window.jQuery && jQuery().select2) {
            jQuery(locationFilter).trigger('change');
        }
        // Otherwise, dispatch a change event
        else {
            locationFilter.dispatchEvent(new Event('change'));
        }
    }
}

/**
 * Set up a MutationObserver to detect changes to the table content
 * and reapply the location filter when needed
 */
function setupTableContentObserver() {
    const tableBody = document.querySelector('.table tbody');
    if (!tableBody) return;
    
    // Create a MutationObserver to watch for changes in the table
    const observer = new MutationObserver(function(mutations) {
        // If the location filter has a value, reapply the filter
        const locationFilter = document.getElementById('filter_location');
        if (locationFilter && locationFilter.value) {
            applyLocationFilter();
        }
    });
    
    // Start observing the table for content changes
    observer.observe(tableBody, {
        childList: true, // observe direct children changes
        subtree: true,   // observe all descendants
        characterData: true // observe text changes
    });
} 