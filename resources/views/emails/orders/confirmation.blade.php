@extends('emails.orders.layout')

@section('title', 'Order Confirmed - ' . $order->order_number)

@section('content')
{{-- Icon + Title --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td align="center" style="padding-bottom: 25px;">
            <div style="width: 60px; height: 60px; border-radius: 50%; background-color: #e8f5e9; display: inline-block; line-height: 60px; text-align: center;">
                <span style="font-size: 30px; color: #2D7A3E;">&#10003;</span>
            </div>
            <h1 style="margin: 15px 0 5px; font-size: 24px; color: #212529;">Order Confirmed!</h1>
            <p style="margin: 0; color: #6c757d; font-size: 14px;">Thank you for your order</p>
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
            <p style="margin: 0 0 5px; font-size: 14px; color: #6c757d;">Order Date</p>
            <p style="margin: 0; font-size: 16px; color: #212529;">{{ $order->created_at->format('M d, Y') }}</p>
        </td>
    </tr>
</table>

{{-- Items table --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px;">
    <tr>
        <td colspan="4" style="padding-bottom: 10px; border-bottom: 2px solid #2D7A3E;">
            <strong style="font-size: 16px; color: #212529;">Items Ordered</strong>
        </td>
    </tr>
    @foreach($order->items as $item)
    <tr>
        <td style="padding: 12px 0; border-bottom: 1px solid #e9ecef; width: 50px; vertical-align: top;">
            @if($item->product_image)
                <img src="{{ $imageBaseUrl }}/storage/{{ $item->product_image }}" alt="" style="width: 45px; height: 45px; object-fit: cover; border-radius: 4px;">
            @else
                <div style="width: 45px; height: 45px; background-color: #e9ecef; border-radius: 4px;"></div>
            @endif
        </td>
        <td style="padding: 12px 10px; border-bottom: 1px solid #e9ecef; vertical-align: top;">
            <p style="margin: 0; font-size: 14px; font-weight: 500; color: #212529;">{{ $item->product_name }}</p>
            <p style="margin: 3px 0 0; font-size: 12px; color: #6c757d;">Qty: {{ $item->quantity }}</p>
        </td>
        <td align="right" style="padding: 12px 0; border-bottom: 1px solid #e9ecef; vertical-align: top; white-space: nowrap;">
            <p style="margin: 0; font-size: 13px; color: #6c757d;">${{ number_format($item->unit_price, 2) }} x {{ $item->quantity }}</p>
            <p style="margin: 3px 0 0; font-size: 14px; font-weight: 500; color: #212529;">${{ number_format($item->subtotal, 2) }}</p>
        </td>
    </tr>
    @endforeach
</table>

{{-- Totals --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px;">
    <tr>
        <td style="padding: 5px 0; font-size: 14px; color: #6c757d;">Subtotal</td>
        <td align="right" style="padding: 5px 0; font-size: 14px; color: #212529;">${{ number_format($order->subtotal, 2) }}</td>
    </tr>
    @if($order->shipping_cost > 0)
    <tr>
        <td style="padding: 5px 0; font-size: 14px; color: #6c757d;">Shipping</td>
        <td align="right" style="padding: 5px 0; font-size: 14px; color: #212529;">${{ number_format($order->shipping_cost, 2) }}</td>
    </tr>
    @else
    <tr>
        <td style="padding: 5px 0; font-size: 14px; color: #6c757d;">Shipping</td>
        <td align="right" style="padding: 5px 0; font-size: 14px; color: #2D7A3E;">Free</td>
    </tr>
    @endif
    @if($order->discount > 0)
    <tr>
        <td style="padding: 5px 0; font-size: 14px; color: #6c757d;">Discount</td>
        <td align="right" style="padding: 5px 0; font-size: 14px; color: #dc3545;">-${{ number_format($order->discount, 2) }}</td>
    </tr>
    @endif
    <tr>
        <td style="padding: 12px 0 5px; font-size: 18px; font-weight: 700; color: #212529; border-top: 2px solid #212529;">Total</td>
        <td align="right" style="padding: 12px 0 5px; font-size: 18px; font-weight: 700; color: #212529; border-top: 2px solid #212529;">${{ number_format($order->total, 2) }}</td>
    </tr>
</table>

{{-- Payment method --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px; background-color: #f8f9fa; border-radius: 6px;">
    <tr>
        <td style="padding: 15px 20px;">
            <p style="margin: 0 0 3px; font-size: 12px; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px;">Payment Method</p>
            <p style="margin: 0; font-size: 14px; color: #212529;">{{ $order->payment_method ?? 'Online Payment' }}</p>
        </td>
    </tr>
</table>

{{-- Shipping address --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 30px; background-color: #f8f9fa; border-radius: 6px;">
    <tr>
        <td style="padding: 15px 20px;">
            <p style="margin: 0 0 8px; font-size: 12px; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px;">Shipping Address</p>
            <p style="margin: 0; font-size: 14px; color: #212529; line-height: 22px;">
                {{ $order->shipping_first_name }} {{ $order->shipping_last_name }}<br>
                @if($order->shipping_company){{ $order->shipping_company }}<br>@endif
                {{ $order->shipping_address_line1 }}<br>
                @if($order->shipping_address_line2){{ $order->shipping_address_line2 }}<br>@endif
                {{ $order->shipping_postal_code }} {{ $order->shipping_city }}<br>
                @if($order->shipping_state){{ $order->shipping_state }}<br>@endif
                {{ $order->shipping_country }}
            </p>
        </td>
    </tr>
</table>

{{-- Message --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px;">
    <tr>
        <td style="padding: 15px 20px; background-color: #e8f5e9; border-radius: 6px; border-left: 4px solid #2D7A3E;">
            <p style="margin: 0; font-size: 14px; color: #2D7A3E; line-height: 22px;">
                We will send you another email when your order has been shipped. You can track your order status from your account.
            </p>
        </td>
    </tr>
</table>

{{-- CTA Button --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td align="center" style="padding: 10px 0;">
            <a href="{{ $orderUrl }}" style="display: inline-block; padding: 14px 35px; background: linear-gradient(135deg, #5FAE51, #258132); color: #ffffff; font-size: 15px; font-weight: 700; text-decoration: none; border-radius: 6px;">
                Visit Our Store
            </a>
        </td>
    </tr>
</table>
@endsection
