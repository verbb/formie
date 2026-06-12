import SignaturePad from 'signature_pad';
import signatureCss from '#theme-css/fields/_signature.css?inline';

import type { FormieModuleDefinition } from '#contracts/modules';
import { dispatchFieldEvent, getModuleFieldContainers } from '#modules/fields/shared';
import { ensureModuleStyles } from '#modules/styles';

const INPUT_SELECTOR = 'input[data-formie-signature-input]';
const CANVAS_SELECTOR = 'canvas[data-formie-signature-canvas]';
const CLEAR_SELECTOR = '[data-formie-signature-clear]';
const MESSAGE_SELECTOR = '[data-formie-signature-message]';
const MODULE_ID = 'signature';
const MAX_SIZE_ATTEMPTS = 20;
const SIZE_RETRY_MS = 100;
const VISIBILITY_ATTRS = [
    'hidden',
    'data-formie-conditionally-hidden',
    'data-formie-page-hidden',
    'data-formie-row-hidden',
] as const;

ensureModuleStyles(MODULE_ID, [signatureCss]);

type SignatureOptions = {
    backgroundColor?: string;
    penColor?: string;
    penWeight?: string;
};

type SignatureMessages = {
    noCanvas: string;
    initFailed: string;
};

function getCanvasSize(canvas: HTMLCanvasElement): { width: number; height: number } {
    const rect = canvas.getBoundingClientRect();

    return {
        width: Math.round(rect.width),
        height: Math.round(rect.height),
    };
}

function supportsCanvas(): boolean {
    const probe = document.createElement('canvas');

    return typeof probe.getContext === 'function' && !!probe.getContext('2d');
}

function getMessageElement(field: HTMLElement): HTMLElement | null {
    const message = field.querySelector(MESSAGE_SELECTOR);

    return message instanceof HTMLElement ? message : null;
}

function getSignatureMessages(field: HTMLElement): SignatureMessages {
    const message = getMessageElement(field);

    return {
        noCanvas: message?.dataset.formieSignatureMessageNoCanvas
            || 'This browser does not support canvas, which is required for signatures.',
        initFailed: message?.dataset.formieSignatureMessageInitFailed
            || 'The signature pad could not be loaded. Try refreshing the page.',
    };
}

function setSignatureMessage(field: HTMLElement, canvas: HTMLCanvasElement, message: string | null): void {
    const messageElement = getMessageElement(field);

    if (!messageElement) {
        return;
    }

    if (message) {
        messageElement.textContent = message;
        messageElement.hidden = false;
        canvas.setAttribute('aria-hidden', 'true');
        field.classList.add('formie-signature-has-message');
        return;
    }

    messageElement.textContent = '';
    messageElement.hidden = true;
    canvas.removeAttribute('aria-hidden');
    field.classList.remove('formie-signature-has-message');
}

function isFieldVisible(field: HTMLElement): boolean {
    return !field.hasAttribute('hidden')
        && !field.hasAttribute('data-formie-conditionally-hidden')
        && !field.hasAttribute('data-formie-page-hidden')
        && !field.hasAttribute('data-formie-row-hidden')
        && field.getClientRects().length > 0;
}

function drawValueOnCanvas(canvas: HTMLCanvasElement, value: string): void {
    if (!value) {
        return;
    }

    const image = new Image();
    image.src = value;
    image.onload = () => {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const context = canvas.getContext('2d');
        if (!context) {
            return;
        }

        context.drawImage(image, 0, 0, canvas.width / ratio, canvas.height / ratio);
    };
}

async function restoreSignatureValue(signaturePad: SignaturePad, canvas: HTMLCanvasElement, value: string): Promise<void> {
    if (!value) {
        return;
    }

    try {
        await signaturePad.fromDataURL(value);
    } catch {
        drawValueOnCanvas(canvas, value);
    }
}

