import { onMounted, onBeforeUnmount } from 'vue';

/**
 * Detects barcode scanner input (HID keyboard wedge mode).
 *
 * Heuristic (matches typical Zebra / Honeywell scanners):
 *   - A series of keypresses arrives faster than humans can type.
 *   - The sequence ends with Enter.
 *   - Total length ≥ 4 chars qualifies as a scan.
 *
 * Skips events fired from inside form fields so manual typing is not hijacked.
 *
 * Usage:
 *   useBarcodeScanner({ onScan: (code) => cart.addByBarcode(code) });
 */
export function useBarcodeScanner({ onScan, minLength = 4, maxIntervalMs = 60 } = {}) {
    let buffer = '';
    let lastKeyAt = 0;

    function handleKey(ev) {
        // Ignore events from inputs/textareas/contenteditable so manual typing isn't captured.
        const target = ev.target;
        if (target && target.matches && target.matches('input, textarea, [contenteditable=""], [contenteditable="true"]')) {
            return;
        }

        const now = performance.now();
        const interval = now - lastKeyAt;
        lastKeyAt = now;

        if (ev.key === 'Enter') {
            if (buffer.length >= minLength) {
                const code = buffer;
                buffer = '';
                if (typeof onScan === 'function') {
                    onScan(code);
                    ev.preventDefault();
                }
            } else {
                buffer = '';
            }
            return;
        }

        // Reset buffer if too slow (likely human typing on a hot key)
        if (interval > maxIntervalMs * 4) {
            buffer = '';
        }

        if (ev.key.length === 1 && /^[\w\-]$/.test(ev.key)) {
            buffer += ev.key;
        }
    }

    onMounted(() => window.addEventListener('keydown', handleKey, true));
    onBeforeUnmount(() => window.removeEventListener('keydown', handleKey, true));
}
