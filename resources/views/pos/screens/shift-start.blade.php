<div id="screen-shiftstart" class="pos-screen d-none text-center">

    <!-- En-tête : bouton menu + titre alignés à gauche -->
    <div class="d-flex justify-content-start align-items-center mb-3 gap-2">
        <button id="btn-open-menu" class="btn btn-outline-secondary" title="@t('Menu')">
            <i class="bi bi-list"></i>
        </button>
        <h2 class="mb-0">Démarrer votre shift</h2>
    </div>

    <p>Entrez le montant initial dans la caisse</p>

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
            <button class="btn btn-outline-danger btn-lg w-100" id="shift-start-clear">C</button>
        </div>
        <div class="col-4">
            <button class="btn btn-outline-dark btn-lg w-100 shift-num-btn">0</button>
        </div>
        <div class="col-4">
            <button class="btn btn-outline-success btn-lg w-100" id="shift-start-ok">Démarrer</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function initShiftstart() {
    let buffer = "";
    $("#shift-start-input").val("");

    $(".shift-num-btn").off("click").on("click", function() {
        buffer += $(this).text();
        $("#shift-start-input").val(buffer);
    });

    $("#shift-start-clear").off("click").on("click", function() {
        buffer = "";
        $("#shift-start-input").val("");
    });

    $("#shift-start-ok").off("click").on("click", async function() {
        const amount = parseFloat(buffer);
        if (isNaN(amount) || amount <= 0) {
            alert("Veuillez saisir un montant valide !");
            return;
        }

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const res = await fetch(`http://kabas.dev-back.fr/api/pos/shifts/start`, {
                method: "POST",
                headers: { "Content-Type": "application/json",  "X-CSRF-TOKEN": csrfToken },
                body: JSON.stringify({ user_id: currentUser.id, start_amount: amount })
            });
            if (!res.ok) throw new Error("Impossible de démarrer le shift");
            currentShift = await res.json();
            console.log("Shift démarré :", currentShift);
            showScreen("dashboard");
        } catch(err) {
            alert(err.message);
        }
    });
}
</script>
@endpush
