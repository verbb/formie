import type { FormieModuleDefinition } from '#contracts/modules';
import { getModuleFieldTarget, observeMatchingElements } from '#modules/fields/shared';

const MODULE_ID = 'custom-google-maps';
const GOOGLE_MAPS_SELECTOR = '[data-formie-custom-google-maps]';

type LatLng = {
    lat: number;
    lng: number;
};

type GoogleMapsSettings = {
    apiUrl?: string;
    defaultLat?: number | null;
    defaultLng?: number | null;
    defaultZoom?: number | null;
    minZoom?: number | null;
    maxZoom?: number | null;
    country?: string | null;
};

type GoogleMapsElements = {
    root: HTMLElement;
    canvas: HTMLElement | null;
    searchInput: HTMLInputElement | null;
    currentLocationButton: HTMLButtonElement | null;
    inputs: Record<string, HTMLInputElement>;
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
        Map: new(element: HTMLElement, options: Record<string, unknown>) => GoogleMap;
        Marker: new(options: Record<string, unknown>) => GoogleMarker;
        LatLng: new(lat: number, lng: number) => unknown;
        importLibrary?: (name: string) => Promise<unknown>;
        places: {
            Autocomplete: new(input: HTMLInputElement, options?: Record<string, unknown>) => GoogleAutocomplete;
        };
    };
};

type GoogleMap = {
    setCenter: (latLng: LatLng) => void;
    setZoom: (zoom: number) => void;
    getZoom: () => number | undefined;
    addListener: (event: string, callback: (event: { latLng?: { lat: () => number; lng: () => number } }) => void) => void;
};

type GoogleMarker = {
    setPosition: (latLng: LatLng) => void;
    getPosition: () => { lat: () => number; lng: () => number } | null | undefined;
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

let googleMapsPromise: Promise<GoogleMapsNamespace> | null = null;

function isCustomGoogleMaps(element: Element): element is HTMLElement {
    return element instanceof HTMLElement && element.matches(GOOGLE_MAPS_SELECTOR);
}

function parseSettings(root: HTMLElement): GoogleMapsSettings {
    const raw = root.getAttribute('data-formie-custom-google-maps-settings');

    if (!raw) {
        return {};
    }

    try {
        return JSON.parse(raw) as GoogleMapsSettings;
    } catch {
        return {};
    }
}

function findElements(root: HTMLElement): GoogleMapsElements {
    const inputs: Record<string, HTMLInputElement> = {};

    root.querySelectorAll<HTMLInputElement>('[data-formie-custom-google-maps-field]').forEach((input) => {
        const key = input.getAttribute('data-formie-custom-google-maps-field');

        if (key) {
            inputs[key] = input;
        }
    });

    return {
        root,
        canvas: root.querySelector<HTMLElement>('[data-formie-custom-google-maps-canvas]'),
        searchInput: root.querySelector<HTMLInputElement>('[data-formie-custom-google-maps-search]'),
        currentLocationButton: root.querySelector<HTMLButtonElement>('[data-formie-custom-google-maps-current-location]'),
        inputs,
    };
}

function toNumber(value: string | number | null | undefined): number | null {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const number = Number(value);

    return Number.isFinite(number) ? number : null;
}

function getCurrentLatLng(elements: GoogleMapsElements, settings: GoogleMapsSettings): LatLng {
    return {
        lat: toNumber(elements.inputs.lat?.value) ?? settings.defaultLat ?? -37.7841813,
        lng: toNumber(elements.inputs.lng?.value) ?? settings.defaultLng ?? 144.9378721,
    };
}

function getCurrentZoom(elements: GoogleMapsElements, settings: GoogleMapsSettings): number {
    return toNumber(elements.inputs.zoom?.value) ?? settings.defaultZoom ?? 11;
}

function setValue(input: HTMLInputElement | undefined | null, value: string | number | null | undefined): void {
    if (!input) {
        return;
    }

    input.value = value === null || value === undefined ? '' : String(value);
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
}

function loadScript(src: string): Promise<void> {
    return new Promise((resolve, reject) => {
        const existing = Array.from(document.scripts).find((script) => script.src === src);

        if (window.google?.maps?.places) {
            resolve();
            return;
        }

        if (existing) {
            existing.addEventListener('load', () => resolve(), { once: true });
            existing.addEventListener('error', () => reject(new Error(`Failed to load ${src}`)), { once: true });
            return;
        }

        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error(`Failed to load ${src}`));
        document.head.appendChild(script);
    });
}

function wait(ms: number): Promise<void> {
    return new Promise((resolve) => {
        window.setTimeout(resolve, ms);
    });
}

async function ensureGoogleMapsLibraries(timeoutMs = 12000, intervalMs = 50): Promise<GoogleMapsNamespace> {
    const started = Date.now();

    while (Date.now() - started < timeoutMs) {
        const maps = window.google?.maps;

        if (maps?.importLibrary) {
            try {
                if (!maps.Map) {
                    await maps.importLibrary('maps');
                }

                if (!maps.Marker) {
                    await maps.importLibrary('marker');
                }

                if (!maps.places?.Autocomplete) {
                    await maps.importLibrary('places');
                }
            } catch {
                // Keep polling; the classic constructors may still arrive after the script settles.
            }
        }

        if (window.google?.maps?.Map && window.google.maps.Marker && window.google.maps.places?.Autocomplete) {
            return window.google;
        }

        await wait(intervalMs);
    }

    throw new Error('Google Maps Places API did not initialize.');
}

function loadGoogleMaps(settings: GoogleMapsSettings): Promise<GoogleMapsNamespace> {
    if (!settings.apiUrl) {
        return Promise.reject(new Error('Google Maps API URL is missing.'));
    }

    if (!googleMapsPromise) {
        googleMapsPromise = loadScript(settings.apiUrl).then(() => ensureGoogleMapsLibraries());
    }

    return googleMapsPromise;
}

