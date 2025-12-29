# POS Exchange & Voucher System Specification

## Overview

This document specifies the implementation of a product exchange system for the Kabas POS, including automatic voucher generation for price differences.

---

## 1. Voucher System

### 1.1 Voucher Specifications

| Property | Value |
|----------|-------|
| Code Format | `KBA` + 9 random digits (e.g., `KBA123456789`) |
| Validity | 6 months from creation date |
| Usage | Single use only |
| Scope | Valid in all stores |
| Value | Fixed amount in USD |

### 1.2 Voucher States

| State | Description |
|-------|-------------|
| `active` | Can be used as payment |
| `used` | Has been redeemed |
| `expired` | Validity period exceeded |
| `cancelled` | Manually cancelled by admin |

### 1.3 Voucher Generation Rules

- **Automatic**: Generated when exchange creates a positive credit for customer
- **Manual**: Admin can create vouchers from Back Office (for exceptional cases)

---

## 2. Exchange System

### 2.1 Exchange Rules

| Rule | Constraint |
|------|------------|
| Time Limit | 30 days from original purchase |
| Proof Required | Original ticket (sale ID) |
| Product Condition | Not damaged, with original tags |
| Multi-article | Yes, can return multiple items from same ticket |
| Cross-store | Yes, can exchange at any store |

### 2.2 Exchange Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                        EXCHANGE PROCESS                              │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  1. SCAN ORIGINAL TICKET                                            │
│     └─> Validate: exists, within 30 days, has returnable items      │
│                                                                      │
│  2. SELECT ITEMS TO RETURN                                          │
│     └─> Show eligible items with prices PAID (from original sale)   │
│                                                                      │
│  3. SCAN NEW ITEMS (optional)                                       │
│     └─> Add replacement products at CURRENT price                   │
│                                                                      │
│  4. CALCULATE BALANCE                                               │
│     │                                                                │
│     ├─> If RETURN_TOTAL > NEW_TOTAL                                 │
│     │   └─> Generate VOUCHER for difference                         │
│     │                                                                │
│     ├─> If RETURN_TOTAL < NEW_TOTAL                                 │
│     │   └─> Customer pays difference (cash/card/voucher)            │
│     │                                                                │
│     └─> If RETURN_TOTAL = NEW_TOTAL                                 │
│         └─> Even exchange, no payment needed                        │
│                                                                      │
│  5. FINALIZE                                                        │
│     └─> Update stock, create records, print receipt                 │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

### 2.3 Price Rules

| Scenario | Price Used |
|----------|------------|
| Returned items | Price PAID on original ticket (from `sale_items.unit_price`) |
| New items | CURRENT selling price |

> **Important**: If an item was bought at 50% discount, the return credit is the discounted price paid, not the original price.

### 2.4 Stock Management

#### For Returned Items
- Create `StockBatch` with:
  - `source_exchange_id` = exchange ID
  - `store_id` = current store (where exchange happens)
  - `unit_price` = original purchase price (for COGS tracking)
- Create `StockTransaction` with:
  - `type` = `in`
  - `reason` = `exchange_return`

#### For New Items Sold
- Normal sale process (FIFO stock deduction)
- Create `StockTransaction` with:
  - `type` = `out`
  - `reason` = `exchange_sale`

### 2.5 Edge Cases

#### Customer Returns Without Taking New Items
- Allowed
- Full return value goes to voucher
- No cash refund (store policy)

#### Customer Wants to Exchange Item Already Exchanged
- Check `exchange_items` table
- If item already has `exchanged_at` set → reject
- Each item can only be exchanged once

#### Original Ticket Has Mixed Items (Some Already Exchanged)
- Show only items where `exchanged_at` IS NULL
- Display exchanged items as "Already exchanged" (grayed out)

---

## 3. Database Schema

### 3.1 New Tables

