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
let uploadedFiles = [];
let editUploadedFiles = [];

// Initialize on document ready
$(document).ready(function() {
    // Initialize Select2 for account dropdowns
    $('#customer_id, #supplier_id, #mixed_account_id').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#transactionAddModal'),
        width: '100%'
    });
    
    // Edit transaction modal selects
    $('#edit_customer_id, #edit_supplier_id, #edit_mixed_account_id').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#transactionEditModal'),
        width: '100%'
    });
    
    // Load transactions on page load
    loadTransactions();
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
    loadTransactions();
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
    
    // Reset to first page and reload
    currentPage = 1;
    loadTransactions();
});

// Configure Dropzone
Dropzone.autoDiscover = false;

// Pre-process images before adding to Dropzone
async function preProcessFile(file) {
    // Only compress images
    if (file.type.startsWith('image/')) {
        try {
            console.log(`Processing image: ${file.name} (${formatBytes(file.size)})`);
            const compressedFile = await compressImage(file, 1200, 0.5);
            console.log(`Original: ${formatBytes(file.size)}, Compressed: ${formatBytes(compressedFile.size)}, Saved: ${calculateReduction(file.size, compressedFile.size)}`);
            // Add a flag to indicate this file was compressed
            compressedFile.isCompressed = true;
            return compressedFile;
        } catch (error) {
            console.error('Error compressing image:', error);
            return file; // Return original file if compression fails
        }
    }
    return file; // Return non-image files as is
}

