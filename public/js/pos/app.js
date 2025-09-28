// public/js/pos/app.js
// --------------------
// POS central app.js
// --------------------

/* global Database, UsersTable, CatalogTable */

const db = new Database();
db.register(new UsersTable());
db.register(new CatalogTable());
db.register(new PaymentsTable());

// Session
let currentUser = null;
let currentShift = null;

// Sales (dashboard)
let sales = [];
let activeSaleId = null;
let saleCounter = 1;

// Categories tree
let categoryTree = {}; // arbre global des catégories
let currentCategoryPath = []; // chemin courant pour navigation dans l'arbre

function calculateAndSaveSale(sale, currentShift) {
    if (!sale || !currentShift) return;

    sale.items.forEach(item => {
        if (!item.product_id && item.id) {
            item.product_id = item.id;
        }

        let lineTotal = item.price * item.quantity;

        if (item.discounts && item.discounts.length > 0) {
            item.discounts.forEach(d => {
                if (d.type === 'amount') lineTotal -= d.value;
                else if (d.type === 'percent') lineTotal -= lineTotal * (d.value / 100);
            });
        }

        item.line_total = Math.max(0, lineTotal); // pour éviter les négatifs
    });
    let totalBeforeGlobalDiscount = sale.items.reduce((sum, item) => sum + item.line_total, 0);
    let discountTotal = 0;
    if (sale.discounts && sale.discounts.length > 0) {
        sale.discounts.forEach(d => {
            if (d.type === 'amount') discountTotal += d.value;
            else if (d.type === 'percent') discountTotal += totalBeforeGlobalDiscount * (d.value / 100);
        });
    }

    sale.discount_total = discountTotal;
    sale.total = Math.max(0, totalBeforeGlobalDiscount - discountTotal);
    const key = `pos_sales_shift_${currentShift.id}`;
    
    let sales = JSON.parse(localStorage.getItem(key)) || [];

    const index = sales.findIndex(s => s.id === sale.id);
    if (index !== -1) sales[index] = sale;
    else sales.push(sale);

    localStorage.setItem(key, JSON.stringify(sales));
}

async function syncSalesToBO() {
    if (!currentShift) return;

    const payload = prepareSalesSync();
    if (!payload || !payload.length) return;

    try {
        const res = await fetch(`http://kabas.dev-back.fr/api/pos/sales/sync`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ shift_id: currentShift.id, sales: payload })
        });

        const data = await res.json();
        if (data.status === 'success') {
            // marquer les ventes locales comme synchronisées
            const key = `pos_sales_validated_shift_${currentShift.id}`;
            const stored = JSON.parse(localStorage.getItem(key)) || [];
            data.synced_sales.forEach(localId => {
                const sale = stored.find(s => s.id === localId);
                if (sale) sale.synced = true;
            });
            localStorage.setItem(key, JSON.stringify(stored));
            console.log("Ventes synchronisées :", data.synced_sales);
        }
    } catch (err) {
        console.error("Erreur synchronisation ventes :", err);
    }
}

// auto-sync toutes les 30s
setInterval(syncSalesToBO, 30000);


function prepareSalesSync() {
    if (!currentShift) {
        console.warn("Aucun shift actif pour synchroniser les ventes.");
        return;
    }

    const key = `pos_sales_validated_shift_${currentShift.id}`;
    const validatedSales = JSON.parse(localStorage.getItem(key)) || [];

    const unsyncedSales = validatedSales.filter(s => !s.synced);

    if (unsyncedSales.length === 0) {
        console.log("Aucune vente en attente de synchronisation.");
        return;
    }

    const payload = unsyncedSales.map(sale => ({
        id: sale.id,
        label: sale.label,
        payment_type: sale.payment_type,
        items: sale.items.map(item => ({
            product_id: item.product_id,
            name: item.name || null,
            ean: item.ean || null,
            price: item.price,
            quantity: item.quantity,
            discounts: item.discounts || []
        })),
        discounts: sale.discounts || [],
        total: calculateSaleTotal(sale)
    }));

    console.log("JSON prêt pour synchronisation :", JSON.stringify(payload, null, 2));
    return payload;
}


