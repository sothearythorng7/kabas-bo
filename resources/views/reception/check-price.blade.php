@extends('reception.layouts.app')

@section('title', 'Check Price')

@section('styles')
<style>
    .scanner-container {
        position: relative;
        width: 100%;
        max-width: 400px;
        height: 210px;
        margin: 0 auto;
        background: #000;
        border-radius: 16px;
        overflow: hidden;
    }

    .scanner-container video,
    .scanner-container canvas {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        min-width: 100%;
        min-height: 100%;
        width: auto;
        height: auto;
        object-fit: cover;
    }

    .scanner-overlay {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 80%;
        height: 60px;
        border: 3px solid var(--success);
        border-radius: 8px;
        box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
        z-index: 10;
    }

    .scanner-line {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: var(--success);
        animation: scan 2s linear infinite;
    }

    @keyframes scan {
        0% { top: 0; }
        50% { top: 100%; }
        100% { top: 0; }
    }

    .store-selector {
        margin-bottom: 16px;
    }

    .store-selector select {
        width: 100%;
        height: 48px;
        padding: 0 16px;
        border: 2px solid var(--border);
        border-radius: 12px;
        font-size: 16px;
        background: var(--white);
        outline: none;
    }

    .store-selector select:focus {
        border-color: var(--primary);
    }

    .manual-input {
        display: flex;
        gap: 8px;
        margin-top: 16px;
    }

    .manual-input input {
        flex: 1;
        height: 48px;
        padding: 0 16px;
        border: 2px solid var(--border);
        border-radius: 12px;
        font-size: 16px;
        outline: none;
    }

    .manual-input input:focus {
        border-color: var(--primary);
    }

    .manual-input button {
        height: 48px;
        padding: 0 20px;
        background: var(--primary);
        color: var(--white);
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
    }

    .result-card {
        background: var(--white);
        border-radius: 16px;
        padding: 20px;
        margin-top: 20px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        display: none;
    }

    .result-card.show {
        display: block;
        animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .result-image {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border-radius: 12px;
        background: var(--bg);
        margin: 0 auto 16px;
        display: block;
    }

    .result-name {
        font-size: 18px;
        font-weight: 600;
        text-align: center;
        margin-bottom: 4px;
    }

    .result-brand {
        font-size: 14px;
        color: var(--text-light);
        text-align: center;
        margin-bottom: 16px;
    }

    .result-details {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .result-item {
        background: var(--bg);
        padding: 12px;
        border-radius: 12px;
        text-align: center;
    }

    .result-item-label {
        font-size: 12px;
        color: var(--text-light);
        margin-bottom: 4px;
    }

    .result-item-value {
        font-size: 20px;
        font-weight: 700;
    }

    .result-item-value.price {
        color: var(--primary);
    }

    .result-item-value.in-stock {
        color: var(--success);
    }

    .result-item-value.out-of-stock {
        color: var(--danger);
    }

    .result-meta {
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid var(--border);
        font-size: 13px;
        color: var(--text-light);
    }

    .result-meta-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 4px;
    }

    .not-found {
        background: #fee2e2;
        color: #991b1b;
        padding: 20px;
        border-radius: 16px;
        text-align: center;
        margin-top: 20px;
        display: none;
    }

    .not-found.show {
        display: block;
        animation: slideUp 0.3s ease;
    }

    .not-found-icon {
        font-size: 48px;
        margin-bottom: 8px;
    }

    .camera-error {
        background: var(--bg);
        padding: 40px 20px;
        border-radius: 16px;
        text-align: center;
        color: var(--text-light);
    }

    .camera-error-icon {
        font-size: 48px;
        margin-bottom: 12px;
    }

    .scan-again-btn {
        margin-top: 16px;
        width: 100%;
    }
</style>
@endsection

@section('content')
<div class="header">
    <a href="{{ route('reception.home') }}" class="header-back">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Back
    </a>
    <div class="header-title">Check Price</div>
    <div style="width: 60px;"></div>
</div>

<div class="container">
    <div class="store-selector">
        <select id="storeSelect">
            @foreach($stores as $store)
                <option value="{{ $store->id }}" {{ $store->id == $userStoreId ? 'selected' : '' }}>
                    {{ $store->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="scanner-container" id="scannerContainer">
        <video id="video" playsinline></video>
        <div class="scanner-overlay">
            <div class="scanner-line"></div>
        </div>
    </div>

    <div class="camera-error" id="cameraError" style="display: none;">
        <div class="camera-error-icon">📷</div>
        <p>Camera access denied or not available</p>
        <p style="font-size: 14px; margin-top: 8px;">Use manual input below</p>
    </div>

    <div class="manual-input">
        <input type="text" id="barcodeInput" placeholder="Enter barcode manually" inputmode="numeric">
        <button id="searchBtn">Search</button>
    </div>

    <div class="result-card" id="resultCard">
        <img id="resultImage" class="result-image" src="" alt="">
        <div id="resultName" class="result-name"></div>
        <div id="resultBrand" class="result-brand"></div>

        <div class="result-details">
            <div class="result-item">
                <div class="result-item-label">Price</div>
                <div id="resultPrice" class="result-item-value price"></div>
            </div>
            <div class="result-item">
                <div class="result-item-label">Stock</div>
                <div id="resultStock" class="result-item-value"></div>
            </div>
        </div>

        <div class="result-meta">
            <div class="result-meta-row">
                <span>Store:</span>
                <span id="resultStore"></span>
            </div>
            <div class="result-meta-row">
                <span>Barcode:</span>
                <span id="resultBarcode"></span>
            </div>
            <div class="result-meta-row" id="resultColorRow" style="display: none;">
                <span>Color:</span>
                <span id="resultColor"></span>
            </div>
            <div class="result-meta-row" id="resultSizeRow" style="display: none;">
                <span>Size:</span>
                <span id="resultSize"></span>
            </div>
        </div>

        <button class="btn btn-primary scan-again-btn" id="scanAgainBtn">Scan Another</button>
    </div>

    <div class="not-found" id="notFound">
        <div class="not-found-icon">🔍</div>
        <p><strong>Product not found</strong></p>
        <p style="font-size: 14px; margin-top: 4px;">Barcode: <span id="notFoundBarcode"></span></p>
        <button class="btn btn-primary scan-again-btn" id="scanAgainBtn2">Try Again</button>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://unpkg.com/@ericblade/quagga2@1.8.4/dist/quagga.min.js"></script>
<script>
    const video = document.getElementById('video');
    const scannerContainer = document.getElementById('scannerContainer');
    const cameraError = document.getElementById('cameraError');
    const barcodeInput = document.getElementById('barcodeInput');
    const searchBtn = document.getElementById('searchBtn');
    const resultCard = document.getElementById('resultCard');
    const notFound = document.getElementById('notFound');
    const storeSelect = document.getElementById('storeSelect');

    let isScanning = true;
    let lastScannedCode = '';
    let lastScanTime = 0;
    let scannerInitialized = false;
    let firstLoad = true;

    // Stop scanner
    function stopScanner() {
        if (scannerInitialized) {
            try {
                Quagga.stop();
                scannerInitialized = false;
            } catch (e) {
                console.log('Stop scanner error:', e);
            }
        }
    }

    // Restart scanner (for when app resumes)
    function restartScanner() {
        stopScanner();
        // Reset UI
        scannerContainer.style.display = 'block';
        cameraError.style.display = 'none';
        setTimeout(() => {
            initScanner();
        }, 500);
    }

    // Handle visibility change (app goes to background/foreground)
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible' && !firstLoad) {
            // App came back to foreground - restart scanner
            restartScanner();
        } else if (document.visibilityState === 'hidden') {
            // App went to background - stop scanner
            stopScanner();
        }
    });

    // Handle page show (for iOS PWA - bfcache restore)
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            // Page was restored from bfcache
            restartScanner();
        }
    });

    // Initialize Quagga barcode scanner
    function initScanner() {
        Quagga.init({
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: scannerContainer,
                constraints: {
                    facingMode: "environment",
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            },
            decoder: {
                readers: [
                    "ean_reader",
                    "ean_8_reader",
                    "upc_reader",
                    "upc_e_reader",
                    "code_128_reader",
                    "code_39_reader"
                ]
            },
            locate: true,
            locator: {
                patchSize: "medium",
                halfSample: true
            }
        }, function(err) {
            if (err) {
                console.error('Quagga init error:', err);
                scannerContainer.style.display = 'none';
                cameraError.style.display = 'block';
                return;
            }
            Quagga.start();
            scannerInitialized = true;
            firstLoad = false;
        });

        Quagga.onDetected(function(result) {
            if (!isScanning) return;

            const code = result.codeResult.code;
            const now = Date.now();

            // Debounce: ignore same code within 2 seconds
            if (code === lastScannedCode && (now - lastScanTime) < 2000) {
                return;
            }

            lastScannedCode = code;
            lastScanTime = now;

            // Vibrate feedback
            if (navigator.vibrate) {
                navigator.vibrate(100);
            }

            lookupProduct(code);
        });
    }

    // Lookup product via API
    async function lookupProduct(barcode) {
        isScanning = false;
        barcodeInput.value = barcode;

        // Hide previous results
        resultCard.classList.remove('show');
        notFound.classList.remove('show');

        try {
            const requestBody = {
                barcode: barcode,
                store_id: storeSelect.value
            };

            const response = await fetch('{{ route("reception.check-price.lookup") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.csrfToken
                },
                body: JSON.stringify(requestBody)
            });

            // Check if response is OK
            if (!response.ok) {
                const errorText = await response.text();
                alert('Server error: ' + response.status + '\n' + errorText.substring(0, 200));
                showNotFound(barcode);
                return;
            }

            const data = await response.json();

            if (data.found) {
                showResult(data);
            } else {
                showNotFound(barcode);
            }
        } catch (error) {
            alert('Fetch error: ' + error.message);
            showNotFound(barcode);
        }
    }

    // Show product result
    function showResult(data) {
        const product = data.product;

        document.getElementById('resultImage').src = product.image || '/images/placeholder.png';
        document.getElementById('resultName').textContent = product.name;
        document.getElementById('resultBrand').textContent = product.brand || '';
        document.getElementById('resultPrice').textContent = '$' + parseFloat(product.price).toFixed(2);

        const stockEl = document.getElementById('resultStock');
        stockEl.textContent = product.stock;
        stockEl.className = 'result-item-value ' + (product.stock > 0 ? 'in-stock' : 'out-of-stock');

        document.getElementById('resultStore').textContent = data.store_name;
        document.getElementById('resultBarcode').textContent = product.ean;

        // Show color if available
        const colorRow = document.getElementById('resultColorRow');
        if (product.color) {
            document.getElementById('resultColor').textContent = product.color;
            colorRow.style.display = 'flex';
        } else {
            colorRow.style.display = 'none';
        }

        // Show size if available
        const sizeRow = document.getElementById('resultSizeRow');
        if (product.size) {
            document.getElementById('resultSize').textContent = product.size;
            sizeRow.style.display = 'flex';
        } else {
            sizeRow.style.display = 'none';
        }

        resultCard.classList.add('show');
    }

    // Show not found message
    function showNotFound(barcode) {
        document.getElementById('notFoundBarcode').textContent = barcode;
        notFound.classList.add('show');
    }

    // Reset scanner for new scan
    function resetScanner() {
        isScanning = true;
        lastScannedCode = '';
        barcodeInput.value = '';
        resultCard.classList.remove('show');
        notFound.classList.remove('show');
    }

    // Event listeners
    searchBtn.addEventListener('click', function() {
        const barcode = barcodeInput.value.trim();
        if (barcode) {
            lookupProduct(barcode);
        }
    });

    barcodeInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const barcode = barcodeInput.value.trim();
            if (barcode) {
                lookupProduct(barcode);
            }
        }
    });

    document.getElementById('scanAgainBtn').addEventListener('click', resetScanner);
    document.getElementById('scanAgainBtn2').addEventListener('click', resetScanner);

    // Store change - reset result
    storeSelect.addEventListener('change', function() {
        if (resultCard.classList.contains('show') && lastScannedCode) {
            lookupProduct(lastScannedCode);
        }
    });

    // Initialize scanner on page load
    initScanner();
</script>
@endsection
