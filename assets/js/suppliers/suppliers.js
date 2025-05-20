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
    fetch(`../process/suppliers/select.php?page=${currentPage}&per_page=${recordsPerPage}`)
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
    
    if (totalPages <= 1) return;
    
    let paginationHtml = '<nav aria-label="Page navigation" class="mt-4">';
    paginationHtml += '<ul class="pagination justify-content-center">';
    
    // Previous button
    const prevDisabled = currentPage <= 1 ? 'disabled' : '';
    paginationHtml += `
    <li class="page-item ${prevDisabled}">
        <a class="page-link rounded-circle mx-1" href="#" data-page="${currentPage - 1}" aria-label="Previous">
            <span aria-hidden="true">&laquo;</span>
        </a>
    </li>
    `;
    
    // Calculate start and end page
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, currentPage + 2);
    
    // Adjust to always show 5 pages when possible
    if (endPage - startPage < 4) {
        if (startPage === 1) {
            endPage = Math.min(totalPages, 5);
        } else if (endPage === totalPages) {
            startPage = Math.max(1, totalPages - 4);
        }
    }
    
    // First page link
    if (startPage > 1) {
        paginationHtml += `
        <li class="page-item">
            <a class="page-link rounded-circle mx-1" href="#" data-page="1">1</a>
        </li>
        `;
        
        // Ellipsis if needed
        if (startPage > 2) {
            paginationHtml += `
            <li class="page-item disabled">
                <span class="page-link rounded-circle mx-1">...</span>
            </li>
            `;
        }
    }
    
    // Page numbers
    for (let i = startPage; i <= endPage; i++) {
        const active = i === currentPage ? 'active' : '';
        paginationHtml += `
        <li class="page-item ${active}">
            <a class="page-link rounded-circle mx-1" href="#" data-page="${i}">${i}</a>
        </li>
        `;
    }
    
    // Last page link
    if (endPage < totalPages) {
        // Ellipsis if needed
        if (endPage < totalPages - 1) {
            paginationHtml += `
            <li class="page-item disabled">
                <span class="page-link rounded-circle mx-1">...</span>
            </li>
            `;
        }
        
        paginationHtml += `
        <li class="page-item">
            <a class="page-link rounded-circle mx-1" href="#" data-page="${totalPages}">${totalPages}</a>
        </li>
        `;
    }
    
    // Next button
    const nextDisabled = currentPage >= totalPages ? 'disabled' : '';
    paginationHtml += `
    <li class="page-item ${nextDisabled}">
        <a class="page-link rounded-circle mx-1" href="#" data-page="${currentPage + 1}" aria-label="Next">
            <span aria-hidden="true">&raquo;</span>
        </a>
    </li>
    `;
    
    paginationHtml += '</ul></nav>';
    
    pagination.innerHTML = paginationHtml;

    // Add click event listeners to pagination links
    const paginationLinks = pagination.querySelectorAll('.page-link');
    paginationLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.getAttribute('data-page');
            if (page) {
                changePage(parseInt(page));
            }
        });
    });
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