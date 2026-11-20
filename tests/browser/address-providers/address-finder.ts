import type { Page } from '@playwright/test';
import type { AddressProvider } from './types';

// A stand-in for AddressFinder's widget. Like the real one, it attaches to the input it's given, searches as the
// visitor types, shows suggestions in a list on the page body, and on a click fetches the address metadata, writes the
// full address into the input and fires `result:select`. Autocomplete and metadata go out as JSONP calls that the
// routes answer. Formie's module fills the other parts from the metadata.
const FAKE_WIDGET = `
(function () {
    var BASE = 'https://api.addressfinder.io/api/';

    function jsonp(url) {
        var script = document.createElement('script');
        script.src = url;
        document.head.appendChild(script);
    }

    function Widget(element, key, country, options) {
        var widget = this;
        var region = String(country || 'au').toLowerCase();
        var list = document.createElement('ul');

        this.handlers = {};
        list.className = 'af_list';
        list.style.display = 'none';
        document.body.appendChild(list);
        element.setAttribute('data-fake-addressfinder', region);

        window.AddressFinder.__completed = function (response) {
            list.innerHTML = '';
            (response.completions || []).forEach(function (completion) {
                var item = document.createElement('li');
                item.className = 'af_item';
                item.textContent = completion.full_address;
                item.addEventListener('click', function () {
                    list.style.display = 'none';
                    jsonp(BASE + region + '/address/metadata/?key=' + encodeURIComponent(key) + '&id=' + encodeURIComponent(completion.id) + '&format=json&callback=AddressFinder.__metadata');
                });
                list.appendChild(item);
            });
            list.style.display = response.completions && response.completions.length ? 'block' : 'none';
        };

        window.AddressFinder.__metadata = function (metaData) {
            element.value = metaData.full_address;
            (widget.handlers['result:select'] || []).forEach(function (handler) {
                handler(metaData.full_address, metaData);
            });
        };

        element.addEventListener('input', function () {
            if (!element.value) return;
            jsonp(BASE + region + '/address/autocomplete/?key=' + encodeURIComponent(key) + '&q=' + encodeURIComponent(element.value) + '&format=json&callback=AddressFinder.__completed');
        });
    }

    Widget.prototype.on = function (event, handler) {
        (this.handlers[event] = this.handlers[event] || []).push(handler);
        return this;
    };

    window.AddressFinder = { Widget: Widget };
})();`;

const COMPLETED = {
    completions: [{ id: 'parity-10-smith', full_address: '10 Smith Street, COLLINGWOOD VIC 3066', canonical_address_id: 'parity-10-smith' }],
    paid: true,
    success: true,
};

const METADATA = {
    id: 'parity-10-smith',
    full_address: '10 Smith Street, COLLINGWOOD VIC 3066',
    address_line_1: '10 Smith Street',
    address_line_2: null,
    locality_name: 'COLLINGWOOD',
    state_territory: 'VIC',
    postcode: '3066',
    success: true,
};

// The visitor types part of the address and clicks the suggestion. Waiting for it is bounded, so a surface where the
// widget doesn't search is recorded as an unfilled address rather than a timeout.
async function chooseSuggestion(page: Page) {
    const suggestion = page.locator('.af_list .af_item', { hasText: '10 Smith Street' }).first();

    try {
        await suggestion.waitFor({ state: 'visible', timeout: 5000 });
    } catch {
        return;
    }

    await suggestion.click();
    await page.waitForTimeout(500);
}

export const addressFinder: AddressProvider = {
    name: 'AddressFinder',
    routes: {
        'https://api.addressfinder.io/assets/**': { contentType: 'text/javascript', body: FAKE_WIDGET },
        'https://api.addressfinder.io/api/au/address/autocomplete/**': { contentType: 'text/javascript', body: `AddressFinder.__completed(${JSON.stringify(COMPLETED)});` },
        'https://api.addressfinder.io/api/au/address/metadata/**': { contentType: 'text/javascript', body: `AddressFinder.__metadata(${JSON.stringify(METADATA)});` },
    },
    mounted: async (field) => {
        await field.locator('[data-formie-address-autocomplete-input][data-fake-addressfinder]').waitFor({ state: 'attached', timeout: 15000 });
    },
    pick: async (field, page) => {
        await field.locator('[data-formie-address-autocomplete-input]').pressSequentially('10 Smith', { delay: 20 });
        await chooseSuggestion(page);
    },
};
