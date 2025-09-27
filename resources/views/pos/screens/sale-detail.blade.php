<div id="screen-sale-detail" class="pos-screen d-none p-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>@t("Sale details")</h3>
        <button id="btn-back-sales-history" class="btn btn-primary">← @t("btn.back")</button>
    </div>

    <!-- Produits -->
    <div class="mb-4">
        <h5 class="mb-3">@t("product.products")</h5>
        <div class="table-responsive">
            <table class="table table-striped table-sm align-middle" id="sale-items-table">
                <thead class="table-light">
                    <tr>
                        <th>@t("Product name")</th>
                        <th class="text-center">@t("resellers.quantity")</th>
                        <th class="text-center">@t("Unit price")</th>
                        <th class="text-center">@t("total_value")</th>
                        <th>@t("Discount")</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Résumé financier -->
    <div class="row mb-4">
        <div class="col-md-4 mb-2">
            <div class="card text-white bg-secondary h-100">
                <div class="card-body text-center">
                    <h6 class="card-title">@t("Amount before discount")</h6>
                    <p class="card-text fs-5 fw-bold" id="detail-total-before-discount">0.00 €</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card text-white bg-warning h-100">
                <div class="card-body text-center">
                    <h6 class="card-title">@t("Total discount")</h6>
                    <p class="card-text fs-5 fw-bold" id="detail-discounts-total">0.00 €</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card text-white bg-success h-100">
                <div class="card-body text-center">
                    <h6 class="card-title">@t("Total paid")</h6>
                    <p class="card-text fs-5 fw-bold" id="detail-final-total">0.00 €</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Moyen de paiement -->
    <div class="mb-4">
        <h5 class="mb-2">@t("Méthode de paiement")</h5>
        <span class="badge bg-info text-dark fs-6" id="detail-payment-type"></span>
    </div>

    <!-- Réductions globales -->
    <div>
        <h5 class="mb-3">@t("Global discount")</h5>
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle" id="sale-global-discounts">
                <thead class="table-light">
                    <tr>
                        <th>@t("Libellé")</th>
                        <th>@t("resellers.type")</th>
                        <th>@t("Value")</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>
