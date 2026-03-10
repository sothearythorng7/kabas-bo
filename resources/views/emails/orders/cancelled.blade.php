@extends('emails.orders.layout')

@section('title', 'Order Cancelled - ' . $order->order_number)

@section('content')
{{-- Icon + Title --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td align="center" style="padding-bottom: 25px;">
            <div style="width: 60px; height: 60px; border-radius: 50%; background-color: #fce4e4; display: inline-block; line-height: 60px; text-align: center;">
                <span style="font-size: 30px; color: #dc3545;">&#10007;</span>
            </div>
            <h1 style="margin: 15px 0 5px; font-size: 24px; color: #212529;">Order Cancelled</h1>
            <p style="margin: 0; color: #6c757d; font-size: 14px;">Your order has been cancelled</p>
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

{{-- Refund message --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px;">
    <tr>
        <td style="padding: 20px; background-color: #fff3cd; border-radius: 6px; border-left: 4px solid #ffc107;">
            <p style="margin: 0; font-size: 14px; color: #664d03; line-height: 22px;">
                Your order has been cancelled and a full refund has been processed. Please allow up to <strong>2 business days</strong> for the refund to appear in your account.
            </p>
        </td>
    </tr>
</table>

{{-- Items table --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px;">
    <tr>
        <td colspan="3" style="padding-bottom: 10px; border-bottom: 2px solid #dc3545;">
            <strong style="font-size: 16px; color: #212529;">Cancelled Items</strong>
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
        <td align="right" style="padding: 12px 0; border-bottom: 1px solid #e9ecef; vertical-align: top;">
            <p style="margin: 0; font-size: 14px; font-weight: 500; color: #212529;">${{ number_format($item->subtotal, 2) }}</p>
        </td>
    </tr>
    @endforeach
</table>

{{-- Refund total --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 30px;">
    <tr>
        <td style="padding: 15px 20px; background-color: #f8f9fa; border-radius: 6px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="font-size: 16px; font-weight: 700; color: #212529;">Refund Amount</td>
                    <td align="right" style="font-size: 18px; font-weight: 700; color: #dc3545;">${{ number_format($order->total, 2) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- Support message --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td style="padding: 15px 20px; background-color: #f8f9fa; border-radius: 6px; border-left: 4px solid #6c757d;">
            <p style="margin: 0; font-size: 14px; color: #495057; line-height: 22px;">
                If you have any questions about this cancellation or your refund, please contact us at
                <a href="mailto:kabasconceptstore@gmail.com" style="color: #2D7A3E; text-decoration: none; font-weight: 500;">kabasconceptstore@gmail.com</a>
            </p>
        </td>
    </tr>
</table>
@endsection
