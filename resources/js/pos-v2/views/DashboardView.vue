<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useSessionStore } from '../stores/session.js';
import { useCashStore } from '../stores/cash.js';
import { useI18nStore } from '../stores/i18n.js';
import { useCatalogStore } from '../stores/catalog.js';
import { useCartStore } from '../stores/cart.js';
import { useSalesStore } from '../stores/sales.js';
import { useBarcodeScanner } from '../composables/useBarcodeScanner.js';

import TopBar from '../components/TopBar.vue';
import LeftRail from '../components/LeftRail.vue';
import CashInOutDialog from '../components/CashInOutDialog.vue';
import ChangeUserDialog from '../components/ChangeUserDialog.vue';
import ExpectedCashWidget from '../components/ExpectedCashWidget.vue';

import CategoryBreadcrumb from '../components/catalog/CategoryBreadcrumb.vue';
import CategoryChips from '../components/catalog/CategoryChips.vue';
import BrandFilter from '../components/catalog/BrandFilter.vue';
import ProductGrid from '../components/catalog/ProductGrid.vue';

import CartItem from '../components/cart/CartItem.vue';
import DiscountDialog from '../components/cart/DiscountDialog.vue';
import CustomServiceDialog from '../components/cart/CustomServiceDialog.vue';
import DeliveryDialog from '../components/cart/DeliveryDialog.vue';
import PaymentDialog from '../components/payment/PaymentDialog.vue';
import HoldSalesDropdown from '../components/cart/HoldSalesDropdown.vue';
import ExchangeWizard from '../components/exchange/ExchangeWizard.vue';

import { useSync } from '../composables/useSync.js';
import { useExchangeStore } from '../stores/exchange.js';

const router = useRouter();
const session = useSessionStore();
const cash = useCashStore();
const i18n = useI18nStore();
const catalog = useCatalogStore();
const cart = useCartStore();
const sales = useSalesStore();
const exchange = useExchangeStore();

const t = computed(() => i18n.t);

const search = ref('');
const categoryPath = ref([]); // [{ id, name }]
const selectedSubcategoryId = ref(null);
const brandFilter = ref('');

const cashDialogOpen = ref(false);
const cashDirection = ref('in');
const switchDialogOpen = ref(false);
const customServiceOpen = ref(false);
const deliveryOpen = ref(false);
const discountDialog = ref({ open: false, scope: 'line', lineId: null, initial: null });
const paymentOpen = ref(false);
const paymentFlash = ref(null); // last paid feedback

// Auto-sync loop (60s + on online). Runs while DashboardView is mounted.
useSync({ intervalMs: 60_000 });

// ─── Boot ───
onMounted(async () => {
    if (!session.currentShift?.id) return;
    await cart.initForShift(session.currentShift.id, session.currentUser?.name || '');
    await cash.loadForShift(session.currentShift.id);
    await sales.refreshCounts();

    const hadCache = await catalog.loadFromCache();
    if (session.isOnline) {
        catalog.syncFromApi(session.currentUser.store_id).catch((err) => {
            if (!hadCache) {
                console.error('[POS V2] no cached catalog and sync failed', err);
            }
        });
    }
});

// ─── Catalog navigation ───
const currentNode = computed(() => {
    if (!catalog.categoryTree) return null;
    if (categoryPath.value.length === 0) return catalog.categoryTree;
    return categoryPath.value[categoryPath.value.length - 1];
});

const childrenOfCurrent = computed(() => {
    const node = currentNode.value;
    if (!node) return [];
    return node.children || [];
});

const filteredProducts = computed(() => {
    let pool = catalog.products;
    // Filter by category
    if (categoryPath.value.length > 0 || selectedSubcategoryId.value) {
        const targetId = selectedSubcategoryId.value
            ?? categoryPath.value[categoryPath.value.length - 1]?.id;
        if (targetId) {
            pool = pool.filter((p) => {
                const cats = p.categories || [];
                return cats.some((c) => (c.id === targetId) || c === targetId || matchAncestor(c, targetId));
            });
        }
    }
    // Filter by brand
    if (brandFilter.value) {
        pool = pool.filter((p) => {
            const raw = p.brand?.name || p.brand || '';
            const name = typeof raw === 'string' ? raw : (raw.en || raw.fr || '');
            return titleCase(name) === brandFilter.value;
        });
    }
    // Filter by search
    if (search.value.trim()) {
        const q = search.value.trim().toLowerCase();
        const isCode = q.length >= 4 && /^[\d\-a-z]+$/i.test(q);
        if (isCode) {
            const exact = pool.filter((p) => {
                if (p.ean && String(p.ean).toLowerCase() === q) return true;
                if (p.barcodes && p.barcodes.includes(q)) return true;
                return false;
            });
            if (exact.length) return exact;
        }
        return pool.filter((p) => {
            const fr = (p.name?.fr || '').toLowerCase();
            const en = (p.name?.en || '').toLowerCase();
            return fr.includes(q) || en.includes(q);
        });
    }
    return pool;
});