// Fonction utilitaire pour calculer le total final d'une vente
function calculateSaleTotal(sale) {
    let total = 0;
    sale.items.forEach(item => {
        let t = item.price * item.quantity;
        if (item.discounts) item.discounts.forEach(d => {
            if (d.type === 'amount') t -= d.value;
            else if (d.type === 'percent') t *= (1 - d.value / 100);
        });
        total += t;
    });
    if (sale.discounts) sale.discounts.forEach(d => {
        if (d.type === 'amount') total -= d.value;
        else if (d.type === 'percent') total *= (1 - d.value / 100);
    });
    return total;
}


function saveSalesToLocal() {
    if (!currentShift) return;
    if (!sales) return;
    const key = `pos_sales_shift_${currentShift.id}`;
    localStorage.removeItem(key);
    // Pour chaque vente, recalculer les totaux et sauvegarder
    sales.forEach(sale => {
        calculateAndSaveSale(sale, currentShift);
    });

    console.log("Ventes sauvegardées dans localStorage pour le shift :", currentShift.id);
}

function loadSalesFromLocal() {
    if (!currentShift) return;
    const key = `pos_sales_shift_${currentShift.id}`;
    const stored = localStorage.getItem(key);
    if (stored) sales = JSON.parse(stored);
}

// helper
function capitalize(str) {
    if (!str) return "";
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// --------------------
// showScreen (sécurisée)
// --------------------
function showScreen(screenId) {
    if (!currentUser && screenId !== "login") {
        screenId = "login";
    }

    $(".pos-screen").addClass("d-none");
    const $screen = $(`#screen-${screenId}`);
    $screen.removeClass("d-none");

    // navbar visibility
    if (screenId === "login") {
        $(".navbar").addClass("d-none");
    } else {
        $(".navbar").removeClass("d-none");
    }

    // logout / end shift button visibility
    if (currentUser) {
        $("#btn-logout").removeClass("d-none");
        if (currentShift) {
            $("#btn-end-shift").removeClass("d-none");
        } else {
            $("#btn-end-shift").addClass("d-none");
        }
    } else {
        $("#btn-logout").addClass("d-none");
        $("#btn-end-shift").addClass("d-none");
    }

    // call init function if exists: init + Capitalize(screenId)
    const initFn = window["init" + capitalize(screenId)];
    if (typeof initFn === "function") {
        setTimeout(() => initFn(), 10);
    }

    // focus convenience: dashboard search
    if (screenId === "dashboard") {
        setTimeout(() => {
            const $s = $("#sale-search");
            if ($s.length) $s.focus();
            setInitialWidths()
        }, 80);
        
    }
}

// --------------------
// CSRF token helper
// --------------------
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

// --------------------
// showNumericModal (global, réutilisable)
// retourne Promise<number> (0 si cancel)
// --------------------
function showNumericModal(title) {
    return new Promise(resolve => {
        $("#numericModal").remove();

        const modalHtml = `
            <div class="modal fade" id="numericModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content text-center p-4">
                        <div class="modal-body">
                            <h5>${title}</h5>
                            <input type="text" id="numericInput" class="form-control mb-3 text-center fs-3" readonly>
                            <div class="row g-2 justify-content-center">
                                ${[1,2,3,4,5,6,7,8,9].map(n => `<div class="col-4"><button class="btn btn-outline-dark btn-lg w-100 num-btn">${n}</button></div>`).join('')}
                                <div class="w-100"></div>
                                <div class="col-4"><button class="btn btn-outline-danger btn-lg w-100" id="num-clear">C</button></div>
                                <div class="col-4"><button class="btn btn-outline-dark btn-lg w-100 num-btn">0</button></div>
                                <div class="col-4"><button class="btn btn-outline-secondary btn-lg w-100" id="num-cancel">Annuler</button></div>
                                <div class="col-4"><button class="btn btn-outline-success btn-lg w-100" id="num-ok">OK</button></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        $("body").append(modalHtml);

        const modalEl = document.getElementById("numericModal");
        const modal = new bootstrap.Modal(modalEl);
        let buffer = "";
        $("#numericInput").val("");

        $(".num-btn").off("click").on("click", function() {
            buffer += $(this).text();
            $("#numericInput").val(buffer);
        });

        $("#num-clear").off("click").on("click", function() {
            buffer = "";
            $("#numericInput").val("");
        });

        $("#num-ok").off("click").on("click", function() {
            modal.hide();
            modalEl.remove();
            resolve(parseFloat(buffer) || 0);
        });

        $("#num-cancel").off("click").on("click", function() {
            modal.hide();
            modalEl.remove();
            resolve(0);
        });

        setTimeout(() => modal.show(), 10);
    });
}

// --------------------
// Build category tree from JSON key `category_tree`
// Retourne un tableau de nodes: [{ id: Number, name: String, children: [...] }]
// --------------------
function buildCategoryTreeFromJson(jsonCategoryTree) {
    function convert(nodes) {
        if (!Array.isArray(nodes)) return [];
        return nodes.map(node => {
            const id = node.id !== undefined ? Number(node.id) : (node._id !== undefined ? Number(node._id) : null);
            const name = node.name || node.title || node.label || "";
            return {
                id: id,
                name: name,
                children: convert(node.children || [])
            };
        });
    }
    return convert(jsonCategoryTree || []);
}

// --------------------
// Navigation et affichage catégories
// --------------------
function renderCategoryButtons(nodes = categoryTree, path = []) {
    const $container = $("#category-buttons");
    $container.empty();

    nodes.forEach(n => {
        const $btn = $(`<button class="btn btn-outline-primary m-1 category-btn" data-id="${n.id}" data-name="${n.name}">${n.name}</button>`);
        $btn.on("click", () => {
            currentCategoryPath = [...path, n.id];
            if (!n.children || n.children.length === 0) {
                showProductsByCategory(currentCategoryPath);
            } else {
                renderCategoryButtons(n.children, currentCategoryPath);
            }
        });
        $container.append($btn);
    });

    if (path.length > 0) {
        const $backBtn = $(`<button class="btn btn-outline-secondary m-1">Retour</button>`);
        $backBtn.on("click", () => {
            const parentPath = path.slice(0, -1);

            let parentNodes = categoryTree;
            for (let i = 0; i < parentPath.length; i++) {
                const id = parentPath[i];
                const found = parentNodes.find(x => String(x.id) === String(id));
                parentNodes = found ? found.children : [];
            }

            currentCategoryPath = parentPath;
            renderCategoryButtons(parentNodes, parentPath);
        });
        $container.prepend($backBtn);
    }
}

function showProductsByCategory(path) {
    const catalog = db.table("catalog");
    let results = catalog.data.filter(p => {
        const categories = p.categories || [];
        for (let i = 0; i < path.length; i++) {
            const target = String(path[i]);
            const matched = categories.some(c => {
                if (c === null || c === undefined) return false;
                if (typeof c === "object") {
                    return String(c.id) === target || String(c.name) === target;
                } else {
                    return String(c) === target;
                }
            });
            if (!matched) return false;
        }
        return true;
    });

    if (!results.length) {
        alert("Aucun produit trouvé dans cette catégorie");
        return;
    }

    $("#productSelectModal").remove();

    const modalHtml = `
        <div class="modal fade" id="productSelectModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content p-3">
                    <h5>Sélectionnez le produit</h5>
                    <ul class="list-group mb-3">
                        ${results.map((p, i) => `<li class="list-group-item list-group-item-action product-item" data-idx="${i}">${p.name.en} - ${parseFloat(p.price).toFixed(2)}</li>`).join('')}
                    </ul>
                    <button class="btn btn-secondary w-100" id="product-cancel">Annuler</button>
                </div>
            </div>
        </div>
    `;
    $("body").append(modalHtml);
    const modalEl = document.getElementById("productSelectModal");
    const modal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
    modal.show();

    $(".product-item").off("click").on("click", async function() {
        const idx = $(this).data("idx");
        const product = results[idx];

        modal.hide();
        modalEl.remove();

        const qty = await showNumericModal(`Quantité pour ${product.name.en}`);
        if (qty > 0) {
            const sale = sales.find(s => s.id === activeSaleId);
            if (!sale) addNewSale();
            const targetSale = sales.find(s => s.id === activeSaleId);
            targetSale.items.push({
                product_id: product.id,
                id: product.id,
                name: product.name,
                price: parseFloat(product.price),
                quantity: qty,
                discount: 0
            });
            renderSalesTabs();
            saveSalesToLocal();
        }
    });

    $("#product-cancel").off("click").on("click", function() {
        modal.hide();
        modalEl.remove();
    });
}

// --------------------
// Catalogue loader
// --------------------
async function loadCatalog(storeId) {
    try {
        const res = await fetch(`http://kabas.dev-back.fr/api/pos/catalog/${storeId}`);
        if (!res.ok) throw new Error('Erreur catalogue');

        const json = await res.json();
        if (!Array.isArray(json.products)) throw new Error('Catalogue invalide : products n\'est pas un tableau');

        // --- 1️⃣ Catalog ---
        const catalog = db.table("catalog");
        catalog.clear();

        const dataWithStore = json.products.map(item => ({
            ...item,
            store_id: storeId,
            images: item.photos || [],
            categories: item.categories || []
        }));

        catalog.insertMany(dataWithStore);

        // --- 2️⃣ Payments ---
        if (Array.isArray(json.paymentsMethod)) {
            const payments = db.table("payments");
            payments.clear();

            const paymentsData = json.paymentsMethod.map(p => ({
                id: p.id,
                name: p.name,
                code: p.code
            }));

            payments.insertMany(paymentsData);
            console.log("Moyens de paiement chargés :", payments.data);
        } else {
            console.warn("Pas de moyens de paiement reçus");
        }

        // --- 3️⃣ Categories ---
        categoryTree = buildCategoryTreeFromJson(json.category_tree);
        console.log("RAW categoryTree from BO:", json.category_tree);
        console.log("Normalized categoryTree (array with numeric ids):", categoryTree);

    } catch (err) {
        console.error("Erreur loadCatalog:", err);
        throw err;
    }
}


// --------------------
// Shift : check / start / end
// --------------------
async function checkUserShift(userId) {
    const syncModalEl = document.getElementById('syncModal');
    const syncModal = syncModalEl ? new bootstrap.Modal(syncModalEl) : null;
    if (syncModal) syncModal.show();

    try {
        const res = await fetch(`http://kabas.dev-back.fr/api/pos/shifts/current/${userId}`);
        if (!res.ok) throw new Error('Erreur check shift');
        const shift = await res.json();

        if (!shift || !shift.id) {
            showScreen("shiftstart");
        } else {
            currentShift = shift;
            showScreen("dashboard");
        }
    } catch (err) {
        console.error("checkUserShift:", err);
        alert("Erreur lors de la vérification du shift.");
        showScreen("login");
    } finally {
        if (syncModal) syncModal.hide();
    }
}

async function startShift(userId, startAmount) {
    try {
        const res = await fetch(`http://kabas.dev-back.fr/api/pos/shifts/start`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": getCsrfToken()
            },
            body: JSON.stringify({ user_id: userId, start_amount: startAmount })
        });
        if (!res.ok) {
            const txt = await res.text();
            throw new Error(txt || "Impossible de démarrer le shift");
        }
        currentShift = await res.json();
        $("#btn-end-shift").removeClass("d-none");
        showScreen("dashboard");
        console.log("Shift démarré:", currentShift);
    } catch (err) {
        console.error("startShift:", err);
        throw err;
    }
}

