import * as bootstrap from 'bootstrap';
window.initMenu = function(menuData) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const menuBtn = document.getElementById('menu-btn');
    const modalEl = document.getElementById('menuModal');
    const menuContainer = document.getElementById('menuContainer');
    const bsModal = new bootstrap.Modal(modalEl, { backdrop: true, keyboard: true });
    const panels = [];

    function createPanel(items) {
        const panel = document.createElement('div');
        panel.className = 'menu-panel';
        panel.style.transform = 'translateX(100%)';
        panel.style.transition = 'transform .28s ease';

        if (panels.length > 0) {
            const backCard = document.createElement('div');
            backCard.className = 'menu-card go-back';
            backCard.innerHTML = '<i class="bi bi-arrow-left"></i><div>Retour</div>';
            backCard.addEventListener('click', goBack);
            panel.appendChild(backCard);
        }

        items.forEach(item => {
            const card = document.createElement('div');
            card.className = 'menu-card';
            card.innerHTML = `<i class="${item.icon || ''}"></i><div>${item.label || ''}</div>`;
            card.addEventListener('click', () => {
                if (item.submenu?.length) return pushPanel(item.submenu);
                if (!item.url) return;

                if ((item.method || 'GET').toUpperCase() !== 'GET') {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = item.url;
                    form.style.display = 'none';
                    form.innerHTML = `<input type="hidden" name="_token" value="${csrfToken}">`;
                    if ((item.method || 'GET').toUpperCase() !== 'POST') {
                        form.innerHTML += `<input type="hidden" name="_method" value="${item.method}">`;
                    }
                    document.body.appendChild(form);
                    form.submit();
                    return;
                }
                window.location.href = item.url;
            });
            panel.appendChild(card);
        });

        return panel;
    }

    function pushPanel(items) {
        const newPanel = createPanel(items);
        menuContainer.appendChild(newPanel);
        const previous = panels.length ? panels[panels.length - 1] : null;
        requestAnimationFrame(() => {
            if (previous) previous.style.transform = 'translateX(-100%)';
            newPanel.style.transform = 'translateX(0)';
        });
        panels.push(newPanel);
    }

    function goBack() {
        if (panels.length < 2) return;
        const current = panels.pop();
        const previous = panels[panels.length - 1];
        current.style.transform = 'translateX(100%)';
        previous.style.transform = 'translateX(0)';
        current.addEventListener('transitionend', function handler() {
            current.removeEventListener('transitionend', handler);
            current.remove();
        });
    }

    menuBtn.addEventListener('click', () => {
        menuContainer.innerHTML = '';
        panels.length = 0;
        pushPanel(menuData);
        bsModal.show();
    });

    modalEl.addEventListener('hidden.bs.modal', () => {
        menuContainer.innerHTML = '';
        panels.length = 0;
    });
}
