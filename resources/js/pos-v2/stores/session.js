import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { db } from '../db/index.js';
import { fetchUsers } from '../api/endpoints/users.js';
import { fetchCurrentShift } from '../api/endpoints/shifts.js';

/**
 * Session lifecycle:
 *  1. Boot → `loadUsers()` refreshes the local cache from `/api/pos/users`.
 *  2. PIN entry → `signInWithPin()` matches against the local cache.
 *  3. On success → `checkShift()` calls `/api/pos/shifts/current/{userId}` to
 *     decide whether to route to shift-start or dashboard.
 *  4. Logout → `signOut()` clears the current user (but keeps users cache).
 */
export const useSessionStore = defineStore('session', () => {
    const currentUser = ref(null);
    const currentShift = ref(null);
    const isOnline = ref(navigator.onLine);
    const usersLoaded = ref(false);
    const lastError = ref(null);

    const isAuthenticated = computed(() => currentUser.value !== null);
    const hasOpenShift = computed(() => currentShift.value !== null);

    function detectOnline() {
        isOnline.value = navigator.onLine;
        window.addEventListener('online', () => { isOnline.value = true; });
        window.addEventListener('offline', () => { isOnline.value = false; });
    }

    async function loadUsers({ force = false } = {}) {
        try {
            const count = await db.table('users').count();
            if (count > 0 && !force) {
                usersLoaded.value = true;
                if (isOnline.value) {
                    refreshUsersInBackground();
                }
                return;
            }
            await refreshUsers();
        } catch (err) {
            lastError.value = err.message;
            console.warn('[POS V2] users load failed, relying on cache', err);
        }
    }

    async function refreshUsers() {
        const list = await fetchUsers();
        if (!Array.isArray(list)) return;
        await db.transaction('rw', db.table('users'), async () => {
            await db.table('users').clear();
            await db.table('users').bulkPut(list);
        });
        usersLoaded.value = true;
    }

    function refreshUsersInBackground() {
        refreshUsers().catch((err) => {
            console.warn('[POS V2] background users refresh failed', err);
        });
    }

    async function signInWithPin(pin) {
        lastError.value = null;
        const trimmed = (pin || '').trim();
        if (!trimmed) {
            lastError.value = 'invalidPin';
            return { ok: false };
        }
        const match = await db.table('users').where('pin_code').equals(trimmed).first();
        if (!match) {
            lastError.value = 'invalidPin';
            return { ok: false };
        }
        currentUser.value = match;
        return { ok: true, user: match };
    }

    async function checkShift(userId) {
        const id = userId ?? currentUser.value?.id;
        if (!id) return null;
        try {
            const shift = await fetchCurrentShift(id);
            if (shift && shift.id) {
                currentShift.value = shift;
                return shift;
            }
            currentShift.value = null;
            return null;
        } catch (err) {
            console.warn('[POS V2] checkShift failed', err);
            // Offline fallback: assume no shift; the user will start one offline if needed.
            return null;
        }
    }

    function setShift(shift) {
        currentShift.value = shift;
    }

    function clearShift() {
        currentShift.value = null;
    }

    function signOut() {
        currentUser.value = null;
        currentShift.value = null;
    }

    return {
        currentUser,
        currentShift,
        isOnline,
        usersLoaded,
        lastError,
        isAuthenticated,
        hasOpenShift,
        detectOnline,
        loadUsers,
        refreshUsers,
        signInWithPin,
        checkShift,
        setShift,
        clearShift,
        signOut,
    };
});
