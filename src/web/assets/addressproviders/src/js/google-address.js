class FormieGoogleAddress {
    constructor(settings = {}) {
        this.appId = settings.appId;
        this.apiKey = settings.apiKey;
        this.container = settings.container;
        this.formId = settings.formId;
        this.fieldContainer = settings.fieldContainer;
        this.options = settings.options;

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

        document.addEventListener('DOMContentLoaded', this.initScript.bind(this));
    }

    componentMap() {
        /* eslint-disable camelcase */
        return {
            subpremise: 'short_name',
            street_number: 'short_name',
            route: 'long_name',
            locality: 'long_name',
            administrative_area_level_1: 'short_name',
            country: 'short_name',
            postal_code: 'short_name',
        };
        /* eslint-enable camelcase */
    }

    initScript() {
        var initAutocomplete = () => {
            var options = Object.assign({ types: ['geocode'] }, this.options);

            var autocomplete = new google.maps.places.Autocomplete(this.$input, options);

            autocomplete.setFields(['address_component']);

            autocomplete.addListener('place_changed', () => {
                var place = autocomplete.getPlace();
                var componentMap = this.componentMap();

                let formData = {};

                if (!place.address_components) {
                    // Seem to be having some issues with `address_components` being empty for units...
                    return;
                }

                // Sort out the data from Google so its easier to manage
                for (var i = 0; i < place.address_components.length; i++) {
                    var [addressType] = place.address_components[i].types;

                    if (componentMap[addressType]) {
                        formData[addressType] = place.address_components[i][componentMap[addressType]];
                    }
                }

                if (formData.street_number && formData.route) {
                    let street = formData.street_number + ' ' + formData.route;

                    if (formData.subpremise) {
                        street = formData.subpremise + '/' + street;
                    }

                    this.setFieldValue('[data-address1]', street);
                }

                this.setFieldValue('[data-city]', formData.locality);
                this.setFieldValue('[data-zip]', formData.postal_code);
                this.setFieldValue('[data-state]', formData.administrative_area_level_1);
                this.setFieldValue('[data-country]', formData.country);
            });
        };

        var script = document.createElement('script');
        script.src = 'https://maps.googleapis.com/maps/api/js?key=' + this.apiKey + '&libraries=places';
        script.defer = true;
        script.onload = initAutocomplete;
        document.body.appendChild(script);
    }

    setFieldValue(selector, value) {
        if (this.$field.querySelector(selector)) {
            this.$field.querySelector(selector).value = value || '';
        }
    }
}

window.FormieGoogleAddress = FormieGoogleAddress;
