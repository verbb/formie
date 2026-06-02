import { createDebug } from '#utils/debug';

const debug = createDebug('general', 'page-client-event');

const CLIENT_EVENT_ATTR = 'data-formie-client-event';

type ClientEventPayload = {
    fields?: Array<{ label?: string; value?: string }>;
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

/**
 * When the builder enables JavaScript events for a page, the theme emits
 * `data-formie-client-event` on that page's section. On each successful
 * **submit** (not back/save), push the configured key/value object to
 * `window.dataLayer` (when present) and dispatch `formie:client-event`.
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
    const win = window as Window & { dataLayer?: unknown[] };

    win.dataLayer = win.dataLayer || [];
    win.dataLayer.push(payload);

    form.dispatchEvent(new CustomEvent('formie:client-event', {
        bubbles: true,
        detail: { payload },
    }));

    debug.log('Dispatched page client event.', {
        pageId,
        keys: Object.keys(payload),
    });
}
