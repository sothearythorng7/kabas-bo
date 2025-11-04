import { Notyf } from 'notyf';
window.initFlash = function(flashData, errorData = []) {
    const notyf = new Notyf({
        duration: 4000,
        dismissible: true,
        position: { x: 'right', y: 'top' },
        types: [
            {
                type: 'success',
                backgroundColor: '#198754',
                icon: {
                    className: 'bi bi-check-circle',
                    tagName: 'i',
                }
            },
            {
                type: 'error',
                backgroundColor: '#dc3545',
                icon: {
                    className: 'bi bi-exclamation-circle',
                    tagName: 'i',
                }
            },
            {
                type: 'warning',
                backgroundColor: '#ffc107',
                icon: {
                    className: 'bi bi-exclamation-triangle',
                    tagName: 'i',
                }
            },
            {
                type: 'info',
                backgroundColor: '#0dcaf0',
                icon: {
                    className: 'bi bi-info-circle',
                    tagName: 'i',
                }
            }
        ]
    });

    Object.entries(flashData).forEach(([type, message]) => {
        if (message) {
            if (type === 'error') {
                notyf.error(message);
            } else if (type === 'success') {
                notyf.success(message);
            } else if (type === 'warning') {
                notyf.open({ type: 'warning', message: message });
            } else if (type === 'info') {
                notyf.open({ type: 'info', message: message });
            }
        }
    });

    if (errorData.length) {
        errorData.forEach(msg => notyf.error(msg));
    }
}

window.initFlash = initFlash;
