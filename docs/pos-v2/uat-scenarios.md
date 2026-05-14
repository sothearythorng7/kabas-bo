# POS V2 — UAT Scenarios for Kabas Staff

> **Audience**: cashiers and managers running real sales on
> `testing-bo.kabasconceptstore.com/pos-v2` (basic auth: `kabas-dev` /
> ask Mickaël).
> **Goal**: cover the 4 most critical end-to-end paths in conditions as
> close as possible to a real shift. If anything diverges from V1
> behaviour, write it in the comment column and post a brief to
> `/var/shared/claude-comms/from-mickael/`.

Environment: **`kabas_dev` database**, no real money, no real Telegram.
PayWay sandbox is not testable from dev (domain not whitelisted at ABA).

---

## Scenario A — Standard sale, online, mixed cart

Goal: every cart variant + split payment + auto-print + sync.

### Setup
- Test account: `claude-seo@kabas.local` or any seeded POS user (creds in `from-mickael/creds-testing.md`)
- Network: online
- Printer: optional — if no daemon at `localhost:8888`, expect web-print preview

### Steps

| # | Action | Expected | OK ? | Notes |
|---|---|---|---|---|
| 1 | Visit `/pos-v2`, type PIN | Routes to shift-start | ☐ | |
| 2 | Type initial cash `100`, pick a popup event chip if any, Start | TopBar shows shift timer counting from 0 | ☐ | |
| 3 | Tap any product card | Cart shows the item, total updates | ☐ | |
| 4 | Tap same product 2 more times | Quantity is `3`, not 3 separate lines | ☐ | |
| 5 | Tap a Gift Card card | Dark card in cart with unique code `GIFT-XXXX-XXXX-XXXX` | ☐ | |
| 6 | Tap Gift Card again | New line with **different** code | ☐ | |
| 7 | "Service" button → desc "Embroidery" + amount 8 → Add | Amber service line added | ☐ | |
| 8 | "Delivery" button → "House #42 St 240" + fee 5 → Add | Sky delivery line added | ☐ | |
| 9 | "Add discount" on first product line → Percent 10 → Apply | Strikethrough + new total on that line | ☐ | |
| 10 | "Discount" footer button → Amount 5 → Apply | Global discount visible in totals | ☐ | |
| 11 | Tap "Pay $XXX" | PaymentDialog opens | ☐ | |
| 12 | Switch to Split tab, add CASH 30 + CARD remaining → Confirm | Flash banner "Sale recorded", cart resets | ☐ | |
| 13 | TopBar sync badge | Counts down to 0 within 60s | ☐ | |
| 14 | Footer "Expected cash" | Increased by the CASH portion only | ☐ | |
| 15 | Cash drawer | Opens automatically (if daemon online) | ☐ | |
| 16 | Receipt | Printed if daemon online; otherwise web-print preview shown | ☐ | |

### Acceptance
- All 16 steps OK
- Sale is visible in BO at `/website-orders` ? **No, this is a POS sale not a website order.** It should appear under whatever the BO POS sale list is (TBD with manager).
- No console errors

---

## Scenario B — Offline sale, sync on reconnect

Goal: prove offline-first persistence and idempotent sync.

### Setup
- Open shift normally (online)
- Add 2-3 items to cart

### Steps

| # | Action | Expected | OK ? |
|---|---|---|---|
| 1 | Browser DevTools → Network → set "Offline" | TopBar dot turns grey, "Offline" label | ☐ |
| 2 | Add 1 product, finalize with CASH | Flash banner shows, sync badge increments | ☐ |
| 3 | Add another, finalize with CARD | Pending badge `2` in TopBar | ☐ |
| 4 | Refresh the page | After boot, cart is empty (the drafts were paid), 2 pending sales still visible | ☐ |
| 5 | Network → Online | Within seconds: pending badge → 0 | ☐ |
| 6 | Open Journal, search today's date | The 2 sales appear with real BO sale IDs | ☐ |

### Edge cases

| Case | Expected |
|---|---|
| Finalize same sale twice during a transient offline glitch | Backend de-dupes via `pos_local_id` — only ONE sale in journal |
| Network restored mid-sync | `syncing` rows marked `synced` or rolled back to `pending` cleanly (no orphan `syncing`) |
| Close browser before sync | Re-open → auto sync kicks within 60s |

