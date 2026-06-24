import { getTextLimitMetrics } from '@verbb/formie-core';

import type { FormieModuleDefinition } from '#contracts/modules';
import { getModuleFieldTarget, releaseFormValidators, retainFormValidators } from '#modules/fields/shared';

const INPUT_SELECTOR = 'input[data-formie-password-input]';
const PASSWORD_VALIDATORS = [
    'passwordMinLength',
    'passwordUppercase',
    'passwordLowercase',
    'passwordSpecialCharacter',
] as const;
const VALIDATOR_SCOPE = 'password-validation';

function isPasswordInput(input: Element): input is HTMLInputElement {
    return input instanceof HTMLInputElement && input.matches(INPUT_SELECTOR);
}

function getMinLength(input: HTMLInputElement): number {
    return parseInt(input.getAttribute('data-formie-password-min-length') || '', 10) || 0;
}

function hasAnyPasswordValidationAttributes(input: HTMLInputElement): boolean {
    return input.hasAttribute('data-formie-password-min-length') ||
        input.hasAttribute('data-formie-password-require-uppercase') ||
        input.hasAttribute('data-formie-password-require-lowercase') ||
        input.hasAttribute('data-formie-password-require-special-character');
}

function hasNoRawValue(input: HTMLInputElement): boolean {
    return input.value === '';
}

function registerValidators(form: HTMLFormElement | null): void {
    retainFormValidators(form, VALIDATOR_SCOPE, (validator) => {
        validator.addValidator('passwordMinLength', ({ input }) => {
            if (!isPasswordInput(input)) {
                return true;
            }

            const limit = getMinLength(input);

            if (!limit || hasNoRawValue(input)) {
                return true;
            }

            return getTextLimitMetrics(input.value).graphemeCount >= limit;
        }, ({ label, input, t: translate }) => {
            return input.getAttribute('data-formie-validation-min-characters-message')
                || translate('{label} must be no less than {min} characters.', {
                    label,
                    min: input.getAttribute('data-formie-password-min-length') || '',
                });
        });

        validator.addValidator('passwordUppercase', ({ input }) => {
            if (!isPasswordInput(input) || !input.hasAttribute('data-formie-password-require-uppercase')) {
                return true;
            }

            if (hasNoRawValue(input)) {
                return true;
            }

            return /[A-Z]/.test(input.value);
        }, ({ label, input, t: translate }) => {
            return input.getAttribute('data-formie-validation-password-uppercase-message')
                || translate('{label} must contain at least one uppercase letter.', { label });
        });

        validator.addValidator('passwordLowercase', ({ input }) => {
            if (!isPasswordInput(input) || !input.hasAttribute('data-formie-password-require-lowercase')) {
                return true;
            }

            if (hasNoRawValue(input)) {
                return true;
            }

            return /[a-z]/.test(input.value);
        }, ({ label, input, t: translate }) => {
            return input.getAttribute('data-formie-validation-password-lowercase-message')
                || translate('{label} must contain at least one lowercase letter.', { label });
        });

        validator.addValidator('passwordSpecialCharacter', ({ input }) => {
            if (!isPasswordInput(input) || !input.hasAttribute('data-formie-password-require-special-character')) {
                return true;
            }

            if (hasNoRawValue(input)) {
                return true;
            }

            return /[^a-zA-Z0-9]/.test(input.value);
        }, ({ label, input, t: translate }) => {
            return input.getAttribute('data-formie-validation-password-special-character-message')
                || translate('{label} must contain at least one special character.', { label });
        });
    });
}

function unregisterValidators(form: HTMLFormElement | null): void {
    releaseFormValidators(form, VALIDATOR_SCOPE, PASSWORD_VALIDATORS);
}

export const passwordValidationModule: FormieModuleDefinition = {
    id: 'password-validation',
    kind: 'field',
    match: (ctx) => {
        return !!ctx.target.querySelector(`${INPUT_SELECTOR}[data-formie-password-min-length], ${INPUT_SELECTOR}[data-formie-password-require-uppercase], ${INPUT_SELECTOR}[data-formie-password-require-lowercase], ${INPUT_SELECTOR}[data-formie-password-require-special-character]`);
    },
    setup: async (ctx) => {
        const field = getModuleFieldTarget(ctx);
        const inputs = Array.from((field || ctx.target).querySelectorAll(INPUT_SELECTOR)).filter((input): input is HTMLInputElement => {
            return input instanceof HTMLInputElement && hasAnyPasswordValidationAttributes(input);
        });

        registerValidators(ctx.form);

        await ctx.emit('formie:module:password-validation:init', {
            count: inputs.length,
        });

        return {
            destroy: () => {
                unregisterValidators(ctx.form);
                void ctx.emit('formie:module:password-validation:destroy', {});
            },
        };
    },
};
