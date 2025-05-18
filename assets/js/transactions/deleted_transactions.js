// Display success or error message if present
if (typeof successMessage !== 'undefined' && successMessage) {
    Swal.fire({
        icon: 'success',
        title: 'سەرکەوتوو بوو!',
        text: successMessage,
        confirmButtonText: 'باشە'
    });
}

if (typeof errorMessage !== 'undefined' && errorMessage) {
    Swal.fire({
        icon: 'error',
        title: 'هەڵە!',
        text: errorMessage,
        confirmButtonText: 'باشە'
    });
}

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
    loadDeletedTransactions();
    
    // Per page change handler
    $("#per_page").on("change", function() {
        perPage = parseInt($(this).val());
        currentPage = 1; // Reset to first page
        loadDeletedTransactions();
    });
    
    // Column search input handlers
    $(".search-field").on("keyup", function() {
        // Delay search to reduce server load during typing
        clearTimeout($.data(this, 'timer'));
        $(this).data('timer', setTimeout(() => {
            updateSearchParams();
            currentPage = 1; // Reset to first page
            loadDeletedTransactions();
        }, 500));
    });
    
    // Pagination click handler
    $(document).on("click", ".page-link", function(e) {
        e.preventDefault();
        const page = $(this).data("page");
        if (page) {
            currentPage = page;
            loadDeletedTransactions();
        }
    });
});

// Handle filters automatically
$("#filter_type, #filter_account_type, #filter_date_from, #filter_date_to").on("change", function() {
    // Get filter values
    const type = $("#filter_type").val();
    const accountType = $("#filter_account_type").val();
    const dateFrom = $("#filter_date_from").val();
    const dateTo = $("#filter_date_to").val();
    
    // Update search params
    if (type) searchParams.type = type;
    else delete searchParams.type;
    
    if (accountType) searchParams.account_type = accountType;
    else delete searchParams.account_type;
    
    if (dateFrom) searchParams.date_from = dateFrom;
    else delete searchParams.date_from;
    
    if (dateTo) searchParams.date_to = dateTo;
    else delete searchParams.date_to;
    
    // Reset to first page and reload
    currentPage = 1;
    loadDeletedTransactions();
});

// Reset filters
$("#reset_filters").click(function() {
    // Clear filter inputs
    $("#filter_type").val("");
    $("#filter_account_type").val("");
    $("#filter_date_from").val("");
    $("#filter_date_to").val("");
    
    // Clear search params
    searchParams = {};
    $(".search-field").val("");
    
    // Reset to first page and reload
    currentPage = 1;
    loadDeletedTransactions();
});

// Update search parameters from search fields
function updateSearchParams() {
    // Clear existing search fields
    delete searchParams.search_type;
    delete searchParams.search_amount;
    delete searchParams.search_date;
    delete searchParams.search_account;
    delete searchParams.search_account_type;
    delete searchParams.search_notes;
    delete searchParams.search_deleted_at;
    
    // Get search values and add to params if not empty
    const searchType = $("#search_type").val();
    const searchAmount = $("#search_amount").val();
    const searchDate = $("#search_date").val();
    const searchAccount = $("#search_account").val();
    const searchAccountType = $("#search_account_type").val();
    const searchNotes = $("#search_notes").val();
    const searchDeletedAt = $("#search_deleted_at").val();
    
    if (searchType) searchParams.search_type = searchType;
    if (searchAmount) searchParams.search_amount = searchAmount;
    if (searchDate) searchParams.search_date = searchDate;
    if (searchAccount) searchParams.search_account = searchAccount;
    if (searchAccountType) searchParams.search_account_type = searchAccountType;
    if (searchNotes) searchParams.search_notes = searchNotes;
    if (searchDeletedAt) searchParams.search_deleted_at = searchDeletedAt;
}

