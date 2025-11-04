<div id="screen-dashboard" class="pos-screen d-none vh-100">
    <div class="container-fluid h-100 p-0">
        <div class="d-flex h-100">

            <!-- Colonne gauche : ventes -->
            <div id="left-panel" class="border-end d-flex flex-column">

                <!-- (RETIRÉ ICI car global maintenant)
                <div id="side-menu">...</div>
                <div id="side-menu-overlay"></div>
                -->

                <!-- Barre d'actions -->
                <div class="d-flex p-2 border-bottom action-bar align-items-center gap-1">
                    <button class="btn btn-sm btn-outline-primary" title="Nouvelle vente" id="btn-new-sale">
                        <i class="bi bi-plus-circle"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" id="btn-open-menu" title="Menu">
                        <i class="bi bi-list"></i>
                    </button>

                    <!-- Champ de recherche (déplacé ici) -->
                    <div class="input-group input-group-sm ms-2 flex-grow-1" style="min-width: 120px;">
                        <input type="text" id="sale-search" class="form-control form-control-sm" placeholder='@t("Search product by name or EAN")'>
                        <button class="btn btn-outline-secondary" id="btn-reset-search" type="button">&times;</button>
                    </div>
                </div>

                <!-- Onglets des ventes -->
                <ul class="nav nav-tabs" id="sales-tabs" style="margin-top:20px;"></ul>

                <!-- Contenu des ventes -->
                <div class="tab-content flex-grow-1 overflow-auto" id="sales-contents"></div>
            </div>

            <!-- Resizer -->
            <div id="resizer" style="width:5px; min-width: 5px; cursor:col-resize; background:#dee2e6;"></div>

            <!-- Colonne droite : recherche produit -->
            <div id="right-panel" class="d-flex flex-column">
                <div class="p-3 border-bottom">
                    <!-- Liste des catégories parentes -->
                    <div id="category-parents" class="d-flex overflow-auto mb-2"></div>

                    <!-- Liste des catégories enfants -->
                    <div id="category-children" class="d-flex overflow-auto"></div>

                    <!-- N niveaux supplémentaires injectés dynamiquement -->
                    <div id="category-extra-levels" class="d-flex flex-column gap-2 mt-2"></div>
                </div>

                <div class="flex-grow-1 p-3 overflow-auto" id="search-results">
                    <!-- Résultats de recherche / catalogue -->
                </div>
            </div>

        </div>
    </div>
</div>


<style>
    #sales-contents .sale-table { font-size: 0.9rem; }
    #sales-contents .sale-footer { background: #f8f9fa; border-top: 1px solid #dee2e6; padding: 0.5rem; }

    .product-card { cursor: pointer; text-align: center; margin-bottom: 15px; height: 180px; display: flex; flex-direction: column; justify-content: flex-start; align-items: center; overflow: hidden; }
    .product-card img { width: 100%; height: 120px; object-fit: cover; border-radius: 4px; margin-bottom: 4px; }
    .product-card:hover { transform: scale(1.05); transition: transform 0.1s ease-in-out; }

    #category-parents div, #category-children div {
        padding: 0.4rem 0.8rem; background: #e9ecef; border-radius: 12px; margin-right: 0.5rem; cursor: pointer; white-space: nowrap; flex-shrink: 0;
    }
    #category-parents div.active, #category-children div.active { background: #0d6efd; color: white; }

    /* niveaux dynamiques 2+ */
    #category-extra-levels .cat-level { display: flex; overflow: auto; }
    #category-extra-levels .cat-level div {
        padding: 0.4rem 0.8rem; background: #e9ecef; border-radius: 12px; margin-right: 0.5rem; cursor: pointer; white-space: nowrap; flex-shrink: 0;
    }
    #category-extra-levels .cat-level div.active { background: #0d6efd; color: #fff; }

    #btn-reset-search { width: 2rem; padding: 0 0.4rem; font-weight: bold; }

    .action-bar button {
        width: 2.5rem; height: 2.5rem; padding: 0; display: flex; justify-content: center; align-items: center;
    }
    .action-bar .input-group { flex: 1; }

    #discount-keypad { display:flex; flex-wrap:wrap; max-width:200px; margin-top:0.5rem; }
    #discount-keypad button { width:60px; height:60px; margin:2px; font-size:1.2rem; }

    #side-menu a { text-decoration: none; color: #000; font-weight: 500; }
    #side-menu a:hover { color: #0d6efd; }
    #side-menu-overlay { background: rgba(0, 0, 0, 0.4); }

    html, body { height: 100%; overflow: hidden; }
    #screen-dashboard { height: 100vh; }

    .col-4, .col-8 { height: 100%; display: flex; flex-direction: column; }
    #sales-contents, #search-results { flex-grow: 1; overflow-y: auto; }

    #resizer { background-color: #dee2e6; width: 20px; cursor: col-resize; z-index: 1000; }
    #resizer:hover { background-color: #0d6efd; }

    #left-panel { flex: 0 0 40%;  min-width: 200px; }
    #right-panel { flex: 0 0 60%; min-width: 200px; }

    #sales-contents { flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; }
    #sales-contents .tab-pane { display: flex; flex-direction: column; min-height: 0; }
    #sales-contents .tab-pane:not(.active) { display: none; }
    #sales-contents .tab-pane.active { display: flex !important; flex-direction: column; min-height: 0; }
    #sales-contents .tab-pane .flex-grow-1 { overflow-y: auto; }

    #discount-keypad { display: grid; grid-template-columns: repeat(4, 60px); justify-content: center; gap: 5px; margin: 0 auto; }
    #discount-keypad button { width: 60px; height: 60px; font-size: 1.2rem; }
