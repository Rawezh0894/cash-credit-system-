$(document).ready(function() {
    // Pagination variables for roles
    let currentRolesPage = 1;
    let rowsPerPageRoles = parseInt($('#per_page_roles').val() || 10);
    const rolesRows = $('#rolesTableBody tr');
    
    // Pagination variables for permissions
    let currentPermissionsPage = 1;
    let rowsPerPagePermissions = parseInt($('#per_page_permissions').val() || 10);
    const permissionsRows = $('#permissionsTableBody tr');
    
    // Setup pagination for roles
    function setupRolesPagination() {
        const totalRows = rolesRows.filter(':visible').length;
        const totalPages = Math.ceil(totalRows / rowsPerPageRoles);
        
        $('#roles_pagination').empty();
        
        if (totalPages <= 1) {
            return;
        }
        
        // Previous button
        const prevBtn = $('<button class="btn btn-sm btn-outline-primary me-1">&laquo;</button>');
        if (currentRolesPage === 1) {
            prevBtn.addClass('disabled');
        } else {
            prevBtn.click(() => {
                currentRolesPage--;
                displayRolesRows();
            });
        }
        $('#roles_pagination').append(prevBtn);
        
        // Page buttons
        for (let i = 1; i <= totalPages; i++) {
            const pageBtn = $(`<button class="btn btn-sm btn-outline-primary me-1">${i}</button>`);
            if (i === currentRolesPage) {
                pageBtn.addClass('active');
            }
            pageBtn.click(() => {
                currentRolesPage = i;
                displayRolesRows();
            });
            $('#roles_pagination').append(pageBtn);
        }
        
        // Next button
        const nextBtn = $('<button class="btn btn-sm btn-outline-primary">&raquo;</button>');
        if (currentRolesPage === totalPages) {
            nextBtn.addClass('disabled');
        } else {
            nextBtn.click(() => {
                currentRolesPage++;
                displayRolesRows();
            });
        }
        $('#roles_pagination').append(nextBtn);
    }
    
    // Setup pagination for permissions
    function setupPermissionsPagination() {
        const totalRows = permissionsRows.filter(':visible').length;
        const totalPages = Math.ceil(totalRows / rowsPerPagePermissions);
        
        $('#permissions_pagination').empty();
        
        if (totalPages <= 1) {
            return;
        }
        
        // Previous button
        const prevBtn = $('<button class="btn btn-sm btn-outline-primary me-1">&laquo;</button>');
        if (currentPermissionsPage === 1) {
            prevBtn.addClass('disabled');
        } else {
            prevBtn.click(() => {
                currentPermissionsPage--;
                displayPermissionsRows();
            });
        }
        $('#permissions_pagination').append(prevBtn);
        
        // Page buttons
        for (let i = 1; i <= totalPages; i++) {
            const pageBtn = $(`<button class="btn btn-sm btn-outline-primary me-1">${i}</button>`);
            if (i === currentPermissionsPage) {
                pageBtn.addClass('active');
            }
            pageBtn.click(() => {
                currentPermissionsPage = i;
                displayPermissionsRows();
            });
            $('#permissions_pagination').append(pageBtn);
        }
        
        // Next button
        const nextBtn = $('<button class="btn btn-sm btn-outline-primary">&raquo;</button>');
        if (currentPermissionsPage === totalPages) {
            nextBtn.addClass('disabled');
        } else {
            nextBtn.click(() => {
                currentPermissionsPage++;
                displayPermissionsRows();
            });
        }
        $('#permissions_pagination').append(nextBtn);
    }
    
    // Display roles rows based on current page
    function displayRolesRows() {
        const visibleRows = rolesRows.filter(':visible');
        const start = (currentRolesPage - 1) * rowsPerPageRoles;
        const end = start + rowsPerPageRoles;
        
        visibleRows.hide();
        visibleRows.slice(start, end).show();
        
        setupRolesPagination();
    }
    
    // Display permissions rows based on current page
    function displayPermissionsRows() {
        const visibleRows = permissionsRows.filter(':visible');
        const start = (currentPermissionsPage - 1) * rowsPerPagePermissions;
        const end = start + rowsPerPagePermissions;
        
        visibleRows.hide();
        visibleRows.slice(start, end).show();
        
        setupPermissionsPagination();
    }
    
    // Initialize display
    displayRolesRows();
    displayPermissionsRows();
    
    // Handle per page change for roles
    $('#per_page_roles').change(function() {
        const newRowsPerPage = parseInt($(this).val());
        if (newRowsPerPage !== rowsPerPageRoles) {
            currentRolesPage = 1; // Reset to first page
            rowsPerPageRoles = newRowsPerPage;
            displayRolesRows();
        }
    });
    
    // Handle per page change for permissions
    $('#per_page_permissions').change(function() {
        const newRowsPerPage = parseInt($(this).val());
        if (newRowsPerPage !== rowsPerPagePermissions) {
            currentPermissionsPage = 1; // Reset to first page
            rowsPerPagePermissions = newRowsPerPage;
            displayPermissionsRows();
        }
    });
    
    // Filter roles table
    window.filterRolesTable = function(input, column) {
        const filterValue = input.value.toLowerCase();
        
        rolesRows.each(function() {
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
        rolesRows.filter('[data-filtered="true"]').hide();
        rolesRows.filter('[data-filtered="false"]').show();
        
        // Reset pagination after filtering
        currentRolesPage = 1;
        displayRolesRows();
    };
    
    // Filter permissions table
    window.filterPermissionsTable = function(input, column) {
        const filterValue = input.value.toLowerCase();
        
        permissionsRows.each(function() {
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
        permissionsRows.filter('[data-filtered="true"]').hide();
        permissionsRows.filter('[data-filtered="false"]').show();
        
        // Reset pagination after filtering
        currentPermissionsPage = 1;
        displayPermissionsRows();
    };
    
    // Handle Edit Role Modal
    $('.action-btn.edit[data-bs-target="#editRoleModal"]').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const description = $(this).data('description');
        
        $('#editRoleId').val(id);
        $('#editRoleName').val(name);
        $('#editRoleDescription').val(description);
    });
    
    // Handle Delete Role Button Click
    $('.action-btn.delete[data-bs-target="#deleteRoleModal"]').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        
        Swal.fire({
            title: 'دڵنیای لە سڕینەوەی ڕۆڵ؟',
            text: `ئایا دڵنیایت کە دەتەوێت ئەم ڕۆڵە بسڕیتەوە: ${name}؟`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'بەڵێ، بسڕەوە',
            cancelButtonText: 'نەخێر'
        }).then((result) => {
            if (result.isConfirmed) {
                // Send delete request
                $.ajax({
                    url: '../process/roles/delete.php',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'سەرکەوتوو بوو!',
                                text: response.message,
                                confirmButtonText: 'باشە'
                            }).then(() => {
                                // Remove the deleted role row from the table
                                $(`tr:has(a[data-id="${id}"][data-bs-target="#deleteRoleModal"])`).remove();
                                // Update pagination
                                displayRolesRows();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'هەڵە!',
                                text: response.message,
                                confirmButtonText: 'باشە'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە!',
                            text: 'هەڵەیەک ڕوویدا لە کاتی سڕینەوەی ڕۆڵ',
                            confirmButtonText: 'باشە'
                        });
                    }
                });
            }
        });
    });
    
    // Handle Edit Permission Modal
    $('.action-btn.edit[data-bs-target="#editPermissionModal"]').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const description = $(this).data('description');
        
        $('#editPermissionId').val(id);
        $('#editPermissionName').val(name);
        $('#editPermissionDescription').val(description);
    });
    
    // Handle Delete Permission Button Click
    $('.action-btn.delete[data-bs-target="#deletePermissionModal"]').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        
        Swal.fire({
            title: 'دڵنیای لە سڕینەوەی مۆڵەت؟',
            text: `ئایا دڵنیایت کە دەتەوێت ئەم مۆڵەتە بسڕیتەوە: ${name}؟`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'بەڵێ، بسڕەوە',
            cancelButtonText: 'نەخێر'
        }).then((result) => {
            if (result.isConfirmed) {
                // Send delete request
                $.ajax({
                    url: '../process/permissions/delete.php',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'سەرکەوتوو بوو!',
                                text: response.message,
                                confirmButtonText: 'باشە'
                            }).then(() => {
                                // Remove the deleted permission row from the table
                                $(`tr:has(a[data-id="${id}"][data-bs-target="#deletePermissionModal"])`).remove();
                                // Update pagination
                                displayPermissionsRows();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'هەڵە!',
                                text: response.message,
                                confirmButtonText: 'باشە'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە!',
                            text: 'هەڵەیەک ڕوویدا لە کاتی سڕینەوەی مۆڵەت',
                            confirmButtonText: 'باشە'
                        });
                    }
                });
            }
        });
    });
    
    // Handle Manage Permissions Modal
    $('.action-btn.view[data-bs-target="#managePermissionsModal"]').click(function() {
        const roleId = $(this).data('id');
        const roleName = $(this).data('name');
        
        $('#permissionRoleId').val(roleId);
        $('#permissionRoleName').text(roleName);
        
        // Load permissions for this role
        $.ajax({
            url: '../process/roles/get_permissions.php',
            type: 'GET',
            data: { role_id: roleId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Clear previous checkboxes
                    $('#permissionsCheckboxes').empty();
                    
                    // Add permission checkboxes
                    $.each(response.permissions, function(i, permission) {
                        const isChecked = permission.assigned ? 'checked' : '';
                        const checkboxHtml = `
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="permission_${permission.id}" 
                                        name="permissions[]" value="${permission.id}" ${isChecked}>
                                    <label class="form-check-label" for="permission_${permission.id}">
                                        ${permission.name}
                                        <small class="text-muted d-block">${permission.description || ''}</small>
                                    </label>
                                </div>
                            </div>
                        `;
                        $('#permissionsCheckboxes').append(checkboxHtml);
                    });
                } else {
                    alert('هەڵەیەک ڕوویدا لە کاتی وەرگرتنی مۆڵەتەکان');
                }
            },
            error: function() {
                alert('هەڵەیەک ڕوویدا لە کاتی وەرگرتنی مۆڵەتەکان');
            }
        });
    });

    // Handle Edit Permission Form Submission
    $('#editPermissionForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update the table row with new data
                    const id = $('#editPermissionId').val();
                    const name = $('#editPermissionName').val();
                    const description = $('#editPermissionDescription').val();
                    
                    // Find and update the row
                    const row = $(`tr:has(a[data-id="${id}"][data-bs-target="#editPermissionModal"])`);
                    row.find('td:eq(1)').text(name);
                    row.find('td:eq(2)').text(description || 'بێ وەسف');
                    
                    // Update the data attributes
                    row.find('a[data-bs-target="#editPermissionModal"]')
                        .attr('data-name', name)
                        .attr('data-description', description);
                    
                    // Close the modal
                    $('#editPermissionModal').modal('hide');
                    
                    // Show success message
                    Swal.fire({
                        title: 'سەرکەوتوو',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'باشە'
                    });
                } else {
                    Swal.fire({
                        title: 'هەڵە',
                        text: response.message,
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'هەڵە',
                    text: 'هەڵەیەک ڕوویدا لە کاتی دەستکاریکردنی مۆڵەت',
                    icon: 'error',
                    confirmButtonText: 'باشە'
                });
            }
        });
    });

    // Handle Add Permission Form Submission
    $('#addPermissionForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Get the new permission data
                    const name = $('#permissionName').val();
                    const description = $('#permissionDescription').val();
                    
                    // Get the current number of rows
                    const rowCount = $('#permissionsTableBody tr').length;
                    
                    // Create new row HTML
                    const newRow = `
                        <tr>
                            <td>${rowCount + 1}</td>
                            <td>${name}</td>
                            <td>${description || 'بێ وەسف'}</td>
                            <td>${new Date().toLocaleDateString('ku-IQ')}</td>
                            <td>
                                <div class="d-flex gap-2 justify-content-center">
                                    <a 
                                        href="javascript:void(0);"
                                        class="action-btn edit" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editPermissionModal"
                                        data-id="${response.id}"
                                        data-name="${name}"
                                        data-description="${description}"
                                        title="دەستکاری"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a 
                                        href="javascript:void(0);"
                                        class="action-btn delete" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deletePermissionModal"
                                        data-id="${response.id}"
                                        data-name="${name}"
                                        title="سڕینەوە"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    `;
                    
                    // Add the new row to the table
                    $('#permissionsTableBody').append(newRow);
                    
                    // Clear the form
                    $('#addPermissionForm')[0].reset();
                    
                    // Close the modal
                    $('#addPermissionModal').modal('hide');
                    
                    // Show success message
                    Swal.fire({
                        title: 'سەرکەوتوو',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'باشە'
                    });
                    
                    // Update pagination
                    displayPermissionsRows();
                } else {
                    Swal.fire({
                        title: 'هەڵە',
                        text: response.message,
                        icon: 'error',
                        confirmButtonText: 'باشە'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'هەڵە',
                    text: 'هەڵەیەک ڕوویدا لە کاتی زیادکردنی مۆڵەت',
                    icon: 'error',
                    confirmButtonText: 'باشە'
                });
            }
        });
    });
});