// Load deleted transactions
function loadDeletedTransactions() {
    // Show loading indicator
    $("#transactions_table").html('<tr><td colspan="9" class="text-center">جاوەڕوانبە...</td></tr>');
    
    // Prepare parameters
    const params = {
        page: currentPage,
        per_page: perPage,
        ...searchParams
    };
    
    // Make AJAX request
    $.ajax({
        url: "../process/transactions/get_deleted_transactions.php",
        type: "GET",
        data: params,
        dataType: "json",
        success: function(response) {
            if (response.success) {
                // Update total pages
                totalPages = response.total_pages;
                
                // Render transactions
                renderDeletedTransactions(response.transactions);
                
                // Update pagination
                renderPagination(totalPages);
            } else {
                $("#transactions_table").html('<tr><td colspan="9" class="text-center text-danger">' + response.message + '</td></tr>');
            }
        },
        error: function(xhr, status, error) {
            console.error(error);
            $("#transactions_table").html('<tr><td colspan="9" class="text-center text-danger">هەڵەیەک ڕوویدا لە بارکردنی داتاکان</td></tr>');
        }
    });
}

// Render deleted transactions
function renderDeletedTransactions(transactions) {
    if (transactions.length === 0) {
        $("#transactions_table").html('<tr><td colspan="9" class="text-center">هیچ مامەڵەیەکی سڕاوە نەدۆزرایەوە</td></tr>');
        return;
    }
    
    let html = '';
    
    transactions.forEach((transaction, index) => {
        const rowNumber = (currentPage - 1) * perPage + index + 1;
        
        // Format the transaction type
        let typeLabel = '';
        let typeClass = '';
        
        switch (transaction.type) {
            case 'cash':
                typeLabel = 'نەقد';
                typeClass = 'bg-success-subtle text-success';
                break;
            case 'credit':
                typeLabel = 'قەرز';
                typeClass = 'bg-danger-subtle text-danger';
                break;
            case 'collection':
                typeLabel = 'قەرز وەرگرتنەوە';
                typeClass = 'bg-info-subtle text-info';
                break;
            case 'payment':
                typeLabel = 'قەرز دانەوە';
                typeClass = 'bg-primary-subtle text-primary';
                break;
            case 'advance':
                typeLabel = 'پێشەکی';
                typeClass = 'bg-warning-subtle text-warning';
                break;
            case 'advance_refund':
                typeLabel = 'گەڕاندنەوەی پێشەکی';
                typeClass = 'bg-purple-subtle text-purple';
                break;
            case 'advance_collection':
                typeLabel = 'پێشەکی وەرگرتنەوە';
                typeClass = 'bg-orange-subtle text-orange';
                break;
            default:
                typeLabel = transaction.type;
                typeClass = 'bg-secondary-subtle text-secondary';
        }
        
        // Get account name and type
        let accountName = '';
        let accountType = '';
        
        if (transaction.customer_id) {
            accountName = transaction.customer_name;
            accountType = 'کڕیار';
        } else if (transaction.supplier_id) {
            accountName = transaction.supplier_name;
            accountType = 'دابینکەر';
        } else if (transaction.mixed_account_id) {
            accountName = transaction.mixed_account_name;
            accountType = 'هەژماری تێکەڵ';
            
            // Add direction indicator for mixed accounts
            if (transaction.direction === 'sale') {
                accountType += ' (فرۆشتن)';
            } else if (transaction.direction === 'purchase') {
                accountType += ' (کڕین)';
            }
        }
        
        // Format date
        const transactionDate = new Date(transaction.date);
        const formattedDate = new Intl.DateTimeFormat('ku-IQ', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        }).format(transactionDate);
        
        // Format deleted date
        const deletedDate = new Date(transaction.deleted_at);
        const formattedDeletedDate = new Intl.DateTimeFormat('ku-IQ', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        }).format(deletedDate);
        
        // Check if has receipts
        const hasReceipts = transaction.receipt_files && transaction.receipt_files !== '[]' && transaction.receipt_files !== '';
        
        html += `
        <tr>
            <td class="border">${rowNumber}</td>
            <td class="border"><span class="badge ${typeClass}">${typeLabel}</span></td>
            <td class="border">${parseFloat(transaction.amount).toLocaleString('en-US')} دینار</td>
            <td class="border">${formattedDate}</td>
            <td class="border">${accountName}</td>
            <td class="border">${accountType}</td>
            <td class="border">${transaction.notes || ''}</td>
            <td class="border deleted-date">${formattedDeletedDate}</td>
            <td class="border">
                <div class="d-flex gap-1 justify-content-center">
                    <button type="button" class="action-btn edit restore-btn" data-id="${transaction.id}" title="گەڕاندنەوەی مامەڵە">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                    <button type="button" class="action-btn delete permanent-delete-btn" data-id="${transaction.id}" title="سڕینەوەی هەتاهەتایی">
                        <i class="bi bi-trash"></i>
                    </button>
                    ${hasReceipts ? `
                    <button type="button" class="action-btn view view-receipt-btn" 
                            data-receipts='${transaction.receipt_files}'
                            title="بینینی پسووڵە">
                        <i class="bi bi-file-earmark-image"></i>
                    </button>
                    ` : ''}
                </div>
            </td>
        </tr>
        `;
    });
    
    $("#transactions_table").html(html);
    
    // Add event listeners for receipt view buttons
    $(".view-receipt-btn").on("click", function() {
        const receipts = JSON.parse($(this).data("receipts"));
        showReceiptModal(receipts);
    });
    
    // Add event listeners for restore buttons
    $(".restore-btn").on("click", function() {
        const transactionId = $(this).data("id");
        restoreTransaction(transactionId);
    });
    
    // Add event listeners for permanent delete buttons
    $(".permanent-delete-btn").on("click", function() {
        const transactionId = $(this).data("id");
        permanentDeleteTransaction(transactionId);
    });
}

