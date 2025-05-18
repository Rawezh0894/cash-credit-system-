$(document).ready(function() {
    // Pagination variables
    let currentPage = 1;
    const rowsPerPage = parseInt($('#per_page').val() || 10);
    const rows = $('table tbody tr');
    
    // Setup pagination
    function setupPagination() {
        const totalRows = rows.length;
        const totalPages = Math.ceil(totalRows / rowsPerPage);
        
        $('#pagination').empty();
        
        if (totalPages <= 1) {
            return;
        }
        
        // Previous button
        const prevBtn = $('<button class="btn btn-sm btn-outline-primary me-1">&laquo;</button>');
        if (currentPage === 1) {
            prevBtn.addClass('disabled');
        } else {
            prevBtn.click(() => {
                currentPage--;
                displayRows();
            });
        }
        $('#pagination').append(prevBtn);
        
        // Page buttons
        for (let i = 1; i <= totalPages; i++) {
            const pageBtn = $(`<button class="btn btn-sm btn-outline-primary me-1">${i}</button>`);
            if (i === currentPage) {
                pageBtn.addClass('active');
            }
            pageBtn.click(() => {
                currentPage = i;
                displayRows();
            });
            $('#pagination').append(pageBtn);
        }
        
        // Next button
        const nextBtn = $('<button class="btn btn-sm btn-outline-primary">&raquo;</button>');
        if (currentPage === totalPages) {
            nextBtn.addClass('disabled');
        } else {
            nextBtn.click(() => {
                currentPage++;
                displayRows();
            });
        }
        $('#pagination').append(nextBtn);
    }
    
    // Display rows based on current page
    function displayRows() {
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        
        rows.hide();
        rows.slice(start, end).show();
        
        setupPagination();
    }
    
    // Initialize display
    displayRows();
    
    // Handle per page change
    $('#per_page').change(function() {
        const newRowsPerPage = parseInt($(this).val());
        if (newRowsPerPage !== rowsPerPage) {
            currentPage = 1; // Reset to first page
            rowsPerPage = newRowsPerPage;
            displayRows();
        }
    });
    
    // Filter table
    window.filterTable = function(input, column) {
        const filterValue = input.value.toLowerCase();
        
        rows.each(function() {
            const row = $(this);
            const cell = row.find(`td:eq(${column})`);
            const text = cell.text().toLowerCase();
            
            if (text.indexOf(filterValue) > -1) {
                row.attr('data-filtered', 'false');
            } else {
                row.attr('data-filtered', 'true');
            }
        });
        
        // Only show rows that aren't filtered
        rows.filter('[data-filtered="true"]').hide();
        rows.filter('[data-filtered="false"]').show();
        
        // Reset pagination after filtering
        currentPage = 1;
        setupPagination();
    };
    
    // Handle Edit User Modal
    $('.action-btn.edit').click(function() {
        const id = $(this).data('id');
        const fullname = $(this).data('fullname');
        const username = $(this).data('username');
        const role = $(this).data('role');
        const active = $(this).data('active');
        
        $('#editUserId').val(id);
        $('#editFullName').val(fullname);
        $('#editUsername').val(username);
        $('#editRole').val(role);
        $('#editIsActive').prop('checked', active == 1);
    });
    
    // Handle Delete User Modal
    $('.action-btn.delete').click(function() {
        const id = $(this).data('id');
        const fullname = $(this).data('fullname');
        
        $('#deleteUserId').val(id);
        $('#deleteUserName').text(fullname);
    });
    
    // Toggle password visibility
    $('.toggle-password').click(function() {
        const passwordField = $(this).prev('input');
        const icon = $(this).find('i');
        
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            icon.removeClass('bi-eye').addClass('bi-eye-slash');
        } else {
            passwordField.attr('type', 'password');
            icon.removeClass('bi-eye-slash').addClass('bi-eye');
        }
    });
});
