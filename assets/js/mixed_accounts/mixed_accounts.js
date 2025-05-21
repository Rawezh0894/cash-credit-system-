// Global variables
let currentPage = 1;
let totalPages = 1;
let perPage = 10;
let searchParams = {};

// Document ready function
$(document).ready(function() {
    // Set default per page from select
    perPage = parseInt($("#per_page").val());
    
    // Initial load
    loadMixedAccounts();
    
    // Per page change handler
    $("#per_page").on("change", function() {
        perPage = parseInt($(this).val());
        currentPage = 1; // Reset to first page
        loadMixedAccounts();
    });
});

// Load mixed accounts for the current page
function loadMixedAccounts() {
    $.ajax({
        url: "../process/mixed_accounts/get_mixed_accounts.php",
        type: "GET",
        data: {
            page: currentPage,
            per_page: perPage,
            ...searchParams
        },
        dataType: "json",
        success: function(response) {
            if (response.success) {
                // Update total pages
                totalPages = response.total_pages;
                
                // Render mixed accounts
                renderMixedAccounts(response.mixed_accounts);
                
                // Update pagination
                updatePagination();
            } else {
                $("#mixedAccountsTableBody").html('<tr><td colspan="9" class="text-center text-danger">' + response.message + '</td></tr>');
            }
        },
        error: function(xhr, status, error) {
            console.error(error);
            $("#mixedAccountsTableBody").html('<tr><td colspan="9" class="text-center text-danger">هەڵەیەک ڕوویدا لە بارکردنی داتاکان</td></tr>');
        }
    });
}

// Update pagination function
function updatePagination() {
    const pagination = $("#pagination");
    pagination.empty();
    
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
            loadMixedAccounts();
        });
    }
    pagination.append(prevBtn);
    
    // Calculate range of pages to show
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, startPage + 4);
    
    // Adjust start page if we're near the end
    if (endPage - startPage < 4) {
        startPage = Math.max(1, endPage - 4);
    }
    
    // First page
    if (startPage > 1) {
        const firstPageBtn = $('<button class="btn btn-sm btn-outline-primary me-1">1</button>');
        firstPageBtn.click(() => {
            currentPage = 1;
            loadMixedAccounts();
        });
        pagination.append(firstPageBtn);
        
        if (startPage > 2) {
            pagination.append('<span class="btn btn-sm btn-outline-primary me-1 disabled">...</span>');
        }
    }
    
    // Page buttons
    for (let i = startPage; i <= endPage; i++) {
        const pageBtn = $(`<button class="btn btn-sm btn-outline-primary me-1">${i}</button>`);
        if (i === currentPage) {
            pageBtn.addClass('active');
        }
        pageBtn.click(() => {
            currentPage = i;
            loadMixedAccounts();
        });
        pagination.append(pageBtn);
    }
    
    // Last page
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            pagination.append('<span class="btn btn-sm btn-outline-primary me-1 disabled">...</span>');
        }
        
        const lastPageBtn = $(`<button class="btn btn-sm btn-outline-primary me-1">${totalPages}</button>`);
        lastPageBtn.click(() => {
            currentPage = totalPages;
            loadMixedAccounts();
        });
        pagination.append(lastPageBtn);
    }
    
    // Next button
    const nextBtn = $('<button class="btn btn-sm btn-outline-primary">&raquo;</button>');
    if (currentPage === totalPages) {
        nextBtn.addClass('disabled');
    } else {
        nextBtn.click(() => {
            currentPage++;
            loadMixedAccounts();
        });
    }
    pagination.append(nextBtn);
}

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