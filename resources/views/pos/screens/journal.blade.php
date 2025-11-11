<div id="screen-sales-history" class="pos-screen d-none p-3" style="height: 100vh; overflow-y: auto; overflow-x: hidden;">
    <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
        <div class="d-flex align-items-center gap-2">
            <button id="btn-open-menu" class="btn btn-outline-secondary" title="Menu">
                <i class="bi bi-list"></i>
            </button>
            <h3 class="mb-0">Shift Details</h3>
        </div>
        <div class="d-flex align-items-center gap-2">
            <label class="mb-0 fw-bold">Search Date:</label>
            <select id="journal-day" class="form-select form-select-lg" style="width: 80px;">
                <option value="">Day</option>
            </select>
            <select id="journal-month" class="form-select form-select-lg" style="width: 120px;">
                <option value="">Month</option>
                <option value="01">January</option>
                <option value="02">February</option>
                <option value="03">March</option>
                <option value="04">April</option>
                <option value="05">May</option>
                <option value="06">June</option>
                <option value="07">July</option>
                <option value="08">August</option>
                <option value="09">September</option>
                <option value="10">October</option>
                <option value="11">November</option>
                <option value="12">December</option>
            </select>
            <select id="journal-year" class="form-select form-select-lg" style="width: 100px;">
                <option value="">Year</option>
            </select>
            <button id="btn-search-date" class="btn btn-lg btn-primary">
                <i class="bi bi-search"></i> Search
            </button>
        </div>
    </div>

    <!-- Shift summary -->
    <div id="shift-summary" class="mb-3 p-2 border rounded bg-light">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Shift ID</th>
                    <th>Start At</th>
                    <th>End At</th>
                    <th>Duration</th>
                    <th>Staff</th>
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

    <!-- Summary cards -->
    <div class="row mb-3">
        <div class="col-md-3 col-sm-6 col-12 mb-2">
            <div class="card text-white bg-primary h-100">
                <div class="card-body">
                    <h6 class="card-title">Total Paid</h6>
                    <p class="card-text fs-3" id="summary-total-amount">0.00</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-12 mb-2">
            <div class="card text-white bg-success h-100">
                <div class="card-body">
                    <h6 class="card-title">Sales Count</h6>
                    <p class="card-text fs-3" id="summary-sales-count">0</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-12 mb-2">
            <div class="card text-white bg-warning h-100">
                <div class="card-body">
                    <h6 class="card-title">Items Sold</h6>
                    <p class="card-text fs-3" id="summary-items-count">0</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-12 mb-2">
            <div class="card text-white bg-danger h-100">
                <div class="card-body">
                    <h6 class="card-title">Total Discount</h6>
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
                    <th class="text-end">Total Value</th>
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

    <!-- Payment method breakdown -->
    <div class="mb-3">
        <table class="table table-sm table-striped" id="summary-payment-table">
            <thead>
                <tr>
                    <th>Payment Method</th>
                    <th>Total Value</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <!-- Sales table -->
    <table class="table table-striped mb-3" id="sales-history-table">
        <thead>
            <tr>
                <th>Date/Time</th>
                <th class="text-center">Products</th>
                <th class="text-center">Amount Before Discount</th>
                <th class="text-center">Paid Amount</th>
                <th class="text-center">Payment Type</th>
                <th class="text-center">Delivery</th>
                <th class="text-center">Synchronized</th>
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

    // Automatically refresh when journal is displayed
    const $screen = $("#screen-sales-history");
    function refreshIfVisible() {
        if ($screen.length && !$screen.hasClass("d-none")) {
            renderCashIOSummary();
        }
    }

    // Monitor screen changes
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
