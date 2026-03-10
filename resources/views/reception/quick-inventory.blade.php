@extends('reception.layouts.app')

@section('title', __('messages.reception.quick_inventory'))

@section('styles')
<style>
    /* ─── Shared ─── */
    .screen { display: none; }
    .screen.active { display: block; }

    /* ─── Screen 1 : Store ─── */
    .location-form select {
        width: 100%;
        height: 48px;
        padding: 0 16px;
        border: 2px solid var(--border);
        border-radius: 12px;
        font-size: 16px;
        background: var(--white);
        outline: none;
        margin-bottom: 12px;
    }
    .location-form select:focus { border-color: var(--primary); }
    .location-form label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 6px;
        color: var(--text-light);
    }

    /* ─── Screen 2 : Mode ─── */
    .mode-btn {
        width: 100%;
        padding: 20px;
        border: 2px solid var(--border);
        border-radius: 12px;
        background: var(--white);
        cursor: pointer;
        text-align: left;
        margin-bottom: 12px;
        transition: border-color 0.2s;
    }
    .mode-btn:hover { border-color: var(--primary); }
    .mode-btn .mode-icon { font-size: 24px; margin-bottom: 6px; }
    .mode-btn .mode-title { font-size: 16px; font-weight: 600; }
    .mode-btn .mode-desc { font-size: 13px; color: var(--text-light); margin-top: 4px; }

    /* ─── Screen 2a : Manual search ─── */
    .search-bar {
        display: flex;
        gap: 8px;
        margin-bottom: 12px;
    }
    .search-bar input {
        flex: 1;
        height: 44px;
        padding: 0 14px;
        border: 2px solid var(--border);
        border-radius: 12px;
        font-size: 15px;
        outline: none;
    }
    .search-bar input:focus { border-color: var(--primary); }
    .search-bar button {
        height: 44px;
        width: 44px;
        border: none;
        border-radius: 12px;
        background: var(--primary);
        color: var(--white);
        font-size: 20px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .search-results {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-bottom: 16px;
        max-height: 250px;
        overflow-y: auto;
    }
    .search-result-item {
        background: var(--white);
        border: 2px solid var(--border);
        border-radius: 10px;
        padding: 10px 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: border-color 0.2s;
    }
    .search-result-item:hover { border-color: var(--primary); }
    .search-result-item .sri-info { flex: 1; min-width: 0; }
    .search-result-item .sri-name { font-size: 14px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .search-result-item .sri-meta { font-size: 12px; color: var(--text-light); margin-top: 2px; }
    .search-result-item .sri-add {
        width: 32px; height: 32px;
        border-radius: 50%;
        background: var(--primary);
        color: var(--white);
        border: none;
        font-size: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-left: 8px;
    }

    .selected-products { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
    .selected-item {
        background: var(--white);
        border: 2px solid var(--primary);
        border-radius: 10px;
        padding: 10px 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .selected-item .si-info { flex: 1; min-width: 0; }
    .selected-item .si-name { font-size: 14px; font-weight: 600; }
    .selected-item .si-meta { font-size: 12px; color: var(--text-light); }
    .selected-item .si-remove {
        width: 28px; height: 28px;
        border-radius: 50%;
        background: var(--danger);
        color: var(--white);
        border: none;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
        margin-left: 8px;
    }

    /* ─── Screen 3 : Counting ─── */
    .filter-tabs {
        display: flex;
        gap: 6px;
        margin-bottom: 12px;
        overflow-x: auto;
    }
    .filter-tab {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        border: 2px solid var(--border);
        background: var(--white);
        cursor: pointer;
        white-space: nowrap;
    }
    .filter-tab.active {
        background: var(--primary);
        color: var(--white);
        border-color: var(--primary);
    }
    .filter-tab .badge-count {
        display: inline-block;
        background: rgba(0,0,0,0.15);
        border-radius: 10px;
        padding: 0 6px;
        margin-left: 4px;
        font-size: 11px;
    }
    .filter-tab.active .badge-count {
        background: rgba(255,255,255,0.3);
    }

    .product-list { display: flex; flex-direction: column; gap: 8px; }

    .product-row {
        background: var(--white);
        border-radius: 12px;
        padding: 12px 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        border: 2px solid var(--border);
        transition: border-color 0.2s;
    }
    .product-row.counted-match { border-color: var(--success); }
    .product-row.counted-diff { border-color: #f59e0b; }
    .product-row.highlight {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb, 79, 70, 229), 0.2);
        animation: pulse 0.6s;
    }
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.01); }
    }

    .product-info { flex: 1; min-width: 0; }
    .product-info .name {
        font-size: 14px;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .product-info .meta {
        font-size: 12px;
        color: var(--text-light);
        margin-top: 2px;
    }
    .product-info .last-count {
        font-size: 11px;
        color: var(--text-light);
        margin-top: 2px;
        font-style: italic;
    }

    .product-stock {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        min-width: 70px;
    }
    .product-stock .label {
        font-size: 11px;
        color: var(--text-light);
    }
    .product-stock .theo-value {
        font-size: 16px;
        font-weight: 700;
    }

    .product-input {
        width: 70px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
    }
    .product-input .label {
        font-size: 11px;
        color: var(--text-light);
    }
    .product-input input {
        width: 60px;
        height: 40px;
        text-align: center;
        font-size: 16px;
        font-weight: 700;
        border: 2px solid var(--border);
        border-radius: 10px;
        outline: none;
    }
    .product-input input:focus { border-color: var(--primary); }

    .sticky-bottom {
        position: sticky;
        bottom: 0;
        padding: 16px 0;
        background: var(--bg);
    }

    /* ─── Screen 4 : Review ─── */
    .summary-cards {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 16px;
    }
    .summary-card {
        background: var(--white);
        border-radius: 12px;
        padding: 16px;
        text-align: center;
    }
    .summary-card .value {
        font-size: 28px;
        font-weight: 700;
    }
    .summary-card .label {
        font-size: 13px;
        color: var(--text-light);
        margin-top: 4px;
    }

    .diff-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: var(--white);
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 16px;
    }
    .diff-table th {
        background: var(--bg);
        padding: 10px 12px;
        font-size: 12px;
        font-weight: 600;
        color: var(--text-light);
        text-align: left;
    }
    .diff-table td {
        padding: 10px 12px;
        font-size: 14px;
        border-top: 1px solid var(--border);
    }
    .diff-table .diff-positive { color: var(--success); font-weight: 700; }
    .diff-table .diff-negative { color: var(--danger); font-weight: 700; }
    .no-diff-message {
        background: #ecfdf5;
        color: #065f46;
        padding: 20px;
        border-radius: 12px;
        text-align: center;
        margin-bottom: 16px;
    }
    .note-message {
        background: #eff6ff;
        color: #1e40af;
        padding: 14px;
        border-radius: 12px;
        text-align: center;
        margin-bottom: 16px;
        font-size: 13px;
    }

    /* ─── Scanner overlay ─── */
    .scanner-overlay-full {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: #000;
        z-index: 1000;
    }
    .scanner-overlay-full.active { display: block; }
    .scanner-overlay-full video,
    .scanner-overlay-full canvas {
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        min-width: 100%; min-height: 100%;
        width: auto; height: auto;
        object-fit: cover;
    }
    .scanner-guide {
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        width: 90%; height: 200px;
        border: 3px solid var(--success);
        border-radius: 8px;
        box-shadow: 0 0 0 9999px rgba(0,0,0,0.5);
        z-index: 10;
    }
    .scanner-guide .line {
        position: absolute; top: 0; left: 0; right: 0;
        height: 2px; background: var(--success);
        animation: scanLine 2s linear infinite;
    }
    @keyframes scanLine { 0%{top:0} 50%{top:100%} 100%{top:0} }
    .scanner-close {
        position: absolute;
        top: 16px; right: 16px;
        z-index: 20;
        width: 44px; height: 44px;
        border-radius: 50%;
        background: rgba(0,0,0,0.6);
        color: #fff;
        border: none;
        font-size: 24px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>
@endsection

@section('content')
<div class="header">
    <a href="{{ route('reception.home') }}" class="header-back" id="headerBack">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        {{ __('messages.reception.back') }}
    </a>
    <div class="header-title">{{ __('messages.reception.quick_inventory') }}</div>
    <div style="width: 60px;"></div>
</div>

<div class="container">

    {{-- ═══ SCREEN 1 : Store selection ═══ --}}
    <div class="screen active" id="screenStore">
        <div class="card" style="margin-top: 16px; padding: 20px;">
            <h3 style="margin: 0 0 4px;">{{ __('messages.reception.quick_inventory') }}</h3>
            <p style="color: var(--text-light); font-size: 14px; margin: 0 0 20px;">{{ __('messages.reception.quick_inventory_subtitle') }}</p>

            <div class="location-form">
                <label>{{ __('messages.reception.select_store') }}</label>
                <select id="storeId">
                    <option value="">{{ __('messages.reception.select_location') }}</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}">{{ $store->name }}</option>
                    @endforeach
                </select>

                <button class="btn btn-primary btn-lg" id="btnSelectStore" style="width:100%; margin-top: 8px;" disabled>
                    {{ __('messages.reception.continue') }}
                </button>
            </div>
        </div>
    </div>

    {{-- ═══ SCREEN 2 : Mode selection ═══ --}}
    <div class="screen" id="screenMode">
        <div style="margin-top: 16px;">
            <h3 style="margin: 0 0 4px;">{{ __('messages.reception.select_mode') }}</h3>
            <p style="color: var(--text-light); font-size: 14px; margin: 0 0 20px;" id="storeName"></p>

            <button class="mode-btn" id="btnModeManual">
                <div class="mode-icon">🔍</div>
                <div class="mode-title">{{ __('messages.reception.mode_manual') }}</div>
                <div class="mode-desc">{{ __('messages.reception.mode_manual_desc') }}</div>
            </button>

            <button class="mode-btn" id="btnModeBrand">
                <div class="mode-icon">🏷️</div>
                <div class="mode-title">{{ __('messages.reception.mode_brand') }}</div>
                <div class="mode-desc">{{ __('messages.reception.mode_brand_desc') }}</div>
            </button>
        </div>
    </div>

    {{-- ═══ SCREEN 2a : Manual search ═══ --}}
    <div class="screen" id="screenManual">
        <div style="margin-top: 16px;">
            <h3 style="margin: 0 0 12px;">{{ __('messages.reception.mode_manual') }}</h3>

            <div class="search-bar">
                <input type="text" id="manualSearchInput" placeholder="{{ __('messages.reception.search_name_ean') }}">
                <button id="btnManualScan" title="{{ __('messages.reception.scan_barcode') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M1.5 1a.5.5 0 0 0-.5.5v3a.5.5 0 0 1-1 0v-3A1.5 1.5 0 0 1 1.5 0h3a.5.5 0 0 1 0 1h-3zM11 .5a.5.5 0 0 1 .5-.5h3A1.5 1.5 0 0 1 16 1.5v3a.5.5 0 0 1-1 0v-3a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 1-.5-.5zM.5 11a.5.5 0 0 1 .5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 1 0 1h-3A1.5 1.5 0 0 1 0 14.5v-3a.5.5 0 0 1 .5-.5zm15 0a.5.5 0 0 1 .5.5v3a1.5 1.5 0 0 1-1.5 1.5h-3a.5.5 0 0 1 0-1h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 1 .5-.5zM3 4.5a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0v-7zm2 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0v-7zm2 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0v-7zm2 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-7zm3 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0v-7z"/>
                    </svg>
                </button>
            </div>

            <div class="search-results" id="searchResults"></div>

            <div id="selectedSection" style="display: none;">
                <label style="font-size: 14px; font-weight: 600; color: var(--text-light); margin-bottom: 8px; display: block;">
                    {{ __('messages.reception.selected_products') }} (<span id="selectedCount">0</span>)
                </label>
                <div class="selected-products" id="selectedProducts"></div>
            </div>

            <div class="sticky-bottom">
                <button class="btn btn-primary btn-lg" id="btnStartManualCount" style="width: 100%;" disabled>
                    {{ __('messages.reception.start_counting') }}
                </button>
            </div>
        </div>
    </div>

    {{-- ═══ SCREEN 2b : Brand mode ═══ --}}
    <div class="screen" id="screenBrand">
        <div style="margin-top: 16px;">
            <h3 style="margin: 0 0 12px;">{{ __('messages.reception.mode_brand') }}</h3>

            <div class="location-form">
                <label>{{ __('messages.reception.select_brand') }}</label>
                <select id="brandId" disabled>
                    <option value="">{{ __('messages.reception.loading') }}...</option>
                </select>

                <button class="btn btn-primary btn-lg" id="btnLoadBrand" style="width:100%; margin-top: 8px;" disabled>
                    {{ __('messages.reception.load_products') }}
                </button>
            </div>
        </div>
    </div>

    {{-- ═══ SCREEN 3 : Counting ═══ --}}
    <div class="screen" id="screenCounting">
        <div style="margin-top: 16px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <strong id="countingTitle"></strong>
            </div>

            <div class="search-bar">
                <input type="text" id="countSearchInput" placeholder="{{ __('messages.reception.search_name_ean') }}">
                <button id="btnCountScan" title="{{ __('messages.reception.scan_barcode') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M1.5 1a.5.5 0 0 0-.5.5v3a.5.5 0 0 1-1 0v-3A1.5 1.5 0 0 1 1.5 0h3a.5.5 0 0 1 0 1h-3zM11 .5a.5.5 0 0 1 .5-.5h3A1.5 1.5 0 0 1 16 1.5v3a.5.5 0 0 1-1 0v-3a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 1-.5-.5zM.5 11a.5.5 0 0 1 .5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 1 0 1h-3A1.5 1.5 0 0 1 0 14.5v-3a.5.5 0 0 1 .5-.5zm15 0a.5.5 0 0 1 .5.5v3a1.5 1.5 0 0 1-1.5 1.5h-3a.5.5 0 0 1 0-1h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 1 .5-.5zM3 4.5a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0v-7zm2 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0v-7zm2 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0v-7zm2 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-7zm3 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0v-7z"/>
                    </svg>
                </button>
            </div>

            <div class="filter-tabs">
                <button class="filter-tab active" data-filter="all">{{ __('messages.reception.all') }} <span class="badge-count" id="countAll">0</span></button>
                <button class="filter-tab" data-filter="remaining">{{ __('messages.reception.remaining') }} <span class="badge-count" id="countRemaining">0</span></button>
                <button class="filter-tab" data-filter="counted">{{ __('messages.reception.counted') }} <span class="badge-count" id="countCounted">0</span></button>
                <button class="filter-tab" data-filter="differences">{{ __('messages.reception.differences') }} <span class="badge-count" id="countDiff">0</span></button>
            </div>

            <div class="product-list" id="productList"></div>

            <div class="sticky-bottom">
                <button class="btn btn-primary btn-lg" id="btnReview" style="width: 100%;">
                    {{ __('messages.reception.validate_counted') }}
                </button>
            </div>
        </div>
    </div>

    {{-- ═══ SCREEN 4 : Review ═══ --}}
    <div class="screen" id="screenReview">
        <div style="margin-top: 16px;">
            <h3 style="margin: 0 0 16px;">{{ __('messages.reception.review_title') }}</h3>

            <div class="summary-cards">
                <div class="summary-card">
                    <div class="value" id="reviewCounted">0</div>
                    <div class="label">{{ __('messages.reception.counted') }}</div>
                </div>
                <div class="summary-card">
                    <div class="value" id="reviewDiffs">0</div>
                    <div class="label">{{ __('messages.reception.adjustments') }}</div>
                </div>
            </div>

            <div id="diffTableWrapper"></div>

            <div class="note-message">
                {{ __('messages.reception.uncounted_not_affected') }}
            </div>

            <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 8px;">
                <button class="btn btn-primary btn-lg" id="btnApply" style="width: 100%;">
                    {{ __('messages.reception.validate_and_apply') }}
                </button>
                <button class="btn btn-outline btn-lg" id="btnBackCount" style="width: 100%;">
                    {{ __('messages.reception.back_to_counting') }}
                </button>
            </div>
        </div>
    </div>

