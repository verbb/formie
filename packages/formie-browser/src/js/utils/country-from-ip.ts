export type CountryFromIpResponse = {
    countryCode?: string | null;
    countryName?: string | null;
};

const DEFAULT_COUNTRY_FROM_IP_ACTION = 'formie/address/country-from-ip';

let cachedLookup: Promise<CountryFromIpResponse | null> | null = null;

function buildActionUrl(action: string): string {
    return new URL(action.startsWith('/') ? action : `/actions/${action}`, window.location.origin).toString();
}

export async function fetchCountryFromIp(
    action: string = DEFAULT_COUNTRY_FROM_IP_ACTION,
): Promise<CountryFromIpResponse | null> {
    if (!cachedLookup) {
        cachedLookup = (async () => {
            try {
                const response = await fetch(buildActionUrl(action), {
                    headers: {
                        Accept: 'application/json',
                    },
                });

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
        })();
    }

    return cachedLookup;
}

export function createGeoIpLookup(
    action: string = DEFAULT_COUNTRY_FROM_IP_ACTION,
): (callback: (countryCode: string) => void) => void {
    return (callback) => {
        void fetchCountryFromIp(action).then((data) => {
            callback(data?.countryCode?.toLowerCase() || '');
        });
    };
}
