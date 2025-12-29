<style>
/* Toggle buttons for iPad touch */
.exchange-toggle {
    width: 60px;
    height: 36px;
    border-radius: 18px;
    border: 2px solid #dee2e6;
    background: #f8f9fa;
    position: relative;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.exchange-toggle:active {
    transform: scale(0.95);
}
.exchange-toggle.selected {
    background: #ffc107;
    border-color: #ffc107;
}
.exchange-toggle .toggle-icon {
    font-size: 1.2rem;
    color: #adb5bd;
    transition: all 0.2s ease;
}
.exchange-toggle.selected .toggle-icon {
    color: #000;
}
.exchange-toggle.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
/* Make table rows more touch-friendly */
#sale-items-table tbody tr {
    min-height: 60px;
}
#sale-items-table tbody td {
    padding: 12px 8px;
    vertical-align: middle;
}
</style>

<div id="screen-sale-detail" class="pos-screen d-none p-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Sale Details <span id="detail-sale-id" class="text-muted fs-6"></span></h3>
        <div class="d-flex gap-2">
            <button id="btn-reprint-sale" class="btn btn-success btn-lg">
                <i class="bi bi-printer"></i> Reprint
            </button>
            <button id="btn-start-exchange" class="btn btn-warning btn-lg d-none">
                <i class="bi bi-arrow-left-right"></i> Exchange (<span id="exchange-count">0</span>)
            </button>
            <button id="btn-back-sales-history" class="btn btn-primary btn-lg">← Back</button>
        </div>
    </div>

    <!-- Products -->
    <div class="mb-4">
        <h5 class="mb-3">Products <small class="text-muted">(tap to select for exchange)</small></h5>
        <div class="table-responsive">
            <table class="table table-striped align-middle" id="sale-items-table">
                <thead class="table-light">
                    <tr>
                        <th style="width: 70px;" class="text-center">Exchange</th>
                        <th>Product Name</th>
                        <th class="text-center">Qty</th>
                        <th class="text-center">Price</th>
                        <th class="text-center">Total</th>
                        <th>Status</th>
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

