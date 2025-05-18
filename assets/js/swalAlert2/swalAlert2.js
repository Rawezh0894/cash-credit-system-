function showSwalAlert2(type, title, message, redirectUrl = null) {
    // Prevent reload/close while alert is open
    function blockUnload(e) {
        e.preventDefault();
        e.returnValue = '';
        return '';
    }
    window.addEventListener('beforeunload', blockUnload);

    Swal.fire({
        icon: type, // 'success', 'error', 'info', 'warning'
        title: title,
        text: message,
        confirmButtonText: 'باشە',
        allowOutsideClick: false,
        allowEscapeKey: false,
        allowEnterKey: true
    }).then(() => {
        window.removeEventListener('beforeunload', blockUnload);
        if (redirectUrl) {
            window.location.href = redirectUrl;
        }
    });
}
