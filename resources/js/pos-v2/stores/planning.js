import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import {
    fetchUserPlanning,
    fetchUserLeaves,
    fetchUserLeaveBalance,
    requestLeave as apiRequestLeave,
} from '../api/endpoints/planning.js';

/**
 * Planning store — user's shift calendar, leaves, and balance.
 *
 * The backend response shapes vary slightly (some controllers wrap under
 * { planning: [...] }, others return the array directly), so we normalise
 * via `extractList` and index by ISO date for fast cell lookup.
 */
export const usePlanningStore = defineStore('planning', () => {
    const loading = ref(false);
    const error = ref('');

    const plannedShifts = ref([]); // [{ date: 'YYYY-MM-DD', store_id, start_time, end_time, ... }]
    const leaves = ref([]);         // [{ id, date_start, date_end, type, status }]
    const balance = ref(null);      // { available_days, used_days, pending_requests }

    function extractList(payload, ...candidateKeys) {
        if (Array.isArray(payload)) return payload;
        for (const k of candidateKeys) {
            if (Array.isArray(payload?.[k])) return payload[k];
        }
        return [];
    }

    function isoDay(d) {
        if (!d) return null;
        if (typeof d === 'string' && d.length >= 10) return d.slice(0, 10);
        try {
            const dt = new Date(d);
            const y = dt.getFullYear();
            const m = String(dt.getMonth() + 1).padStart(2, '0');
            const day = String(dt.getDate()).padStart(2, '0');
            return `${y}-${m}-${day}`;
        } catch {
            return null;
        }
    }

    async function load(userId) {
        if (!userId) return;
        loading.value = true;
        error.value = '';
        try {
            const [planRes, leavesRes, balRes] = await Promise.all([
                fetchUserPlanning(userId).catch(() => null),
                fetchUserLeaves(userId).catch(() => null),
                fetchUserLeaveBalance(userId).catch(() => null),
            ]);
            plannedShifts.value = extractList(planRes, 'planning', 'shifts', 'data');
            leaves.value = extractList(leavesRes, 'leaves', 'data');
            balance.value = balRes && typeof balRes === 'object'
                ? (balRes.balance || balRes.data || balRes)
                : null;
        } catch (err) {
            console.error('[POS V2] planning load failed', err);
            error.value = err.message || 'Load failed';
        } finally {
            loading.value = false;
        }
    }

    /** Map ISO date → { shift?, leave? } for fast cell rendering. */
    const dayIndex = computed(() => {
        const out = new Map();
        for (const s of plannedShifts.value) {
            const day = isoDay(s.date || s.day || s.start_time);
            if (!day) continue;
            const slot = out.get(day) || {};
            slot.shift = s;
            out.set(day, slot);
        }
        for (const l of leaves.value) {
            const start = isoDay(l.date_start || l.start_date || l.date);
            const end = isoDay(l.date_end || l.end_date || start);
            if (!start) continue;
            const cursor = new Date(start);
            const stop = new Date(end);
            while (cursor.getTime() <= stop.getTime()) {
                const day = isoDay(cursor);
                const slot = out.get(day) || {};
                slot.leave = l;
                out.set(day, slot);
                cursor.setDate(cursor.getDate() + 1);
            }
        }
        return out;
    });

    function dayInfo(isoDate) {
        return dayIndex.value.get(isoDate) || null;
    }

    /**
     * Check whether a date range collides with an existing leave for the
     * current user. Returns the first conflicting leave or null.
     */
    function findConflict(dateStart, dateEnd) {
        const a = new Date(dateStart);
        const b = new Date(dateEnd);
        if (isNaN(a.getTime()) || isNaN(b.getTime())) return null;
        return leaves.value.find((l) => {
            const ls = new Date(l.date_start || l.start_date || l.date);
            const le = new Date(l.date_end || l.end_date || ls);
            if (isNaN(ls.getTime())) return false;
            return ls <= b && le >= a; // ranges overlap
        }) || null;
    }

    async function submitLeave({ userId, dateStart, dateEnd, type, reason }) {
        const conflict = findConflict(dateStart, dateEnd);
        if (conflict) {
            return { ok: false, conflict };
        }
        try {
            const res = await apiRequestLeave({
                user_id: userId,
                date_start: dateStart,
                date_end: dateEnd,
                type,
                reason: reason || null,
            });
            // Optimistically append so the calendar reflects the request.
            if (res?.id || res?.leave?.id) {
                leaves.value.push(res.leave || res);
            }
            return { ok: true, leave: res };
        } catch (err) {
            return { ok: false, error: err.message || 'Submit failed' };
        }
    }

    return {
        loading,
        error,
        plannedShifts,
        leaves,
        balance,
        dayIndex,
        dayInfo,
        load,
        submitLeave,
        findConflict,
    };
});
