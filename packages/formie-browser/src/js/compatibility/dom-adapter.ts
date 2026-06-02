import type { FormieFormInstance } from '#contracts/client';
import {
    LEGACY_FORMIE_DOM_EVENT_BRIDGES,
    type LegacyDomEventBridge,
    type ResolvedLegacyCompatibilityOptions,
} from '#compatibility/event-map';
import { toDomEventName } from '#utils/event-names';

type BindLegacyDomEventCompatibilityOptions = {
    target: Element;
    form: HTMLFormElement;
    instance: FormieFormInstance;
    options: ResolvedLegacyCompatibilityOptions;
    unbinds: Array<() => void>;
};

type SubmitResultLike = {
    ok?: boolean;
};

function dispatchLegacyDomEvent(target: Document | HTMLFormElement, legacyEvent: string, detail: unknown): void {
    target.dispatchEvent(new CustomEvent(legacyEvent, {
        bubbles: true,
        detail,
    }));
}

function shouldDispatchBridge(bridge: LegacyDomEventBridge, detail: unknown): boolean {
    if (bridge.canonicalEvent !== 'formie:submit:result') {
        return true;
    }

    const result = detail as SubmitResultLike | null;

    if (bridge.legacyEvent === 'onAfterFormieSubmit') {
        return !!result?.ok;
    }

    if (bridge.legacyEvent === 'onFormieSubmitError') {
        return result?.ok === false;
    }

    return true;
}

function createLegacyPageToggleDetail(form: HTMLFormElement, detail: unknown): unknown {
    const eventDetail = detail && typeof detail === 'object' ? detail as Record<string, unknown> : {};
    const nextPageId = typeof eventDetail.pageId === 'string' ? eventDetail.pageId : '';
    const pages = Array.from(form.querySelectorAll<HTMLElement>('[data-formie-page-id]'));
    const nextPageIndex = pages.findIndex((page) => {
        return page.getAttribute('data-formie-page-id') === nextPageId;
    });

    return {
        data: {
            nextPageId,
            nextPageIndex,
            totalPages: pages.length,
        },
    };
}

function createLegacyDetail(
    bridge: LegacyDomEventBridge,
    detail: unknown,
    target: Element,
    form: HTMLFormElement,
    instance: FormieFormInstance,
): unknown {
    const legacyFormieApi = (globalThis as { Formie?: unknown }).Formie || instance;

    if (bridge.legacyEvent === 'onFormieLoaded') {
        return {
            formie: legacyFormieApi,
        };
    }

    if (bridge.legacyEvent === 'onFormieInit') {
        return {
            formie: legacyFormieApi,
            form: instance,
            $form: form,
            formId: instance.id,
        };
    }

    if (bridge.legacyEvent === 'onFormieReady') {
        return {
            ...(detail && typeof detail === 'object' ? detail as Record<string, unknown> : {}),
            form,
            target,
            instance,
        };
    }

    if (bridge.legacyEvent === 'onFormiePageToggle') {
        return createLegacyPageToggleDetail(form, detail);
    }

    return detail;
}

export function bindLegacyDomEventCompatibility({
    target,
    form,
    instance,
    options,
    unbinds,
}: BindLegacyDomEventCompatibilityOptions): void {
    if (!options.legacyDomEvents) {
        return;
    }

    LEGACY_FORMIE_DOM_EVENT_BRIDGES.forEach((bridge) => {
        const handler = (event: Event) => {
            if (!(event instanceof CustomEvent) || !shouldDispatchBridge(bridge, event.detail)) {
                return;
            }

            const legacyTarget = bridge.target === 'document' ? document : form;
            dispatchLegacyDomEvent(legacyTarget, bridge.legacyEvent, createLegacyDetail(bridge, event.detail, target, form, instance));
        };

        target.addEventListener(toDomEventName(bridge.canonicalEvent), handler as EventListener);
        unbinds.push(() => {
            target.removeEventListener(toDomEventName(bridge.canonicalEvent), handler as EventListener);
        });
    });
}