async function endShift(userId, endAmount) {
    try {
        const res = await fetch(`http://kabas.dev-back.fr/api/pos/shifts/end`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": getCsrfToken()
            },
            body: JSON.stringify({ user_id: userId, end_amount: endAmount })
        });
        if (!res.ok) {
            const txt = await res.text();
            throw new Error(txt || "Impossible de terminer le shift");
        }
        const shift = await res.json();
        currentShift = null;
        $("#btn-end-shift").addClass("d-none");
        showScreen("shiftstart");
        alert("Shift terminé !");
        console.log("Shift terminé:", shift);
    } catch (err) {
        console.error("endShift:", err);
        throw err;
    }
}

// --------------------
// shift screens init
// --------------------
function initShiftstart() {
    let buffer = "";
    $("#shift-start-input").val("");

    $("#screen-shiftstart .shift-num-btn").off("click").on("click", function() {
        if (buffer.length < 9) {
            buffer += $(this).text();
            $("#shift-start-input").val(buffer);
        }
    });

    $("#shift-start-clear").off("click").on("click", function() {
        buffer = "";
        $("#shift-start-input").val("");
    });

    $("#shift-start-ok").off("click").on("click", async function() {
        const amount = parseFloat(buffer);
        if (isNaN(amount) || amount <= 0) {
            alert("Veuillez saisir un montant valide !");
            return;
        }
        try {
            await startShift(currentUser.id, amount);
        } catch (err) {
            alert(err.message || "Erreur démarrage shift");
        }
    });
}

