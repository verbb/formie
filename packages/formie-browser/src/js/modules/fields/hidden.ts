import type { FormieModuleDefinition } from '#contracts/modules';
import { getModuleFieldContainers } from '#modules/fields/shared';

const INPUT_SELECTOR = 'input[data-formie-hidden-input]';

type HiddenOptions = {
    cookieName?: string;
};

function getCookieValue(name: string): string | null {
    const cookies = document.cookie ? document.cookie.split('; ') : [];

    for (const cookie of cookies) {
        const parts = cookie.split('=');
        const key = decodeURIComponent(parts.shift() || '');

        if (key === name) {
            return decodeURIComponent(parts.join('='));
        }
    }

    return null;
}

export const hiddenModule: FormieModuleDefinition = {
    id: 'hidden',
    kind: 'field',
    match: (ctx) => {
        return !!ctx.target.querySelector(INPUT_SELECTOR);
    },
    setup: async(ctx) => {
        const options = (ctx.options || {}) as HiddenOptions;
        const cookieValue = options.cookieName ? getCookieValue(options.cookieName) : null;
        const inputs = getModuleFieldContainers(ctx).map((field) => {
            return field.querySelector(INPUT_SELECTOR);
        }).filter((input): input is HTMLInputElement => {
            return input instanceof HTMLInputElement;
        });

        // Hidden fields can mirror cookie-backed state without extra host code,
        // which keeps lightweight tracking/prefill use cases declarative in SSR.
        if (cookieValue !== null) {
            inputs.forEach((input) => {
                input.value = cookieValue;
            });
        }

        await ctx.emit('formie:module:hidden:init', {
            count: inputs.length,
        });

        return {
            destroy: () => {
                void ctx.emit('formie:module:hidden:destroy', {});
            },
        };
    },
};
