import type { FormieModuleDefinition } from '#contracts/modules';
import type * as Leaflet from 'leaflet';
import leafletCss from 'leaflet/dist/leaflet.css?inline';
import { getModuleFieldTarget, observeMatchingElements } from '#modules/fields/shared';
import { ensureModuleStyles } from '#modules/styles';

const MODULE_ID = 'custom-maps';
const MAPS_SELECTOR = '[data-formie-custom-maps]';

ensureModuleStyles(MODULE_ID, [leafletCss]);

type LatLng = {
    lat: number;
    lng: number;
};

type MapsSettings = {
    mapTiles?: string;
    mapToken?: string | Record<string, string>;
    geoService?: string;
    geoToken?: string | Record<string, string>;
    defaultLat?: number | null;
    defaultLng?: number | null;
    defaultZoom?: number | null;
    minZoom?: number | null;
    maxZoom?: number | null;
    country?: string | null;
};

type MapsElements = {
    root: HTMLElement;
    canvas: HTMLElement | null;
    searchInput: HTMLInputElement | null;
    addressInput: HTMLInputElement | null;
    latInput: HTMLInputElement | null;
    lngInput: HTMLInputElement | null;
    zoomInput: HTMLInputElement | null;
    partsInput: HTMLInputElement | null;
    currentLocationButton: HTMLButtonElement | null;
};

type NominatimResult = {
    display_name?: string;
    lat?: string;
    lon?: string;
    address?: Record<string, string | undefined>;
};

let leafletPromise: Promise<typeof Leaflet> | null = null;

function isCustomMaps(element: Element): element is HTMLElement {
    return element instanceof HTMLElement && element.matches(MAPS_SELECTOR);
}

function parseSettings(root: HTMLElement): MapsSettings {
    const raw = root.getAttribute('data-formie-custom-maps-settings');

    if (!raw) {
        return {};
    }

    try {
        return JSON.parse(raw) as MapsSettings;
    } catch {
        return {};
    }
}

function findElements(root: HTMLElement): MapsElements {
    return {
        root,
        canvas: root.querySelector<HTMLElement>('[data-formie-custom-maps-canvas]'),
        searchInput: root.querySelector<HTMLInputElement>('[data-formie-custom-maps-search]'),
        addressInput: root.querySelector<HTMLInputElement>('[data-formie-custom-map-address]'),
        latInput: root.querySelector<HTMLInputElement>('[data-formie-custom-map-lat]'),
        lngInput: root.querySelector<HTMLInputElement>('[data-formie-custom-map-lng]'),
        zoomInput: root.querySelector<HTMLInputElement>('[data-formie-custom-map-zoom]'),
        partsInput: root.querySelector<HTMLInputElement>('[data-formie-custom-maps-parts]'),
        currentLocationButton: root.querySelector<HTMLButtonElement>('[data-formie-custom-maps-current-location]'),
    };
}

function toNumber(value: string | number | null | undefined): number | null {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const number = Number(value);

    return Number.isFinite(number) ? number : null;
}

function getCurrentLatLng(elements: MapsElements, settings: MapsSettings): LatLng {
    return {
        lat: toNumber(elements.latInput?.value) ?? settings.defaultLat ?? 51.272154,
        lng: toNumber(elements.lngInput?.value) ?? settings.defaultLng ?? 0.514951,
    };
}

function getCurrentZoom(elements: MapsElements, settings: MapsSettings): number {
    return toNumber(elements.zoomInput?.value) ?? settings.defaultZoom ?? 15;
}

function setValue(input: HTMLInputElement | null, value: string | number | null | undefined): void {
    if (!input) {
        return;
    }

    input.value = value === null || value === undefined ? '' : String(value);
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
}

function syncCoordinates(elements: MapsElements, latLng: LatLng, zoom?: number): void {
    setValue(elements.latInput, latLng.lat);
    setValue(elements.lngInput, latLng.lng);

    if (zoom !== undefined) {
        setValue(elements.zoomInput, zoom);
    }
}

function loadLeaflet(): Promise<typeof Leaflet> {
    if (!leafletPromise) {
        leafletPromise = import('leaflet');
    }

    return leafletPromise;
}

function getRasterTileUrl(settings: MapsSettings): string {
    switch (settings.mapTiles) {
        case 'openstreetmap':
        case 'wikimedia':
            return 'https://a.tile.openstreetmap.org/{z}/{x}/{y}.png';
        case 'carto.light_all':
            return 'https://a.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png';
        case 'carto.dark_all':
            return 'https://a.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png';
        case 'carto.rastertiles/voyager':
        default:
            return 'https://a.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png';
    }
}

function mapNominatimParts(address: Record<string, string | undefined> | undefined): Record<string, string> {
    if (!address) {
        return {};
    }

    return {
        number: address.house_number || '',
        address: address.road || address.pedestrian || address.footway || address.path || address.neighbourhood || '',
        city: address.city || address.town || address.village || address.suburb || '',
        postcode: address.postcode || '',
        county: address.county || '',
        state: address.state || address.state_district || '',
        country: address.country || '',
    };
}

