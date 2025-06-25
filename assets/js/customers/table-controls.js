// Table controls functionality for customers page
function changePerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.set('page', '1'); // Reset to first page
    window.location.href = url.toString();
}

function filterTable(input, columnIndex) {
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

    fetch(`../process/customers/select.php?search_column=${encodeURIComponent(searchColumn)}&search_value=${encodeURIComponent(searchValue)}`)
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
        .catch(() => {
            if (typeof renderCustomers === 'function') renderCustomers([]);
            const pagination = document.getElementById('pagination');
            if (pagination) pagination.style.display = 'none';
        });
} 