#### `vouchers`
```sql
CREATE TABLE vouchers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(12) NOT NULL UNIQUE,          -- KBA + 9 digits
    amount DECIMAL(10,2) NOT NULL,             -- Value in USD
    status ENUM('active', 'used', 'expired', 'cancelled') DEFAULT 'active',

    -- Source tracking
    source_type ENUM('exchange', 'manual', 'promotion') NOT NULL,
    source_exchange_id BIGINT UNSIGNED NULL,   -- If from exchange

    -- Usage tracking
    used_at TIMESTAMP NULL,
    used_in_sale_id BIGINT UNSIGNED NULL,
    used_at_store_id BIGINT UNSIGNED NULL,

    -- Validity
    expires_at TIMESTAMP NOT NULL,

    -- Audit
    created_by_user_id BIGINT UNSIGNED NULL,   -- Staff who created
    created_at_store_id BIGINT UNSIGNED NULL,  -- Store where created
    cancelled_by_user_id BIGINT UNSIGNED NULL,
    cancelled_at TIMESTAMP NULL,
    cancellation_reason VARCHAR(255) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_code (code),
    INDEX idx_status (status),
    INDEX idx_expires_at (expires_at),
    FOREIGN KEY (source_exchange_id) REFERENCES exchanges(id),
    FOREIGN KEY (used_in_sale_id) REFERENCES sales(id),
    FOREIGN KEY (used_at_store_id) REFERENCES stores(id),
    FOREIGN KEY (created_by_user_id) REFERENCES users(id),
    FOREIGN KEY (created_at_store_id) REFERENCES stores(id)
);
```

#### `exchanges`
```sql
CREATE TABLE exchanges (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Original sale reference
    original_sale_id BIGINT UNSIGNED NOT NULL,

    -- Location & staff
    store_id BIGINT UNSIGNED NOT NULL,         -- Where exchange happened
    user_id BIGINT UNSIGNED NOT NULL,          -- Staff processing

    -- Amounts
    return_total DECIMAL(10,2) NOT NULL,       -- Sum of returned items
    new_items_total DECIMAL(10,2) NOT NULL,    -- Sum of new items (0 if none)
    balance DECIMAL(10,2) NOT NULL,            -- return_total - new_items_total

    -- Payment for difference (if customer owes)
    payment_method VARCHAR(20) NULL,           -- cash, card, voucher
    payment_amount DECIMAL(10,2) NULL,
    payment_voucher_id BIGINT UNSIGNED NULL,   -- If paid with voucher

    -- Generated voucher (if store owes customer)
    generated_voucher_id BIGINT UNSIGNED NULL,

    -- Related sale (for new items)
    new_sale_id BIGINT UNSIGNED NULL,          -- If new items were taken

    notes TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (original_sale_id) REFERENCES sales(id),
    FOREIGN KEY (store_id) REFERENCES stores(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (payment_voucher_id) REFERENCES vouchers(id),
    FOREIGN KEY (generated_voucher_id) REFERENCES vouchers(id),
    FOREIGN KEY (new_sale_id) REFERENCES sales(id)
);
```

#### `exchange_items`
```sql
CREATE TABLE exchange_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exchange_id BIGINT UNSIGNED NOT NULL,

    -- What was returned
    original_sale_item_id BIGINT UNSIGNED NOT NULL,  -- Reference to sale_items
    product_id BIGINT UNSIGNED NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,               -- Price paid originally
    total_price DECIMAL(10,2) NOT NULL,

    -- Stock tracking
    stock_batch_id BIGINT UNSIGNED NULL,             -- Created batch for return

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (exchange_id) REFERENCES exchanges(id) ON DELETE CASCADE,
    FOREIGN KEY (original_sale_item_id) REFERENCES sale_items(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (stock_batch_id) REFERENCES stock_batches(id)
);
```

### 3.2 Modified Tables

