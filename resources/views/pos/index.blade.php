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
    @php
        // Force cache bust - utilise timestamp actuel
        $posVersion = time();
    @endphp
    <link href="{{ asset('css/pos/main.css') }}?v={{ $posVersion }}" rel="stylesheet">
    <link href="{{ asset('css/pos/virtual-keyboard.css') }}?v={{ $posVersion }}" rel="stylesheet">

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
            @include('pos.screens.my-planning')
            @include('pos.screens.leave-request')
        </div>

        <!-- ===== GLOBAL Side Menu (present everywhere except login) ===== -->
        <div id="side-menu" class="position-fixed top-0 start-0 vh-100 bg-white shadow"
             style="width:0; max-width:30%; overflow:auto; z-index:1050; transition: width 0.3s;">
            <div class="d-flex justify-content-end p-2 border-bottom">
                <button class="btn btn-sm btn-outline-dark" id="btn-close-menu"><i class="bi bi-x-lg"></i></button>
            </div>
            <!-- Expected Cash Display -->
            <div id="expected-cash-display" class="p-3 bg-dark text-white text-center" style="display: none;">
                <div class="small text-white-50">{{ __('messages.pos.expected_cash') }}</div>
                <div class="fs-3 fw-bold" id="expected-cash-amount">$0.00</div>
            </div>
            <div class="p-3">
                <button id="btn-go-dashboard" class="btn btn-dark w-100 mb-2">
                    <i class="bi bi-house"></i> {{ __('messages.Dashboard') }}
                </button>
                <button id="btn-end-shift" class="btn btn-warning w-100 mb-2">{{ __('messages.Close Shift') }}</button>
                <button id="btn-journal" class="btn btn-primary w-100 mb-2">Journal</button>
                <hr />

                <!-- Planning & Leave Request -->
                <button id="btn-my-planning" class="btn btn-info w-100 mb-2">
                    <i class="bi bi-calendar-week"></i> {{ __('messages.my_planning.menu_title') }}
                </button>
                <button id="btn-leave-request" class="btn btn-success w-100 mb-2">
                    <i class="bi bi-calendar-plus"></i> {{ __('messages.staff.request_leave') }}
                </button>
                <hr />

                <button id="btn-logout" class="btn btn-danger w-100 mb-2">{{ __('messages.logout') }}</button>
                <button id="btn-force-sync" class="btn btn-info w-100 mb-2"><i class="bi bi-arrow-clockwise"></i> Refresh</button>

                <!-- Change User -->
                <button id="btn-change-user" class="btn btn-secondary w-100 mb-2"><i class="bi bi-person-badge"></i> {{ __('messages.Change User') }}</button>

                <!-- Cash In / Cash Out -->
                <button id="btn-cash-in"  class="btn btn-success w-100 mb-2"><i class="bi bi-plus-circle"></i> Cash In</button>
                <button id="btn-cash-out" class="btn btn-danger  w-100 mb-2"><i class="bi bi-dash-circle"></i> Cash Out</button>
            </div>
        </div>

        <!-- Global overlay -->
        <div id="side-menu-overlay" class="position-fixed top-0 start-0 w-100 h-100"
             style="display:none; z-index:1040; background: rgba(0,0,0,0.4);"></div>
        <!-- ===== End GLOBAL Side Menu ===== -->

        <!-- Change User Modal -->
        <div class="modal fade" id="changeUserModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-person-badge"></i> {{ __('messages.Change User') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <p>{{ __('messages.Enter the PIN of the new user') }}</p>

                        <div id="change-user-pin-display" class="mb-3 fs-3 fw-bold">••••••</div>

                        <div class="row g-2 justify-content-center">
                            @for ($i = 1; $i <= 9; $i++)
                                <div class="col-4">
                                    <button class="btn btn-outline-dark btn-lg w-100 change-user-pin-btn">{{ $i }}</button>
                                </div>
                                @if ($i % 3 === 0)
                                    <div class="w-100"></div>
                                @endif
                            @endfor
                            <div class="col-4">
                                <button class="btn btn-outline-danger btn-lg w-100" id="change-user-clear">C</button>
                            </div>
                            <div class="col-4">
                                <button class="btn btn-outline-dark btn-lg w-100 change-user-pin-btn">0</button>
                            </div>
                            <div class="col-4">
                                <button class="btn btn-outline-success btn-lg w-100" id="change-user-confirm">OK</button>
                            </div>
                        </div>

                        <div id="change-user-error" class="alert alert-danger mt-3 d-none"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Core DB -->
    <script src="{{ asset('js/pos/core/Table.js') }}?v={{ $posVersion }}"></script>
    <script src="{{ asset('js/pos/core/Database.js') }}?v={{ $posVersion }}"></script>

    <!-- Tables -->
    <script src="{{ asset('js/pos/tables/UsersTable.js') }}?v={{ $posVersion }}"></script>
    <script src="{{ asset('js/pos/tables/CatalogTable.js') }}?v={{ $posVersion }}"></script>
    <script src="{{ asset('js/pos/tables/PaymentsTable.js') }}?v={{ $posVersion }}"></script>

    <!-- Remote Logger (must load before app.js to track all events) -->
    <script src="{{ asset('js/pos/remote-logger.js') }}?v={{ $posVersion }}"></script>

    <!-- App -->
    <script>
        // Base URL for API calls
        const APP_BASE_URL = '{{ config('app.url') }}';
    </script>
    <script src="{{ asset('js/pos/app.js') }}?v={{ $posVersion }}"></script>

    <!-- Virtual Keyboard -->
    <script src="{{ asset('js/pos/virtual-keyboard.js') }}?v={{ $posVersion }}"></script>
    <script>
        // Initialiser le clavier virtuel une fois le DOM pret
        $(function() {
            window.virtualKeyboard = new POSVirtualKeyboard({
                // Selecteur des inputs a activer
                inputSelector: 'input[type="text"], input[type="search"], input[type="number"], input[type="tel"], input[type="email"], textarea',
                // Exclure les inputs readonly et ceux avec data-no-keyboard
                excludeSelector: '[data-no-keyboard], [readonly], .pin-btn, .change-user-pin-btn, .shift-num-btn, .cash-num',
                // Pas de backdrop sombre
                showBackdrop: false,
                // Masquer le clavier apres Enter
                autoHideOnEnter: true
            });
            console.log('Virtual Keyboard initialized');
        });
    </script>

<script>
// --- GLOBALES --- //
window.selectedParentId = window.selectedParentId ?? null;
window.selectedChildId  = window.selectedChildId  ?? null;
window.selectedPath     = window.selectedPath     ?? [];
window.currentQuery     = window.currentQuery     ?? "";

window.sales        = window.sales        ?? [];
window.saleCounter  = window.saleCounter  ?? 1;
// currentUser et currentShift sont définis dans app.js avec var (donc accessibles via window.)

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
        Add_custom_service: @json(__('messages.pos.add_custom_service')),
        Amount: @json(__('messages.pos.amount')),
        Description: @json(__('messages.pos.description')),
        Enter_description: @json(__('messages.pos.enter_description')),
        Cancel: @json(__('messages.pos.cancel')),
        Add: @json(__('messages.pos.add')),
        Please_enter_valid_amount: @json(__('messages.pos.please_enter_valid_amount')),
        Please_enter_description: @json(__('messages.pos.please_enter_description')),
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
            // Update expected cash display
            if (typeof window.updateExpectedCashDisplay === "function") {
                window.updateExpectedCashDisplay();
            }
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

    <!-- ===== Planning & Leave Request Navigation ===== -->
    <script>
    (function() {
        // From menu (during active shift)
        $(document).on("click", "#btn-my-planning", function() {
            showScreen("myplanning");
            if (typeof initMyplanning === "function") initMyplanning();
        });

        $(document).on("click", "#btn-leave-request", function() {
            showScreen("leaverequest");
            if (typeof initLeaverequest === "function") initLeaverequest();
        });

        // From shift-start screen (before shift)
        $(document).on("click", "#btn-my-planning-preshift", function() {
            showScreen("myplanning");
            if (typeof initMyplanning === "function") initMyplanning();
        });

        $(document).on("click", "#btn-leave-request-preshift", function() {
            showScreen("leaverequest");
            if (typeof initLeaverequest === "function") initLeaverequest();
        });
    })();
    </script>

    <!-- ===== Cash In / Cash Out : logique modale + cumul localStorage PAR SHIFT ===== -->
    <script>
    (function() {
      // Keys are now per-shift
      function getCashInKey(shiftId) {
        return `pos_cash_in_shift_${shiftId}`;
      }
      function getCashOutKey(shiftId) {
        return `pos_cash_out_shift_${shiftId}`;
      }

      // Global functions to get Cash In/Out for current shift
      window.getShiftCashIn = function() {
        if (!window.currentShift || !window.currentShift.id) return 0;
        return parseFloat(localStorage.getItem(getCashInKey(window.currentShift.id)) || "0") || 0;
      };
      window.getShiftCashOut = function() {
        if (!window.currentShift || !window.currentShift.id) return 0;
        return parseFloat(localStorage.getItem(getCashOutKey(window.currentShift.id)) || "0") || 0;
      };
      // Clear Cash In/Out for a shift (called after shift ends)
      window.clearShiftCashInOut = function(shiftId) {
        if (!shiftId) return;
        localStorage.removeItem(getCashInKey(shiftId));
        localStorage.removeItem(getCashOutKey(shiftId));
      };

      // Calculate and display expected cash in drawer
      window.updateExpectedCashDisplay = function() {
        if (!window.currentShift || !window.currentShift.id) {
          $("#expected-cash-display").hide();
          return;
        }

        // Opening cash
        const openingCash = parseFloat(window.currentShift.opening_cash) || 0;

        // Cash In/Out
        const cashIn = window.getShiftCashIn();
        const cashOut = window.getShiftCashOut();

        // Cash sales from localStorage
        let cashSales = 0;
        const salesKey = `pos_sales_shift_${window.currentShift.id}`;
        const validatedKey = `pos_sales_validated_shift_${window.currentShift.id}`;

        // Helper function to extract cash amount from a sale
        const getCashFromSale = (sale) => {
          let cash = 0;
          const paymentType = (sale.payment_type || '').toLowerCase();

          // Check if it's a split payment
          if (sale.split_payments && Array.isArray(sale.split_payments) && sale.split_payments.length > 0) {
            // Iterate through split payments and sum cash portions
            sale.split_payments.forEach(payment => {
              const method = (payment.payment_type || payment.method || '').toLowerCase();
              if (method === 'cash' || method === 'espèces') {
                cash += parseFloat(payment.amount) || 0;
              }
            });
          } else if (paymentType === 'cash' || paymentType === 'espèces') {
            // Full amount is cash
            cash = parseFloat(sale.total) || 0;
          }

          return cash;
        };

        try {
          // Current sales (not yet synced)
          const currentSales = JSON.parse(localStorage.getItem(salesKey) || "[]");
          currentSales.forEach(sale => {
            cashSales += getCashFromSale(sale);
          });

          // Validated sales (already synced)
          const validatedSales = JSON.parse(localStorage.getItem(validatedKey) || "[]");
          validatedSales.forEach(sale => {
            cashSales += getCashFromSale(sale);
          });
        } catch (e) {
          console.error("Error calculating cash sales:", e);
        }

        // Calculate expected cash
        const expectedCash = openingCash + cashSales + cashIn - cashOut;

        // Update display
        $("#expected-cash-amount").text("$" + expectedCash.toFixed(2));
        $("#expected-cash-display").show();
      };

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

      function openCashDialog(title, type) {
        if (!window.currentShift || !window.currentShift.id) {
          alert("No active shift!");
          return;
        }

        const storageKey = type === 'in' ? getCashInKey(window.currentShift.id) : getCashOutKey(window.currentShift.id);

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
            alert(`${title}: $${amount.toFixed(2)} — Shift Total: $${next.toFixed(2)}`);
            // Update expected cash display
            if (typeof window.updateExpectedCashDisplay === "function") {
              window.updateExpectedCashDisplay();
            }
          } catch (e) {
            console.error("localStorage error:", e);
            alert("Error saving!");
          }
        });
      }

      // Boutons du menu
      $(document).on("click", "#btn-cash-in", function() {
        openCashDialog("Cash In", "in");
      });
      $(document).on("click", "#btn-cash-out", function() {
        openCashDialog("Cash Out", "out");
      });

      // ===== CHANGE USER =====
      let changeUserPinBuffer = "";

      function updateChangeUserPinDisplay() {
        let masked = "*".repeat(changeUserPinBuffer.length);
        masked = masked.padEnd(6, "•");
        $("#change-user-pin-display").text(masked);
      }

      function resetChangeUserModal() {
        changeUserPinBuffer = "";
        updateChangeUserPinDisplay();
        $("#change-user-error").addClass("d-none").text("");
      }

      $(document).on("click", "#btn-change-user", function() {
        if (!window.currentShift || !window.currentShift.id) {
          alert("No active shift!");
          return;
        }
        resetChangeUserModal();
        const modalEl = document.getElementById("changeUserModal");
        const modal = new bootstrap.Modal(modalEl, { backdrop: "static", keyboard: false });
        modal.show();
      });

      $(document).on("click", ".change-user-pin-btn", function() {
        if (changeUserPinBuffer.length < 6) {
          changeUserPinBuffer += $(this).text();
          updateChangeUserPinDisplay();
        }
      });

      $(document).on("click", "#change-user-clear", function() {
        changeUserPinBuffer = "";
        updateChangeUserPinDisplay();
        $("#change-user-error").addClass("d-none");
      });

      $(document).on("click", "#change-user-confirm", async function() {
        if (!changeUserPinBuffer) {
          $("#change-user-error").removeClass("d-none").text("Please enter a PIN!");
          return;
        }

        // Find user with this PIN in IndexedDB
        const users = db.table("users");
        const match = users.findExact({ pin_code: changeUserPinBuffer });

        if (match.length === 0) {
          $("#change-user-error").removeClass("d-none").text("Incorrect PIN!");
          changeUserPinBuffer = "";
          updateChangeUserPinDisplay();
          return;
        }

        const newUser = match[0];

        // Check if it's the same user
        if (newUser.id === window.currentUser.id) {
          $("#change-user-error").removeClass("d-none").text("You are already logged in as this user!");
          changeUserPinBuffer = "";
          updateChangeUserPinDisplay();
          return;
        }

        // Check if user belongs to the same store
        if (newUser.store_id !== window.currentUser.store_id) {
          $("#change-user-error").removeClass("d-none").text("This user belongs to a different store!");
          changeUserPinBuffer = "";
          updateChangeUserPinDisplay();
          return;
        }

        try {
          // Call API to change user
          const res = await fetch(`{{ config('app.url') }}/api/pos/shifts/change-user`, {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
              shift_id: window.currentShift.id,
              old_user_id: window.currentUser.id,
              new_user_id: newUser.id
            })
          });

          if (!res.ok) {
            const err = await res.json();
            throw new Error(err.error || "Failed to change user");
          }

          const data = await res.json();

          // Update current user
          window.currentUser = newUser;

          // Update shift with new user_id
          if (data.shift) {
            window.currentShift = data.shift;
          }

          // Close modal
          const modalEl = document.getElementById("changeUserModal");
          const modal = bootstrap.Modal.getInstance(modalEl);
          if (modal) modal.hide();

          alert(`User changed to: ${newUser.name}`);
        } catch (err) {
          console.error("Change user error:", err);
          $("#change-user-error").removeClass("d-none").text(err.message);
          changeUserPinBuffer = "";
          updateChangeUserPinDisplay();
        }
      });
    })();
    </script>
</body>
</html>
