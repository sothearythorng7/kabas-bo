<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- jQuery & Bootstrap Bundle -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- CSS spécifique POS -->
    <link href="{{ asset('css/pos/main.css') }}" rel="stylesheet">

    @stack('styles')
</head>
<body>
    <div class="container py-4">
        <!-- Conteneur principal -->
        <div id="pos-container" class="mt-4">

            <!-- Écrans -->
            @include('pos.screens.dashboard')
            @include('pos.screens.sales')
            @include('pos.screens.products')
            @include('pos.screens.login')
            @include('pos.screens.shift-start')
            @include('pos.screens.shift-end')
            @include('pos.screens.journal')
            @include('pos.screens.search-results')
            @include('pos.screens.sale-detail')
        </div>

        <!-- ===== GLOBAL Side Menu (present everywhere except login) ===== -->
        <div id="side-menu" class="position-fixed top-0 start-0 vh-100 bg-white shadow"
             style="width:0; max-width:30%; overflow:auto; z-index:1050; transition: width 0.3s;">
            <div class="d-flex justify-content-end p-2 border-bottom">
                <button class="btn btn-sm btn-outline-dark" id="btn-close-menu"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="p-3">
                <button id="btn-go-dashboard" class="btn btn-dark w-100 mb-2">
                    <i class="bi bi-house"></i> @t("Dashboard")
                </button>
                <button id="btn-end-shift" class="btn btn-warning w-100 mb-2">@t("Close Shift")</button>
                <button id="btn-journal" class="btn btn-primary w-100 mb-2">Journal</button>
                <hr />
                <button id="btn-logout" class="btn btn-danger w-100 mb-2">@t("logout")</button>
                <button id="btn-force-sync" class="btn btn-info w-100 mb-2">@t("Force catalog sync")</button>

                <!-- NEW: Cash In / Cash Out -->
                <button id="btn-cash-in"  class="btn btn-success w-100 mb-2"><i class="bi bi-plus-circle"></i> Cash In</button>
                <button id="btn-cash-out" class="btn btn-danger  w-100 mb-2"><i class="bi bi-dash-circle"></i> Cash Out</button>
            </div>
        </div>

        <!-- Global overlay -->
        <div id="side-menu-overlay" class="position-fixed top-0 start-0 w-100 h-100"
             style="display:none; z-index:1040; background: rgba(0,0,0,0.4);"></div>
        <!-- ===== End GLOBAL Side Menu ===== -->

    </div>

    <!-- Core DB -->
    <script src="{{ asset('js/pos/core/Table.js') }}"></script>
    <script src="{{ asset('js/pos/core/Database.js') }}"></script>

    <!-- Tables -->
    <script src="{{ asset('js/pos/tables/UsersTable.js') }}"></script>
    <script src="{{ asset('js/pos/tables/CatalogTable.js') }}"></script>
    <script src="{{ asset('js/pos/tables/PaymentsTable.js') }}"></script>

    <!-- App -->
    <script>
        // Base URL for API calls
        const APP_BASE_URL = '{{ config('app.url') }}';
    </script>
    <script src="{{ asset('js/pos/app.js') }}"></script>

<script>
// --- GLOBALES --- //
window.selectedParentId = window.selectedParentId ?? null;
window.selectedChildId  = window.selectedChildId  ?? null;
window.selectedPath     = window.selectedPath     ?? [];
window.currentQuery     = window.currentQuery     ?? "";

window.sales        = window.sales        ?? [];
window.saleCounter  = window.saleCounter  ?? 1;
window.currentUser  = window.currentUser  ?? null;
window.currentShift = window.currentShift ?? null;

// Arbre des catégories (mis en mémoire à chaud)
window.categoryTree = window.categoryTree ?? null;

// --------------------------------------------------
// Cache localStorage (catalog + payments) PAR STORE
// + CLEF DÉDIÉE pour categoryTree
// --------------------------------------------------

function getCatalogCacheKey(storeId) {
  return `pos_catalog_cache_v1_store_${storeId}`;
}
function getCategoryKey(storeId) {
  return `pos_category_tree_store_${storeId}`;
}

