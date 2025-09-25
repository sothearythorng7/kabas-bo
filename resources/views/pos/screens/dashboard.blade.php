<div id="screen-dashboard" class="pos-screen d-none vh-100">
    <div class="container-fluid h-100">
        <div class="row h-100">
            <!-- Colonne gauche : ventes -->
            <div class="col-4 border-end d-flex flex-column">
                <!-- Barre d'actions -->
                <div class="d-flex p-2 border-bottom action-bar">
                    <button class="btn btn-sm btn-outline-primary me-1" title="Nouvelle vente" id="btn-new-sale">
                        <i class="bi bi-plus-circle"></i>
                    </button>
                </div>

                <!-- Onglets des ventes -->
                <ul class="nav nav-tabs" id="sales-tabs"></ul>

                <!-- Contenu des ventes -->
                <div class="tab-content flex-grow-1 overflow-auto" id="sales-contents"></div>
            </div>

            <!-- Colonne droite : recherche produit -->
            <div class="col-8 d-flex flex-column">
                <div class="p-3 border-bottom">
                    <div class="input-group mb-2">
                        <input type="text" id="sale-search" class="form-control" placeholder="Rechercher un produit par EAN ou nom">
                        <button class="btn btn-outline-secondary" id="btn-reset-search" type="button">&times;</button>
                    </div>

                    <!-- Liste des catégories parentes -->
                    <div id="category-parents" class="d-flex overflow-auto mb-2"></div>

                    <!-- Liste des catégories enfants -->
                    <div id="category-children" class="d-flex overflow-auto"></div>
                </div>

                <div class="flex-grow-1 p-3 overflow-auto" id="search-results">
                    <!-- Résultats de recherche / catalogue -->
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    #sales-contents .sale-table {
        font-size: 0.9rem;
    }
    #sales-contents .sale-footer {
        background: #f8f9fa;
        border-top: 1px solid #dee2e6;
        padding: 0.5rem;
    }

    .product-card {
        cursor: pointer;
        text-align: center; 
        margin-bottom: 15px;
        height: 180px;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        align-items: center;
        overflow: hidden;
    }

    .product-card img {
        width: 100%;
        height: 120px;
        object-fit: cover;
        border-radius: 4px;
        margin-bottom: 4px;
    }

    .product-card:hover {
        transform: scale(1.05);
        transition: transform 0.1s ease-in-out;
    }

    #category-parents div, #category-children div {
        padding: 0.4rem 0.8rem;
        background: #e9ecef;
        border-radius: 12px;
        margin-right: 0.5rem;
        cursor: pointer;
        white-space: nowrap;
        flex-shrink: 0;
    }
    #category-parents div.active, #category-children div.active {
        background: #0d6efd;
        color: white;
    }

    #btn-reset-search {
        width: 2rem;
        padding: 0 0.4rem;
        font-weight: bold;
    }

    .action-bar button {
        width: 2.5rem;
        height: 2.5rem;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    /* clavier visuel pour remise */
    #discount-keypad { display:flex; flex-wrap:wrap; max-width:200px; margin-top:0.5rem; }
    #discount-keypad button { width:60px; height:60px; margin:2px; font-size:1.2rem; }
</style>

@push('scripts')
<script>
let selectedParentId = null;
let selectedChildId = null;
let currentQuery = "";

// --- Popup remise avec select et clavier ---
async function showDiscountModal(label = "Remise") {
    return new Promise(resolve => {
        const modal = $(`
            <div class="modal fade" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content p-3">
                        <h5>${label}</h5>
                        <div class="mb-2">
                            <select id="discount-type" class="form-select">
                                <option value="amount">Montant</option>
                                <option value="percent">Pourcentage</option>
                            </select>
                        </div>
                        <input type="text" id="discount-value" class="form-control mb-2" readonly>
                        <div id="discount-keypad">
                            ${[1,2,3,4,5,6,7,8,9,'.',0,'C'].map(n=>`<button data-key="${n}">${n}</button>`).join('')}
                        </div>
                        <div class="mt-2 text-end">
                            <button class="btn btn-sm btn-secondary me-1" id="discount-cancel">Annuler</button>
                            <button class="btn btn-sm btn-primary" id="discount-ok">OK</button>
                        </div>
                    </div>
                </div>
            </div>
        `);
        $("body").append(modal);
        modal.modal('show');

        const $input = modal.find("#discount-value");
        modal.find("#discount-keypad button").on("click", function() {
            const k = $(this).data("key");
            if(k==='C') $input.val('');
            else $input.val($input.val()+k);
        });

        modal.find("#discount-cancel").on("click", function() { modal.modal('hide'); modal.remove(); resolve(null); });
        modal.find("#discount-ok").on("click", function() {
            const val = parseFloat($input.val());
            if(isNaN(val) || val<=0) return alert("Valeur invalide");
            const type = modal.find("#discount-type").val();
            modal.modal('hide'); modal.remove();
            resolve({type,value:val,label});
        });
    });
}

// --- Rendu des ventes avec remises ---
function renderSalesTabs() {
    const $tabs = $("#sales-tabs");
    const $contents = $("#sales-contents");
    $tabs.empty();
    $contents.empty();

    sales.forEach((sale, idx) => {
        const activeClass = sale.id === activeSaleId ? "active" : "";

        $tabs.append(`
            <li class="nav-item">
                <a class="nav-link ${activeClass}" data-bs-toggle="tab" href="#sale-${sale.id}">
                    Vente ${idx+1}
                </a>
            </li>
        `);

        // Collecte toutes les remises (ligne + globale) pour le tableau
        const allDiscounts = [];

        sale.items.forEach((item,i)=>{
            if(item.discounts && item.discounts.length){
                item.discounts.forEach(d=>{
                    allDiscounts.push({
                        type:d.type,
                        value:d.value,
                        label:`Remise ${item.name.en}`,
                        saleIdx:i,
                        item:true
                    });
                });
            }
        });
        if(sale.discounts && sale.discounts.length){
            sale.discounts.forEach(d=>{
                allDiscounts.push({
                    type:d.type,
                    value:d.value,
                    label:d.label,
                    item:false
                });
            });
        }

        // calcul total
        let total = 0;
        sale.items.forEach(item=>{
            let itemTotal = item.price * item.quantity;
            if(item.discounts)item.discounts.forEach(d=>{
                if(d.type==='amount') itemTotal -= d.value;
                else if(d.type==='percent') itemTotal *= (1-d.value/100);
            });
            total += itemTotal;
        });
        if(sale.discounts)sale.discounts.forEach(d=>{
            if(d.type==='amount') total -= d.value;
            else if(d.type==='percent') total *= (1-d.value/100);
        });

        const discountRows = allDiscounts.map((d,i)=>`
            <tr>
                <td>${d.label}</td>
                <td>${d.type==='percent'?d.value+'%':d.value.toFixed(2)}</td>
                <td><button class="btn btn-sm btn-danger remove-discount" data-sale="${sale.id}" data-idx="${i}">X</button></td>
            </tr>
        `).join('');

        $contents.append(`
            <div class="tab-pane fade ${activeClass?'show active':''}" id="sale-${sale.id}">
                <table class="table table-bordered sale-table mb-0">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Qté</th>
                            <th>Prix</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${sale.items.map((item,i)=>{
                            return `
                                <tr>
                                    <td>${item.name.en}</td>
                                    <td>${item.quantity}</td>
                                    <td>${item.price.toFixed(2)}</td>
                                    <td>${(item.price*item.quantity).toFixed(2)}</td>
                                    <td>
                                        <button class="btn btn-sm btn-danger remove-item" data-sale="${sale.id}" data-idx="${i}">X</button>
                                        <button class="btn btn-sm btn-warning line-discount" data-sale="${sale.id}" data-idx="${i}">Remise</button>
                                    </td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>

                ${discountRows?`
                    <table class="table table-sm table-bordered mb-2">
                        <thead>
                            <tr>
                                <th>Remise</th>
                                <th>Valeur</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>${discountRows}</tbody>
                    </table>
                `:''}

                <div class="sale-footer d-flex justify-content-between align-items-center">
                    <strong>Total : ${total.toFixed(2)}</strong>
                    <div>
                        <button class="btn btn-sm btn-secondary cancel-sale" data-sale="${sale.id}">Annuler</button>
                        <button class="btn btn-sm btn-success validate-sale" data-sale="${sale.id}">Valider</button>
                        <button class="btn btn-sm btn-warning set-global-discount" data-sale="${sale.id}">Remise</button>
                    </div>
                </div>
            </div>
        `);
    });

    // Listeners
    $(".remove-item").off("click").on("click", function() { const saleId=$(this).data("sale"); const idx=$(this).data("idx"); const sale=sales.find(s=>s.id===saleId); if(sale){ sale.items.splice(idx,1); renderSalesTabs(); saveSalesToLocal(); }});
    $(".cancel-sale").off("click").on("click", function(){ const saleId=$(this).data("sale"); sales=sales.filter(s=>s.id!==saleId); if(sales.length===0)addNewSale(); else activeSaleId=sales[0].id; renderSalesTabs(); saveSalesToLocal(); });
    $(".validate-sale").off("click").on("click", function(){ const saleId=$(this).data("sale"); alert("Vente "+saleId+" validée (à implémenter)"); });

    $(".set-global-discount").off("click").on("click", async function(){ const saleId=$(this).data("sale"); const sale=sales.find(s=>s.id===saleId); if(!sale) return; const d=await showDiscountModal("Remise globale"); if(!d) return; sale.discounts=sale.discounts||[]; sale.discounts.push(d); renderSalesTabs(); saveSalesToLocal(); });
    $(".line-discount").off("click").on("click", async function(){ const saleId=$(this).data("sale"); const idx=$(this).data("idx"); const sale=sales.find(s=>s.id===saleId); if(!sale) return; const item=sale.items[idx]; if(!item) return; const d=await showDiscountModal("Remise ligne"); if(!d) return; item.discounts=item.discounts||[]; item.discounts.push(d); renderSalesTabs(); saveSalesToLocal(); });

    $(".remove-discount").off("click").on("click", function(){
        const saleId=$(this).data("sale");
        const idx=$(this).data("idx");
        const sale=sales.find(s=>s.id===saleId);
        if(!sale) return;
        // suppression de l'élément correspondant (ligne ou globale)
        let counter = 0;
        let removed=false;
        // remises lignes
        for(let i=0;i<sale.items.length;i++){
            let item = sale.items[i];
            if(item.discounts){
                if(counter+item.discounts.length>idx){
                    item.discounts.splice(idx-counter,1);
                    removed=true; break;
                } else counter+=item.discounts.length;
            }
        }
        if(!removed && sale.discounts && sale.discounts.length>0){
            sale.discounts.splice(idx-counter,1);
        }
        renderSalesTabs(); saveSalesToLocal();
    });

    $('#sales-tabs a[data-bs-toggle="tab"]').off('shown.bs.tab').on('shown.bs.tab',function(e){ const href=$(e.target).attr('href'); activeSaleId=parseInt(href.replace('#sale-','')); });
}

// --- Ajouter produit à la vente active ---
function addProductToActiveSale(product) {
    const sale = sales.find(s => s.id === activeSaleId);
    if (!sale) return;
    const existing = sale.items.find(i => i.ean === product.ean);
    if (existing) existing.quantity += 1;
    else sale.items.push({ ean: product.ean, name: product.name, price: parseFloat(product.price), quantity: 1, discounts: [] });
    renderSalesTabs(); saveSalesToLocal();
}

// --- Catalogue et catégories restent identiques ---
function productHasCategory(product, targetId) {
    if (!product || !targetId) return false;
    const tid = Number(targetId);
    const cats = product.categories || [];
    return cats.some(c => {
        if (!c) return false;
        if (c.id !== undefined && Number(c.id) === tid) return true;
        if (c.parent_id !== undefined && Number(c.parent_id) === tid) return true;
        if (typeof c === "number" && Number(c) === tid) return true;
        return false;
    });
}

function renderCatalog() {
    const catalog = (db.table("catalog") && db.table("catalog").data) ? db.table("catalog").data : [];
    let filtered = catalog.slice();
    if (selectedParentId) filtered = filtered.filter(p => productHasCategory(p, selectedParentId));
    if (selectedChildId) filtered = filtered.filter(p => productHasCategory(p, selectedChildId));
    if (currentQuery) {
        const q = currentQuery.toLowerCase();
        filtered = filtered.filter(p => (p.ean && p.ean.toLowerCase().includes(q)) || (p.name && p.name.en && p.name.en.toLowerCase().includes(q)));
    }

    const $results = $("#search-results"); $results.empty();
    if (filtered.length === 0) { $results.html(`<div class="alert alert-warning">Aucun produit trouvé</div>`); return; }
    const $row = $('<div class="row"></div>');
    filtered.forEach(product => {
        const imgObj = (product.images && product.images.length) ? (product.images.find(i=>i.is_primary) || product.images[0]) : null;
        const imgUrl = (imgObj && imgObj.url) ? imgObj.url : 'http://kabas.dev-back.fr/images/no_picture.jpg';
        const title = (product.name && product.name.en) ? product.name.en : (product.name || product.title || 'Produit');
        $row.append(`
            <div class="col-3">
                <div class="product-card" data-ean="${product.ean}">
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

// --- Gestion catégories avec categoryTree ---
function renderCategoryLists() {
    const $parents = $("#category-parents").empty();
    const $children = $("#category-children").empty();
    if (!categoryTree) return;
    const normalized = normalizeCategoryTree(categoryTree);
    normalized.forEach(parentNode => {
        const $div = $(`<div data-id="${parentNode.id}">${parentNode.name}</div>`);
        if (String(selectedParentId) === String(parentNode.id)) $div.addClass("active");
        $div.on("click", () => {
            selectedParentId = (String(selectedParentId) === String(parentNode.id)) ? null : parentNode.id;
            selectedChildId = null;
            renderCategoryLists(); renderCatalog();
        });
        $parents.append($div);
    });
    if (selectedParentId) {
        const parentNode = normalized.find(n => String(n.id) === String(selectedParentId));
        if (parentNode && parentNode.children && parentNode.children.length > 0) {
            parentNode.children.forEach(childNode => {
                const $div = $(`<div data-id="${childNode.id}">${childNode.name}</div>`);
                if (String(selectedChildId) === String(childNode.id)) $div.addClass("active");
                $div.on("click", () => {
                    selectedChildId = (String(selectedChildId) === String(childNode.id)) ? null : childNode.id;
                    renderCategoryLists(); renderCatalog();
                });
                $children.append($div);
            });
        }
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
    $("#sale-search").off("input").on("input", performSearch);
    $("#sale-search").off("keypress").on("keypress", e => { if (e.key === "Enter") performSearch(); });
    $("#btn-reset-search").off("click").on("click", () => { $("#sale-search").val(''); currentQuery = ''; renderCatalog(); });
    if (sales.length === 0) addNewSale();
    renderCategoryLists(); renderCatalog();

    $("#search-results").off("click", ".product-card").on("click", ".product-card", function() {
        const ean = $(this).data("ean");
        const catalog = db.table("catalog");
        const product = catalog.data.find(p => p.ean === ean);
        if (product) addProductToActiveSale(product);
    });
}
</script>
@endpush
