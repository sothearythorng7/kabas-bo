<div id="screen-shiftend" class="pos-screen d-none text-center">

    <!-- En-tête avec bouton menu + titre alignés à gauche -->
    <div class="d-flex justify-content-start align-items-center mb-3 gap-2">
        <button id="btn-open-menu" class="btn btn-outline-secondary" title="@t('Menu')">
            <i class="bi bi-list"></i>
        </button>
        <h2 class="mb-0">Terminer votre shift</h2>
    </div>

    <p>Entrez le montant final dans la caisse</p>

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
            <button class="btn btn-outline-success btn-lg w-100" id="shift-end-ok">Terminer</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function initShiftEnd() {
    let buffer = "";
    const $input = $("#shift-end-input");
    $input.val("");

    // Gestion du pavé numérique
    $(".shift-end-num-btn").off("click").on("click", function() {
        buffer += $(this).text();
        $input.val(buffer);
    });

    // Bouton Clear
    $("#shift-end-clear").off("click").on("click", function() {
        buffer = "";
        $input.val("");
    });

    // Bouton Terminer
    $("#shift-end-ok").off("click").on("click", async function() {
        const amount = parseFloat(buffer);
        if (isNaN(amount)) {
            alert("Veuillez saisir un montant valide !");
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

            if (!res.ok) throw new Error("Impossible de terminer le shift");

            currentShift = null;
            $("#btn-end-shift").addClass("d-none");
            const shift = await res.json();
            console.log("Shift terminé :", shift);
            alert("Shift terminé !");
            logout();
        } catch(err) {
            alert(err.message);
        }
    });
}
</script>
@endpush
