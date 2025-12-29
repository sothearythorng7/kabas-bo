@extends('reception.layouts.app')

@section('title', 'Login')

@section('styles')
<style>
    .login-container {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    }

    .login-logo-container {
        background-color: #ffffff;
        padding: 20px 30px;
        border-radius: 16px;
        margin-bottom: 32px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .login-logo {
        width: 120px;
        height: auto;
        display: block;
    }

    .login-card {
        background: var(--white);
        border-radius: 24px;
        padding: 32px;
        width: 100%;
        max-width: 320px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    }

    .login-title {
        text-align: center;
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 24px;
        color: var(--text);
    }

    .pin-display {
        display: flex;
        justify-content: center;
        gap: 12px;
        margin-bottom: 24px;
    }

    .pin-dot {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: var(--border);
        transition: background 0.2s;
    }

    .pin-dot.filled {
        background: var(--primary);
    }

    .pin-pad {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
    }

    .pin-key {
        height: 64px;
        border: none;
        border-radius: 16px;
        background: var(--bg);
        font-size: 28px;
        font-weight: 600;
        color: var(--text);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.1s, transform 0.1s;
        -webkit-tap-highlight-color: transparent;
    }

    .pin-key:active {
        background: var(--border);
        transform: scale(0.95);
    }

    .pin-key.action {
        background: transparent;
        font-size: 14px;
        color: var(--text-light);
    }

    .pin-key.action:active {
        background: var(--bg);
    }

    .pin-key.submit {
        background: var(--success);
        color: var(--white);
    }

    .pin-key svg {
        width: 24px;
        height: 24px;
    }

    .login-error {
        background: #fee2e2;
        color: #991b1b;
        padding: 12px 16px;
        border-radius: 12px;
        margin-bottom: 16px;
        text-align: center;
        font-size: 14px;
    }

    .login-footer {
        position: absolute;
        bottom: 20px;
        left: 0;
        right: 0;
        text-align: center;
        font-size: 12px;
        color: rgba(255, 255, 255, 0.7);
    }

    .login-footer a {
        color: #ffffff;
        text-decoration: none;
        font-weight: 500;
    }

    .login-footer a:hover {
        text-decoration: underline;
    }
</style>
@endsection

@section('content')
<div class="login-container">
    <div class="login-logo-container">
        <img src="/images/kabas_logo.png" alt="Kabas" class="login-logo">
    </div>

    <div class="login-card">
        <h1 class="login-title">Enter your PIN</h1>

        @if(session('error'))
            <div class="login-error">{{ session('error') }}</div>
        @endif

        <form id="pinForm" action="{{ route('reception.auth') }}" method="POST">
            @csrf
            <input type="hidden" name="pin" id="pinInput">

            <div class="pin-display">
                <div class="pin-dot" data-index="0"></div>
                <div class="pin-dot" data-index="1"></div>
                <div class="pin-dot" data-index="2"></div>
                <div class="pin-dot" data-index="3"></div>
                <div class="pin-dot" data-index="4"></div>
                <div class="pin-dot" data-index="5"></div>
            </div>

            <div class="pin-pad">
                <button type="button" class="pin-key" data-key="1">1</button>
                <button type="button" class="pin-key" data-key="2">2</button>
                <button type="button" class="pin-key" data-key="3">3</button>
                <button type="button" class="pin-key" data-key="4">4</button>
                <button type="button" class="pin-key" data-key="5">5</button>
                <button type="button" class="pin-key" data-key="6">6</button>
                <button type="button" class="pin-key" data-key="7">7</button>
                <button type="button" class="pin-key" data-key="8">8</button>
                <button type="button" class="pin-key" data-key="9">9</button>
                <button type="button" class="pin-key action" data-key="clear">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414 6.414a2 2 0 001.414.586H19a2 2 0 002-2V7a2 2 0 00-2-2h-8.172a2 2 0 00-1.414.586L3 12z" />
                    </svg>
                </button>
                <button type="button" class="pin-key" data-key="0">0</button>
                <button type="button" class="pin-key submit" data-key="submit">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </button>
            </div>
        </form>
    </div>

    <div class="login-footer">
        Powered by Aurelien Dippe - <a href="https://t.me/adsofts" target="_blank">@adsofts</a>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const pinInput = document.getElementById('pinInput');
    const pinForm = document.getElementById('pinForm');
    const dots = document.querySelectorAll('.pin-dot');
    let pin = '';

    function updateDots() {
        dots.forEach((dot, index) => {
            dot.classList.toggle('filled', index < pin.length);
        });
    }

    document.querySelectorAll('.pin-key').forEach(key => {
        key.addEventListener('click', () => {
            const value = key.dataset.key;

            if (value === 'clear') {
                pin = pin.slice(0, -1);
                updateDots();
            } else if (value === 'submit') {
                if (pin.length === 6) {
                    pinInput.value = pin;
                    pinForm.submit();
                }
            } else if (pin.length < 6) {
                pin += value;
                updateDots();

                // Auto-submit when 6 digits
                if (pin.length === 6) {
                    setTimeout(() => {
                        pinInput.value = pin;
                        pinForm.submit();
                    }, 200);
                }
            }
        });
    });
</script>
@endsection
