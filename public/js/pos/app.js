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
let currentJournalSales = null; // For storing current journal view (current shift or historical)

// Sales (dashboard)
let sales = [];
let activeSaleId = null;
let saleCounter = 1;

// Categories tree
//let categoryTree = {}; // arbre global des catégories
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
        const res = await fetch(`${APP_BASE_URL}/api/pos/sales/sync`, {
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
            console.log("Sales synchronized:", data.synced_sales);
        }
    } catch (err) {
        console.error("Sales synchronization error:", err);
    }
}

// auto-sync toutes les 30s
setInterval(syncSalesToBO, 30000);


function prepareSalesSync() {
    if (!currentShift) {
        console.warn("No active shift to synchronize sales.");
        return;
    }

    const key = `pos_sales_validated_shift_${currentShift.id}`;
    const validatedSales = JSON.parse(localStorage.getItem(key)) || [];

    const unsyncedSales = validatedSales.filter(s => !s.synced);

    if (unsyncedSales.length === 0) {
        console.log("No sales pending synchronization.");
        return;
    }

    const payload = unsyncedSales.map(sale => ({
        id: sale.id,
        label: sale.label,
        payment_type: sale.payment_type,
        split_payments: sale.split_payments || null,
        items: sale.items.map(item => ({
            product_id: item.product_id,
            name: item.name || null,
            ean: item.ean || null,
            price: item.price,
            quantity: item.quantity,
            discounts: item.discounts || [],
            is_delivery: item.is_delivery || false,
            delivery_address: item.delivery_address || null
        })),
        discounts: sale.discounts || [],
        total: calculateSaleTotal(sale)
    }));

    console.log("JSON ready for synchronization:", JSON.stringify(payload, null, 2));
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

    console.log("Sales saved in localStorage for shift:", currentShift.id);
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
function renderCategoryButtons(nodes = window.categoryTree, path = []) {
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

            let parentNodes = window.categoryTree;
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
        alert("No products found in this category");
        return;
    }

    $("#productSelectModal").remove();

    const modalHtml = `
        <div class="modal fade" id="productSelectModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content p-3">
                    <h5>Select the product</h5>
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

        const qty = await showNumericModal(`Quantity for ${product.name.en}`);
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
        const res = await fetch(`${APP_BASE_URL}/api/pos/catalog/${storeId}`);
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
            console.log("Payment methods loaded:", payments.data);
        } else {
            console.warn("No payment methods received");
        }

        // --- 3️⃣ Categories ---
        window.categoryTree = buildCategoryTreeFromJson(json.category_tree);
        console.log("RAW categoryTree from BO:", json.category_tree);
        console.log("Normalized categoryTree (array with numeric ids):", window.categoryTree);

    } catch (err) {
        console.error("loadCatalog error:", err);
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
        const res = await fetch(`${APP_BASE_URL}/api/pos/shifts/current/${userId}`);
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
        alert("Error checking shift.");
        showScreen("login");
    } finally {
        if (syncModal) syncModal.hide();
    }
}

async function startShift(userId, startAmount) {
    try {
        const res = await fetch(`${APP_BASE_URL}/api/pos/shifts/start`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": getCsrfToken()
            },
            body: JSON.stringify({ user_id: userId, start_amount: startAmount })
        });
        if (!res.ok) {
            const txt = await res.text();
            throw new Error(txt || "Unable to start shift");
        }
        currentShift = await res.json();
        $("#btn-end-shift").removeClass("d-none");
        showScreen("dashboard");
        console.log("Shift started:", currentShift);
    } catch (err) {
        console.error("startShift:", err);
        throw err;
    }
}

async function endShift(userId, endAmount) {
    try {
        const res = await fetch(`${APP_BASE_URL}/api/pos/shifts/end`, {
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
        alert("Shift ended!");
        console.log("Shift ended:", shift);
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
            alert(err.message || "Error starting shift");
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
            alert(err.message || "Error closing shift");
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
        alert("No products found");
        return;
    }

    $("#productSelectModal").remove();

    const modalHtml = `
        <div class="modal fade" id="productSelectModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content p-3">
                    <h5>Select the product</h5>
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

        const qty = await showNumericModal(`Quantity for ${product.name.en}`);
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
        const res = await fetch(`${APP_BASE_URL}/api/pos/users`);
        if (!res.ok) throw new Error("Erreur users");
        const data = await res.json();
        const users = db.table("users");
        users.clear();
        users.insertMany(data);
        console.log("Users synchronized:", users.data);
        showScreen("login");
    } catch (err) {
        console.error("initPOS:", err);
        alert("Unable to synchronize users.");
    }
}

