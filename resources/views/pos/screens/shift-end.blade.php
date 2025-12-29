<div id="screen-shiftend" class="pos-screen d-none text-center">

    <!-- Step 1: Visitors Count -->
    <div id="step-visitors" class="shift-step">
        <div class="d-flex justify-content-start align-items-center mb-3 gap-2">
            <button id="btn-open-menu" class="btn btn-outline-secondary" title="{{ __('messages.Menu') }}">
                <i class="bi bi-list"></i>
            </button>
            <h2 class="mb-0">{{ __('messages.Close Shift') }} - {{ __('messages.Visitors') }}</h2>
        </div>

        <p>{{ __('messages.How many visitors came to the store during your shift?') }}</p>

        <input type="text" id="visitors-input" class="form-control mb-3 text-center fs-3" readonly>

        <div class="row g-2 justify-content-center mb-3">
            @for ($i=1; $i<=9; $i++)
                <div class="col-4">
                    <button class="btn btn-outline-dark btn-lg w-100 visitors-num-btn">{{ $i }}</button>
                </div>
                @if ($i % 3 === 0)
                    <div class="w-100"></div>
                @endif
            @endfor
            <div class="col-4">
                <button class="btn btn-outline-danger btn-lg w-100" id="visitors-clear">C</button>
            </div>
            <div class="col-4">
                <button class="btn btn-outline-dark btn-lg w-100 visitors-num-btn">0</button>
            </div>
            <div class="col-4">
                <button class="btn btn-outline-success btn-lg w-100" id="visitors-next">{{ __('messages.Next') }}</button>
            </div>
        </div>
    </div>

    <!-- Step 2: Cash Amount -->
    <div id="step-cash" class="shift-step d-none">
        <div class="d-flex justify-content-start align-items-center mb-3 gap-2">
            <button id="btn-back-to-visitors" class="btn btn-outline-secondary" title="{{ __('messages.Back') }}">
                <i class="bi bi-arrow-left"></i>
            </button>
            <h2 class="mb-0">{{ __('messages.Close Shift') }} - {{ __('messages.Cash Count') }}</h2>
        </div>

        <p>{{ __('messages.Enter the final cash amount in the register') }}</p>

        <input type="text" id="shift-end-input" class="form-control mb-3 text-center fs-3" readonly>

        <div class="row g-2 justify-content-center mb-3">
            @for ($i=1; $i<=9; $i++)
                <div class="col-4">
                    <button class="btn btn-outline-dark btn-lg w-100 shift-end-num-btn">{{ $i }}</button>
                </div>
                @if ($i % 3 === 0)
                    <div class="w-100"></div>
                @endif
            @endfor
            <div class="col-4">
                <button class="btn btn-outline-dark btn-lg w-100 shift-end-num-btn" id="shift-end-decimal">.</button>
            </div>
            <div class="col-4">
                <button class="btn btn-outline-dark btn-lg w-100 shift-end-num-btn">0</button>
            </div>
            <div class="col-4">
                <button class="btn btn-outline-danger btn-lg w-100" id="shift-end-clear">C</button>
            </div>
        </div>
        <div class="row g-2 justify-content-center mb-3">
            <div class="col-12">
                <button class="btn btn-outline-primary btn-lg w-100" id="shift-end-verify">{{ __('messages.Verify') }}</button>
            </div>
        </div>
    </div>

    <!-- Step 3: Verification -->
    <div id="step-verify" class="shift-step d-none">
        <div class="d-flex justify-content-start align-items-center mb-3 gap-2">
            <button id="btn-back-to-cash" class="btn btn-outline-secondary" title="{{ __('messages.Back') }}">
                <i class="bi bi-arrow-left"></i>
            </button>
            <h2 class="mb-0">{{ __('messages.Cash Verification') }}</h2>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <td class="text-start"><strong>{{ __('messages.Opening Cash') }}:</strong></td>
                        <td class="text-end" id="verify-opening">$0.00</td>
                    </tr>
                    <tr>
                        <td class="text-start"><strong>{{ __('messages.Cash Sales') }}:</strong></td>
                        <td class="text-end" id="verify-sales">$0.00</td>
                    </tr>
                    <tr id="row-cash-in" class="d-none">
                        <td class="text-start"><strong class="text-success">+ Cash In:</strong></td>
                        <td class="text-end text-success" id="verify-cash-in">$0.00</td>
                    </tr>
                    <tr id="row-cash-out" class="d-none">
                        <td class="text-start"><strong class="text-danger">- Cash Out:</strong></td>
                        <td class="text-end text-danger" id="verify-cash-out">$0.00</td>
                    </tr>
                    <tr class="border-top">
                        <td class="text-start"><strong>{{ __('messages.Expected Amount') }}:</strong></td>
                        <td class="text-end"><strong class="text-primary" id="verify-expected">$0.00</strong></td>
                    </tr>
                    <tr>
                        <td class="text-start"><strong>{{ __('messages.Counted Amount') }}:</strong></td>
                        <td class="text-end" id="verify-counted">$0.00</td>
                    </tr>
                    <tr class="border-top">
                        <td class="text-start"><strong>{{ __('messages.Difference') }}:</strong></td>
                        <td class="text-end"><strong id="verify-difference">$0.00</strong></td>
                    </tr>
                </table>

                <div id="verify-alert" class="alert d-none mt-3">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span id="verify-alert-text"></span>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2">
            <button class="btn btn-warning btn-lg d-none" id="btn-correct-amount">
                <i class="bi bi-pencil"></i> {{ __('messages.Correct Amount') }}
            </button>
            <button class="btn btn-success btn-lg" id="shift-end-confirm">
                <span id="confirm-btn-text">{{ __('messages.Confirm') }}</span>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