// Image compression function
async function compressImage(file, maxWidth = 1200, quality = 0.5) {
    return new Promise((resolve, reject) => {
        // Skip compression for non-image files or already small files
        if (!file.type.startsWith('image/')) {
            resolve(file);
            return;
        }
        
        // Create a FileReader to read the image
        const reader = new FileReader();
        reader.onload = function(event) {
            const img = new Image();
            img.onload = function() {
                // Calculate new dimensions while maintaining aspect ratio
                let width = img.width;
                let height = img.height;
                
                if (width > maxWidth) {
                    const ratio = maxWidth / width;
                    width = maxWidth;
                    height = height * ratio;
                }
                
                // Create canvas for resizing
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                
                // Draw resized image on canvas
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                
                // Get blob from canvas
                canvas.toBlob(function(blob) {
                    // Create a new file with original metadata
                    const compressedFile = new File([blob], file.name, {
                        type: 'image/jpeg',
                        lastModified: new Date().getTime()
                    });
                    
                    console.log(`Original: ${formatBytes(file.size)}, Compressed: ${formatBytes(compressedFile.size)}, Saved: ${calculateReduction(file.size, compressedFile.size)}`);
                    resolve(compressedFile);
                }, 'image/jpeg', quality);
            };
            img.src = event.target.result;
        };
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}

// Helper function to format bytes
function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 بایت';
    
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['بایت', 'کیلۆبایت', 'مێگابایت', 'گێگابایت'];
    
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

// Helper function to calculate reduction percentage
function calculateReduction(originalSize, compressedSize) {
    const reduction = 100 - ((compressedSize / originalSize) * 100);
    return `${reduction.toFixed(1)}%`;
}

let myDropzone = new Dropzone("#receipt-dropzone", {
    url: "../process/transactions/upload_receipt.php",
    paramName: "file",
    maxFilesize: 10, // MB
    acceptedFiles: "image/*",
    addRemoveLinks: true,
    dictDefaultMessage: "وێنەکان بۆ ئەپلۆدکردن بکێشە ئێرە یان کلیک لێرە بکە",
    dictRemoveFile: "سڕینەوە",
    dictCancelUpload: "هەڵوەشاندنەوەی ئەپلۆد",
    autoProcessQueue: true,
    accept: function(file, done) {
        // Show processing message
        const processingMessage = document.createElement('div');
        processingMessage.className = 'dz-processing-message';
        processingMessage.textContent = 'بچووک کردنەوەی وێنە...';
        file.previewElement.appendChild(processingMessage);
        
        // Process the file before accepting
        preProcessFile(file).then(processedFile => {
            // Remove processing message
            if (processingMessage.parentNode) {
                processingMessage.parentNode.removeChild(processingMessage);
            }
            
            // If the file was compressed, update the original file with new data
            if (processedFile !== file) {
                // Copy the processed data to the original file to maintain Dropzone's reference
                file.isCompressed = true;
                file._originalFile = processedFile;
                
                // Add a visual indicator showing compression savings
                if (processedFile.size < file.size) {
                    const savings = file.size - processedFile.size;
                    const percentSaved = Math.round((savings / file.size) * 100);
                    
                    // Add compression info to the file preview
                    const compressionInfo = document.createElement('div');
                    compressionInfo.className = 'compression-info';
                    compressionInfo.innerHTML = `<span class="compression-badge">-${percentSaved}%</span>`;
                    file.previewElement.appendChild(compressionInfo);
                }
            }
            
            done(); // Accept the file
        }).catch(error => {
            console.error('Error in file preprocessing:', error);
            done(); // Accept the file despite error
        });
    },
    init: function() {
        this.on("sending", function(file, xhr, formData) {
            // If file was compressed, send the compressed version instead
            if (file.isCompressed && file._originalFile) {
                // Create a new FormData object
                const newFormData = new FormData();
                
                // Append the compressed file
                newFormData.append("file", file._originalFile, file.name);
                
                // Add client_compressed flag
                newFormData.append("client_compressed", "true");
                
                // Copy any other form data
                for (let pair of formData.entries()) {
                    if (pair[0] !== "file") {
                        newFormData.append(pair[0], pair[1]);
                    }
                }
                
                // Replace xhr.send to use our new FormData
                const originalSend = xhr.send;
                xhr.send = function() {
                    originalSend.call(xhr, newFormData);
                };
            } else if (file.isCompressed) {
                // Just add the flag if file was processed but not replaced
                formData.append("client_compressed", "true");
            }
        });
        
        this.on("success", function(file, response) {
            try {
                // Check if response is already an object
                let data = response;
                if (typeof response === 'string') {
                    data = JSON.parse(response);
                }
                
                if (data.success) {
                    uploadedFiles.push(data.file_path);
                    $("#receipt_files").val(JSON.stringify(uploadedFiles));
                    file.previewElement.classList.add("dz-success");
                } else {
                    console.error("Server error:", data.message);
                    file.previewElement.classList.add("dz-error");
                    
                    // Show error message
                    const errorDisplay = document.createElement('div');
                    errorDisplay.className = 'dz-error-message';
                    errorDisplay.textContent = data.message || 'هەڵەیەک ڕوویدا';
                    file.previewElement.appendChild(errorDisplay);
                }
            } catch (e) {
                console.error("Error handling response:", e);
                file.previewElement.classList.add("dz-error");
                
                // Show a more helpful error message for debugging
                console.error("Raw response:", response);
                
                // Try to extract error message if it's HTML
                let errorMsg = 'هەڵەیەک ڕوویدا لە سێرڤەر';
                if (typeof response === 'string' && response.includes('<b>')) {
                    // Extract error from PHP error output format
                    const match = response.match(/<b>.*?<\/b>:(.*?)<br/);
                    if (match && match[1]) {
                        errorMsg = match[1].trim();
                    }
                }
                
                // Show error message
                const errorDisplay = document.createElement('div');
                errorDisplay.className = 'dz-error-message';
                errorDisplay.textContent = errorMsg;
                file.previewElement.appendChild(errorDisplay);
            }
        });
        
        this.on("removedfile", function(file) {
            if (file.xhr) {
                try {
                    const response = JSON.parse(file.xhr.response);
                    if (response.file_path) {
                        const index = uploadedFiles.indexOf(response.file_path);
                        if (index > -1) {
                            uploadedFiles.splice(index, 1);
                            $("#receipt_files").val(JSON.stringify(uploadedFiles));
                            
                            $.ajax({
                                url: "../process/transactions/delete_receipt.php",
                                type: "POST",
                                data: { file_path: response.file_path },
                                success: function(response) {
                                    console.log("File deleted from server");
                                },
                                error: function(xhr, status, error) {
                                    console.error("Error deleting file:", error);
                                }
                            });
                        }
                    }
                } catch (e) {
                    console.error("Error handling removed file:", e);
                }
            }
        });
    }
});

let editMyDropzone = new Dropzone("#edit-receipt-dropzone", {
    url: "../process/transactions/upload_receipt.php",
    paramName: "file",
    maxFilesize: 10, // MB
    acceptedFiles: "image/*",
    addRemoveLinks: true,
    dictDefaultMessage: "وێنەکان بۆ ئەپلۆدکردن بکێشە ئێرە یان کلیک لێرە بکە",
    dictRemoveFile: "سڕینەوە",
    dictCancelUpload: "هەڵوەشاندنەوەی ئەپلۆد",
    autoProcessQueue: true,
    accept: function(file, done) {
        // Show processing message
        const processingMessage = document.createElement('div');
        processingMessage.className = 'dz-processing-message';
        processingMessage.textContent = 'بچووک کردنەوەی وێنە...';
        file.previewElement.appendChild(processingMessage);
        
        // Process the file before accepting
        preProcessFile(file).then(processedFile => {
            // Remove processing message
            if (processingMessage.parentNode) {
                processingMessage.parentNode.removeChild(processingMessage);
            }
            
            // If the file was compressed, update the original file with new data
            if (processedFile !== file) {
                // Copy the processed data to the original file to maintain Dropzone's reference
                file.isCompressed = true;
                file._originalFile = processedFile;
                
                // Add a visual indicator showing compression savings
                if (processedFile.size < file.size) {
                    const savings = file.size - processedFile.size;
                    const percentSaved = Math.round((savings / file.size) * 100);
                    
                    // Add compression info to the file preview
                    const compressionInfo = document.createElement('div');
                    compressionInfo.className = 'compression-info';
                    compressionInfo.innerHTML = `<span class="compression-badge">-${percentSaved}%</span>`;
                    file.previewElement.appendChild(compressionInfo);
                }
            }
            
            done(); // Accept the file
        }).catch(error => {
            console.error('Error in file preprocessing:', error);
            done(); // Accept the file despite error
        });
    },
    init: function() {
        this.on("sending", function(file, xhr, formData) {
            // If file was compressed, send the compressed version instead
            if (file.isCompressed && file._originalFile) {
                // Create a new FormData object
                const newFormData = new FormData();
                
                // Append the compressed file
                newFormData.append("file", file._originalFile, file.name);
                
                // Add client_compressed flag
                newFormData.append("client_compressed", "true");
                
                // Copy any other form data
                for (let pair of formData.entries()) {
                    if (pair[0] !== "file") {
                        newFormData.append(pair[0], pair[1]);
                    }
                }
                
                // Replace xhr.send to use our new FormData
                const originalSend = xhr.send;
                xhr.send = function() {
                    originalSend.call(xhr, newFormData);
                };
            } else if (file.isCompressed) {
                // Just add the flag if file was processed but not replaced
                formData.append("client_compressed", "true");
            }
        });
        
        this.on("success", function(file, response) {
            try {
                // Check if response is already an object
                let data = response;
                if (typeof response === 'string') {
                    data = JSON.parse(response);
                }
                
                if (data.success) {
                    editUploadedFiles.push(data.file_path);
                    $("#edit_receipt_files").val(JSON.stringify(editUploadedFiles));
                    file.previewElement.classList.add("dz-success");
                } else {
                    console.error("Server error:", data.message);
                    file.previewElement.classList.add("dz-error");
                    
                    // Show error message
                    const errorDisplay = document.createElement('div');
                    errorDisplay.className = 'dz-error-message';
                    errorDisplay.textContent = data.message || 'هەڵەیەک ڕوویدا';
                    file.previewElement.appendChild(errorDisplay);
                }
            } catch (e) {
                console.error("Error handling response:", e);
                file.previewElement.classList.add("dz-error");
                
                // Show a more helpful error message for debugging
                console.error("Raw response:", response);
                
                // Try to extract error message if it's HTML
                let errorMsg = 'هەڵەیەک ڕوویدا لە سێرڤەر';
                if (typeof response === 'string' && response.includes('<b>')) {
                    // Extract error from PHP error output format
                    const match = response.match(/<b>.*?<\/b>:(.*?)<br/);
                    if (match && match[1]) {
                        errorMsg = match[1].trim();
                    }
                }
                
                // Show error message
                const errorDisplay = document.createElement('div');
                errorDisplay.className = 'dz-error-message';
                errorDisplay.textContent = errorMsg;
                file.previewElement.appendChild(errorDisplay);
            }
        });
        
        this.on("removedfile", function(file) {
            if (file.xhr) {
                try {
                    const response = JSON.parse(file.xhr.response);
                    if (response.file_path) {
                        const index = editUploadedFiles.indexOf(response.file_path);
                        if (index > -1) {
                            editUploadedFiles.splice(index, 1);
                            $("#edit_receipt_files").val(JSON.stringify(editUploadedFiles));
                            
                            $.ajax({
                                url: "../process/transactions/delete_receipt.php",
                                type: "POST",
                                data: { file_path: response.file_path },
                                success: function(response) {
                                    console.log("File deleted from server");
                                },
                                error: function(xhr, status, error) {
                                    console.error("Error deleting file:", error);
                                }
                            });
                        }
                    }
                } catch (e) {
                    console.error("Error handling removed file:", e);
                }
            }
        });
    }
});

// Handle account type selection
$("#account_type").change(function() {
    const selectedType = $(this).val();
    $(".customer-select-container, .supplier-select-container, .mixed-select-container, .direction-select-container").hide();
    
    if (selectedType === "customer") {
        $(".customer-select-container").show();
        // Check if customer is selected
        if ($("#customer_id").val()) {
            checkAdvanceAndDisableOptions("customer", $("#customer_id").val());
        }
    } else if (selectedType === "supplier") {
        $(".supplier-select-container").show();
        // Check if supplier is selected
        if ($("#supplier_id").val()) {
            checkAdvanceAndDisableOptions("supplier", $("#supplier_id").val());
        }
    } else if (selectedType === "mixed") {
        $(".mixed-select-container, .direction-select-container").show();
        // Check if mixed account is selected
        if ($("#mixed_account_id").val()) {
            checkAdvanceAndDisableOptions("mixed", $("#mixed_account_id").val());
        }
    }
});

// Handle edit account type selection
$("#edit_account_type").change(function() {
    const selectedType = $(this).val();
    $(".edit-customer-select-container, .edit-supplier-select-container, .edit-mixed-select-container, .edit-direction-select-container").hide();
    
    if (selectedType === "customer") {
        $(".edit-customer-select-container").show();
        // Check if customer is selected
        if ($("#edit_customer_id").val()) {
            checkAdvanceAndDisableOptions("customer", $("#edit_customer_id").val());
        }
    } else if (selectedType === "supplier") {
        $(".edit-supplier-select-container").show();
        // Check if supplier is selected
        if ($("#edit_supplier_id").val()) {
            checkAdvanceAndDisableOptions("supplier", $("#edit_supplier_id").val());
        }
    } else if (selectedType === "mixed") {
        $(".edit-mixed-select-container, .edit-direction-select-container").show();
        // Check if mixed account is selected
        if ($("#edit_mixed_account_id").val()) {
            checkAdvanceAndDisableOptions("mixed", $("#edit_mixed_account_id").val());
        }
    }
});

// Load transactions for the current page
function loadTransactions() {
    // First check permissions
    Promise.all([
        fetch('../includes/check_permission.php?check=edit_transaction').then(response => response.json()),
        fetch('../includes/check_permission.php?check=delete_transaction').then(response => response.json())
    ]).then(([editPerm, deletePerm]) => {
        const canEdit = editPerm.success && editPerm.has_permission;
        const canDelete = deletePerm.success && deletePerm.has_permission;
        
        $.ajax({
            url: "../process/transactions/get_transactions.php",
            type: "GET",
            data: {
                page: currentPage,
                per_page: perPage,
                ...searchParams
            },
            dataType: "json",
            success: function(data) {
                if (data.success) {
                    // Clear the table
                    $("#transactions_table").empty();
                    
                    // Show message if no transactions
                    if (data.transactions.length === 0) {
                        $("#transactions_table").html('<tr><td colspan="9" class="text-center text-muted">هیچ مامەڵيەک نییە</td></tr>');
                    } else {
                        // Add transactions to the table
                        data.transactions.forEach(function(transaction, index) {
                            const rowNumber = (currentPage - 1) * perPage + index + 1;
                            
                            // Format transaction type
                            let typeText = "";
                            if (transaction.type === "cash") {
                                typeText = "نەقد";
                            } else if (transaction.type === "credit") {
                                typeText = "قەرز";
                            } else if (transaction.type === "advance") {
                                typeText = "پێشەکی";
                            } else if (transaction.type === "payment") {
                                typeText = "قەرز دانەوە";
                            } else if (transaction.type === "collection") {
                                typeText = "قەرز وەرگرتنەوە";
                            } else if (transaction.type === "advance_refund") {
                                typeText = "گەڕاندنەوەی پێشەکی";
                            } else if (transaction.type === "advance_collection") {
                                typeText = "پێشەکی وەرگرتنەوە";
                            }
                            
                            // Format account type and name
                            let accountType = "";
                            let accountName = "";
                            let originalAccountName = ""; // Store original name without direction
                            
                            if (transaction.customer_id) {
                                accountType = "کڕیار";
                                accountName = transaction.customer_name;
                                originalAccountName = accountName;
                            } else if (transaction.supplier_id) {
                                accountType = "دابینکەر";
                                accountName = transaction.supplier_name;
                                originalAccountName = accountName;
                            } else if (transaction.mixed_account_id) {
                                accountType = "هەژماری تێکەڵ";
                                accountName = transaction.mixed_account_name;
                                originalAccountName = accountName;
                                
                                // Add direction to display name
                                if (transaction.direction === "sale") {
                                    accountName += " (فرۆشتن)";
                                } else if (transaction.direction === "purchase") {
                                    accountName += " (کڕین)";
                                } else if (transaction.direction === "advance_give") {
                                    accountName += " (پێشەکی دان)";
                                } else if (transaction.direction === "advance_receive") {
                                    accountName += " (پێشەکی وەرگرتن)";
                                }
                            }
                            
                            // Helper to show dash for empty
                            function showDash(val) {
                                return (val === undefined || val === null || val === "") ? "-" : val;
                            }
                            
                            // Check if there are receipt files
                            let receiptButton = '';
                            if (transaction.receipt_files && transaction.receipt_files.length > 0) {
                                receiptButton = `<button type="button" class="action-btn view view-receipts" data-transaction-id="${transaction.id}">
                                    <i class="bi bi-image"></i>
                                </button>`;
                            } else {
                                receiptButton = '<span class="text-muted">هیچ پسووڵەیەک نییە</span>';
                            }
                            
                            // Action buttons with permission checks
                            let actionButtons = `
                                <div class="d-flex gap-1 justify-content-center">
                                    ${canEdit ? `<button type="button" class="action-btn edit edit-transaction" data-transaction-id="${transaction.id}">
                                        <i class="bi bi-pencil"></i>
                                    </button>` : ''}
                                    ${canDelete ? `<button type="button" class="action-btn delete delete-transaction" data-transaction-id="${transaction.id}">
                                        <i class="bi bi-trash"></i>
                                    </button>` : ''}
                                    <button type="button" class="action-btn pdf generate-pdf" data-transaction-id="${transaction.id}" title="پسووڵە بە PDF">
                                        <i class="bi bi-file-pdf"></i>
                                    </button>
                                </div>
                            `;
                            
                            // Add row to table
                            $("#transactions_table").append(`
                                <tr>
                                    <td>${rowNumber}</td>
                                    <td>${showDash(typeText)}</td>
                                    <td>${showDash(parseFloat(transaction.amount).toLocaleString())} د.ع</td>
                                    <td>${showDash(transaction.date)}${transaction.due_date ? '<br><small class="text-muted">بەرواری گەڕاندنەوە: ' + transaction.due_date + '</small>' : ''}</td>
                                    <td data-account-name="${originalAccountName}">${showDash(accountName)}</td>
                                    <td>${showDash(accountType)}</td>
                                    <td>${showDash(transaction.notes)}</td>
                                    <td>${receiptButton}</td>
                                    <td>${actionButtons}</td>
                                </tr>
                            `);
                        });
                    }
                    
                    // Update pagination
                    totalPages = Math.ceil(data.total_count / perPage);
                    updatePagination();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە!',
                        text: data.message,
                        confirmButtonText: 'باشە'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە!',
                    text: 'هەڵەیەک ڕوویدا لە کاتی پەیوەندیکردن بە سێرڤەر.',
                    confirmButtonText: 'باشە'
                });
            }
        });
    }).catch(error => {
        console.error('Error checking permissions:', error);
        
        // Fall back to loading without permission checks
        $.ajax({
            url: "../process/transactions/get_transactions.php",
            type: "GET",
            data: {
                page: currentPage,
                per_page: perPage,
                ...searchParams
            },
            dataType: "json",
            success: function(data) {
                if (data.success) {
                    // Similar code but without edit/delete buttons
                    // ...
                }
            }
        });
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
            loadTransactions();
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
            loadTransactions();
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
            loadTransactions();
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
            loadTransactions();
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
            loadTransactions();
        });
    }
    pagination.append(nextBtn);
}

