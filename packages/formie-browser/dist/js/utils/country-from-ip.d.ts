export type CountryFromIpResponse = {
    countryCode?: string | null;
    countryName?: string | null;
};
export declare function fetchCountryFromIp(action?: string, form?: HTMLFormElement | null): Promise<CountryFromIpResponse | null>;
export declare function createGeoIpLookup(action?: string, form?: HTMLFormElement | null): (callback: (countryCode: string) => void) => void;
//# sourceMappingURL=country-from-ip.d.ts.map