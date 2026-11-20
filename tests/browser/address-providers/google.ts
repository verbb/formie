import { expect } from '@playwright/test';
import type { AddressProvider } from './types';

// A stand-in for the Maps JavaScript API with the Places library. `PlaceAutocompleteElement` renders its own search
// box, offers one suggestion for what the visitor types, and emits `gmp-select` with a place prediction, as Google does.
const FAKE_GOOGLE_MAPS = `
(function () {
    var PLACE = {
        formattedAddress: '10 Smith St, Collingwood VIC 3066, Australia',
        postalAddress: { addressLines: ['10 Smith St'] },
        addressComponents: [
            { types: ['street_number'], longText: '10', shortText: '10' },
            { types: ['route'], longText: 'Smith Street', shortText: 'Smith St' },
            { types: ['locality', 'political'], longText: 'Collingwood', shortText: 'Collingwood' },
            { types: ['administrative_area_level_1', 'political'], longText: 'Victoria', shortText: 'VIC' },
            { types: ['country', 'political'], longText: 'Australia', shortText: 'AU' },
            { types: ['postal_code'], longText: '3066', shortText: '3066' },
        ],
    };

    class PlaceAutocompleteElement extends HTMLElement {
        constructor(options) {
            super();
            Object.assign(this, options || {});
        }

        connectedCallback() {
            if (this.shadowRoot) return;

            var root = this.attachShadow({ mode: 'open' });
            root.innerHTML = '<input type="text" aria-label="Search for a place"><ul role="listbox"></ul>';

            var input = root.querySelector('input');
            var list = root.querySelector('ul');
            var element = this;

            input.addEventListener('input', function () {
                list.innerHTML = input.value.trim() ? '<li role="option">' + PLACE.formattedAddress + '</li>' : '';
            });

            list.addEventListener('click', function (event) {
                if (!event.target.closest('[role="option"]')) return;

                list.innerHTML = '';
                input.value = PLACE.formattedAddress;

                var place = {
                    fetchFields: function () {
                        Object.assign(place, PLACE);
                        return Promise.resolve({ place: place });
                    },
                };
                var select = new Event('gmp-select');
                select.placePrediction = { toPlace: function () { return place; } };
                element.dispatchEvent(select);
            });
        }
    }

    customElements.define('gmp-place-autocomplete', PlaceAutocompleteElement);

    var places = { PlaceAutocompleteElement: PlaceAutocompleteElement };
    window.google = {
        maps: {
            places: places,
            importLibrary: function (name) { return Promise.resolve(name === 'places' ? places : {}); },
        },
    };

    var script = document.currentScript;
    var callback = script ? new URL(script.src).searchParams.get('callback') : null;

    if (callback && typeof window[callback] === 'function') {
        window[callback]();
    }
})();`;

// The module replaces the auto-complete input with Google's element, then fills the parts from the selected place.
export const google: AddressProvider = {
    name: 'Google',
    routes: {
        'https://maps.googleapis.com/maps/api/js**': { contentType: 'text/javascript', body: FAKE_GOOGLE_MAPS },
    },
    mounted: async (field) => {
        await expect(field.locator('gmp-place-autocomplete input')).toHaveCount(1, { timeout: 15000 });
    },
    pick: async (field) => {
        await field.locator('gmp-place-autocomplete input').pressSequentially('10 Smith');
        await field.locator('gmp-place-autocomplete [role="option"]', { hasText: '10 Smith St' }).click();
    },
};
