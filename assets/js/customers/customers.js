// Global variables
let currentPage = 1;
let recordsPerPage = 10;
let totalPages = 1;

// Load customers on page load
document.addEventListener('DOMContentLoaded', function() {
    loadCustomers();
    
    // Handle per page change
    const perPageSelect = document.getElementById('per_page');
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function() {
            recordsPerPage = this.value;
            currentPage = 1;
            loadCustomers();
        });
    }
    
    // Handle add button click
    const saveCustomerAddBtn = document.getElementById('saveCustomerAddBtn');
    if (saveCustomerAddBtn) {
        saveCustomerAddBtn.addEventListener('click', function() {
            saveCustomer('add');
        });
    }

    // Handle edit button click
    const saveCustomerEditBtn = document.getElementById('saveCustomerEditBtn');
    if (saveCustomerEditBtn) {
        saveCustomerEditBtn.addEventListener('click', function() {
            saveCustomer('edit');
        });
    }
    
    // Add validation for owed_amount and advance_payment fields in both forms
    setupFieldValidation('customerAddForm');
    setupFieldValidation('customerEditForm');
});

// Function to load customers
function loadCustomers() {
    fetch(`../process/customers/select.php?page=${currentPage}&per_page=${recordsPerPage}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderCustomers(data.data);
                renderPagination(data.totalPages);
            } else {
                showSwalAlert2('error', 'هەڵە!', data.message);
            }
        })
        .catch(error => {
            showSwalAlert2('error', 'هەڵە!', 'هەڵەیەک ڕوویدا لە کاتی بارکردنی کڕیارەکان');
        });
}

// Function to render customers table
function renderCustomers(customers) {
    const tbody = document.getElementById('customersTableBody');
    tbody.innerHTML = '';
    
    if (customers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center border">هیچ کڕیارێک نەدۆزرایەوە</td></tr>';
        return;
    }
    
    // First check permissions
    Promise.all([
        fetch('../includes/check_permission.php?check=edit_customer').then(response => response.json()),
        fetch('../includes/check_permission.php?check=delete_customer').then(response => response.json())
    ]).then(([editPerm, deletePerm]) => {
        const canEdit = editPerm.success && editPerm.has_permission;
        const canDelete = deletePerm.success && deletePerm.has_permission;
        
        customers.forEach((customer, index) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="border">${index + 1}</td>
                <td class="border text-break">${safeCell(customer.name)}</td>
                <td class="border text-break">${safeCell(customer.phone1)}</td>
                <td class="border text-break">${formatNumber(customer.owed_amount)}</td>
                <td class="border text-break">${formatNumber(customer.advance_payment)}</td>
                <td class="border text-break">${customer.city ? safeCell(customer.city) : '-'}</td>
                <td class="border text-break">${customer.location === 'inside' ? 'ناو شار' : (customer.location === 'outside' ? 'دەرەوەی شار' : '-')}</td>
                <td class="border">
                    <a href="javascript:void(0);" class="action-btn person" title="زانیاری کڕیار" onclick="viewPerson(${customer.id})">
                        <i class="bi bi-person"></i>
                    </a>
                    ${canEdit ? `
                    <a href="javascript:void(0);" class="action-btn edit edit-customer-btn" title="دەستکاری" onclick="editCustomer(${customer.id})">
                        <i class="bi bi-pencil"></i>
                    </a>
                    ` : ''}
                    ${canDelete ? `
                    <a href="javascript:void(0);" class="action-btn delete delete-customer-btn" title="سڕینەوە" onclick="deleteCustomer(${customer.id}, this)">
                        <i class="bi bi-trash"></i>
                    </a>
                    ` : ''}
                    <a href="javascript:void(0);" class="action-btn pdf" title="پسووڵە بە PDF" onclick="generatePdf(${customer.id})">
                        <i class="bi bi-file-pdf"></i>
                    </a>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }).catch(error => {
        console.error('Error checking permissions:', error);
        
        // Still render the customer list but without action buttons
        customers.forEach((customer, index) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="border">${index + 1}</td>
                <td class="border text-break">${safeCell(customer.name)}</td>
                <td class="border text-break">${safeCell(customer.phone1)}</td>
                <td class="border text-break">${formatNumber(customer.owed_amount)}</td>
                <td class="border text-break">${formatNumber(customer.advance_payment)}</td>
                <td class="border text-break">${customer.city ? safeCell(customer.city) : '-'}</td>
                <td class="border text-break">${customer.location === 'inside' ? 'ناو شار' : (customer.location === 'outside' ? 'دەرەوەی شار' : '-')}</td>
                <td class="border">
                    <a href="javascript:void(0);" class="action-btn person" title="زانیاری کڕیار" onclick="viewPerson(${customer.id})">
                        <i class="bi bi-person"></i>
                    </a>
                    <a href="javascript:void(0);" class="action-btn pdf" title="پسووڵە بە PDF" onclick="generatePdf(${customer.id})">
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
        <a class="page-link rounded-circle mx-1" href="javascript:void(0)" data-page="${currentPage - 1}" aria-label="Previous">
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
            <a class="page-link rounded-circle mx-1" href="javascript:void(0)" data-page="1">1</a>
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
            <a class="page-link rounded-circle mx-1" href="javascript:void(0)" data-page="${i}">${i}</a>
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
            <a class="page-link rounded-circle mx-1" href="javascript:void(0)" data-page="${totalPages}">${totalPages}</a>
        </li>
        `;
    }
    
    // Next button
    const nextDisabled = currentPage >= totalPages ? 'disabled' : '';
    paginationHtml += `
    <li class="page-item ${nextDisabled}">
        <a class="page-link rounded-circle mx-1" href="javascript:void(0)" data-page="${currentPage + 1}" aria-label="Next">
            <span aria-hidden="true">&raquo;</span>
        </a>
    </li>
    `;
    
    paginationHtml += '</ul></nav>';
    
    pagination.innerHTML = paginationHtml;
}

