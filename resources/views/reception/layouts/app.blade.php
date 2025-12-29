<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1a1a2e">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Kabas">
    <meta name="application-name" content="Kabas">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Kabas</title>

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/images/icons/icon-192x192.png">

    <!-- Styles -->
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --primary: #1a1a2e;
            --primary-light: #16213e;
            --accent: #0f3460;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --text: #1f2937;
            --text-light: #6b7280;
            --bg: #f3f4f6;
            --white: #ffffff;
            --border: #e5e7eb;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            font-size: 16px;
            line-height: 1.5;
            color: var(--text);
            background: var(--bg);
            min-height: 100vh;
            padding-bottom: env(safe-area-inset-bottom);
        }

        .header {
            background: var(--primary);
            color: var(--white);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            padding-top: calc(16px + env(safe-area-inset-top));
        }

        .header-title {
            font-size: 18px;
            font-weight: 600;
        }

        .header-back {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--white);
            text-decoration: none;
            font-size: 16px;
        }

        .header-back svg {
            width: 24px;
            height: 24px;
        }

        .container {
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
        }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            min-height: 56px;
            padding: 16px 24px;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.1s, opacity 0.2s;
        }

        .btn:active {
            transform: scale(0.98);
        }

        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }

        .btn-success {
            background: var(--success);
            color: var(--white);
        }

        .btn-danger {
            background: var(--danger);
            color: var(--white);
        }

        .btn-outline {
            background: var(--white);
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-secondary {
            background: var(--accent);
            color: var(--white);
        }

        .btn-lg {
            min-height: 80px;
            font-size: 20px;
            border-radius: 16px;
        }

        .btn-icon {
            font-size: 28px;
        }

        .card {
            background: var(--white);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .card-subtitle {
            font-size: 14px;
            color: var(--text-light);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 24px;
            height: 24px;
            padding: 0 8px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
        }

        .badge-primary {
            background: var(--primary);
            color: var(--white);
        }

        .badge-success {
            background: var(--success);
            color: var(--white);
        }

        .badge-warning {
            background: var(--warning);
            color: var(--white);
        }

        .input {
            width: 100%;
            height: 56px;
            padding: 0 16px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 18px;
            outline: none;
            transition: border-color 0.2s;
        }

        .input:focus {
            border-color: var(--primary);
        }

        .input-number {
            text-align: center;
            font-weight: 600;
            font-size: 24px;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .quantity-btn {
            width: 48px;
            height: 48px;
            border: 2px solid var(--border);
            border-radius: 12px;
            background: var(--white);
            font-size: 24px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .quantity-btn:active {
            background: var(--bg);
        }

        .quantity-input {
            width: 80px;
            height: 48px;
            text-align: center;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 20px;
            font-weight: 600;
        }

        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }

        .mt-4 { margin-top: 16px; }
        .mb-4 { margin-bottom: 16px; }
        .gap-4 { gap: 16px; }

        .flex { display: flex; }
        .flex-col { flex-direction: column; }
        .items-center { align-items: center; }
        .justify-between { justify-content: space-between; }

        .text-center { text-align: center; }
        .text-sm { font-size: 14px; }
        .text-lg { font-size: 18px; }
        .text-muted { color: var(--text-light); }
        .font-bold { font-weight: 600; }

        .sticky-bottom {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 16px 20px;
            padding-bottom: calc(16px + env(safe-area-inset-bottom));
            background: var(--white);
            border-top: 1px solid var(--border);
            z-index: 100;
        }

        .has-sticky-bottom {
            padding-bottom: 100px;
        }

        .product-item {
            display: flex;
            gap: 12px;
            padding: 12px;
            background: var(--white);
            border-radius: 12px;
            margin-bottom: 8px;
        }

        .product-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            background: var(--bg);
        }

        .product-info {
            flex: 1;
            min-width: 0;
        }

        .product-name {
            font-weight: 600;
            font-size: 15px;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .product-meta {
            font-size: 13px;
            color: var(--text-light);
        }

        .search-box {
            position: relative;
            margin-bottom: 16px;
        }

        .search-box input {
            width: 100%;
            height: 48px;
            padding: 0 16px 0 48px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 16px;
            outline: none;
        }

        .search-box svg {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            color: var(--text-light);
        }

        .empty-state {
            text-align: center;
            padding: 48px 20px;
            color: var(--text-light);
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid var(--border);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Hide number input spinners */
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type="number"] {
            -moz-appearance: textfield;
        }
    </style>

    @yield('styles')
</head>
<body>
    @yield('content')

    <script>
        // Register Service Worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(err => {
                console.log('SW registration failed:', err);
            });
        }

        // CSRF token for AJAX
        window.csrfToken = '{{ csrf_token() }}';
    </script>

    @yield('scripts')
</body>
</html>
