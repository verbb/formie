import SignaturePad from 'signature_pad';
import signatureCss from '#theme-css/fields/_signature.css?inline';

import type { FormieModuleDefinition } from '#contracts/modules';
import { dispatchFieldEvent, getModuleFieldContainers } from '#modules/fields/shared';
import { ensureModuleStyles } from '#modules/styles';

const INPUT_SELECTOR = 'input[data-formie-signature-input]';
const CANVAS_SELECTOR = 'canvas[data-formie-signature-canvas]';
const CLEAR_SELECTOR = '[data-formie-signature-clear]';
const MODULE_ID = 'signature';

ensureModuleStyles(MODULE_ID, [signatureCss]);

type SignatureOptions = {
    backgroundColor?: string;
    penColor?: string;
    penWeight?: string;
};

function getCanvasSize(canvas: HTMLCanvasElement): { width: number; height: number } {
    const rect = canvas.getBoundingClientRect();

    return {
        width: Math.round(rect.width),
        height: Math.round(rect.height),
    };
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

function initSignatureField(
    root: HTMLElement,
    field: HTMLElement,
    input: HTMLInputElement,
    canvas: HTMLCanvasElement,
    clearButton: HTMLElement | null,
    options: SignatureOptions,
): () => void {
    const penWeight = parseFloat(options.penWeight || '2') || 2;
    const resizeTarget = canvas.parentElement instanceof HTMLElement ? canvas.parentElement : field;
    const signaturePad = new SignaturePad(canvas, {
        backgroundColor: options.backgroundColor || 'rgba(255, 255, 255, 0)',
        penColor: options.penColor || '#000000',
        dotSize: penWeight,
        minWidth: penWeight,
        maxWidth: penWeight,
    });

    const resizeCanvas = () => {
        const { width, height } = getCanvasSize(canvas);

        if (!(width > 0) || !(height > 0)) {
            return;
        }

        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const context = canvas.getContext('2d');
        if (!context) {
            return;
        }

        // SignaturePad clears bitmap state when the canvas size changes, so resize
        // captures the current data URL first and redraws it onto the new surface.
        const existingValue = input.value || (signaturePad.isEmpty() ? '' : signaturePad.toDataURL());
        canvas.width = width * ratio;
        canvas.height = height * ratio;
        context.setTransform(1, 0, 0, 1, 0, 0);
        context.scale(ratio, ratio);
        signaturePad.clear();
        drawValueOnCanvas(canvas, existingValue);
    };

    const scheduleResize = (delay = 0) => {
        window.setTimeout(() => {
            window.requestAnimationFrame(() => {
                resizeCanvas();
            });
        }, delay);
    };
    const resizeHandler = () => {
        scheduleResize();
    };
    const pageNavigateHandler = () => {
        // Multi-page forms can reveal a signature pad after layout changes; delay
        // slightly so the newly visible page has settled before measuring.
        scheduleResize(100);
    };
    const resizeObserver = typeof ResizeObserver === 'undefined'
        ? null
        : new ResizeObserver(() => {
            scheduleResize();
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
    scheduleResize();

    if (clearButton) {
        clearButton.addEventListener('click', clearSignature);
    }

    dispatchFieldEvent(field, MODULE_ID, 'init', {
        signature: signaturePad,
    });

    return () => {
        signaturePad.removeEventListener('endStroke', syncValue);
        window.removeEventListener('resize', resizeHandler);
        root.removeEventListener('formie:page:navigate:after', pageNavigateHandler as EventListener);
        resizeObserver?.disconnect();
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