#### `sale_items` - Add column
```sql
ALTER TABLE sale_items
ADD COLUMN exchanged_at TIMESTAMP NULL,
ADD COLUMN exchanged_in_exchange_id BIGINT UNSIGNED NULL,
ADD FOREIGN KEY (exchanged_in_exchange_id) REFERENCES exchanges(id);
```

#### `sales` - Add payment type
```sql
-- Ensure 'voucher' is a valid payment method
-- If using ENUM, add 'voucher' to the list
-- If using VARCHAR, no change needed
```

#### `stock_batches` - Add source
```sql
ALTER TABLE stock_batches
ADD COLUMN source_exchange_id BIGINT UNSIGNED NULL,
ADD FOREIGN KEY (source_exchange_id) REFERENCES exchanges(id);
```

#### `stock_transactions` - Add reason
```sql
-- Ensure these reasons are valid: 'exchange_return', 'exchange_sale'
```

---

## 4. API Endpoints

### 4.1 POS Endpoints

#### Lookup Original Sale
```
GET /api/pos/exchange/lookup-sale?ticket_id={id}
```

**Response:**
```json
{
    "success": true,
    "sale": {
        "id": 12345,
        "created_at": "2024-12-01T14:30:00",
        "store": "Phnom Penh",
        "days_since_purchase": 15,
        "is_exchangeable": true,
        "items": [
            {
                "sale_item_id": 1001,
                "product_id": 50,
                "product_name": "T-Shirt Blue M",
                "quantity": 1,
                "unit_price": 25.00,
                "is_exchangeable": true,
                "exchanged_at": null
            },
            {
                "sale_item_id": 1002,
                "product_id": 51,
                "product_name": "Jeans Black 32",
                "quantity": 1,
                "unit_price": 45.00,
                "is_exchangeable": false,
                "exchanged_at": "2024-12-10T10:00:00",
                "exchange_reason": "Already exchanged"
            }
        ]
    }
}
```

**Errors:**
- `404`: Sale not found
- `400`: Sale older than 30 days

#### Process Exchange
```
POST /api/pos/exchange/process
```

**Request:**
```json
{
    "original_sale_id": 12345,
    "returned_items": [
        {
            "sale_item_id": 1001,
            "quantity": 1
        }
    ],
    "new_items": [
        {
            "product_id": 60,
            "quantity": 1
        }
    ],
    "payment": {
        "method": "cash",        // cash, card, voucher, null
        "amount": 10.00,         // If customer pays difference
        "voucher_code": null     // If paying with voucher
    },
    "notes": "Customer wanted different size"
}
```

**Response:**
```json
{
    "success": true,
    "exchange": {
        "id": 500,
        "return_total": 25.00,
        "new_items_total": 35.00,
        "balance": -10.00,
        "payment_received": 10.00,
        "voucher_generated": null
    },
    "receipt": {
        "type": "exchange",
        "print_data": "..."
    }
}
```

**Or if voucher generated:**
```json
{
    "success": true,
    "exchange": {
        "id": 501,
        "return_total": 45.00,
        "new_items_total": 25.00,
        "balance": 20.00,
        "payment_received": 0,
        "voucher_generated": {
            "code": "KBA847291035",
            "amount": 20.00,
            "expires_at": "2025-06-16"
        }
    },
    "receipt": {
        "type": "exchange_with_voucher",
        "print_data": "..."
    }
}
```

#### Validate Voucher
```
GET /api/pos/voucher/validate?code={code}
```

**Response:**
```json
{
    "success": true,
    "voucher": {
        "code": "KBA847291035",
        "amount": 20.00,
        "status": "active",
        "expires_at": "2025-06-16",
        "created_at": "2024-12-16",
        "source": "exchange"
    }
}
```

**Errors:**
- `404`: Voucher not found
- `400`: Voucher already used / expired / cancelled

#### Apply Voucher to Sale
```
POST /api/pos/voucher/apply
```