// Handle search fields
$(".search-field").on("input", function() {
    const fieldId = $(this).attr("id");
    const value = $(this).val().trim();
    
    // Extract field name from the ID
    let fieldName = fieldId.replace("search_", "");
    
    // Special handling for account field to match the PHP query parameter
    if (fieldName === "account" && value) {
        searchParams["account"] = value;
    } else if (value) {
        searchParams[fieldName] = value;
    } else {
        delete searchParams[fieldName];
    }
    
    // Reset to first page and reload with server-side filtering
    currentPage = 1;
    loadTransactions();
});

// Handle per page change
$("#per_page").change(function() {
    perPage = parseInt($(this).val());
    currentPage = 1;
    loadTransactions();
});

// --- Fix: Clear direction if not needed before submit ---
function clearDirectionIfNotNeeded() {
    const accountType = $('#account_type').val();
    const type = $('#type').val();
    // Only clear direction for mixed accounts when NOT cash, credit, or advance
    if (accountType === 'mixed' && type !== 'cash' && type !== 'credit' && type !== 'advance') {
        $('#direction').val('');
    }
}
function clearEditDirectionIfNotNeeded() {
    const accountType = $('#edit_account_type').val();
    const type = $('#edit_type').val();
    // Only clear direction for mixed accounts when NOT cash, credit, or advance
    if (accountType === 'mixed' && type !== 'cash' && type !== 'credit' && type !== 'advance') {
        $('#edit_direction').val('');
    }
}

