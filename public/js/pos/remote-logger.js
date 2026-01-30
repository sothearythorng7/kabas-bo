/**
 * POS Remote Logger
 * Sends client-side logs to the server for debugging UI freezes
 */
(function(window) {
    'use strict';

    const LOG_ENDPOINT = '/api/pos/logs';
    const FLUSH_INTERVAL = 10000; // 10 seconds
    const MAX_QUEUE_SIZE = 50;
    const MAX_RETRIES = 3;

    let logQueue = [];
    let flushTimer = null;
    let isOnline = navigator.onLine;
    let deviceInfo = null;
    let sessionId = null;

    // Generate unique session ID
    function generateSessionId() {
        return 'pos_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    // Collect device info once
    function collectDeviceInfo() {
        return {
            userAgent: navigator.userAgent,
            platform: navigator.platform,
            language: navigator.language,
            screenWidth: screen.width,
            screenHeight: screen.height,
            viewportWidth: window.innerWidth,
            viewportHeight: window.innerHeight,
            devicePixelRatio: window.devicePixelRatio,
            touchPoints: navigator.maxTouchPoints,
            online: navigator.onLine,
            sessionId: sessionId
        };
    }

    // Format timestamp
    function getTimestamp() {
        return new Date().toISOString();
    }

    // Add log to queue
    function addLog(level, message, context) {
        const logEntry = {
            level: level,
            message: message,
            context: context || {},
            timestamp: getTimestamp()
        };

        // Add to queue
        logQueue.push(logEntry);

        // Also log to console for local debugging
        const consoleMethod = level === 'error' || level === 'critical' ? 'error'
            : level === 'warn' ? 'warn'
            : 'log';
        console[consoleMethod](`[POS:${level.toUpperCase()}]`, message, context || '');

        // Flush immediately on critical errors
        if (level === 'critical' || level === 'error') {
            flush();
        }

        // Prevent queue overflow
        if (logQueue.length > MAX_QUEUE_SIZE) {
            flush();
        }
    }

    // Send logs to server
    async function flush(retryCount = 0) {
        if (logQueue.length === 0) return;
        if (!isOnline) {
            // Store in localStorage for later
            storeOfflineLogs();
            return;
        }

        const logsToSend = [...logQueue];
        logQueue = [];

        try {
            const response = await fetch(LOG_ENDPOINT, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    logs: logsToSend,
                    device_info: deviceInfo
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
        } catch (err) {
            // Put logs back in queue
            logQueue = logsToSend.concat(logQueue);

            if (retryCount < MAX_RETRIES) {
                setTimeout(() => flush(retryCount + 1), 5000 * (retryCount + 1));
            } else {
                storeOfflineLogs();
            }
        }
    }

    // Store logs offline when network unavailable
    function storeOfflineLogs() {
        if (logQueue.length === 0) return;

        try {
            const stored = JSON.parse(localStorage.getItem('pos_offline_logs') || '[]');
            const combined = stored.concat(logQueue);
            // Keep only last 200 offline logs
            const trimmed = combined.slice(-200);
            localStorage.setItem('pos_offline_logs', JSON.stringify(trimmed));
            logQueue = [];
        } catch (e) {
            console.error('Failed to store offline logs:', e);
        }
    }

    // Send stored offline logs
    function sendOfflineLogs() {
        try {
            const stored = JSON.parse(localStorage.getItem('pos_offline_logs') || '[]');
            if (stored.length > 0) {
                logQueue = stored.concat(logQueue);
                localStorage.removeItem('pos_offline_logs');
                flush();
            }
        } catch (e) {
            console.error('Failed to retrieve offline logs:', e);
        }
    }

    // Get CSRF token
    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    // Track UI freeze indicators
    function setupFreezeDetection() {
        let lastHeartbeat = Date.now();
        let freezeThreshold = 3000; // 3 seconds without heartbeat = potential freeze

        // Heartbeat every second
        setInterval(() => {
            const now = Date.now();
            const elapsed = now - lastHeartbeat;

            if (elapsed > freezeThreshold) {
                addLog('critical', 'UI_FREEZE_DETECTED', {
                    elapsed_ms: elapsed,
                    threshold_ms: freezeThreshold,
                    visible_modals: getVisibleModals(),
                    active_element: getActiveElementInfo(),
                    body_classes: document.body.className
                });
            }

            lastHeartbeat = now;
        }, 1000);
    }

    // Get info about visible modals/overlays
    function getVisibleModals() {
        const modals = [];

        // Bootstrap modals
        document.querySelectorAll('.modal.show, .modal[style*="display: block"]').forEach(el => {
            modals.push({
                id: el.id,
                class: el.className,
                backdrop: !!document.querySelector('.modal-backdrop')
            });
        });

        // Check for modal backdrops without corresponding modals
        const backdrops = document.querySelectorAll('.modal-backdrop');
        if (backdrops.length > 0) {
            modals.push({
                type: 'orphan_backdrop',
                count: backdrops.length
            });
        }

        // Virtual keyboard
        const vk = document.querySelector('.virtual-keyboard-overlay');
        if (vk && !vk.classList.contains('d-none') && !vk.style.transform.includes('100%')) {
            modals.push({
                type: 'virtual_keyboard',
                visible: true
            });
        }

        // Side menu
        const sideMenu = document.getElementById('side-menu');
        if (sideMenu && parseInt(sideMenu.style.width) > 0) {
            modals.push({
                type: 'side_menu',
                width: sideMenu.style.width
            });
        }

        return modals;
    }

    // Get info about active/focused element
    function getActiveElementInfo() {
        const el = document.activeElement;
        if (!el) return null;

        return {
            tag: el.tagName,
            id: el.id,
            class: el.className,
            type: el.type || null
        };
    }

    // Track touch events
    function setupTouchTracking() {
        let touchStartTime = 0;
        let touchStartTarget = null;

        document.addEventListener('touchstart', (e) => {
            touchStartTime = Date.now();
            touchStartTarget = e.target;

            addLog('debug', 'TOUCH_START', {
                target_tag: e.target.tagName,
                target_id: e.target.id,
                target_class: e.target.className.substring(0, 100),
                touch_count: e.touches.length,
                x: e.touches[0]?.clientX,
                y: e.touches[0]?.clientY
            });
        }, { passive: true });

        document.addEventListener('touchend', (e) => {
            const duration = Date.now() - touchStartTime;

            // Log if touch took suspiciously long to complete
            if (duration > 500) {
                addLog('warn', 'SLOW_TOUCH_RESPONSE', {
                    duration_ms: duration,
                    start_target: touchStartTarget ? {
                        tag: touchStartTarget.tagName,
                        id: touchStartTarget.id
                    } : null,
                    end_target: e.target ? {
                        tag: e.target.tagName,
                        id: e.target.id
                    } : null
                });
            }
        }, { passive: true });

        // Track when touches seem to be ignored
        document.addEventListener('click', (e) => {
            addLog('debug', 'CLICK_EVENT', {
                target_tag: e.target.tagName,
                target_id: e.target.id,
                target_class: e.target.className.substring(0, 100),
                is_trusted: e.isTrusted
            });
        }, { passive: true });
    }

    // Track network sync operations
    function wrapFetch() {
        const originalFetch = window.fetch;

        window.fetch = async function(...args) {
            const url = typeof args[0] === 'string' ? args[0] : args[0]?.url;
            const startTime = Date.now();

            // Only log POS API calls
            if (url && url.includes('/api/pos/')) {
                addLog('info', 'FETCH_START', {
                    url: url,
                    method: args[1]?.method || 'GET'
                });
            }

            try {
                const response = await originalFetch.apply(this, args);
                const duration = Date.now() - startTime;

                if (url && url.includes('/api/pos/')) {
                    const level = duration > 5000 ? 'warn' : 'info';
                    addLog(level, 'FETCH_COMPLETE', {
                        url: url,
                        status: response.status,
                        duration_ms: duration,
                        slow: duration > 5000
                    });
                }

                return response;
            } catch (err) {
                const duration = Date.now() - startTime;

                if (url && url.includes('/api/pos/')) {
                    addLog('error', 'FETCH_ERROR', {
                        url: url,
                        error: err.message,
                        duration_ms: duration
                    });
                }

                throw err;
            }
        };
    }

    // Track modal operations
    function setupModalTracking() {
        // Track Bootstrap modal events
        document.addEventListener('show.bs.modal', (e) => {
            addLog('info', 'MODAL_SHOW', {
                modal_id: e.target.id,
                modal_class: e.target.className
            });
        });

        document.addEventListener('shown.bs.modal', (e) => {
            addLog('info', 'MODAL_SHOWN', {
                modal_id: e.target.id,
                backdrop_present: !!document.querySelector('.modal-backdrop')
            });
        });

        document.addEventListener('hide.bs.modal', (e) => {
            addLog('info', 'MODAL_HIDE', {
                modal_id: e.target.id
            });
        });

        document.addEventListener('hidden.bs.modal', (e) => {
            addLog('info', 'MODAL_HIDDEN', {
                modal_id: e.target.id,
                backdrop_remaining: !!document.querySelector('.modal-backdrop')
            });

            // Alert if backdrop is still present after modal hidden AND clean it up
            setTimeout(() => {
                cleanupOrphanedModals(e.target.id);
            }, 500);
        });
    }

    // Nettoyage automatique des modals orphelins
    function cleanupOrphanedModals(triggeredBy = null) {
        const orphanBackdrops = document.querySelectorAll('.modal-backdrop');
        const visibleModals = document.querySelectorAll('.modal.show');

        // S'il y a des backdrops mais pas de modals visibles, nettoyer
        if (orphanBackdrops.length > 0 && visibleModals.length === 0) {
            addLog('warn', 'CLEANING_ORPHAN_BACKDROPS', {
                triggered_by: triggeredBy,
                backdrop_count: orphanBackdrops.length
            });

            orphanBackdrops.forEach(backdrop => backdrop.remove());

            // Aussi nettoyer la classe modal-open sur body
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');

            addLog('info', 'ORPHAN_CLEANUP_COMPLETE', {
                triggered_by: triggeredBy
            });

            return true;
        }

        return false;
    }

    // Vérification périodique des orphelins (toutes les 30s)
    setInterval(() => {
        const cleaned = cleanupOrphanedModals('periodic_check');
        if (cleaned) {
            addLog('critical', 'ORPHAN_BACKDROP_DETECTED_PERIODIC', {
                message: 'Periodic cleanup found and removed orphan backdrops'
            });
        }
    }, 30000);

    // Track JavaScript errors
    function setupErrorTracking() {
        window.addEventListener('error', (e) => {
            addLog('error', 'JS_ERROR', {
                message: e.message,
                filename: e.filename,
                lineno: e.lineno,
                colno: e.colno,
                stack: e.error?.stack?.substring(0, 500)
            });
        });

        window.addEventListener('unhandledrejection', (e) => {
            addLog('error', 'UNHANDLED_PROMISE', {
                reason: e.reason?.message || String(e.reason).substring(0, 200),
                stack: e.reason?.stack?.substring(0, 500)
            });
        });
    }

    // Track online/offline status
    function setupNetworkTracking() {
        window.addEventListener('online', () => {
            isOnline = true;
            addLog('info', 'NETWORK_ONLINE', {});
            sendOfflineLogs();
        });

        window.addEventListener('offline', () => {
            isOnline = false;
            addLog('warn', 'NETWORK_OFFLINE', {});
        });
    }

    // Track page visibility (can indicate background issues)
    function setupVisibilityTracking() {
        document.addEventListener('visibilitychange', () => {
            addLog('info', 'VISIBILITY_CHANGE', {
                hidden: document.hidden,
                visibility_state: document.visibilityState
            });
        });
    }

    // Public API
    window.POSLogger = {
        debug: (msg, ctx) => addLog('debug', msg, ctx),
        info: (msg, ctx) => addLog('info', msg, ctx),
        warn: (msg, ctx) => addLog('warn', msg, ctx),
        error: (msg, ctx) => addLog('error', msg, ctx),
        critical: (msg, ctx) => addLog('critical', msg, ctx),

        // Manual flush
        flush: flush,

        // Get current queue size
        queueSize: () => logQueue.length,

        // Report UI state for debugging
        reportUIState: () => {
            addLog('info', 'UI_STATE_REPORT', {
                visible_modals: getVisibleModals(),
                active_element: getActiveElementInfo(),
                body_classes: document.body.className,
                scroll_y: window.scrollY,
                online: navigator.onLine
            });
        }
    };

    // Initialize
    function init() {
        sessionId = generateSessionId();
        deviceInfo = collectDeviceInfo();

        addLog('info', 'POS_LOGGER_INIT', {
            session_id: sessionId,
            url: window.location.href
        });

        // Setup all tracking
        setupFreezeDetection();
        setupTouchTracking();
        setupModalTracking();
        setupErrorTracking();
        setupNetworkTracking();
        setupVisibilityTracking();
        wrapFetch();

        // Periodic flush
        flushTimer = setInterval(flush, FLUSH_INTERVAL);

        // Flush on page unload
        window.addEventListener('beforeunload', () => {
            flush();
        });

        // Send any stored offline logs
        if (isOnline) {
            sendOfflineLogs();
        }
    }

    // Start when DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})(window);