</style>

@push('scripts')
<script>
let selectedParentId = null;
let selectedChildId = null;
let selectedPath = [];              // [parentId, childId, subChildId, ...]
let currentQuery = "";

let isResizing = false;
const resizer = $("#resizer");
const left = $("#left-panel");
const right = $("#right-panel");
const container = left.parent();

function setInitialWidths() {
    const containerWidth = container.width();
    const leftWidth = Math.max(200, Math.floor(containerWidth * 0.40));
    const rightWidth = containerWidth - leftWidth - resizer.width();

    left.css({ width: leftWidth + "px", flex: "none" });
    right.css({ width: rightWidth + "px", flex: "none" });
}

$(document).ready(function() {
    // (les handlers d’ouverture/fermeture du menu sont globaux dans index.blade.php)

    // resize
    function startResize(e) { isResizing = true; $("body").css("cursor", "col-resize"); e.preventDefault(); }
    function doResize(clientX) {
        if (!isResizing) return;
        const containerWidth = container.width();
        let newLeftWidth = clientX - left.offset().left;
        const minWidth = 200, maxWidth = containerWidth - 200;
        if (newLeftWidth < minWidth) newLeftWidth = minWidth;
        if (newLeftWidth > maxWidth) newLeftWidth = maxWidth;
        left.css("width", newLeftWidth + "px");
        right.css("width", (containerWidth - newLeftWidth - resizer.width()) + "px");
    }
    function stopResize() { if (isResizing) { isResizing = false; $("body").css("cursor", "default"); } }

    resizer.on("mousedown", startResize);
    $(document).on("mousemove", e => doResize(e.pageX));
    $(document).on("mouseup", stopResize);
    resizer.on("touchstart", startResize);
    $(document).on("touchmove", e => { if(e.touches.length>0) doResize(e.touches[0].clientX); });
    $(document).on("touchend touchcancel", stopResize);

    $(window).on("resize", setInitialWidths);
});

// ================== SYNC FORCÉE (menu) ==================
async function handleForceSync() {
  if (!currentUser) {
    alert("@t('No user logged in')");
    return;
  }
  const storeId = currentUser.store_id;

  const syncModalEl = document.getElementById('syncModal');
  const syncModal = new bootstrap.Modal(syncModalEl, { backdrop: 'static', keyboard: false });
  syncModal.show();
  try {
    await loadCatalog(storeId);
    writeCatalogCache(storeId);        // catalogue + payments
    saveCategoryTreeToLocal(storeId);  // catégories dans clef dédiée

    console.log("Force sync → hasCategoryTree:", !!window.categoryTree);

    renderCategoryLists();
    renderCatalog();

    //alert("@t('Catalog synced successfully')");
  } catch (err) {
    console.error(err);
    alert("@t('Catalog sync failed')");
  } finally {
    syncModal.hide();
  }
}


// ================== SYNC FORCÉE (menu) ==================
async function handleForceSync() {
  if (!currentUser) {
    alert("@t('No user logged in')");
    return;
  }
  const storeId = currentUser.store_id;

  const syncModalEl = document.getElementById('syncModal');
  const syncModal = new bootstrap.Modal(syncModalEl, { backdrop: 'static', keyboard: false });
  syncModal.show();
  try {
    await loadCatalog(storeId);
    writeCatalogCache(storeId);        // catalogue + payments
    saveCategoryTreeToLocal(storeId);  // catégories dans clef dédiée

    console.log("Force sync → hasCategoryTree:", !!window.categoryTree);

    renderCategoryLists();
    renderCatalog();

    //alert("@t('Catalog synced successfully')");
  } catch (err) {
    console.error(err);
    alert("@t('Catalog sync failed')");
  } finally {
    syncModal.hide();
  }
}

// ================== Remises (inchangé) ==================
// (tout le reste du fichier reste identique à ta version originale)

function addNewSale() {
    const newSale = { id: Date.now(), label: saleCounter++, items: [], discount_total: 0, payment_type: null, synced: false, validated: false };
    sales.push(newSale);
    activeSaleId = newSale.id;
    renderSalesTabs();
    saveSalesToLocal();
}

