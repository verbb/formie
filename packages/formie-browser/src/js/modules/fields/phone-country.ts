import intlTelInput from 'intl-tel-input';
import intlTelInputCss from 'intl-tel-input/styles?inline';

import type { FormieModuleDefinition } from '#contracts/modules';
import { dispatchFieldEvent, getModuleFieldContainers, releaseFormValidators, retainFormValidators } from '#modules/fields/shared';
import { ensureModuleStyles } from '#modules/styles';
import { createGeoIpLookup } from '#utils/country-from-ip';
import { createDebug } from '#utils/debug';

const PHONE_SELECTOR = 'input[type="tel"][data-formie-phone-input]';
const COUNTRY_SELECTOR = 'input[data-formie-phone-country-input]';
const MODULE_ID = 'phone-country';
const PHONE_COUNTRY_VALIDATOR = 'phoneCountry';
const VALIDATOR_SCOPE = 'phone-country';
const debug = createDebug('fields', 'phone');

ensureModuleStyles(MODULE_ID, [intlTelInputCss]);

type PhoneCountryOptions = {
    countryDefaultValue?: string;
    countryAllowed?: string[];
    countryPreselectFromIp?: boolean;
    countryFromIpAction?: string;
    language?: string;
};

type PhoneInput = HTMLInputElement & {
    validator?: ReturnType<typeof intlTelInput>;
    restrictedCountries?: boolean;
    allowedCountries?: string[];
    $countryInput?: HTMLInputElement;
};

type IntlCountryCode = Parameters<ReturnType<typeof intlTelInput>['setCountry']>[0];

function registerValidators(form: HTMLFormElement | null): void {
    retainFormValidators(form, VALIDATOR_SCOPE, (validator) => {
        validator.addValidator(PHONE_COUNTRY_VALIDATOR, ({ input }) => {
            if (input.type !== 'tel') {
                return true;
            }

            const phoneInput = input as PhoneInput;
            const phoneValidator = phoneInput.validator;

            if (!input.value.trim() || !phoneValidator) {
                return true;
            }

            if (!phoneValidator.isValidNumber()) {
                return false;
            }

            const selectedCountryCode = phoneValidator.getSelectedCountryData()?.iso2 || '';

            if (phoneInput.restrictedCountries) {
                const allowedCountries = phoneInput.allowedCountries || [];

                if (!allowedCountries.includes(selectedCountryCode)) {
                    return false;
                }
            }

            if (phoneInput.$countryInput && selectedCountryCode) {
                phoneInput.$countryInput.value = selectedCountryCode.toUpperCase();
            }

            return true;
        }, ({ input, t }) => {
            const phoneInput = input as PhoneInput;
            const errorMap = ['Invalid number', 'Invalid country code', 'Too short', 'Too long'];
            const errorCode = phoneInput.validator?.getValidationError() ?? 0;
            const errorMessage = errorMap[errorCode] || errorMap[0];

            return t(errorMessage);
        });
    });
}

function unregisterValidators(form: HTMLFormElement | null): void {
    releaseFormValidators(form, VALIDATOR_SCOPE, [PHONE_COUNTRY_VALIDATOR]);
}

function buildOptions(options: PhoneCountryOptions): Record<string, unknown> {
    const intlOptions: Record<string, unknown> = {
        allowDropdown: true,
        nationalMode: false,
        separateDialCode: true,
        initialCountry: 'auto',
        autoPlaceholder: 'off',
        formatOnDisplay: false,
        formatAsYouType: false,
        loadUtils: () => import('intl-tel-input/utils'),
    };

    const allowedCountries = (options.countryAllowed || [])
        .map((item) => {
            return item.toLowerCase();
        })
        .filter(Boolean);

    if (allowedCountries.length) {
        intlOptions.onlyCountries = allowedCountries;
        intlOptions.initialCountry = allowedCountries[0];

        // One allowed country behaves more like a formatting helper than a full
        // country-picker UI, so tighten the plugin options accordingly.
        if (allowedCountries.length === 1) {
            intlOptions.allowDropdown = false;
            intlOptions.separateDialCode = false;
            intlOptions.nationalMode = true;
        }
    }

    if (options.countryDefaultValue) {
        intlOptions.initialCountry = options.countryDefaultValue.toLowerCase();
    } else if (options.countryPreselectFromIp) {
        intlOptions.initialCountry = 'auto';
        intlOptions.geoIpLookup = createGeoIpLookup(options.countryFromIpAction);
    }

    if (Array.isArray(intlOptions.onlyCountries) && typeof intlOptions.initialCountry === 'string') {
        const initialCountry = intlOptions.initialCountry.toLowerCase();
        if (!intlOptions.onlyCountries.includes(initialCountry)) {
            intlOptions.onlyCountries.push(initialCountry);
        }
    }

    return intlOptions;
}

