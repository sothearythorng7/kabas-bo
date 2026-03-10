@extends('emails.orders.layout')

@section('title', 'Payment Link - ' . $order->order_number)

@section('content')
{{-- Icon + Title --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td align="center" style="padding-bottom: 25px;">
            <div style="width: 60px; height: 60px; border-radius: 50%; background-color: #e3f2fd; display: inline-block; line-height: 60px; text-align: center;">
                <span style="font-size: 30px; color: #1976D2;">&#128179;</span>
            </div>
            <h1 style="margin: 15px 0 5px; font-size: 24px; color: #212529;">Payment Required</h1>
            <p style="margin: 0; color: #6c757d; font-size: 14px;">Please complete your payment to confirm your order</p>
        </td>
    </tr>
</table>

{{-- Order info --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px; background-color: #f8f9fa; border-radius: 6px;">
    <tr>
        <td style="padding: 15px 20px;">
            <p style="margin: 0 0 5px; font-size: 14px; color: #6c757d;">Order Number</p>
            <p style="margin: 0; font-size: 16px; font-weight: 700; color: #212529;">{{ $order->order_number }}</p>
        </td>
        <td align="right" style="padding: 15px 20px;">
            <p style="margin: 0 0 5px; font-size: 14px; color: #6c757d;">Total</p>
            <p style="margin: 0; font-size: 16px; font-weight: 700; color: #212529;">${{ number_format($order->total, 2) }}</p>
        </td>
    </tr>
</table>

{{-- Items summary --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px;">
    <tr>
        <td>
            <h2 style="margin: 0 0 15px; font-size: 16px; color: #212529;">Order Items</h2>
        </td>
    </tr>
    @foreach($order->items as $item)
    <tr>
        <td style="padding: 8px 0; border-bottom: 1px solid #e9ecef;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="font-size: 14px; color: #212529;">
                        {{ $item->product_name }} <span style="color: #6c757d;">x{{ $item->quantity }}</span>
                    </td>
                    <td align="right" style="font-size: 14px; font-weight: 600; color: #212529;">
                        ${{ number_format($item->subtotal, 2) }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    @endforeach
    <tr>
        <td style="padding: 12px 0 0;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="font-size: 16px; font-weight: 700; color: #212529;">Total</td>
                    <td align="right" style="font-size: 16px; font-weight: 700; color: #212529;">
                        ${{ number_format($order->total, 2) }}
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- Payment button --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px;">
    <tr>
        <td align="center" style="padding: 20px 0;">
            <a href="{{ $paymentLink }}" style="display: inline-block; padding: 14px 40px; background-color: #1976D2; color: #ffffff; text-decoration: none; border-radius: 6px; font-size: 16px; font-weight: 600;">
                Pay Now
            </a>
        </td>
    </tr>
    <tr>
        <td align="center">
            <p style="margin: 0; font-size: 12px; color: #6c757d;">
                If the button doesn't work, copy and paste this link in your browser:<br>
                <a href="{{ $paymentLink }}" style="color: #1976D2; word-break: break-all;">{{ $paymentLink }}</a>
            </p>
        </td>
    </tr>
</table>
@endsection
