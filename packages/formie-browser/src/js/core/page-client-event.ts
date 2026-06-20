import { createDebug } from '#utils/debug';

const debug = createDebug('general', 'page-client-event');

const CLIENT_EVENT_ATTR = 'data-formie-client-event';
const PENDING_CLIENT_EVENTS_ATTR = 'data-formie-pending-client-events';

type ClientEventPayload = {
    fields?: Array<{ label?: string; value?: string }>;
};

export type ResolvedClientEvent = {
    event: string;
    payload: Record<string, string>;
};

function escapePageIdForSelector(pageId: string): string {
    if (typeof window !== 'undefined' && window.CSS?.escape) {
        return window.CSS.escape(pageId);
    }

    return pageId.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
}

export function resolveSubmittedPageId(form: HTMLFormElement): string | null {
    const input = form.querySelector('input[name="pageId"]') as HTMLInputElement | null;
    const fromInput = input?.value?.trim();

    if (fromInput) {
        return fromInput;
    }

    const visible = form.querySelector('[data-formie-page]:not([data-formie-page-hidden])') as HTMLElement | null;
    const fromVisible = visible?.getAttribute('data-formie-page-id')?.trim();

    if (fromVisible) {
        return fromVisible;
    }

    const fallback = form.querySelector('[data-formie-page]') as HTMLElement | null;

    return fallback?.getAttribute('data-formie-page-id')?.trim() || null;
}

function parseClientEventAttr(raw: string | null): ClientEventPayload | null {
    if (!raw?.trim()) {
        return null;
    }

    try {
        const parsed = JSON.parse(raw) as ClientEventPayload;

        return parsed && typeof parsed === 'object' ? parsed : null;
    } catch {
        debug.warn('Invalid data-formie-client-event JSON.', {
            rawPreview: raw.slice(0, 80),
        });

        return null;
    }
}

function buildPayloadObject(fields: Array<{ label?: string; value?: string }>): Record<string, string> {
    const out: Record<string, string> = {};

    fields.forEach((row) => {
        const key = typeof row.label === 'string' ? row.label.trim() : '';

        if (!key) {
            return;
        }

        out[key] = typeof row.value === 'string' ? row.value : '';
    });

    return out;
}

function normalizeResolvedClientEvents(events: unknown): ResolvedClientEvent[] {
    if (!Array.isArray(events)) {
        return [];
    }

    return events
        .map((item) => {
            if (!item || typeof item !== 'object') {
                return null;
            }

            const record = item as ResolvedClientEvent;
            const eventName = typeof record.event === 'string' ? record.event.trim() : '';
            const payload = record.payload && typeof record.payload === 'object' ? record.payload : null;

            if (!eventName || !payload) {
                return null;
            }

            return {
                event: eventName,
                payload,
            };
        })
        .filter((item): item is ResolvedClientEvent => item !== null);
}

export function dispatchResolvedClientEvents(form: HTMLFormElement, events: ResolvedClientEvent[]): void {
    if (!events.length) {
        return;
    }

    const win = window as Window & { dataLayer?: unknown[] };
    win.dataLayer = win.dataLayer || [];

    events.forEach((item) => {
        win.dataLayer!.push(item.payload);

        form.dispatchEvent(new CustomEvent('formie:client-event', {
            bubbles: true,
            detail: {
                event: item.event,
                payload: item.payload,
            },
        }));
    });

    debug.log('Dispatched resolved client events.', {
        count: events.length,
        events: events.map((item) => item.event),
    });
}

export function dispatchPendingClientEventsFromForm(form: HTMLFormElement): void {
    const rawAttr = form.getAttribute(PENDING_CLIENT_EVENTS_ATTR);

    if (!rawAttr?.trim()) {
        return;
    }

    try {
        const parsed = JSON.parse(rawAttr) as unknown;
        const events = normalizeResolvedClientEvents(parsed);

        if (events.length) {
            dispatchResolvedClientEvents(form, events);
        }
    } catch {
        debug.warn('Invalid pending client events JSON on form element.');
    } finally {
        form.removeAttribute(PENDING_CLIENT_EVENTS_ATTR);
    }
}

/**
 * Legacy static page attribute dispatch. Prefer server-resolved `clientEvents`
 * from the submit response when available.
 */
export function dispatchPageClientEventForSubmit(form: HTMLFormElement, action: string): void {
    if (action !== 'submit') {
        return;
    }

    const pageId = resolveSubmittedPageId(form);

    if (!pageId) {
        debug.log('No submitted page id; skipping client event.');

        return;
    }

    const section = form.querySelector(
        `[data-formie-page][data-formie-page-id="${escapePageIdForSelector(pageId)}"]`,
    ) as HTMLElement | null;

    if (!section) {
        debug.log('No page section for id; skipping client event.', { pageId });

        return;
    }

    const rawAttr = section.getAttribute(CLIENT_EVENT_ATTR);

    if (rawAttr === null) {
        return;
    }

    const config = parseClientEventAttr(rawAttr);

    if (!config || !Array.isArray(config.fields)) {
        return;
    }

    const payload = buildPayloadObject(config.fields);
    dispatchResolvedClientEvents(form, [{
        event: typeof payload.event === 'string' && payload.event !== '' ? payload.event : 'formPageSubmission',
        payload,
    }]);
}
