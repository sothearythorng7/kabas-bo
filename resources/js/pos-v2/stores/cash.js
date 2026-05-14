import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { db } from '../db/index.js';

/**
 * Tracks cash_in and cash_out cumulative amounts per shift.
 * V1 stored these in localStorage keys `pos_cash_in_shift_X` / `pos_cash_out_shift_X`.
 * V2 stores them in Dexie `settings` table under the same key conventions.
 * The shift end payload reads both totals.
 */
export const useCashStore = defineStore('cash', () => {
    const cashIn = ref(0);
    const cashOut = ref(0);
    const cashSalesFromSales = ref(0); // computed by sales store; injected here for expected cash

    function keyIn(shiftId) {
        return `cash_in_shift_${shiftId}`;
    }
    function keyOut(shiftId) {
        return `cash_out_shift_${shiftId}`;
    }

    async function loadForShift(shiftId) {
        if (!shiftId) {
            cashIn.value = 0;
            cashOut.value = 0;
            return;
        }
        const [inRow, outRow] = await Promise.all([
            db.table('settings').get(keyIn(shiftId)),
            db.table('settings').get(keyOut(shiftId)),
        ]);
        cashIn.value = inRow ? Number(inRow.value) || 0 : 0;
        cashOut.value = outRow ? Number(outRow.value) || 0 : 0;
    }

    async function addCashIn(shiftId, amount) {
        cashIn.value = round2(cashIn.value + amount);
        await db.table('settings').put({ key: keyIn(shiftId), value: cashIn.value });
    }

    async function addCashOut(shiftId, amount) {
        cashOut.value = round2(cashOut.value + amount);
        await db.table('settings').put({ key: keyOut(shiftId), value: cashOut.value });
    }

    function setCashSales(amount) {
        cashSalesFromSales.value = Number(amount) || 0;
    }

    function reset() {
        cashIn.value = 0;
        cashOut.value = 0;
        cashSalesFromSales.value = 0;
    }

    return {
        cashIn,
        cashOut,
        cashSalesFromSales,
        loadForShift,
        addCashIn,
        addCashOut,
        setCashSales,
        reset,
    };
});

function round2(v) {
    return Math.round(v * 100) / 100;
}
