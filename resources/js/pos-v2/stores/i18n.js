import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

const MESSAGES = {
    en: {
        signIn: 'Sign in',
        enterPin: 'Enter your PIN',
        invalidPin: 'Invalid PIN',
        signingIn: 'Signing in…',
        startShift: 'Start shift',
        initialCash: 'Initial cash in drawer',
        popupEvent: 'Popup event (optional)',
        none: 'None',
        todayAbsences: "Today's absences",
        noAbsences: 'No absences today',
        endShift: 'End shift',
        visitorsCount: 'How many visitors today?',
        countedCash: 'Counted cash in drawer',
        verification: 'Verification',
        openingCash: 'Opening cash',
        cashSales: 'Cash sales',
        cashIn: 'Cash in',
        cashOut: 'Cash out',
        expectedCash: 'Expected cash',
        countedAmount: 'Counted amount',
        difference: 'Difference',
        next: 'Next',
        back: 'Back',
        confirm: 'Confirm',
        cancel: 'Cancel',
        cashier: 'Cashier',
        switchCashier: 'Switch cashier',
        addCashIn: 'Cash in',
        addCashOut: 'Cash out',
        amount: 'Amount',
        ok: 'OK',
        loading: 'Loading…',
        offline: 'Offline — using cached data',
        retry: 'Retry',
        payment: 'Payment',
        pay: 'Pay',
        single: 'Single',
        split: 'Split',
        method: 'Method',
        addLine: 'Add line',
        voucher: 'Voucher',
        applyVoucher: 'Apply voucher',
        voucherCode: 'Voucher code',
        validate: 'Validate',
        invalidVoucher: 'Invalid or expired voucher',
        voucherAvailable: 'Available',
        expires: 'Expires',
        totalDue: 'Total due',
        totalPaid: 'Paid',
        remaining: 'Remaining',
        confirmPayment: 'Confirm payment',
        paymentMismatch: 'Split amount does not equal total',
        emptyCart: 'Cart is empty',
        syncedNow: 'Synced',
        syncFailed: 'Sync failed — will retry',
        forceSync: 'Sync now',
    },
    fr: {
        signIn: 'Connexion',
        enterPin: 'Saisissez votre PIN',
        invalidPin: 'PIN invalide',
        signingIn: 'Connexion…',
        startShift: 'Ouvrir la caisse',
        initialCash: 'Fond de caisse initial',
        popupEvent: 'Événement (optionnel)',
        none: 'Aucun',
        todayAbsences: "Absences du jour",
        noAbsences: 'Aucune absence aujourd\'hui',
        endShift: 'Fermer la caisse',
        visitorsCount: 'Combien de visiteurs aujourd\'hui ?',
        countedCash: 'Espèces comptées en caisse',
        verification: 'Vérification',
        openingCash: 'Fond initial',
        cashSales: 'Ventes espèces',
        cashIn: 'Entrée caisse',
        cashOut: 'Sortie caisse',
        expectedCash: 'Caisse attendue',
        countedAmount: 'Montant compté',
        difference: 'Écart',
        next: 'Suivant',
        back: 'Retour',
        confirm: 'Confirmer',
        cancel: 'Annuler',
        cashier: 'Caissier',
        switchCashier: 'Changer de caissier',
        addCashIn: 'Entrée caisse',
        addCashOut: 'Sortie caisse',
        amount: 'Montant',
        ok: 'OK',
        loading: 'Chargement…',
        offline: 'Hors-ligne — données en cache',
        retry: 'Réessayer',
        payment: 'Paiement',
        pay: 'Payer',
        single: 'Simple',
        split: 'Scindé',
        method: 'Méthode',
        addLine: 'Ajouter une ligne',
        voucher: 'Bon d\'achat',
        applyVoucher: 'Appliquer un bon',
        voucherCode: 'Code du bon',
        validate: 'Valider',
        invalidVoucher: 'Bon invalide ou expiré',
        voucherAvailable: 'Disponible',
        expires: 'Expire',
        totalDue: 'À payer',
        totalPaid: 'Payé',
        remaining: 'Reste',
        confirmPayment: 'Valider le paiement',
        paymentMismatch: 'Le total scindé ne correspond pas au total',
        emptyCart: 'Panier vide',
        syncedNow: 'Synchronisé',
        syncFailed: 'Sync échouée — réessai automatique',
        forceSync: 'Synchroniser',
    },
};

export const useI18nStore = defineStore('i18n', () => {
    const locale = ref(loadLocale());

    const t = computed(() => (key) => {
        const bundle = MESSAGES[locale.value] || MESSAGES.en;
        return bundle[key] ?? MESSAGES.en[key] ?? key;
    });

    function setLocale(next) {
        if (!MESSAGES[next]) return;
        locale.value = next;
        try { localStorage.setItem('pos_v2_locale', next); } catch { /* ignore */ }
    }

    return { locale, t, setLocale };
});

function loadLocale() {
    try {
        const stored = localStorage.getItem('pos_v2_locale');
        if (stored && MESSAGES[stored]) return stored;
    } catch { /* ignore */ }
    const nav = (navigator.language || 'en').slice(0, 2);
    return MESSAGES[nav] ? nav : 'en';
}
