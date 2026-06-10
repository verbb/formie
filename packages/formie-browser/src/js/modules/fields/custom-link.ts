import type { FormieModuleDefinition } from '#contracts/modules';
import { getModuleFieldTarget, observeMatchingElements } from '#modules/fields/shared';

const MODULE_ID = 'custom-link';
const LINK_SELECTOR = '[data-formie-custom-link]';

type LinkInputAttributes = {
    type: string;
    inputMode: string;
    autocomplete: string;
};

function flag(element: HTMLElement, name: string): boolean {
    return element.getAttribute(name) === '1';
}

function getValueInputAttributes(link: HTMLElement, type: string): LinkInputAttributes {
    if (type === 'email') {
        return {
            type: 'email',
            inputMode: 'email',
            autocomplete: 'email',
        };
    }

    if (type === 'tel' || type === 'sms') {
        return {
            type: 'tel',
            inputMode: 'tel',
            autocomplete: 'tel',
        };
    }

    const strictUrl = !flag(link, 'data-formie-custom-link-allow-root-relative') &&
        !flag(link, 'data-formie-custom-link-allow-anchors') &&
        !flag(link, 'data-formie-custom-link-allow-custom-schemes');

    return {
        type: strictUrl ? 'url' : 'text',
        inputMode: 'url',
        autocomplete: 'url',
    };
}

function isCustomLink(element: Element): element is HTMLElement {
    return element instanceof HTMLElement && element.matches(LINK_SELECTOR);
}

function initCustomLink(link: HTMLElement): () => void {
    const typeInput = link.querySelector('[data-formie-custom-link-type]');
    const valueInput = link.querySelector('[data-formie-custom-link-value]');

    if (!(typeInput instanceof HTMLSelectElement) || !(valueInput instanceof HTMLInputElement)) {
        return () => {};
    }

    const syncValueInput = (): void => {
        const attributes = getValueInputAttributes(link, typeInput.value);
        valueInput.type = attributes.type;
        valueInput.inputMode = attributes.inputMode;
        valueInput.setAttribute('autocomplete', attributes.autocomplete);
    };

    syncValueInput();
    typeInput.addEventListener('change', syncValueInput);

    return () => {
        typeInput.removeEventListener('change', syncValueInput);
    };
}

export const customLinkModule: FormieModuleDefinition = {
    id: MODULE_ID,
    kind: 'field',
    match: ({ target }) => {
        return target instanceof Element && (
            target.matches(LINK_SELECTOR) ||
            !!target.querySelector(LINK_SELECTOR)
        );
    },
    setup: async(ctx) => {
        const field = getModuleFieldTarget(ctx);
        const root = field || ctx.target;

        if (!(root instanceof Element)) {
            return;
        }

        const cleanup = observeMatchingElements(root, LINK_SELECTOR, isCustomLink, initCustomLink);

        return {
            destroy: cleanup,
        };
    },
};