function initSignatureField(
    root: HTMLElement,
    field: HTMLElement,
    input: HTMLInputElement,
    canvas: HTMLCanvasElement,
    clearButton: HTMLElement | null,
    options: SignatureOptions,
): () => void {
    const messages = getSignatureMessages(field);

    if (!supportsCanvas()) {
        setSignatureMessage(field, canvas, messages.noCanvas);

        return () => {};
    }

    const penWeight = parseFloat(options.penWeight || '2') || 2;
    const resizeTarget = canvas.parentElement instanceof HTMLElement ? canvas.parentElement : field;
    let sizeAttempts = 0;
    let sizeRetryTimer: number | null = null;
    let readyDispatched = false;
    let initFailed = false;

    const signaturePad = new SignaturePad(canvas, {
        backgroundColor: options.backgroundColor || 'rgba(255, 255, 255, 0)',
        penColor: options.penColor || '#000000',
        dotSize: penWeight,
        minWidth: penWeight,
        maxWidth: penWeight,
    });

    const clearSizeRetry = () => {
        if (sizeRetryTimer !== null) {
            window.clearTimeout(sizeRetryTimer);
            sizeRetryTimer = null;
        }
    };

    const markReady = () => {
        if (readyDispatched) {
            return;
        }

        readyDispatched = true;
        initFailed = false;
        setSignatureMessage(field, canvas, null);
        dispatchFieldEvent(field, MODULE_ID, 'init', {
            signature: signaturePad,
        });
    };

    const markInitFailed = () => {
        if (readyDispatched || initFailed) {
            return;
        }

        initFailed = true;
        setSignatureMessage(field, canvas, messages.initFailed);
    };

    const resizeCanvas = async(): Promise<boolean> => {
        const { width, height } = getCanvasSize(canvas);

        if (!(width > 0) || !(height > 0)) {
            return false;
        }

        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const context = canvas.getContext('2d');
        if (!context) {
            return false;
        }

        const existingValue = input.value || (signaturePad.isEmpty() ? '' : signaturePad.toDataURL());

        canvas.width = width * ratio;
        canvas.height = height * ratio;
        context.setTransform(1, 0, 0, 1, 0, 0);
        context.scale(ratio, ratio);
        signaturePad.clear();

        await restoreSignatureValue(signaturePad, canvas, existingValue);
        markReady();

        return true;
    };

    const scheduleSizeRetry = () => {
        if (readyDispatched || initFailed) {
            return;
        }

        if (sizeAttempts >= MAX_SIZE_ATTEMPTS) {
            markInitFailed();
            return;
        }

        clearSizeRetry();
        sizeRetryTimer = window.setTimeout(() => {
            sizeRetryTimer = null;
            sizeAttempts += 1;
            void attemptResize();
        }, SIZE_RETRY_MS);
    };

    const attemptResize = async() => {
        const sized = await resizeCanvas();

        if (!sized) {
            scheduleSizeRetry();
            return;
        }

        clearSizeRetry();
        sizeAttempts = 0;
    };

    const scheduleResize = (delay = 0) => {
        window.setTimeout(() => {
            window.requestAnimationFrame(() => {
                void attemptResize();
            });
        }, delay);
    };

    const requestResize = () => {
        if (isFieldVisible(field) && !readyDispatched) {
            initFailed = false;
        }

        scheduleResize();
    };

    const resizeHandler = () => {
        requestResize();
    };

    const pageNavigateHandler = () => {
        if (!isFieldVisible(field)) {
            return;
        }

        sizeAttempts = 0;
        initFailed = false;
        scheduleResize(100);
    };

    const visibilityHandler = () => {
        if (!isFieldVisible(field)) {
            return;
        }

        sizeAttempts = 0;
        initFailed = false;
        requestResize();
    };

    const resizeObserver = typeof ResizeObserver === 'undefined'
        ? null
        : new ResizeObserver(() => {
            requestResize();
        });

    const visibilityObserver = new MutationObserver(() => {
        visibilityHandler();
    });

    const syncInputValue = (nextValue: string) => {
        const valueChanged = input.value !== nextValue;
        input.value = nextValue;

        if (!valueChanged) {
            return;
        }

        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const syncValue = () => {
        syncInputValue(signaturePad.isEmpty() ? '' : signaturePad.toDataURL());
    };

    const clearSignature = () => {
        signaturePad.clear();
        syncInputValue('');
    };

    signaturePad.addEventListener('endStroke', syncValue);
    window.addEventListener('resize', resizeHandler);
    root.addEventListener('formie:page:navigate:after', pageNavigateHandler as EventListener);
    resizeObserver?.observe(resizeTarget);
    visibilityObserver.observe(field, {
        attributes: true,
        attributeFilter: [...VISIBILITY_ATTRS],
    });
    scheduleResize();

    if (clearButton) {
        clearButton.addEventListener('click', clearSignature);
    }

    return () => {
        clearSizeRetry();
        signaturePad.removeEventListener('endStroke', syncValue);
        window.removeEventListener('resize', resizeHandler);
        root.removeEventListener('formie:page:navigate:after', pageNavigateHandler as EventListener);
        resizeObserver?.disconnect();
        visibilityObserver.disconnect();
        if (clearButton) {
            clearButton.removeEventListener('click', clearSignature);
        }

        signaturePad.clear();
    };
}

export const signatureModule: FormieModuleDefinition = {
    id: MODULE_ID,
    kind: 'field',
    match: (ctx) => {
        return !!ctx.target.querySelector(CANVAS_SELECTOR);
    },
    setup: async(ctx) => {
        const options = (ctx.options || {}) as SignatureOptions;
        const root = ctx.root instanceof HTMLElement
            ? ctx.root
            : (ctx.target instanceof HTMLElement ? ctx.target : null);

        if (!root) {
            return;
        }

        const fields = getModuleFieldContainers(ctx);
        const cleanups = fields.map((field) => {
            const input = field.querySelector(INPUT_SELECTOR);
            const canvas = field.querySelector(CANVAS_SELECTOR);
            const clearButton = field.querySelector(CLEAR_SELECTOR);

            if (!(input instanceof HTMLInputElement) || !(canvas instanceof HTMLCanvasElement)) {
                return () => {};
            }

            return initSignatureField(root, field, input, canvas, clearButton instanceof HTMLElement ? clearButton : null, options);
        });

        await ctx.emit('formie:module:signature:init', {
            count: cleanups.length,
        });

        return {
            destroy: () => {
                cleanups.forEach((cleanup) => {
                    cleanup();
                });

                void ctx.emit('formie:module:signature:destroy', {});
            },
        };
    },
};
