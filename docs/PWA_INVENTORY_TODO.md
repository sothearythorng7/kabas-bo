# PWA Inventory Module - Specifications & Todo List

## Overview
New inventory module for the Reception PWA allowing staff to perform physical inventory counts for stores and consignment resellers.

---

## Requirements Summary

### Inventory Types
1. **Internal Inventory** - For Kabas stores (select store after choosing "internal")
2. **Reseller Inventory** - For consignment partners (select from reseller list with search)

### Key Features
- **Shared inventories** - Multiple staff can work on the same inventory simultaneously
- **Progress tracking** - Show total products, counted, remaining
- **Barcode scanning** - Scan products to count them
- **Manual search** - Find products via Meilisearch if barcode fails
- **Quantity input** - Default shows theoretical stock, user enters actual count
- **Duplicate warning** - Alert if product was already counted (but allow re-counting)
- **Save & resume** - Can stop and continue later
- **Two-step validation**:
  - Staff marks inventory as "completed" in PWA
  - BO staff validates and applies stock adjustments

### Validation Logic
- **Internal inventory** → Create stock_batches adjustments (positive or negative)
- **Reseller inventory** → Create alerts for each product with difference (further processing TBD)

---

## Database Schema

### Tables to Create

```sql
-- Main inventory table
CREATE TABLE pwa_inventories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type ENUM('internal', 'reseller') NOT NULL,
    store_id BIGINT UNSIGNED NULL,           -- For internal type
    reseller_id BIGINT UNSIGNED NULL,        -- For reseller type
    status ENUM('in_progress', 'completed', 'validated', 'cancelled') DEFAULT 'in_progress',
    started_by BIGINT UNSIGNED NOT NULL,     -- reception_users.id
    started_at TIMESTAMP NOT NULL,
    completed_at TIMESTAMP NULL,
    validated_by BIGINT UNSIGNED NULL,       -- users.id (BO user)
    validated_at TIMESTAMP NULL,
    note TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (store_id) REFERENCES stores(id),
    FOREIGN KEY (reseller_id) REFERENCES resellers(id),
    FOREIGN KEY (started_by) REFERENCES reception_users(id),
    FOREIGN KEY (validated_by) REFERENCES users(id)
);

-- Individual product counts
CREATE TABLE pwa_inventory_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pwa_inventory_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    theoretical_quantity INT NOT NULL DEFAULT 0,
    counted_quantity INT NULL,               -- NULL = not yet counted
    counted_by BIGINT UNSIGNED NULL,         -- reception_users.id
    counted_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (pwa_inventory_id) REFERENCES pwa_inventories(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (counted_by) REFERENCES reception_users(id),

    UNIQUE KEY unique_inventory_product (pwa_inventory_id, product_id)
);

-- Audit log for modifications
CREATE TABLE pwa_inventory_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pwa_inventory_item_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,        -- reception_users.id
    user_type VARCHAR(20) NOT NULL,          -- 'reception' or 'bo'
    old_quantity INT NULL,
    new_quantity INT NOT NULL,
    created_at TIMESTAMP,

    FOREIGN KEY (pwa_inventory_item_id) REFERENCES pwa_inventory_items(id) ON DELETE CASCADE
);

-- Alerts for reseller inventory differences
CREATE TABLE pwa_inventory_alerts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pwa_inventory_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    theoretical_quantity INT NOT NULL,
    counted_quantity INT NOT NULL,
    difference INT NOT NULL,
    status ENUM('pending', 'resolved') DEFAULT 'pending',
    resolved_by BIGINT UNSIGNED NULL,
    resolved_at TIMESTAMP NULL,
    resolution_note TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (pwa_inventory_id) REFERENCES pwa_inventories(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);
```

---

## Analysis Notes

### Potential Issues Identified

1. **Shop-as-reseller IDs** - The `Reseller::allWithShops()` method returns shop-based resellers with IDs like `'shop-123'`. Need to handle this mixed ID format.

2. **No existing inventory tables** - Current system uses Excel import/export only. This will be a completely new feature.

3. **Concurrent editing conflicts** - If two users scan the same product simultaneously, last write wins. Consider adding optimistic locking or temporary locks.

### Suggested Improvements

1. **Temporary product lock** - When user scans a product, lock it for 2 minutes to prevent duplicate counting.

2. **Real-time updates** - Use Laravel Echo/Pusher to show other users' counts in real-time.

3. **Offline mode** - Queue counts locally when offline, sync when connection returns.

4. **Excel export** - Allow exporting inventory results from BO.

5. **Partial validation** - Allow validating inventory even if some products aren't counted.

---

## Todo List

### Phase 1: Database & Models

- [ ] Create migration: `pwa_inventories` table
- [ ] Create migration: `pwa_inventory_items` table
- [ ] Create migration: `pwa_inventory_logs` table
- [ ] Create migration: `pwa_inventory_alerts` table
- [ ] Create model: `PwaInventory`
- [ ] Create model: `PwaInventoryItem`
- [ ] Create model: `PwaInventoryLog`
- [ ] Create model: `PwaInventoryAlert`

