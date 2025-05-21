// Global variables
let currentPage = 1;
let recordsPerPage = 10;
let totalPages = 1;

// Load suppliers on page load
document.addEventListener('DOMContentLoaded', function() {
    loadSuppliers();
    
    // Handle per page change
    const perPageSelect = document.getElementById('per_page');
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function() {
            recordsPerPage = this.value;
            currentPage = 1;
            loadSuppliers();
        });
    }
    
    // Handle add button click
    const saveSupplierAddBtn = document.getElementById('saveSupplierAddBtn');
    if (saveSupplierAddBtn) {
        saveSupplierAddBtn.addEventListener('click', function() {
            saveSupplier('add');
        });
    }

    // Handle edit button click
    const saveSupplierEditBtn = document.getElementById('saveSupplierEditBtn');
    if (saveSupplierEditBtn) {
        saveSupplierEditBtn.addEventListener('click', function() {
            saveSupplier('edit');
        });
    }
    
    // Add validation for we_owe and advance_payment fields in both forms
    setupFieldValidation('supplierAddForm');
    setupFieldValidation('supplierEditForm');
});

// Function to set up validation for we_owe and advance_payment fields
function setupFieldValidation(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    const weOweField = formId === 'supplierAddForm' ? 
                      form.querySelector('[name="we_owe"]') : 
                      form.querySelector('[name="we_owe"]');
                      
    const advancePaymentField = formId === 'supplierAddForm' ? 
                               form.querySelector('[name="advance_payment"]') : 
                               form.querySelector('[name="advance_payment"]');
    
    if (weOweField && advancePaymentField) {
        weOweField.addEventListener('input', function() {
            if (parseFloat(this.value) > 0) {
                advancePaymentField.value = '0';
                advancePaymentField.disabled = true;
            } else {
                advancePaymentField.disabled = false;
            }
        });
        
        advancePaymentField.addEventListener('input', function() {
            if (parseFloat(this.value) > 0) {
                weOweField.value = '0';
                weOweField.disabled = true;
            } else {
                weOweField.disabled = false;
            }
        });
    }
}

// Function to load suppliers
function loadSuppliers() {
    let url = `../process/suppliers/select.php?page=${currentPage}&per_page=${recordsPerPage}`;
    
    // Get filter values
    const nameFilter = $('#filter_name').val();
    const cityFilter = $('#filter_city').val();
    const locationFilter = $('#filter_location').val();
    
    // Add filters to URL if they have values
    if (nameFilter) {
        url += `&name=${encodeURIComponent(nameFilter)}`;
    }
    
    if (cityFilter) {
        url += `&city=${encodeURIComponent(cityFilter)}`;
    }
    
    if (locationFilter) {
        url += `&location=${encodeURIComponent(locationFilter)}`;
    }
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderSuppliers(data.data);
                renderPagination(data.totalPages);
            } else {
                showSwalAlert2('error', 'هەڵە!', data.message);
            }
        })
        .catch(error => {
            showSwalAlert2('error', 'هەڵە!', 'هەڵەیەک ڕوویدا لە کاتی بارکردنی دابینکەرەکان');
        });
}

