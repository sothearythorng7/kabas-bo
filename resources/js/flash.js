import { Notyf } from 'notyf';
window.initFlash = function(flashData, errorData = []) {
    const notyf = new Notyf({
        duration: 4000,
        dismissible: true,
        position: { x: 'center', y: 'top' },
    });

    Object.entries(flashData).forEach(([type, message]) => {
        if (message) notyf[type === 'error' ? 'error' : type](message);
    });

    if (errorData.length) {
        errorData.forEach(msg => notyf.error(msg));
    }
}

window.initFlash = initFlash;
