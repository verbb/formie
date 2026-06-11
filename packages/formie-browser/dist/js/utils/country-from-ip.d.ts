export type CountryFromIpResponse = {
    countryCode?: string | null;
    countryName?: string | null;
};
export declare function fetchCountryFromIp(action?: string): Promise<CountryFromIpResponse | null>;
export declare function createGeoIpLookup(action?: string): (callback: (countryCode: string) => void) => void;
//# sourceMappingURL=country-from-ip.d.ts.map