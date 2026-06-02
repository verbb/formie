const DIRTY_TRACKING_IGNORED_FIELD_NAMES = new Set([
    'CRAFT_CSRF_TOKEN',
    'action',
    'redirect',
    'requestToken',
    'renderId',
    'submitAction',
    'pageId',
    'draftContextToken',
    'draftContext',
    'continuationToken',
]);

function serializeStableValue(value: unknown, seen: WeakSet<object>): string {
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
        return `[${value.map((item) => serializeStableValue(item, seen)).join(',')}]`;
    }

    if (typeof value === 'object') {
        if (seen.has(value as object)) {
            return '[circular]';
        }

        seen.add(value as object);
        const entries = Object.entries(value as Record<string, unknown>)
            .sort(([left], [right]) => left.localeCompare(right))
            .map(([key, item]) => {
                return `${JSON.stringify(key)}:${serializeStableValue(item, seen)}`;
            });
        seen.delete(value as object);

        return `{${entries.join(',')}}`;
    }

    return JSON.stringify(String(value));
}

function stableSerialize(value: unknown): string {
    return serializeStableValue(value, new WeakSet<object>());
}

function shouldTrackFieldName(name: string): boolean {
    if (!name) {
        return false;
    }

    const normalizedName = name.endsWith('[]') ? name.slice(0, -2) : name;

    return !DIRTY_TRACKING_IGNORED_FIELD_NAMES.has(normalizedName);
}

function buildTrackedSnapshot(form: HTMLFormElement): string {
    const entries = Array.from(new FormData(form).entries()).filter(([name]) => {
        return shouldTrackFieldName(String(name || ''));
    });

    return stableSerialize(entries);
}

export type FormUnloadWarningGuard = {
    captureBaseline: () => void;
    scheduleBaselineCapture: () => void;
    refreshDirtyState: () => boolean;
    destroy: () => void;
};

export function createFormUnloadWarningGuard(
    form: HTMLFormElement,
    options: {
        shouldWarn?: () => boolean;
    } = {},
): FormUnloadWarningGuard {
    let baselineSnapshot: string | null = null;
    let isReady = false;
    let isDirty = false;
    let animationFrameId: number | null = null;
    let dirtyTimerId: number | null = null;
    let baselineTimerId: number | null = null;

    const clearScheduledWork = (): void => {
        if (animationFrameId !== null) {
            window.cancelAnimationFrame(animationFrameId);
            animationFrameId = null;
        }

        if (dirtyTimerId !== null) {
            window.clearTimeout(dirtyTimerId);
            dirtyTimerId = null;
        }

        if (baselineTimerId !== null) {
            window.clearTimeout(baselineTimerId);
            baselineTimerId = null;
        }
    };

    const refreshDirtyState = (): boolean => {
        if (!isReady) {
            return false;
        }

        isDirty = buildTrackedSnapshot(form) !== baselineSnapshot;

        return isDirty;
    };

    const captureBaseline = (): void => {
        baselineSnapshot = buildTrackedSnapshot(form);
        isReady = true;
        isDirty = false;
    };

    const scheduleBaselineCapture = (): void => {
        clearScheduledWork();
        isReady = false;

        animationFrameId = window.requestAnimationFrame(() => {
            animationFrameId = null;
            baselineTimerId = window.setTimeout(() => {
                baselineTimerId = null;
                captureBaseline();
            }, 0);
        });
    };

    const scheduleDirtyRefresh = (): void => {
        if (dirtyTimerId !== null) {
            window.clearTimeout(dirtyTimerId);
        }

        dirtyTimerId = window.setTimeout(() => {
            dirtyTimerId = null;
            refreshDirtyState();
        }, 120);
    };

    const handleBeforeUnload = (event: BeforeUnloadEvent): void => {
        if (options.shouldWarn && !options.shouldWarn()) {
            return;
        }

        if (!refreshDirtyState()) {
            return;
        }

        event.preventDefault();
        event.returnValue = '';
    };

    form.addEventListener('input', scheduleDirtyRefresh);
    form.addEventListener('change', scheduleDirtyRefresh);
    window.addEventListener('beforeunload', handleBeforeUnload);
    scheduleBaselineCapture();

    return {
        captureBaseline,
        scheduleBaselineCapture,
        refreshDirtyState,
        destroy: () => {
            clearScheduledWork();
            form.removeEventListener('input', scheduleDirtyRefresh);
            form.removeEventListener('change', scheduleDirtyRefresh);
            window.removeEventListener('beforeunload', handleBeforeUnload);
        },
    };
}
