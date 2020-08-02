const places = require('places.js');

class FormieAlgoliaPlaces {
    constructor(settings = {}) {
        this.appId = settings.appId;
        this.apiKey = settings.apiKey;
        this.container = settings.container;
        this.formId = settings.formId;
        this.fieldContainer = settings.fieldContainer;
        this.reconfigurableOptions = settings.reconfigurableOptions;

        this.$form = document.querySelector('#' + this.formId);

        if (!this.$form) {
            console.error('Unable to find form #' + this.formId);

            return;
        }

        this.$field = this.$form.querySelector('[' + settings.fieldContainer + ']');

        if (!this.$field) {
            console.error('Unable to find field [' + settings.fieldContainer + ']');

            return;
        }

        this.$input = document.querySelector('[data-' + settings.container + ']');

        if (!this.$input) {
            console.error('Unable to find input [data-' + settings.container + ']');

            return;
        }

        const placesAutocomplete = places({
            appId: this.appId,
            apiKey: this.apiKey,
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
