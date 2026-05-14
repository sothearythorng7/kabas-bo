import { api } from '../client.js';

export function fetchTodayAbsences(storeId) {
    return api.get(`/api/pos/planning/today-absences/${storeId}`);
}

export function fetchUserPlanning(userId) {
    return api.get(`/api/pos/planning/user-planning/${userId}`);
}

export function fetchUserLeaves(userId) {
    return api.get(`/api/pos/planning/user-leaves/${userId}`);
}

export function fetchUserLeaveBalance(userId) {
    return api.get(`/api/pos/planning/user-balance/${userId}`);
}

export function fetchStoreStaff(storeId) {
    return api.get(`/api/pos/planning/staff/${storeId}`);
}

export function requestLeave(payload) {
    return api.post('/api/pos/planning/request-leave', payload);
}