function titleCase(str) {
    return String(str).toLowerCase().replace(/\b\w/g, (c) => c.toUpperCase());
}

function matchAncestor(category, targetId) {
    // Simple recursive ancestor check if category is a node object
    if (!category || typeof category !== 'object') return false;
    if (category.id === targetId) return true;
    if (Array.isArray(category.children)) {
        return category.children.some((c) => matchAncestor(c, targetId));
    }
    return false;
}

function selectBreadcrumb(path) {
    categoryPath.value = path;
    selectedSubcategoryId.value = null;
}

function selectChip(cat) {
    if (cat == null) {
        selectedSubcategoryId.value = null;
        return;
    }
    selectedSubcategoryId.value = cat.id;
}

// ─── Cart actions ───
async function handleAddProduct(item) {
    await cart.addCatalogItem(item, 1);
}

function openLineDiscount(item) {
    discountDialog.value = {
        open: true,
        scope: 'line',
        lineId: item.line_id,
        initial: item.discounts?.[0] || null,
    };
}

function openGlobalDiscount() {
    discountDialog.value = {
        open: true,
        scope: 'global',
        lineId: null,
        initial: cart.activeSale.discounts?.[0] || null,
    };
}

async function applyDiscount(discount) {
    if (discountDialog.value.scope === 'global') {
        await cart.setGlobalDiscount(discount);
    } else {
        await cart.setLineDiscount(discountDialog.value.lineId, discount);
    }
    discountDialog.value.open = false;
}

async function clearDiscount() {
    if (discountDialog.value.scope === 'global') {
        await cart.setGlobalDiscount(null);
    } else {
        await cart.setLineDiscount(discountDialog.value.lineId, null);
    }
    discountDialog.value.open = false;
}

async function submitCustomService({ description, amount }) {
    await cart.addCustomService(description, amount);
    customServiceOpen.value = false;
}

async function submitDelivery({ address, fee }) {
    await cart.addDelivery(address, fee);
    deliveryOpen.value = false;
}

// ─── Barcode scanner ───
useBarcodeScanner({
    onScan: async (code) => {
        const item = catalog.findByBarcode(code);
        if (item) {
            await cart.addCatalogItem(item, 1);
            search.value = '';
        } else {
            search.value = code;
        }
    },
});

// ─── Dialog helpers ───
function openCashIn() {
    cashDirection.value = 'in';
    cashDialogOpen.value = true;
}
function openCashOut() {
    cashDirection.value = 'out';
    cashDialogOpen.value = true;
}

function fmt(v) {
    return `$${(Math.round((v || 0) * 100) / 100).toFixed(2)}`;
}

function payClicked() {
    if (cart.itemCount === 0) return;
    paymentOpen.value = true;
}

async function onPaid({ localId }) {
    paymentOpen.value = false;
    paymentFlash.value = { id: localId, at: Date.now() };
    // Recompute expected cash + trigger sync immediately.
    await sales.refresh();
    cash.setCashSales(sales.cashSalesForShift(session.currentShift.id));
    if (session.isOnline) {
        sales.syncNow(session.currentShift.id).then(() => {
            cash.setCashSales(sales.cashSalesForShift(session.currentShift.id));
        });
    }
    // Auto-fade flash banner.
    setTimeout(() => { paymentFlash.value = null; }, 4000);
}

async function forceSync() {
    if (!session.currentShift?.id) return;
    await sales.syncNow(session.currentShift.id);
    cash.setCashSales(sales.cashSalesForShift(session.currentShift.id));
}

async function holdCurrent() {
    if (cart.itemCount === 0) return;
    await cart.holdActive();
}

const globalDiscountSummary = computed(() => {
    const d = cart.activeSale.discounts?.[0];
    if (!d) return null;
    if (d.type === 'percent') return `−${d.value}%`;
    return `−$${Number(d.value).toFixed(2)}`;
});
</script>

