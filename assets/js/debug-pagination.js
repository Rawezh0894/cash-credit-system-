// Debug script for pagination issues
document.addEventListener('DOMContentLoaded', function() {
    console.log("Debug script loaded");
    
    // Debug pagination links
    const debugPagination = () => {
        const links = document.querySelectorAll('.pagination .page-link');
        console.log(`Found ${links.length} pagination links`);
        
        links.forEach((link, index) => {
            const page = link.getAttribute('data-page');
            console.log(`Link ${index}: data-page=${page}`);
            
            // Add a direct click handler for debugging
            link.addEventListener('click', function(e) {
                console.log(`Pagination link clicked: data-page=${page}`);
                
                // Check if changePage function exists
                if (typeof changePage === 'function') {
                    console.log("changePage function exists, calling it");
                    try {
                        changePage(parseInt(page));
                    } catch (error) {
                        console.error("Error calling changePage:", error);
                    }
                } else {
                    console.error("changePage function does not exist");
                }
                
                e.preventDefault();
            });
        });
    };
    
    // Debug when pagination is rendered
    const paginationContainer = document.getElementById('pagination');
    if (paginationContainer) {
        console.log("Found pagination container, setting up observer");
        
        // Call debug function immediately
        debugPagination();
        
        // Watch for changes to pagination
        const observer = new MutationObserver(function(mutations) {
            console.log("Pagination container changed, reattaching handlers");
            debugPagination();
        });
        
        observer.observe(paginationContainer, { childList: true, subtree: true });
    } else {
        console.error("Pagination container not found");
    }
    
    // Check if the required functions exist
    console.log("changePage function exists:", typeof changePage === 'function');
    console.log("loadCustomers function exists:", typeof loadCustomers === 'function');
    console.log("loadSuppliers function exists:", typeof loadSuppliers === 'function');
    console.log("loadMixedAccounts function exists:", typeof loadMixedAccounts === 'function');
}); 