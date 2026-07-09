import {
    useCallback, useEffect, useRef,
} from 'react';

function serializeStableValue(value, seen) {
    if (value == null) {
        return String(value);
    }

    if (typeof value === 'string') {
        return JSON.stringify(value);
    }

    if (typeof value === 'number' || typeof value === 'boolean') {
        return String(value);
    }

    if (typeof value === 'function') {
        return '[function]';
    }

    if (typeof File !== 'undefined' && value instanceof File) {
        return `[file:${value.name}:${value.size}:${value.type}]`;
    }

    if (typeof Blob !== 'undefined' && value instanceof Blob) {
        return `[blob:${value.size}:${value.type}]`;
    }

    if (Array.isArray(value)) {
        return `[${value.map((item) => { return serializeStableValue(item, seen); }).join(',')}]`;
    }

    if (typeof value === 'object') {
        if (seen.has(value)) {
            return '[circular]';
        }

        seen.add(value);
        const entries = Object.entries(value)
            .sort(([left], [right]) => { return left.localeCompare(right); })
            .map(([key, item]) => {
                return `${JSON.stringify(key)}:${serializeStableValue(item, seen)}`;
            });
        seen.delete(value);

        return `{${entries.join(',')}}`;
    }

    return JSON.stringify(String(value));
}

function stableSerialize(value) {
    return serializeStableValue(value, new WeakSet());
}

export function useUnloadWarning({
    enabled = true,
    autoCaptureBaseline = true,
    baselineSettleQuietMs = null,
    baselineSettleMaxMs = null,
    computeSnapshot,
    subscribe,
}) {
    const computeSnapshotRef = useRef(computeSnapshot);
    const subscribeRef = useRef(subscribe);
    const baselineRef = useRef(null);
    const readyRef = useRef(false);
    const dirtyRef = useRef(false);
    const suppressedRef = useRef(false);
    const frameRef = useRef(null);
    const timerRef = useRef(null);
    const settleQuietTimerRef = useRef(null);
    const settleMaxTimerRef = useRef(null);

    const clearScheduledWork = useCallback(() => {
        if (frameRef.current !== null) {
            window.cancelAnimationFrame(frameRef.current);
            frameRef.current = null;
        }

        if (timerRef.current !== null) {
            window.clearTimeout(timerRef.current);
            timerRef.current = null;
        }
    }, []);

    const clearSettleTimers = useCallback(() => {
        if (settleQuietTimerRef.current !== null) {
            window.clearTimeout(settleQuietTimerRef.current);
            settleQuietTimerRef.current = null;
        }

        if (settleMaxTimerRef.current !== null) {
            window.clearTimeout(settleMaxTimerRef.current);
            settleMaxTimerRef.current = null;
        }
    }, []);

    useEffect(() => {
        computeSnapshotRef.current = computeSnapshot;
    }, [computeSnapshot]);

    useEffect(() => {
        subscribeRef.current = subscribe;
    }, [subscribe]);

    const refreshDirtyState = useCallback((snapshot = null) => {
        if (!readyRef.current) {
            return false;
        }

        const nextSnapshot = snapshot ?? computeSnapshotRef.current();
        dirtyRef.current = nextSnapshot !== baselineRef.current;

        return dirtyRef.current;
    }, []);

    const captureBaseline = useCallback((snapshot = null) => {
        clearSettleTimers();
        baselineRef.current = snapshot ?? computeSnapshotRef.current();
        readyRef.current = true;
        dirtyRef.current = false;
        suppressedRef.current = false;
    }, [clearSettleTimers]);

    const scheduleBaselineCapture = useCallback((snapshot = null) => {
        clearScheduledWork();
        readyRef.current = false;

        frameRef.current = window.requestAnimationFrame(() => {
            frameRef.current = null;
            timerRef.current = window.setTimeout(() => {
                timerRef.current = null;
                captureBaseline(snapshot);
            }, 0);
        });
    }, [captureBaseline, clearScheduledWork]);

    const suppressWarning = useCallback(() => {
        suppressedRef.current = true;
    }, []);

    useEffect(() => {
        if (!enabled) {
            return undefined;
        }

        const usesSettledBaseline = Number(baselineSettleQuietMs) > 0;

        const scheduleSettledBaselineCapture = () => {
            clearSettleTimers();
            settleQuietTimerRef.current = window.setTimeout(() => {
                settleQuietTimerRef.current = null;
                captureBaseline();
            }, baselineSettleQuietMs);
        };

        if (usesSettledBaseline) {
            scheduleSettledBaselineCapture();

            if (Number(baselineSettleMaxMs) > 0) {
                settleMaxTimerRef.current = window.setTimeout(() => {
                    settleMaxTimerRef.current = null;
                    captureBaseline();
                }, baselineSettleMaxMs);
            }
        } else if (autoCaptureBaseline) {
            scheduleBaselineCapture();
        }

        const unsubscribe = typeof subscribeRef.current === 'function'
            ? subscribeRef.current(() => {
                if (suppressedRef.current) {
                    suppressedRef.current = false;
                }

                if (usesSettledBaseline && !readyRef.current) {
                    scheduleSettledBaselineCapture();
                    return;
                }

                if (timerRef.current !== null) {
                    window.clearTimeout(timerRef.current);
                }

                timerRef.current = window.setTimeout(() => {
                    timerRef.current = null;
                    refreshDirtyState();
                }, 120);
            })
            : undefined;

        const handleBeforeUnload = (event) => {
            if (suppressedRef.current || !readyRef.current) {
                return;
            }

            if (!refreshDirtyState()) {
                return;
            }

            event.preventDefault();
            event.returnValue = '';
        };

        window.addEventListener('beforeunload', handleBeforeUnload);

        return () => {
            clearScheduledWork();
            clearSettleTimers();
            window.removeEventListener('beforeunload', handleBeforeUnload);

            if (typeof unsubscribe === 'function') {
                unsubscribe();
            }
        };
    }, [
        autoCaptureBaseline,
        baselineSettleMaxMs,
        baselineSettleQuietMs,
        captureBaseline,
        clearScheduledWork,
        clearSettleTimers,
        enabled,
        refreshDirtyState,
        scheduleBaselineCapture,
    ]);

    return {
        captureBaseline,
        refreshDirtyState,
        scheduleBaselineCapture,
        suppressWarning,
        recaptureBaseline: captureBaseline,
        isDirty: () => {
            return dirtyRef.current;
        },
    };
}

export { stableSerialize };
