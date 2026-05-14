# POS V2 — Parity Checklist V1 ↔ V2

> **Goal**: prove that every behaviour of the V1 POS (`/pos`) is reproduced
> by the V2 POS (`/pos-v2`), so the cutover (Phase 10) is a no-op for staff.

Reference cartography: V1 has 15 functional features. Each is covered below
with nominal + error cases. Tick a box once the corresponding behaviour has
been confirmed on `testing-bo.kabasconceptstore.com/pos-v2`.

Legend: ✅ implemented · 🔬 needs UAT · ⚠️ delta from V1 (intentional)

---

## 1. Authentication (PIN login)

| Case | V1 source | V2 source | Tester action | Expected |
|---|---|---|---|---|
| 1.1 Valid PIN | `app.js:checkUser` | `LoginView.vue` + `session.signInWithPin` | Type a known PIN, last digit | Auto-submit, route to dashboard or shift-start |
| 1.2 Invalid PIN | idem | idem | Type an unknown 6-digit PIN | Red shake animation, PIN cleared, "Invalid PIN" message |
| 1.3 Users not yet cached | initial fetch in V1 boot | `session.loadUsers` | First login after install | Users list pulled from `/api/pos/users`, then cached in Dexie `users` table |
| 1.4 Offline login | local table | Dexie compound `pin_code` lookup | Disconnect network, type PIN | Works (cache-only validation, no API call) |

Acceptance: ✅ ✅ ✅ ✅ — 🔬 1.4 needs offline UAT

---

## 2. Shift opening

| Case | V1 source | V2 source | Tester action | Expected |
|---|---|---|---|---|
| 2.1 Open shift with cash | `shift-start.blade.php` | `ShiftStartView.vue` + `shifts.startShift` | Type initial amount, press "Start shift" | `POST /shifts/start`, redirected to dashboard, shift visible top-bar |
| 2.2 Open shift with popup event | event picker | event chips in ShiftStartView | Tap an event chip before submit | `popup_event_id` sent in payload |
| 2.3 Today's absences shown | `getTodayAbsences` | `fetchTodayAbsences` | Open shift-start | Right panel lists absences with avatars; empty state otherwise |
| 2.4 Telegram notification | backend side-effect | unchanged | Open a shift | Mickaël/managers receive Telegram message (prod only) |

Acceptance: 🔬 — UAT in dev (Telegram disabled per CLAUDE.md, only verify payload)

---

## 3. Shift closing (3-step wizard)

| Case | V1 source | V2 source | Tester action | Expected |
|---|---|---|---|---|
| 3.1 Step 1 — visitor count | step 1 | `ShiftEndView.vue` step 1 | Type a number, Next | Step 2 |
| 3.2 Step 2 — counted cash | step 2 | step 2 | Type counted cash, Next | Step 3 |
| 3.3 Step 3 — verification math | expected = opening + cash_sales + in − out | identical formula in `ShiftEndView` computed | Compare displayed values | Match V1 exactly |
| 3.4 Cash gap alert | diff > 0.01 → warning | amber banner if `hasGap` true | Force counted ≠ expected | Yellow alert shown, still allows confirm |
| 3.5 Submit | `POST /shifts/end` | `shifts.endShift` | Press Confirm | Cashier logged out, redirected to login |

---

## 4. Mid-shift cashier switch

| Case | V1 source | V2 source | Tester action | Expected |
|---|---|---|---|---|
| 4.1 Switch with valid PIN | `ShiftController@changeUser` | `ChangeUserDialog` + `shifts.changeShiftUser` | Top-bar avatar → modal → type new cashier PIN | TopBar name updates, shift continues |
| 4.2 Invalid PIN | local validation | local Dexie lookup | Type unknown PIN | Red shake, no API call |
| 4.3 Same user re-entered | no-op | no-op | Type current cashier's PIN | Dialog closes silently |

---

## 5. Cash In / Cash Out