// Add Transaction
$("#saveTransactionAddBtn").click(function() {
    clearDirectionIfNotNeeded();
    const formData = new FormData(document.getElementById("transactionAddForm"));
    
    $.ajax({
        url: "../process/transactions/add_transaction.php",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json",
        success: function(data) {
            if (data.success) {
                // Reset form
                $("#transactionAddForm")[0].reset();
                // Don't remove files from dropzone
                uploadedFiles = [];
                $("#receipt_files").val("");
                
                // Close modal
                $("#transactionAddModal").modal("hide");
                
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'سەرکەوتوو بوو!',
                    text: data.message,
                    confirmButtonText: 'باشە'
                });
                
                // Reload transactions
                loadTransactions();
                
                // Refresh the SELECT2 filters after adding a transaction
                const filterConfig = {
                    '#filter_account_name': 4,  // Account name column
                    '#filter_transaction_type': 1, // Transaction type column
                    '#filter_account_type_select2': 5 // Account type column
                };
                refreshFilters(filterConfig);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە!',
                    text: data.message,
                    confirmButtonText: 'باشە'
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'هەڵە!',
                text: 'هەڵەیەک ڕوویدا لە کاتی پەیوەندیکردن بە سێرڤەر.',
                confirmButtonText: 'باشە'
            });
        }
    });
});

// Load Transaction for Edit
$(document).on("click", ".edit-transaction", function() {
    const transactionId = $(this).data("transaction-id");
    
    $.ajax({
        url: "../process/transactions/get_transaction.php",
        type: "GET",
        data: { transaction_id: transactionId },
        dataType: "json",
        success: function(data) {
            if (data.success) {
                const transaction = data.transaction;
                
                // Set form values
                $("#edit_transaction_id").val(transaction.id);
                $("#edit_type").val(transaction.type);
                $("#edit_amount").val(transaction.amount);
                $("#edit_date").val(transaction.date);
                $("#edit_due_date").val(transaction.due_date);
                $("#edit_notes").val(transaction.notes);
                
                // Set account type and related fields
                $("#edit_account_type").val("");
                $("#edit_customer_id, #edit_supplier_id, #edit_mixed_account_id").val("");
                $("#edit_direction").val("");
                
                $(".edit-customer-select-container, .edit-supplier-select-container, .edit-mixed-select-container, .edit-direction-select-container").hide();
                
                // Show/hide due date field based on transaction type
                if (transaction.type === 'credit') {
                    $('.edit-due-date-container').show();
                } else {
                    $('.edit-due-date-container').hide();
                }

                if (transaction.customer_id) {
                    $("#edit_account_type").val("customer");
                    $("#edit_customer_id").val(transaction.customer_id);
                    $(".edit-customer-select-container").show();
                } else if (transaction.supplier_id) {
                    $("#edit_account_type").val("supplier");
                    $("#edit_supplier_id").val(transaction.supplier_id);
                    $(".edit-supplier-select-container").show();
                } else if (transaction.mixed_account_id) {
                    $("#edit_account_type").val("mixed");
                    $("#edit_mixed_account_id").val(transaction.mixed_account_id);
                    $("#edit_direction").val(transaction.direction);
                    $(".edit-mixed-select-container, .edit-direction-select-container").show();
                }
                
                // Clear previous receipt files in the form
                $(".edit-receipt-images").empty();
                editMyDropzone.removeAllFiles();
                editUploadedFiles = [];
                $("#edit_receipt_files").val("");
                
                // Set existing receipt files
                if (transaction.receipt_files && transaction.receipt_files.length > 0) {
                    $("#existing_receipt_files").val(JSON.stringify(transaction.receipt_files));
                    
                    // Display existing receipt files
                    transaction.receipt_files.forEach(function(filePath, index) {
                        const fileContainer = $(`
                            <div class="receipt-image-container existing-file" data-file-path="${filePath}">
                                <img src="../${filePath}" class="receipt-image" alt="Receipt ${index + 1}">
                                <button type="button" class="remove-receipt" data-file-path="${filePath}">✕</button>
                            </div>
                        `);
                        
                        $(".edit-receipt-images").append(fileContainer);
                    });
                }
                
                // Open the edit modal
                $("#transactionEditModal").modal("show");
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە!',
                    text: data.message,
                    confirmButtonText: 'باشە'
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'هەڵە!',
                text: 'هەڵەیەک ڕوویدا لە کاتی پەیوەندیکردن بە سێرڤەر.',
                confirmButtonText: 'باشە'
            });
        }
    });
});

