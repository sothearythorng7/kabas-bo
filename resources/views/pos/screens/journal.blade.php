<div id="screen-sales-history" class="pos-screen d-none p-3" style="height: 100vh; overflow-y: auto; overflow-x: hidden;">
    <div class="d-flex justify-content-start align-items-center mb-3 gap-2">
        <button id="btn-open-menu" class="btn btn-outline-secondary" title="@t('Menu')">
            <i class="bi bi-list"></i>
        </button>
        <h3 class="mb-0">@t("Shift details")</h3>
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
                    <td><span id="shift-id"></span></td>
                    <td><span id="shift-start"></span></td>
                    <td><span id="shift-end"></span></td>
                    <td><span id="shift-duration"></span></td>
                    <td><span id="shift-seller"></span></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Cartes résumé -->
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

    <!-- 🔹 Cumul Cash In / Cash Out -->
    <div class="mb-3" id="cash-io-summary">
        <table class="table table-sm table-striped">
            <thead>
                <tr>
                    <th>Cash I/O</th>
                    <th class="text-end">@t("total_value")</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="badge bg-success me-1">+ </span>Cash In</td>
                    <td class="text-end"><strong id="cash-in-total">0.00</strong></td>
                </tr>
                <tr>
                    <td><span class="badge bg-danger me-1">− </span>Cash Out</td>
                    <td class="text-end"><strong id="cash-out-total">0.00</strong></td>
                </tr>
                <tr class="table-light">
                    <td><strong>Net (In − Out)</strong></td>
                    <td class="text-end"><strong id="cash-net-total">0.00</strong></td>
                </tr>
            </tbody>
        </table>
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

@push('scripts')
<script>
(function() {
    const CASH_IN_KEY  = "pos_cash_in_total";
    const CASH_OUT_KEY = "pos_cash_out_total";

    function parseNum(v) {
        const n = parseFloat(v);
        return Number.isFinite(n) ? n : 0;
    }

    function renderCashIOSummary() {
        const cashIn  = parseNum(localStorage.getItem(CASH_IN_KEY));
        const cashOut = parseNum(localStorage.getItem(CASH_OUT_KEY));
        const net     = cashIn - cashOut;

        $("#cash-in-total").text(cashIn.toFixed(2));
        $("#cash-out-total").text(cashOut.toFixed(2));
        $("#cash-net-total").text(net.toFixed(2));
    }

    // Rafraîchir automatiquement à l’affichage du journal
    const $screen = $("#screen-sales-history");
    function refreshIfVisible() {
        if ($screen.length && !$screen.hasClass("d-none")) {
            renderCashIOSummary();
        }
    }

    // Surveiller le changement d’écran
    const target = document.getElementById("pos-container");
    if (target) {
        const obs = new MutationObserver(refreshIfVisible);
        obs.observe(target, { attributes: true, subtree: true, attributeFilter: ["class"] });
    }

    $(document).ready(refreshIfVisible);
    window.renderCashIOSummary = renderCashIOSummary;
})();
</script>
@endpush
