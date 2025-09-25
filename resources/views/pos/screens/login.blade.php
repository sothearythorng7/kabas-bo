<div id="screen-login" class="pos-screen d-none text-center">
    <h2>Authentification</h2>
    <p>Entrez votre code PIN</p>

    <div id="pin-display" class="mb-3 fs-3 fw-bold">••••••</div>

    <div class="row g-2 justify-content-center">
        @for ($i = 1; $i <= 9; $i++)
            <div class="col-4">
                <button class="btn btn-outline-dark btn-lg w-100 pin-btn">{{ $i }}</button>
            </div>
            @if ($i % 3 === 0)
                <div class="w-100"></div>
            @endif
        @endfor
        <div class="col-4">
            <button class="btn btn-outline-danger btn-lg w-100" id="btn-clear">C</button>
        </div>
        <div class="col-4">
            <button class="btn btn-outline-dark btn-lg w-100 pin-btn">0</button>
        </div>
        <div class="col-4">
            <button class="btn btn-outline-success btn-lg w-100" id="btn-enter">OK</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
let pinBuffer = "";

function initLogin() {
    pinBuffer = "";
    updatePinDisplay();

    $(".pin-btn").off("click").on("click", function() {
        if (pinBuffer.length < 6) {
            pinBuffer += $(this).text();
            updatePinDisplay();
        }
    });

    $("#btn-clear").off("click").on("click", function() {
        pinBuffer = "";
        updatePinDisplay();
    });

    $("#btn-enter").off("click").on("click", async function() {
        const users = db.table("users");
        const match = users.findExact({ pin_code: pinBuffer });

        if (match.length > 0) {
            currentUser = match[0];
            console.log("Utilisateur connecté :", currentUser);

            // Modal de synchronisation
            const syncModalEl = document.getElementById('syncModal');
            const syncModal = new bootstrap.Modal(syncModalEl, { backdrop: 'static', keyboard: false });
            syncModal.show();

            try {
                await loadCatalog(currentUser.store_id); // chargement catalogue
                // Vérification shift
                const res = await fetch(`http://kabas.dev-back.fr/api/pos/shifts/current/${currentUser.id}`);
                const shift = await res.json();

                if (!shift || !shift.id) {
                    // Pas de shift → passer à l'écran shiftstart
                    showScreen("shiftstart");
                } else {
                    currentShift = shift;
                    loadSalesFromLocal();
                    renderSalesTabs();
                    showScreen("dashboard");
                }

            } catch (err) {
                alert("Erreur lors de la synchronisation !");
                console.error(err);
            } finally {
                syncModal.hide();
            }

        } else {
            alert("PIN incorrect !");
            pinBuffer = "";
            updatePinDisplay();
        }
    });
}

function updatePinDisplay() {
    let masked = "*".repeat(pinBuffer.length);
    masked = masked.padEnd(6, "•");
    $("#pin-display").text(masked);
}
</script>
@endpush
