/**
 * Initialize tooltips for permission-related elements
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips for all disabled menu items
    const disabledItems = document.querySelectorAll('.nav-link.disabled, .dropdown-item.disabled');
    disabledItems.forEach(item => {
        // Create tooltip text if not already set
        if (!item.getAttribute('title')) {
            const pageName = item.textContent.trim();
            item.setAttribute('title', `توانای دەستگەیشتن نییە بە "${pageName}"`);
        }
        
        // Initialize Bootstrap tooltip
        new bootstrap.Tooltip(item, {
            placement: 'bottom',
            trigger: 'hover',
            delay: { show: 300, hide: 100 }
        });
    });
    
    // Initialize tooltips for elements with data-requires-permission attribute
    const permissionElements = document.querySelectorAll('[data-requires-permission]');
    permissionElements.forEach(element => {
        // Create tooltip text if not already set
        if (!element.getAttribute('title')) {
            const actionText = element.textContent.trim();
            element.setAttribute('title', `توانای ئەنجامدانی "${actionText}" نییە`);
        }
        
        // Initialize Bootstrap tooltip
        new bootstrap.Tooltip(element, {
            placement: 'top',
            trigger: 'hover',
            delay: { show: 300, hide: 100 }
        });
    });
}); 