---

## Scenario C — Exchange with voucher generation

Goal: full exchange flow when the return is worth more than the new items.

### Setup
- A past sale at least 1 day old, on the test store, with at least 2 items that haven't been exchanged before
- Have the sale's ID handy (visible in the Journal)

### Steps

| # | Action | Expected | OK ? |
|---|---|---|---|
| 1 | Dashboard → "Exchange" button | Wizard opens at Lookup step | ☐ |
| 2 | Type sale ID, Validate | Routes to "Return items" step with items listed | ☐ |
| 3 | Check 2 items to return | Footer "Return total" updates | ☐ |
| 4 | Next → New items step | New items list empty | ☐ |
| 5 | Type a partial product name | Live search results below the input | ☐ |
| 6 | Tap one match | New items list grows, footer "Balance" turns emerald with "auto voucher" hint | ☐ |
| 7 | Tap "Process exchange" | Wizard goes to "done" step with voucher details | ☐ |
| 8 | Voucher code displayed | Format `EX-XXXX-XXXX` (or similar) with non-zero amount and expiry date | ☐ |
| 9 | Voucher receipt printed | Daemon prints it, or web-preview shown | ☐ |
| 10 | Close wizard, refresh Journal, open the original sale | Items now flagged "exchanged" (or hidden from exchangeable list on next lookup) | ☐ |

### Edge cases

| Case | Expected |
|---|---|
| Lookup with unknown ID | Red "Sale not found" |
| Lookup with sale 30+ days old | Red "Sale older than 30 days" |
| Try to return an already-exchanged item | Item shown disabled with "Not exchangeable" tag |
| Customer owes more (new > return) | Wizard adds a "Pay" step before "Done", with split payment + voucher support |

---

## Scenario D — End shift with cash discrepancy

Goal: 3-step closing, cash gap detection, accurate calculation.

### Setup
- Open a shift with initial cash `50`
- Do 2-3 mixed-payment sales (CASH and CARD)
- Add a "cash in" of $10 (e.g. petty cash deposit) and a "cash out" of $5 (e.g. floral expense)

### Steps

| # | Action | Expected | OK ? |
|---|---|---|---|
| 1 | LeftRail → End shift icon (red) | ShiftEndView step 1 (visitors) | ☐ |
| 2 | Type `47` visitors → Next | Step 2 (counted cash) | ☐ |
| 3 | Compute expected manually: opening + cash_sales + cash_in − cash_out | Should match the value about to be shown at step 3 | ☐ |
| 4 | Type the counted amount → Next | Step 3 verification | ☐ |
| 5 | Compare displayed Expected, Counted, Difference | Math matches manual computation; difference colour is emerald (=0), amber (over) or rose (short) | ☐ |
| 6 | Type a deliberately wrong counted amount (off by $3) | Difference shows ±$3, warning banner appears | ☐ |
| 7 | Confirm | API call to `/shifts/end`, cashier logged out, returned to login screen | ☐ |
| 8 | Re-login | Routes to shift-start (no open shift) | ☐ |

### Verify in BO
- The shift row in the BO has `closing_cash`, `cash_difference`, `cash_in`, `cash_out`, `visitors_count`, `ended_at` populated
- Telegram should be silent (dev env)

---

## What to do if a step fails

1. Note the step number and the symptom in the table.
2. Take a screenshot of the screen.
3. Open the browser console, copy the last 20 lines of errors.
4. Write a brief in `/var/shared/claude-comms/from-mickael/YYYY-MM-DD-pos-v2-uat-issue-<short>.md` with:
   - Scenario letter + step
   - What you did / expected / saw
   - Console errors
   - Whether the issue is blocking the scenario

We will triage these before scheduling Phase 10 cutover.

---

## Hard rules before cutover

- [ ] All 4 scenarios pass at least once
- [ ] Scenario B passes both with `Network = Offline` and `Network = Slow 3G`
- [ ] No console errors during any scenario
- [ ] Receipts print correctly when daemon is reachable; web preview is acceptable in dev
- [ ] Telegram messages are SILENT in dev (would be a config bug otherwise)
- [ ] V1 (`/pos`) still works as expected on `testing-bo` and `bo` (basic non-regression check)
