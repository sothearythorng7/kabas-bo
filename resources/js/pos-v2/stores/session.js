import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useSessionStore = defineStore('session', () => {
    const currentUser = ref(null);
    const currentShift = ref(null);
    const isOnline = ref(navigator.onLine);

    const isAuthenticated = computed(() => currentUser.value !== null);
    const hasOpenShift = computed(() => currentShift.value !== null);

    function setUser(user) {
        currentUser.value = user;
    }

    function clearUser() {
        currentUser.value = null;
    }

    function setShift(shift) {
        currentShift.value = shift;
    }

    function clearShift() {
        currentShift.value = null;
    }

    function detectOnline() {
        isOnline.value = navigator.onLine;
        window.addEventListener('online', () => { isOnline.value = true; });
        window.addEventListener('offline', () => { isOnline.value = false; });
    }

    return {
        currentUser,
        currentShift,
        isOnline,
        isAuthenticated,
        hasOpenShift,
        setUser,
        clearUser,
        setShift,
        clearShift,
        detectOnline,
    };
});
