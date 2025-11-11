<div id="screen-sales" class="pos-screen d-none d-flex flex-column vh-100">
    <h2 class="text-center py-2">New Sale</h2>

    <div class="mb-3 px-3">
        <div class="input-group">
            <input type="text" id="sale-search" class="form-control" placeholder="Search product by EAN or name" autofocus>
            <button class="btn btn-outline-primary" id="sale-search-btn">Search</button>
        </div>
    </div>

    <!-- Scrollable container for product list -->
    <div class="flex-grow-1 overflow-auto px-3 mb-3">
        <table class="table table-bordered" id="sale-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <!-- Sticky footer for total -->
    <div class="bg-light border-top p-3 fixed-bottom">
        <h4>Sale Total: <span id="sale-total">0.00</span></h4>
    </div>
</div>

<style>
#screen-sales {
    display: flex;
    flex-direction: column;
    height: 100vh;
}
#screen-sales .flex-grow-1 {
    overflow-y: auto;
}
</style>

@push('scripts')
<script>
let currentSale = [];

function showNumericModal(title) {
    return new Promise(resolve => {
        $("#numericModal").remove(); // Supprimer l'ancienne modal si existante

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
            resolve(0); // Annulation
        });

        setTimeout(() => modal.show(), 10);
    });
}



function initSales() {
    const $search = $("#sale-search");
    const $searchBtn = $("#sale-search-btn");
    const $tbody = $("#sale-table tbody");
    const $total = $("#sale-total");

    function renderTable() {
        $tbody.empty();
        let total = 0;
        currentSale.forEach((item, idx) => {
            const lineTotal = item.quantity * parseFloat(item.price);
            total += lineTotal;
            $tbody.append(`
                <tr>
                    <td>${item.name.en}</td>
                    <td>${item.quantity}</td>
                    <td>${item.price}</td>
                    <td>${lineTotal.toFixed(2)}</td>
                    <td><button class="btn btn-sm btn-danger remove-item" data-idx="${idx}">Remove</button></td>
                </tr>
            `);
        });
        $total.text(total.toFixed(2));

        $(".remove-item").off("click").on("click", function() {
            const idx = $(this).data("idx");
            currentSale.splice(idx, 1);
            renderTable();
        });
    }

    async function performSearch() {
        const query = $search.val().trim();
        if (!query) return;

        const catalog = db.table("catalog");

        // Recherche "like" sur EAN ou name.en
        const results = catalog.data.filter(p =>
            (p.ean && p.ean.toLowerCase().includes(query.toLowerCase())) ||
            (p.name && p.name.en && p.name.en.toLowerCase().includes(query.toLowerCase()))
        );

        if (results.length === 0) {
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
                            ${results.map((p, i) =>
                                `<li class="list-group-item list-group-item-action product-item" data-idx="${i}">${p.name.en} - ${p.price}</li>`
                            ).join('')}
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

            const quantity = await showNumericModal(`Quantity for ${product.name.en}`);
            if (quantity > 0) {
                currentSale.push({
                    id: product.id,
                    name: product.name,
                    price: parseFloat(product.price),
                    quantity
                });
                renderTable();
            }

            $search.val("").focus();
        });

        $("#product-cancel").off("click").on("click", function() {
            modal.hide();
            modalEl.remove();
            $search.val("").focus();
        });
    }

    $searchBtn.off("click").on("click", performSearch);
}


</script>
@endpush
