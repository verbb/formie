import type { FormieFormInstance } from '#contracts/client';
import { FORMIE_HTML_EVENT_NAMES, toDomEventName } from '#utils/event-names';

const formEventInstances = new WeakMap<Element, FormieFormInstance>();
const lifecycleEvents = new Set<string>([...FORMIE_HTML_EVENT_NAMES, 'formie:state:reset']);

export function bindFormEventInstance(root: Element, form: HTMLFormElement | null, instance: FormieFormInstance): () => void {
    // Wrapper mounts also dispatch lifecycle events from their inner form.
    const targets = new Set<Element>(form ? [root, form] : [root]);
    targets.forEach((target) => formEventInstances.set(target, instance));
    return () => {
        targets.forEach((target) => {
            if (formEventInstances.get(target) === instance) formEventInstances.delete(target);
        });
    };
}

export function withFormEventInstance<T extends object>(target: Element, detail: T): T & { instance?: FormieFormInstance } {
    const instance = formEventInstances.get(target);
    // Retain payload identity: synchronous hooks can modify the active result.
    return instance ? Object.assign(detail, { instance }) : detail;
}

export function dispatchFormieDomEvent(target: Element, eventName: string, detail: unknown): void {
    // Early setup and standalone module hydration have no mounted form instance.
    // Custom module events retain their own payload shapes.
    if (lifecycleEvents.has(eventName) || eventName.startsWith('formie:stage:')) {
        if (detail == null) detail = formEventInstances.has(target) ? {} : detail;
        if (detail && typeof detail === 'object' && !Array.isArray(detail)) {
            detail = withFormEventInstance(target, detail);
        }
    }
    target.dispatchEvent(new CustomEvent(toDomEventName(eventName), {
        bubbles: true,
        detail,
    }));
}
