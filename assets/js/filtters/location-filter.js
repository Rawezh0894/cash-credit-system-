/**
 * Location filter functionality for customers, suppliers, and mixed accounts pages
 * This file is kept for compatibility, but the actual filtering is now handled
 * by the server-side select.php files.
 */
document.addEventListener('DOMContentLoaded', function() {
    // The functionality has been moved to the individual JS files
    // to enable server-side filtering rather than client-side filtering.
    // This file is kept for reference and backward compatibility.
});

/**
 * Legacy function - moved to server-side filtering
 * This function will be called by existing code but does nothing now
 */
function applyLocationFilter() {
    // The filtering is now handled at the server level
    // This function is kept for compatibility with existing code
    console.log("Info: Location filtering is now handled server-side");
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
 * This function is no longer needed since we use server-side filtering
 */
function setupTableContentObserver() {
    // No longer needed - kept for compatibility
} 