**Request:**
```json
{
    "sale_id": 12400,
    "voucher_code": "KBA847291035"
}
```

**Response:**
```json
{
    "success": true,
    "voucher_amount": 20.00,
    "remaining_to_pay": 15.00
}
```

---

## 5. Back Office Management

### 5.1 Voucher Management Page

**Location:** `/admin/vouchers`

**Features:**
- List all vouchers with filters:
  - Status (active, used, expired, cancelled)
  - Date range
  - Store created
  - Amount range
- Search by code
- View voucher details
- Cancel voucher (with reason)
- Create manual voucher (admin only)
- Export to CSV

**Voucher Detail View:**
- Full voucher info
- Creation source (exchange link if applicable)
- Usage info (sale link if used)
- Audit trail

### 5.2 Exchange History Page

**Location:** `/admin/exchanges`

**Features:**
- List all exchanges with filters:
  - Date range
  - Store
  - Staff member
  - Original sale ID
- View exchange details:
  - Original sale info
  - Returned items
  - New items taken
  - Balance calculation
  - Payment or voucher generated
- Link to generated voucher (if any)

### 5.3 Reports

#### Voucher Report
- Total vouchers issued (count & value)
- Vouchers redeemed (count & value)
- Outstanding vouchers (count & value)
- Expired vouchers (count & value lost)
- By store breakdown

#### Exchange Report
- Total exchanges (count)
- Return value vs new items value
- Average exchange balance
- Most exchanged products
- Exchange rate by store

---

## 6. POS Interface

### 6.1 Exchange Mode Entry

**From main POS screen:**
- Add "Exchange" button in header/menu
- Or keyboard shortcut (F8 suggested)

### 6.2 Exchange Screen Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│  EXCHANGE MODE                                          [X] Cancel  │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  Step 1: Enter Original Ticket                                      │
│  ┌─────────────────────────────────────┐                           │
│  │ Ticket #: [____________] [Search]   │                           │
│  └─────────────────────────────────────┘                           │
│                                                                      │
│  ─────────────────────────────────────────────────────────────────  │
│                                                                      │
│  Original Sale #12345 - 01/12/2024 - Phnom Penh                    │
│  15 days ago (✓ within 30 days)                                    │
│                                                                      │
│  ITEMS TO RETURN:                              RETURN CREDIT        │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │ [✓] T-Shirt Blue M          x1     $25.00              $25.00│  │
│  │ [✗] Jeans Black 32          x1     $45.00   (already exchanged)│ │
│  │ [ ] Cap Red                 x1     $15.00              $0.00 │  │
│  └──────────────────────────────────────────────────────────────┘  │
│                                                                      │
│  NEW ITEMS:                                    NEW ITEMS TOTAL      │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │ T-Shirt Green L             x1     $28.00              $28.00│  │
│  │                                                               │  │
│  │ [+ Add Product]                                              │  │
│  └──────────────────────────────────────────────────────────────┘  │
│                                                                      │
├─────────────────────────────────────────────────────────────────────┤
│  SUMMARY                                                            │
│  ─────────────────────────────────────────                          │
│  Return Credit:                    $25.00                           │
│  New Items:                       -$28.00                           │
│  ─────────────────────────────────────────                          │
│  Customer Owes:                    $3.00                            │
│                                                                      │
│  Payment: [Cash ▼]  [$3.00]                                        │
│                                                                      │
│                    [Complete Exchange]                              │
└─────────────────────────────────────────────────────────────────────┘
```

### 6.3 Voucher as Payment Method

**In regular sale or exchange:**
- Payment method dropdown includes "Voucher"
- When selected, prompt for voucher code
- Validate and apply
- Show remaining balance if voucher doesn't cover full amount
- Can combine with other payment methods

---

## 7. Receipt Format

### 7.1 Exchange Receipt

```
═══════════════════════════════════════
           KABAS CONCEPT STORE
           [Store Address]
