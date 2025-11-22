<div id="screen-shiftend" class="pos-screen d-none text-center">

    <!-- Step 1: Visitors Count -->
    <div id="step-visitors" class="shift-step">
        <div class="d-flex justify-content-start align-items-center mb-3 gap-2">
            <button id="btn-open-menu" class="btn btn-outline-secondary" title="@t('Menu')">
                <i class="bi bi-list"></i>
            </button>
            <h2 class="mb-0">@t('Close Shift') - @t('Visitors')</h2>
        </div>

        <p>@t('How many visitors came to the store during your shift?')</p>

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
                <button class="btn btn-outline-success btn-lg w-100" id="visitors-next">@t('Next')</button>
            </div>
        </div>
    </div>

    <!-- Step 2: Cash Amount -->
    <div id="step-cash" class="shift-step d-none">
        <div class="d-flex justify-content-start align-items-center mb-3 gap-2">
            <button id="btn-back-to-visitors" class="btn btn-outline-secondary" title="@t('Back')">
                <i class="bi bi-arrow-left"></i>
            </button>
            <h2 class="mb-0">@t('Close Shift') - @t('Cash Count')</h2>
        </div>

        <p>@t('Enter the final cash amount in the register')</p>

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
                <button class="btn btn-outline-danger btn-lg w-100" id="shift-end-clear">C</button>
            </div>
            <div class="col-4">
                <button class="btn btn-outline-dark btn-lg w-100 shift-end-num-btn">0</button>
            </div>
            <div class="col-4">
                <button class="btn btn-outline-primary btn-lg w-100" id="shift-end-verify">@t('Verify')</button>
            </div>
        </div>
    </div>

    <!-- Step 3: Verification -->
    <div id="step-verify" class="shift-step d-none">
        <div class="d-flex justify-content-start align-items-center mb-3 gap-2">
            <button id="btn-back-to-cash" class="btn btn-outline-secondary" title="@t('Back')">
                <i class="bi bi-arrow-left"></i>
            </button>
            <h2 class="mb-0">@t('Cash Verification')</h2>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <td class="text-start"><strong>@t('Opening Cash'):</strong></td>
                        <td class="text-end" id="verify-opening">$0.00</td>
                    </tr>
                    <tr>
                        <td class="text-start"><strong>@t('Cash Sales'):</strong></td>
                        <td class="text-end" id="verify-sales">$0.00</td>
                    </tr>
                    <tr class="border-top">
                        <td class="text-start"><strong>@t('Expected Amount'):</strong></td>
                        <td class="text-end"><strong class="text-primary" id="verify-expected">$0.00</strong></td>
                    </tr>
                    <tr>
                        <td class="text-start"><strong>@t('Counted Amount'):</strong></td>
                        <td class="text-end" id="verify-counted">$0.00</td>
                    </tr>
                    <tr class="border-top">
                        <td class="text-start"><strong>@t('Difference'):</strong></td>
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
                <i class="bi bi-pencil"></i> @t('Correct Amount')
            </button>
            <button class="btn btn-success btn-lg" id="shift-end-confirm">
                <span id="confirm-btn-text">@t('Confirm')</span>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function initShiftEnd() {
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
        console.log("Cash button clicked:", $(this).text());
        cashBuffer += $(this).text();
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
            alert("@t('Please enter a valid amount!')");
            return;
        }

        // Fetch expected cash from API
        try {
            const res = await fetch(`{{ config('app.url') }}/api/pos/shifts/expected-cash/${currentUser.id}`);
            if (!res.ok) throw new Error("Unable to fetch expected cash");

            expectedData = await res.json();

            // Display verification data
            $("#verify-opening").text("$" + expectedData.opening_cash.toFixed(2));
            $("#verify-sales").text("$" + expectedData.cash_from_sales.toFixed(2));
            $("#verify-expected").text("$" + expectedData.expected_cash.toFixed(2));
            $("#verify-counted").text("$" + cashAmount.toFixed(2));

            const difference = cashAmount - expectedData.expected_cash;
            $("#verify-difference").text((difference >= 0 ? "+$" : "-$") + Math.abs(difference).toFixed(2));

            // Color code the difference
            if (difference === 0) {
                $("#verify-difference").removeClass("text-danger").addClass("text-success");
                $("#verify-alert").addClass("d-none");
                $("#btn-correct-amount").addClass("d-none");
                $("#confirm-btn-text").text("@t('Confirm')");
            } else {
                $("#verify-difference").removeClass("text-success").addClass(difference > 0 ? "text-success" : "text-danger");
                $("#verify-alert").removeClass("d-none alert-success alert-danger")
                    .addClass(difference > 0 ? "alert-success" : "alert-danger");
                $("#verify-alert-text").text(
                    difference > 0
                        ? "@t('There is more cash than expected')"
                        : "@t('There is missing cash')"
                );
                $("#btn-correct-amount").removeClass("d-none");
                $("#confirm-btn-text").text("@t('Force Close')");
            }

            showStep("step-verify");
        } catch(err) {
            console.error(err);
            alert("@t('Error fetching expected cash. Proceeding without verification.')");
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
        const difference = expectedData ? (cashAmount - expectedData.expected_cash) : null;

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
                    cash_difference: difference
                })
            });

            if (!res.ok) throw new Error("Unable to end shift");

            currentShift = null;
            $("#btn-end-shift").addClass("d-none");
            const shift = await res.json();
            console.log("Shift ended:", shift);
            alert("@t('Shift ended!')");
            logout();
        } catch(err) {
            alert(err.message);
        }
    }
}

// Do NOT call initShiftEnd automatically - it's called from app.js when showing the screen
</script>
@endpush