<!-- Exchange Modal -->
<div class="modal fade" id="exchangeModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="bi bi-arrow-left-right me-2"></i>Exchange - Step 2: New Items</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Left: Items to return -->
                    <div class="col-md-5">
                        <div class="card h-100">
                            <div class="card-header bg-danger text-white">
                                <h6 class="mb-0">Items to Return</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush" id="exchange-return-items">
                                </div>
                            </div>
                            <div class="card-footer">
                                <strong>Return Credit: $<span id="exchange-return-credit">0.00</span></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Right: New items -->
                    <div class="col-md-7">
                        <div class="card h-100">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0">New Items (Optional)</h6>
                            </div>
                            <div class="card-body">
                                <!-- Search products -->
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control" id="exchange-product-search" placeholder="Search product...">
                                </div>
                                <div class="list-group mb-3" id="exchange-search-results" style="max-height: 150px; overflow-y: auto;">
                                </div>
                                <!-- Selected new items -->
                                <div class="list-group" id="exchange-new-items">
                                    <div class="text-muted text-center py-3">No new items added (optional)</div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <strong>New Items Total: $<span id="exchange-new-total">0.00</span></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <div class="card mt-3">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <table class="table table-sm mb-0">
                                    <tr>
                                        <td>Return Credit:</td>
                                        <td class="text-end text-success fw-bold">+$<span id="exchange-summary-return">0.00</span></td>
                                    </tr>
                                    <tr>
                                        <td>New Items:</td>
                                        <td class="text-end text-danger fw-bold">-$<span id="exchange-summary-new">0.00</span></td>
                                    </tr>
                                    <tr class="table-active">
                                        <td class="fw-bold">Balance:</td>
                                        <td class="text-end fw-bold" id="exchange-balance">$0.00</td>
                                    </tr>
                                </table>
                                <div id="exchange-balance-message" class="mt-2"></div>
                            </div>
                            <div class="col-md-6">
                                <!-- Payment section (if customer owes) -->
                                <div id="exchange-payment-section" class="d-none">
                                    <div class="border rounded p-2 bg-light">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong>Amount Due:</strong>
                                            <span class="text-danger fw-bold">$<span id="exchange-amount-due">0.00</span></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span>Remaining:</span>
                                            <span class="text-warning fw-bold">$<span id="exchange-remaining">0.00</span></span>
                                        </div>

                                        <!-- Payments list -->
                                        <div id="exchange-payments-list" class="mb-2" style="max-height: 100px; overflow-y: auto;"></div>

                                        <!-- Add payment form -->
                                        <div class="row g-1 mb-2">
                                            <div class="col-5">
                                                <select class="form-select form-select-sm" id="exchange-payment-method">
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <input type="number" class="form-control form-control-sm" id="exchange-payment-amount" step="0.01" min="0" placeholder="Amount">
                                            </div>
                                            <div class="col-3">
                                                <button class="btn btn-sm btn-primary w-100" id="btn-add-exchange-payment">
                                                    <i class="bi bi-plus"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Voucher validation -->
                                        <div id="exchange-voucher-input" class="d-none">
                                            <div class="input-group input-group-sm mb-1">
                                                <input type="text" class="form-control" id="exchange-voucher-code" placeholder="KBA123456789" maxlength="12">
                                                <button class="btn btn-outline-secondary" type="button" id="btn-validate-exchange-voucher">Validate</button>
                                            </div>
                                            <div id="exchange-voucher-result" class="small"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="btn-confirm-exchange">
                    <i class="bi bi-check-lg me-1"></i>Confirm Exchange
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Exchange Success Modal -->
<div class="modal fade" id="exchangeSuccessModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-5">
                <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                <h4 class="mt-3 text-success">Exchange Completed!</h4>
                <div id="exchange-success-content" class="mt-3"></div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal" id="btn-exchange-done">Done</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    // Exchange state
    let currentSaleForExchange = null;
    let selectedItemsForExchange = [];
    let newItemsForExchange = [];
    let validatedExchangeVoucher = null;
    let exchangePayments = [];
    let exchangeAmountDue = 0;

    // Toggle exchange button visibility based on selected items
    function updateExchangeButtonVisibility() {
        const selectedToggles = $(".exchange-toggle.selected");
        const count = selectedToggles.length;
        $("#exchange-count").text(count);
        if (count > 0) {
            $("#btn-start-exchange").removeClass("d-none");
        } else {
            $("#btn-start-exchange").addClass("d-none");
        }
    }

    // Calculate return credit
    function calculateReturnCredit() {
        let total = 0;
        selectedItemsForExchange.forEach(item => {
            total += item.quantity * item.price;
        });
        return total;
    }

    // Calculate new items total
    function calculateNewItemsTotal() {
        let total = 0;
        newItemsForExchange.forEach(item => {
            total += item.quantity * item.price;
        });
        return total;
    }

    // Update exchange totals
    function updateExchangeTotals() {
        const returnCredit = calculateReturnCredit();
        const newTotal = calculateNewItemsTotal();
        const balance = returnCredit - newTotal;

        console.log("updateExchangeTotals:", { returnCredit, newTotal, balance });

        $("#exchange-return-credit").text(returnCredit.toFixed(2));
        $("#exchange-new-total").text(newTotal.toFixed(2));
        $("#exchange-summary-return").text(returnCredit.toFixed(2));
        $("#exchange-summary-new").text(newTotal.toFixed(2));

        const $balance = $("#exchange-balance");
        $balance.text((balance >= 0 ? '+' : '') + '$' + balance.toFixed(2));
        $balance.removeClass("text-success text-danger").addClass(balance >= 0 ? "text-success" : "text-danger");

        // Show appropriate message and payment section
        const $message = $("#exchange-balance-message");
        const $paymentSection = $("#exchange-payment-section");

        if (balance > 0) {
            $message.html(`<div class="alert alert-success mb-0 py-2"><i class="bi bi-gift me-2"></i>A voucher of <strong>$${balance.toFixed(2)}</strong> will be generated</div>`);
            $paymentSection.addClass("d-none");
            exchangeAmountDue = 0;
            console.log("Balance > 0: voucher will be generated");
        } else if (balance < 0) {
            $message.html(`<div class="alert alert-warning mb-0 py-2"><i class="bi bi-cash me-2"></i>Customer owes <strong>$${Math.abs(balance).toFixed(2)}</strong></div>`);
            $paymentSection.removeClass("d-none");
            exchangeAmountDue = Math.abs(balance);
            console.log("Balance < 0: customer owes, exchangeAmountDue =", exchangeAmountDue);
            updateExchangePaymentUI();
        } else {
            $message.html(`<div class="alert alert-info mb-0 py-2"><i class="bi bi-check-circle me-2"></i>Even exchange - no payment needed</div>`);
            $paymentSection.addClass("d-none");
            exchangeAmountDue = 0;
            console.log("Balance = 0: even exchange");
        }

        updateExchangeConfirmButton();
    }

    // Update exchange payment UI
    function updateExchangePaymentUI() {
        const totalPaid = exchangePayments.reduce((sum, p) => sum + p.amount, 0);
        const remaining = Math.max(0, exchangeAmountDue - totalPaid);

        $("#exchange-amount-due").text(exchangeAmountDue.toFixed(2));
        $("#exchange-remaining").text(remaining.toFixed(2));
        $("#exchange-payment-amount").val(remaining.toFixed(2));

        // Render payments list
        const $list = $("#exchange-payments-list");
        if (exchangePayments.length === 0) {
            $list.html('<div class="text-muted small text-center">No payments added</div>');
        } else {
            let html = '';
            exchangePayments.forEach((p, idx) => {
                html += `
                    <div class="d-flex justify-content-between align-items-center small border-bottom py-1">
                        <span>${p.payment_type}${p.voucher_code ? ' (' + p.voucher_code.substring(0,6) + '...)' : ''}</span>
                        <span>
                            $${p.amount.toFixed(2)}
                            <button class="btn btn-sm btn-link text-danger p-0 ms-1 remove-exchange-payment" data-idx="${idx}">
                                <i class="bi bi-x"></i>
                            </button>
                        </span>
                    </div>
                `;
            });
            $list.html(html);
        }

        // Update add button state
        $("#btn-add-exchange-payment").prop("disabled", remaining <= 0);
    }

    // Update confirm button state
    function updateExchangeConfirmButton() {
        const returnCredit = calculateReturnCredit();
        const newItemsTotal = calculateNewItemsTotal();
        const balance = returnCredit - newItemsTotal;

        console.log("updateExchangeConfirmButton:", {
            returnCredit,
            newItemsTotal,
            balance,
            exchangeAmountDue,
            exchangePayments,
            selectedItemsCount: selectedItemsForExchange.length
        });

        if (selectedItemsForExchange.length === 0) {
            console.log("Button disabled: no items selected");
            $("#btn-confirm-exchange").prop("disabled", true);
            return;
        }

        if (balance >= 0) {
            // Voucher will be generated or even exchange
            console.log("Button enabled: balance >= 0");
            $("#btn-confirm-exchange").prop("disabled", false).removeClass("btn-secondary").addClass("btn-warning");
        } else {
            // Customer owes - check if fully paid
            const totalPaid = exchangePayments.reduce((sum, p) => sum + p.amount, 0);
            const isPaid = Math.abs(totalPaid - exchangeAmountDue) < 0.01;
            console.log("Customer owes - isPaid:", isPaid, "totalPaid:", totalPaid, "exchangeAmountDue:", exchangeAmountDue);

            if (isPaid) {
                $("#btn-confirm-exchange").prop("disabled", false).removeClass("btn-secondary").addClass("btn-warning");
            } else {
                $("#btn-confirm-exchange").prop("disabled", true).removeClass("btn-warning").addClass("btn-secondary");
            }
        }
    }

    // Render return items in modal with quantity controls
    function renderReturnItems() {
        const $container = $("#exchange-return-items").empty();
        selectedItemsForExchange.forEach((item, index) => {
            const name = typeof item.name === 'object' ? (item.name.en || item.name.fr || 'Product') : item.name;
            const maxQty = item.max_quantity || item.quantity;
            const currentQty = item.quantity;

            $container.append(`
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-medium">${name}</span>
                        <span class="text-muted small">$${item.price.toFixed(2)}/unit</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-danger return-qty-decrease" data-index="${index}" ${currentQty <= 1 ? 'disabled' : ''}>
                                <i class="bi bi-dash"></i>
                            </button>
                            <span class="btn btn-outline-secondary disabled" style="min-width: 50px;">
                                ${currentQty} / ${maxQty}
                            </span>
                            <button type="button" class="btn btn-outline-success return-qty-increase" data-index="${index}" ${currentQty >= maxQty ? 'disabled' : ''}>
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                        <strong class="text-danger">-$${(currentQty * item.price).toFixed(2)}</strong>
                    </div>
                </div>
            `);
        });
    }

    // Event: Decrease return quantity
    $(document).on("click", ".return-qty-decrease", function() {
        const index = $(this).data("index");
        if (selectedItemsForExchange[index].quantity > 1) {
            selectedItemsForExchange[index].quantity--;
            renderReturnItems();
            updateExchangeTotals();
        }
    });

    // Event: Increase return quantity
    $(document).on("click", ".return-qty-increase", function() {
        const index = $(this).data("index");
        const item = selectedItemsForExchange[index];
        const maxQty = item.max_quantity || item.quantity;
        if (item.quantity < maxQty) {
            item.quantity++;
            renderReturnItems();
            updateExchangeTotals();
        }
    });

    // Render new items in modal
    function renderNewItems() {
        const $container = $("#exchange-new-items").empty();
        if (newItemsForExchange.length === 0) {
            $container.html('<div class="text-muted text-center py-3">No new items added (optional)</div>');
            return;
        }
        newItemsForExchange.forEach((item, index) => {
            $container.append(`
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${item.name}</strong>
                        <small class="d-block">Qty: ${item.quantity} x $${item.price.toFixed(2)} = $${(item.quantity * item.price).toFixed(2)}</small>
                    </div>
                    <button class="btn btn-sm btn-outline-danger remove-exchange-item" data-index="${index}">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `);
        });
    }

    // Search products for exchange
    let searchTimeout = null;
    function searchProducts(query) {
        if (!query || query.length < 2) {
            $("#exchange-search-results").empty();
            return;
        }

        const catalog = db.table("catalog")?.data || [];
        const q = query.toLowerCase();
        const results = catalog.filter(p =>
            (p.name?.en && p.name.en.toLowerCase().includes(q)) ||
            (p.name?.fr && p.name.fr.toLowerCase().includes(q)) ||
            (p.ean && p.ean.toLowerCase().includes(q))
        ).slice(0, 8);

        const $container = $("#exchange-search-results").empty();
        results.forEach(product => {
            const name = product.name?.en || product.name?.fr || product.name || 'Product';
            $container.append(`
                <button class="list-group-item list-group-item-action add-exchange-product"
                    data-product='${JSON.stringify({id: product.id, name: name, price: parseFloat(product.price)})}'>
                    <div class="d-flex justify-content-between">
                        <span>${name}</span>
                        <span class="text-success">$${parseFloat(product.price).toFixed(2)}</span>
                    </div>
                </button>
            `);
        });
    }

    // Event: Toggle click on items (touch-friendly)
    $(document).on("click", ".exchange-toggle:not(.disabled)", function() {
        $(this).toggleClass("selected");
        updateExchangeButtonVisibility();
    });

    // Event: Start exchange button
    $(document).on("click", "#btn-start-exchange", function() {
        // Collect selected items from toggles
        selectedItemsForExchange = [];
        $(".exchange-toggle.selected").each(function() {
            const itemData = $(this).data("item");
            if (itemData) {
                // Store the max quantity and set current quantity to max by default
                const item = {
                    ...itemData,
                    max_quantity: itemData.quantity, // Original quantity from sale
                    quantity: itemData.quantity // Start with all units selected
                };
                selectedItemsForExchange.push(item);
            }
        });

        if (selectedItemsForExchange.length === 0) {
            alert("Please select at least one item to exchange");
            return;
        }

        // Reset modal state
        newItemsForExchange = [];
        exchangePayments = [];
        validatedExchangeVoucher = null;
        exchangeAmountDue = 0;
        $("#exchange-product-search").val("");
        $("#exchange-search-results").empty();
        $("#exchange-voucher-code").val("");
        $("#exchange-voucher-result").html("");
        $("#exchange-voucher-input").addClass("d-none");
        $("#exchange-payments-list").html('<div class="text-muted small text-center">No payments added</div>');

        // Load payment methods
        const payments = db.table("payments")?.data || [];
        const $select = $("#exchange-payment-method").empty();
        payments.forEach(p => {
            $select.append(`<option value="${p.code}">${p.name}</option>`);
        });

        // Render and show modal
        renderReturnItems();
        renderNewItems();
        updateExchangeTotals();

        const modal = new bootstrap.Modal(document.getElementById("exchangeModal"));
        modal.show();
    });

    // Event: Search products
    $(document).on("input", "#exchange-product-search", function() {
        const query = $(this).val().trim();
        if (searchTimeout) clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => searchProducts(query), 300);
    });

    // Event: Add product
    $(document).on("click", ".add-exchange-product", function() {
        const product = $(this).data("product");
        const existing = newItemsForExchange.find(i => i.product_id === product.id);
        if (existing) {
            existing.quantity++;
        } else {
            newItemsForExchange.push({
                product_id: product.id,
                name: product.name,
                price: product.price,
                quantity: 1
            });
        }
        $("#exchange-product-search").val("");
        $("#exchange-search-results").empty();
        renderNewItems();
        updateExchangeTotals();
    });

    // Event: Remove new item
    $(document).on("click", ".remove-exchange-item", function() {
        const index = $(this).data("index");
        newItemsForExchange.splice(index, 1);
        renderNewItems();
        updateExchangeTotals();
    });

    // Event: Payment method change
    $(document).on("change", "#exchange-payment-method", function() {
        if ($(this).val() === "VOUCHER") {
            $("#exchange-voucher-input").removeClass("d-none");
            validatedExchangeVoucher = null;
            $("#exchange-voucher-code").val("");
            $("#exchange-voucher-result").html("");
        } else {
            $("#exchange-voucher-input").addClass("d-none");
            validatedExchangeVoucher = null;
        }
    });

    // Event: Validate voucher
    $(document).on("click", "#btn-validate-exchange-voucher", async function() {
        const code = $("#exchange-voucher-code").val().trim().toUpperCase();
        if (!code || code.length !== 12) {
            $("#exchange-voucher-result").html('<span class="text-danger">Code must be 12 characters</span>');
            return;
        }

        const $btn = $(this);
        $btn.prop("disabled", true).text("...");
        $("#exchange-voucher-result").html('<span class="text-muted">Validating...</span>');

        try {
            const res = await fetch(`${APP_BASE_URL}/api/pos/voucher/validate?code=${code}`);
            const data = await res.json();

            if (!data.success) {
                $("#exchange-voucher-result").html(`<span class="text-danger">${data.error || 'Invalid voucher'}</span>`);
                validatedExchangeVoucher = null;
            } else {
                validatedExchangeVoucher = data.voucher;
                validatedExchangeVoucher.code = code;

                // Auto-fill amount with voucher value or remaining
                const totalPaid = exchangePayments.reduce((sum, p) => sum + p.amount, 0);
                const remaining = exchangeAmountDue - totalPaid;
                const voucherAmount = Math.min(parseFloat(data.voucher.amount), remaining);
                $("#exchange-payment-amount").val(voucherAmount.toFixed(2));

                $("#exchange-voucher-result").html(`<span class="text-success"><i class="bi bi-check-circle"></i> Valid: $${data.voucher.amount}</span>`);
            }
        } catch (err) {
            console.error(err);
            $("#exchange-voucher-result").html('<span class="text-danger">Connection error</span>');
            validatedExchangeVoucher = null;
        } finally {
            $btn.prop("disabled", false).text("Validate");
        }
    });

    // Event: Add payment
    $(document).on("click", "#btn-add-exchange-payment", function() {
        const paymentType = $("#exchange-payment-method").val();
        const amount = parseFloat($("#exchange-payment-amount").val());

        if (!paymentType || !amount || amount <= 0) {
            alert("Please select a payment method and enter a valid amount");
            return;
        }

        const totalPaid = exchangePayments.reduce((sum, p) => sum + p.amount, 0);
        const remaining = exchangeAmountDue - totalPaid;

        if (amount > remaining + 0.01) {
            alert(`Amount cannot exceed remaining balance of $${remaining.toFixed(2)}`);
            return;
        }

        // Check voucher validation for VOUCHER payment
        if (paymentType === "VOUCHER") {
            if (!validatedExchangeVoucher) {
                alert("Please validate the voucher code first");
                return;
            }
            if (amount > parseFloat(validatedExchangeVoucher.amount)) {
                alert(`Voucher value is only $${validatedExchangeVoucher.amount}`);
                return;
            }
        }

        const payment = { payment_type: paymentType, amount: amount };
        if (paymentType === "VOUCHER" && validatedExchangeVoucher) {
            payment.voucher_code = validatedExchangeVoucher.code;
        }

        exchangePayments.push(payment);

        // Reset voucher state
        if (paymentType === "VOUCHER") {
            validatedExchangeVoucher = null;
            $("#exchange-voucher-code").val("");
            $("#exchange-voucher-result").html("");
        }

        updateExchangePaymentUI();
        updateExchangeConfirmButton();
    });

    // Event: Remove payment
    $(document).on("click", ".remove-exchange-payment", function() {
        const idx = $(this).data("idx");
        exchangePayments.splice(idx, 1);
        updateExchangePaymentUI();
        updateExchangeConfirmButton();
    });

    // Event: Confirm exchange
    $(document).on("click", "#btn-confirm-exchange", async function() {
        if (selectedItemsForExchange.length === 0) return;

        const $btn = $(this);
        $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1"></span>Processing...');

        const balance = calculateReturnCredit() - calculateNewItemsTotal();

        const payload = {
            original_sale_id: currentSaleForExchange.id,
            shift_id: window.currentShift?.id,
            returned_items: selectedItemsForExchange.map(item => ({
                sale_item_id: item.sale_item_id,
                quantity: item.quantity
            })),
            new_items: newItemsForExchange.map(item => ({
                product_id: item.product_id,
                quantity: item.quantity
            })),
            notes: ""
        };

        // Add payments if customer owes money
        if (balance < 0 && exchangePayments.length > 0) {
            payload.payments = exchangePayments.map(p => ({
                method: p.payment_type.toLowerCase(),
                amount: p.amount,
                voucher_code: p.voucher_code || null
            }));
        }

        try {
            const url = `${APP_BASE_URL}/api/pos/exchange/process`;
            console.log("Exchange API URL:", url);
            console.log("Exchange payload:", JSON.stringify(payload, null, 2));

            const res = await fetch(url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();

            if (!data.success) {
                alert(data.error || "Exchange failed");
                return;
            }

            // Close exchange modal
            bootstrap.Modal.getInstance(document.getElementById("exchangeModal")).hide();

            // Show success
            let successHtml = `<p><strong>Exchange #${data.exchange.id}</strong></p>`;
            successHtml += `<p>Return Credit: $${data.exchange.return_total.toFixed(2)}</p>`;
            successHtml += `<p>New Items: $${data.exchange.new_items_total.toFixed(2)}</p>`;
            successHtml += `<p class="fw-bold">Balance: $${data.exchange.balance.toFixed(2)}</p>`;

            if (data.exchange.voucher_generated) {
                const code = data.exchange.voucher_generated.code;
                const formattedCode = code.replace(/(.{3})/g, '$1 ').trim();
                successHtml += `
                    <div class="alert alert-success mt-3">
                        <h5>Voucher Generated</h5>
                        <div class="fs-4 fw-bold my-2" style="font-family: monospace; letter-spacing: 2px;">${formattedCode}</div>
                        <p class="mb-0">Value: <strong>$${data.exchange.voucher_generated.amount.toFixed(2)}</strong><br>
                        Expires: ${data.exchange.voucher_generated.expires_at}</p>
                    </div>
                `;
            }

            $("#exchange-success-content").html(successHtml);
            const successModal = new bootstrap.Modal(document.getElementById("exchangeSuccessModal"));
            successModal.show();

            // Update the cached sale data with the updated sale from server
            if (data.updated_sale && window.currentJournalSales) {
                const saleIndex = window.currentJournalSales.findIndex(s => s.id === data.updated_sale.id);
                if (saleIndex !== -1) {
                    // Update the sale in cache with new data
                    const cachedSale = window.currentJournalSales[saleIndex];
                    cachedSale.total = data.updated_sale.total;
                    cachedSale.items = data.updated_sale.items;
                }
            }

            // Reset state
            selectedItemsForExchange = [];
            newItemsForExchange = [];
            validatedExchangeVoucher = null;
            $(".exchange-toggle").removeClass("selected");
            updateExchangeButtonVisibility();

        } catch (err) {
            console.error(err);
            alert("Connection error");
        } finally {
            $btn.prop("disabled", false).html('<i class="bi bi-check-lg me-1"></i>Confirm Exchange');
        }
    });

    // Event: Done after exchange success
    $(document).on("click", "#btn-exchange-done", function() {
        // Go back to journal
        $("#btn-back-sales-history").click();
    });

    // Export function to set current sale for exchange (called from app.js showSaleDetail)
    window.setCurrentSaleForExchange = function(sale) {
        currentSaleForExchange = sale;
    };
})();
</script>
@endpush
