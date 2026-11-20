import { test, expect, type Page } from '@playwright/test';

// Custom fields wrapping a Craft Link field and the Maps and Google Maps plugins' map fields work in client-rendered
// forms as in server-rendered ones: the same controls, the same browser modules, and the same saved answer. Map tiles,
// the geocoding service and the Google Maps SDK are faked at the network boundary.
const ADAPTERS = ['vue', 'react', 'web-components'] as const;

// A stand-in for the Maps JavaScript API's classic Map, Marker and Places Autocomplete. Pressing Enter in the search
// input picks the one place it knows, as choosing Google's suggestion does.
const FAKE_GOOGLE_MAPS = `
(function () {
    var PLACE = {
        formatted_address: 'Sydney Opera House, Bennelong Point, Sydney NSW 2000, Australia',
        name: 'Sydney Opera House',
        place_id: 'opera-house',
        address_components: [
            { types: ['route'], long_name: 'Bennelong Point', short_name: 'Bennelong Point' },
            { types: ['locality', 'political'], long_name: 'Sydney', short_name: 'Sydney' },
            { types: ['administrative_area_level_1', 'political'], long_name: 'New South Wales', short_name: 'NSW' },
            { types: ['country', 'political'], long_name: 'Australia', short_name: 'AU' },
            { types: ['postal_code'], long_name: '2000', short_name: '2000' },
        ],
        geometry: { location: { lat: function () { return -33.8568; }, lng: function () { return 151.2153; } } },
    };
    function Listeners() { this.listeners = {}; }
    Listeners.prototype.addListener = function (name, callback) { (this.listeners[name] = this.listeners[name] || []).push(callback); };
    function Map() { Listeners.call(this); this.zoom = 11; }
    Map.prototype = Object.create(Listeners.prototype);
    Map.prototype.setCenter = function () {};
    Map.prototype.setZoom = function (zoom) { this.zoom = zoom; };
    Map.prototype.getZoom = function () { return this.zoom; };
    function Marker(options) { Listeners.call(this); this.position = options && options.position; }
    Marker.prototype = Object.create(Listeners.prototype);
    Marker.prototype.setPosition = function (position) { this.position = position; };
    Marker.prototype.getPosition = function () { return null; };
    function Autocomplete(input) {
        Listeners.call(this);
        var self = this;
        input.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            (self.listeners.place_changed || []).forEach(function (callback) { callback(); });
        });
    }
    Autocomplete.prototype = Object.create(Listeners.prototype);
    Autocomplete.prototype.getPlace = function () { return PLACE; };
    window.google = { maps: { Map: Map, Marker: Marker, LatLng: function (lat, lng) { return { lat: lat, lng: lng }; }, places: { Autocomplete: Autocomplete } } };
})();
`;

const NOMINATIM = [{
    lat: '-37.8183',
    lon: '144.9671',
    display_name: 'Federation Square, Melbourne, Victoria, 3000, Australia',
    address: { road: 'Swanston Street', city: 'Melbourne', state: 'Victoria', postcode: '3000', country: 'Australia', country_code: 'au' },
}];

function field(page: Page, handle: string) {
    return page.locator(`[data-formie-field-handle="${handle}"]`).first();
}

async function fillAndSave(page: Page, surface: string): Promise<Record<string, unknown>> {
    const errors: string[] = [];
    page.on('pageerror', error => errors.push(error.message || String(error)));
    await page.route(/tile\.openstreetmap\.org|basemaps\.cartocdn\.com/, route => route.fulfill({ status: 200, contentType: 'image/png', body: '' }));
    await page.route(/nominatim\.openstreetmap\.org/, route => route.fulfill({ json: NOMINATIM }));
    await page.route(/maps\.googleapis\.com/, route => route.fulfill({ contentType: 'text/javascript', body: FAKE_GOOGLE_MAPS }));
    await page.goto(surface === 'server'
        ? '/browser-fixture?adapter=vue&product=server&form=adapterCustomFields'
        : `/browser-fixture?adapter=${surface}&form=adapterCustomFields`);
    await expect(page.locator('button[type="submit"]').first()).toBeVisible();

    // The link type sets how its value is typed and checked.
    const website = field(page, 'website');
    await expect(website.getByRole('textbox', { name: 'Website' })).toHaveAttribute('type', 'url');
    await website.getByRole('combobox', { name: 'Link Type' }).selectOption('email');
    await expect(website.getByRole('textbox', { name: 'Website' })).toHaveAttribute('type', 'email');
    await website.getByRole('textbox', { name: 'Website' }).fill('ada@example.test');
    await website.getByPlaceholder('Label').fill('Write to Ada');
    await website.getByLabel('Open in a new tab').check();
    await website.getByPlaceholder('Title').fill('Contact Ada');

    // The map draws, and searching moves its marker to the place found.
    const location = field(page, 'location');
    await expect(location.locator('.leaflet-container')).toBeVisible();
    await location.locator('[data-formie-custom-maps-search]').fill('Federation Square');
    await location.locator('[data-formie-custom-maps-search]').press('Enter');
    await expect(location.locator('[data-formie-custom-map-lat]')).toHaveValue('-37.8183');

    const place = field(page, 'place');
    // Server-rendered forms label a different input, so the search box is found by its marker.
    await place.locator('[data-formie-custom-google-maps-search]').fill('Opera House');
    await place.locator('[data-formie-custom-google-maps-search]').press('Enter');
    await expect(place.locator('[data-formie-custom-google-maps-field="city"]')).toHaveValue('Sydney');

    // A link in a Group is named under the Group.
    await field(page, 'contact').getByRole('textbox', { name: 'Profile' }).fill('https://example.test/ada');

    await page.waitForTimeout(3000);
    const response = page.waitForResponse(r => r.request().method() === 'POST' && /submissions(%2F|\/)submit/.test(r.url()));
    await page.locator('button[type="submit"]').last().click();
    const uid = (await (await response).json()).submissionUid;
    expect(uid).toBeTruthy();
    const saved = await (await page.request.get(`/browser-parity-saved?uid=${uid}`)).json();
    expect(errors).toEqual([]);

    return saved.values;
}

for (const adapter of ADAPTERS) {
    test(`${adapter}: saves Custom link and map fields as server-rendered forms do`, async ({ browser }) => {
        const serverPage = await browser.newPage();
        const server = await fillAndSave(serverPage, 'server');
        await serverPage.close();

        // The server-rendered baseline took every step.
        expect(server.website).toMatchObject({ type: 'email', value: 'mailto:ada@example.test', label: 'Write to Ada', target: '_blank', title: 'Contact Ada' });
        expect(server.location).toMatchObject({ lat: -37.8183, lng: 144.9671 });
        expect(server.place).toMatchObject({ city: 'Sydney', placeId: 'opera-house' });

        const page = await browser.newPage();
        const client = await fillAndSave(page, adapter);
        await page.close();

        expect(client.website).toEqual(server.website);
        expect(client.location).toEqual(server.location);
        expect(client.place).toEqual(server.place);
        expect(server.contact).toMatchObject({ profile: { type: 'url', value: 'https://example.test/ada' } });
        expect(client.contact).toEqual(server.contact);
    });
}
