import SignaturePad from 'signature_pad';
import signatureCss from '#theme-css/fields/_signature.css?inline';

import type { BrowserModuleDefinition } from '#contracts/modules';
import { bindConditionalClear, dispatchFieldEvent, getModuleFieldContainers, retainFieldReset } from '#modules/fields/shared';
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

function sumPixels(...values: string[]): number {
    return values.reduce((total, value) => total + (parseFloat(value) || 0), 0);
}

function getCanvasEdges(canvas: HTMLCanvasElement): { horizontal: number; vertical: number } {
    const style = window.getComputedStyle(canvas);

    return {
        horizontal: sumPixels(style.borderLeftWidth, style.borderRightWidth, style.paddingLeft, style.paddingRight),
        vertical: sumPixels(style.borderTopWidth, style.borderBottomWidth, style.paddingTop, style.paddingBottom),
    };
}

function getCanvasSize(canvas: HTMLCanvasElement): { width: number; height: number } {
    const rect = canvas.getBoundingClientRect();
    const edges = getCanvasEdges(canvas);

    // Measure the content box, which is where the drawing is shown. Sizing the drawing to the
    // border box made a canvas with `height: auto` taller on every resize, so it never settled.
    return {
        width: Math.max(0, Math.round(rect.width - edges.horizontal)),
        height: Math.max(0, Math.round(rect.height - edges.vertical)),
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

function getImageDimensions(dataUrl: string): Promise<{ width: number; height: number } | null> {
    return new Promise((resolve) => {
        const image = new Image();
        image.onload = () => {
            resolve({
                width: image.naturalWidth,
                height: image.naturalHeight,
            });
        };
        image.onerror = () => resolve(null);
        image.src = dataUrl;
    });
}

async function syncCanvasHeightToSignatureValue(canvas: HTMLCanvasElement, value: string): Promise<void> {
    const dimensions = await getImageDimensions(value);

    if (!dimensions?.width || !dimensions?.height) {
        return;
    }

    const { width: containerWidth } = getCanvasSize(canvas);

    if (!(containerWidth > 0)) {
        return;
    }

    // Match the stored PNG aspect ratio so restored signatures are not vertically stretched.
    const aspectHeight = Math.max(1, Math.round(containerWidth * (dimensions.height / dimensions.width)));
    const boxEdges = window.getComputedStyle(canvas).boxSizing === 'border-box' ? getCanvasEdges(canvas).vertical : 0;
    canvas.style.height = `${aspectHeight + boxEdges}px`;
}

async function restoreSignatureValue(signaturePad: SignaturePad, canvas: HTMLCanvasElement, value: string): Promise<void> {
    if (!value) {
        return;
    }

    await syncCanvasHeightToSignatureValue(canvas, value);

    try {
        await signaturePad.fromDataURL(value);
    } catch {
        drawValueOnCanvas(canvas, value);
    }
}

function syncInputValue(input: HTMLInputElement, nextValue: string): void {
    const valueChanged = input.value !== nextValue;
    input.value = nextValue;

    if (!valueChanged) {
        return;
    }

    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
}

function initSignatureField(
    root: HTMLElement,
    field: HTMLElement,
    input: HTMLInputElement,
    canvas: HTMLCanvasElement,
    clearButton: HTMLElement | null,
    options: SignatureOptions,
): () => void {
    const form = input.form;
    const messages = getSignatureMessages(field);
    const retainedReset = retainFieldReset(form, input, MODULE_ID, () => {
        const initialValue = input.value;

        // Hidden inputs do not restore an earlier value through native reset,
        // and this also runs while the field's page is hidden and unmounted.
        return () => syncInputValue(input, initialValue);
    });

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
    let strokeActive = false;
    let resizeDeferred = false;

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

    const deferResizeDuringStroke = (): boolean => {
        if (!strokeActive) {
            return false;
        }

        // Resizing clears the pad. Clearing it mid-stroke cuts the stroke short and leaves
        // signature_pad unable to start another, so resize once the stroke ends.
        resizeDeferred = true;

        return true;
    };

    const resizeCanvas = async(): Promise<boolean> => {
        if (deferResizeDuringStroke()) {
            return true;
        }

        const existingValue = input.value || (signaturePad.isEmpty() ? '' : signaturePad.toDataURL());

        if (existingValue) {
            await syncCanvasHeightToSignatureValue(canvas, existingValue);

            if (deferResizeDuringStroke()) {
                return true;
            }
        }

        const { width, height } = getCanvasSize(canvas);

        if (!(width > 0) || !(height > 0)) {
            return false;
        }

        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const context = canvas.getContext('2d');
        if (!context) {
            return false;
        }

        const canvasWidth = Math.floor(width * ratio);
        const canvasHeight = Math.floor(height * ratio);

        // Already drawn at this size: clearing and redrawing would only lose detail.
        if (readyDispatched && canvas.width === canvasWidth && canvas.height === canvasHeight) {
            return true;
        }

        canvas.width = canvasWidth;
        canvas.height = canvasHeight;
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

    const syncValue = () => {
        syncInputValue(input, signaturePad.isEmpty() ? '' : signaturePad.toDataURL());
    };

    // `beginStroke` can be cancelled, so only count a stroke once signature_pad is drawing it.
    const onStrokeUpdate = () => {
        strokeActive = true;
    };

    const onEndStroke = () => {
        strokeActive = false;
        syncValue();

        if (resizeDeferred) {
            resizeDeferred = false;
            requestResize();
        }
    };

    const clearSignature = () => {
        signaturePad.clear();
        syncInputValue(input, '');
    };

    // The retained reset has restored the input. Redraw the pad to match.
    const afterReset = () => {
        signaturePad.clear();
        void restoreSignatureValue(signaturePad, canvas, input.value);
    };
    retainedReset.afterReset = afterReset;

    const unbindConditionalClear = bindConditionalClear(input, clearSignature);
    signaturePad.addEventListener('beforeUpdateStroke', onStrokeUpdate);
    signaturePad.addEventListener('endStroke', onEndStroke);
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
        unbindConditionalClear();
        retainedReset.unmount(afterReset);
        signaturePad.removeEventListener('beforeUpdateStroke', onStrokeUpdate);
        signaturePad.removeEventListener('endStroke', onEndStroke);
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

export const signatureModule: BrowserModuleDefinition = {
    moduleId: `formie:${MODULE_ID}`,
    version: 2,
    surfaces: ['server-rendered', 'client-rendered', 'cp-edit'],
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
