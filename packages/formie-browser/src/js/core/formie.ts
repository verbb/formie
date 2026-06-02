import type { FormMountOptions, FormieClient, FormieFormInstance } from '#contracts/client';
import type { FormSubmitResult } from '#contracts/schema';
import { createFormieClient } from '#core/create-formie-client';
import { FORMIE_HTML_EVENT_NAMES } from '#utils/event-names';

export type FormieElementTarget = string | Element | Iterable<Element>;

export type FormieEvent = {
    name: string;
    payload: unknown;
};

export type FormieOptions = Omit<Partial<FormMountOptions>, 'mode'> & {
    element: FormieElementTarget;
    observe?: boolean;
    allowEmpty?: boolean;
    client?: FormieClient;
    onReady?: (instance: FormieFormInstance) => void;
    onResult?: (result: FormSubmitResult, instance: FormieFormInstance) => void;
    onSuccess?: (result: FormSubmitResult, instance: FormieFormInstance) => void;
    onError?: (result: FormSubmitResult, instance: FormieFormInstance) => void;
    onEvent?: (event: FormieEvent, instance: FormieFormInstance) => void;
};

export type FormieApp = {
    client: FormieClient;
    readonly instances: FormieFormInstance[];
    get: (target: string | Element) => FormieFormInstance | null;
    rescan: () => Promise<FormieFormInstance[]>;
    destroy: () => Promise<void>;
};

type MountedState = {
    instance: FormieFormInstance;
    unsubs: Array<() => void>;
};

function isElement(value: unknown): value is Element {
    return value instanceof Element;
}

function isSuccessfulResult(result: FormSubmitResult): boolean {
    return result.ok;
}

function describeTarget(target: FormieElementTarget): string {
    if (typeof target === 'string') {
        return `selector "${target}"`;
    }

    if (isElement(target)) {
        return `element "${target.tagName.toLowerCase()}"`;
    }

    return 'provided element collection';
}

function toUniqueElements(values: Iterable<Element>): Element[] {
    const seen = new Set<Element>();
    const elements: Element[] = [];

    for (const value of values) {
        if (!isElement(value) || seen.has(value)) {
            continue;
        }

        seen.add(value);
        elements.push(value);
    }

    return elements;
}

function resolveElements(target: FormieElementTarget): Element[] {
    if (typeof target === 'string') {
        return Array.from(document.querySelectorAll(target));
    }

    if (isElement(target)) {
        return [target];
    }

    return toUniqueElements(target);
}

function waitForDomReady(): Promise<void> {
    if (document.readyState !== 'loading') {
        return Promise.resolve();
    }

    return new Promise((resolve) => {
        document.addEventListener('DOMContentLoaded', () => resolve(), { once: true });
    });
}

async function resolveElementsWhenReady(target: FormieElementTarget): Promise<Element[]> {
    const elements = resolveElements(target);

    if (elements.length > 0 || typeof target !== 'string') {
        return elements;
    }

    await waitForDomReady();

    return resolveElements(target);
}

function resolveObservationScope(target: FormieElementTarget): ParentNode | undefined {
    if (typeof target === 'string') {
        return document;
    }

    if (isElement(target)) {
        return target.getRootNode() as ParentNode;
    }

    return document;
}

function buildMountOptions(options: FormieOptions): FormMountOptions {
    const {
        element: _element,
        observe: _observe,
        allowEmpty: _allowEmpty,
        client: _client,
        onReady: _onReady,
        onResult: _onResult,
        onSuccess: _onSuccess,
        onError: _onError,
        onEvent: _onEvent,
        ...mountOptions
    } = options;

    return {
        mode: 'server-rendered',
        ...mountOptions,
    };
}

async function mountResolvedElements(options: FormieOptions, client: FormieClient, states: Map<Element, MountedState>, targets: Element[]): Promise<FormieFormInstance[]> {
    const mounted: FormieFormInstance[] = [];
    const mountOptions = buildMountOptions(options);

    for (const target of targets) {
        const existing = states.get(target);

        if (existing) {
            mounted.push(existing.instance);
            continue;
        }

        const instance = await client.mount(target, mountOptions);
        const unsubs: Array<() => void> = [];

        options.onReady?.(instance);

        unsubs.push(instance.on('formie:submit:result', (payload) => {
            const result = payload as FormSubmitResult;

            options.onResult?.(result, instance);

            if (isSuccessfulResult(result)) {
                options.onSuccess?.(result, instance);
            } else {
                options.onError?.(result, instance);
            }
        }));

        if (options.onEvent) {
            for (const eventName of FORMIE_HTML_EVENT_NAMES) {
                unsubs.push(instance.on(eventName, (payload) => {
                    options.onEvent?.({
                        name: eventName,
                        payload,
                    }, instance);
                }));
            }
        }

        states.set(target, {
            instance,
            unsubs,
        });
        mounted.push(instance);
    }

    return mounted;
}

export async function formie(options: FormieOptions): Promise<FormieApp> {
    const client = options.client ?? createFormieClient();
    const states = new Map<Element, MountedState>();
    const matchedElements = await resolveElementsWhenReady(options.element);

    if (matchedElements.length === 0 && !options.allowEmpty) {
        throw new Error(`Formie could not find any elements for ${describeTarget(options.element)}.`);
    }

    await mountResolvedElements(options, client, states, matchedElements);

    // The helper keeps observation opt-in so the default path stays predictable
    // for one-off page loads while still offering a light SPA escape hatch.
    const stopObserving = options.observe
        ? client.observe(resolveObservationScope(options.element))
        : null;

    return {
        client,
        get instances() {
            return Array.from(states.values()).map(({ instance }) => instance);
        },
        get(target) {
            const element = typeof target === 'string' ? document.querySelector(target) : target;

            if (!element) {
                return null;
            }

            return states.get(element)?.instance ?? client.getInstance(element);
        },
        async rescan() {
            const nextTargets = resolveElements(options.element);

            if (nextTargets.length === 0) {
                return Array.from(states.values()).map(({ instance }) => instance);
            }

            return mountResolvedElements(options, client, states, nextTargets);
        },
        async destroy() {
            stopObserving?.();

            const mountedEntries = Array.from(states.entries());

            for (const [target, state] of mountedEntries) {
                state.unsubs.forEach((unsubscribe) => unsubscribe());
                await client.unmount(target);
                states.delete(target);
            }
        },
    };
}
