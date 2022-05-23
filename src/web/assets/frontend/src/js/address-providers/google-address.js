import { FormieAddressProvider } from './address-provider';

export class FormieGoogleAddress extends FormieAddressProvider {
    constructor(settings = {}) {
        super(settings);

        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;
        this.$input = this.$field.querySelector('[data-autocomplete]');
        this.scriptId = 'FORMIE_GOOGLE_ADDRESS_SCRIPT';

        this.appId = settings.appId;
        this.apiKey = settings.apiKey;
        this.geocodingApiKey = settings.geocodingApiKey || settings.apiKey;
        this.options = settings.options;

        // Keep track of how many times we try to load.
        this.retryTimes = 0;
        this.maxRetryTimes = 150;
        this.waitTimeout = 200;

        this.initScript();
    }

    initScript() {
        // Prevent the script from loading multiple times (which throw warnings anyway)
        if (!document.getElementById(this.scriptId)) {
            var script = document.createElement('script');
            script.src = 'https://maps.googleapis.com/maps/api/js?key=' + this.apiKey + '&libraries=places';
            script.defer = true;
            script.async = true;
            script.id = this.scriptId;
            script.onload = () => {
                this.initAutocomplete();
            };

            document.body.appendChild(script);
        } else {
            // Script already present, but might not be loaded yet...
            this.waitForLoad();
        }
    }

    waitForLoad() {
        // Prevent running forever
        if (this.retryTimes > this.maxRetryTimes) {
            console.error('Unable to load Google API after ' + this.retryTimes + ' times.');
            return;
        }
        
        if (typeof google === 'undefined') {
            this.retryTimes += 1;
            
            setTimeout(this.waitForLoad.bind(this), this.waitTimeout);
        } else {
            this.initAutocomplete();
        }
    }

    initAutocomplete() {
        var options = Object.assign({ types: ['geocode'] }, this.options);

        var autocomplete = new google.maps.places.Autocomplete(this.$input, options);

        autocomplete.setFields(['address_component']);

        autocomplete.addListener('place_changed', () => {
            var place = autocomplete.getPlace();

            if (!place.address_components) {
                // Seem to be having some issues with `address_components` being empty for units...
                return;
            }

            this.setAddressValues(place.address_components);
        });
    }

    setAddressValues(address) {
        const formData = {};
        const componentMap = this.componentMap();

        // Sort out the data from Google so its easier to manage
        for (var i = 0; i < address.length; i++) {
            var [addressType] = address[i].types;

            if (componentMap[addressType]) {
                formData[addressType] = address[i][componentMap[addressType]];
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
    }

    onCurrentLocation(position) {
        const { latitude, longitude } = position.coords;

        const xhr = new XMLHttpRequest();
        xhr.open('POST', window.location.href, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('Cache-Control', 'no-cache');
        xhr.timeout = 10 * 1000;

        xhr.ontimeout = () => {
            console.log('The request timed out.');
        };

        xhr.onerror = (e) => {
            console.log('The request encountered a network error. Please try again.');
        };

        xhr.onload = () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const response = JSON.parse(xhr.responseText);

                    if (response && response.results && response.results[0] && response.results[0].address_components) {
                        this.setAddressValues(response.results[0].address_components);
                    }

                    if (response.error_message || response.error) {
                        console.log(response);
                    }
                } catch(e) {
                    console.log(e);
                }
            } else {
                console.log(xhr.status + ': ' + xhr.statusText);
            }
        };

        // Use our own proxy to get around lack of support from Google Places and restricted API keys
        const formData = new FormData();
        formData.append('action', 'formie/address/google-places-geocode');
        formData.append('latlng', latitude + ',' + longitude);
        formData.append('key', this.geocodingApiKey);

        xhr.send(formData);
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

    setFieldValue(selector, value) {
        if (this.$field.querySelector(selector)) {
            this.$field.querySelector(selector).value = value || '';
        }
    }
}

window.FormieGoogleAddress = FormieGoogleAddress;
