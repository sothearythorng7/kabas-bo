<div id="screen-login" class="pos-screen d-none text-center">
    <h2>Authentication</h2>
    <p>Enter your PIN code</p>

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

function updatePinDisplay() {
    let masked = "*".repeat(pinBuffer.length);
    masked = masked.padEnd(6, "•");
    $("#pin-display").text(masked);
}

// Synchro SEULEMENT si le catalogue n'est pas en cache.
// Toujours restaurer les catégories depuis la clef dédiée si déjà présentes.
async function ensureCatalogSyncedIfNeeded(storeId) {
  if (hasCatalogCachedForStore(storeId)) {
    restoreCategoryTreeFromLocal(storeId); // <- lit pos_category_tree_store_{id}
    return;
  }

  const syncModalEl = document.getElementById('syncModal');
  const syncModal = new bootstrap.Modal(syncModalEl, { backdrop: 'static', keyboard: false });
  syncModal.show();
  try {
    await loadCatalog(storeId);                // remplit db (catalog + payments) et window.categoryTree
    writeCatalogCache(storeId);                // sauve catalog + payments
    saveCategoryTreeToLocal(storeId);          // sauve le tree dans la clef dédiée
    console.log("Login sync → hasCategoryTree:", !!window.categoryTree);
  } catch (err) {
    console.error("Catalog sync error:", err);
  } finally {
    syncModal.hide();
  }
}

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

        if (match.length === 0) {
            alert("Incorrect PIN!");
            pinBuffer = "";
            updatePinDisplay();
            return;
        }

        currentUser = match[0];
        console.log("User logged in:", currentUser);

        try {
            // 1) Check current shift
            const res = await fetch(`{{ config('app.url') }}/api/pos/shifts/current/${currentUser.id}`);
            const shift = await res.json();

            if (shift && shift.id) {
                currentShift = shift;
                await ensureCatalogSyncedIfNeeded(currentUser.store_id);

                loadSalesFromLocal();
                renderSalesTabs();
                showScreen("dashboard");
            } else {
                await ensureCatalogSyncedIfNeeded(currentUser.store_id);
                showScreen("shiftstart");
            }
        } catch (e) {
            console.error("Error checking shift:", e);
            // no alert
        }
    });
}
</script>
@endpush
