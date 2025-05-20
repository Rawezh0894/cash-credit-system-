// Global variables
let currentPage = 1;
let recordsPerPage = 10;
let totalPages = 1;

// Load mixed accounts on page load
document.addEventListener('DOMContentLoaded', function() {
    loadMixedAccounts();
    
    // Handle per page change
    const perPageSelect = document.getElementById('per_page');
    if (perPageSelect) {
        perPageSelect.addEventListener('change', function() {
            recordsPerPage = this.value;
            currentPage = 1;
            loadMixedAccounts();
        });
    }
    
    // Handle add button click
    const saveAccountAddBtn = document.getElementById('saveAccountAddBtn');
    if (saveAccountAddBtn) {
        saveAccountAddBtn.addEventListener('click', function() {
            saveMixedAccount('add');
        });
    }

    // Handle edit button click
    const saveAccountEditBtn = document.getElementById('saveAccountEditBtn');
    if (saveAccountEditBtn) {
        saveAccountEditBtn.addEventListener('click', function() {
            saveMixedAccount('edit');
        });
    }
    
    // Add event listeners for the "they" fields in Add form
    setupCreditAdvanceToggle('they_owe', 'they_advance', 'mixedAccountAddForm');
    
    // Add event listeners for the "we" fields in Add form
    setupCreditAdvanceToggle('we_owe', 'we_advance', 'mixedAccountAddForm');
    
    // Add event listeners for the "they" fields in Edit form
    setupCreditAdvanceToggle('edit_they_owe', 'edit_they_advance', 'mixedAccountEditForm');
    
    // Add event listeners for the "we" fields in Edit form
    setupCreditAdvanceToggle('edit_we_owe', 'edit_we_advance', 'mixedAccountEditForm');

    // Attach event delegation for pagination links
    const pagination = document.getElementById('pagination');
    if (pagination) {
        pagination.addEventListener('click', function(e) {
            if (e.target.tagName === 'A' && e.target.hasAttribute('data-page')) {
                e.preventDefault();
                const page = parseInt(e.target.getAttribute('data-page'));
                if (!isNaN(page)) {
                    changePage(page);
                }
            }
        });
    }
});

// Function to setup the credit vs advance toggle behavior
function setupCreditAdvanceToggle(creditFieldId, advanceFieldId, formId) {
    const creditField = document.getElementById(creditFieldId);
    const advanceField = document.getElementById(advanceFieldId);
    
    if (creditField && advanceField) {
        creditField.addEventListener('input', function() {
            if (parseFloat(this.value) > 0) {
                advanceField.value = 0;
                advanceField.setAttribute('disabled', 'disabled');
            } else {
                advanceField.removeAttribute('disabled');
            }
        });
        
        advanceField.addEventListener('input', function() {
            if (parseFloat(this.value) > 0) {
                creditField.value = 0;
                creditField.setAttribute('disabled', 'disabled');
            } else {
                creditField.removeAttribute('disabled');
            }
        });
        
        // Initial check when form loads
        if (parseFloat(creditField.value) > 0) {
            advanceField.value = 0;
            advanceField.setAttribute('disabled', 'disabled');
        } else if (parseFloat(advanceField.value) > 0) {
            creditField.value = 0;
            creditField.setAttribute('disabled', 'disabled');
        }
    }
}