function initSalesHistory() {
    const $tableBody = $("#sales-history-table tbody");

    // Get today's date
    const today = new Date();
    const todayDay = today.getDate().toString().padStart(2, '0');
    const todayMonth = (today.getMonth() + 1).toString().padStart(2, '0');
    const todayYear = today.getFullYear();

    // Populate day dropdown (1-31)
    const $daySelect = $("#journal-day");
    $daySelect.empty().append('<option value="">Day</option>');
    for (let i = 1; i <= 31; i++) {
        const day = i.toString().padStart(2, '0');
        $daySelect.append(`<option value="${day}">${i}</option>`);
    }
    $daySelect.val(todayDay); // Set to today

    // Month is already populated in HTML, just set to today
    $("#journal-month").val(todayMonth);

    // Populate year dropdown (current year and 2 years back)
    const $yearSelect = $("#journal-year");
    $yearSelect.empty().append('<option value="">Year</option>');
    const currentYear = new Date().getFullYear();
    for (let i = currentYear; i >= currentYear - 2; i--) {
        $yearSelect.append(`<option value="${i}">${i}</option>`);
    }
    $yearSelect.val(todayYear); // Set to current year

    // Setup search button
    $("#btn-search-date").off("click").on("click", async function() {
        const day = $("#journal-day").val();
        const month = $("#journal-month").val();
        const year = $("#journal-year").val();

        if (!day || !month || !year) {
            alert("Please select a complete date (day, month, and year)");
            return;
        }

        const selectedDate = `${year}-${month}-${day}`;

        try {
            const response = await fetch("/api/pos/shifts/sales-by-date", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    date: selectedDate,
                    user_id: currentUser.id
                })
            });

            const data = await response.json();

            if (data.sales.length === 0) {
                alert("No sales found for this date");
                return;
            }

            // Convert backend data to frontend format
            const formattedSales = data.sales.map(sale => ({
                id: sale.id,
                date: new Date(sale.created_at).toLocaleString(),
                items: sale.items.map(item => {
                    const price = parseFloat(item.price);
                    const quantity = item.quantity;
                    return {
                        product_id: item.product_id,
                        name: item.product ? item.product.name : { en: 'Delivery Service' },
                        ean: item.product ? item.product.ean : 'DELIVERY',
                        price: price,
                        quantity: quantity,
                        line_total: price * quantity,
                        discounts: item.discounts || [],
                        is_delivery: item.is_delivery || false,
                        delivery_address: item.delivery_address || null
                    };
                }),
                payment_type: sale.payment_type,
                split_payments: sale.split_payments,
                total: parseFloat(sale.total),
                discounts: sale.discounts || [],
                discount_total: sale.discounts ? sale.discounts.reduce((sum, d) => sum + parseFloat(d.amount || 0), 0) : 0,
                validated: true,
                synced: true
            }));

            // Show search results on dedicated screen
            showSearchResults(selectedDate, formattedSales);
        } catch (error) {
            console.error("Error loading historical data:", error);
            alert("Error loading sales for this date");
        }
    });

    // Load current shift data from localStorage
    const key = `pos_sales_validated_shift_${currentShift.id}`;
    const stored = JSON.parse(localStorage.getItem(key)) || [];
    const validatedSales = stored.filter(s => s.validated);
    const shiftInfo = currentShift;

    // Store in global variable for sale detail view
    currentJournalSales = validatedSales;

    // --- Affichage résumé du shift ---
    const start = shiftInfo.started_at ? new Date(shiftInfo.started_at) : null;
    const end = shiftInfo.ended_at ? new Date(shiftInfo.ended_at) : new Date(); // si pas terminé, on prend maintenant

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

    $("#shift-id").text(shiftInfo.id);
    $("#shift-start").text(fmt(start));
    $("#shift-end").text(shiftInfo.ended_at ? fmt(end) : window.i18n.running);
    $("#shift-duration").text(duration);
    $("#shift-seller").text(currentUser?.name || "-");

    // Bouton retour Dashboard
    $("#btn-back-dashboard").off("click").on("click", () => {
        showScreen("dashboard");
    });

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

        // Display payment info - handle split payments
        let paymentDisplay = '';
        if (sale.split_payments && sale.split_payments.length > 1) {
            paymentDisplay = `<span class="badge bg-warning text-dark">Split (${sale.split_payments.length})</span>`;
        } else if (sale.split_payments && sale.split_payments.length === 1) {
            paymentDisplay = sale.split_payments[0].payment_type || "";
        } else {
            paymentDisplay = sale.payment_type || "";
        }

        // Delivery display - check if any item is a delivery service
        let deliveryDisplay = '-';
        const deliveryItem = sale.items.find(item => item.is_delivery);
        if (deliveryItem) {
            const deliveryFee = deliveryItem.price * deliveryItem.quantity;
            deliveryDisplay = `<span class="badge bg-success">$${deliveryFee.toFixed(2)}</span>`;
        }

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
                <td class="text-center">${paymentDisplay}</td>
                <td class="text-center">${deliveryDisplay}</td>
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

