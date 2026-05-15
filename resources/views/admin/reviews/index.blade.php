@extends('layouts.app')

@section('title', 'Reviews — Moderation')

@section('content')
<div style="padding:24px;font-family:'Ubuntu',sans-serif;">
  <h1 style="font-size:1.5rem;font-weight:700;margin-bottom:18px;">Reviews — Moderation</h1>

  <div style="display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap;">
    @foreach(['pending', 'approved', 'rejected', 'all'] as $s)
      <a href="{{ route('admin.reviews.index', ['status' => $s]) }}"
         style="padding:8px 14px;border-radius:20px;border:1.5px solid {{ $status === $s ? '#2D7A3E' : '#d1d5db' }};color:{{ $status === $s ? '#2D7A3E' : '#374151' }};text-decoration:none;font-size:.85rem;font-weight:600;background:{{ $status === $s ? '#f0faf1' : '#fff' }};">
        {{ ucfirst($s) }}
        @if(isset($counts[$s])) <span style="opacity:.6;">({{ $counts[$s] }})</span>@endif
      </a>
    @endforeach
  </div>

  @if(session('success'))
    <div style="background:#f0faf1;border-left:3px solid #2D7A3E;padding:10px 14px;border-radius:6px;color:#2D7A3E;margin-bottom:14px;font-size:.88rem;">
      {{ session('success') }}
    </div>
  @endif

  <table style="width:100%;border-collapse:collapse;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
    <thead>
      <tr style="background:#f8f9fa;">
        <th style="padding:12px;text-align:left;font-size:.78rem;text-transform:uppercase;letter-spacing:.8px;color:#6b7280;">Date</th>
        <th style="padding:12px;text-align:left;font-size:.78rem;text-transform:uppercase;letter-spacing:.8px;color:#6b7280;">Product</th>
        <th style="padding:12px;text-align:left;font-size:.78rem;text-transform:uppercase;letter-spacing:.8px;color:#6b7280;">Rating</th>
        <th style="padding:12px;text-align:left;font-size:.78rem;text-transform:uppercase;letter-spacing:.8px;color:#6b7280;">Customer</th>
        <th style="padding:12px;text-align:left;font-size:.78rem;text-transform:uppercase;letter-spacing:.8px;color:#6b7280;">Excerpt</th>
        <th style="padding:12px;text-align:right;font-size:.78rem;text-transform:uppercase;letter-spacing:.8px;color:#6b7280;">Actions</th>
      </tr>
    </thead>
    <tbody>
      @forelse($reviews as $r)
      <tr style="border-top:1px solid #f0f0f0;">
        <td style="padding:12px;font-size:.85rem;color:#6b7280;">{{ $r->created_at->format('d M Y H:i') }}</td>
        <td style="padding:12px;font-size:.85rem;">
          @php $pname = is_array(optional($r->product)->name) ? ($r->product->name[app()->getLocale()] ?? $r->product->name['en'] ?? '?') : (optional($r->product)->name ?? '?'); @endphp
          {{ $pname }}
        </td>
        <td style="padding:12px;color:#F59E0B;white-space:nowrap;">
          {!! str_repeat('★', $r->rating) . str_repeat('☆', 5 - $r->rating) !!}
        </td>
        <td style="padding:12px;font-size:.85rem;">
          {{ $r->customer_name }}
          @if($r->verified_purchase)
            <span style="color:#2D7A3E;font-size:.7rem;font-weight:700;padding:2px 6px;background:#f0faf1;border-radius:8px;margin-left:4px;">✓</span>
          @endif
          <div style="font-size:.72rem;color:#9ca3af;">{{ $r->customer_email }}</div>
        </td>
        <td style="padding:12px;font-size:.85rem;color:#374151;max-width:300px;">
          @if($r->title)<strong>{{ $r->title }}</strong> — @endif
          {{ \Illuminate\Support\Str::limit($r->body, 100) }}
        </td>
        <td style="padding:12px;text-align:right;white-space:nowrap;">
          <a href="{{ route('admin.reviews.show', $r) }}" style="color:#3D8B40;font-size:.78rem;font-weight:600;text-decoration:underline;">Open</a>
        </td>
      </tr>
      @empty
      <tr><td colspan="6" style="padding:32px;text-align:center;color:#9ca3af;">No reviews in this status.</td></tr>
      @endforelse
    </tbody>
  </table>

  <div style="margin-top:16px;">
    {{ $reviews->links() }}
  </div>
</div>
@endsection