// Handle removing existing receipt files
$(document).on("click", ".remove-receipt", function() {
    const filePath = $(this).data("file-path");
    const container = $(this).closest(".receipt-image-container");
    
    // Remove from DOM
    container.remove();
    
    // Update existing files list
    let existingFiles = [];
    if ($("#existing_receipt_files").val()) {
        existingFiles = JSON.parse($("#existing_receipt_files").val());
    }
    
    const index = existingFiles.indexOf(filePath);
    if (index > -1) {
        existingFiles.splice(index, 1);
        $("#existing_receipt_files").val(JSON.stringify(existingFiles));
    }
});

// Update Transaction
$("#saveTransactionEditBtn").click(function() {
    clearEditDirectionIfNotNeeded();
    const formData = new FormData(document.getElementById("transactionEditForm"));
    
    $.ajax({
        url: "../process/transactions/update_transaction.php",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json",
        success: function(data) {
            if (data.success) {
                // Close modal
                $("#transactionEditModal").modal("hide");
                
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'سەرکەوتوو بوو!',
                    text: data.message,
                    confirmButtonText: 'باشە'
                });
                
                // Reload transactions
                loadTransactions();
                
                // Refresh the SELECT2 filters after editing a transaction
                const filterConfig = {
                    '#filter_account_name': 4,  // Account name column
                    '#filter_transaction_type': 1, // Transaction type column
                    '#filter_account_type_select2': 5 // Account type column
                };
                refreshFilters(filterConfig);
            } else {
                console.error('Update Transaction Error:', data);
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە!',
                    text: data.message,
                    confirmButtonText: 'باشە'
                });
            }
        },
        error: function(xhr, status, error) {
            // Debug: log the form data and server response
            console.error('AJAX Error:', status, error);
            if (xhr && xhr.responseText) {
                console.error('Server Response:', xhr.responseText);
            }
            // Log form data
            for (let pair of formData.entries()) {
                console.log(pair[0]+ ': ' + pair[1]);
            }
            Swal.fire({
                icon: 'error',
                title: 'هەڵە!',
                text: 'هەڵەیەک ڕوویدا لە کاتی پەیوەندیکردن بە سێرڤەر.',
                confirmButtonText: 'باشە'
            });
        }
    });
});

// Delete Transaction
$(document).on("click", ".delete-transaction", function() {
    const transactionId = $(this).data("transaction-id");
    
    deleteTransaction(transactionId);
});

