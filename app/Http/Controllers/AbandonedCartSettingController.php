<?php

namespace App\Http\Controllers;

use App\Models\AbandonedCartSetting;
use App\Models\PromotionAction;
use App\Models\PromotionRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AbandonedCartSettingController extends Controller
{
    public function edit()
    {
        $setting = AbandonedCartSetting::current();
        $stats = $this->buildStats();
        return view('abandoned_cart_settings.edit', compact('setting', 'stats'));
    }

    protected function buildStats(): array
    {
        $today = now()->startOfDay();
        $weekStart = now()->subDays(6)->startOfDay();
        $monthStart = now()->subDays(29)->startOfDay();

        $total = DB::table('abandoned_cart_reminders')->count();
        $todayCount = DB::table('abandoned_cart_reminders')->where('sent_at', '>=', $today)->count();
        $weekCount = DB::table('abandoned_cart_reminders')->where('sent_at', '>=', $weekStart)->count();
        $monthCount = DB::table('abandoned_cart_reminders')->where('sent_at', '>=', $monthStart)->count();

        $raw = DB::table('abandoned_cart_reminders')
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
            'discount_type' => 'required|in:percent,amount',
            'discount_value' => 'required|numeric|min:0',
            'validity_days' => 'required|integer|min:1|max:365',
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

        if ($data['discount_type'] === 'percent') {
            $data['discount_value'] = min(100, $data['discount_value']);
        }

        DB::transaction(function () use ($data) {
            $setting = AbandonedCartSetting::current();
            $setting->fill($data);

            $rule = $setting->promotion_rule_id ? PromotionRule::find($setting->promotion_rule_id) : null;
            if (! $rule) {
                $rule = PromotionRule::create([
                    'name' => 'Abandoned cart reminder',
                    'status' => $data['enabled'] ? PromotionRule::STATUS_ACTIVE : PromotionRule::STATUS_PAUSED,
                    'activation_mode' => PromotionRule::MODE_CODE_REQUIRED,
                    'channel' => PromotionRule::CHANNEL_WEBSITE,
                    'conditions_logic' => PromotionRule::LOGIC_ALL,
                    'priority' => 10,
                    'is_exclusive' => false,
                    'max_uses_per_customer' => 1,
                    'created_by_user_id' => auth()->id(),
                ]);
                $setting->promotion_rule_id = $rule->id;
            } else {
                $rule->update([
                    'status' => $data['enabled'] ? PromotionRule::STATUS_ACTIVE : PromotionRule::STATUS_PAUSED,
                ]);
            }

            $action = $rule->actions()->first();
            $actionType = $data['discount_type'] === 'percent' ? 'action.percent_off_cart' : 'action.amount_off_cart';
            $params = $data['discount_type'] === 'percent'
                ? ['percent' => (float) $data['discount_value']]
                : ['amount' => (float) $data['discount_value']];

            if (! $action) {
                PromotionAction::create([
                    'promotion_rule_id' => $rule->id,
                    'type' => $actionType,
                    'params' => $params,
                    'position' => 0,
                ]);
            } else {
                $action->update(['type' => $actionType, 'params' => $params]);
            }

            $setting->save();
        });

        return redirect()->route('abandoned-cart-settings.edit')
            ->with('success', __('messages.abandoned_cart.updated'));
    }
}
