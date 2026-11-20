import { type BrowserRequestOptions } from './request-profile.js';
import type { ClientFieldDefinition } from './types.js';
/**
 * An Address field's State / Province part with **Input Mode** set to **Dropdown when available** shows the selected
 * country's subdivisions as a dropdown, names itself after them (such as "County" or "Prefecture"), and hides for
 * countries whose addresses don't use them, as the `formie:address-state` module does in server-rendered forms.
 * Adapters render it from the part definition `addressStatePart()` returns, so they keep their own controls.
 */
export type AddressSubdivisions = {
    label: string;
    used: boolean;
    options: Array<{
        label: string;
        value: string;
        name?: string;
        short?: string;
    }>;
};
export type AddressStatePart = {
    /** The part to render, with the country's label and, when it has subdivisions, a dropdown of them. */
    field: ClientFieldDefinition;
    hidden: boolean;
    /** The answer the part should hold for this country, which adapters store when it differs. */
    value: string;
    /** True while the country's subdivisions are loading. */
    loading: boolean;
};
export declare function isDynamicAddressStatePart(field: ClientFieldDefinition): boolean;
/**
 * The country's subdivisions once loaded: null when they couldn't be loaded, and undefined until they have been.
 */
export declare function cachedAddressSubdivisions(field: ClientFieldDefinition, country: string): AddressSubdivisions | null | undefined;
export declare function loadAddressSubdivisions(field: ClientFieldDefinition, country: string, requestOptions?: BrowserRequestOptions): Promise<AddressSubdivisions | null>;
/**
 * The State / Province part for the chosen country. Without a country it's hidden when **Hide When Not Used** is on,
 * or a disabled text input otherwise. While the country's subdivisions load, the part stays as it is.
 */
export declare function addressStatePart(part: ClientFieldDefinition, country: unknown, value: unknown): AddressStatePart;
//# sourceMappingURL=address-state.d.ts.map