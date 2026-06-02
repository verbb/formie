import textLimitCss from '#theme/fields/_text-limit.css?inline';

import { getTextLimitMetrics } from '@verbb/formie-core';

import type { FormieModuleDefinition } from '#contracts/modules';
import { getModuleFieldTarget, releaseFormValidators, retainFormValidators } from '#modules/fields/shared';
import { ensureModuleStyles } from '#modules/styles';

const INPUT_SELECTOR = 'input[data-formie-single-line-text-input], textarea[data-formie-multi-line-text-input]';
const TEXT_LIMIT_VALIDATORS = [
    'textMinCharacterLimit',
    'textMaxCharacterLimit',
    'textMinWordLimit',
    'textMaxWordLimit',
] as const;
const VALIDATOR_SCOPE = 'text-limit';
const ALLOW_OVERTYPE_ATTR = 'data-formie-text-limit-allow-overtype';
const limitTargetCache = new WeakMap<HTMLInputElement | HTMLTextAreaElement, HTMLElement | null>();

ensureModuleStyles('text-limit', [textLimitCss]);

type TextLimitOptions = {
    allowOvertype?: boolean;
};

function isTextLimitInput(input: Element): input is HTMLInputElement | HTMLTextAreaElement {
    return input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement;
}

function getLimitValue(input: HTMLInputElement | HTMLTextAreaElement, attribute: string): number {
    return parseInt(input.getAttribute(attribute) || '', 10) || 0;
}

function hasAnyLimitAttributes(input: HTMLInputElement | HTMLTextAreaElement): boolean {
    return input.hasAttribute('data-formie-min-chars') ||
        input.hasAttribute('data-formie-max-chars') ||
        input.hasAttribute('data-formie-min-words') ||
        input.hasAttribute('data-formie-max-words');
}

function hasCounterLimitAttributes(input: HTMLInputElement | HTMLTextAreaElement): boolean {
    return input.hasAttribute('data-formie-max-chars') || input.hasAttribute('data-formie-max-words');
}

function allowsOvertype(input: HTMLInputElement | HTMLTextAreaElement): boolean {
    return input.hasAttribute(ALLOW_OVERTYPE_ATTR);
}

function hasNoRawValue(input: HTMLInputElement | HTMLTextAreaElement): boolean {
    // Character limits count literal spaces, so only skip validation for a
    // truly empty control, not whitespace-only input.
    return input.value === '';
}

function registerValidators(form: HTMLFormElement | null): void {
    // Register once per form even when many text-limit fields mount, otherwise
    // the validator would evaluate identical limit rules multiple times.
    retainFormValidators(form, VALIDATOR_SCOPE, (validator) => {
        validator.addValidator('textMinCharacterLimit', ({ input }) => {
            if (!isTextLimitInput(input)) {
                return true;
            }

            const limit = getLimitValue(input, 'data-formie-min-chars');

            if (!limit || hasNoRawValue(input)) {
                return true;
            }

            return getTextLimitMetrics(input.value).graphemeCount >= limit;
        }, ({ label, input, t }) => {
            return t('{attribute} must be no less than {min} characters.', {
                attribute: label,
                min: input.getAttribute('data-formie-min-chars') || '',
            });
        });

        validator.addValidator('textMaxCharacterLimit', ({ input }) => {
            if (!isTextLimitInput(input)) {
                return true;
            }

            if (allowsOvertype(input)) {
                return true;
            }

            const limit = getLimitValue(input, 'data-formie-max-chars');

            if (!limit || hasNoRawValue(input)) {
                return true;
            }

            return getTextLimitMetrics(input.value).graphemeCount <= limit;
        }, ({ label, input, t }) => {
            return t('{attribute} must be no greater than {max} characters.', {
                attribute: label,
                max: input.getAttribute('data-formie-max-chars') || '',
            });
        });

        validator.addValidator('textMinWordLimit', ({ input }) => {
            if (!isTextLimitInput(input)) {
                return true;
            }

            const limit = getLimitValue(input, 'data-formie-min-words');

            if (!limit || input.value.trim() === '') {
                return true;
            }

            return getTextLimitMetrics(input.value).wordCount >= limit;
        }, ({ label, input, t }) => {
            return t('{attribute} must be no less than {min} words.', {
                attribute: label,
                min: input.getAttribute('data-formie-min-words') || '',
            });
        });

        validator.addValidator('textMaxWordLimit', ({ input }) => {
            if (!isTextLimitInput(input)) {
                return true;
            }

            if (allowsOvertype(input)) {
                return true;
            }

            const limit = getLimitValue(input, 'data-formie-max-words');

            if (!limit || input.value.trim() === '') {
                return true;
            }

            return getTextLimitMetrics(input.value).wordCount <= limit;
        }, ({ label, input, t }) => {
            return t('{attribute} must be no greater than {max} words.', {
                attribute: label,
                max: input.getAttribute('data-formie-max-words') || '',
            });
        });
    });
}