// Update pagination
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

// Show receipt modal
function showReceiptModal(receipts) {
    // Clear previous content
    const carouselInner = $("#receipt-carousel .carousel-inner");
    carouselInner.empty();
    
    // Add receipts to carousel
    receipts.forEach((receipt, index) => {
        // Check if receipt is in backup directory
        const receiptPath = receipt.includes('/') ? receipt : `../uploads/receipts_backup/${receipt}`;
        
        carouselInner.append(`
        <div class="carousel-item ${index === 0 ? 'active' : ''}">
            <img src="${receiptPath}" class="d-block w-100" alt="پسووڵە ${index + 1}">
        </div>
        `);
    });
    
    // Show modal
    $("#receiptViewModal").modal("show");
}

// Restore transaction
function restoreTransaction(transactionId) {
    Swal.fire({
        title: 'دڵنیای؟',
        text: 'ئایا دڵنیای لە گەڕاندنەوەی ئەم مامەڵەیە؟',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'بەڵێ، بیگەڕێنەوە',
        cancelButtonText: 'نەخێر'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'چاوەڕوانبە...',
                text: 'گەڕاندنەوەی مامەڵە',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Send restore request
            $.ajax({
                url: "deleted_transactions.php",
                type: "POST",
                data: {
                    action: 'restore',
                    transaction_id: transactionId
                },
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'سەرکەوتوو بوو!',
                            text: response.message,
                            confirmButtonText: 'باشە'
                        }).then(() => {
                            // Reload transactions
                            loadDeletedTransactions();
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
                error: function(xhr, status, error) {
                    console.error(error);
                    let errorMessage = 'هەڵەیەک ڕوویدا لە گەڕاندنەوەی مامەڵەکە';
                    
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            errorMessage = response.message;
                        }
                    } catch (e) {
                        // Could not parse JSON response
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە!',
                        text: errorMessage,
                        confirmButtonText: 'باشە'
                    });
                }
            });
        }
    });
}

// Permanent delete transaction
function permanentDeleteTransaction(transactionId) {
    Swal.fire({
        title: 'دڵنیای؟',
        text: 'ئایا دڵنیای لە سڕینەوەی هەتاهەتایی ئەم مامەڵەیە؟',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'بەڵێ، بیسڕێنەوە',
        cancelButtonText: 'نەخێر'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'چاوەڕوانبە...',
                text: 'سڕینەوەی مامەڵە',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Send delete request
            $.ajax({
                url: "deleted_transactions.php",
                type: "POST",
                data: {
                    action: 'delete',
                    transaction_id: transactionId
                },
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'سەرکەوتوو بوو!',
                            text: response.message,
                            confirmButtonText: 'باشە'
                        }).then(() => {
                            // Reload transactions
                            loadDeletedTransactions();
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
                error: function(xhr, status, error) {
                    console.error(error);
                    let errorMessage = 'هەڵەیەک ڕوویدا لە سڕینەوەی مامەڵەکە';
                    
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            errorMessage = response.message;
                        }
                    } catch (e) {
                        // Could not parse JSON response
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە!',
                        text: errorMessage,
                        confirmButtonText: 'باشە'
                    });
                }
            });
        }
    });
} 