@php $isFr = ($locale ?? 'en') === 'fr'; @endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
<meta charset="UTF-8">
<title>{{ $isFr ? 'Votre avis est en ligne' : 'Your review is live' }}</title>
<style>
  body { margin:0; padding:0; font-family:'Ubuntu', Arial, sans-serif; background:#f8f9fa; color:#000; line-height:1.6; }
  .wrap { max-width:600px; margin:0 auto; background:#fff; }
  .header { background:#000; padding:24px; text-align:center; }
  .header img { width:56px; height:56px; border-radius:50%; background:#fff; padding:5px; }
  .header h1 { color:#fff; font-size:16px; margin:10px 0 0; font-weight:700; }
  .body { padding:32px 28px; }
  .body h2 { color:#000; font-size:22px; font-weight:700; margin:0 0 14px; letter-spacing:-0.3px; }
  .body p { color:#374151; font-size:15px; margin:0 0 12px; }
  .stars { font-size:30px; color:#F59E0B; letter-spacing:6px; text-align:center; margin:18px 0; }
  .review-quote { background:#f0faf1; border-left:3px solid #2D7A3E; padding:14px 18px; border-radius:8px; margin:16px 0; color:#374151; font-style:italic; font-size:14px; }
  .cta-wrap { text-align:center; margin:24px 0; }
  .cta { display:inline-block; background:linear-gradient(90deg, #5FAE51 13.05%, #258132 92.95%); color:#fff!important; text-decoration:none; padding:13px 30px; border-radius:24px; font-weight:500; text-transform:uppercase; letter-spacing:0.5px; font-size:14px; }
  .footer { background:#212529; color:#9ca3af; padding:24px; text-align:center; font-size:11px; line-height:1.6; }
  .footer a { color:#d1d5db; text-decoration:none; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <img src="{{ $imageBaseUrl }}/images/logo-circle.png" alt="Kabas">
    <h1>Kabas Concept Store</h1>
  </div>
  <div class="body">
    <h2>
      @if($isFr)
        Merci {{ $review->customer_name }} ! Votre avis est en ligne.
      @else
        Thank you {{ $review->customer_name }}! Your review is live.
      @endif
    </h2>
    <p>
      @if($isFr)
        Votre avis sur <strong>{{ $productName }}</strong> est désormais visible sur notre site et aide d'autres clients à découvrir nos produits.
      @else
        Your review of <strong>{{ $productName }}</strong> is now visible on our site and helps other customers discover our products.
      @endif
    </p>

    <div class="stars">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</div>

    @if($review->title)
      <p style="text-align:center;font-weight:700;color:#000;font-size:16px;margin:0 0 8px;">{{ $review->title }}</p>
    @endif
    <div class="review-quote">"{{ \Illuminate\Support\Str::limit($review->body, 220) }}"</div>

    <div class="cta-wrap">
      <a href="{{ $productUrl }}" class="cta">
        {{ $isFr ? 'Voir mon avis' : 'See my review' }}
      </a>
    </div>

    <p style="font-size:13px;color:#6b7280;text-align:center;">
      @if($isFr)
        Partagez votre expérience avec vos amis — chaque avis nous aide à grandir.
      @else
        Share your experience with friends — every review helps us grow.
      @endif
    </p>
  </div>
  <div class="footer">
    Kabas Concept Store · #65 Street 178, Phnom Penh, Cambodia<br>
    <a href="{{ $imageBaseUrl }}">www.kabasconceptstore.com</a><br>
    {{ $isFr ? 'Vous ne souhaitez plus recevoir ces emails ?' : 'No longer want these emails?' }}
    <a href="{{ $unsubscribeUrl }}">{{ $isFr ? 'Se désabonner' : 'Unsubscribe' }}</a>
  </div>
</div>
</body>
</html>
