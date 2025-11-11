<div id="screen-shiftend" class="pos-screen d-none text-center">

    <!-- Header with menu button + title aligned left -->
    <div class="d-flex justify-content-start align-items-center mb-3 gap-2">
        <button id="btn-open-menu" class="btn btn-outline-secondary" title="@t('Menu')">
            <i class="bi bi-list"></i>
        </button>
        <h2 class="mb-0">End Your Shift</h2>
    </div>

    <p>Enter the final cash amount in the register</p>

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
            <button class="btn btn-outline-success btn-lg w-100" id="shift-end-ok">End Shift</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function initShiftEnd() {
    let buffer = "";
    const $input = $("#shift-end-input");
    $input.val("");

    // Numeric keypad handling
    $(".shift-end-num-btn").off("click").on("click", function() {
        buffer += $(this).text();
        $input.val(buffer);
    });

    // Clear button
    $("#shift-end-clear").off("click").on("click", function() {
        buffer = "";
        $input.val("");
    });

    // End button
    $("#shift-end-ok").off("click").on("click", async function() {
        const amount = parseFloat(buffer);
        if (isNaN(amount)) {
            alert("Please enter a valid amount!");
            return;
        }

        try {
            const res = await fetch(`{{ config('app.url') }}/api/pos/shifts/end`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ user_id: currentUser.id, end_amount: amount })
            });

            if (!res.ok) throw new Error("Unable to end shift");

            currentShift = null;
            $("#btn-end-shift").addClass("d-none");
            const shift = await res.json();
            console.log("Shift ended:", shift);
            alert("Shift ended!");
            logout();
        } catch(err) {
            alert(err.message);
        }
    });
}
</script>
@endpush