// -- CATALOG/PAYMENTS CACHE --
function writeCatalogCache(storeId) {
  try {
    const catalog  = (db.table("catalog")?.data)  ?? [];
    const payments = (db.table("payments")?.data) ?? [];
    const payload = { catalog, payments, savedAt: Date.now() };
    localStorage.setItem(getCatalogCacheKey(storeId), JSON.stringify(payload));
    console.log("✅ Catalog cache written (store:", storeId, ")", {catalog: catalog?.length ?? 0, payments: payments?.length ?? 0});
  } catch (e) {
    console.warn("❌ writeCatalogCache error:", e);
  }
}
function readCatalogCache(storeId) {
  try {
    const raw = localStorage.getItem(getCatalogCacheKey(storeId));
    if (!raw) return null;
    return JSON.parse(raw);
  } catch (e) {
    console.warn("❌ readCatalogCache error:", e);
    return null;
  }
}
// hydrate DB (catalog + payments) depuis cache
function hydrateCatalogFromCache(storeId) {
  const cached = readCatalogCache(storeId);
  if (!cached) return false;
  try {
    const catTable = db.table("catalog");
    const payTable = db.table("payments");
    if (catTable) catTable.data = Array.isArray(cached.catalog) ? cached.catalog : [];
    if (payTable) payTable.data = Array.isArray(cached.payments) ? cached.payments : [];
    return (catTable && Array.isArray(catTable.data) && catTable.data.length > 0);
  } catch (e) {
    console.warn("❌ hydrateCatalogFromCache error:", e);
    return false;
  }
}
// a-t-on le catalogue sans synchro ?
function hasCatalogCachedForStore(storeId) {
  try {
    const t = db.table("catalog");
    const hasCatalogInDB = t && Array.isArray(t.data) && t.data.length > 0;
    if (hasCatalogInDB) return true;
    return hydrateCatalogFromCache(storeId);
  } catch {
    return hydrateCatalogFromCache(storeId);
  }
}

