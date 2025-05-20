// Direct pagination fix script
document.addEventListener('DOMContentLoaded', function() {
    console.log('Pagination fix script loaded');
    
    // Function to add direct click handlers to pagination links
    function fixPagination() {
        const paginationLinks = document.querySelectorAll('.pagination .page-link');
        console.log(`Found ${paginationLinks.length} pagination links to fix`);
        
        paginationLinks.forEach((link, index) => {
            // Remove existing click handlers by cloning and replacing
            const newLink = link.cloneNode(true);
            link.parentNode.replaceChild(newLink, link);
            
            // Get page number
            const page = newLink.getAttribute('data-page');
            if (!page) return;
            
            // Add new click handler
            newLink.addEventListener('click', function(e) {
                e.preventDefault();
                console.log(`Pagination link clicked: page ${page}`);
                
                // Determine which page we're on
                if (window.location.pathname.includes('/customers.php') && typeof window.changePage === 'function') {
                    console.log('Using customers changePage function');
                    window.changePage(parseInt(page));
                } 
                else if (window.location.pathname.includes('/suppliers.php') && typeof window.changePage === 'function') {
                    console.log('Using suppliers changePage function');
                    window.changePage(parseInt(page));
                }
                else if (window.location.pathname.includes('/mixed_accounts.php') && typeof window.changePage === 'function') {
                    console.log('Using mixed_accounts changePage function');
                    window.changePage(parseInt(page));
                }
                else {
                    console.log('Direct approach: reload with page parameter');
                    // Direct approach - update URL and reload
                    const url = new URL(window.location.href);
                    url.searchParams.set('page', page);
                    window.location.href = url.toString();
                }
            });
        });
    }
    
    // Fix immediately
    fixPagination();
    
    // Fix again after a short delay (in case of dynamic loading)
    setTimeout(fixPagination, 1000);
    
    // Set up observer to keep watching for pagination changes
    const paginationContainer = document.getElementById('pagination');
    if (paginationContainer) {
        const observer = new MutationObserver(function(mutations) {
            console.log('Pagination container changed, reapplying fix');
            fixPagination();
        });
        
        observer.observe(paginationContainer, { childList: true, subtree: true });
    }
    
    // Make sure changePage function is exposed globally
    if (typeof changePage === 'function' && typeof window.changePage !== 'function') {
        window.changePage = changePage;
    }
}); 