// Function to render suppliers table
function renderSuppliers(suppliers) {
    const tbody = document.getElementById('suppliersTableBody');
    tbody.innerHTML = '';
    
    if (suppliers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center border">هیچ دابینکەرێک نەدۆزرایەوە</td></tr>';
        return;
    }
    
    // First check permissions
    Promise.all([
        fetch('../includes/check_permission.php?check=edit_supplier').then(response => response.json()),
        fetch('../includes/check_permission.php?check=delete_supplier').then(response => response.json())
    ]).then(([editPerm, deletePerm]) => {
        const canEdit = editPerm.success && editPerm.has_permission;
        const canDelete = deletePerm.success && deletePerm.has_permission;
        
        suppliers.forEach((supplier, index) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="border">${index + 1}</td>
                <td class="border text-break">${safeCell(supplier.name)}</td>
                <td class="border text-break">${safeCell(supplier.phone1)}</td>
                <td class="border text-break">${formatNumber(supplier.we_owe)}</td>
                <td class="border text-break">${formatNumber(supplier.advance_payment)}</td>
                <td class="border text-break">${safeCell(supplier.city)}</td>
                <td class="border text-break">${supplier.location === 'inside' ? 'ناو شار' : (supplier.location === 'outside' ? 'دەرەوەی شار' : '-')}</td>
                <td class="border">
                    <a href="javascript:void(0);" class="action-btn person" title="زانیاری دابینکەر" onclick="viewPerson(${supplier.id})">
                        <i class="bi bi-person"></i>
                    </a>
                    ${canEdit ? `
                    <a href="javascript:void(0);" class="action-btn edit edit-supplier-btn" title="دەستکاری" onclick="editSupplier(${supplier.id})">
                        <i class="bi bi-pencil"></i>
                    </a>
                    ` : ''}
                    ${canDelete ? `
                    <a href="javascript:void(0);" class="action-btn delete delete-supplier-btn" title="سڕینەوە" onclick="deleteSupplier(${supplier.id}, this)">
                        <i class="bi bi-trash"></i>
                    </a>
                    ` : ''}
                    <a href="javascript:void(0);" class="action-btn pdf" title="پسووڵە بە PDF" onclick="generatePdf(${supplier.id})">
                        <i class="bi bi-file-pdf"></i>
                    </a>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }).catch(error => {
        console.error('Error checking permissions:', error);
        
        // Still render the supplier list but without action buttons
        suppliers.forEach((supplier, index) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="border">${index + 1}</td>
                <td class="border text-break">${safeCell(supplier.name)}</td>
                <td class="border text-break">${safeCell(supplier.phone1)}</td>
                <td class="border text-break">${formatNumber(supplier.we_owe)}</td>
                <td class="border text-break">${formatNumber(supplier.advance_payment)}</td>
                <td class="border text-break">${safeCell(supplier.city)}</td>
                <td class="border text-break">${supplier.location === 'inside' ? 'ناو شار' : (supplier.location === 'outside' ? 'دەرەوەی شار' : '-')}</td>
                <td class="border">
                    <a href="javascript:void(0);" class="action-btn person" title="زانیاری دابینکەر" onclick="viewPerson(${supplier.id})">
                        <i class="bi bi-person"></i>
                    </a>
                    <a href="javascript:void(0);" class="action-btn pdf" title="پسووڵە بە PDF" onclick="generatePdf(${supplier.id})">
                        <i class="bi bi-file-pdf"></i>
                    </a>
                </td>
            `;
            tbody.appendChild(tr);
        });
    });
}

// Function to render pagination
function renderPagination(totalPages) {
    const pagination = document.getElementById('pagination');
    pagination.innerHTML = '';
    
    // Add a container div for centering the pagination
    const paginationContainer = document.createElement('div');
    paginationContainer.className = 'd-flex justify-content-center my-3';
    pagination.appendChild(paginationContainer);
    
    if (totalPages <= 1) {
        return;
    }
    
    // Previous button
    const prevBtn = document.createElement('button');
    prevBtn.className = 'btn btn-sm btn-outline-primary me-1';
    prevBtn.innerHTML = '&laquo;';
    if (currentPage === 1) {
        prevBtn.classList.add('disabled');
    } else {
        prevBtn.addEventListener('click', function(e) {
            e.preventDefault();
            currentPage--;
            loadSuppliers();
            return false; // Prevent default action and bubbling
        });
    }
    paginationContainer.appendChild(prevBtn);
    
    // Calculate range of pages to show
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, startPage + 4);
    
    // Adjust start page if we're near the end
    if (endPage - startPage < 4) {
        startPage = Math.max(1, endPage - 4);
    }
    
    // First page
    if (startPage > 1) {
        const firstPageBtn = document.createElement('button');
        firstPageBtn.className = 'btn btn-sm btn-outline-primary me-1';
        firstPageBtn.textContent = '1';
        firstPageBtn.addEventListener('click', function(e) {
            e.preventDefault();
            currentPage = 1;
            loadSuppliers();
            return false; // Prevent default action and bubbling
        });
        paginationContainer.appendChild(firstPageBtn);
        
        if (startPage > 2) {
            const ellipsis = document.createElement('span');
            ellipsis.className = 'btn btn-sm btn-outline-primary me-1 disabled';
            ellipsis.textContent = '...';
            paginationContainer.appendChild(ellipsis);
        }
    }
    
    // Page buttons
    for (let i = startPage; i <= endPage; i++) {
        const pageBtn = document.createElement('button');
        pageBtn.className = 'btn btn-sm btn-outline-primary me-1';
        if (i === currentPage) {
            pageBtn.classList.add('active');
        }
        pageBtn.textContent = i;
        pageBtn.addEventListener('click', function(e) {
            e.preventDefault();
            currentPage = i;
            loadSuppliers();
            return false; // Prevent default action and bubbling
        });
        paginationContainer.appendChild(pageBtn);
    }
    
    // Last page
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            const ellipsis = document.createElement('span');
            ellipsis.className = 'btn btn-sm btn-outline-primary me-1 disabled';
            ellipsis.textContent = '...';
            paginationContainer.appendChild(ellipsis);
        }
        
        const lastPageBtn = document.createElement('button');
        lastPageBtn.className = 'btn btn-sm btn-outline-primary me-1';
        lastPageBtn.textContent = totalPages;
        lastPageBtn.addEventListener('click', function(e) {
            e.preventDefault();
            currentPage = totalPages;
            loadSuppliers();
            return false; // Prevent default action and bubbling
        });
        paginationContainer.appendChild(lastPageBtn);
    }
    
    // Next button
    const nextBtn = document.createElement('button');
    nextBtn.className = 'btn btn-sm btn-outline-primary';
    nextBtn.innerHTML = '&raquo;';
    if (currentPage === totalPages) {
        nextBtn.classList.add('disabled');
    } else {
        nextBtn.addEventListener('click', function(e) {
            e.preventDefault();
            currentPage++;
            loadSuppliers();
            return false; // Prevent default action and bubbling
        });
    }
    paginationContainer.appendChild(nextBtn);
}

// Function to change page
function changePage(page) {
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    loadSuppliers();
}

// Function to save supplier
function saveSupplier(type) {
    const form = type === 'add' ? document.getElementById('supplierAddForm') : document.getElementById('supplierEditForm');
    
    // Get values from the form
    const weOwe = parseFloat(form.querySelector('[name="we_owe"]').value) || 0;
    const advancePayment = parseFloat(form.querySelector('[name="advance_payment"]').value) || 0;
    
    // Check if both fields have values
    if (weOwe > 0 && advancePayment > 0) {
        showSwalAlert2('error', 'هەڵە!', 'تەنیا دەتوانیت یان بڕی قەرز یان بڕی پێشەکی پڕ بکەیتەوە، نەک هەردووکیان');
        return;
    }
    
    const formData = new FormData(form);
    
    fetch(`../process/suppliers/${type === 'add' ? 'create.php' : 'update.php'}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSwalAlert2('success', 'سەرکەوتوو!', data.message);
            const modalId = type === 'add' ? 'supplierAddModal' : 'supplierEditModal';
            bootstrap.Modal.getInstance(document.getElementById(modalId)).hide();
            form.reset();
            loadSuppliers();
            
            // Refresh the SELECT2 filters after adding or editing a supplier
            const filterConfig = {
                '#filter_name': 1,   // Name column
                '#filter_city': 5    // City column
            };
            refreshFilters(filterConfig);
        } else {
            showSwalAlert2('error', 'هەڵە!', data.message);
        }
    })
    .catch(error => {
        showSwalAlert2('error', 'هەڵە!', 'هەڵەیەک ڕوویدا لە کاتی زیادکردن/دەستکاری دابینکەر');
    });
}

// Function to edit supplier
function editSupplier(id) {
    fetch(`../process/suppliers/select.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                const supplier = data.data[0];
                const form = document.getElementById('supplierEditForm');
                
                form.supplier_id.value = supplier.id;
                form.name.value = supplier.name;
                form.phone1.value = supplier.phone1;
                form.phone2.value = supplier.phone2 || '';
                form.we_owe.value = supplier.we_owe;
                form.advance_payment.value = supplier.advance_payment;
                form.city.value = supplier.city;
                form.notes.value = supplier.notes || '';
                
                if (supplier.location === 'inside') {
                    document.getElementById('edit_location_inside').checked = true;
                } else {
                    document.getElementById('edit_location_outside').checked = true;
                }
                
                new bootstrap.Modal(document.getElementById('supplierEditModal')).show();
            } else {
                showSwalAlert2('error', 'هەڵە!', 'دابینکەر نەدۆزرایەوە');
            }
        })
        .catch(error => {
            showSwalAlert2('error', 'هەڵە!', 'هەڵەیەک ڕوویدا لە کاتی وەرگرتنی زانیاری دابینکەر');
        });
}

// Function to delete supplier
function deleteSupplier(id, element) {
    Swal.fire({
        title: 'دڵنیای؟',
        text: "ئایا دڵنیای لە سڕینەوەی ئەم دابینکەرە؟",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'بەڵێ، بسڕەوە',
        cancelButtonText: 'نەخێر'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`../process/suppliers/delete.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSwalAlert2('success', 'سەرکەوتوو!', data.message);
                    loadSuppliers();
                } else {
                    showSwalAlert2('error', 'هەڵە!', data.message);
                }
            })
            .catch(error => {
                showSwalAlert2('error', 'هەڵە!', 'هەڵەیەک ڕوویدا لە کاتی سڕینەوەی دابینکەر');
            });
        }
    });
}

