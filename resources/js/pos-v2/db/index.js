import Dexie from 'dexie';
import { registerSchema } from './tables.js';

export const db = new Dexie('kabas_pos_v2');
registerSchema(db);
