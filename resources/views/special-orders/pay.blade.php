<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - {{ $order->order_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f6fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .pay-container { max-width: 520px; margin: 40px auto; padding: 0 16px; }
        .pay-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); overflow: hidden; }
        .pay-header { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: #fff; padding: 28px 24px; text-align: center; }
        .pay-header h1 { font-size: 18px; margin: 0 0 4px; font-weight: 600; }
        .pay-header .order-num { font-size: 14px; opacity: 0.7; }
        .pay-body { padding: 24px; }
        .pay-total { font-size: 36px; font-weight: 700; text-align: center; margin: 16px 0; color: #1a1a2e; }
        .pay-total small { font-size: 18px; font-weight: 400; color: #666; }
        .item-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
        .item-row:last-child { border-bottom: none; }
        .btn-pay { background: #0f5132; color: #fff; border: none; padding: 14px; font-size: 16px; font-weight: 600; border-radius: 12px; width: 100%; cursor: pointer; }
        .btn-pay:hover { background: #0a3622; color: #fff; }
        .btn-pay:disabled { background: #ccc; cursor: not-allowed; }
        .status-msg { text-align: center; padding: 12px; border-radius: 8px; margin-top: 16px; font-size: 14px; }
        .powered { text-align: center; padding: 16px; font-size: 12px; color: #999; }
    </style>
</head>
<body>

<div class="pay-container">
    <div class="pay-card">
        <div class="pay-header">
            <h1>Kabas Concept Store</h1>
            <div class="order-num">{{ $order->order_number }}</div>
        </div>

        <div class="pay-body">
            <div class="pay-total">
                ${{ number_format($order->total, 2) }}
                <small>USD</small>
            </div>

            <div class="mb-3">
                @foreach($order->items as $item)
                    <div class="item-row">
                        <span>{{ $item->product_name }} x{{ $item->quantity }}</span>
                        <span>${{ number_format($item->subtotal, 2) }}</span>
                    </div>
                @endforeach
            </div>

            <button class="btn-pay" id="btnPay" onclick="startPayment()">
                Pay with Card
            </button>

            <div id="statusMsg" class="status-msg" style="display:none;"></div>
        </div>
    </div>
    <div class="powered">Secured by ABA PayWay</div>
</div>

{{-- Hidden form required by AbaPayway.checkout() --}}
<div id="aba_main_modal" class="aba-modal" style="display:none;">
    <div class="aba-modal-content">
        <form method="POST" target="aba_webservice" action="{{ $purchaseUrl }}" id="aba_merchant_request"></form>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<script src="{{ config('payway.checkout_js') }}"></script>

<script>
var _state = { tranId: '{{ $formData['tran_id'] }}', isRedirecting: false, pollingInterval: null };

function checkout_callback(response) {
    console.log('PayWay checkout_callback:', response);
    if (!_state.isRedirecting) checkPaymentStatus();
}

function close_checkout_popup() {
    console.log('PayWay popup closed');
    if (!_state.isRedirecting) checkPaymentStatus();
}

function startPayment() {
    var btn = document.getElementById('btnPay');
    btn.disabled = true;
    btn.textContent = 'Processing...';

    var form = document.getElementById('aba_merchant_request');
    form.action = '{{ $purchaseUrl }}';
    form.innerHTML = '';

    var formData = @json($formData);
    for (var key in formData) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = formData[key];
        form.appendChild(input);
    }

    AbaPayway.checkout();
    startPolling();
}

function startPolling() {
    _state.pollingInterval = setInterval(function() {
        checkPaymentStatus();
    }, 4000);

    setTimeout(function() {
        clearInterval(_state.pollingInterval);
    }, 600000);
}

function checkPaymentStatus(attempt) {
    attempt = attempt || 1;

    fetch('{{ route("special-orders.check-status") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ tran_id: _state.tranId })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.status === 'paid') {
            _state.isRedirecting = true;
            clearInterval(_state.pollingInterval);

            var msg = document.getElementById('statusMsg');
            msg.style.display = 'block';
            msg.className = 'status-msg alert alert-success';
            msg.textContent = 'Payment successful! Redirecting...';

            document.getElementById('btnPay').style.display = 'none';

            setTimeout(function() {
                window.location.href = '{{ route("special-orders.pay.success", ["order" => $order->id, "token" => $token]) }}';
            }, 1500);
        } else if (attempt < 5) {
            setTimeout(function() { checkPaymentStatus(attempt + 1); }, 3000);
        } else {
            var btn = document.getElementById('btnPay');
            btn.disabled = false;
            btn.textContent = 'Pay with Card';
        }
    })
    .catch(function() {
        var btn = document.getElementById('btnPay');
        btn.disabled = false;
        btn.textContent = 'Pay with Card';
    });
}
</script>
</body>
</html>
