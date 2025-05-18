function deleteSupplier(id, elem) {
    Swal.fire({
        icon: 'warning',
        title: 'دڵنیایت؟',
        text: 'دڵنیایت دەتەوێت ئەم دابینکەرە بسڕیتەوە؟',
        showCancelButton: true,
        confirmButtonText: 'سڕینەوە',
        cancelButtonText: 'پاشگەزبوونەوە',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('../process/suppliers/delete.php?id=' + id)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        elem.closest('tr').remove();
                    }
                    showSwalAlert2(data.success ? 'success' : 'error', data.success ? 'سەرکەوتوو!' : 'هەڵە!', data.message);
                });
        }
    });
} 