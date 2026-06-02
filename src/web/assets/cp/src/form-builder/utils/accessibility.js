import { getPortalContainer } from '@verbb/plugin-kit-react/utils';

const FORM_BUILDER_LIVE_REGION_ID = 'form-builder-live-region';
const FORM_BUILDER_CONTAINER_SELECTOR = '.formie-form-builder';

const getDocument = () => {
    if (typeof document === 'undefined') {
        return null;
    }

    return document;
};

const getLiveRegionContainer = (currentDocument) => {
    const portalContainer = getPortalContainer();

    if (portalContainer) {
        return portalContainer;
    }

    const formBuilderContainer = currentDocument.querySelector(FORM_BUILDER_CONTAINER_SELECTOR);

    return formBuilderContainer?.shadowRoot ?? formBuilderContainer ?? currentDocument.body;
};

const ensureLiveRegion = () => {
    const currentDocument = getDocument();

    if (!currentDocument?.body) {
        return null;
    }

    const liveRegionContainer = getLiveRegionContainer(currentDocument);
    const existingRegion = liveRegionContainer.getElementById?.(FORM_BUILDER_LIVE_REGION_ID) ?? liveRegionContainer.querySelector?.(`#${FORM_BUILDER_LIVE_REGION_ID}`);

    if (existingRegion) {
        return existingRegion;
    }

    const existingDocumentRegion = currentDocument.getElementById(FORM_BUILDER_LIVE_REGION_ID);

    if (existingDocumentRegion) {
        liveRegionContainer.appendChild(existingDocumentRegion);

        return existingDocumentRegion;
    }

    const region = currentDocument.createElement('div');
    region.id = FORM_BUILDER_LIVE_REGION_ID;
    region.className = 'sr-only';
    region.setAttribute('role', 'status');
    region.setAttribute('aria-live', 'polite');
    region.setAttribute('aria-atomic', 'true');

    liveRegionContainer.appendChild(region);

    return region;
};

export const announceFormBuilderStatus = (message) => {
    const region = ensureLiveRegion();

    if (!region || !message) {
        return;
    }

    // Clearing first helps screen readers announce repeated move actions.
    region.textContent = '';

    window.setTimeout(() => {
        region.textContent = message;
    }, 20);
};

export const focusFieldActionsTrigger = (fieldId) => {
    const currentDocument = getDocument();

    if (!currentDocument || !fieldId) {
        return;
    }

    window.requestAnimationFrame(() => {
        const triggers = currentDocument.querySelectorAll('[data-form-builder-field-actions-trigger]');
        const trigger = Array.from(triggers).find((candidate) => {
            return candidate.getAttribute('data-form-builder-field-actions-trigger') === fieldId;
        });

        trigger?.focus?.();
    });
};
