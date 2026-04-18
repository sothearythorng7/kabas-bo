import { defineStore } from 'pinia';
import { db, syncCatalog, syncActiveEvents, startShift as dbStartShift } from '../db.js';

export const useUserStore = defineStore('user', {
  state: () => ({
    user: null,
    lastActive: Date.now(),
    activeShift: null
  }),
  getters: {
    isLocked: state => !state.user || (Date.now() - state.lastActive > 5*60*1000)
  },
  actions: {
    async verifyPin(pin) {
      const u = await db.users.where('pin_code').equals(pin).first();
      if (!u) return false;
      this.user = u;
      this.lastActive = Date.now();
      localStorage.setItem('pos_store_id', u.store_id);
      const shift = await db.shifts.where({ user_id: u.id, ended_at: null }).first();
      this.activeShift = shift || null;
      return true;
    },
    async refreshCatalog() {
      const storeId = this.user?.store_id || localStorage.getItem('pos_store_id');
      if (!storeId) return false;
      try {
        await syncCatalog(Number(storeId));
        return true;
      } catch {
        return false;
      }
    },
    async startShift(cashStart, eventId = null) {
      if (!this.user) return null;
      const shiftId = await dbStartShift(this.user.id, this.user.store_id, cashStart, eventId);
      const shift = await db.shifts.get(shiftId);
      this.activeShift = shift;
      return shift;
    },
    async refreshEvents() {
      const storeId = this.user?.store_id || localStorage.getItem('pos_store_id');
      if (!storeId) return;
      await syncActiveEvents(Number(storeId));
    },
    setActiveShift(shift) { this.activeShift = shift },
    updateLastActive() { this.lastActive = Date.now() }
  }
});