// Generate PDF Function
function generatePdf(supplierId) {
    window.open(`../process/suppliers/generate_pdf.php?id=${supplierId}`, '_blank');
}

function safeCell(val) {
    return (val === null || val === undefined || val === '') ? '-' : val;
}

function formatNumber(val) {
    if (val === null || val === undefined || val === '' || isNaN(val)) return '-';
    return Number(val).toLocaleString('en-US');
}

function viewPerson(id) {
    window.location.href = 'supplier_profile.php?id=' + id;
}

// Document ready handlers for filters
$(document).ready(function() {
    // Handle select2 filters
    $('#filter_name, #filter_city').on('change', function() {
        currentPage = 1;
        loadSuppliers();
    });
    
    // Location filter is already handled by the location-filter.js file,
    // but we need to update it to reload data instead of just hiding rows
    $('#filter_location').on('change', function() {
        currentPage = 1;
        loadSuppliers();
    });
    
    // Reset all filters function
    window.resetAllFilters = function() {
        // Reset all select2 filters
        $('.select2-filter').val(null).trigger('change');
        // Reset location filter
        $('#filter_location').val('').trigger('change');
        // Reset text filters in table headers
        $('.table thead input[type="text"]').val('');
        // Reset to page 1 and reload
        currentPage = 1;
        // Reload suppliers
        loadSuppliers();
    };
}); 