// View Receipt Images
$(document).on("click", ".view-receipts", function() {
    const transactionId = $(this).data("transaction-id");
    
    $.ajax({
        url: "../process/transactions/get_transaction.php",
        type: "GET",
        data: { transaction_id: transactionId },
        dataType: "json",
        success: function(data) {
            if (data.success && data.transaction.receipt_files.length > 0) {
                // Clear existing slides
                $("#receipt-carousel .carousel-inner").empty();
                
                // Add receipt images to carousel
                data.transaction.receipt_files.forEach(function(filePath, index) {
                    $("#receipt-carousel .carousel-inner").append(`
                        <div class="carousel-item ${index === 0 ? 'active' : ''}">
                            <img src="../${filePath}" class="d-block w-100" alt="Receipt ${index + 1}">
                        </div>
                    `);
                });
                
                // Show the modal
                $("#receiptViewModal").modal("show");
            } else {
                Swal.fire({
                    icon: 'info',
                    title: 'سەرنج',
                    text: 'هیچ پسووڵەیەک بۆ ئەم مامەڵەیە نییە.',
                    confirmButtonText: 'باشە'
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'هەڵە!',
                text: 'هەڵەیەک ڕوویدا لە کاتی پەیوەندیکردن بە سێرڤەر.',
                confirmButtonText: 'باشە'
            });
        }
    });
});

// Generate PDF Receipt
$(document).on("click", ".generate-pdf", function() {
    const transactionId = $(this).data("transaction-id");
    window.open(`../process/transactions/generate_pdf.php?id=${transactionId}`, '_blank');
});

// Load transactions on page load
$(document).ready(function() {
    updateFilterOptions();
    loadTransactions();
});

// Handle account type and transaction type change
$('#type, #account_type').on('change', function() {
    updateFormFields();
    
    // Check if transaction type is advance refund or collection
    const type = $('#type').val();
    if (type === 'advance_refund' || type === 'advance_collection') {
        const accountType = $('#account_type').val();
        let accountId = null;
        
        if (accountType === 'customer') {
            accountId = $('#customer_id').val();
        } else if (accountType === 'supplier') {
            accountId = $('#supplier_id').val();
        } else if (accountType === 'mixed') {
            accountId = $('#mixed_account_id').val();
        }
        
        if (accountId) {
            checkAdvanceAndDisableOptions(accountType, accountId);
        }
    }
});

// Also apply to edit form
$('#edit_type, #edit_account_type').on('change', function() {
    updateEditFormFields();
    
    // Check if transaction type is advance refund or collection
    const type = $('#edit_type').val();
    if (type === 'advance_refund' || type === 'advance_collection') {
        const accountType = $('#edit_account_type').val();
        let accountId = null;
        
        if (accountType === 'customer') {
            accountId = $('#edit_customer_id').val();
        } else if (accountType === 'supplier') {
            accountId = $('#edit_supplier_id').val();
        } else if (accountType === 'mixed') {
            accountId = $('#edit_mixed_account_id').val();
        }
        
        if (accountId) {
            checkAdvanceAndDisableOptions(accountType, accountId);
        }
    }
});

function updateFormFields() {
    const type = $('#type').val();
    const accountType = $('#account_type').val();
    
    // Hide all account selects first
    $('.customer-select-container, .supplier-select-container, .mixed-select-container, .direction-select-container').hide();
    
    // Show relevant account select based on account type
    if (accountType === 'customer') {
        $('.customer-select-container').show();
    } else if (accountType === 'supplier') {
        $('.supplier-select-container').show();
    } else if (accountType === 'mixed') {
        $('.mixed-select-container').show();
        $('.direction-select-container').show();
    }
    
    // Add logic for payment and collection types
    if (type === 'payment' || type === 'collection') {
        // These types require selecting an account since they're about paying/collecting debts
        if (!accountType) {
            $('#account_type').addClass('is-invalid');
        } else {
            $('#account_type').removeClass('is-invalid');
        }
    }
}

function updateEditFormFields() {
    const type = $('#edit_type').val();
    const accountType = $('#edit_account_type').val();
    
    // Hide all account selects first
    $('.edit-customer-select-container, .edit-supplier-select-container, .edit-mixed-select-container, .edit-direction-select-container').hide();
    
    // Show relevant account select based on account type
    if (accountType === 'customer') {
        $('.edit-customer-select-container').show();
    } else if (accountType === 'supplier') {
        $('.edit-supplier-select-container').show();
    } else if (accountType === 'mixed') {
        $('.edit-mixed-select-container').show();
        $('.edit-direction-select-container').show();
    }
    
    // Add logic for payment and collection types
    if (type === 'payment' || type === 'collection') {
        // These types require selecting an account since they're about paying/collecting debts
        if (!accountType) {
            $('#edit_account_type').addClass('is-invalid');
        } else {
            $('#edit_account_type').removeClass('is-invalid');
        }
    }
}

function renderTransactions(transactions) {
    var $table = $('#transactions_table');
    $table.empty();
    if (transactions.length === 0) {
        $table.append('<tr><td colspan="9" class="text-center text-muted">هیچ مامەڵيەک نییە</td></tr>');
    } else {
        // ... render transactions as rows ...
    }
}

// Add a new function to check advance payment and disable payment/collection options
function checkAdvanceAndDisableOptions(accountType, accountId) {
    if (!accountType || !accountId) return;
    
    let apiEndpoint = "";
    if (accountType === "customer") {
        apiEndpoint = "../process/customers/select.php?id=" + accountId;
    } else if (accountType === "supplier") {
        apiEndpoint = "../process/suppliers/select.php?id=" + accountId;
    } else if (accountType === "mixed") {
        apiEndpoint = "../process/mixed_accounts/select.php?id=" + accountId;
    }
    
    // If we have an endpoint, fetch the account data
    if (apiEndpoint) {
        fetch(apiEndpoint)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    const account = data.data[0];
                    let hasAdvance = false;
                    let hasDebt = false;
                    let advanceAmount = 0;
                    
                    if (accountType === "customer") {
                        hasAdvance = account.advance_payment > 0;
                        hasDebt = account.owed_amount > 0;
                        advanceAmount = parseFloat(account.advance_payment || 0);
                    } else if (accountType === "supplier") {
                        hasAdvance = account.advance_payment > 0;
                        hasDebt = account.we_owe > 0;
                        advanceAmount = parseFloat(account.advance_payment || 0);
                    } else if (accountType === "mixed") {
                        // For mixed accounts, we need to check both directions
                        const direction = $("#direction").val() || $("#edit_direction").val();
                        
                        // Check both sale and purchase balances
                        const hasTheyOwe = account.they_owe > 0;
                        const hasWeOwe = account.we_owe > 0;
                        const hasTheyAdvance = account.they_advance > 0;
                        const hasWeAdvance = account.we_advance > 0;
                        
                        if (direction === "sale") {
                            // In sale direction:
                            // - they_owe means they have debt to us
                            // - we_advance means we have advance to them
                            hasDebt = hasTheyOwe;
                            hasAdvance = hasWeAdvance;
                            advanceAmount = parseFloat(account.we_advance || 0);
                        } else if (direction === "purchase") {
                            // In purchase direction:
                            // - we_owe means we have debt to them
                            // - they_advance means they have advance to us
                            hasDebt = hasWeOwe;
                            hasAdvance = hasTheyAdvance;
                            advanceAmount = parseFloat(account.they_advance || 0);
                        } else {
                            // If no direction selected, enable options if any debt or advance exists
                            hasDebt = hasTheyOwe || hasWeOwe;
                            hasAdvance = hasTheyAdvance || hasWeAdvance;
                            advanceAmount = parseFloat(account.they_advance || 0) + parseFloat(account.we_advance || 0);
                        }
                    }

                    const isAddForm = $("#transactionAddModal").hasClass('show');
                    const typeSelector = isAddForm ? $("#type") : $("#edit_type");
                    const amountField = isAddForm ? $("#amount") : $("#edit_amount");
                    
                    const paymentOption = typeSelector.find('option[value="payment"]');
                    const collectionOption = typeSelector.find('option[value="collection"]');
                    const advanceOption = typeSelector.find('option[value="advance"]');
                    const advanceRefundOption = typeSelector.find('option[value="advance_refund"]');
                    const advanceCollectionOption = typeSelector.find('option[value="advance_collection"]');

                    if (accountType === "mixed") {
                        const direction = $("#direction").val() || $("#edit_direction").val();
                        
                        // Enable all options by default for mixed accounts
                        paymentOption.prop('disabled', false);
                        collectionOption.prop('disabled', false);
                        advanceOption.prop('disabled', false);
                        advanceRefundOption.prop('disabled', false);
                        advanceCollectionOption.prop('disabled', false);
                        
                        if (direction === "sale") {
                            // For sale direction:
                            // - Enable payment/collection if they owe us
                            // - Enable advance_refund if we have advance to them
                            paymentOption.prop('disabled', !hasTheyOwe);
                            collectionOption.prop('disabled', !hasTheyOwe);
                            advanceRefundOption.prop('disabled', !hasWeAdvance);
                            advanceCollectionOption.prop('disabled', true); // No advance collection in sale direction for mixed
                        } else if (direction === "purchase") {
                            // For purchase direction:
                            // - Enable payment/collection if we owe them
                            // - Enable advance_collection if they have advance to us
                            paymentOption.prop('disabled', !hasWeOwe);
                            collectionOption.prop('disabled', !hasWeOwe);
                            advanceRefundOption.prop('disabled', true); // No advance refund in purchase direction for mixed
                            advanceCollectionOption.prop('disabled', !hasTheyAdvance);
                        }
                    } else {
                        // Original logic for customers and suppliers
                        if (accountType === "customer") {
                            paymentOption.prop('disabled', true); // Disable payment for customers
                            collectionOption.prop('disabled', !hasDebt); // Enable collection only if there is debt
                            advanceRefundOption.prop('disabled', !hasAdvance); // Keep advance refund logic
                            advanceCollectionOption.prop('disabled', true); // Disable advance collection for customers
                        } else if (accountType === "supplier") {
                            paymentOption.prop('disabled', !hasDebt); // Enable payment only if there is debt
                            collectionOption.prop('disabled', true); // Disable collection for suppliers
                            advanceRefundOption.prop('disabled', true); // Disable advance refund for suppliers
                            advanceCollectionOption.prop('disabled', !hasAdvance); // Keep advance collection logic (they have advance to us)
                        }
                        
                        // Disable advance if there is debt
                        advanceOption.prop('disabled', hasDebt);
                        if (typeSelector.val() === "advance" && hasDebt) {
                            typeSelector.val("");
                        }
                    }
                    
                    // Store advance amount as data attribute for validation
                    amountField.data('max-advance', advanceAmount);
                    
                    // Add event handler for amount validation for refund/collection
                    amountField.off('change.advanceValidate').on('change.advanceValidate', function() {
                        const currentType = typeSelector.val();
                        const enteredAmount = parseFloat($(this).val() || 0);
                        
                        if ((currentType === 'advance_refund' || currentType === 'advance_collection') && enteredAmount > advanceAmount) {
                            Swal.fire({
                                icon: 'error',
                                title: 'هەڵە!',
                                text: `بڕی داخڵکراو ناتوانێت لە بڕی پێشەکی بەردەست (${advanceAmount}) زیاتر بێت`,
                                confirmButtonText: 'باشە'
                            });
                            $(this).val(advanceAmount);
                        }
                    });
                }
            })
            .catch(error => {
                console.error("Error fetching account data:", error);
            });
    }
}

