import Dexie from 'dexie';

export const db = new Dexie('POS_DB');

db.version(2).stores({
  products: 'id,store_id,name,price,total_stock',
  sales: '++id,product_id,quantity,total,synced',
  users: 'id,name,pin_code,store_id',
  shifts: '++id,user_id,store_id,started_at,ended_at,cash_start,cash_end,synced',
});

// --- Utilisateurs ---
export async function syncUsers() {
  try {
    const res = await fetch('/api/pos/users');
    const users = await res.json();
    if (!Array.isArray(users)) throw new Error('Users non reçus');
    await db.users.clear();
    await db.users.bulkAdd(users);
    console.log('Utilisateurs synchronisés localement');
  } catch (err) {
    console.warn('Impossible de synchroniser les utilisateurs, mode offline activé', err);
  }
}

// --- Catalogue ---
export async function syncCatalog(storeId) {
  const sid = Number(storeId);
  if (!sid) return;
  try {
    const res = await fetch(`/api/pos/catalog/${sid}`);
    const products = await res.json();
    if (!Array.isArray(products)) return;

    await db.products.where('store_id').equals(sid).delete();
    const mapped = products.map(p => ({
      id: p.id,
      store_id: sid,
      ean: p.ean,
      name: p.name,
      description: p.description,
      slugs: p.slugs,
      price: parseFloat(p.price),
      price_btob: parseFloat(p.price_btob),
      brand: p.brand,
      categories: p.categories,
      images: p.images,
      total_stock: parseInt(p.total_stock, 10),
    }));
    await db.products.bulkAdd(mapped);
    console.log(`Catalogue store ${sid} synchronisé (${mapped.length})`);
  } catch (err) {
    console.error('Erreur de synchronisation catalogue', err);
  }
}

// --- Shifts ---
export async function startShift(userId, storeId, cashStart) {
  const uid = Number(userId);
  const sid = Number(storeId);
  if (!uid || !sid) throw new Error('UserId ou StoreId invalide');
  const now = new Date().toISOString();
  const shiftId = await db.shifts.add({
    user_id: uid,
    store_id: sid,
    cash_start: Number(cashStart) || 0,
    started_at: now,
    ended_at: null,
    synced: 0
  });
  return shiftId;
}

export async function closeShift(shiftId, cashEnd) {
  const id = Number(shiftId);
  if (!id) throw new Error('ShiftId invalide');
  const shift = await db.shifts.get(id);
  if (!shift) throw new Error('Shift non trouvé');
  await db.shifts.update(id, {
    cash_end: Number(cashEnd) || 0,
    ended_at: new Date().toISOString(),
    synced: 0
  });
}

export async function getShifts(userId) {
  const uid = Number(userId);
  if (!uid) return [];
  return await db.shifts.where('user_id').equals(uid).toArray();
}

export async function syncShifts() {
  try {
    const allShifts = await db.shifts.toArray();
    const valid = allShifts.filter(s =>
      typeof s.id === 'number' &&
      typeof s.user_id === 'number' &&
      typeof s.store_id === 'number' &&
      s.synced === 0
    );
    if (!valid.length) return;

    const res = await fetch('/api/pos/shifts/sync', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ shifts: valid }),
    });
    if (res.ok) {
      await Promise.all(valid.map(s => db.shifts.update(s.id, { synced: 1 })));
      console.log(`Synchronisation shifts réussie (${valid.length})`);
    }
  } catch (err) {
    console.warn('Impossible de synchroniser shifts', err);
  }
}