</div>

{{-- Scanner fullscreen overlay --}}
<div class="scanner-overlay-full" id="scannerOverlay">
    <div id="scannerTarget"></div>
    <div class="scanner-guide"><div class="line"></div></div>
    <button class="scanner-close" id="btnCloseScanner">&times;</button>
</div>
@endsection

@section('scripts')
<script src="https://unpkg.com/@ericblade/quagga2@1.8.4/dist/quagga.min.js"></script>
<script>
(function() {
    // ─── State ───
    let storeId = '';
    let storeName = '';
    let products = [];
    let selectedManual = []; // products selected in manual mode before counting
    let currentFilter = 'all';
    let searchQuery = '';
    let scannerInitialized = false;
    let scannerCallback = null; // which mode uses the scanner

    // ─── DOM refs ───
    const screenStore = document.getElementById('screenStore');
    const screenMode = document.getElementById('screenMode');
    const screenManual = document.getElementById('screenManual');
    const screenBrand = document.getElementById('screenBrand');
    const screenCounting = document.getElementById('screenCounting');
    const screenReview = document.getElementById('screenReview');

    const storeIdSelect = document.getElementById('storeId');
    const btnSelectStore = document.getElementById('btnSelectStore');
    const storeNameEl = document.getElementById('storeName');
    const countingTitle = document.getElementById('countingTitle');
    const productListEl = document.getElementById('productList');
    const countSearchInput = document.getElementById('countSearchInput');
    const btnReview = document.getElementById('btnReview');
    const btnApply = document.getElementById('btnApply');
    const btnBackCount = document.getElementById('btnBackCount');
    const scannerOverlay = document.getElementById('scannerOverlay');
    const headerBack = document.getElementById('headerBack');

    // ─── Screen navigation ───
    const screens = [screenStore, screenMode, screenManual, screenBrand, screenCounting, screenReview];
    let screenHistory = [screenStore];

    function showScreen(screen) {
        screens.forEach(s => s.classList.remove('active'));
        screen.classList.add('active');
        // Track history for back button
        if (screenHistory[screenHistory.length - 1] !== screen) {
            screenHistory.push(screen);
        }
        updateBackButton(screen);
    }

    function goBack() {
        if (screenHistory.length > 1) {
            screenHistory.pop();
            const prev = screenHistory[screenHistory.length - 1];
            screens.forEach(s => s.classList.remove('active'));
            prev.classList.add('active');
            updateBackButton(prev);
        }
    }

    function updateBackButton(screen) {
        if (screen === screenStore) {
            headerBack.onclick = null;
            headerBack.href = '{{ route("reception.home") }}';
        } else {
            headerBack.onclick = function(e) { e.preventDefault(); goBack(); };
        }
    }

    // ─── Screen 1: Store selection ───
    storeIdSelect.addEventListener('change', function() {
        btnSelectStore.disabled = !this.value;
    });

    btnSelectStore.addEventListener('click', function() {
        storeId = storeIdSelect.value;
        storeName = storeIdSelect.options[storeIdSelect.selectedIndex].text;
        storeNameEl.textContent = storeName;
        showScreen(screenMode);
    });

    // ─── Screen 2: Mode selection ───
    document.getElementById('btnModeManual').addEventListener('click', function() {
        selectedManual = [];
        renderSelectedProducts();
        showScreen(screenManual);
        document.getElementById('manualSearchInput').focus();
    });

    document.getElementById('btnModeBrand').addEventListener('click', function() {
        showScreen(screenBrand);
        loadBrands();
    });

    // ─── Screen 2a: Manual search ───
    let searchTimeout = null;
    const manualSearchInput = document.getElementById('manualSearchInput');
    const searchResultsEl = document.getElementById('searchResults');
    const selectedProductsEl = document.getElementById('selectedProducts');
    const btnStartManualCount = document.getElementById('btnStartManualCount');

    manualSearchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const q = this.value.trim();
        if (q.length < 2) {
            searchResultsEl.innerHTML = '';
            return;
        }
        searchTimeout = setTimeout(() => searchProducts(q), 300);
    });

    async function searchProducts(query) {
        try {
            const res = await fetch('{{ route("reception.quick-inventory.search-products") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken },
                body: JSON.stringify({ store_id: storeId, query: query })
            });
            const data = await res.json();
            renderSearchResults(data.products || []);
        } catch (err) {
            console.error(err);
        }
    }

    function renderSearchResults(results) {
        searchResultsEl.innerHTML = '';
        const selectedIds = selectedManual.map(p => p.id);

        results.forEach(p => {
            if (selectedIds.includes(p.id)) return; // already selected

            const item = document.createElement('div');
            item.className = 'search-result-item';
            item.innerHTML = `
                <div class="sri-info">
                    <div class="sri-name">${escHtml(p.name)}</div>
                    <div class="sri-meta">${escHtml(p.brand || '')}${p.ean ? ' &middot; ' + escHtml(p.ean) : ''} &middot; {{ __('messages.reception.stock') }}: ${p.theoretical}</div>
                </div>
                <button class="sri-add">+</button>
            `;
            item.querySelector('.sri-add').addEventListener('click', function(e) {
                e.stopPropagation();
                addManualProduct(p);
            });
            item.addEventListener('click', function() {
                addManualProduct(p);
            });
            searchResultsEl.appendChild(item);
        });
    }

    function addManualProduct(p) {
        if (selectedManual.find(s => s.id === p.id)) return;
        selectedManual.push(p);
        renderSelectedProducts();
        manualSearchInput.value = '';
        searchResultsEl.innerHTML = '';
        manualSearchInput.focus();
    }

    function renderSelectedProducts() {
        const section = document.getElementById('selectedSection');
        document.getElementById('selectedCount').textContent = selectedManual.length;
        section.style.display = selectedManual.length > 0 ? 'block' : 'none';
        btnStartManualCount.disabled = selectedManual.length === 0;

        selectedProductsEl.innerHTML = '';
        selectedManual.forEach(p => {
            const item = document.createElement('div');
            item.className = 'selected-item';
            item.innerHTML = `
                <div class="si-info">
                    <div class="si-name">${escHtml(p.name)}</div>
                    <div class="si-meta">${escHtml(p.brand || '')}${p.ean ? ' &middot; ' + escHtml(p.ean) : ''}</div>
                </div>
                <button class="si-remove">&times;</button>
            `;
            item.querySelector('.si-remove').addEventListener('click', function() {
                selectedManual = selectedManual.filter(s => s.id !== p.id);
                renderSelectedProducts();
            });
            selectedProductsEl.appendChild(item);
        });
    }

    // Scanner for manual mode
    document.getElementById('btnManualScan').addEventListener('click', function() {
        scannerCallback = 'manual';
        openScanner();
    });

    btnStartManualCount.addEventListener('click', async function() {
        btnStartManualCount.disabled = true;
        btnStartManualCount.textContent = '{{ __("messages.reception.loading_products") }}';

        try {
            const res = await fetch('{{ route("reception.quick-inventory.products") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken },
                body: JSON.stringify({ store_id: storeId, product_ids: selectedManual.map(p => p.id) })
            });
            const data = await res.json();

            products = (data.products || []).map(p => ({ ...p, real: null }));
            countingTitle.textContent = storeName + ' — ' + products.length + ' {{ __("messages.reception.products") }}';
            restoreFromLocalStorage();
            renderProducts();
            updateFilterCounts();
            showScreen(screenCounting);
        } catch (err) {
            alert(err.message);
        } finally {
            btnStartManualCount.disabled = false;
            btnStartManualCount.textContent = '{{ __("messages.reception.start_counting") }}';
        }
    });

    // ─── Screen 2b: Brand mode ───
    const brandIdSelect = document.getElementById('brandId');
    const btnLoadBrand = document.getElementById('btnLoadBrand');

    async function loadBrands() {
        brandIdSelect.disabled = true;
        brandIdSelect.innerHTML = '<option value="">{{ __("messages.reception.loading") }}...</option>';

        try {
            const res = await fetch('{{ route("reception.quick-inventory.brands") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken },
                body: JSON.stringify({ store_id: storeId })
            });
            const data = await res.json();

            brandIdSelect.innerHTML = '<option value="">{{ __("messages.reception.select_brand") }}</option>';
            (data.brands || []).forEach(b => {
                const opt = document.createElement('option');
                opt.value = b.id;
                opt.textContent = b.name;
                brandIdSelect.appendChild(opt);
            });
            brandIdSelect.disabled = false;
        } catch (err) {
            alert(err.message);
        }
    }

    brandIdSelect.addEventListener('change', function() {
        btnLoadBrand.disabled = !this.value;
    });

    btnLoadBrand.addEventListener('click', async function() {
        const brandId = brandIdSelect.value;
        if (!brandId) return;

        btnLoadBrand.disabled = true;
        btnLoadBrand.textContent = '{{ __("messages.reception.loading_products") }}';

        try {
            const res = await fetch('{{ route("reception.quick-inventory.products") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken },
                body: JSON.stringify({ store_id: storeId, brand_id: brandId })
            });
            const data = await res.json();

            if (!data.products || data.products.length === 0) {
                alert('{{ __("messages.reception.no_products_found") }}');
                return;
            }

            products = data.products.map(p => ({ ...p, real: null }));
            const brandName = brandIdSelect.options[brandIdSelect.selectedIndex].text;
            countingTitle.textContent = storeName + ' — ' + brandName + ' (' + products.length + ')';
            restoreFromLocalStorage();
            renderProducts();
            updateFilterCounts();
            showScreen(screenCounting);
        } catch (err) {
            alert(err.message);
        } finally {
            btnLoadBrand.disabled = false;
            btnLoadBrand.textContent = '{{ __("messages.reception.load_products") }}';
        }
    });

    // ─── Screen 3: Counting ───
    function getFilteredProducts() {
        let list = products;

        if (searchQuery) {
            const q = searchQuery.toLowerCase();
            list = list.filter(p =>
                (p.name && p.name.toLowerCase().includes(q)) ||
                (p.ean && p.ean.toLowerCase().includes(q)) ||
                (p.brand && p.brand.toLowerCase().includes(q))
            );
        }

        if (currentFilter === 'remaining') {
            list = list.filter(p => p.real === null);
        } else if (currentFilter === 'counted') {
            list = list.filter(p => p.real !== null);
        } else if (currentFilter === 'differences') {
            list = list.filter(p => p.real !== null && p.real !== p.theoretical);
        }

        return list;
    }

    function formatLastCounted(p) {
        if (!p.last_counted_at) {
            return '{{ __("messages.reception.last_counted") }}: {{ __("messages.reception.never_counted") }}';
        }
        const date = new Date(p.last_counted_at);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMins / 60);
        const diffDays = Math.floor(diffHours / 24);

        let ago;
        if (diffMins < 1) ago = '{{ __("messages.reception.just_now") }}';
        else if (diffMins < 60) ago = diffMins + ' min';
        else if (diffHours < 24) ago = diffHours + 'h';
        else ago = diffDays + ' {{ __("messages.reception.days_ago") }}';

        let text = '{{ __("messages.reception.last_counted") }}: ' + ago;
        if (p.counted_by_name) text += ' {{ __("messages.reception.by") }} ' + p.counted_by_name;
        return text;
    }

    function renderProducts() {
        const filtered = getFilteredProducts();
        productListEl.innerHTML = '';

        filtered.forEach(p => {
            const isCounted = p.real !== null;
            const hasDiff = isCounted && p.real !== p.theoretical;
            let rowClass = 'product-row';
            if (isCounted && !hasDiff) rowClass += ' counted-match';
            if (hasDiff) rowClass += ' counted-diff';

            const row = document.createElement('div');
            row.className = rowClass;
            row.id = 'product-row-' + p.id;
            row.innerHTML = `
                <div class="product-info">
                    <div class="name">${escHtml(p.name)}</div>
                    <div class="meta">${escHtml(p.brand || '')}${p.ean ? ' &middot; ' + escHtml(p.ean) : ''}</div>
                    <div class="last-count">${escHtml(formatLastCounted(p))}</div>
                </div>
                <div class="product-stock">
                    <div class="label">{{ __('messages.reception.theoretical') }}</div>
                    <div class="theo-value">${p.theoretical}</div>
                </div>
                <div class="product-input">
                    <div class="label">{{ __('messages.reception.real') }}</div>
                    <input type="number" inputmode="numeric" min="0" data-id="${p.id}"
                           value="${p.real !== null ? p.real : ''}" placeholder="-">
                </div>
            `;

            const input = row.querySelector('input');
            input.addEventListener('input', function() {
                const val = this.value.trim();
                p.real = val === '' ? null : parseInt(val);
                updateRowStyle(row, p);
                updateFilterCounts();
                saveToLocalStorage();
            });
            input.addEventListener('focus', function() { this.select(); });

            productListEl.appendChild(row);
        });

        updateFilterCounts();
    }

    function updateRowStyle(row, p) {
        row.classList.remove('counted-match', 'counted-diff');
        if (p.real !== null) {
            row.classList.add(p.real !== p.theoretical ? 'counted-diff' : 'counted-match');
        }
    }

    function updateFilterCounts() {
        document.getElementById('countAll').textContent = products.length;
        document.getElementById('countRemaining').textContent = products.filter(p => p.real === null).length;
        document.getElementById('countCounted').textContent = products.filter(p => p.real !== null).length;
        document.getElementById('countDiff').textContent = products.filter(p => p.real !== null && p.real !== p.theoretical).length;
    }

    // Filter tabs
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            renderProducts();
        });
    });

    // Search in counting screen
    countSearchInput.addEventListener('input', function() {
        searchQuery = this.value.trim();
        renderProducts();
    });

    // Scanner for counting screen
    document.getElementById('btnCountScan').addEventListener('click', function() {
        scannerCallback = 'counting';
        openScanner();
    });

    // ─── Scanner ───
    document.getElementById('btnCloseScanner').addEventListener('click', closeScanner);

    function openScanner() {
        scannerOverlay.classList.add('active');
        startQuagga();
    }

    function closeScanner() {
        stopQuagga();
        scannerOverlay.classList.remove('active');
    }

    function startQuagga() {
        if (scannerInitialized) return;
        Quagga.init({
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: document.getElementById('scannerTarget'),
                constraints: {
                    facingMode: "environment",
                    width: { ideal: 1920 },
                    height: { ideal: 1080 }
                }
            },
            decoder: {
                readers: ["ean_reader", "ean_8_reader", "upc_reader", "upc_e_reader", "code_128_reader", "code_39_reader"],
                multiple: false
            },
            locate: true,
            frequency: 10,
            locator: { patchSize: "large", halfSample: false }
        }, function(err) {
            if (err) { console.error(err); closeScanner(); return; }
            Quagga.start();
            scannerInitialized = true;
        });

        Quagga.onDetected(onBarcodeDetected);
    }

    function stopQuagga() {
        if (scannerInitialized) {
            try { Quagga.offDetected(onBarcodeDetected); Quagga.stop(); } catch(e) {}
            scannerInitialized = false;
        }
    }

    let lastDetectedCode = '';
    let lastDetectedTime = 0;

    function onBarcodeDetected(result) {
        const code = result.codeResult.code;
        const now = Date.now();
        if (code === lastDetectedCode && (now - lastDetectedTime) < 2000) return;
        lastDetectedCode = code;
        lastDetectedTime = now;

        if (navigator.vibrate) navigator.vibrate(100);
        closeScanner();

        if (scannerCallback === 'manual') {
            // Search for the scanned barcode and add to selection
            manualSearchInput.value = code;
            searchProducts(code);
        } else if (scannerCallback === 'counting') {
            // Find product in current list
            const product = products.find(p => p.ean && (
                p.ean === code ||
                p.ean.replace(/\s/g, '') === code ||
                p.ean.includes(code) ||
                code.includes(p.ean.replace(/\s/g, ''))
            ));

            if (product) {
                currentFilter = 'all';
                searchQuery = '';
                countSearchInput.value = '';
                document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
                document.querySelector('.filter-tab[data-filter="all"]').classList.add('active');
                renderProducts();

                const row = document.getElementById('product-row-' + product.id);
                if (row) {
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    row.classList.add('highlight');
                    setTimeout(() => row.classList.remove('highlight'), 1500);
                    const input = row.querySelector('input');
                    if (input) setTimeout(() => input.focus(), 400);
                }
            } else {
                countSearchInput.value = code;
                searchQuery = code;
                renderProducts();
            }
        }
    }

    // Handle visibility
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'hidden' && scannerInitialized) {
            stopQuagga();
            scannerOverlay.classList.remove('active');
        }
    });

    // ─── Screen 4: Review ───
    btnReview.addEventListener('click', function() {
        const counted = products.filter(p => p.real !== null);
        const diffs = counted.filter(p => p.real !== p.theoretical);

        if (counted.length === 0) {
            alert('{{ __("messages.reception.no_products_counted") }}');
            return;
        }

        document.getElementById('reviewCounted').textContent = counted.length;
        document.getElementById('reviewDiffs').textContent = diffs.length;

        const wrapper = document.getElementById('diffTableWrapper');

        if (diffs.length === 0) {
            wrapper.innerHTML = '<div class="no-diff-message">{{ __("messages.reception.no_differences") }}</div>';
        } else {
            let html = '<table class="diff-table"><thead><tr>' +
                '<th>{{ __("messages.reception.product_name") }}</th>' +
                '<th>{{ __("messages.reception.theo") }}</th>' +
                '<th>{{ __("messages.reception.real") }}</th>' +
                '<th>{{ __("messages.reception.diff") }}</th>' +
                '</tr></thead><tbody>';

            diffs.forEach(p => {
                const diff = p.real - p.theoretical;
                const cls = diff > 0 ? 'diff-positive' : 'diff-negative';
                const sign = diff > 0 ? '+' : '';
                html += `<tr>
                    <td>${escHtml(p.name)}</td>
                    <td>${p.theoretical}</td>
                    <td>${p.real}</td>
                    <td class="${cls}">${sign}${diff}</td>
                </tr>`;
            });

            html += '</tbody></table>';
            wrapper.innerHTML = html;
        }

        showScreen(screenReview);
    });

    btnBackCount.addEventListener('click', function() {
        goBack();
    });

    btnApply.addEventListener('click', async function() {
        if (!confirm('{{ __("messages.reception.confirm_apply") }}')) return;

        const counted = products.filter(p => p.real !== null);
        const diffs = counted
            .filter(p => p.real !== p.theoretical)
            .map(p => ({ product_id: p.id, difference: p.real - p.theoretical }));

        const countedIds = counted.map(p => p.id);

        btnApply.disabled = true;
        btnApply.textContent = '{{ __("messages.reception.applying") }}';

        try {
            const res = await fetch('{{ route("reception.quick-inventory.apply") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrfToken },
                body: JSON.stringify({
                    store_id: storeId,
                    adjustments: diffs,
                    counted_product_ids: countedIds
                })
            });
            const data = await res.json();

            if (data.success) {
                clearLocalStorage();
                window.location.href = '{{ route("reception.home") }}';
            } else {
                alert(data.message || '{{ __("messages.reception.inventory_error") }}');
            }
        } catch (err) {
            alert(err.message);
        } finally {
            btnApply.disabled = false;
            btnApply.textContent = '{{ __("messages.reception.validate_and_apply") }}';
        }
    });

    // ─── LocalStorage crash-recovery ───
    const STORAGE_KEY = 'kabas_quick_inventory';

    function saveToLocalStorage() {
        const data = {
            storeId: storeId,
            savedAt: new Date().toISOString(),
            counts: {}
        };
        products.forEach(p => {
            if (p.real !== null) data.counts[p.id] = p.real;
        });
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(data)); } catch(e) {}
    }

    function restoreFromLocalStorage() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) return;
            const saved = JSON.parse(raw);
            if (saved.storeId != storeId) return;
            // Only restore if recent (< 2 hours)
            const age = Date.now() - new Date(saved.savedAt).getTime();
            if (age > 2 * 60 * 60 * 1000) return;
            if (!saved.counts || Object.keys(saved.counts).length === 0) return;

            products.forEach(p => {
                if (saved.counts[p.id] !== undefined) {
                    p.real = saved.counts[p.id];
                }
            });
        } catch(e) {}
    }

    function clearLocalStorage() {
        try { localStorage.removeItem(STORAGE_KEY); } catch(e) {}
    }

    // ─── Helpers ───
    function escHtml(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }
})();
</script>
@endsection
