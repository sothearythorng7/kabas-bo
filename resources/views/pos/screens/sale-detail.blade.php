<div id="screen-sale-detail" class="pos-screen d-none p-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Sale Details</h3>
        <button id="btn-back-sales-history" class="btn btn-primary">← Back</button>
    </div>

    <!-- Products -->
    <div class="mb-4">
        <h5 class="mb-3">Products</h5>
        <div class="table-responsive">
            <table class="table table-striped table-sm align-middle" id="sale-items-table">
                <thead class="table-light">
                    <tr>
                        <th>Product Name</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-center">Unit Price</th>
                        <th class="text-center">Total Value</th>
                        <th>Discount</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Financial summary -->
    <div class="row mb-4">
        <div class="col-md-4 mb-2">
            <div class="card text-white bg-secondary h-100">
                <div class="card-body text-center">
                    <h6 class="card-title">Amount Before Discount</h6>
                    <p class="card-text fs-5 fw-bold" id="detail-total-before-discount">0.00 $</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card text-white bg-warning h-100">
                <div class="card-body text-center">
                    <h6 class="card-title">Total Discount</h6>
                    <p class="card-text fs-5 fw-bold" id="detail-discounts-total">0.00 $</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card text-white bg-success h-100">
                <div class="card-body text-center">
                    <h6 class="card-title">Total Paid</h6>
                    <p class="card-text fs-5 fw-bold" id="detail-final-total">0.00 $</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment method -->
    <div class="mb-4">
        <h5 class="mb-2">Payment Method</h5>
        <span class="badge bg-info text-dark fs-6" id="detail-payment-type"></span>
    </div>

    <!-- Delivery info -->
    <div class="mb-4" id="delivery-info-section" style="display:none;">
        <h5 class="mb-2">Delivery Information</h5>
        <div class="card">
            <div class="card-body">
                <p class="mb-1"><strong>Delivery Fee:</strong> <span id="detail-delivery-fee">$0.00</span></p>
                <p class="mb-0"><strong>Delivery Address:</strong></p>
                <p class="text-muted" id="detail-delivery-address"></p>
            </div>
        </div>
    </div>

    <!-- Global discounts -->
    <div>
        <h5 class="mb-3">Global Discount</h5>
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle" id="sale-global-discounts">
                <thead class="table-light">
                    <tr>
                        <th>Label</th>
                        <th>Type</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>