// -- CATEGORY TREE (CLEF DÉDIÉE) --
function saveCategoryTreeToLocal(storeId) {
  if (!storeId) return;
  try {
    const tree = window.categoryTree ?? null;
    localStorage.setItem(getCategoryKey(storeId), JSON.stringify({ tree, savedAt: Date.now() }));
    console.log("✅ Categories saved (store:", storeId, ") hasTree:", !!tree);
  } catch (e) {
    console.warn("❌ saveCategoryTreeToLocal error:", e);
  }
}
function readCategoryTreeFromLocal(storeId) {
  try {
    const raw = localStorage.getItem(getCategoryKey(storeId));
    if (!raw) return null;
    const parsed = JSON.parse(raw);
    return parsed?.tree ?? null;
  } catch (e) {
    console.warn("❌ readCategoryTreeFromLocal error:", e);
    return null;
  }
}
function restoreCategoryTreeFromLocal(storeId) {
  if (!storeId) return;
  const tree = readCategoryTreeFromLocal(storeId);
  if (tree) {
    window.categoryTree = tree;
    console.log("✅ Categories restored from dedicated key (store:", storeId, ")");
  } else {
    console.log("ℹ️ No categories to restore (store:", storeId, ")");
  }
}
</script>

    <!-- Scripts spécifiques poussés par chaque écran -->
    @stack('scripts')

    <!-- Modal synchronisation -->
    <div class="modal fade" id="syncModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center p-4">
                <div class="modal-body">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p>Synchronisation en cours...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Cash In/Out (réutilisable) -->
    <div class="modal fade" id="cashDialogModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3">
          <h5 id="cashDialogTitle" class="mb-3">Cash</h5>

          <input type="text" id="cashDialogInput" class="form-control mb-3 text-center fs-3" readonly>

          <div class="row g-2 justify-content-center mb-3" id="cashDialogPad">
            <!-- pavé numérique injecté par JS -->
          </div>

          <div class="d-flex justify-content-end">
            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-success" id="cashDialogOk">Validate</button>
          </div>
        </div>
      </div>
    </div>

    <script>
    window.i18n = {
        yes: @json(__('messages.yes')),
        no: @json(__('messages.no')),
        running: @json(__('messages.en_cours')),
        No_global_discount: @json(__('messages.No_global_discount')),
    };
    </script>

    <!-- ===== Handlers GLOBAUX du menu + visibilité “sauf login” ===== -->
    <script>
    (function() {
        const $menu   = $("#side-menu");
        const $overlay= $("#side-menu-overlay");

        // ✅ utilitaire: fermer le menu partout
        function closeSideMenu() {
            $("#side-menu").css("width", "0");
            $("#side-menu-overlay").hide();
        }

        // Ouverture depuis n'importe quel écran ayant un #btn-open-menu
        $(document).on("click", "#btn-open-menu", function() {
            // si login visible ou pas de shift actif, on ignore
            if (isLoginVisible() || !hasActiveShift() || isShiftStartVisible()) return;
            $menu.css("width", "30%");
            $overlay.show();
        });

        $(document).on("click", "#btn-go-dashboard", function() {
            $("#side-menu").css("width", "0");
            $("#side-menu-overlay").hide();
            showScreen("dashboard");
        });

        // Fermeture (bouton ou clic overlay)
        $(document).on("click", "#btn-close-menu, #side-menu-overlay", function() {
            $menu.css("width", "0");
            $overlay.hide();
        });

        // ✅ Fermer le menu quand on clique n'importe quel bouton/lien DANS le menu
        $(document).on("click", "#side-menu .btn, #side-menu a", function() {
            closeSideMenu();
        });

        // ✅ Wrap non-intrusif de showScreen pour fermer le menu avant navigation
        if (typeof window.showScreen === "function" && !window.__menuWrappedShowScreen) {
            const __origShowScreen = window.showScreen;
            window.showScreen = function() {
                closeSideMenu();
                return __origShowScreen.apply(this, arguments);
            };
            window.__menuWrappedShowScreen = true;
        }

        // Masquer automatiquement le menu sur l'écran de login ou shift-start
        function isLoginVisible() {
            const $login = $("#screen-login");
            return $login.length && !$login.hasClass("d-none");
        }
        function isShiftStartVisible() {
            const $shiftStart = $("#screen-shiftstart");
            return $shiftStart.length && !$shiftStart.hasClass("d-none");
        }
        function hasActiveShift() {
            return window.currentShift && window.currentShift.id;
        }
        function updateMenuVisibility() {
            // Désactiver le menu si: login visible OU pas de shift actif OU écran shift-start visible
            if (isLoginVisible() || !hasActiveShift() || isShiftStartVisible()) {
                // force close + disable overlay
                $menu.css("width", "0");
                $overlay.hide();
                $menu.attr("aria-hidden", "true");
            } else {
                $menu.removeAttr("aria-hidden");
            }
        }

        // Observer : dès qu’un écran change (affichage par d-none), on met à jour
        const target = document.getElementById("pos-container");
        if (target) {
            const obs = new MutationObserver(() => {
                updateMenuVisibility();
                // ✅ ferme le menu si un nouvel écran vient d'être affiché
                if ($("#side-menu").width() > 0) closeSideMenu();
            });
            obs.observe(target, { attributes: true, subtree: true, attributeFilter: ["class"] });
        }
        // Init au chargement
        $(updateMenuVisibility);
    })();
    </script>

    <!-- ===== Cash In / Cash Out : logique modale + cumul localStorage ===== -->
    <script>
    (function() {
      const CASH_IN_KEY  = "pos_cash_in_total";
      const CASH_OUT_KEY = "pos_cash_out_total";

      // Construit le pavé numérique une fois
      function ensureCashPadBuilt() {
        const $pad = $("#cashDialogPad");
        if ($pad.children().length) return;
        const rows = [];
        for (let i = 1; i <= 9; i++) {
          rows.push(`<div class="col-4"><button class="btn btn-outline-dark btn-lg w-100 cash-num">${i}</button></div>`);
          if (i % 3 === 0) rows.push(`<div class="w-100"></div>`);
        }
        rows.push(`<div class="col-4"><button class="btn btn-outline-danger btn-lg w-100" id="cash-clear">C</button></div>`);
        rows.push(`<div class="col-4"><button class="btn btn-outline-dark btn-lg w-100 cash-num">0</button></div>`);
        rows.push(`<div class="col-4"><button class="btn btn-outline-dark btn-lg w-100" id="cash-dot">.</button></div>`);
        $pad.html(rows.join(""));
      }

      function openCashDialog(title, storageKey) {
        $("#cashDialogTitle").text(title);
        $("#cashDialogInput").val("");
        ensureCashPadBuilt();

        const modalEl = document.getElementById("cashDialogModal");
        const modal = new bootstrap.Modal(modalEl, { backdrop: "static", keyboard: false });
        modal.show();

        // Délégations d'événements (pas de doublons)
        $("#cashDialogPad").off("click", ".cash-num").on("click", ".cash-num", function() {
          const cur = $("#cashDialogInput").val();
          $("#cashDialogInput").val(cur + $(this).text());
        });
        $("#cash-clear").off("click").on("click", function() {
          $("#cashDialogInput").val("");
        });
        $("#cash-dot").off("click").on("click", function() {
          const cur = $("#cashDialogInput").val();
          if (!cur.includes(".")) $("#cashDialogInput").val(cur ? cur + "." : "0.");
        });

        $("#cashDialogOk").off("click").on("click", function() {
          const raw = $("#cashDialogInput").val();
          const amount = parseFloat(raw);
          if (!Number.isFinite(amount) || amount <= 0) {
            alert("Please enter a valid amount!");
            return;
          }
          try {
            const prev = parseFloat(localStorage.getItem(storageKey) || "0") || 0;
            const next = prev + amount;
            localStorage.setItem(storageKey, String(next));
            modal.hide();
            alert(`${title}: ${amount.toFixed(2)} — Cumulative Total: ${next.toFixed(2)}`);
          } catch (e) {
            console.error("localStorage error:", e);
            alert("Error saving!");
          }
        });
      }

      // Boutons du menu
      $(document).on("click", "#btn-cash-in", function() {
        openCashDialog("Cash In", CASH_IN_KEY);
      });
      $(document).on("click", "#btn-cash-out", function() {
        openCashDialog("Cash Out", CASH_OUT_KEY);
      });
    })();
    </script>
</body>
</html>