window.posTranslations = window.posTranslations || {};
window.posTranslations.shiftEnd = {
    pleaseEnterValidAmount: @json(__('messages.Please enter a valid amount!')),
    confirm: @json(__('messages.Confirm')),
    thereIsMoreCash: @json(__('messages.There is more cash than expected')),
    thereIsMissingCash: @json(__('messages.There is missing cash')),
    forceClose: @json(__('messages.Force Close')),
    errorFetchingExpectedCash: @json(__('messages.Error fetching expected cash. Proceeding without verification.')),
    shiftEnded: @json(__('messages.Shift ended!'))
};

function initShiftEnd() {
    const t = window.posTranslations.shiftEnd;

    // Variables to store shift end data
    let visitorsCount = 0;
    let cashAmount = 0;
    let expectedData = null;

    // Buffers for keypads
    let visitorsBuffer = "";
    let cashBuffer = "";

    // Reset to step 1
    resetShiftEnd();

    console.log("✅ initShiftEnd() called - event handlers registered");
    console.log("Found visitors buttons:", $(".visitors-num-btn").length);
    console.log("Found cash buttons:", $(".shift-end-num-btn").length);

    // ===== STEP 1: VISITORS COUNT =====
    $(document).off("click", ".visitors-num-btn").on("click", ".visitors-num-btn", function() {
        console.log("Visitor button clicked:", $(this).text());
        visitorsBuffer += $(this).text();
        $("#visitors-input").val(visitorsBuffer);
        console.log("visitorsBuffer now:", visitorsBuffer);
    });

    $(document).off("click", "#visitors-clear").on("click", "#visitors-clear", function() {
        visitorsBuffer = "";
        $("#visitors-input").val("");
    });

    $(document).off("click", "#visitors-next").on("click", "#visitors-next", function() {
        visitorsCount = parseInt(visitorsBuffer) || 0;
        showStep("step-cash");
        cashBuffer = "";
        $("#shift-end-input").val("");
    });

    // ===== STEP 2: CASH AMOUNT =====
    $(document).off("click", "#btn-back-to-visitors").on("click", "#btn-back-to-visitors", function() {
        showStep("step-visitors");
    });

    $(document).off("click", ".shift-end-num-btn").on("click", ".shift-end-num-btn", function() {
        const char = $(this).text();
        console.log("Cash button clicked:", char);
        // Prevent multiple decimal points
        if (char === "." && cashBuffer.includes(".")) {
            return;
        }
        cashBuffer += char;
        $("#shift-end-input").val(cashBuffer);
        console.log("cashBuffer now:", cashBuffer);
    });

    $(document).off("click", "#shift-end-clear").on("click", "#shift-end-clear", function() {
        cashBuffer = "";
        $("#shift-end-input").val("");
    });

    $(document).off("click", "#shift-end-verify").on("click", "#shift-end-verify", async function() {
        console.log("cashBuffer value:", cashBuffer);
        cashAmount = parseFloat(cashBuffer);
        console.log("cashAmount parsed:", cashAmount);
        if (isNaN(cashAmount)) {
            alert(t.pleaseEnterValidAmount);
            return;
        }

        // Sync sales to backend BEFORE fetching expected cash
        // This ensures all local sales are counted in the expected amount
        try {
            console.log("Syncing sales before cash verification...");
            // Check what's in localStorage before sync
            const preKey = `pos_sales_validated_shift_${currentShift.id}`;
            const preSales = JSON.parse(localStorage.getItem(preKey)) || [];
            console.log("Sales in localStorage before sync:", preSales.length, preSales);

            await syncSalesToBO();

            // Small delay to ensure DB write is complete
            await new Promise(resolve => setTimeout(resolve, 500));

            console.log("Sales sync completed");
        } catch (syncErr) {
            console.warn("Sales sync failed, continuing with verification:", syncErr);
        }

        // Fetch expected cash from API
        try {
            const res = await fetch(`{{ config('app.url') }}/api/pos/shifts/expected-cash/${currentUser.id}`);
            if (!res.ok) throw new Error("Unable to fetch expected cash");

            expectedData = await res.json();

            // Get Cash In/Out from localStorage (linked to current shift)
            const cashIn = window.getShiftCashIn ? window.getShiftCashIn() : 0;
            const cashOut = window.getShiftCashOut ? window.getShiftCashOut() : 0;

            // Calculate adjusted expected cash: Opening + Sales + CashIn - CashOut
            const adjustedExpected = expectedData.expected_cash + cashIn - cashOut;

            // Display verification data
            $("#verify-opening").text("$" + expectedData.opening_cash.toFixed(2));
            $("#verify-sales").text("$" + expectedData.cash_from_sales.toFixed(2));

            // Show Cash In row if > 0
            if (cashIn > 0) {
                $("#row-cash-in").removeClass("d-none");
                $("#verify-cash-in").text("$" + cashIn.toFixed(2));
            } else {
                $("#row-cash-in").addClass("d-none");
            }

            // Show Cash Out row if > 0
            if (cashOut > 0) {
                $("#row-cash-out").removeClass("d-none");
                $("#verify-cash-out").text("$" + cashOut.toFixed(2));
            } else {
                $("#row-cash-out").addClass("d-none");
            }

            $("#verify-expected").text("$" + adjustedExpected.toFixed(2));
            $("#verify-counted").text("$" + cashAmount.toFixed(2));

            // Store adjusted expected for difference calculation
            expectedData.adjusted_expected = adjustedExpected;
            expectedData.cash_in = cashIn;
            expectedData.cash_out = cashOut;

            const difference = cashAmount - adjustedExpected;
            $("#verify-difference").text((difference >= 0 ? "+$" : "-$") + Math.abs(difference).toFixed(2));

            // Color code the difference
            if (difference === 0) {
                $("#verify-difference").removeClass("text-danger").addClass("text-success");
                $("#verify-alert").addClass("d-none");
                $("#btn-correct-amount").addClass("d-none");
                $("#confirm-btn-text").text(t.confirm);
            } else {
                $("#verify-difference").removeClass("text-success").addClass(difference > 0 ? "text-success" : "text-danger");
                $("#verify-alert").removeClass("d-none alert-success alert-danger")
                    .addClass(difference > 0 ? "alert-success" : "alert-danger");
                $("#verify-alert-text").text(
                    difference > 0
                        ? t.thereIsMoreCash
                        : t.thereIsMissingCash
                );
                $("#btn-correct-amount").removeClass("d-none");
                $("#confirm-btn-text").text(t.forceClose);
            }

            showStep("step-verify");
        } catch(err) {
            console.error(err);
            alert(t.errorFetchingExpectedCash);
            // Proceed directly to end shift without verification
            await endShift();
        }
    });

    // ===== STEP 3: VERIFICATION =====
    $(document).off("click", "#btn-back-to-cash").on("click", "#btn-back-to-cash", function() {
        showStep("step-cash");
    });

    $(document).off("click", "#btn-correct-amount").on("click", "#btn-correct-amount", function() {
        // Retour à l'écran de saisie du cash pour correction
        showStep("step-cash");
    });

    $(document).off("click", "#shift-end-confirm").on("click", "#shift-end-confirm", async function() {
        await endShift();
    });

    // ===== HELPER FUNCTIONS =====
    function showStep(stepId) {
        $(".shift-step").addClass("d-none");
        $("#" + stepId).removeClass("d-none");
    }

    function resetShiftEnd() {
        visitorsBuffer = "";
        cashBuffer = "";
        visitorsCount = 0;
        cashAmount = 0;
        expectedData = null;

        $("#visitors-input").val("");
        $("#shift-end-input").val("");

        showStep("step-visitors");
    }

    async function endShift() {
        // Use adjusted expected (with Cash In/Out) for difference calculation
        const difference = expectedData ? (cashAmount - (expectedData.adjusted_expected ?? expectedData.expected_cash)) : null;
        const shiftIdToClean = currentShift ? currentShift.id : null;

        try {
            const res = await fetch(`{{ config('app.url') }}/api/pos/shifts/end`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    user_id: currentUser.id,
                    end_amount: cashAmount,
                    visitors_count: visitorsCount > 0 ? visitorsCount : null,
                    cash_difference: difference,
                    cash_in: expectedData?.cash_in ?? 0,
                    cash_out: expectedData?.cash_out ?? 0
                })
            });

            if (!res.ok) throw new Error("Unable to end shift");

            // Clean up Cash In/Out from localStorage for this shift
            if (shiftIdToClean && window.clearShiftCashInOut) {
                window.clearShiftCashInOut(shiftIdToClean);
            }

            currentShift = null;
            $("#btn-end-shift").addClass("d-none");
            const shift = await res.json();
            console.log("Shift ended:", shift);
            alert(t.shiftEnded);
            logout();
        } catch(err) {
            alert(err.message);
        }
    }
}

// Do NOT call initShiftEnd automatically - it's called from app.js when showing the screen
</script>
@endpush
