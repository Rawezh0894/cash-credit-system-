/**
 * Check if user has a permission and execute callback accordingly
 * 
 * @param {string} permission Permission name to check
 * @param {Function} hasPermissionCallback Function to execute if user has permission
 * @param {Function} noPermissionCallback Function to execute if user doesn't have permission
 */
function checkPermission(permission, hasPermissionCallback, noPermissionCallback = null) {
    fetch(`../includes/check_permission.php?check=${permission}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.has_permission) {
                if (typeof hasPermissionCallback === 'function') {
                    hasPermissionCallback();
                }
            } else {
                if (typeof noPermissionCallback === 'function') {
                    noPermissionCallback();
                }
            }
        })
        .catch(error => {
            console.error('Error checking permission:', error);
        });
}

/**
 * Set permission attributes on buttons based on their classes
 */
function setupPermissionAttributes() {
    // Map of button classes to permissions
    const classToPermissionMap = {
        'add-transaction-btn': 'add_transaction',
        'edit-transaction-btn': 'edit_transaction',
        'delete-transaction-btn': 'delete_transaction',
        'add-customer-btn': 'add_customer',
        'edit-customer-btn': 'edit_customer',
        'delete-customer-btn': 'delete_customer',
        'add-supplier-btn': 'add_supplier',
        'edit-supplier-btn': 'edit_supplier',
        'delete-supplier-btn': 'delete_supplier',
        'add-mixed-account-btn': 'add_mixed_account',
        'edit-mixed-account-btn': 'edit_mixed_account',
        'delete-mixed-account-btn': 'delete_mixed_account',
        'add-user-btn': 'add_user',
        'edit-user-btn': 'edit_user',
        'delete-user-btn': 'delete_user',
        'restore-transaction-btn': 'restore_transaction',
        'export-report-btn': 'export_reports',
        'add-role-btn': 'add_role',
        'edit-role-btn': 'edit_role',
        'delete-role-btn': 'delete_role',
        'manage-permissions-btn': 'manage_permissions'
    };

    // For each button class in the map
    Object.entries(classToPermissionMap).forEach(([btnClass, permission]) => {
        // Check if the user has the permission
        fetch(`../includes/check_permission.php?check=${permission}`)
            .then(response => response.json())
            .then(data => {
                // If the user doesn't have the permission
                if (!data.success || !data.has_permission) {
                    // Find all buttons with this class and add the data attribute
                    document.querySelectorAll(`.${btnClass}`).forEach(btn => {
                        btn.setAttribute('data-requires-permission', permission);
                        // Add a title for tooltip
                        btn.setAttribute('title', 'ڕێگەپێدانی ناتەواو');
                    });
                }
            })
            .catch(error => {
                console.error(`Error checking permission for ${btnClass}:`, error);
            });
    });
}

/**
 * Initialize permission-based UI elements
 */
function initPermissionBasedUI() {
    // First set up permission attributes on buttons
    setupPermissionAttributes();
    
    // Add event handlers for buttons/elements that need permission checks
    document.querySelectorAll('[data-requires-permission]').forEach(element => {
        const permission = element.getAttribute('data-requires-permission');
        
        element.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Get button text or closest text content
            const actionText = element.textContent.trim() || 'this action';
            
            // Show error toast
            Swal.fire({
                title: 'ڕێگەپێدانی ناتەواو',
                text: `ببوورە، توانای ئەنجامدانی "${actionText}" نییە`,
                icon: 'error',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        });
    });
    
    // Add click handlers for all disabled menu items
    const disabledMenuItems = document.querySelectorAll('.nav-link.disabled, .dropdown-item.disabled');
    
    disabledMenuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get the page name from the text content or link text
            const pageName = this.textContent.trim();
            
            // Show an error toast notification
            Swal.fire({
                title: 'ڕێگەپێدانی ناتەواو',
                text: `ببوورە، توانای دەستگەیشتنت نییە بە "${pageName}"`,
                icon: 'error',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        });
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', initPermissionBasedUI); 