<template>
    <div class="flex-1 flex flex-col h-full">
        <TopBar @switch-cashier="switchDialogOpen = true" @force-sync="forceSync" />

        <div class="flex flex-1 overflow-hidden">
            <LeftRail @cash-in="openCashIn" @cash-out="openCashOut" />

            <!-- ── Catalog area ── -->
            <main class="flex-1 flex flex-col bg-stone-50 overflow-hidden">
                <!-- Search bar -->
                <div class="px-6 pt-5 pb-3">
                    <div class="flex items-center gap-3">
                        <div class="flex-1 relative">
                            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-stone-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Scan barcode or type product name / EAN…"
                                class="w-full bg-white border border-stone-200 rounded-xl pl-12 pr-32 h-12 text-[15px] placeholder:text-stone-400 focus:border-stone-400 focus:outline-none focus:ring-4 focus:ring-stone-200/50"
                            >
                            <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-2">
                                <span class="flex items-center gap-1.5 text-[11px] text-emerald-700 bg-emerald-50 border border-emerald-100 px-2 py-1 rounded-md font-medium">
                                    <span class="inline-block w-2 h-2 bg-emerald-500 rounded-full pulse-dot"></span>
                                    Scanner ready
                                </span>
                            </div>
                        </div>
                        <button disabled class="h-12 px-4 bg-white border border-stone-200 rounded-xl text-[14px] font-medium text-stone-300 cursor-not-allowed flex items-center gap-2" :title="'Voucher — Phase 4'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            Voucher
                        </button>
                        <button @click="exchange.start()" class="h-12 px-4 bg-white border border-stone-200 rounded-xl text-[14px] font-medium text-stone-700 hover:bg-stone-50 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                            {{ t('exchange') }}
                        </button>
                    </div>
                </div>

                <!-- Breadcrumb + filters -->
                <div class="px-6 pb-3 flex items-center justify-between">
                    <CategoryBreadcrumb
                        :path="categoryPath"
                        :product-count="filteredProducts.length"
                        @select="selectBreadcrumb"
                    />
                    <div class="flex items-center gap-2">
                        <BrandFilter v-model="brandFilter" :brands="catalog.brands" />
                    </div>
                </div>

                <!-- Subcategory chips -->
                <div class="px-6 pb-4">
                    <CategoryChips
                        :categories="childrenOfCurrent"
                        :selected-id="selectedSubcategoryId"
                        @select="selectChip"
                    />
                </div>

                <!-- Product grid -->
                <div class="flex-1 overflow-y-auto px-6 pb-6 scrollbar-thin">
                    <ProductGrid
                        :items="filteredProducts"
                        :loading="catalog.loading && filteredProducts.length === 0"
                        @add="handleAddProduct"
                    />
                </div>
            </main>

            <!-- ── Cart panel ── -->
            <aside class="w-[420px] bg-white border-l border-stone-200/80 flex flex-col">
                <!-- Header -->
                <div class="px-5 py-4 border-b border-stone-200/80 flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-serif text-lg">Sale</span>
                            <span class="text-[11px] font-mono text-stone-500 bg-stone-100 px-2 py-0.5 rounded-md">
                                {{ cart.activeSale.id?.slice(0, 6) }}
                            </span>
                        </div>
                        <div class="text-[11px] text-stone-500 mt-0.5">
                            Walk-in customer · {{ session.currentUser?.name }}
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <HoldSalesDropdown />
                        <button
                            type="button"
                            @click="holdCurrent"
                            :disabled="cart.itemCount === 0"
                            class="text-[11px] font-medium px-2.5 py-1 bg-stone-100 hover:bg-stone-200 rounded-full disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-1"
                            :title="t('holdSale')"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                            {{ t('holdSale') }}
                        </button>
                    </div>
                </div>

                <!-- Items -->
                <div class="flex-1 overflow-y-auto scrollbar-thin px-5 py-3 space-y-2.5">
                    <div v-if="cart.itemCount === 0" class="py-12 text-center text-stone-400">
                        <svg class="w-10 h-10 mx-auto mb-2 text-stone-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                        <p class="text-sm">Empty cart</p>
                        <p class="text-xs mt-1">Tap a product or scan an EAN to start.</p>
                    </div>
                    <CartItem
                        v-for="item in cart.activeSale.items"
                        :key="item.line_id"
                        :item="item"
                        @discount="openLineDiscount"
                    />
                </div>

                <!-- Add buttons -->
                <div class="px-5 py-3 border-t border-stone-200/80 grid grid-cols-3 gap-2">
                    <button @click="customServiceOpen = true" class="flex flex-col items-center gap-1 py-2 rounded-lg border border-dashed border-stone-300 text-stone-600 hover:bg-stone-50 hover:border-stone-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                        <span class="text-[10px] font-medium">Service</span>
                    </button>
                    <button @click="deliveryOpen = true" class="flex flex-col items-center gap-1 py-2 rounded-lg border border-dashed border-stone-300 text-stone-600 hover:bg-stone-50 hover:border-stone-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                        <span class="text-[10px] font-medium">Delivery</span>
                    </button>
                    <button @click="openGlobalDiscount" :disabled="cart.itemCount === 0" class="flex flex-col items-center gap-1 py-2 rounded-lg border border-dashed border-stone-300 text-stone-600 hover:bg-stone-50 hover:border-stone-400 disabled:opacity-40 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
                        <span class="text-[10px] font-medium">Discount</span>
                    </button>
                </div>

                <!-- Totals -->
                <div class="px-5 py-4 border-t border-stone-200/80 bg-stone-50/50">
                    <div class="space-y-1.5 text-[13px]">
                        <div class="flex justify-between text-stone-600">
                            <span>Subtotal</span>
                            <span class="tabular-nums">{{ fmt(cart.subtotal) }}</span>
                        </div>
                        <div v-if="globalDiscountSummary" class="flex justify-between text-amber-700">
                            <span class="flex items-center gap-1">
                                Global discount
                                <span class="text-[10px] bg-amber-100 px-1.5 py-0.5 rounded font-medium">{{ globalDiscountSummary }}</span>
                            </span>
                            <span class="tabular-nums">−{{ fmt(cart.discountAmount) }}</span>
                        </div>
                    </div>
                    <div class="flex items-baseline justify-between mt-3 pt-3 border-t border-stone-200">
                        <span class="text-[13px] font-medium text-stone-600">Total</span>
                        <span class="font-serif text-3xl">{{ fmt(cart.total) }}</span>
                    </div>
                    <button
                        @click="payClicked"
                        :disabled="cart.itemCount === 0"
                        class="w-full mt-4 h-14 bg-stone-900 hover:bg-stone-800 text-white rounded-2xl font-semibold text-[16px] flex items-center justify-center gap-2 shadow-lg shadow-stone-900/10 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                        {{ t('pay') }} {{ fmt(cart.total) }}
                    </button>
                    <div class="flex items-center justify-center gap-1.5 mt-2 text-[11px] text-stone-500">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/><polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/><line x1="4" y1="4" x2="9" y2="9"/></svg>
                        Cash, Card, Mobile, Voucher · split supported
                    </div>
                </div>
            </aside>
        </div>

        <!-- Footer -->
        <footer class="h-8 bg-white border-t border-stone-200/80 px-4 flex items-center justify-between text-[11px] text-stone-500">
            <div class="flex items-center gap-4">
                <span class="flex items-center gap-1.5">
                    <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    Catalog: {{ catalog.products.length + catalog.giftBoxes.length + catalog.giftCards.length }} items
                </span>
                <span v-if="catalog.lastSyncedAt">·</span>
                <span v-if="catalog.lastSyncedAt">synced {{ new Date(catalog.lastSyncedAt).toLocaleTimeString() }}</span>
            </div>
            <div class="flex items-center gap-4">
                <ExpectedCashWidget />
                <span class="text-stone-400">POS v2.0.0-phase3</span>
            </div>
        </footer>

        <!-- Dialogs -->
        <CashInOutDialog :open="cashDialogOpen" :direction="cashDirection" @close="cashDialogOpen = false" />
        <ChangeUserDialog :open="switchDialogOpen" @close="switchDialogOpen = false" />
        <DiscountDialog
            :open="discountDialog.open"
            :scope="discountDialog.scope"
            :initial="discountDialog.initial"
            @close="discountDialog.open = false"
            @apply="applyDiscount"
            @clear="clearDiscount"
        />
        <CustomServiceDialog :open="customServiceOpen" @close="customServiceOpen = false" @submit="submitCustomService" />
        <DeliveryDialog :open="deliveryOpen" @close="deliveryOpen = false" @submit="submitDelivery" />
        <PaymentDialog :open="paymentOpen" @close="paymentOpen = false" @paid="onPaid" />
        <ExchangeWizard />

        <!-- Paid flash banner -->
        <Transition name="dialog">
            <div v-if="paymentFlash" class="fixed bottom-4 right-4 z-50 bg-emerald-600 text-white px-5 py-3 rounded-2xl shadow-2xl text-sm font-medium flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                Sale recorded · queued for sync
            </div>
        </Transition>
    </div>
</template>
