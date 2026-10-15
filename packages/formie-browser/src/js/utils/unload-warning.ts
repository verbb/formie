import { isFormCsrfFieldName } from '#utils/csrf';
import { dispatchFormieDomEvent } from '#core/dom-events';

const DIRTY_TRACKING_IGNORED_FIELD_NAMES = new Set([
    'action',
    'redirect',
    'requestToken',
    'uploadCreateToken',
    'renderId',
    'formStartedAt',
    'submitAction',
    'pageId',
    'draftContextToken',
    'draftContext',
    'progressId',
]);

type FormSnapshot = Array<[string, FormDataEntryValue]>;

export type FormUnloadWarningExclusionsEventDetail = {
    readonly form: HTMLFormElement;
    readonly exclude: (...fieldNames: string[]) => void;
};

function collectExcludedFieldNames(form: HTMLFormElement): Set<string> {
    const excludedFieldNames = new Set<string>();
    let collecting = true;

    dispatchFormieDomEvent(form, 'formie:unload-warning:collect-exclusions', {
        form,
        exclude: (...fieldNames: string[]) => {
            // Exclusions belong to this synchronous comparison, not a later check.
            if (!collecting) {
                return;
            }

            fieldNames.forEach((name) => {
                if (typeof name === 'string' && name !== '') {
                    excludedFieldNames.add(name);
                }
            });
        },
    } satisfies FormUnloadWarningExclusionsEventDetail);
    collecting = false;

    return excludedFieldNames;
}

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

function shouldTrackFieldName(name: string, form: HTMLFormElement): boolean {
    if (!name) {
        return false;
    }

    const normalizedName = name.endsWith('[]') ? name.slice(0, -2) : name;

    // Ignore CSRF regardless of custom csrfTokenName so token refresh
    // does not mark the form dirty.
    if (isFormCsrfFieldName(normalizedName, form)) {
        return false;
    }

    return !DIRTY_TRACKING_IGNORED_FIELD_NAMES.has(normalizedName);
}

function buildTrackedSnapshot(
    snapshot: FormSnapshot,
    form: HTMLFormElement,
    excludedFieldNames: Set<string>,
): string {
    const entries = snapshot.filter(([name]) => {
        return (
            !excludedFieldNames.has(name) &&
            shouldTrackFieldName(String(name || ''), form)
        );
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
    let baselineSnapshot: FormSnapshot = [];
    let isReady = false;
    let destroyed = false;
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

        // Apply the same current exclusions to both sides. A late registration
        // must neither create a false edit nor reset genuine visitor changes.
        const excludedFieldNames = collectExcludedFieldNames(form);
        const currentSnapshot = Array.from(new FormData(form).entries());
        isDirty =
            buildTrackedSnapshot(currentSnapshot, form, excludedFieldNames) !==
            buildTrackedSnapshot(baselineSnapshot, form, excludedFieldNames);

        return isDirty;
    };

    const captureBaseline = (): void => {
        if (destroyed) {
            return;
        }
        clearScheduledWork();
        baselineSnapshot = Array.from(new FormData(form).entries());
        isReady = true;
        isDirty = false;
    };

    const scheduleBaselineCapture = (): void => {
        if (destroyed) {
            return;
        }
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
    captureBaseline();

    return {
        captureBaseline,
        scheduleBaselineCapture,
        refreshDirtyState,
        destroy: () => {
            destroyed = true;
            isReady = false;
            baselineSnapshot = [];
            clearScheduledWork();
            form.removeEventListener('input', scheduleDirtyRefresh);
            form.removeEventListener('change', scheduleDirtyRefresh);
            window.removeEventListener('beforeunload', handleBeforeUnload);
        },
    };
}
