<div id="screen-search-results" class="pos-screen d-none p-3" style="height: 100vh; overflow-y: auto; overflow-x: hidden;">
    <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
        <div class="d-flex align-items-center gap-2">
            <button id="btn-back-to-journal" class="btn btn-outline-secondary" title="Back to Journal">
                <i class="bi bi-arrow-left"></i>
            </button>
            <h3 class="mb-0">Sales for <span class="badge bg-primary" id="search-date-display"></span></h3>
        </div>
        <div class="d-flex align-items-center gap-2">
            <label class="mb-0 fw-bold">Search Date:</label>
            <select id="search-day" class="form-select form-select-lg" style="width: 80px;">
                <option value="">Day</option>
            </select>
            <select id="search-month" class="form-select form-select-lg" style="width: 120px;">
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
            <select id="search-year" class="form-select form-select-lg" style="width: 100px;">
                <option value="">Year</option>
            </select>
            <button id="btn-search-date-results" class="btn btn-lg btn-primary">
                <i class="bi bi-search"></i> Search
            </button>
        </div>
    </div>

    <!-- Sales table -->
    <table class="table table-striped mb-3" id="search-sales-table">
        <thead>
            <tr>
                <th>Date/Time</th>
                <th class="text-center">Products</th>
                <th class="text-center">Amount Before Discount</th>
                <th class="text-center">Paid Amount</th>
                <th class="text-center">Payment Type</th>
                <th class="text-center">Delivery</th>
                <th class="text-center"></th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