// Modify customer, supplier and mixed account select change handlers
$("#customer_id, #supplier_id, #mixed_account_id").on('change', function() {
    const accountType = $("#account_type").val();
    const accountId = $(this).val();
    
    checkAdvanceAndDisableOptions(accountType, accountId);
});

// Modify edit form customer, supplier and mixed account select change handlers
$("#edit_customer_id, #edit_supplier_id, #edit_mixed_account_id").on('change', function() {
    const accountType = $("#edit_account_type").val();
    const accountId = $(this).val();
    
    checkAdvanceAndDisableOptions(accountType, accountId);
});

// Also check when edit modal is shown
$('#transactionEditModal').on('shown.bs.modal', function () {
    const accountType = $("#edit_account_type").val();
    let accountId = null;
    
    if (accountType === "customer") {
        accountId = $("#edit_customer_id").val();
    } else if (accountType === "supplier") {
        accountId = $("#edit_supplier_id").val();
    } else if (accountType === "mixed") {
        accountId = $("#edit_mixed_account_id").val();
    }
    
    if (accountType && accountId) {
        checkAdvanceAndDisableOptions(accountType, accountId);
    }
});

// Handle direction change for mixed accounts
$('#direction, #edit_direction').on('change', function() {
    const isAddForm = $(this).attr('id') === 'direction';
    const accountType = isAddForm ? $('#account_type').val() : $('#edit_account_type').val();
    let accountId = null;
    
    if (accountType === 'mixed') {
        accountId = isAddForm ? $('#mixed_account_id').val() : $('#edit_mixed_account_id').val();
        
        if (accountId) {
            checkAdvanceAndDisableOptions(accountType, accountId);
        }
    }
});

// Load the filter dropdown with transaction types
function updateFilterOptions() {
    // Add options to filter_type dropdown
    const typeOptions = [
        { value: '', text: 'هەموو' },
        { value: 'cash', text: 'نەقد' },
        { value: 'credit', text: 'قەرز' },
        { value: 'advance', text: 'پێشەکی' },
        { value: 'payment', text: 'قەرز دانەوە' },
        { value: 'collection', text: 'قەرز وەرگرتنەوە' },
        { value: 'advance_refund', text: 'گەڕاندنەوەی پێشەکی' },
        { value: 'advance_collection', text: 'پێشەکی وەرگرتنەوە' }
    ];
    
    const filterTypeSelect = $('#filter_type');
    const currentValue = filterTypeSelect.val();
    
    // Clear existing options
    filterTypeSelect.empty();
    
    // Add new options
    typeOptions.forEach(option => {
        filterTypeSelect.append(`<option value="${option.value}">${option.text}</option>`);
    });
    
    // Restore selected value if it was set
    if (currentValue) {
        filterTypeSelect.val(currentValue);
    }
}

// Add delete transaction function
function deleteTransaction(transactionId) {
    Swal.fire({
        title: 'دڵنیای لە سڕینەوەی مامەڵەکە؟',
        text: "ئەم کردارە ناگەڕێتەوە!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'بەڵێ، بیسڕەوە!',
        cancelButtonText: 'پاشگەزبوونەوە'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
                title: 'سڕینەوە...',
                text: 'تکایە چاوەڕوان بکە',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Create form data
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('transaction_id', transactionId);

            // Send AJAX request
            $.ajax({
                url: '../process/transactions/delete_transaction_function.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    // Close loading state
                    Swal.close();
                    
                    // Check if response is already an object
                    let data = response;
                    if (typeof response === 'string') {
                        try {
                            data = JSON.parse(response);
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            Swal.fire({
                                title: 'هەڵە!',
                                text: 'هەڵەیەک ڕوویدا لە کاتی سڕینەوەی مامەڵەکە',
                                icon: 'error'
                            });
                            return;
                        }
                    }
                    
                    if (data.success) {
                        Swal.fire({
                            title: 'سڕایەوە!',
                            text: data.message,
                            icon: 'success'
                        }).then(() => {
                            // Refresh the transactions table
                            loadTransactions();
                        });
                    } else {
                        Swal.fire({
                            title: 'هەڵە!',
                            text: data.message || 'هەڵەیەک ڕوویدا لە کاتی سڕینەوەی مامەڵەکە',
                            icon: 'error'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    // Close loading state
                    Swal.close();
                    
                    // Try to parse error response
                    let errorMessage = 'هەڵەیەک ڕوویدا لە کاتی سڕینەوەی مامەڵەکە';
                    
                    if (xhr.responseText) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.message) {
                                errorMessage = response.message;
                            }
                        } catch (e) {
                            // If response is not JSON, try to extract error message from HTML
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = xhr.responseText;
                            const errorText = tempDiv.textContent || tempDiv.innerText;
                            if (errorText) {
                                errorMessage = errorText.trim();
                            }
                        }
                    }
                    
                    Swal.fire({
                        title: 'هەڵە!',
                        text: errorMessage,
                        icon: 'error'
                    });
                }
            });
        }
    });
}

// Function to handle account type change
function handleAccountTypeChange() {
    const accountType = $('#account_type').val();
    const type = $('#type').val();
    // Hide all select containers first
    $('.customer-select-container, .supplier-select-container, .mixed-select-container, .direction-select-container').hide();
    // Show relevant select container based on account type
    if (accountType === 'customer') {
        $('.customer-select-container').show();
    } else if (accountType === 'supplier') {
        $('.supplier-select-container').show();
    } else if (accountType === 'mixed') {
        $('.mixed-select-container').show();
        // For mixed accounts, only show direction for cash, credit, and advance transactions
        if (type === 'cash' || type === 'credit' || type === 'advance') {
            $('.direction-select-container').show();
        } else {
            $('.direction-select-container').hide();
            $('#direction').val('');
        }
    }
}

// Function to handle transaction type change
function handleTransactionTypeChange() {
    const accountType = $('#account_type').val();
    const type = $('#type').val();
    // For mixed accounts, show direction for cash, credit, and advance transactions
    if (accountType === 'mixed') {
        if (type === 'cash' || type === 'credit' || type === 'advance') {
            $('.direction-select-container').show();
            
            // Update direction options based on transaction type
            const directionSelect = $('#direction');
            const currentValue = directionSelect.val();
            
            if (type === 'advance') {
                // For advance transactions, show advance_give and advance_receive options
                directionSelect.html(`
                    <option value="">هەڵبژاردن</option>
                    <option value="advance_give">پێشەکی دان</option>
                    <option value="advance_receive">پێشەکی وەرگرتن</option>
                `);
            } else {
                // For cash and credit transactions, show sale and purchase options
                directionSelect.html(`
                    <option value="">هەڵبژاردن</option>
                    <option value="sale">فرۆشتن</option>
                    <option value="purchase">کڕین</option>
                `);
            }
            
            // Restore previous value if it exists in new options
            if (currentValue && directionSelect.find(`option[value="${currentValue}"]`).length) {
                directionSelect.val(currentValue);
            } else {
                directionSelect.val('');
            }
        } else {
            $('.direction-select-container').hide();
            $('#direction').val('');
        }
    }
    // Show due date field only for credit transactions
    if (type === 'credit') {
        $('.due-date-container').show();
    } else {
        $('.due-date-container').hide();
        $('#due_date').val('');
    }
}