| Case | V1 source | V2 source | Tester action | Expected |
|---|---|---|---|---|
| 5.1 Add cash in | `pos_cash_in_shift_*` localStorage | `cash.addCashIn` → Dexie `settings` | Open cash-in modal, type amount, OK | Cumul incremented, persisted, footer "Expected cash" updated |
| 5.2 Add cash out | idem | `cash.addCashOut` | Cash-out modal | Cumul decremented from drawer |
| 5.3 Reload preservation | localStorage survives | Dexie row survives reload | F5 mid-shift | Cumuls preserved |
| 5.4 Sent at shift end | payload contains `cash_in/cash_out` | identical | End shift | Backend receives both totals |

---

## 6. Expected cash widget

| Case | V1 source | V2 source | Tester action | Expected |
|---|---|---|---|---|
| 6.1 Initial value | opening cash | `ExpectedCashWidget` reads `currentShift.opening_cash` | Just after open shift | = opening |
| 6.2 Updates on cash sale | recomputed | `sales.cashSalesForShift` → `cash.setCashSales` after each paid sale | Finalize a CASH-paid sale | Widget increments by CASH portion |
| 6.3 Voucher excluded | excluded in V1 | `cashSalesForShift` filters out VOUCHER | Pay with VOUCHER only | Widget unchanged |
| 6.4 Split CASH+CARD | only CASH part counts | identical | Pay half cash, half card | Widget incremented by cash portion only |

---

## 7. Catalog loading (offline-first)

| Case | V1 source | V2 source | Tester action | Expected |
|---|---|---|---|---|
| 7.1 First sync | `/api/pos/catalog/{storeId}` | `catalog.syncFromApi` | First boot, online | All products + giftBoxes + giftCards + tree + payments cached in Dexie |
| 7.2 Reload from cache | localStorage cache | `catalog.loadFromCache` | F5 offline | Catalog loads instantly without network |
| 7.3 Background refresh | manual force-sync only | auto on every boot if online | Boot when online | Background refresh kicks off without blocking UI |
| 7.4 Empty catalog handling | n/a | empty state component | Brand new install offline | "No products to show" empty state |

---

## 8. Search & barcode scan

| Case | V1 source | V2 source | Tester action | Expected |
|---|---|---|---|---|
| 8.1 Text search | LIKE on name FR/EN | `catalog.searchLocal` LIKE | Type partial name | Live filter, FR + EN coverage |
| 8.2 EAN exact match | regex 4+ alphanum | `catalog.findByBarcode` | Type/scan 13-digit EAN | Auto-add to cart if exact match |
| 8.3 Scanner HID burst | global keydown listener | `useBarcodeScanner` | Scan with real HID device | Item added directly, search input not focused |
| 8.4 Scanner while typing | suppressed | composable skips when target is input/textarea | Type while focused | Manual typing not hijacked |

---

## 9. Cart — 5 item types

| Case | V1 source | V2 source | Tester action | Expected |
|---|---|---|---|---|
| 9.1 Product line | `addProductToCart` | `cart.addCatalogItem` (product) | Tap a product | Line added or qty incremented if same product |
| 9.2 Gift box | type=gift_box, increments | identical | Tap a gift box | Line with amber gift-box badge |
| 9.3 Gift card | always qty=1, unique code | `cart.addCatalogItem` (gift_card) — alphabet `[A-HJ-NP-Z2-9]`, format `GIFT-XXXX-XXXX-XXXX` | Tap a gift card 3× | 3 separate lines, each with distinct code |
| 9.4 Custom service | modal description+amount | `CustomServiceDialog` | "Service" button | Amber service line with description |
| 9.5 Delivery | modal address+fee | `DeliveryDialog` | "Delivery" button | Sky-blue delivery line with address |

⚠️ Gift card alphabet must be exactly `[A-HJ-NP-Z2-9]` — see `composables/useGiftCardCode.js`. Confirmed identical to V1 carto.

---

## 10. Discounts (line + global)