function showSaleDetail(saleId, returnScreen = "sales-history") {
    // Try to find sale in current journal view first
    let sale = currentJournalSales ? currentJournalSales.find(s => s.id === saleId) : null;

    // If not found, try localStorage (for backward compatibility)
    if (!sale) {
        const key = `pos_sales_validated_shift_${currentShift.id}`;
        const stored = JSON.parse(localStorage.getItem(key)) || [];
        sale = stored.find(s => s.id === saleId);
    }

    if (!sale) {
        alert("Sale not found");
        return;
    }

    // --- Produits ---
    const $tbody = $("#sale-items-table tbody").empty();
    sale.items.forEach(item => {
        const discounts = item.discounts && item.discounts.length
            ? item.discounts.map(d => {
                let label = "";
                // Scope par défaut 'line' si absent
                const scope = d.scope || "line";

                if(scope === "line") {
                    label = d.type === "percent"
                        ? `Remise ligne: ${d.value}%`
                        : `Remise ligne: $${parseFloat(d.value).toFixed(2)}`;
                } else { // unit
                    label = d.type === "percent"
                        ? `Remise par article: ${d.value}%`
                        : `Remise par article: $${parseFloat(d.value).toFixed(2)}`;
                }
                return label;
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

    // Display payment method(s) - handle split payments
    const $paymentType = $("#detail-payment-type");
    $paymentType.empty();
    if (sale.split_payments && sale.split_payments.length > 0) {
        sale.split_payments.forEach(payment => {
            $paymentType.append(`<span class="badge bg-info text-dark me-1">${payment.payment_type}: $${payment.amount.toFixed(2)}</span>`);
        });
    } else {
        $paymentType.text(sale.payment_type || "");
    }

    // Display delivery information - check if any item is a delivery service
    const deliveryItem = sale.items.find(item => item.is_delivery);
    if (deliveryItem) {
        $("#delivery-info-section").show();
        const deliveryFee = deliveryItem.price * deliveryItem.quantity;
        $("#detail-delivery-fee").text("$" + deliveryFee.toFixed(2));
        $("#detail-delivery-address").text(deliveryItem.delivery_address || "");
    } else {
        $("#delivery-info-section").hide();
    }

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
        showScreen(returnScreen);
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

// Show search results on dedicated screen
function showSearchResults(searchDate, sales) {
    // Store sales globally for detail view
    currentJournalSales = sales;

    // Display date
    $("#search-date-display").text(new Date(searchDate).toLocaleDateString());

    // Populate date selectors for search on results page
    const dateObj = new Date(searchDate);
    const searchDay = dateObj.getDate().toString().padStart(2, '0');
    const searchMonth = (dateObj.getMonth() + 1).toString().padStart(2, '0');
    const searchYear = dateObj.getFullYear();

    // Populate day dropdown
    const $daySelect = $("#search-day");
    $daySelect.empty().append('<option value="">Day</option>');
    for (let i = 1; i <= 31; i++) {
        const day = i.toString().padStart(2, '0');
        $daySelect.append(`<option value="${day}">${i}</option>`);
    }
    $daySelect.val(searchDay);

    // Set month
    $("#search-month").val(searchMonth);

    // Populate year dropdown
    const $yearSelect = $("#search-year");
    $yearSelect.empty().append('<option value="">Year</option>');
    const currentYear = new Date().getFullYear();
    for (let i = currentYear; i >= currentYear - 2; i--) {
        $yearSelect.append(`<option value="${i}">${i}</option>`);
    }
    $yearSelect.val(searchYear);

    // Setup search button on results page
    $("#btn-search-date-results").off("click").on("click", async function() {
        const day = $("#search-day").val();
        const month = $("#search-month").val();
        const year = $("#search-year").val();

        if (!day || !month || !year) {
            alert("Please select a complete date (day, month, and year)");
            return;
        }

        const selectedDate = `${year}-${month}-${day}`;

        try {
            const response = await fetch("/api/pos/shifts/sales-by-date", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    date: selectedDate,
                    user_id: currentUser.id
                })
            });

            const data = await response.json();

            if (data.sales.length === 0) {
                alert("No sales found for this date");
                return;
            }

            // Convert backend data to frontend format
            const formattedSales = data.sales.map(sale => ({
                id: sale.id,
                date: new Date(sale.created_at).toLocaleString(),
                items: sale.items.map(item => {
                    const price = parseFloat(item.price);
                    const quantity = item.quantity;
                    return {
                        product_id: item.product_id,
                        name: item.product ? item.product.name : { en: 'Delivery Service' },
                        ean: item.product ? item.product.ean : 'DELIVERY',
                        price: price,
                        quantity: quantity,
                        line_total: price * quantity,
                        discounts: item.discounts || [],
                        is_delivery: item.is_delivery || false,
                        delivery_address: item.delivery_address || null
                    };
                }),
                payment_type: sale.payment_type,
                split_payments: sale.split_payments,
                total: parseFloat(sale.total),
                discounts: sale.discounts || [],
                discount_total: sale.discounts ? sale.discounts.reduce((sum, d) => sum + parseFloat(d.amount || 0), 0) : 0,
                validated: true,
                synced: true
            }));

            // Show new search results
            showSearchResults(selectedDate, formattedSales);
        } catch (error) {
            console.error("Error loading historical data:", error);
            alert("Error loading sales for this date");
        }
    });

    // Sales table
    const $tableBody = $("#search-sales-table tbody").empty();
    sales.forEach(sale => {
        const numProducts = sale.items.reduce((sum, item) => sum + item.quantity, 0);
        const totalBeforeDiscount = sale.items.reduce((sum, item) => sum + item.price * item.quantity, 0);

        // Display payment info - handle split payments
        let paymentDisplay = '';
        if (sale.split_payments && sale.split_payments.length > 1) {
            paymentDisplay = `<span class="badge bg-warning text-dark">Split (${sale.split_payments.length})</span>`;
        } else if (sale.split_payments && sale.split_payments.length === 1) {
            paymentDisplay = sale.split_payments[0].payment_type || "";
        } else {
            paymentDisplay = sale.payment_type || "";
        }

        // Delivery display - check if any item is a delivery service
        let deliveryDisplay = '-';
        const deliveryItem = sale.items.find(item => item.is_delivery);
        if (deliveryItem) {
            const deliveryFee = deliveryItem.price * deliveryItem.quantity;
            deliveryDisplay = `<span class="badge bg-success">$${deliveryFee.toFixed(2)}</span>`;
        }

        const saleDate = sale.date || new Date(sale.id).toLocaleString();

        const $row = $(`
            <tr>
                <td>${saleDate}</td>
                <td class="text-center">${numProducts}</td>
                <td class="text-center">$${totalBeforeDiscount.toFixed(2)}</td>
                <td class="text-center">$${(sale.total || 0).toFixed(2)}</td>
                <td class="text-center">${paymentDisplay}</td>
                <td class="text-center">${deliveryDisplay}</td>
                <td><button class="btn btn-info view-sale-detail" data-id="${sale.id}"><i class="bi bi-eye"></i></button></td>
            </tr>
        `);

        $row.find(".view-sale-detail").off("click").on("click", function() {
            const saleId = $(this).data("id");
            showSaleDetail(saleId, "search-results");
        });

        $tableBody.append($row);
    });

    // Setup back button
    $("#btn-back-to-journal").off("click").on("click", () => {
        // Reset to today's date
        const today = new Date();
        const todayDay = today.getDate().toString().padStart(2, '0');
        const todayMonth = (today.getMonth() + 1).toString().padStart(2, '0');
        const todayYear = today.getFullYear();

        $("#journal-day").val(todayDay);
        $("#journal-month").val(todayMonth);
        $("#journal-year").val(todayYear);
        showScreen("sales-history");
    });

    // Show the search results screen
    showScreen("search-results");
}

// bind global buttons
$(document).on("click", "#btn-logout", logout);
$(document).on("click", "#btn-end-shift", function() {
    if (currentUser) { showScreen("shiftend"); initShiftEnd(); }
});
$(document).on("click", "#btn-journal", function() {
    if (currentUser) showScreen("sales-history");
    initSalesHistory();
});

// start
document.addEventListener("DOMContentLoaded", initPOS);
