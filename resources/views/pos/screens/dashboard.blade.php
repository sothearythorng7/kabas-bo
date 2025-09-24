<div id="screen-dashboard" class="pos-screen d-none vh-100">
    <div class="container-fluid h-100">
        <div class="row h-100">
            <!-- Colonne gauche : ventes -->
            <div class="col-4 border-end d-flex flex-column">
                <!-- Onglets des ventes -->
                <ul class="nav nav-tabs" id="sales-tabs"></ul>

                <!-- Contenu des ventes -->
                <div class="tab-content flex-grow-1 overflow-auto" id="sales-contents"></div>

                <!-- Bouton nouvelle vente -->
                <div class="p-2 border-top">
                    <button id="btn-new-sale" class="btn btn-primary w-100">+ Nouvelle vente</button>
                </div>
            </div>

            <!-- Colonne droite : recherche produit -->
            <div class="col-8 d-flex flex-column">
                <div class="p-3 border-bottom">
                    <div class="input-group">
                        <input type="text" id="sale-search" class="form-control" placeholder="Rechercher un produit par EAN ou nom" autofocus>
                    </div>
                </div>
                <div class="flex-grow-1 p-3 overflow-auto" id="search-results">
                    <!-- Résultats de recherche sous forme de vignettes -->
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

    /* Styles pour les vignettes produits */
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
</style>

@push('scripts')
<script>
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

        $contents.append(`
            <div class="tab-pane fade ${activeClass ? 'show active' : ''}" id="sale-${sale.id}">
                <table class="table table-bordered sale-table mb-0">
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Qté</th>
                            <th>Prix</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        ${sale.items.map((item, i) => `
                            <tr>
                                <td>${item.name.en}</td>
                                <td>${item.quantity}</td>
                                <td>${item.price.toFixed(2)}</td>
                                <td>${(item.price * item.quantity).toFixed(2)}</td>
                                <td><button class="btn btn-sm btn-danger remove-item" data-sale="${sale.id}" data-idx="${i}">X</button></td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                <div class="sale-footer d-flex justify-content-between align-items-center">
                    <strong>Total : ${sale.items.reduce((sum, it) => sum + it.price * it.quantity, 0).toFixed(2)}</strong>
                    <div>
                        <button class="btn btn-sm btn-secondary cancel-sale" data-sale="${sale.id}">Annuler</button>
                        <button class="btn btn-sm btn-success validate-sale" data-sale="${sale.id}">Valider</button>
                    </div>
                </div>
            </div>
        `);
    });

    $(".remove-item").off("click").on("click", function() {
        const saleId = $(this).data("sale");
        const idx = $(this).data("idx");
        const sale = sales.find(s => s.id === saleId);
        if (sale) {
            sale.items.splice(idx, 1);
            renderSalesTabs();
        }
    });

    $(".cancel-sale").off("click").on("click", function() {
        const saleId = $(this).data("sale");
        sales = sales.filter(s => s.id !== saleId);
        if (sales.length === 0) {
            addNewSale();
        } else {
            activeSaleId = sales[0].id;
        }
        renderSalesTabs();
    });

    $(".validate-sale").off("click").on("click", function() {
        const saleId = $(this).data("sale");
        alert("Vente " + saleId + " validée (à implémenter)");
    });

    $('#sales-tabs a[data-bs-toggle="tab"]').off('shown.bs.tab').on('shown.bs.tab', function (e) {
        const href = $(e.target).attr('href');
        const id = parseInt(href.replace('#sale-', ''));
        activeSaleId = id;
    });
}

function addProductToActiveSale(product) {
    const sale = sales.find(s => s.id === activeSaleId);
    if (!sale) return;

    const existing = sale.items.find(i => i.ean === product.ean);
    if (existing) {
        existing.quantity += 1;
    } else {
        sale.items.push({
            ean: product.ean,
            name: product.name,
            price: parseFloat(product.price),
            quantity: 1
        });
    }
    renderSalesTabs();
}

function performSearch() {
    const query = $("#sale-search").val().trim();
    const $results = $("#search-results");
    $results.empty();

    if (!query) return;

    const catalog = db.table("catalog");
    const results = catalog.data.filter(p =>
        (p.ean && p.ean.toLowerCase().includes(query.toLowerCase())) ||
        (p.name?.en && p.name.en.toLowerCase().includes(query.toLowerCase()))
    );

    if (results.length === 0) {
        $results.html(`<div class="alert alert-warning">Aucun produit trouvé</div>`);
        return;
    }

    const $row = $('<div class="row"></div>');
    results.forEach(product => {
        const img = product.images?.find(p => p.is_primary) || { url: 'http://kabas.dev-back.fr/images/no_picture.jpg' };
        const $col = $(`
            <div class="col-3">
                <div class="product-card" data-ean="${product.ean}">
                    <img src="${img.url}" alt="${product.name.en}">
                    <div><small>${product.name.en}</small></div>
                </div>
            </div>
        `);
        $row.append($col);
    });

    $results.append($row);
}

// Handler délégué pour que les clics sur vignettes ajoutent toujours dans la vente active
$("#search-results").off("click", ".product-card").on("click", ".product-card", function() {
    const ean = $(this).data("ean");
    const catalog = db.table("catalog");
    const product = catalog.data.find(p => p.ean === ean);
    if (product) addProductToActiveSale(product);
});

function addNewSale() {
    const id = Date.now();
    sales.push({ id, items: [] });
    activeSaleId = id;
    renderSalesTabs();
}

function initDashboard() {
    $("#btn-new-sale").off("click").on("click", addNewSale);
    $("#sale-search").off("input").on("input", performSearch);
    $("#sale-search").off("keypress").on("keypress", e => { if (e.key === "Enter") performSearch(); });

    if (sales.length === 0) addNewSale();
}
</script>
@endpush
