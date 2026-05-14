/**
 * Generate a unique gift card code matching the V1 format.
 *
 * V1 contract:
 *   - 3 groups of 4 characters separated by hyphens, prefixed with "GIFT-"
 *   - Alphabet excludes ambiguous characters: 0, O, 1, I.
 *   - Final shape: GIFT-XXXX-XXXX-XXXX
 */
const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

function randomSegment(len = 4) {
    let out = '';
    const arr = new Uint8Array(len);
    crypto.getRandomValues(arr);
    for (let i = 0; i < len; i++) {
        out += ALPHABET[arr[i] % ALPHABET.length];
    }
    return out;
}

export function generateGiftCardCode() {
    return `GIFT-${randomSegment(4)}-${randomSegment(4)}-${randomSegment(4)}`;
}
