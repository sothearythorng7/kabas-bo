import { syncShifts, syncCatalog } from './db.js';

const TWO_HOURS = 2 * 60 * 60 * 1000; // 2 heures en millisecondes

async function syncCatalogIfNeeded() {
  const storeId = localStorage.getItem('pos_store_id');
  if (storeId) {
    await syncCatalog(Number(storeId));
  }
}

export function startSyncWorker() {
  // Sync shifts toutes les 30 secondes
  setInterval(syncShifts, 30_000);

  // Sync catalogue toutes les 2 heures
  setInterval(syncCatalogIfNeeded, TWO_HOURS);
}
