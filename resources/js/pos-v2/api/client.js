/**
 * fetch wrapper for the POS V2 API layer.
 *
 * Conventions:
 *   - Reads CSRF token from <meta name="csrf-token"> (rendered by Blade host).
 *   - 30s default timeout via AbortController (matches V1 sync behaviour).
 *   - Throws ApiError on non-2xx so callers can catch/branch.
 *   - Network failures throw with `code: 'network'` to differentiate offline.
 */

const DEFAULT_TIMEOUT_MS = 30_000;

export class ApiError extends Error {
    constructor(message, { status, code, body } = {}) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.code = code;
        this.body = body;
    }
}

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : null;
}

export async function apiFetch(path, { method = 'GET', body, timeoutMs = DEFAULT_TIMEOUT_MS, headers = {} } = {}) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), timeoutMs);

    const finalHeaders = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...headers,
    };
    const csrf = getCsrfToken();
    if (csrf) finalHeaders['X-CSRF-TOKEN'] = csrf;

    let response;
    try {
        response = await fetch(path, {
            method,
            headers: finalHeaders,
            body: body !== undefined ? JSON.stringify(body) : undefined,
            signal: controller.signal,
            credentials: 'same-origin',
        });
    } catch (err) {
        clearTimeout(timer);
        if (err.name === 'AbortError') {
            throw new ApiError('Request timed out', { code: 'timeout' });
        }
        throw new ApiError(err.message || 'Network error', { code: 'network' });
    } finally {
        clearTimeout(timer);
    }

    let payload = null;
    const ctype = response.headers.get('content-type') || '';
    if (ctype.includes('application/json')) {
        try { payload = await response.json(); } catch { /* ignore */ }
    }

    if (!response.ok) {
        throw new ApiError(
            payload?.message || `HTTP ${response.status}`,
            { status: response.status, code: 'http', body: payload }
        );
    }

    return payload;
}

export const api = {
    get: (path, opts) => apiFetch(path, { ...opts, method: 'GET' }),
    post: (path, body, opts) => apiFetch(path, { ...opts, method: 'POST', body }),
};
