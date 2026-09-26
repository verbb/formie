import { browserRequest } from '@verbb/formie-core';
import { getFormBrowserRequestOptions } from './request-profile';
export type CountryFromIpResponse = {
    countryCode?: string | null;
    countryName?: string | null;
};

const DEFAULT_COUNTRY_FROM_IP_ACTION = 'formie/address/country-from-ip';

const cachedLookups = new Map<string, Promise<CountryFromIpResponse | null>>();

function buildActionUrl(action: string): string {
    return new URL(/^https?:\/\//.test(action) || action.startsWith('/') ? action : `/actions/${action}`, window.location.origin).toString();
}

export async function fetchCountryFromIp(
    action: string = DEFAULT_COUNTRY_FROM_IP_ACTION,
    form?: HTMLFormElement | null,
): Promise<CountryFromIpResponse | null> {
    const key = buildActionUrl(action);
    if (!cachedLookups.has(key)) {
        cachedLookups.set(key, (async () => {
            try {
                const response = await browserRequest(key, {
                    headers: {
                        Accept: 'application/json',
                    },
                }, getFormBrowserRequestOptions(form));

                if (!response.ok) {
                    return null;
                }

                const data = await response.json() as CountryFromIpResponse;

                if (!data?.countryCode) {
                    return null;
                }

                return data;
            } catch {
                return null;
            }
        })());
    }

    return cachedLookups.get(key)!;
}

export function createGeoIpLookup(
    action: string = DEFAULT_COUNTRY_FROM_IP_ACTION,
    form?: HTMLFormElement | null,
): (callback: (countryCode: string) => void) => void {
    return (callback) => {
        void fetchCountryFromIp(action, form).then((data) => {
            callback(data?.countryCode?.toLowerCase() || '');
        });
    };
}
