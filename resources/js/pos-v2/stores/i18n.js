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
