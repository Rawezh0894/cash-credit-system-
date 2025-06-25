// Table controls functionality for customers page
function changePerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.set('page', '1'); // Reset to first page
    window.location.href = url.toString();
}

// Debounce utility function
function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

// Debounced version of filterTable
const debouncedFilterTable = debounce(filterTable, 300);

// Global AbortController for fetch cancellation
let lastFetchController = null;

function filterTable(input, columnIndex) {
    if (lastFetchController) lastFetchController.abort();
    lastFetchController = new AbortController();
    const searchValue = input.value.trim();
    if (!searchValue) {
        // If search is empty, reload customers and show pagination
        if (typeof loadCustomers === 'function') loadCustomers();
        const pagination = document.getElementById('pagination');
        if (pagination) pagination.style.display = '';
        return;
    }

    // Map columnIndex to column name in database
    let searchColumn = '';
    switch (columnIndex) {
        case 1:
            searchColumn = 'name';
            break;
        case 2:
            searchColumn = 'phone1';
            break;
        case 3:
            searchColumn = 'owed_amount';
            break;
        case 4:
            searchColumn = 'advance_payment';
            break;
        case 5:
            searchColumn = 'city';
            break;
        case 6:
            searchColumn = 'location';
            break;
        default:
            searchColumn = '';
    }
    if (!searchColumn) return;

    fetch(`../process/customers/select.php?search_column=${encodeURIComponent(searchColumn)}&search_value=${encodeURIComponent(searchValue)}`, { signal: lastFetchController.signal })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (typeof renderCustomers === 'function') renderCustomers(data.data);
                // Hide pagination when searching
                const pagination = document.getElementById('pagination');
                if (pagination) pagination.style.display = 'none';
            } else {
                if (typeof renderCustomers === 'function') renderCustomers([]);
                const pagination = document.getElementById('pagination');
                if (pagination) pagination.style.display = 'none';
            }
        })
        .catch((err) => {
            if (err.name === 'AbortError') return; // Ignore aborted fetches
            if (typeof renderCustomers === 'function') renderCustomers([]);
            const pagination = document.getElementById('pagination');
            if (pagination) pagination.style.display = 'none';
        });
}

// Patch loadCustomers to use AbortController as well
if (typeof window.loadCustomers === 'function') {
    const originalLoadCustomers = window.loadCustomers;
    window.loadCustomers = function() {
        if (lastFetchController) lastFetchController.abort();
        lastFetchController = new AbortController();
        let url = `../process/customers/select.php?page=${window.currentPage || 1}&per_page=${window.recordsPerPage || 10}`;
        const typeFilter = document.getElementById('filter_type')?.value;
        if (typeFilter) {
            url += `&customer_type_name=${encodeURIComponent(typeFilter)}`;
        }
        fetch(url, { signal: lastFetchController.signal })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (typeof renderCustomers === 'function') renderCustomers(data.data);
                    if (typeof renderPagination === 'function') renderPagination(data.totalPages);
                } else {
                    if (typeof showSwalAlert2 === 'function') showSwalAlert2('error', 'هەڵە!', data.message);
                }
            })
            .catch(error => {
                if (error.name === 'AbortError') return;
                if (typeof showSwalAlert2 === 'function') showSwalAlert2('error', 'هەڵە!', 'هەڵەیەک ڕوویدا لە کاتی بارکردنی کڕیارەکان');
            });
    };
}

// On DOMContentLoaded, replace all onkeyup="filterTable(this, ...)" with debouncedFilterTable
window.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.table-header-with-search input').forEach((input, idx) => {
        // Remove any inline onkeyup if present
        input.onkeyup = function() { debouncedFilterTable(this, idx + 1); };
    });
}); 