function component(components: GoogleAddressComponent[] | undefined, type: string, short = false): string {
    const match = components?.find((entry) => entry.types.includes(type));

    return short ? match?.short_name || '' : match?.long_name || '';
}

function parsePlace(place: GooglePlace): Record<string, string> {
    const components = place.address_components || [];
    const streetNumber = component(components, 'street_number');
    const route = component(components, 'route');
    const street1 = [streetNumber, route].filter(Boolean).join(' ');
    const lat = place.geometry?.location?.lat();
    const lng = place.geometry?.location?.lng();

    return {
        formatted: place.formatted_address || '',
        raw: JSON.stringify(place),
        name: place.name || '',
        street1,
        city: component(components, 'locality') || component(components, 'postal_town') || component(components, 'administrative_area_level_2'),
        state: component(components, 'administrative_area_level_1', true),
        zip: component(components, 'postal_code'),
        neighborhood: component(components, 'neighborhood') || component(components, 'sublocality'),
        county: component(components, 'administrative_area_level_2'),
        country: component(components, 'country'),
        countryCode: component(components, 'country', true),
        placeId: place.place_id || '',
        lat: lat === undefined ? '' : String(lat),
        lng: lng === undefined ? '' : String(lng),
    };
}

function applyValues(elements: GoogleMapsElements, values: Record<string, string | number>, map?: GoogleMap, marker?: GoogleMarker): void {
    Object.entries(values).forEach(([key, value]) => {
        setValue(elements.inputs[key], value);
    });

    if (values.formatted !== undefined) {
        setValue(elements.searchInput, values.formatted);
    }

    const lat = toNumber(values.lat);
    const lng = toNumber(values.lng);

    if (lat !== null && lng !== null) {
        const latLng = { lat, lng };
        marker?.setPosition(latLng);
        map?.setCenter(latLng);
    }
}

async function initGoogleMapsField(elements: GoogleMapsElements, settings: GoogleMapsSettings): Promise<() => void> {
    const google = await loadGoogleMaps(settings);
    const initialLatLng = getCurrentLatLng(elements, settings);
    const initialZoom = getCurrentZoom(elements, settings);
    let map: GoogleMap | undefined;
    let marker: GoogleMarker | undefined;

    if (elements.canvas) {
        map = new google.maps.Map(elements.canvas, {
            center: initialLatLng,
            zoom: initialZoom,
            minZoom: settings.minZoom ?? undefined,
            maxZoom: settings.maxZoom ?? undefined,
        });

        marker = new google.maps.Marker({
            position: initialLatLng,
            map,
            draggable: true,
        });

        map.addListener('click', (event) => {
            const lat = event.latLng?.lat();
            const lng = event.latLng?.lng();

            if (lat === undefined || lng === undefined) {
                return;
            }

            applyValues(elements, {
                lat,
                lng,
                zoom: map?.getZoom() || initialZoom,
            }, map, marker);
        });

        marker.addListener('dragend', () => {
            const position = marker?.getPosition();

            if (!position) {
                return;
            }

            applyValues(elements, {
                lat: position.lat(),
                lng: position.lng(),
                zoom: map?.getZoom() || initialZoom,
            }, map, marker);
        });
    }

    let autocomplete: GoogleAutocomplete | null = null;

    if (elements.searchInput) {
        autocomplete = new google.maps.places.Autocomplete(elements.searchInput, {
            fields: ['address_components', 'formatted_address', 'geometry', 'name', 'place_id'],
            componentRestrictions: settings.country ? { country: settings.country.toLowerCase() } : undefined,
        });

        autocomplete.addListener('place_changed', () => {
            const place = autocomplete?.getPlace();

            if (!place) {
                return;
            }

            applyValues(elements, parsePlace(place), map, marker);
        });
    }

    const onCurrentLocationClick = (): void => {
        navigator.geolocation?.getCurrentPosition((position) => {
            applyValues(elements, {
                lat: position.coords.latitude,
                lng: position.coords.longitude,
                zoom: map?.getZoom() || initialZoom,
            }, map, marker);
        });
    };

    elements.currentLocationButton?.addEventListener('click', onCurrentLocationClick);

    return () => {
        elements.currentLocationButton?.removeEventListener('click', onCurrentLocationClick);
    };
}

function initCustomGoogleMaps(root: HTMLElement): () => void {
    const elements = findElements(root);
    const settings = parseSettings(root);
    let cleanup: (() => void) | null = null;
    let destroyed = false;

    void initGoogleMapsField(elements, settings).then((destroy) => {
        if (destroyed) {
            destroy();
            return;
        }

        cleanup = destroy;
    }).catch((error: unknown) => {
        console.warn('[Formie] Unable to initialize Google Maps custom field.', error);
    });

    return () => {
        destroyed = true;
        cleanup?.();
    };
}

export const customGoogleMapsModule: FormieModuleDefinition = {
    id: MODULE_ID,
    kind: 'field',
    match: ({ target }) => {
        return target instanceof Element && (
            target.matches(GOOGLE_MAPS_SELECTOR) ||
            !!target.querySelector(GOOGLE_MAPS_SELECTOR)
        );
    },
    setup: async(ctx) => {
        const field = getModuleFieldTarget(ctx);
        const root = field || ctx.target;

        if (!(root instanceof Element)) {
            return;
        }

        const cleanup = observeMatchingElements(root, GOOGLE_MAPS_SELECTOR, isCustomGoogleMaps, initCustomGoogleMaps);

        return {
            destroy: cleanup,
        };
    },
};
