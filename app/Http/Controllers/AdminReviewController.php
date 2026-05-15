<?php

namespace App\Http\Controllers;

use App\Mail\ReviewApprovedNotificationMail;
use App\Models\Review;
use App\Support\ReviewAggregator;
use App\Support\ReviewMenuBadge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AdminReviewController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'pending');
        if (!in_array($status, ['pending', 'approved', 'rejected', 'all'], true)) {
            $status = 'pending';
        }

        $query = Review::with('product')->latest();
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $reviews = $query->paginate(25)->withQueryString();

        $counts = [
            'pending'  => Review::where('status', 'pending')->count(),
            'approved' => Review::where('status', 'approved')->count(),
            'rejected' => Review::where('status', 'rejected')->count(),
        ];

        return view('admin.reviews.index', compact('reviews', 'status', 'counts'));
    }

    public function show(Review $review): View
    {
        $review->load('product', 'order');
        return view('admin.reviews.show', compact('review'));
    }

    public function approve(Review $review): RedirectResponse
    {
        $wasApproved = $review->status === 'approved';
        $review->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        ReviewAggregator::invalidate($review->product_id);
        ReviewMenuBadge::invalidate();

        // Envoyer email "votre avis est en ligne" au customer (queued, opt-in via env)
        if (!$wasApproved && env('REVIEWS_NOTIFY_AUTHOR_ON_APPROVE', true) && $review->customer_email) {
            try {
                Mail::to($review->customer_email)->queue(new ReviewApprovedNotificationMail($review));
            } catch (\Throwable $e) {
                \Log::warning('Review approved notification failed: ' . $e->getMessage());
            }
        }

        return back()->with('success', __('reviews.admin.approved'));
    }

    public function reject(Review $review): RedirectResponse
    {
        $review->update(['status' => 'rejected']);
        ReviewAggregator::invalidate($review->product_id);
        ReviewMenuBadge::invalidate();
        return back()->with('success', __('reviews.admin.rejected'));
    }

    public function reply(Request $request, Review $review): RedirectResponse
    {
        $data = $request->validate([
            'reply_body'   => ['required', 'string', 'min:5', 'max:2000'],
            'reply_author' => ['nullable', 'string', 'max:100'],
        ]);

        $review->update([
            'reply_body'   => $data['reply_body'],
            'reply_author' => $data['reply_author'] ?? 'Kabas Concept Store',
            'replied_at'   => now(),
        ]);

        return back()->with('success', __('reviews.admin.reply_saved'));
    }

    public function destroy(Review $review): RedirectResponse
    {
        $productId = $review->product_id;
        $review->delete();
        ReviewAggregator::invalidate($productId);
        ReviewMenuBadge::invalidate();
        return redirect()->route('admin.reviews.index')->with('success', __('reviews.admin.deleted'));
    }
}
