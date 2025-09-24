// public/js/pos/app.js
// --------------------
// POS central app.js
// --------------------

/* global Database, UsersTable, CatalogTable */

const db = new Database();
db.register(new UsersTable());
db.register(new CatalogTable());

// Session
let currentUser = null;
let currentShift = null;

// Sales (dashboard)
let sales = [];
let activeSaleId = null;
let saleCounter = 1;

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
        // slight defer to allow DOM changes
        setTimeout(() => initFn(), 10);
    }

    // focus convenience: dashboard search
    if (screenId === "dashboard") {
        setTimeout(() => {
            const $s = $("#sale-search");
            if ($s.length) $s.focus();
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
// Catalogue loader
// --------------------
async function loadCatalog(storeId) {
    try {
        const res = await fetch(`http://kabas.dev-back.fr/api/pos/catalog/${storeId}`);
        if (!res.ok) throw new Error('Erreur catalogue');

        const json = await res.json();

        // json.products contient le tableau réel
        if (!Array.isArray(json.products)) {
            throw new Error('Catalogue invalide : products n\'est pas un tableau');
        }

        const catalog = db.table("catalog");
        catalog.clear();

        const dataWithStore = json.products.map(item => ({
            ...item,
            store_id: storeId,
            images: item.photos || [], // renommer photos -> images
            categories: item.categories || [] // toujours un tableau
        }));

        catalog.insertMany(dataWithStore);

        console.log(`Catalogue store ${storeId} chargé, ${catalog.data.length} items`);
    } catch (err) {
        console.error("Erreur loadCatalog:", err);
        throw err;
    }
}



// --------------------
// Shift : check / start / end
// --------------------
async function checkUserShift(userId) {
    // affiche sync modal
    const syncModalEl = document.getElementById('syncModal');
    const syncModal = syncModalEl ? new bootstrap.Modal(syncModalEl) : null;
    if (syncModal) syncModal.show();

    try {
        const res = await fetch(`http://kabas.dev-back.fr/api/pos/shifts/current/${userId}`);
        if (!res.ok) throw new Error('Erreur check shift');
        const shift = await res.json();

        if (!shift || !shift.id) {
            // pas de shift -> écran démarrage
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
        // après clôture -> revenir à l'écran démarrage
        showScreen("shiftstart");
        alert("Shift terminé !");
        console.log("Shift terminé:", shift);
    } catch (err) {
        console.error("endShift:", err);
        throw err;
    }
}

// --------------------
// Auth / login
// --------------------
function initLogin() {
    let pinBuffer = "";
    $("#pin-display").text("••••••");

    $(".pin-btn").off("click").on("click", function() {
        if (pinBuffer.length < 6) {
            pinBuffer += $(this).text();
            const masked = "*".repeat(pinBuffer.length).padEnd(6, "•");
            $("#pin-display").text(masked);
        }
    });

    $("#btn-clear").off("click").on("click", function() {
        pinBuffer = "";
        $("#pin-display").text("••••••");
    });

    $("#btn-enter").off("click").on("click", async function() {
        const users = db.table("users");
        const match = users.findExact({ pin_code: pinBuffer });
        if (match.length === 0) {
            alert("PIN incorrect !");
            pinBuffer = "";
            $("#pin-display").text("••••••");
            return;
        }

        currentUser = match[0];
        console.log("Utilisateur connecté :", currentUser);

        // show sync modal
        const syncModalEl = document.getElementById('syncModal');
        const syncModal = syncModalEl ? new bootstrap.Modal(syncModalEl) : null;
        if (syncModal) syncModal.show();

        try {
            await loadCatalog(currentUser.store_id);
            await checkUserShift(currentUser.id);
        } catch (err) {
            console.error(err);
            alert("Erreur lors de la synchronisation.");
            showScreen("login");
        } finally {
            if (syncModal) syncModal.hide();
        }
    });
}

// --------------------
// Shift screens init
// --------------------
function initShiftstart() {
    // éléments inside screen-shiftstart must exist
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
// Dashboard (nouveau layout)
// --------------------
function renderSalesTabs() {
    const $tabs = $("#sales-tabs");
    const $contents = $("#sales-contents");
    $tabs.empty();
    $contents.empty();

    if (sales.length === 0) {
        $tabs.append(`<li class="nav-item"><span class="nav-link disabled">Aucune vente</span></li>`);
        return;
    }

    sales.forEach(sale => {
        // onglet
        const activeClass = sale.id === activeSaleId ? "active" : "";
        const $tabBtn = $(`<li class="nav-item"><button class="nav-link ${activeClass}" data-sale="${sale.id}">Vente ${sale.label}</button></li>`);
        $tabs.append($tabBtn);
        $tabBtn.find("button").off("click").on("click", function() {
            activeSaleId = sale.id;
            renderSalesTabs();
        });

        // contenu
        const rows = sale.items.map((item, idx) => {
            const lineTotal = item.quantity * item.price;
            return `
                <tr>
                    <td>${item.name.en}</td>
                    <td>${item.quantity}</td>
                    <td>${item.price.toFixed(2)}</td>
                    <td>${lineTotal.toFixed(2)}</td>
                    <td><button class="btn btn-sm btn-danger remove-item" data-sale="${sale.id}" data-idx="${idx}">Supprimer</button></td>
                </tr>
            `;
        }).join("");

        const total = sale.items.reduce((sum, i) => sum + i.price * i.quantity, 0);

        const $pane = $(`
            <div class="tab-pane p-2 ${sale.id === activeSaleId ? "show active" : ""}" id="sale-${sale.id}">
                <table class="table table-sm table-bordered mb-2">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Qté</th>
                            <th>PU</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
                <div class="d-flex justify-content-between align-items-center border-top pt-2">
                    <h5 class="mb-0">Total : ${total.toFixed(2)}</h5>
                    <div>
                        <button class="btn btn-outline-danger me-2 cancel-sale" data-sale="${sale.id}">Annuler</button>
                        <button class="btn btn-success validate-sale" data-sale="${sale.id}">Valider</button>
                    </div>
                </div>
            </div>
        `);
        $contents.append($pane);
    });

    // handlers
    $(".remove-item").off("click").on("click", function() {
        const saleId = $(this).data("sale");
        const idx = $(this).data("idx");
        const sale = sales.find(s => s.id === saleId);
        sale.items.splice(idx, 1);
        // if no item left keep sale (client wanted multiple concurrent sales). re-render.
        renderSalesTabs();
    });

    $(".cancel-sale").off("click").on("click", function() {
        const saleId = $(this).data("sale");
        if (!confirm("Annuler cette vente ?")) return;
        sales = sales.filter(s => s.id !== saleId);
        if (sales.length) activeSaleId = sales[0].id;
        else activeSaleId = null;
        renderSalesTabs();
    });

    $(".validate-sale").off("click").on("click", function() {
        const saleId = $(this).data("sale");
        alert("Validation non implémentée (vente " + saleId + ")");
    });
}

function addNewSale() {
    const newSale = { id: Date.now(), label: saleCounter++, items: [] };
    sales.push(newSale);
    activeSaleId = newSale.id;
    renderSalesTabs();
}

// Recherche produit dans dashboard (colonne droite)
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

    // remove old modal
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

        // remove selection modal fully before opening numeric
        modal.hide();
        modalEl.remove();

        const qty = await showNumericModal(`Quantité pour ${product.name.en}`);
        if (qty > 0) {
            const sale = sales.find(s => s.id === activeSaleId);
            if (!sale) {
                // safety: create a new sale if none
                addNewSale();
            }
            const targetSale = sales.find(s => s.id === activeSaleId);
            targetSale.items.push({
                id: product.id,
                name: product.name,
                price: parseFloat(product.price),
                quantity: qty
            });
            renderSalesTabs();
        }

        $("#sale-search").val("").focus();
    });

    $("#product-cancel").off("click").on("click", function() {
        modal.hide();
        modalEl.remove();
        $("#sale-search").val("").focus();
    });
}

// init dashboard UI
function initDashboard() {
    // ensure there's at least one sale
    if (!sales.length) addNewSale();

    // Add sale button
    $("#add-sale-btn").off("click").on("click", addNewSale);

    // Search button
    $("#sale-search-btn").off("click").on("click", function() {
        const q = $("#sale-search").val().trim();
        if (!q) return;
        performSearchAndShowModal(q);
    });

    // quick focus
    $("#sale-search").off("keydown").on("keydown", function(e) {
        // Prevent Enter from submitting forms / reloading; use button click instead
        if (e.key === "Enter") {
            e.preventDefault();
            $("#sale-search-btn").trigger("click");
        }
    });

    renderSalesTabs();
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
    // go to shift end screen (user will input amount)
    if (currentUser) {
        showScreen("shiftend");
    }
});

// start
document.addEventListener("DOMContentLoaded", initPOS);