// Function to load mixed accounts
function loadMixedAccounts() {
    fetch(`../process/mixed_accounts/select.php?page=${currentPage}&per_page=${recordsPerPage}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderMixedAccounts(data.data);
                totalPages = data.pagination.total_pages;
                renderPagination(totalPages);
            } else {
                showSwalAlert2('error', 'هەڵە!', data.message);
            }
        })
        .catch(error => {
            showSwalAlert2('error', 'هەڵە!', 'هەڵەیەک ڕوویدا لە کاتی بارکردنی حسابەکان');
        });
}

// Function to render mixed accounts table
function renderMixedAccounts(accounts) {
    const tbody = document.getElementById('mixedAccountsTableBody');
    tbody.innerHTML = '';
    
    if (accounts.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center border">هیچ حسابێک نەدۆزرایەوە</td></tr>';
        return;
    }
    
    // First check permissions
    Promise.all([
        fetch('../includes/check_permission.php?check=edit_mixed_account').then(response => response.json()),
        fetch('../includes/check_permission.php?check=delete_mixed_account').then(response => response.json())
    ]).then(([editPerm, deletePerm]) => {
        const canEdit = editPerm.success && editPerm.has_permission;
        const canDelete = deletePerm.success && deletePerm.has_permission;
        
        accounts.forEach((account, index) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="border">${index + 1}</td>
                <td class="border text-break">${safeCell(account.name)}</td>
                <td class="border text-break">${safeCell(account.phone1)}</td>
                <td class="border text-break">${formatNumber(account.they_owe)}</td>
                <td class="border text-break">${formatNumber(account.we_owe)}</td>
                <td class="border text-break">${safeCell(account.city)}</td>
                <td class="border text-break">${account.location === 'inside' ? 'ناو شار' : (account.location === 'outside' ? 'دەرەوەی شار' : '-')}</td>
                <td class="border">
                    <a href="javascript:void(0);" class="action-btn person" title="زانیاری حساب" onclick="viewPerson(${account.id})">
                        <i class="bi bi-person"></i>
                    </a>
                    ${canEdit ? `
                    <a href="javascript:void(0);" class="action-btn edit edit-mixed-account-btn" title="دەستکاری" onclick="editMixedAccount(${account.id})">
                        <i class="bi bi-pencil"></i>
                    </a>
                    ` : ''}
                    ${canDelete ? `
                    <a href="javascript:void(0);" class="action-btn delete delete-mixed-account-btn" title="سڕینەوە" onclick="deleteMixedAccount(${account.id}, this)">
                        <i class="bi bi-trash"></i>
                    </a>
                    ` : ''}
                    <a href="javascript:void(0);" class="action-btn pdf" title="پسووڵە بە PDF" onclick="generatePdf(${account.id})">
                        <i class="bi bi-file-pdf"></i>
                    </a>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }).catch(error => {
        console.error('Error checking permissions:', error);
        
        // Still render the accounts list but without action buttons
        accounts.forEach((account, index) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="border">${index + 1}</td>
                <td class="border text-break">${safeCell(account.name)}</td>
                <td class="border text-break">${safeCell(account.phone1)}</td>
                <td class="border text-break">${formatNumber(account.they_owe)}</td>
                <td class="border text-break">${formatNumber(account.we_owe)}</td>
                <td class="border text-break">${safeCell(account.city)}</td>
                <td class="border text-break">${account.location === 'inside' ? 'ناو شار' : (account.location === 'outside' ? 'دەرەوەی شار' : '-')}</td>
                <td class="border">
                    <a href="javascript:void(0);" class="action-btn person" title="زانیاری حساب" onclick="viewPerson(${account.id})">
                        <i class="bi bi-person"></i>
                    </a>
                    <a href="javascript:void(0);" class="action-btn pdf" title="پسووڵە بە PDF" onclick="generatePdf(${account.id})">
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
    attachPaginationHandler(); // Attach handler after rendering
}

// Attach event delegation for pagination links
function attachPaginationHandler() {
    const pagination = document.getElementById('pagination');
    if (pagination) {
        pagination.onclick = function(e) {
            if (e.target.tagName === 'A' && e.target.hasAttribute('data-page')) {
                e.preventDefault();
                const page = parseInt(e.target.getAttribute('data-page'));
                if (!isNaN(page)) {
                    changePage(page);
                }
            }
        };
    }
}

// Function to change page
function changePage(page) {
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    loadMixedAccounts();
}

// Function to save mixed account
function saveMixedAccount(type) {
    const form = type === 'add' ? document.getElementById('mixedAccountAddForm') : document.getElementById('mixedAccountEditForm');
    const formData = new FormData(form);
    
    // Get the field values for validation
    const theyOwe = parseFloat(form.querySelector('[name="they_owe"]').value) || 0;
    const theyAdvance = parseFloat(form.querySelector('[name="they_advance"]').value) || 0;
    const weOwe = parseFloat(form.querySelector('[name="we_owe"]').value) || 0;
    const weAdvance = parseFloat(form.querySelector('[name="we_advance"]').value) || 0;
    
    // Validate that users don't enter both debt and advance payment
    if (theyOwe > 0 && theyAdvance > 0) {
        showSwalAlert2('error', 'هەڵە!', 'ناتوانیت لە هەمان کاتدا بڕی قەرزار و بڕی پێشەکی بۆ ئەوان داخڵ بکەیت. تەنها یەکێکیان پڕبکەوە.');
        return;
    }
    
    if (weOwe > 0 && weAdvance > 0) {
        showSwalAlert2('error', 'هەڵە!', 'ناتوانیت لە هەمان کاتدا بڕی قەرزار و بڕی پێشەکی بۆ ئێمە داخڵ بکەیت. تەنها یەکێکیان پڕبکەوە.');
        return;
    }
    
    fetch(`../process/mixed_accounts/${type === 'add' ? 'create.php' : 'update.php'}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSwalAlert2('success', 'سەرکەوتوو!', data.message);
            const modalId = type === 'add' ? 'mixedAccountAddModal' : 'mixedAccountEditModal';
            bootstrap.Modal.getInstance(document.getElementById(modalId)).hide();
            form.reset();
            loadMixedAccounts();
            
            // Refresh the SELECT2 filters after adding or editing a mixed account
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
        showSwalAlert2('error', 'هەڵە!', 'هەڵەیەک ڕوویدا لە کاتی زیادکردن/دەستکاری حساب');
    });
}