// Function to handle edit account type change
function handleEditAccountTypeChange() {
    const accountType = $('#edit_account_type').val();
    const type = $('#edit_type').val();
    // Hide all select containers first
    $('.edit-customer-select-container, .edit-supplier-select-container, .edit-mixed-select-container, .edit-direction-select-container').hide();
    // Show relevant select container based on account type
    if (accountType === 'customer') {
        $('.edit-customer-select-container').show();
    } else if (accountType === 'supplier') {
        $('.edit-supplier-select-container').show();
    } else if (accountType === 'mixed') {
        $('.edit-mixed-select-container').show();
        // For mixed accounts, only show direction for cash, credit, and advance transactions
        if (type === 'cash' || type === 'credit' || type === 'advance') {
            $('.edit-direction-select-container').show();
        } else {
            $('.edit-direction-select-container').hide();
            $('#edit_direction').val('');
        }
    }
}

// Function to handle edit transaction type change
function handleEditTransactionTypeChange() {
    const accountType = $('#edit_account_type').val();
    const type = $('#edit_type').val();
    // For mixed accounts, show direction for cash, credit, and advance transactions
    if (accountType === 'mixed') {
        if (type === 'cash' || type === 'credit' || type === 'advance') {
            $('.edit-direction-select-container').show();
            
            // Update direction options based on transaction type
            const directionSelect = $('#edit_direction');
            const currentValue = directionSelect.val();
            
            if (type === 'advance') {
                // For advance transactions, show advance_give and advance_receive options
                directionSelect.html(`
                    <option value="">هەڵبژاردن</option>
                    <option value="advance_give">پێشەکی دان</option>
                    <option value="advance_receive">پێشەکی وەرگرتن</option>
                `);
            } else {
                // For cash and credit transactions, show sale and purchase options
                directionSelect.html(`
                    <option value="">هەڵبژاردن</option>
                    <option value="sale">فرۆشتن</option>
                    <option value="purchase">کڕین</option>
                `);
            }
            
            // Restore previous value if it exists in new options
            if (currentValue && directionSelect.find(`option[value="${currentValue}"]`).length) {
                directionSelect.val(currentValue);
            } else {
                directionSelect.val('');
            }
        } else {
            $('.edit-direction-select-container').hide();
            $('#edit_direction').val('');
        }
    }
    // Show due date field only for credit transactions
    if (type === 'credit') {
        $('.edit-due-date-container').show();
    } else {
        $('.edit-due-date-container').hide();
        $('#edit_due_date').val('');
    }
}

// Add event listeners
$(document).ready(function() {
    // Account type change handler
    $('#account_type').on('change', handleAccountTypeChange);
    // Transaction type change handler
    $('#type').on('change', handleTransactionTypeChange);
    // Edit account type change handler
    $('#edit_account_type').on('change', handleEditAccountTypeChange);
    // Edit transaction type change handler
    $('#edit_type').on('change', handleEditTransactionTypeChange);
    // Initial setup
    handleAccountTypeChange();
});

// Also update select2 related filtering functions
function applySelect2Filters() {
    // Get values from SELECT2 filters
    const accountName = $('#filter_account_name').val();
    const transactionType = $('#filter_transaction_type').val();
    const accountType = $('#filter_account_type_select2').val();
    
    // Get date filter values
    const dateFrom = $('#filter_date_from').val();
    const dateTo = $('#filter_date_to').val();
    
    // Map the translated values to backend values for transaction type
    if (transactionType) {
        let originalValue = '';
        switch(transactionType) {
            case 'نەقد': originalValue = 'cash'; break;
            case 'قەرز': originalValue = 'credit'; break;
            case 'پێشەکی': originalValue = 'advance'; break;
            case 'قەرز دانەوە': originalValue = 'payment'; break;
            case 'قەرز وەرگرتنەوە': originalValue = 'collection'; break;
            case 'گەڕاندنەوەی پێشەکی': originalValue = 'advance_refund'; break;
            case 'پێشەکی وەرگرتنەوە': originalValue = 'advance_collection'; break;
            default: originalValue = '';
        }
        searchParams.type = originalValue;
        $('#filter_type').val(originalValue);
    } else {
        delete searchParams.type;
        $('#filter_type').val('');
    }
    
    // Map the translated values to backend values for account type
    if (accountType) {
        let originalValue = '';
        switch(accountType) {
            case 'کڕیار': originalValue = 'customer'; break;
            case 'دابینکەر': originalValue = 'supplier'; break;
            case 'هەژماری تێکەڵ': originalValue = 'mixed'; break;
            default: originalValue = '';
        }
        searchParams.account_type = originalValue;
        $('#filter_account_type').val(originalValue);
    } else {
        delete searchParams.account_type;
        $('#filter_account_type').val('');
    }
    
    // Handle account name filter - just use the name as is
    // Backend will handle partial matching with LIKE operator
    if (accountName) {
        searchParams.account = accountName;
    } else {
        delete searchParams.account;
    }
    
    // Handle date range filters
    if (dateFrom) {
        searchParams.date_from = dateFrom;
    } else {
        delete searchParams.date_from;
    }
    
    if (dateTo) {
        searchParams.date_to = dateTo;
    } else {
        delete searchParams.date_to;
    }
    
    // Reset to first page and reload with server-side filtering
    currentPage = 1;
    loadTransactions();
}

// Update the reset filters function
function resetAllFilters() {
    // Reset SELECT2 filters
    $('.select2-filter').val(null).trigger('change');
    
    // Reset date filters
    $('#filter_date_from, #filter_date_to').val('');
    
    // Reset hidden original filters
    $('#filter_type, #filter_account_type').val('');
    
    // Clear search parameters
    searchParams = {};
    
    // Reset to first page and reload
    currentPage = 1;
    loadTransactions();
}

// Make sure date filters also work
$('#filter_date_from, #filter_date_to').on('change', function() {
    const dateFrom = $('#filter_date_from').val();
    const dateTo = $('#filter_date_to').val();
    
    if (dateFrom) searchParams.date_from = dateFrom;
    else delete searchParams.date_from;
    
    if (dateTo) searchParams.date_to = dateTo;
    else delete searchParams.date_to;
    
    // Reset to first page and reload
    currentPage = 1;
    loadTransactions();
}); 