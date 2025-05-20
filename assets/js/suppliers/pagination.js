// Pagination functionality for suppliers page
document.addEventListener('DOMContentLoaded', function() {
    const paginationLinks = document.querySelectorAll('.pagination .page-link');
    paginationLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.getAttribute('data-page');
            if (page) {
                loadPage(page);
            }
        });
    });
});

function loadPage(page) {
    const url = new URL(window.location.href);
    url.searchParams.set('page', page);
    
    // Show loading state
    const tableBody = document.querySelector('table tbody');
    const originalContent = tableBody.innerHTML;
    tableBody.innerHTML = '<tr><td colspan="8" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>';
    
    fetch(url.toString())
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Update table content
            const newTableBody = doc.querySelector('table tbody');
            tableBody.innerHTML = newTableBody.innerHTML;
            
            // Update pagination
            const newPagination = doc.querySelector('.pagination').parentElement;
            document.querySelector('.pagination').parentElement.innerHTML = newPagination.innerHTML;
            
            // Reattach event listeners to new pagination links
            const paginationLinks = document.querySelectorAll('.pagination .page-link');
            paginationLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const page = this.getAttribute('data-page');
                    if (page) {
                        loadPage(page);
                    }
                });
            });
            
            // Update URL without reload
            window.history.pushState({}, '', url.toString());
        })
        .catch(error => {
            console.error('Error loading page:', error);
            tableBody.innerHTML = originalContent;
        });
} 