═══════════════════════════════════════

           EXCHANGE RECEIPT

Date: 16/12/2024 14:30
Receipt #: EX-2024-000501
Staff: John D.
Original Sale: #12345 (01/12/2024)

───────────────────────────────────────
RETURNED ITEMS
───────────────────────────────────────
T-Shirt Blue M           1   $25.00
───────────────────────────────────────
Return Credit:               $25.00

───────────────────────────────────────
NEW ITEMS
───────────────────────────────────────
T-Shirt Green L          1   $28.00
───────────────────────────────────────
New Items Total:             $28.00

═══════════════════════════════════════
BALANCE DUE:                  $3.00
Paid (Cash):                  $3.00
═══════════════════════════════════════

Thank you for shopping at Kabas!
Exchange policy: 30 days with receipt
═══════════════════════════════════════
```

### 7.2 Exchange Receipt with Voucher Generated

```
═══════════════════════════════════════
           KABAS CONCEPT STORE
           [Store Address]
═══════════════════════════════════════

           EXCHANGE RECEIPT

Date: 16/12/2024 14:30
Receipt #: EX-2024-000502
Staff: John D.
Original Sale: #12345 (01/12/2024)

───────────────────────────────────────
RETURNED ITEMS
───────────────────────────────────────
Jeans Black 32           1   $45.00
───────────────────────────────────────
Return Credit:               $45.00

───────────────────────────────────────
NEW ITEMS
───────────────────────────────────────
T-Shirt Green L          1   $28.00
───────────────────────────────────────
New Items Total:             $28.00

═══════════════════════════════════════
YOUR CREDIT:                 $17.00
═══════════════════════════════════════

    ╔═══════════════════════════════╗
    ║      STORE CREDIT VOUCHER     ║
    ║                               ║
    ║      KBA 847 291 035          ║
    ║                               ║
    ║      Value: $17.00            ║
    ║      Valid until: 16/06/2025  ║
    ║                               ║
    ║   Use on your next purchase   ║
    ╚═══════════════════════════════╝

Thank you for shopping at Kabas!
Exchange policy: 30 days with receipt
═══════════════════════════════════════
```

---

## 8. Implementation Phases

### Phase 1: Database & Models
1. Create migrations for new tables
2. Create Voucher, Exchange, ExchangeItem models
3. Add relationships to existing models
4. Add `exchanged_at` column to sale_items

### Phase 2: Voucher Core
1. Voucher generation service (code generator)
2. Voucher validation logic
3. Voucher expiration handling (scheduled job)
4. Basic BO voucher list page

### Phase 3: Exchange Core
1. Exchange service class
2. Sale lookup with exchange eligibility
3. Exchange processing logic
4. Stock management for returns

### Phase 4: POS Integration
1. Exchange mode UI
2. Sale lookup interface
3. Item selection for return
4. New items addition
5. Balance calculation
6. Voucher payment method

### Phase 5: Back Office
1. Complete voucher management
2. Exchange history
3. Reports

### Phase 6: Testing & Polish
1. Edge case testing
2. Receipt formatting
3. User training documentation

---

## 9. Security Considerations

1. **Voucher Code Generation**: Use cryptographically secure random number generator
2. **Exchange Authorization**: Only staff with POS access can process exchanges
3. **Voucher Cancellation**: Requires admin role, logged with reason
4. **Audit Trail**: All voucher and exchange operations are logged
5. **Duplicate Prevention**: Check `exchanged_at` before allowing return

---

## 10. Open Questions (Resolved)

| Question | Answer |
|----------|--------|
| Voucher validity period? | 6 months |
| Code format? | KBA + 9 digits |
| BO management needed? | Yes, list/view/cancel/create |
| Return without new items? | Allowed, full value to voucher |
| Stock batch for returns? | Yes, with original purchase price |

---

*Document version: 1.0*
*Created: 2024-12-16*
*Author: Claude Code*