// Function to edit mixed account
function editMixedAccount(id) {
    fetch(`../process/mixed_accounts/select.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                const account = data.data[0];
                const form = document.getElementById('mixedAccountEditForm');
                
                form.account_id.value = account.id;
                form.name.value = account.name;
                form.phone1.value = account.phone1;
                form.phone2.value = account.phone2 || '';
                form.guarantor_name.value = account.guarantor_name || '';
                form.guarantor_phone.value = account.guarantor_phone || '';
                form.they_owe.value = account.they_owe;
                form.we_owe.value = account.we_owe;
                form.they_advance.value = account.they_advance;
                form.we_advance.value = account.we_advance;
                form.city.value = account.city;
                form.notes.value = account.notes || '';
                
                if (account.location === 'inside') {
                    document.getElementById('edit_location_inside').checked = true;
                } else {
                    document.getElementById('edit_location_outside').checked = true;
                }
                
                new bootstrap.Modal(document.getElementById('mixedAccountEditModal')).show();
            } else {
                showSwalAlert2('error', 'هەڵە!', 'حساب نەدۆزرایەوە');
            }
        })
        .catch(error => {
            showSwalAlert2('error', 'هەڵە!', 'هەڵەیەک ڕوویدا لە کاتی وەرگرتنی زانیاری حساب');
        });
}

// Function to delete mixed account
function deleteMixedAccount(id, element) {
    Swal.fire({
        title: 'دڵنیای؟',
        text: "ئایا دڵنیای لە سڕینەوەی ئەم حسابە؟",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'بەڵێ، بسڕەوە',
        cancelButtonText: 'نەخێر'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`../process/mixed_accounts/delete.php`, {
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
                    loadMixedAccounts();
                } else {
                    showSwalAlert2('error', 'هەڵە!', data.message);
                }
            })
            .catch(error => {
                showSwalAlert2('error', 'هەڵە!', 'هەڵەیەک ڕوویدا لە کاتی سڕینەوەی حساب');
            });
        }
    });
}

// Generate PDF Function
function generatePdf(accountId) {
    window.open(`../process/mixed_accounts/generate_pdf.php?id=${accountId}`, '_blank');
}

function safeCell(val) {
    return (val === null || val === undefined || val === '') ? '-' : val;
}

function formatNumber(val) {
    if (val === null || val === undefined || val === '' || isNaN(val)) return '-';
    return Number(val).toLocaleString('en-US');
}

function viewPerson(id) {
    window.location.href = 'mixed_account_profile.php?id=' + id;
} 