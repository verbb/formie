const places = require('places.js');
import { FormieAddressProvider } from './address-provider';

export class FormieAlgoliaPlaces extends FormieAddressProvider {
    constructor(settings = {}) {
        super(settings);

        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;
        this.$input = this.$field.querySelector('[data-autocomplete]');

        if (!this.$input) {
            console.error('Unable to find input `[data-autocomplete]`.');

            return;
        }

        this.appId = settings.appId;
        this.apiKey = settings.apiKey;
        this.reconfigurableOptions = settings.reconfigurableOptions;

        // I have no idea what fresh hell this is, but the keys are swapped!
        const placesAutocomplete = places({
            appId: this.apiKey,
            apiKey: this.appId,
            container: this.$input,
        });

        // Any additional options
        placesAutocomplete.configure(this.reconfigurableOptions);

        // Populate any available detail field
        placesAutocomplete.on('change', (e) => {
            this.setFieldValue('[data-address1]', e.suggestion.name);
            this.setFieldValue('[data-city]', e.suggestion.city);
            this.setFieldValue('[data-zip]', e.suggestion.postcode);
            this.setFieldValue('[data-state]', e.suggestion.administrative);
            this.setFieldValue('[data-country]', e.suggestion.countryCode.toUpperCase());
        });

        placesAutocomplete.on('clear', () => {
            this.setFieldValue('[data-address1]', '');
            this.setFieldValue('[data-city]', '');
            this.setFieldValue('[data-zip]', '');
            this.setFieldValue('[data-state]', '');
            this.setFieldValue('[data-country]', '');
        });
    }

    setFieldValue(selector, value) {
        if (this.$field.querySelector(selector)) {
            this.$field.querySelector(selector).value = value || '';
        }
    }
}

window.FormieAlgoliaPlaces = FormieAlgoliaPlaces;