| Case | V1 source | V2 source | Tester action | Expected |
|---|---|---|---|---|
| 10.1 Line discount percent | line discounts `[{type:percent}]` | `DiscountDialog scope=line` | Tap "Add discount" on a line → Percent → 10 → Apply | Strikethrough on subtotal, new line total |
| 10.2 Line discount amount | `[{type:amount}]` | idem | Amount → 5 → Apply | $5 off the line |
| 10.3 Global discount percent | `sale.discounts` array | `DiscountDialog scope=global` | "Discount" button | Summary panel shows "Global discount −5%" |
| 10.4 Remove discount | replace with empty array | `setLineDiscount(null)` / `setGlobalDiscount(null)` | Open existing discount → Remove | Line/global returns to full price |

---

## 11. Payment (single + split + voucher)

| Case | V1 source | V2 source | Tester action | Expected |
|---|---|---|---|---|
| 11.1 Single CASH | one split_payment | `PaymentDialog Single` → CASH tile | One-tap method picker | Payment created with `split_payments=[{CASH, total}]` |
| 11.2 Split CASH+CARD | multi split | `PaymentDialog Split` mode | Add line CASH $20 + CARD $30 | Confirm enabled when sum = total |
| 11.3 Voucher applied | `applyVoucher` flow | `VoucherInputDialog` → `handleVoucherValidated` | Voucher button → enter code → Validate → Apply | VOUCHER line added with code |
| 11.4 Sum mismatch | block confirm | `canConfirm` = false | Leave $5 missing | Confirm disabled, remaining shown amber |
| 11.5 Voucher invalid | error toast | red banner in dialog | Type fake code → Validate | "Invalid or expired voucher" |

---

## 12. Sync (idempotent)

| Case | V1 source | V2 source | Tester action | Expected |
|---|---|---|---|---|
| 12.1 Auto sync online | manual + background | `useSync` 60s loop | Finalize sale online | `POST /sales/sync` fires immediately + within 60s |
| 12.2 Offline → reconnect | manual force-sync | `useSync` on `online` event + every 60s | Finalize offline, reconnect WiFi | Auto-sync within seconds, pending badge clears |
| 12.3 Idempotent retry | `pos_local_id` server-side | identical | Sync twice manually | No duplicate created |
| 12.4 Sales mapping | `sales_mapping` response | stored under `row.remote` | Sync a sale, open journal | Real sale_id available for future exchange |
| 12.5 Sync error retry | status=error | `sales_queue.status='error'` | Force a 500 in dev | Row marked error, retries next cycle |

⚠️ Critical. This is the money path — Phase 9 UAT must exercise both online and offline branches before cutover.

---

## 13. Journal

| Case | V1 source | V2 source | Tester action | Expected |
|---|---|---|---|---|
| 13.1 Search by date | day/month/year picker | HTML5 `type="date"` | Pick a date, Search | `POST /shifts/sales-by-date` fires, summary cards populate |
| 13.2 Summary cards | 4 cards | identical 4 cards | After search | Sales count / items sold / total paid / total discount |
| 13.3 No results | empty state | empty state | Pick a future date | "No sales found" |
| 13.4 Drill-down to detail | click row | router `name: 'sale-detail'` | Click row | Routes to `/sale/:id`, shows items, totals, payment, exchanges |
| 13.5 Back navigation | back button | LeftRail + header `← Back` | Back button | Returns to journal with previous search preserved |

---

## 14. Exchange (the hardest one)

