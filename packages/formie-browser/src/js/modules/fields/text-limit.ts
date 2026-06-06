import textLimitCss from '#theme-css/fields/_text-limit.css?inline';

import { getTextLimitMetrics } from '@verbb/formie-core';

import type { FormieModuleDefinition } from '#contracts/modules';
import { getModuleFieldTarget, releaseFormValidators, retainFormValidators } from '#modules/fields/shared';
import { ensureModuleStyles } from '#modules/styles';
import { t } from '#utils/i18n';

const INPUT_SELECTOR = 'input[data-formie-single-line-text-input], textarea[data-formie-multi-line-text-input]';
const TEXT_LIMIT_VALIDATORS = [
    'textMinCharacterLimit',
    'textMaxCharacterLimit',
    'textMinWordLimit',
    'textMaxWordLimit',
] as const;
const VALIDATOR_SCOPE = 'text-limit';
const ALLOW_OVERTYPE_ATTR = 'data-formie-text-limit-allow-overtype';
const TEXT_LIMIT_CHARACTERS_ALLOWED = '{count, plural, one{character allowed} other{characters allowed}}';
const TEXT_LIMIT_CHARACTERS_LEFT = '{count, plural, one{character left} other{characters left}}';
const TEXT_LIMIT_CHARACTERS_OVER = '{count, plural, one{character over limit} other{characters over limit}}';
const TEXT_LIMIT_WORDS_ALLOWED = '{count, plural, one{word allowed} other{words allowed}}';
const TEXT_LIMIT_WORDS_LEFT = '{count, plural, one{word left} other{words left}}';
const TEXT_LIMIT_WORDS_OVER = '{count, plural, one{word over limit} other{words over limit}}';
const limitTargetCache = new WeakMap<HTMLInputElement | HTMLTextAreaElement, HTMLElement | null>();

type TextLimitUnit = 'character' | 'word';
type TextLimitCounterState = 'allowed' | 'left' | 'over';

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
            return input.getAttribute('data-formie-validation-min-characters-message')
                || t('{label} must be no less than {min} characters.', {
                    label,
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
            return input.getAttribute('data-formie-validation-max-characters-message')
                || t('{label} must be no greater than {max} characters.', {
                    label,
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
            return input.getAttribute('data-formie-validation-min-words-message')
                || t('{label} must be no less than {min} words.', {
                    label,
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
            return input.getAttribute('data-formie-validation-max-words-message')
                || t('{label} must be no greater than {max} words.', {
                    label,
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

function getTextLimitCounterState(
    input: HTMLInputElement | HTMLTextAreaElement,
    remaining: number,
    unit: TextLimitUnit,
): TextLimitCounterState {
    const isEmpty = unit === 'character' ? input.value === '' : input.value.trim() === '';

    if (isEmpty) {
        return 'allowed';
    }

    if (remaining < 0) {
        return 'over';
    }

    return 'left';
}

function getTextLimitCounterMessageKey(unit: TextLimitUnit, state: TextLimitCounterState): string {
    if (unit === 'character') {
        if (state === 'allowed') {
            return TEXT_LIMIT_CHARACTERS_ALLOWED;
        }

        if (state === 'over') {
            return TEXT_LIMIT_CHARACTERS_OVER;
        }

        return TEXT_LIMIT_CHARACTERS_LEFT;
    }

    if (state === 'allowed') {
        return TEXT_LIMIT_WORDS_ALLOWED;
    }

    if (state === 'over') {
        return TEXT_LIMIT_WORDS_OVER;
    }

    return TEXT_LIMIT_WORDS_LEFT;
}

function renderCounter(
    target: HTMLElement,
    input: HTMLInputElement | HTMLTextAreaElement,
    remaining: number,
    limit: number,
    unit: TextLimitUnit,
): void {
    const state = getTextLimitCounterState(input, remaining, unit);
    const displayCount = state === 'allowed' ? limit : Math.abs(remaining);
    const number = document.createElement('span');
    number.className = state === 'over' ? 'formie-limit-number formie-limit-number-error' : 'formie-limit-number';
    number.textContent = String(displayCount);
    const messageKey = getTextLimitCounterMessageKey(unit, state);

    target.replaceChildren(
        number,
        document.createTextNode(` ${t(messageKey, { count: displayCount })}`)
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
        renderCounter(target, input, remaining, maxChars, 'character');

        return;
    }

    if (maxWords > 0) {
        const remaining = maxWords - metrics.wordCount;
        renderCounter(target, input, remaining, maxWords, 'word');
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
