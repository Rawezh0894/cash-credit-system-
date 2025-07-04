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

    // On DOMContentLoaded, load customer types for both forms
    loadCustomerTypes('add');
    loadCustomerTypes('edit');

    // Handle add customer type modal
    document.getElementById('saveCustomerTypeBtn').addEventListener('click', function() {
        const input = document.getElementById('new_customer_type_name');
        const typeName = input.value.trim();
        if (!typeName) {
            showSwalAlert2('error', 'هەڵە!', 'تکایە ناوی جۆر بنووسە');
            return;
        }
        fetch('../process/customers/add_type.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'type_name=' + encodeURIComponent(typeName)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal, clear input, reload types and select new one
                input.value = '';
                bootstrap.Modal.getInstance(document.getElementById('addCustomerTypeModal')).hide();
                showSwalAlert2('success', 'سەرکەوتوو!', data.message);
                // Reload types and select the new one in both forms
                loadCustomerTypes('add', data.new_id);
                loadCustomerTypes('edit', data.new_id);
            } else {
                showSwalAlert2('error', 'هەڵە!', data.message);
            }
        })
        .catch(() => {
            showSwalAlert2('error', 'هەڵە!', 'هەڵەیەک ڕوویدا لە زیادکردنی جۆر');
        });
    });

    populateCustomerTypeFilter();
});

function populateCustomerTypeFilter() {
    fetch('../process/customers/types.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('filter_type');
                if (!select) return;
                select.innerHTML = '<option value="">هەموو جۆرەکان</option>';
                data.data.forEach(type => {
                    select.innerHTML += `<option value="${type.type_name}">${type.type_name}</option>`;
                });
            }
        });
}

// Function to load customers
function loadCustomers() {
    let url = `../process/customers/select.php?page=${currentPage}&per_page=${recordsPerPage}`;
    const typeFilter = document.getElementById('filter_type')?.value;
    if (typeFilter) {
        url += `&customer_type_name=${encodeURIComponent(typeFilter)}`;
    }
    fetch(url)
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
        tbody.innerHTML = '<tr><td colspan="9" class="text-center border">هیچ کڕیارێک نەدۆزرایەوە</td></tr>';
        return;
    }
    
    // Detect if pagination is hidden (search mode)
    const paginationHidden = document.getElementById('pagination') && document.getElementById('pagination').style.display === 'none';
    
    // First check permissions
    Promise.all([
        fetch('../includes/check_permission.php?check=edit_customer').then(response => response.json()),
        fetch('../includes/check_permission.php?check=delete_customer').then(response => response.json())
    ]).then(([editPerm, deletePerm]) => {
        const canEdit = editPerm.success && editPerm.has_permission;
        const canDelete = deletePerm.success && deletePerm.has_permission;
        
        // Render customers with permissions
        renderCustomerRows(customers, paginationHidden, canEdit, canDelete);
        
    }).catch(error => {
        console.error('Error checking permissions:', error);
        // Still render the customer list but without edit/delete buttons
        renderCustomerRows(customers, paginationHidden, false, false);
    });
}

// Helper function to render customer rows
function renderCustomerRows(customers, paginationHidden, canEdit, canDelete) {
    const tbody = document.getElementById('customersTableBody');
    
    customers.forEach((customer, index) => {
        const tr = document.createElement('tr');
        // Calculate row number based on pagination or search
        const rowNumber = paginationHidden ? (index + 1) : (((currentPage - 1) * recordsPerPage) + index + 1);
        
        tr.innerHTML = `
            <td class="border">${rowNumber}</td>
            <td class="border text-break">${safeCell(customer.name)}</td>
            <td class="border text-break">${safeCell(customer.phone1)}</td>
            <td class="border text-break">${formatNumber(customer.owed_amount)}</td>
            <td class="border text-break">${formatNumber(customer.advance_payment)}</td>
            <td class="border text-break">${customer.city ? safeCell(customer.city) : '-'}</td>
            <td class="border text-break">${customer.location === 'inside' ? 'ناو شار' : (customer.location === 'outside' ? 'دەرەوەی شار' : '-')}
            </td>
            <td class="border text-break">${customer.customer_type_name ? safeCell(customer.customer_type_name) : '-'}</td>
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
            loadCustomers();
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
            loadCustomers();
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
            loadCustomers();
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
            loadCustomers();
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
            loadCustomers();
            return false; // Prevent default action and bubbling
        });
    }
    paginationContainer.appendChild(nextBtn);
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
                
                // Wait for select to be loaded, then set value
                setTimeout(() => {
                    if (document.getElementById('edit_customer_type_id')) {
                        document.getElementById('edit_customer_type_id').value = customer.customer_type_id || '';
                    }
                }, 200);
                
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

// Function to load customer types and render select
function loadCustomerTypes(formType, selectId) {
    fetch('../process/customers/types.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let selectHtml = `<select class="form-select" name="customer_type_id" id="${formType === 'add' ? 'customer_type_id' : 'edit_customer_type_id'}">`;
                selectHtml += '<option value="">-- جۆری کڕیار هەلبژێرە --</option>';
                data.data.forEach(type => {
                    selectHtml += `<option value="${type.id}"${selectId && type.id == selectId ? ' selected' : ''}>${type.type_name}</option>`;
                });
                selectHtml += '</select>';
                document.getElementById(formType === 'add' ? 'customer_type_select_add' : 'customer_type_select_edit').innerHTML = selectHtml;
            } else {
                document.getElementById(formType === 'add' ? 'customer_type_select_add' : 'customer_type_select_edit').innerHTML = '<div class="text-danger">هەڵە لە بارکردنی جۆرەکان</div>';
            }
        })
        .catch(() => {
            document.getElementById(formType === 'add' ? 'customer_type_select_add' : 'customer_type_select_edit').innerHTML = '<div class="text-danger">هەڵە لە بارکردنی جۆرەکان</div>';
        });
}

// Add event listener for filter_type
if (document.getElementById('filter_type')) {
    document.getElementById('filter_type').addEventListener('change', function() {
        currentPage = 1;
        loadCustomers();
    });
}

// Fallback for resetAllFilters if not defined (for customers page)
if (typeof window.resetAllFilters !== 'function') {
    window.resetAllFilters = function() {
        // Reset all select2 filters
        $('.select2-filter').val(null).trigger('change');
        // Reset text filters in table headers
        $('.table thead input[type="text"]').val('');
        // Reload customers if function exists
        if (typeof loadCustomers === 'function') loadCustomers();
    };
} 

// --- AJAX-based filter population for select2 filters ---
function populateAllCustomerFilters() {
    fetch('../process/customers/get_filter_options.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Populate names
                const nameSelect = $('#filter_name');
                nameSelect.empty();
                nameSelect.append('<option value="">هەموو ناوەکان</option>');
                data.names.forEach(name => {
                    nameSelect.append(`<option value="${name}">${name}</option>`);
                });
                nameSelect.trigger('change');

                // Populate cities
                const citySelect = $('#filter_city');
                citySelect.empty();
                citySelect.append('<option value="">هەموو شارەکان</option>');
                data.cities.forEach(city => {
                    citySelect.append(`<option value="${city}">${city}</option>`);
                });
                citySelect.trigger('change');

                // Populate types
                const typeSelect = $('#filter_type');
                typeSelect.empty();
                typeSelect.append('<option value="">هەموو جۆرەکان</option>');
                data.types.forEach(type => {
                    typeSelect.append(`<option value="${type}">${type}</option>`);
                });
                typeSelect.trigger('change');
            }
        });
}

// Call this on page load
$(document).ready(function() {
    populateAllCustomerFilters();
});