### Phase 2: PWA Backend (Controller & Routes)

- [ ] Create controller: `Reception/InventoryController.php`
  - `index()` - List user's inventories
  - `create()` - Start new inventory form
  - `store()` - Create new inventory
  - `show($id)` - Inventory detail/counting interface
  - `scanProduct()` - API: lookup product by barcode
  - `searchProduct()` - API: search via Meilisearch
  - `countProduct()` - API: save product count
  - `getProgress()` - API: get inventory progress stats
  - `getRemainingProducts()` - API: get uncounted products list
  - `complete()` - Mark inventory as completed
- [ ] Add routes in `web.php` under reception group

### Phase 3: PWA Frontend (Views)

- [ ] Add "Inventory" button to `home.blade.php`
- [ ] Create `inventory/index.blade.php` - List of inventories
- [ ] Create `inventory/create.blade.php` - Start new inventory
  - Type selection (internal/reseller)
  - Store selector (for internal)
  - Reseller selector with search (for reseller)
  - Option to join existing in-progress inventory
- [ ] Create `inventory/show.blade.php` - Main counting interface
  - Progress bar (X/Y products counted)
  - Barcode scanner (reuse Quagga2 from check-price)
  - Manual search input
  - "View remaining" button
  - "Complete inventory" button
- [ ] Create `inventory/count-modal.blade.php` - Quantity input modal
  - Product info display
  - Theoretical quantity shown
  - Input for actual count
  - Warning if already counted
  - Save button
- [ ] Create `inventory/remaining.blade.php` - List uncounted products
  - Search/filter functionality
  - Click to count manually

### Phase 4: BO Backend

- [ ] Create controller: `PwaInventoryController.php`
  - `index()` - List all PWA inventories
  - `show($id)` - View inventory details
  - `updateItem()` - Modify counted quantity
  - `validate()` - Validate and apply inventory
  - `cancel()` - Cancel inventory
- [ ] Add routes in `web.php`

### Phase 5: BO Frontend (Views)

- [ ] Create `pwa_inventories/index.blade.php`
  - List with filters (status, type, date)
  - Progress indicator for each
  - Quick actions
- [ ] Create `pwa_inventories/show.blade.php`
  - Inventory header info
  - Progress stats
  - Product list with:
    - Theoretical vs counted quantities
    - Who counted each product
    - Difference highlighting
    - Edit capability
  - Validation button
  - Cancel button
- [ ] Add menu item in BO sidebar

### Phase 6: Validation Logic

- [ ] Implement internal inventory validation
  - Calculate differences
  - Create positive stock_batches for gains
  - Reduce existing stock_batches for losses (FIFO)
  - Log all changes
- [ ] Implement reseller inventory validation
  - Create `pwa_inventory_alerts` for each difference
  - Mark inventory as validated
  - (Further processing to be defined later)

### Phase 7: Testing & Polish

- [ ] Test PWA flow: create → count → complete
- [ ] Test BO flow: view → edit → validate
- [ ] Test concurrent counting by multiple users
- [ ] Test barcode scanner integration
- [ ] Test Meilisearch fallback
- [ ] Test offline behavior (if implemented)
- [ ] Add translations (FR/EN)

---

## Technical References

### Existing Code to Reuse

1. **Barcode Scanner** - Copy from `check-price.blade.php` (Quagga2 integration)
2. **Meilisearch** - See `Product` model `toSearchableArray()` and `StockController` usage
3. **Stock Batch Logic** - See `InventoryController::apply()` for FIFO deduction pattern
4. **Reseller List** - See `Reseller::allWithShops()` for combined reseller + shop list

### Key Files

- `/var/www/kabas/app/Http/Controllers/Reception/ReceptionController.php` - PWA controller pattern
- `/var/www/kabas/app/Http/Controllers/InventoryController.php` - Existing inventory logic (Excel-based)
- `/var/www/kabas/app/Models/StockBatch.php` - Stock batch model
- `/var/www/kabas/app/Models/Reseller.php` - Reseller model with `allWithShops()`
- `/var/www/kabas/resources/views/reception/check-price.blade.php` - Barcode scanner implementation

### Session Variables (PWA)

- `reception_user_id` - Current user ID
- `reception_user_name` - Current user name
- `reception_store_id` - User's assigned store

---

## Questions to Clarify Later

1. Should there be a limit on how many inventories can be "in_progress" at once?
2. For reseller inventory alerts, what is the follow-up workflow?
3. Should inventory items include products with 0 theoretical stock?
4. Do we need to track time spent on inventory?
5. Should completed inventories be archived after X days?

---

*Document created: 2025-12-23*
*Last updated: 2025-12-23*