function initShiftend() {
    let buffer = "";
    $("#shift-end-input").val("");

    $("#screen-shiftend .shift-end-num-btn").off("click").on("click", function() {
        if (buffer.length < 9) {
            buffer += $(this).text();
            $("#shift-end-input").val(buffer);
        }
    });

    $("#shift-end-clear").off("click").on("click", function() {
        buffer = "";
        $("#shift-end-input").val("");
    });

    $("#shift-end-ok").off("click").on("click", async function() {
        const amount = parseFloat(buffer);
        if (isNaN(amount)) {
            alert("Veuillez saisir un montant valide !");
            return;
        }
        try {
            await endShift(currentUser.id, amount);
        } catch (err) {
            alert(err.message || "Erreur clôture shift");
        }
    });
}

// --------------------
// Recherche produit dans dashboard
// --------------------
async function performSearchAndShowModal(query) {
    if (!query) return;
    const catalog = db.table("catalog");
    const results = catalog.search ? catalog.search(query) : (
        catalog.data.filter(p =>
            (p.ean && p.ean.toLowerCase().includes(query.toLowerCase())) ||
            (p.name && p.name.en && p.name.en.toLowerCase().includes(query.toLowerCase()))
        )
    );

    if (!results.length) {
        alert("Aucun produit trouvé");
        return;
    }

    $("#productSelectModal").remove();

    const modalHtml = `
        <div class="modal fade" id="productSelectModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content p-3">
                    <h5>Sélectionnez le produit</h5>
                    <ul class="list-group mb-3">
                        ${results.map((p, i) => `<li class="list-group-item list-group-item-action product-item" data-idx="${i}">${p.name.en} - ${parseFloat(p.price).toFixed(2)}</li>`).join('')}
                    </ul>
                    <button class="btn btn-secondary w-100" id="product-cancel">Annuler</button>
                </div>
            </div>
        </div>
    `;
    $("body").append(modalHtml);
    const modalEl = document.getElementById("productSelectModal");
    const modal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
    modal.show();

    $(".product-item").off("click").on("click", async function() {
        const idx = $(this).data("idx");
        const product = results[idx];

        modal.hide();
        modalEl.remove();

        const qty = await showNumericModal(`Quantité pour ${product.name.en}`);
        if (qty > 0) {
            const sale = sales.find(s => s.id === activeSaleId);
            if (!sale) addNewSale();
            const targetSale = sales.find(s => s.id === activeSaleId);
            targetSale.items.push({
                product_id: product.id,
                id: product.id,
                name: product.name,
                price: parseFloat(product.price),
                quantity: qty,
                discount: 0
            });
            renderSalesTabs();
            saveSalesToLocal();
        }

        $("#sale-search").val("").focus();
    });

    $("#product-cancel").off("click").on("click", function() {
        modal.hide();
        modalEl.remove();
        $("#sale-search").val("").focus();
    });
}

