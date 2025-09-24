import { defineStore } from 'pinia';
import { db, syncCatalog } from '../db.js';

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
      const shift = await db.shifts.where({ user_id: u.id, ended_at: null }).first();
      this.activeShift = shift || null;
      await syncCatalog(u.store_id);
      return true;
    },
    setActiveShift(shift) { this.activeShift = shift },
    updateLastActive() { this.lastActive = Date.now() }
  }
});
