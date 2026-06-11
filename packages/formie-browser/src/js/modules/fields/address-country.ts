import type { FormieModuleDefinition } from '#contracts/modules';
import { ADDRESS_SELECTORS, findAddressFieldInput } from '#modules/address/constants';
import { getModuleFieldContainers } from '#modules/fields/shared';
import { fetchCountryFromIp } from '#utils/country-from-ip';
import { createDebug } from '#utils/debug';

const MODULE_ID = 'address-country';
const COUNTRY_SELECTOR = ADDRESS_SELECTORS.country;
const debug = createDebug('fields', 'address-country');

type ComboboxSelect = HTMLSelectElement & {
    _formieTomSelect?: {
        setValue: (value: string, silent?: boolean) => void;
    };
};

type AddressCountryOptions = {
    countryPreselectFromIp?: boolean;
    countryAllowed?: string[];
    countryOptionValue?: 'short' | 'full';
    countryFromIpAction?: string;
};

function resolveCountrySelectValue(
    countryCode: string,
    countryName: string | null | undefined,
    optionValue: 'short' | 'full',
): string {
    if (optionValue === 'full' && countryName) {
        return countryName;
    }

    return countryCode.toUpperCase();
}

function isCountryAllowed(countryCode: string, countryAllowed: string[] = []): boolean {
    if (!countryAllowed.length) {
        return true;
    }

    const upperCode = countryCode.toUpperCase();

    return countryAllowed.some((allowed) => allowed.toUpperCase() === upperCode);
}

function optionExists(select: HTMLSelectElement, value: string): boolean {
    return Array.from(select.options).some((option) => option.value === value);
}

async function setCountrySelectValue(select: HTMLSelectElement, value: string): Promise<void> {
    for (let attempt = 0; attempt < 20; attempt += 1) {
        const combobox = (select as ComboboxSelect)._formieTomSelect;

        if (combobox || !select.hasAttribute('data-formie-combobox-input')) {
            if (combobox) {
                combobox.setValue(value, true);
            } else {
                select.value = value;
            }

            select.dispatchEvent(new Event('change', { bubbles: true }));
            return;
        }

        await new Promise((resolve) => {
            window.setTimeout(resolve, 50);
        });
    }

    debug.warn('Timed out waiting for country combobox initialisation.');
}

async function preselectCountry(
    addressRoot: HTMLElement,
    options: AddressCountryOptions,
): Promise<void> {
    const countryControl = findAddressFieldInput(addressRoot, 'country');

    if (!(countryControl instanceof HTMLSelectElement)) {
        debug.warn('Country control not found or not a select; skipping preselect.');
        return;
    }

    if (countryControl.value.trim()) {
        return;
    }

    const response = await fetchCountryFromIp(options.countryFromIpAction);

    if (!response?.countryCode) {
        return;
    }

    if (!isCountryAllowed(response.countryCode, options.countryAllowed)) {
        debug.log('Detected country is not in the allowed list; skipping preselect.', {
            countryCode: response.countryCode,
        });
        return;
    }

    const optionValue = options.countryOptionValue === 'full' ? 'full' : 'short';
    const selectValue = resolveCountrySelectValue(
        response.countryCode,
        response.countryName,
        optionValue,
    );

    if (!optionExists(countryControl, selectValue)) {
        debug.warn('Detected country is not available in the country dropdown; skipping preselect.', {
            selectValue,
        });
        return;
    }

    await setCountrySelectValue(countryControl, selectValue);
    debug.log('Preselected country from IP.', { selectValue });
}

export const addressCountryModule: FormieModuleDefinition = {
    id: MODULE_ID,
    kind: 'field',
    match: (ctx) => {
        return !!ctx.target.querySelector(COUNTRY_SELECTOR);
    },
    setup: async (ctx) => {
        const options = (ctx.options || {}) as AddressCountryOptions;

        if (!options.countryPreselectFromIp) {
            return {
                destroy: () => {},
            };
        }

        const fields = getModuleFieldContainers(ctx);
        const tasks = fields.map(async (field) => {
            const addressRoot = field.closest('[data-formie-field-type="address"]')
                || field.closest('[data-formie-address-field-layout]')?.closest('[data-formie-field]')
                || field;

            if (!(addressRoot instanceof HTMLElement)) {
                return;
            }

            await preselectCountry(addressRoot, options);
        });

        await Promise.all(tasks);
        debug.log('Module setup.', { fieldCount: fields.length });

        return {
            destroy: () => {
                debug.log('Module destroy.', { fieldCount: fields.length });
            },
        };
    },
};