| Case | V1 source | V2 source | Tester action | Expected |
|---|---|---|---|---|
| 14.1 Lookup by ID | `/exchange/lookup-sale` | `exchange.runLookup` | Type sale ID | Lookup step → return step |
| 14.2 Not found | 404 | `error='not_found'` | Type unknown ID | Red banner "Sale not found" |
| 14.3 Too old | 400 | `error='too_old'` | Type old sale ID | "Sale older than 30 days" |
| 14.4 Direct from journal | not in V1 | ⚠️ V2 addition: SaleDetail "Exchange" button | Open sale detail → Exchange | Wizard skips lookup |
| 14.5 Select returns | toggles | checkboxes per item | Tap items | Return total updates live |
| 14.6 Add new items | reuses search | embedded search list | Type product → tap | New items list grows, totals update |
| 14.7 Credit due | auto voucher | footer shows "credit → auto voucher" | More return than new | After submit, voucher_generated printed |
| 14.8 Customer owes | split payment | wizard goes to "pay" step | Less return than new | Split editor + voucher button shown |
| 14.9 Even swap | no payment step | jumps directly to confirm | Equal totals | "Even swap" shown, single click confirm |
| 14.10 Submit | `POST /exchange/process` | identical | Confirm | Returns + new items processed atomically, voucher minted |

⚠️ Item 14.7 — automated voucher print uses the V1 voucher ticket format exactly (3-char spacing in code, DD/MM/YYYY expiry).

---

## 15. Planning + leave request

| Case | V1 source | V2 source | Tester action | Expected |
|---|---|---|---|---|
| 15.1 Calendar shows shifts | grid render | `MyPlanningView` Monday-first 6-week grid | Open planning | Shifts shown sky-blue, leaves shown amber |
| 15.2 Today highlight | dot/badge | round black badge | Open planning | Today's date in black circle |
| 15.3 Month navigation | prev/next buttons | identical | Click chevrons | Calendar shifts month |
| 15.4 Leave balance | API | side panel reads `balance.available_days` etc. | Open planning | Available/used/pending shown |
| 15.5 Request leave | form | `LeaveRequestView` | Pick type + dates + reason → Submit | `POST /request-leave` fires, redirect to planning |
| 15.6 Date conflict | client validation | `planning.findConflict` (overlap detection) | Try to overlap existing leave | "These dates overlap" red banner |
| 15.7 Invalid range | client validation | start ≤ end via HTML5 `min` attr | End before start | Browser-side rejection |

---

## Cross-cutting

### Locale toggle (FR/EN)
- 11.1: Switch locale → All labels update — ✅ `useI18nStore` + Pinia reactivity
- All numeric/currency formats preserved (no locale-specific number formatting changes)

### Online/offline indicator
- TopBar dot turns green/amber/grey on connectivity changes — ✅ `session.detectOnline` + `navigator.onLine` events

### Hold sale
- ✅ Multiple drafts can coexist; "N held" dropdown shows count and lets cashier resume or discard

### Printer status
- ✅ TopBar pill shows "Printer" (green) when local daemon `/status=ok`, "No printer" (grey) otherwise. Polled every 60s.

### Auto-print after payment
- ✅ DashboardView.onPaid prints receipt; opens drawer if any CASH leg
- ✅ ExchangeWizard.submit prints voucher if `voucher_generated` in response

### Reprint
- ✅ SaleDetailView "Reprint" button uses `printer.printSale` → daemon → fallback web-print if daemon offline

---

## Known deltas from V1 (intentional)

| Delta | Rationale |
|---|---|
| Vue 3 router instead of `showScreen()` — URLs reflect screens (`/login`, `/dashboard`, `/journal`, etc.) | Better deep-linking + browser back, no behavioural change for staff |
| Tailwind v4 instead of Bootstrap 5 | Modernisation goal, identical layout in mockup-approved style |
| Dexie IndexedDB instead of in-memory `Table` + localStorage | True offline persistence; localStorage migration shim at first boot |
| Locale persisted in `localStorage` key `pos_v2_locale` | V1 reset locale on reload; V2 remembers |
| Exchange wizard accessible from SaleDetail directly | V1 only via dashboard search; V2 adds shortcut |
| Auto-sync 60s background loop (V1 was mostly manual) | Reduces "pending" badge time; same backend contract |

---

## Sign-off

| Role | Name | Date | Signature |
|---|---|---|---|
| Developer | | | |
| Staff UAT lead | | | |
| Manager (Kabas) | | | |

When all 75+ cases are ticked and signed off, Phase 10 cutover can proceed.
