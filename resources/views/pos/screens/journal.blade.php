<div id="screen-sales-history" class="pos-screen d-none p-3" style="height: 100vh; overflow-y: auto; overflow-x: hidden;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>@t("Shift details")</h3>
        <button id="btn-back-dashboard" class="btn btn-primary">← @t("btn.back")</button>
    </div>
    <!-- Résumé du shift -->
    <div id="shift-summary" class="mb-3 p-2 border rounded bg-light">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>@t("Shift ID")</th>
                    <th>@t("Start at")</th>
                    <th>@t("End at")</th>
                    <th>@t("Duration")</th>
                    <th>@t("Staff")</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td></strong> <span id="shift-id"></span></td>
                    <td></strong> <span id="shift-start"></span></td>
                    <td></strong> <span id="shift-end"></span></td>
                    <td></strong> <span id="shift-duration"></span></td>
                    <td></strong> <span id="shift-seller"></span></td>
                </tr>
            </tbody>
        </table>
    </div>
    <!-- Cartes résumé -->
    <!-- Résumé des ventes -->
    <div class="row mb-3">
        <div class="col-md-3 col-sm-6 col-12 mb-2">
            <div class="card text-white bg-primary h-100">
                <div class="card-body">
                    <h6 class="card-title">@t("Total payed")</h6>
                    <p class="card-text fs-3" id="summary-total-amount">0.00</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-12 mb-2">
            <div class="card text-white bg-success h-100">
                <div class="card-body">
                    <h6 class="card-title">@t("Sales count")</h6>
                    <p class="card-text fs-3" id="summary-sales-count">0</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-12 mb-2">
            <div class="card text-white bg-warning h-100">
                <div class="card-body">
                    <h6 class="card-title">@t("Items sold")</h6>
                    <p class="card-text fs-3" id="summary-items-count">0</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-12 mb-2">
            <div class="card text-white bg-danger h-100">
                <div class="card-body">
                    <h6 class="card-title">@t("Total discount")</h6>
                    <p class="card-text fs-3" id="summary-discounts-total">0.00</p>
                </div>
            </div>
        </div>
    </div>


    <!-- Détail par moyen de paiement -->
    <div class="mb-3">
        <table class="table table-sm table-striped" id="summary-payment-table">
            <thead>
                <tr>
                    <th>@t("Méthode de paiement")</th>
                    <th>@t("total_value")</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <!-- Tableau des ventes -->
    <table class="table table-striped mb-3" id="sales-history-table">
        <thead>
            <tr>
                <th>@t("Date/Time")</th>
                <th class="text-center">@t("product.products")</th>
                <th class="text-center">@t("Amount before discount")</th>
                <th class="text-center">@t("Paid amount")</th>
                <th class="text-center">@t("Payment type")</th>
                <th class="text-center">@t("Synchronized")</th>
                <th class="text-center"></th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>

    <hr />
</div>