function renderSalesTabs() {
    const $tabs = $("#sales-tabs");
    const $contents = $("#sales-contents");
    $tabs.empty();
    $contents.empty();

    if (!activeSaleId && sales.length > 0) activeSaleId = sales[0].id;

    sales.forEach((sale, idx) => {
        const activeClass = sale.id === activeSaleId ? "active" : "";
        if (sale.validated) return;

        $tabs.append(`
            <li class="nav-item">
                <a class="nav-link ${activeClass}" data-bs-toggle="tab" href="#sale-${sale.id}">
                    <i class="bi bi-receipt"></i> ${idx + 1}
                </a>
            </li>
        `);

        let totalAvantRemise = 0, totalRemises = 0, total = 0;

        sale.items.forEach(item => {
            let itemTotal = item.price * item.quantity;
            totalAvantRemise += itemTotal;

            let itemDiscountTotal = 0;

            if (Array.isArray(item.discounts)) {
                item.discounts.forEach(d => {
                    const value = Number(d.value) || 0;
                    if (d.scope === 'unit') {
                        if (d.type === 'amount') itemDiscountTotal += value * item.quantity;
                        else if (d.type === 'percent') itemDiscountTotal += (item.price * (value / 100)) * item.quantity;
                    } else if (d.scope === 'line') {
                        if (d.type === 'amount') itemDiscountTotal += value;
                        else if (d.type === 'percent') itemDiscountTotal += itemTotal * (value / 100);
                    }
                });
            }

            if (itemDiscountTotal > itemTotal) itemDiscountTotal = itemTotal;
            totalRemises += itemDiscountTotal;
            total += itemTotal - itemDiscountTotal;
        });

        if (sale.discounts) sale.discounts.forEach(d => {
            let disc = 0;
            if (d.type === 'amount') disc = d.value;
            else if (d.type === 'percent') disc = total * d.value / 100;
            totalRemises += disc;
            total -= disc;
        });

        const itemsHtml = sale.items.map((item, i) => {
            let unitPriceCalc = item.price.toFixed(2);
            let lineTotal = item.price * item.quantity;
            let lineTotalCalc = lineTotal.toFixed(2);
            const calcUnit = [], calcLine = [];

            if (item.discounts && item.discounts.length) {
                item.discounts.forEach(d => {
                    if (d.scope === 'unit') {
                        if (d.type === 'amount') {
                            const discountedUnit = item.price - d.value;
                            calcUnit.push(`${item.price.toFixed(2)} - ${d.value.toFixed(2)}`);
                            unitPriceCalc = `<div>${calcUnit.join('<br>')}<br><strong>${discountedUnit.toFixed(2)}</strong></div>`;
                            lineTotal = discountedUnit * item.quantity;
                            lineTotalCalc = lineTotal.toFixed(2);
                        } else if (d.type === 'percent') {
                            const discountedUnit = item.price * (1 - d.value / 100);
                            calcUnit.push(`${item.price.toFixed(2)} - ${d.value}%`);
                            unitPriceCalc = `<div>${calcUnit.join('<br>')}<br><strong>${discountedUnit.toFixed(2)}</strong></div>`;
                            lineTotal = discountedUnit * item.quantity;
                            lineTotalCalc = lineTotal.toFixed(2);
                        }
                    } else if (d.scope === 'line') {
                        if (d.type === 'amount') {
                            calcLine.push(`${(item.price * item.quantity).toFixed(2)} - ${d.value.toFixed(2)}`);
                            lineTotal = item.price * item.quantity - d.value;
                            lineTotalCalc = `<div>${calcLine.join('<br>')}<br><strong>${lineTotal.toFixed(2)}</strong></div>`;
                        } else if (d.type === 'percent') {
                            calcLine.push(`${(item.price * item.quantity).toFixed(2)} - ${d.value}%`);
                            lineTotal = item.price * item.quantity * (1 - d.value / 100);
                            lineTotalCalc = `<div>${calcLine.join('<br>')}<br><strong>${lineTotal.toFixed(2)}</strong></div>`;
                        }
                    }
                });
            } else {
                unitPriceCalc = `<div>${unitPriceCalc}</div>`;
                lineTotalCalc = `<div>${lineTotalCalc}</div>`;
            }

            let discountMenuHtml = '';
            if (item.discounts && item.discounts.length) {
                discountMenuHtml = item.discounts.map((d, di) => `
                    <li>
                        <a class="dropdown-item remove-line-discount" href="#" data-sale="${sale.id}" data-idx="${i}" data-disc="${di}">
                            <i class="bi bi-x-circle text-danger"></i> Supprimer remise
                        </a>
                    </li>
                `).join('');
            }

            return `
                <tr>
                    <td colspan="4" class="align-middle bg-light text-primary">${item.name.en}</td>
                    <tr>
                    <td class="text-center">${item.quantity}</td>
                    <td class="text-center">${unitPriceCalc}</td>
                    <td class="text-center">${lineTotalCalc}</td>
                    <td>
                        <div class="btn-group dropstart">
                            <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-list"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item remove-item" href="#" data-sale="${sale.id}" data-idx="${i}">
                                        <i class="bi bi-x-circle text-danger"></i> Supprimer produit
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item line-discount" href="#" data-sale="${sale.id}" data-idx="${i}">
                                        <i class="bi bi-percent text-warning"></i> Ajouter remise
                                    </a>
                                </li>
                                ${discountMenuHtml}
                            </ul>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        $contents.append(`
            <div class="tab-pane mt-2 fade ${activeClass ? 'show active' : ''}" id="sale-${sale.id}" style="${activeClass ? 'height:100%;' : ''}">
                <div class="sale-footer border-top pt-2 mt-2">
                    <div class="alert alert-success text-start position-relative" role="alert" style="display:block; width:100%; font-weight:bold;">

                        <div class="btn-group position-absolute top-0 end-0 m-1">
                            <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-list"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                ${!sale.discounts || sale.discounts.length === 0 ? `
                                    <li>
                                        <a class="dropdown-item add-global-discount" href="#" data-sale="${sale.id}">
                                            <i class="bi bi-percent text-warning"></i> Ajouter remise globale
                                        </a>
                                    </li>` : `
                                    <li>
                                        <a class="dropdown-item remove-global-discount" href="#" data-sale="${sale.id}">
                                            <i class="bi bi-x-circle text-danger"></i> Supprimer remise globale
                                        </a>
                                    </li>`}
                            </ul>
                        </div>

                        <div class="totals-hidden" style="display:none; font-weight:normal;">
                            Total avant remise : $${totalAvantRemise.toFixed(2)} <br>
                            Total remises : $${totalRemises.toFixed(2)}
                            <hr />
                        </div>

                        <div>
                            ${sale.discounts && sale.discounts.length > 0 ? `
                                <small>Calcul remise globale : ${sale.discounts.map(d => 
                                    d.type === 'amount' ? `- $${d.value.toFixed(2)}` : `- ${d.value}%`
                                ).join(' + ')}</small><br>` : ''}
                            Total final : <strong>$${total.toFixed(2)}</strong>
                        </div>
                    </div>
                    <div class="d-flex gap-1">
                        <button class="btn btn-secondary flex-fill cancel-sale" data-sale="${sale.id}">Annuler</button>
                        <button class="btn btn-success flex-fill validate-sale" data-sale="${sale.id}">Valider</button>
                        <button class="btn btn-primary flex-fill print-sale" data-sale="${sale.id}">Imprimer</button>
                     </div>
                     <div class="d-flex gap-1 mt-2 text-center">
                         <button id="open-cash-drawer" class="btn btn-warning">Ouvrir le tiroir-caisse</button>
                   </div>
                </div>

                <div class="overflow-auto">
                    <table class="table sale-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-center">Qté</th>
                                <th class="text-center">Prix unitaire</th>
                                <th class="text-center">Total ligne</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>${itemsHtml}</tbody>
                    </table>
                </div>
            </div>
        `);
    });

    // actions (inchangé par rapport à ta version)
    $(".print-sale").off("click").on("click", function() {
        const saleId = $(this).data("sale");
        const sale = sales.find(s => s.id === saleId);
        if (!sale) return;

        let lines = [], total = 0;

        sale.items.forEach(item => {
            const name = (typeof item.name === "object" && item.name.en) ? item.name.en : item.name;
            const qty = item.quantity;
            const unitPrice = item.price;
            let lineTotal = unitPrice * qty;

            let itemDiscountTotal = 0;
            if (Array.isArray(item.discounts)) {
                item.discounts.forEach(d => {
                    const value = Number(d.value) || 0;
                    if (d.scope === 'unit') {
                        if (d.type === 'amount') itemDiscountTotal += value * qty;
                        else if (d.type === 'percent') itemDiscountTotal += unitPrice * (value / 100) * qty;
                    } else if (d.scope === 'line') {
                        if (d.type === 'amount') itemDiscountTotal += value;
                        else if (d.type === 'percent') itemDiscountTotal += lineTotal * (value / 100);
                    }
                });
            }
            if (itemDiscountTotal > lineTotal) itemDiscountTotal = lineTotal;
            lineTotal -= itemDiscountTotal;
            total += lineTotal;

            lines.push({ name, qty, unitPrice, lineTotal, discounts: item.discounts || [] });
        });

        const globalDiscounts = sale.discounts || [];

        $.ajax({
            url: "http://192.168.1.50:5000/print",
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify({ sale: { items: lines, discounts: globalDiscounts, ticket_number: sale.ticket_number, total } }),
            success: function() {},
            error: function(err) { console.error(err); alert("Erreur lors de l'impression !"); }
        });
    });

    $("#open-cash-drawer").off("click").on("click", openCashDrawer);

    $(".remove-item").off("click").on("click", function () {
        const saleId = $(this).data("sale"), idx = $(this).data("idx");
        const sale = sales.find(s => s.id === saleId);
        if (sale) { sale.items.splice(idx, 1); renderSalesTabs(); saveSalesToLocal(); }
    });

    $(".remove-line-discount").off("click").on("click", function () {
        const saleId = $(this).data("sale"), idx = $(this).data("idx"), discIdx = $(this).data("disc");
        const sale = sales.find(s => s.id === saleId);
        if (sale && sale.items[idx] && sale.items[idx].discounts) {
            sale.items[idx].discounts.splice(discIdx, 1);
            renderSalesTabs(); saveSalesToLocal();
        }
    });

    $(".cancel-sale").off("click").on("click", function () {
        const saleId = $(this).data("sale");
        sales = sales.filter(s => s.id !== saleId);
        if (sales.length === 0) addNewSale(); else activeSaleId = sales[0].id;
        renderSalesTabs(); saveSalesToLocal();
    });

    $(".validate-sale").off("click").on("click", function () {
        const saleId = $(this).data("sale");
        handleSaleValidation(saleId);
    });

    $(".set-global-discount").off("click").on("click", async function () {
        const saleId = $(this).data("sale");
        const sale = sales.find(s => s.id === saleId);
        if (!sale) return;
        const d = await showDiscountModal("Remise globale", false);
        if (!d) return;
        sale.discounts = sale.discounts || [];
        sale.discounts.push(d);
        renderSalesTabs(); saveSalesToLocal();
    });

    $(".line-discount").off("click").on("click", async function () {
        const saleId = $(this).data("sale"), idx = $(this).data("idx");
        const sale = sales.find(s => s.id === saleId);
        if (!sale) return;
        const item = sale.items[idx];
        if (!item) return;
        const d = await showDiscountModal("Remise ligne", true, item);
        if (!d) return;
        item.discounts = item.discounts || [];
        item.discounts.push(d);
        renderSalesTabs(); saveSalesToLocal();
    });

    $(".add-global-discount").off("click").on("click", async function(e) {
        e.preventDefault();
        const saleId = $(this).data("sale");
        const sale = sales.find(s => s.id === saleId);
        if (!sale) return;
        const d = await showDiscountModal("Remise globale", false);
        if (!d) return;
        sale.discounts = sale.discounts || [];
        sale.discounts.push(d);
        renderSalesTabs(); saveSalesToLocal();
    });

    $(".remove-global-discount").off("click").on("click", function(e) {
        e.preventDefault();
        const saleId = $(this).data("sale");
        const sale = sales.find(s => s.id === saleId);
        if (!sale || !sale.discounts) return;
        sale.discounts = [];
        renderSalesTabs(); saveSalesToLocal();
    });
}

// ================== Produits / Catalogue (inchangé) ==================
function addProductToActiveSale(product) {
    const sale = sales.find(s => s.id === activeSaleId);
    if (!sale) return;
    const existing = sale.items.find(i => i.ean === product.ean);
    if (existing) existing.quantity += 1;
    else sale.items.push({ product_id: product.id, ean: product.ean, name: product.name, price: parseFloat(product.price), quantity: 1, discounts: [] });
    renderSalesTabs(); saveSalesToLocal();
}

function productMatchesSelectedPath(product) {
    if (!selectedPath.length) return true;
    const cats = product.categories || [];
    const last = Number(selectedPath[selectedPath.length - 1]);
    return cats.some(c => Number(c?.id) === last);
}

function renderCatalog() {
    const catalog = (db.table("catalog") && db.table("catalog").data) ? db.table("catalog").data : [];
    let filtered = catalog.slice();

    filtered = filtered.filter(p => productMatchesSelectedPath(p));

    if (currentQuery) {
        const q = currentQuery.toLowerCase();
        filtered = filtered.filter(p => (p.ean && p.ean.toLowerCase().includes(q)) || (p.name && p.name.en && p.name.en.toLowerCase().includes(q)));
    }

    const $results = $("#search-results"); $results.empty();
    if (filtered.length === 0) { $results.html(`<div class="alert alert-warning">Aucun produit trouvé</div>`); return; }
    const $row = $('<div class="row"></div>');
    filtered.forEach(product => {
        const pics = (product.photos && product.photos.length)
          ? product.photos
          : (product.images && product.images.length ? product.images : []);
        const imgObj = pics.length ? (pics.find(i => i.is_primary) || pics[0]) : null;
        const imgUrl = (imgObj && imgObj.url) ? imgObj.url : '{{ config('app.url') }}/images/no_picture.jpg';

        const title = (product.name && product.name.en) ? product.name.en : (product.name || product.title || 'Produit');
        $row.append(`
            <div class="col-3">
                <div class="product-card" data-ean="${product.ean}" data-id="${product.id}">
                    <img src="${imgUrl}" alt="${title}">
                    <div><small>${title}</small></div>
                </div>
            </div>
        `);
    });
    $results.append($row);
}

function performSearch() {
    currentQuery = $("#sale-search").val().trim();
    renderCatalog();
}

// ========= MODAL DE REMISES (GLOBAL & LIGNE) =========
function showDiscountModal(title = "Remise", isLineDiscount = false) {
  return new Promise((resolve) => {
    // Nettoyage éventuel
    $("#discountModal").remove();

    const scopeControls = isLineDiscount ? `
      <div class="mb-2">
        <label class="form-label">@t("Scope")</label>
        <div class="btn-group w-100" role="group" aria-label="discount-scope">
          <input type="radio" class="btn-check" name="disc-scope" id="disc-scope-unit" value="unit" checked>
          <label class="btn btn-outline-secondary" for="disc-scope-unit">@t("Unit")</label>

          <input type="radio" class="btn-check" name="disc-scope" id="disc-scope-line" value="line">
          <label class="btn btn-outline-secondary" for="disc-scope-line">@t("Line")</label>
        </div>
      </div>
    ` : ``;

    const modalHtml = `
      <div class="modal fade" id="discountModal" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content p-3">
            <h5 class="mb-3">${title}</h5>

            <div class="mb-2">
              <label class="form-label">@t("Discount type")</label>
              <div class="btn-group w-100" role="group" aria-label="discount-type">
                <input type="radio" class="btn-check" name="disc-type" id="disc-type-amount" value="amount" checked>
                <label class="btn btn-outline-secondary" for="disc-type-amount">@t("Amount")</label>

                <input type="radio" class="btn-check" name="disc-type" id="disc-type-percent" value="percent">
                <label class="btn btn-outline-secondary" for="disc-type-percent">@t("Percent")</label>
              </div>
            </div>

            ${scopeControls}

            <div class="mb-3">
              <label for="disc-value" class="form-label">@t("Value")</label>
              <input id="disc-value" type="number" step="0.01" min="0" class="form-control" placeholder="@t('Enter value')">
              <div class="form-text" id="disc-help">@t("Enter amount in store currency")</div>
            </div>

            <div class="text-end">
              <button type="button" class="btn btn-secondary me-1" id="disc-cancel">@t("btn.cancel")</button>
              <button type="button" class="btn btn-success" id="disc-apply">@t("btn.validate")</button>
            </div>
          </div>
        </div>
      </div>
    `;

    $("body").append(modalHtml);
    const modalEl = document.getElementById("discountModal");
    const modal = new bootstrap.Modal(modalEl, { backdrop: "static", keyboard: false });
    modal.show();

    // Aide dynamique selon le type
    function updateHelp() {
      const type = $('input[name="disc-type"]:checked').val();
      const $help = $("#disc-help");
      const $input = $("#disc-value");
      if (type === "percent") {
        $help.text("@t('Enter a percentage between 0 and 100')");
        $input.attr({ min: 0, max: 100, step: "0.01", placeholder: "0 – 100" });
      } else {
        $help.text("@t('Enter amount in store currency')");
        $input.attr({ min: 0, max: null, step: "0.01", placeholder: "ex: 2.50" });
      }
    }
    $(document).on("change", 'input[name="disc-type"]', updateHelp);
    updateHelp();

    $("#disc-cancel").on("click", () => {
      modal.hide(); modalEl.remove(); resolve(null);
    });

    $("#disc-apply").on("click", () => {
      const type  = $('input[name="disc-type"]:checked').val();            // "amount" | "percent"
      const raw   = $("#disc-value").val();
      const value = Number(raw);

      if (!Number.isFinite(value) || value <= 0) {
        alert("@t('Please enter a valid discount value')");
        return;
      }
      if (type === "percent" && (value < 0 || value > 100)) {
        alert("@t('Percent must be between 0 and 100')");
        return;
      }

      const payload = { type, value };

      if (isLineDiscount) {
        payload.scope = $('input[name="disc-scope"]:checked').val();      // "unit" | "line"
      } else {
        payload.label = title || "@t('Global discount')";
      }

      modal.hide(); modalEl.remove();
      resolve(payload);
    });
  });
}

// ================== Catégories (multi-niveaux) ==================
function renderCategoryLists() {
    const $parents = $("#category-parents").empty();
    const $children = $("#category-children").empty();
    theExtra = $("#category-extra-levels").empty();
    const $extra = theExtra;
    if (!window.categoryTree) return;

    const normalized = normalizeCategoryTree(window.categoryTree);

    // Niveau 0 (parents)
    normalized.forEach(node => {
        const $div = $(`<div data-id="${node.id}">${node.name}</div>`);
        if (String(selectedPath[0]) === String(node.id)) $div.addClass("active");
        $div.on("click", () => {
            if (String(selectedPath[0]) === String(node.id)) selectedPath = [];
            else selectedPath = [node.id];
            renderCategoryLists(); renderCatalog();
        });
        $parents.append($div);
    });

    // Niveau 1 (enfants)
    let levelNodes = [];
    if (selectedPath[0]) {
        const parentNode = normalized.find(n => String(n.id) === String(selectedPath[0]));
        levelNodes = parentNode?.children || [];
        levelNodes.forEach(node => {
            const $div = $(`<div data-id="${node.id}">${node.name}</div>`);
            if (String(selectedPath[1]) === String(node.id)) $div.addClass("active");
            $div.on("click", () => {
                if (String(selectedPath[1]) === String(node.id)) selectedPath = selectedPath.slice(0, 1);
                else selectedPath = [selectedPath[0], node.id];
                renderCategoryLists(); renderCatalog();
            });
            $children.append($div);
        });
    }

    // Niveaux 2+
    let nodes = levelNodes;
    for (let lvl = 3; ; lvl++) {
        const parentId = selectedPath[lvl - 2];
        if (parentId == null) break;

        const parentNode = nodes.find(n => String(n.id) === String(parentId));
        if (!parentNode) break;

        const thisLevelNodes = parentNode.children || [];
        if (!thisLevelNodes.length) break;

        const $levelRow = $(`<div class="cat-level" id="cat-level-${lvl}"></div>`);
        thisLevelNodes.forEach(node => {
            const $div = $(`<div data-id="${node.id}">${node.name}</div>`);
            if (String(selectedPath[lvl - 1]) === String(node.id)) $div.addClass("active");
            $div.on("click", () => {
                const already = String(selectedPath[lvl - 1]) === String(node.id);
                selectedPath = selectedPath.slice(0, lvl - 1);
                if (!already) selectedPath.push(node.id);
                renderCategoryLists(); renderCatalog();
            });
            $levelRow.append($div);
        });

        $extra.append($levelRow);

        const nextSelected = selectedPath[lvl - 1];
        if (!nextSelected) break;
        nodes = thisLevelNodes;
    }
}

function normalizeCategoryTree(tree) {
    if (!tree) return [];
    if (Array.isArray(tree)) {
        return tree.map(t => {
            const name = t.name || t.title || t.label || t.slug || '';
            const id = (t.id !== undefined) ? t.id : (t._id !== undefined ? t._id : name);
            return { id, name, children: normalizeCategoryTree(t.children || []) };
        });
    }
    if (typeof tree === 'object') {
        return Object.keys(tree).map(key => {
            const val = tree[key];
            if (val && typeof val === 'object' && (val.id !== undefined || val.name || val.children)) {
                const name = val.name || val.title || key;
                const id = (val.id !== undefined) ? val.id : key;
                return { id, name, children: normalizeCategoryTree(val.children || val) };
            } else return { id: key, name: key, children: normalizeCategoryTree(val) };
        });
    }
    return [];
}

function initDashboard() {
    $("#btn-new-sale").off("click").on("click", addNewSale);

    // Recherche (champ déplacé mais mêmes IDs)
    $("#sale-search").off("input").on("input", performSearch);
    $("#sale-search").off("keypress").on("keypress", e => { if (e.key === "Enter") performSearch(); });
    $("#btn-reset-search").off("click").on("click", () => { $("#sale-search").val(''); currentQuery = ''; renderCatalog(); });

    // Sync forcée (menu)
    $("#btn-force-sync").off("click").on("click", handleForceSync);

    if (sales.length === 0) addNewSale();

    selectedPath = [];

    // 🔁 RESTAURE TOUJOURS les catégories depuis la clef dédiée (si disponibles)
    if (currentUser && currentUser.store_id) {
      restoreCategoryTreeFromLocal(currentUser.store_id);
    }

    renderCategoryLists();
    renderCatalog();

    $("#search-results").off("click", ".product-card").on("click", ".product-card", function() {
        const ean = $(this).data("ean");
        const catalog = db.table("catalog");
        const product = catalog.data.find(p => p.ean === ean);
        if (product) addProductToActiveSale(product);
    });
}

// ================== Validation / impression (inchangé) ==================
function showSaleValidationModal(sale) {
    return new Promise(resolve => {
        $("#saleValidateModal").remove();
        const payments = db.table("payments").data || [];
        const discountSummary = [];

        sale.items.forEach(item => {
            if(item.discounts) item.discounts.forEach(d => discountSummary.push({label:`${item.name.en}`, type:d.type, value:d.value}));
        });
        if(sale.discounts) sale.discounts.forEach(d => discountSummary.push({label:d.label, type:d.type, value:d.value}));

        let total = 0;
        sale.items.forEach(item => {
            let t = item.price*item.quantity;
            if(item.discounts)item.discounts.forEach(d=>{
                if(d.type==='amount') t-=d.value;
                else if(d.type==='percent') t*=(1-d.value/100);
            });
            total+=t;
        });
        if(sale.discounts) sale.discounts.forEach(d=>{
            if(d.type==='amount') total-=d.value;
            else if(d.type==='percent') total*=(1-d.value/100);
        });

        const modalHtml = `
            <div class="modal fade" id="saleValidateModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content p-3">
                        <h5>@t("Sale validation") ${sale.label}</h5>
                        <div class="mb-2">
                            <label>@t("Payment methode")</label>
                            <select class="form-select" id="sale-payment">
                                ${payments.map(p=>`<option value="${p.code}">${p.name}</option>`).join('')}
                            </select>
                        </div>
                        <div class="mb-2"><strong>@t("total_value") : ${total.toFixed(2)}</strong></div>
                        ${discountSummary.length?`
                            <table class="table table-sm table-bordered mb-2">
                                <thead><tr><th>@t("Discount")</th><th>@t("Value")</th></tr></thead>
                                <tbody>
                                    ${discountSummary.map(d=>`<tr><td>${d.label}</td><td>${d.type==='percent'?d.value+'%':d.value.toFixed(2)}</td></tr>`).join('')}
                                </tbody>
                            </table>`:''}
                        <div class="text-end">
                            <button class="btn btn-secondary me-1" id="sale-cancel">@t("btn.cancel")</button>
                            <button class="btn btn-success" id="sale-confirm">@t("btn.validate")</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        $("body").append(modalHtml);
        const modalEl = document.getElementById("saleValidateModal");
        const modal = new bootstrap.Modal(modalEl, {backdrop:'static',keyboard:false});
        modal.show();

        $("#sale-cancel").off("click").on("click",()=>{ modal.hide(); modalEl.remove(); resolve(false); });
        $("#sale-confirm").off("click").on("click",()=>{
            const payment = $("#sale-payment").val();
            sale.payment_type = payment;
            sale.synced = false;
            modal.hide(); modalEl.remove();
            resolve(true);
        });
    });
}

function handleSaleValidation(saleId) {
    const saleIndex = sales.findIndex(s => s.id === saleId);
    if (saleIndex === -1) return;

    const sale = sales[saleIndex];

    showSaleValidationModal(sale).then(valid => {
        if (!valid) return;

        sale.payment_type = sale.payment_type || 'unknown';
        sale.synced = false;
        sale.validated = true;

        saveValidatedSaleToLocal(sale);

        sales.splice(saleIndex, 1);
        saveSalesToLocal();
        renderSalesTabs();

        if (sales.length == 0) addNewSale();

        printSale(sale);

        if (sale.payment_type === "CASH") {
            openCashDrawer();
        }

        alert(`Vente ${sale.label} validée et enregistrée !`);
    });
}

function printSale(sale) {
    let lines = [], total = 0;

    sale.items.forEach(item => {
        const name = (typeof item.name === "object" && item.name.en) ? item.name.en : item.name;
        const qty = item.quantity;
        const unitPrice = item.price;
        let lineTotal = unitPrice * qty;

        let itemDiscountTotal = 0;
        if (Array.isArray(item.discounts)) {
            item.discounts.forEach(d => {
                const value = Number(d.value) || 0;
                if (d.scope === 'unit') {
                    if (d.type === 'amount') itemDiscountTotal += value * qty;
                    else if (d.type === 'percent') itemDiscountTotal += unitPrice * (value / 100) * qty;
                } else if (d.scope === 'line') {
                    if (d.type === 'amount') itemDiscountTotal += value;
                    else if (d.type === 'percent') itemDiscountTotal += lineTotal * (value / 100);
                }
            });
        }
        if (itemDiscountTotal > lineTotal) itemDiscountTotal = lineTotal;
        lineTotal -= itemDiscountTotal;
        total += lineTotal;

        lines.push({ name, qty, unitPrice, lineTotal, discounts: item.discounts || [] });
    });

    const globalDiscounts = sale.discounts || [];

    $.ajax({
        url: "http://192.168.1.50:5000/print",
        method: "POST",
        contentType: "application/json",
        data: JSON.stringify({ sale: { items: lines, discounts: globalDiscounts, ticket_number: sale.ticket_number, total } }),
        success: function() {},
        error: function(err) { console.error(err); alert("Erreur lors de l'impression !"); }
    });
}

function openCashDrawer() {
    $.ajax({
        url: "http://kabas.local:5000/open-drawer",
        method: "POST",
        success: function() { console.log("Tiroir-caisse ouvert automatiquement"); },
        error: function(err) { console.error("Erreur ouverture tiroir :", err); }
    });
}

function saveValidatedSaleToLocal(sale) {
    if (!currentShift) return;
    const key = `pos_sales_validated_shift_${currentShift.id}`;
    let validatedSales = JSON.parse(localStorage.getItem(key)) || [];
    validatedSales.push(sale);
    localStorage.setItem(key, JSON.stringify(validatedSales));
    console.log("Vente validée sauvegardée :", sale);
}
</script>
@endpush
