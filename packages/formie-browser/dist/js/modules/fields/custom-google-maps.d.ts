import type { FormieModuleDefinition } from '#contracts/modules';
type LatLng = {
    lat: number;
    lng: number;
};
type GoogleAddressComponent = {
    long_name: string;
    short_name: string;
    types: string[];
};
type GooglePlace = {
    formatted_address?: string;
    name?: string;
    place_id?: string;
    address_components?: GoogleAddressComponent[];
    geometry?: {
        location?: {
            lat: () => number;
            lng: () => number;
        };
    };
};
type GoogleMapsNamespace = {
    maps: {
        Map: new (element: HTMLElement, options: Record<string, unknown>) => GoogleMap;
        Marker: new (options: Record<string, unknown>) => GoogleMarker;
        LatLng: new (lat: number, lng: number) => unknown;
        importLibrary?: (name: string) => Promise<unknown>;
        places: {
            Autocomplete: new (input: HTMLInputElement, options?: Record<string, unknown>) => GoogleAutocomplete;
        };
    };
};
type GoogleMap = {
    setCenter: (latLng: LatLng) => void;
    setZoom: (zoom: number) => void;
    getZoom: () => number | undefined;
    addListener: (event: string, callback: (event: {
        latLng?: {
            lat: () => number;
            lng: () => number;
        };
    }) => void) => void;
};
type GoogleMarker = {
    setPosition: (latLng: LatLng) => void;
    getPosition: () => {
        lat: () => number;
        lng: () => number;
    } | null | undefined;
    addListener: (event: string, callback: () => void) => void;
};
type GoogleAutocomplete = {
    addListener: (event: string, callback: () => void) => void;
    getPlace: () => GooglePlace;
};
declare global {
    interface Window {
        google?: GoogleMapsNamespace;
    }
}
export declare const customGoogleMapsModule: FormieModuleDefinition;
export {};
//# sourceMappingURL=custom-google-maps.d.ts.map