// --------------------
// init dashboard UI
// --------------------
function initDashboard() {
    if (!sales.length) addNewSale();

    $("#add-sale-btn").off("click").on("click", addNewSale);

    $("#sale-search-btn").off("click").on("click", function() {
        const q = $("#sale-search").val().trim();
        if (!q) return;
        performSearchAndShowModal(q);
    });

    $("#sale-search").off("keydown").on("keydown", function(e) {
        if (e.key === "Enter") {
            e.preventDefault();
            $("#sale-search-btn").trigger("click");
        }
    });

    renderSalesTabs();

    // auto-sync sales toutes les 30s (exemple)
    setInterval(() => {
        const unsynced = sales.filter(s => !s.synced);
        if (!unsynced.length) return;
        console.log("Synchronisation automatique :", unsynced);
        // TODO: envoyer au BO
    }, 30000);
}

// --------------------
// Initial sync / users
// --------------------
async function initPOS() {
    try {
        const res = await fetch("http://kabas.dev-back.fr/api/pos/users");
        if (!res.ok) throw new Error("Erreur users");
        const data = await res.json();
        const users = db.table("users");
        users.clear();
        users.insertMany(data);
        console.log("Utilisateurs synchronisés :", users.data);
        showScreen("login");
    } catch (err) {
        console.error("initPOS:", err);
        alert("Impossible de synchroniser les utilisateurs.");
    }
}