function unregisterValidators(form: HTMLFormElement | null): void {
    releaseFormValidators(form, VALIDATOR_SCOPE, TEXT_LIMIT_VALIDATORS);
}

function getLimitTarget(input: HTMLInputElement | HTMLTextAreaElement): HTMLElement | null {
    if (limitTargetCache.has(input)) {
        return limitTargetCache.get(input) || null;
    }

    const field = input.closest('[data-formie-field-handle]') as HTMLElement | null;

    if (!field) {
        limitTargetCache.set(input, null);
        return null;
    }

    const existingTarget = field.querySelector('[data-formie-limit-text]') as HTMLElement | null;
    if (existingTarget) {
        limitTargetCache.set(input, existingTarget);
        return existingTarget;
    }

    const control = field.querySelector('[data-formie-field-control]') as HTMLElement | null;
    const target = document.createElement('div');
    target.className = 'formie-field-limit formie-limit-text';
    target.setAttribute('data-formie-field-limit', 'true');
    target.setAttribute('data-formie-limit-text', 'true');

    if (control?.parentElement) {
        control.insertAdjacentElement('afterend', target);
        limitTargetCache.set(input, target);
        return target;
    }

    field.appendChild(target);
    limitTargetCache.set(input, target);

    return target;
}

function renderCounter(target: HTMLElement, remaining: number, unit: 'character' | 'word'): void {
    const number = document.createElement('span');
    number.className = remaining < 0 ? 'formie-limit-number formie-limit-number-error' : 'formie-limit-number';
    number.textContent = String(remaining);

    target.replaceChildren(
        number,
        document.createTextNode(` ${Math.abs(remaining) === 1 ? unit : `${unit}s`} left`)
    );
}

function updateCounter(input: HTMLInputElement | HTMLTextAreaElement): void {
    const maxChars = getLimitValue(input, 'data-formie-max-chars');
    const maxWords = getLimitValue(input, 'data-formie-max-words');
    const target = getLimitTarget(input);

    if (!target) {
        return;
    }

    const metrics = getTextLimitMetrics(input.value);

    if (maxChars > 0) {
        const remaining = maxChars - metrics.graphemeCount;
        renderCounter(target, remaining, 'character');

        return;
    }

    if (maxWords > 0) {
        const remaining = maxWords - metrics.wordCount;
        renderCounter(target, remaining, 'word');
    }
}

export const textLimitModule: FormieModuleDefinition = {
    id: 'text-limit',
    kind: 'field',
    match: (ctx) => {
        return !!ctx.target.querySelector(INPUT_SELECTOR);
    },
    setup: async (ctx) => {
        const options = (ctx.options || {}) as TextLimitOptions;
        const field = getModuleFieldTarget(ctx);
        const inputs = Array.from((field || ctx.target).querySelectorAll(INPUT_SELECTOR)).filter((input): input is HTMLInputElement | HTMLTextAreaElement => {
            return (input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement) && hasAnyLimitAttributes(input);
        });
        const counterInputs = inputs.filter((input) => {
            return hasCounterLimitAttributes(input);
        });

        if (options.allowOvertype) {
            inputs.forEach((input) => {
                input.setAttribute(ALLOW_OVERTYPE_ATTR, 'true');
            });
        }

        registerValidators(ctx.form);

        const unbinds = counterInputs.map((input) => {
            const handler = () => {
                updateCounter(input);
            };

            input.addEventListener('input', handler);
            input.addEventListener('change', handler);
            updateCounter(input);

            return () => {
                input.removeEventListener('input', handler);
                input.removeEventListener('change', handler);
            };
        });

        await ctx.emit('formie:module:text-limit:init', {
            count: inputs.length,
        });

        return {
            destroy: () => {
                unbinds.forEach((unbind) => {
                    unbind();
                });

                if (options.allowOvertype) {
                    inputs.forEach((input) => {
                        input.removeAttribute(ALLOW_OVERTYPE_ATTR);
                    });
                }

                unregisterValidators(ctx.form);
                void ctx.emit('formie:module:text-limit:destroy', {});
            },
        };
    },
};
