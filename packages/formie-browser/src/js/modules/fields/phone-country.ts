import intlTelInput from 'intl-tel-input';
import intlTelInputCss from 'intl-tel-input/styles?inline';

import type { BrowserModuleDefinition } from '#contracts/modules';
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
    countryDefaultValue?: string | null;
    allowedCountryCodes?: string[];
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

function buildOptions(options: PhoneCountryOptions, form: HTMLFormElement | null): Record<string, unknown> {
    const intlOptions: Record<string, unknown> = {
        allowDropdown: true,
        nationalMode: false,
        separateDialCode: true,
        initialCountry: '',
        autoPlaceholder: 'off',
        formatOnDisplay: false,
        formatAsYouType: false,
        loadUtils: () => import('intl-tel-input/utils'),
    };

    const allowedCountries = (options.allowedCountryCodes || [])
        .map((item) => {
            return item.toLowerCase();
        })
        .filter(Boolean);

    if (allowedCountries.length) {
        intlOptions.onlyCountries = allowedCountries;

        // One allowed country behaves more like a formatting helper than a full
        // country-picker UI, so tighten the plugin options accordingly.
        if (allowedCountries.length === 1) {
            intlOptions.initialCountry = allowedCountries[0];
            intlOptions.allowDropdown = false;
            intlOptions.separateDialCode = false;
            intlOptions.nationalMode = true;
        }
    }

    if (options.countryDefaultValue) {
        intlOptions.initialCountry = options.countryDefaultValue.toLowerCase();
    } else if (options.countryPreselectFromIp) {
        intlOptions.initialCountry = 'auto';
        intlOptions.geoIpLookup = createGeoIpLookup(options.countryFromIpAction, form);
    }

    if (Array.isArray(intlOptions.onlyCountries) && options.countryDefaultValue) {
        const initialCountry = options.countryDefaultValue.toLowerCase();
        if (!intlOptions.onlyCountries.includes(initialCountry)) {
            intlOptions.onlyCountries.push(initialCountry);
        }
    }

    return intlOptions;
}

function initPhoneField(phoneInput: PhoneInput, countryInput: HTMLInputElement, options: PhoneCountryOptions): () => void {
    const intlOptions = buildOptions(options, phoneInput.closest('form'));

    dispatchFieldEvent(phoneInput, MODULE_ID, 'before-init', {
        phoneCountry: phoneInput,
        options: intlOptions,
    });

    let validator = intlTelInput(phoneInput, intlOptions);
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
        // Clearing the selection must also clear the submitted country.
        countryInput.value = isoCode.toUpperCase();
    };

    phoneInput.addEventListener('countrychange', syncCountry);
    phoneInput.addEventListener('blur', syncCountry);
    syncCountry();

    // Hidden input values also change their native reset default, so retain the initial country.
    let defaultCountry = countryInput.value;
    let destroyed = false;
    void validator.promise.then(() => {
        if (!destroyed && intlOptions.initialCountry === 'auto') {
            defaultCountry ||= validator.getSelectedCountryData()?.iso2?.toUpperCase() || '';
        }
    }, () => {});

    const form = phoneInput.form;
    const onReset = (event: Event) => {
        // Wait for all reset listeners and the browser's native value restoration.
        window.setTimeout(() => {
            if (!destroyed && !event.defaultPrevented) {
                if (defaultCountry) {
                    validator.setCountry(defaultCountry.toLowerCase() as IntlCountryCode);
                    validator.setNumber(phoneInput.defaultValue);
                } else {
                    // intl-tel-input rejects setCountry('') and retains the last country
                    // in setNumber(''), so recreate it to restore an unset default.
                    validator.destroy();
                    validator = intlTelInput(phoneInput, { ...intlOptions, initialCountry: '' });
                    phoneInput.validator = validator;
                    syncCountryButton();
                }

                syncCountry();
            }
        }, 0);
    };
    form?.addEventListener('reset', onReset);

    // Keep the country dropdown button in sync when the tel input is enabled/disabled after
    // init (multi-page page-hidden, or conditions). intl-tel-input disables the button to match
    // a disabled input at init, but won't re-enable it when `disabled` is later removed.
    const syncCountryButton = () => {
        const button = phoneInput.closest('.iti')?.querySelector('.iti__selected-country');

        if (!(button instanceof HTMLElement)) {
            return;
        }

        if (phoneInput.disabled) {
            button.setAttribute('disabled', 'true');
        } else {
            button.removeAttribute('disabled');
        }
    };

    syncCountryButton();
    const countryButtonObserver = new MutationObserver(() => {
        syncCountryButton();
    });
    countryButtonObserver.observe(phoneInput, { attributes: true, attributeFilter: ['disabled'] });

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
        destroyed = true;
        form?.removeEventListener('reset', onReset);
        phoneInput.removeEventListener('countrychange', syncCountry);
        phoneInput.removeEventListener('blur', syncCountry);
        countryButtonObserver.disconnect();
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

export const phoneCountryModule: BrowserModuleDefinition = {
    moduleId: `formie:${MODULE_ID}`,
    version: 2,
    surfaces: ['server-rendered', 'client-rendered', 'cp-edit'],
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