function initPhoneField(phoneInput: PhoneInput, countryInput: HTMLInputElement, options: PhoneCountryOptions): () => void {
    const intlOptions = buildOptions(options);

    dispatchFieldEvent(phoneInput, MODULE_ID, 'before-init', {
        phoneCountry: phoneInput,
        options: intlOptions,
    });

    const validator = intlTelInput(phoneInput, intlOptions);
    const allowedCountries = Array.isArray((intlOptions as { onlyCountries?: unknown[] }).onlyCountries)
        ? (intlOptions as { onlyCountries?: unknown[] }).onlyCountries!.map(String)
        : [];
    phoneInput.validator = validator;
    phoneInput.allowedCountries = allowedCountries;
    phoneInput.$countryInput = countryInput;
    phoneInput.restrictedCountries = allowedCountries.length > 0;

    if (countryInput.value) {
        validator.setCountry(countryInput.value.toLowerCase() as IntlCountryCode);
    }

    const syncCountry = () => {
        const countryData = validator.getSelectedCountryData();
        const isoCode = countryData?.iso2 || '';
        if (isoCode) {
            // Keep the hidden country field authoritative for submit payloads while
            // the tel input focuses on the visible formatted number experience.
            countryInput.value = isoCode.toUpperCase();
        }
    };

    phoneInput.addEventListener('countrychange', syncCountry);
    phoneInput.addEventListener('blur', syncCountry);
    syncCountry();
    debug.log('Initialized.', {
        inputName: phoneInput.name,
        restrictedCountries: phoneInput.restrictedCountries,
    });

    dispatchFieldEvent(phoneInput, MODULE_ID, 'init', {
        phoneCountry: phoneInput,
        validator,
        validatorOptions: intlOptions,
    });

    return () => {
        phoneInput.removeEventListener('countrychange', syncCountry);
        phoneInput.removeEventListener('blur', syncCountry);
        validator.destroy();
        delete phoneInput.allowedCountries;
        delete phoneInput.validator;
        delete phoneInput.$countryInput;
        delete phoneInput.restrictedCountries;
        debug.log('Destroyed.', {
            inputName: phoneInput.name,
        });
    };
}

export const phoneCountryModule: FormieModuleDefinition = {
    id: MODULE_ID,
    kind: 'field',
    match: (ctx) => {
        return !!ctx.target.querySelector(PHONE_SELECTOR);
    },
    setup: async (ctx) => {
        const options = (ctx.options || {}) as PhoneCountryOptions;
        const fields = getModuleFieldContainers(ctx);

        registerValidators(ctx.form);

        const cleanups = fields.map((field) => {
            const phoneInput = field.querySelector(PHONE_SELECTOR);
            const countryInput = field.querySelector(COUNTRY_SELECTOR);

            if (!(phoneInput instanceof HTMLInputElement) || !(countryInput instanceof HTMLInputElement)) {
                debug.warn('Missing phone/country input; skipping field.');
                return () => { };
            }

            return initPhoneField(phoneInput as PhoneInput, countryInput, options);
        });
        debug.log('Module setup.', { fieldCount: fields.length });

        await ctx.emit('formie:module:phone-country:init', {
            count: cleanups.length,
        });

        return {
            destroy: () => {
                cleanups.forEach((cleanup) => {
                    cleanup();
                });

                unregisterValidators(ctx.form);
                debug.log('Module destroy.', { fieldCount: fields.length });
                void ctx.emit('formie:module:phone-country:destroy', {});
            },
        };
    },
};
