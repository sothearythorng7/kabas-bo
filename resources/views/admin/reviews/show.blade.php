@extends('layouts.app')

@section('title', 'Review #' . $review->id)

@section('content')
<div style="max-width:800px;margin:24px auto;padding:24px;font-family:'Ubuntu',sans-serif;">
  <a href="{{ route('admin.reviews.index') }}" style="color:#3D8B40;font-size:.85rem;text-decoration:none;">← Back to list</a>
  <h1 style="font-size:1.625rem;font-weight:700;margin:14px 0 4px;">Review #{{ $review->id }}</h1>
  <div style="color:#6b7280;font-size:.9rem;margin-bottom:24px;">
    Status: <strong style="color:{{ ['pending'=>'#d97706','approved'=>'#2D7A3E','rejected'=>'#e03131'][$review->status] ?? '#374151' }};text-transform:uppercase;letter-spacing:.5px;">{{ $review->status }}</strong>
    · {{ $review->created_at->format('d M Y H:i') }}
  </div>

  @if(session('success'))
    <div style="background:#f0faf1;border-left:3px solid #2D7A3E;padding:10px 14px;border-radius:6px;color:#2D7A3E;margin-bottom:14px;font-size:.88rem;">
      {{ session('success') }}
    </div>
  @endif

  <div style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);margin-bottom:18px;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-bottom:14px;">
      <div>
        <div style="font-weight:700;color:#000;">
          {{ $review->customer_name }}
          @if($review->verified_purchase)<span style="color:#2D7A3E;font-size:.7rem;font-weight:700;padding:2px 8px;background:#f0faf1;border-radius:10px;margin-left:6px;">✓ Verified Buyer</span>@endif
        </div>
        <div style="font-size:.78rem;color:#9ca3af;">{{ $review->customer_email }} · {{ $review->language }} · IP {{ $review->ip_address ?? '?' }}</div>
      </div>
      <div style="color:#F59E0B;font-size:1.4rem;">{{ $review->stars }}</div>
    </div>
    @if($review->title)
      <h2 style="font-size:1.05rem;font-weight:700;color:#000;margin-bottom:8px;">{{ $review->title }}</h2>
    @endif
    <p style="color:#374151;line-height:1.6;white-space:pre-wrap;">{{ $review->body }}</p>

    @if($review->order)
      <div style="margin-top:14px;padding:10px 14px;background:#f8f9fa;border-radius:8px;font-size:.85rem;color:#374151;">
        Linked to order <a href="#" style="color:#3D8B40;font-weight:600;">{{ $review->order->order_number }}</a>
      </div>
    @endif
  </div>

  @if($review->hasReply())
    <div style="background:#f0faf1;border-left:3px solid #2D7A3E;padding:14px 18px;border-radius:8px;margin-bottom:18px;">
      <div style="font-size:.78rem;color:#2D7A3E;font-weight:700;margin-bottom:6px;">{{ $review->reply_author }} · {{ optional($review->replied_at)->format('d M Y H:i') }}</div>
      <div style="color:#374151;line-height:1.5;white-space:pre-wrap;">{{ $review->reply_body }}</div>
    </div>
  @endif

  <div style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);margin-bottom:18px;">
    <h3 style="font-size:.85rem;font-weight:700;color:#000;text-transform:uppercase;letter-spacing:1px;margin-bottom:14px;">Reply (public)</h3>
    <form method="POST" action="{{ route('admin.reviews.reply', $review) }}">
      @csrf
      <textarea name="reply_body" required minlength="5" maxlength="2000" style="width:100%;min-height:100px;padding:12px;border:1.5px solid #d1d5db;border-radius:10px;font-family:inherit;font-size:.95rem;resize:vertical;">{{ $review->reply_body }}</textarea>
      <input type="text" name="reply_author" value="{{ $review->reply_author ?: 'Kabas Concept Store' }}" style="margin-top:8px;width:100%;padding:11px;border:1.5px solid #d1d5db;border-radius:10px;font-family:inherit;font-size:.9rem;" placeholder="Author name (default: Kabas Concept Store)">
      <button type="submit" style="margin-top:12px;background:#000;color:#fff;border:none;padding:11px 24px;border-radius:24px;font-family:inherit;font-size:.82rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;cursor:pointer;">Save reply</button>
    </form>
  </div>

  <div style="display:flex;gap:10px;flex-wrap:wrap;">
    @if($review->status !== 'approved')
      <form method="POST" action="{{ route('admin.reviews.approve', $review) }}">@csrf
        <button type="submit" style="background:linear-gradient(90deg, #5FAE51 13.05%, #258132 92.95%);color:#fff;border:none;padding:11px 24px;border-radius:24px;font-family:inherit;font-size:.82rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;cursor:pointer;">✓ Approve</button>
      </form>
    @endif
    @if($review->status !== 'rejected')
      <form method="POST" action="{{ route('admin.reviews.reject', $review) }}">@csrf
        <button type="submit" style="background:#fff;color:#e03131;border:1.5px solid #e03131;padding:10px 22px;border-radius:24px;font-family:inherit;font-size:.82rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;cursor:pointer;">✗ Reject</button>
      </form>
    @endif
    <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" onsubmit="return confirm('Delete this review permanently?')">
      @csrf @method('DELETE')
      <button type="submit" style="background:#fff;color:#9ca3af;border:1.5px solid #d1d5db;padding:10px 22px;border-radius:24px;font-family:inherit;font-size:.82rem;font-weight:500;text-transform:uppercase;letter-spacing:.5px;cursor:pointer;">Delete</button>
    </form>
  </div>
</div>
@endsection
