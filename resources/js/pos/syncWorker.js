import { syncShifts } from './db.js';

export function startSyncWorker() {
  setInterval(syncShifts, 30_000);
}
