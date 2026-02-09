<div id="screen-shiftstart" class="pos-screen d-none text-center" style="height: 100vh; overflow-y: auto; padding-bottom: 20px;">

    <!-- Header: menu button (disabled) + title aligned left -->
    <div class="d-flex justify-content-start align-items-center mb-2 gap-2">
        <button id="btn-open-menu" class="btn btn-outline-secondary btn-sm" title="{{ __('messages.Menu') }}" disabled style="opacity: 0.5; cursor: not-allowed;">
            <i class="bi bi-list"></i>
        </button>
        <h5 class="mb-0">{{ __('messages.pos.start_your_shift') }}</h5>
    </div>

    <!-- Quick actions: Planning & Leave Request -->
    <div class="row g-2 mb-3">
        <div class="col-6">
            <button class="btn btn-outline-info w-100 py-2" id="btn-my-planning-preshift">
                <i class="bi bi-calendar-week fs-5 d-block mb-1"></i>
                <small>{{ __('messages.my_planning.menu_title') }}</small>
            </button>
        </div>
        <div class="col-6">
            <button class="btn btn-outline-success w-100 py-2" id="btn-leave-request-preshift">
                <i class="bi bi-calendar-plus fs-5 d-block mb-1"></i>
                <small>{{ __('messages.staff.request_leave') }}</small>
            </button>
        </div>
    </div>

    <hr class="my-2">

    <p class="small mb-2">{{ __('messages.pos.enter_initial_cash') }}</p>

    <input type="text" id="shift-start-input" class="form-control mb-3 text-center fs-3" readonly>

    <div class="row g-2 justify-content-center mb-3">
        @for ($i=1; $i<=9; $i++)
            <div class="col-4">
                <button class="btn btn-outline-dark btn-lg w-100 shift-num-btn">{{ $i }}</button>
            </div>
            @if ($i % 3 === 0)
                <div class="w-100"></div>
            @endif
        @endfor
        <div class="col-4">
            <button class="btn btn-outline-dark btn-lg w-100 shift-num-btn" id="shift-start-decimal">.</button>
        </div>
        <div class="col-4">
            <button class="btn btn-outline-dark btn-lg w-100 shift-num-btn">0</button>
        </div>
        <div class="col-4">
            <button class="btn btn-outline-danger btn-lg w-100" id="shift-start-clear">C</button>
        </div>
    </div>
    <div class="row g-2 justify-content-center mb-3">
        <div class="col-12">
            <button class="btn btn-outline-success btn-lg w-100" id="shift-start-ok">Start</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function initShiftstart() {
    let buffer = "";
    $("#shift-start-input").val("");

    $(".shift-num-btn").off("click").on("click", function() {
        const char = $(this).text();
        // Prevent multiple decimal points
        if (char === "." && buffer.includes(".")) {
            return;
        }
        buffer += char;
        $("#shift-start-input").val(buffer);
    });

    $("#shift-start-clear").off("click").on("click", function() {
        buffer = "";
        $("#shift-start-input").val("");
    });

    $("#shift-start-ok").off("click").on("click", async function() {
        const amount = parseFloat(buffer);
        if (isNaN(amount) || amount <= 0) {
            alert("Please enter a valid amount!");
            return;
        }

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const res = await fetch(`{{ config('app.url') }}/api/pos/shifts/start`, {
                method: "POST",
                headers: { "Content-Type": "application/json",  "X-CSRF-TOKEN": csrfToken },
                body: JSON.stringify({ user_id: currentUser.id, start_amount: amount })
            });
            if (!res.ok) throw new Error("Unable to start shift");
            currentShift = await res.json();
            console.log("Shift started:", currentShift);
            showScreen("dashboard");
        } catch(err) {
            alert(err.message);
        }
    });
}
</script>
@endpush