// Function to change page
function changePage(page) {
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    loadCustomers();
}

// Function to save customer
function saveCustomer(type) {
    const form = type === 'add' ? document.getElementById('customerAddForm') : document.getElementById('customerEditForm');
    
    // Get values from the form
    const weOwe = parseFloat(form.querySelector('[name="owed_amount"]')?.value || form.querySelector('[name="we_owe"]')?.value) || 0;
    const advancePayment = parseFloat(form.querySelector('[name="advance_payment"]').value) || 0;
    
    // Check if both fields have values
    if (weOwe > 0 && advancePayment > 0) {
        showSwalAlert2('error', 'هەڵە!', 'تەنیا دەتوانیت یان بڕی قەرز یان بڕی پێشەکی پڕ بکەیتەوە، نەک هەردووکیان');
        return;
    }
    
    const formData = new FormData(form);
    
    // Ensure we_owe field is mapped to owed_amount
    if (formData.has('we_owe')) {
        const weOweValue = formData.get('we_owe');
        formData.set('owed_amount', weOweValue);
    }
    
    fetch(`../process/customers/${type === 'add' ? 'create.php' : 'update.php'}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSwalAlert2('success', 'سەرکەوتوو!', data.message);
            const modalId = type === 'add' ? 'customerAddModal' : 'customerEditModal';
            bootstrap.Modal.getInstance(document.getElementById(modalId)).hide();
            form.reset();
            loadCustomers();
            
            // Refresh the SELECT2 filters after adding or editing a customer
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
        showSwalAlert2('error', 'هەڵە!', 'هەڵەیەک ڕوویدا لە کاتی زیادکردن/دەستکاری کڕیار');
    });
}

// Function to edit customer
function editCustomer(id) {
    fetch(`../process/customers/select.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                const customer = data.data[0];
                const form = document.getElementById('customerEditForm');
                
                form.customer_id.value = customer.id;
                form.name.value = customer.name;
                form.phone1.value = customer.phone1;
                form.phone2.value = customer.phone2 || '';
                form.guarantor_name.value = customer.guarantor_name || '';
                form.guarantor_phone.value = customer.guarantor_phone || '';
                form.owed_amount.value = customer.owed_amount;
                form.advance_payment.value = customer.advance_payment;
                form.city.value = customer.city;
                form.notes.value = customer.notes || '';
                
                if (customer.location === 'inside') {
                    document.getElementById('edit_location_inside').checked = true;
                } else {
                    document.getElementById('edit_location_outside').checked = true;
                }
                
                new bootstrap.Modal(document.getElementById('customerEditModal')).show();
            } else {
                showSwalAlert2('error', 'هەڵە!', 'کڕیار نەدۆزرایەوە');
            }
        })
        .catch(error => {
            showSwalAlert2('error', 'هەڵە!', 'هەڵەیەک ڕوویدا لە کاتی وەرگرتنی زانیاری کڕیار');
        });
}

// Function to delete customer
function deleteCustomer(id, element) {
    Swal.fire({
        title: 'دڵنیای؟',
        text: "ئایا دڵنیای لە سڕینەوەی ئەم کڕیارە؟",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'بەڵێ، بسڕەوە',
        cancelButtonText: 'نەخێر'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`../process/customers/delete.php`, {
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
                    loadCustomers();
                } else {
                    showSwalAlert2('error', 'هەڵە!', data.message);
                }
            })
            .catch(error => {
                showSwalAlert2('error', 'هەڵە!', 'هەڵەیەک ڕوویدا لە کاتی سڕینەوەی کڕیار');
            });
        }
    });
}

function safeCell(val) {
    if (val === null || val === undefined || val === '') return '-';
    
    // If the value looks like it might contain both text and numbers (city with numeric value)
    if (typeof val === 'string' && val.includes(' ')) {
        // Try to extract just the text part before any numbers
        const parts = val.split(' ');
        if (parts.length > 1) {
            // Keep only parts that don't look like numbers
            const nonNumericParts = parts.filter(part => isNaN(Number(part)));
            if (nonNumericParts.length > 0) {
                return nonNumericParts.join(' ');
            }
        }
    }
    return val;
}

function formatNumber(val) {
    if (val === null || val === undefined || val === '' || isNaN(val)) return '-';
    return Number(val).toLocaleString('en-US');
}

function viewPerson(id) {
    window.location.href = 'customer_profile.php?id=' + id;
}

// Generate PDF Function
function generatePdf(customerId) {
    window.open(`../process/customers/generate_pdf.php?id=${customerId}`, '_blank');
}

// Function to validate city field and remove any numeric values
function validateCityField(input) {
    // Remove any digits from the city field
    input.value = input.value.replace(/\d+(\.\d+)?/g, '').trim();
    
    // Remove any extra spaces
    input.value = input.value.replace(/\s+/g, ' ').trim();
}

// Function to set up validation for owed_amount/we_owe and advance_payment fields
function setupFieldValidation(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    const weOweField = formId === 'customerAddForm' ? 
                      form.querySelector('[name="we_owe"]') : 
                      form.querySelector('[name="owed_amount"]');
                      
    const advancePaymentField = formId === 'customerAddForm' ? 
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