function initSalesHistory() {
    const $tableBody = $("#sales-history-table tbody");

    // --- Affichage résumé du shift ---
    const start = currentShift.started_at ? new Date(currentShift.started_at) : null;
    const end = currentShift.ended_at ? new Date(currentShift.ended_at) : new Date(); // si pas terminé, on prend maintenant

    // Format avec année sur 2 chiffres
    const fmt = d => d ? d.toLocaleString("fr-FR", { 
        year: "2-digit", month: "2-digit", day: "2-digit", 
        hour: "2-digit", minute: "2-digit" 
    }) : "-";

    // Calcul durée en heures (arrondi 2 décimales)
    let duration = "-";
    if (start) {
        const diffMs = end - start;
        const diffHrs = diffMs / (1000 * 60 * 60);
        duration = diffHrs.toFixed(2) + " h";
    }

    $("#shift-id").text(currentShift.id);
    $("#shift-start").text(fmt(start));
    $("#shift-end").text(currentShift.ended_at ? fmt(end) : window.i18n.running);
    $("#shift-duration").text(duration);
    $("#shift-seller").text(currentUser?.name || "-");

    // Bouton retour Dashboard
    $("#btn-back-dashboard").off("click").on("click", () => {
        showScreen("dashboard");
    });

    // --- Récupération et traitement des ventes validées ---
    const key = `pos_sales_validated_shift_${currentShift.id}`;
    const stored = JSON.parse(localStorage.getItem(key)) || [];
    const validatedSales = stored.filter(s => s.validated);

    let totalByPayment = {};
    let totalArticles = 0;
    let totalDiscounts = 0;

    validatedSales.forEach(sale => {
        const numArticles = sale.items.reduce((sum, item) => sum + item.quantity, 0);
        totalArticles += numArticles;
        totalDiscounts += sale.discount_total || 0;

        const type = sale.payment_type || "UNKNOWN";
        if (!totalByPayment[type]) totalByPayment[type] = 0;
        totalByPayment[type] += sale.total || 0;
    });

    const totalSalesAmount = Object.values(totalByPayment).reduce((sum, v) => sum + v, 0);

    $("#summary-total-amount").text("$" + totalSalesAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $("#summary-sales-count").text(validatedSales.length.toLocaleString());
    $("#summary-discounts-total").text("$" + totalDiscounts.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $("#summary-items-count").text(totalArticles);

    const $paymentTableBody = $("#summary-payment-table tbody").empty();
    Object.entries(totalByPayment).forEach(([type, amount]) => {
        $paymentTableBody.append(`
            <tr>
                <td>${type}</td>
                <td>$${amount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            </tr>
        `);
    });

    $tableBody.empty();
    validatedSales.forEach(sale => {
        const numProducts = sale.items.reduce((sum, item) => sum + item.quantity, 0);
        const totalBeforeDiscount = sale.items.reduce((sum, item) => sum + item.price * item.quantity, 0);
        const payment = sale.payment_type || "";
        const syncedText = sale.synced ? window.i18n.yes : window.i18n.no;
        const syncedBadge = sale.synced 
            ? `<span class="badge bg-success">${syncedText}</span>` 
            : `<span class="badge bg-danger">${syncedText}</span>`;
        const saleDate = sale.date || new Date(sale.id).toLocaleString();

        const $row = $(`
            <tr>
                <td>${saleDate}</td>
                <td class="text-center">${numProducts}</td>
                <td class="text-center">$${totalBeforeDiscount.toFixed(2)}</td>
                <td class="text-center">$${(sale.total || 0).toFixed(2)}</td>
                <td class="text-center">${payment}</td>
                <td class="text-center">${syncedBadge}</td>
                <td><button class="btn btn-info view-sale-detail" data-id="${sale.id}"><i class="bi bi-eye"></i></button></td>
            </tr>
        `);

        $row.find(".view-sale-detail").off("click").on("click", function() {
            const saleId = $(this).data("id");
            showSaleDetail(saleId);
        });

        $tableBody.append($row);
    });
}

function showSaleDetail(saleId) {
    const key = `pos_sales_validated_shift_${currentShift.id}`;
    const stored = JSON.parse(localStorage.getItem(key)) || [];
    const sale = stored.find(s => s.id === saleId);

    if (!sale) {
        alert("Vente introuvable");
        return;
    }

    // --- Produits ---
    const $tbody = $("#sale-items-table tbody").empty();
    sale.items.forEach(item => {
        const discounts = item.discounts && item.discounts.length
            ? item.discounts.map(d => {
                if (d.type === "amount") {
                    return `${d.label} ($${parseFloat(d.value).toFixed(2)})`;
                } else {
                    return `${d.label} (${d.value}%)`;
                }
            }).join(", ")
            : "-";
        const rowHtml = `
            <tr>
                <td>${item.name.en}</td>
                <td class="text-center">${item.quantity}</td>
                <td class="text-center">$${item.price.toFixed(2)}</td>
                <td class="text-center">$${item.line_total.toFixed(2)}</td>
                <td>${discounts}</td>
            </tr>
        `;
        $tbody.append(rowHtml);
    });

    // --- Détails financiers ---
    const totalBeforeDiscount = sale.items.reduce((sum, i) => sum + i.price * i.quantity, 0);
    const totalDiscounts =  totalBeforeDiscount - sale.total || 0;
    const finalTotal = sale.total || 0;
    $("#detail-total-before-discount").text("$" + totalBeforeDiscount.toFixed(2));
    $("#detail-discounts-total").text("$" + totalDiscounts.toFixed(2));
    $("#detail-final-total").text("$" + finalTotal.toFixed(2));
    $("#detail-payment-type").text(sale.payment_type || "");

    // --- Réductions globales ---
    const $globalDiscountsTbody = $("#sale-global-discounts tbody").empty();
    if (sale.discounts && sale.discounts.length) {
        sale.discounts.forEach(discount => {
            const rowHtml = `
                <tr>
                    <td>${discount.label}</td>
                    <td>${discount.type}</td>
                    <td>${discount.value}</td>
                </tr>
            `;
            $globalDiscountsTbody.append(rowHtml);
        });
    } else {
        $globalDiscountsTbody.append('<tr><td colspan="3">' + window.i18n.No_global_discount + '</td></tr>');
    }

    // --- Bouton retour ---
    $("#btn-back-sales-history").off("click").on("click", () => {
        showScreen("sales-history");
    });

    // --- Affiche l'écran ---
    showScreen("sale-detail");
}


// Logout handler
function logout() {
    currentUser = null;
    currentShift = null;
    $("#btn-logout").addClass("d-none");
    $("#btn-end-shift").addClass("d-none");
    showScreen("login");
}

// bind global buttons
$(document).on("click", "#btn-logout", logout);
$(document).on("click", "#btn-end-shift", function() {
    if (currentUser) showScreen("shiftend");
});
$(document).on("click", "#btn-journal", function() {
    if (currentUser) showScreen("sales-history");
    initSalesHistory();
});

// start
document.addEventListener("DOMContentLoaded", initPOS);
