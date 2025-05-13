import { FormieAddressProvider } from './address-provider';

import placekitAutocomplete from '@placekit/autocomplete-js';

import '@placekit/autocomplete-js/dist/placekit-autocomplete.css';

export class FormiePlaceKit extends FormieAddressProvider {
    constructor(settings = {}) {
        super(settings);

        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;
        this.$input = this.$field.querySelector('[data-autocomplete]');

        this.apiKey = settings.apiKey;
        this.options = settings.options;

        if (!this.$input) {
            console.error('Unable to find input `[data-autocomplete]`.');

            return;
        }

        this.initAutocomplete();
    }

    initAutocomplete() {
        const options = { target: this.$input, ...this.options };

        // Emit an "beforeInit" event. This can directly modify the `options` param
        const beforeInitEvent = new CustomEvent('beforeInit', {
            bubbles: true,
            detail: {
                addressProvider: this,
                options,
            },
        });

        this.$field.dispatchEvent(beforeInitEvent);

        this.picker = placekitAutocomplete(this.apiKey, options);

        this.picker.on('pick', (value, item) => {
            // Allow events to modify behaviour
            const populateAddressEvent = new CustomEvent('populateAddress', {
                bubbles: true,
                detail: {
                    addressProvider: this,
                    addressComponents: item,
                },
            });

            this.$field.dispatchEvent(populateAddressEvent);

            // Build full street address
            let address1 = '';

            if (item.street) {
                const number = item.street.number ?? '';
                const name = item.street.name ?? '';
                const suffix = item.street.suffix ?? '';

                address1 = [number, name, suffix].filter(Boolean).join(' ');
            }

            // Zipcode is an array, get first value
            const zip = Array.isArray(item.zipcode) ? item.zipcode[0] : item.zipcode;

            this.setFieldValue('[data-address1]', address1);
            this.setFieldValue('[data-city]', item.city);
            this.setFieldValue('[data-state]', item.administrative);
            this.setFieldValue('[data-zip]', zip);
            this.setFieldValue('[data-country]', item.country);
        });
    }

    setFieldValue(selector, value, fallback) {
        if (this.$field.querySelector(selector)) {
            this.$field.querySelector(selector).value = value || fallback || '';
        }
    }
}

window.FormiePlaceKit = FormiePlaceKit;
