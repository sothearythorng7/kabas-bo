<?php

namespace App\Services;

/**
 * Single source of truth for condition/action types exposed in the admin UI.
 * Must stay in sync with App\Promotions\* classes on the kabas-site side.
 *
 * Each type exposes:
 *  - key: machine id stored in promotion_conditions.type / promotion_actions.type
 *  - label: translation key (falls back to raw string)
 *  - schema.fields: [{key, type, label, required, default?}]
 *  - schema.supports_operator: bool (conditions only)
 *  - schema.operators: array (when supports_operator)
 */
class PromotionTypeCatalog
{
    public static function conditions(): array
    {
        return [
            [
                'key' => 'cart.min_subtotal',
                'label' => __('messages.promotion.condition.cart_min_subtotal'),
                'fields' => [
                    ['key' => 'amount', 'type' => 'decimal', 'required' => true, 'label' => __('messages.promotion.field.amount')],
                ],
                'supports_operator' => false,
                'operators' => [],
            ],
            [
                'key' => 'cart.has_category',
                'label' => __('messages.promotion.condition.cart_has_category'),
                'fields' => [
                    ['key' => 'category_ids', 'type' => 'category_multi', 'required' => true, 'label' => __('messages.promotion.field.categories')],
                    ['key' => 'min_quantity', 'type' => 'integer', 'required' => false, 'label' => __('messages.promotion.field.min_quantity'), 'default' => 1],
                ],
                'supports_operator' => true,
                'operators' => ['in', 'not_in'],
            ],
            [
                'key' => 'cart.shipping_country',
                'label' => __('messages.promotion.condition.cart_shipping_country'),
                'fields' => [
                    ['key' => 'country_codes', 'type' => 'country_multi', 'required' => true, 'label' => __('messages.promotion.field.country_codes')],
                ],
                'supports_operator' => true,
                'operators' => ['in', 'not_in'],
            ],
            [
                'key' => 'cart.has_brand',
                'label' => __('messages.promotion.condition.cart_has_brand'),
                'fields' => [
                    ['key' => 'brand_ids', 'type' => 'brand_multi', 'required' => true, 'label' => __('messages.promotion.field.brands')],
                    ['key' => 'min_quantity', 'type' => 'integer', 'required' => false, 'label' => __('messages.promotion.field.min_quantity'), 'default' => 1],
                ],
                'supports_operator' => true,
                'operators' => ['in', 'not_in'],
            ],
            [
                'key' => 'customer.is_new',
                'label' => __('messages.promotion.condition.customer_is_new'),
                'fields' => [],
                'supports_operator' => false,
                'operators' => [],
            ],
        ];
    }

    public static function actions(): array
    {
        return [
            [
                'key' => 'action.free_shipping',
                'label' => __('messages.promotion.action.free_shipping'),
                'fields' => [],
            ],
            [
                'key' => 'action.gift_product',
                'label' => __('messages.promotion.action.gift_product'),
                'fields' => [
                    ['key' => 'product_id', 'type' => 'product', 'required' => true, 'label' => __('messages.promotion.field.product')],
                    ['key' => 'quantity', 'type' => 'integer', 'required' => false, 'label' => __('messages.promotion.field.quantity'), 'default' => 1],
                ],
            ],
            [
                'key' => 'action.percent_off_cheapest',
                'label' => __('messages.promotion.action.percent_off_cheapest'),
                'fields' => [
                    ['key' => 'percent', 'type' => 'decimal', 'required' => true, 'label' => __('messages.promotion.field.percent')],
                    ['key' => 'category_ids', 'type' => 'category_multi', 'required' => false, 'label' => __('messages.promotion.field.restrict_categories')],
                ],
            ],
            [
                'key' => 'action.percent_off_cart',
                'label' => __('messages.promotion.action.percent_off_cart'),
                'fields' => [
                    ['key' => 'percent', 'type' => 'decimal', 'required' => true, 'label' => __('messages.promotion.field.percent')],
                ],
            ],
            [
                'key' => 'action.amount_off_cart',
                'label' => __('messages.promotion.action.amount_off_cart'),
                'fields' => [
                    ['key' => 'amount', 'type' => 'decimal', 'required' => true, 'label' => __('messages.promotion.field.amount')],
                ],
            ],
        ];
    }

    public static function conditionByKey(string $key): ?array
    {
        foreach (self::conditions() as $c) {
            if ($c['key'] === $key) {
                return $c;
            }
        }
        return null;
    }

    public static function actionByKey(string $key): ?array
    {
        foreach (self::actions() as $a) {
            if ($a['key'] === $key) {
                return $a;
            }
        }
        return null;
    }
}
