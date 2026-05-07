<?php

namespace App\Http\Controllers;

use App\Models\PaymentRecoverySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentRecoverySettingController extends Controller
{
    public function edit()
    {
        $setting = PaymentRecoverySetting::current();
        $stats = $this->buildStats();
        return view('payment_recovery_settings.edit', compact('setting', 'stats'));
    }

    protected function buildStats(): array
    {
        $today = now()->startOfDay();
        $weekStart = now()->subDays(6)->startOfDay();
        $monthStart = now()->subDays(29)->startOfDay();

        $total = DB::table('order_payment_recovery_reminders')->count();
        $todayCount = DB::table('order_payment_recovery_reminders')->where('sent_at', '>=', $today)->count();
        $weekCount = DB::table('order_payment_recovery_reminders')->where('sent_at', '>=', $weekStart)->count();
        $monthCount = DB::table('order_payment_recovery_reminders')->where('sent_at', '>=', $monthStart)->count();

        $raw = DB::table('order_payment_recovery_reminders')
            ->where('sent_at', '>=', $monthStart)
            ->selectRaw('DATE(sent_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('count', 'day')
            ->all();

        $daily = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $daily[] = ['date' => $date, 'count' => (int) ($raw[$date] ?? 0)];
        }

        return [
            'total' => $total,
            'today' => $todayCount,
            'week' => $weekCount,
            'month' => $monthCount,
            'daily' => $daily,
        ];
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled' => 'sometimes|boolean',
            'delay_hours' => 'required|integer|min:1|max:720',
            'link_validity_days' => 'required|integer|min:1|max:30',
            'subject' => 'required|array',
            'subject.fr' => 'required|string|max:255',
            'subject.en' => 'required|string|max:255',
            'heading' => 'required|array',
            'heading.fr' => 'required|string|max:255',
            'heading.en' => 'required|string|max:255',
            'intro_body' => 'required|array',
            'intro_body.fr' => 'required|string',
            'intro_body.en' => 'required|string',
            'cta_label' => 'required|array',
            'cta_label.fr' => 'required|string|max:100',
            'cta_label.en' => 'required|string|max:100',
            'footer_text' => 'required|array',
            'footer_text.fr' => 'required|string',
            'footer_text.en' => 'required|string',
        ]);
        $data['enabled'] = $request->has('enabled');

        $setting = PaymentRecoverySetting::current();
        $setting->fill($data)->save();

        return redirect()->route('payment-recovery-settings.edit')
            ->with('success', __('messages.payment_recovery.updated'));
    }
}