async function searchNominatim(query: string, settings: MapsSettings): Promise<{ latLng: LatLng; address: string; parts: Record<string, string> } | null> {
    const params = new URLSearchParams({
        format: 'jsonv2',
        limit: '1',
        addressdetails: '1',
        q: query,
    });

    if (settings.country) {
        params.set('countrycodes', settings.country.toLowerCase());
    }

    const response = await fetch(`https://nominatim.openstreetmap.org/search?${params.toString()}`);

    if (!response.ok) {
        return null;
    }

    const results = await response.json() as NominatimResult[];
    const result = results[0];
    const lat = toNumber(result?.lat);
    const lng = toNumber(result?.lon);

    if (!result || lat === null || lng === null) {
        return null;
    }

    return {
        latLng: { lat, lng },
        address: result.display_name || query,
        parts: mapNominatimParts(result.address),
    };
}

function applySearchResult(elements: MapsElements, result: { latLng: LatLng; address: string; parts: Record<string, string> }, zoom: number): void {
    setValue(elements.addressInput, result.address);
    setValue(elements.searchInput, result.address);
    setValue(elements.partsInput, JSON.stringify(result.parts));
    syncCoordinates(elements, result.latLng, zoom);
}

async function initLeafletMap(elements: MapsElements, settings: MapsSettings): Promise<() => void> {
    if (!elements.canvas) {
        return () => {};
    }

    const L = await loadLeaflet();
    const initialLatLng = getCurrentLatLng(elements, settings);
    const initialZoom = getCurrentZoom(elements, settings);
    const map = L.map(elements.canvas, {
        minZoom: settings.minZoom ?? 3,
        maxZoom: settings.maxZoom ?? 18,
    }).setView([initialLatLng.lat, initialLatLng.lng], initialZoom);
    const marker = L.marker([initialLatLng.lat, initialLatLng.lng], {
        draggable: true,
    }).addTo(map);

    L.tileLayer(getRasterTileUrl(settings), {
        maxZoom: settings.maxZoom ?? 18,
        attribution: '&copy; OpenStreetMap contributors, &copy; CARTO',
    }).addTo(map);

    map.on('click', (event) => {
        marker.setLatLng([event.latlng.lat, event.latlng.lng]);
        syncCoordinates(elements, event.latlng, map.getZoom());
    });

    marker.on('dragend', (event) => {
        const latLng = event.target.getLatLng();
        syncCoordinates(elements, latLng, map.getZoom());
    });

    const submitSearch = async(): Promise<void> => {
        const query = elements.searchInput?.value.trim();

        if (!query) {
            return;
        }

        const result = await searchNominatim(query, settings);

        if (!result) {
            return;
        }

        const zoom = getCurrentZoom(elements, settings);
        applySearchResult(elements, result, zoom);
        map.setView([result.latLng.lat, result.latLng.lng], zoom);
        marker.setLatLng([result.latLng.lat, result.latLng.lng]);
    };

    const onSearchKeydown = (event: KeyboardEvent): void => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        void submitSearch();
    };

    const onSearchBlur = (): void => {
        void submitSearch();
    };

    elements.searchInput?.addEventListener('keydown', onSearchKeydown);
    elements.searchInput?.addEventListener('blur', onSearchBlur);

    const onCurrentLocationClick = (): void => {
        navigator.geolocation?.getCurrentPosition((position) => {
            const latLng = {
                lat: position.coords.latitude,
                lng: position.coords.longitude,
            };
            const zoom = getCurrentZoom(elements, settings);

            syncCoordinates(elements, latLng, zoom);
            map.setView([latLng.lat, latLng.lng], zoom);
            marker.setLatLng([latLng.lat, latLng.lng]);
        });
    };

    elements.currentLocationButton?.addEventListener('click', onCurrentLocationClick);

    return () => {
        elements.searchInput?.removeEventListener('keydown', onSearchKeydown);
        elements.searchInput?.removeEventListener('blur', onSearchBlur);
        elements.currentLocationButton?.removeEventListener('click', onCurrentLocationClick);
        map.remove();
    };
}

function initCustomMaps(root: HTMLElement): () => void {
    const elements = findElements(root);
    const settings = parseSettings(root);
    let cleanup: (() => void) | null = null;
    let destroyed = false;

    if (root.getAttribute('data-formie-custom-map-hide-map') !== '1') {
        void initLeafletMap(elements, settings).then((destroy) => {
            if (destroyed) {
                destroy();
                return;
            }

            cleanup = destroy;
        });
    }

    return () => {
        destroyed = true;
        cleanup?.();
    };
}

export const customMapsModule: FormieModuleDefinition = {
    id: MODULE_ID,
    kind: 'field',
    match: ({ target }) => {
        return target instanceof Element && (
            target.matches(MAPS_SELECTOR) ||
            !!target.querySelector(MAPS_SELECTOR)
        );
    },
    setup: async(ctx) => {
        const field = getModuleFieldTarget(ctx);
        const root = field || ctx.target;

        if (!(root instanceof Element)) {
            return;
        }

        const cleanup = observeMatchingElements(root, MAPS_SELECTOR, isCustomMaps, initCustomMaps);

        return {
            destroy: cleanup,
        };
    },
};
