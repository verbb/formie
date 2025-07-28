import { getAjaxClient } from '../utils/utils';
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

        if (!this.$input) {
            console.error('Unable to find input `[data-autocomplete]`.');

            return;
        }

        this.initScript();
    }

    initScript() {
        // Prevent the script from loading multiple times (which throw warnings anyway)
        if (!document.getElementById(this.scriptId)) {
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=${this.apiKey}&loading=async&libraries=places`;
            script.defer = true;
            script.async = true;
            script.id = this.scriptId;
            script.onload = () => {
                // Just in case there's a small delay in initializing the scripts after loaded
                this.waitForLoad();
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
            console.error(`Unable to load Google API after ${this.retryTimes} times.`);
            return;
        }

        // Ensure that Google places is ready
        if (typeof google === 'undefined' || typeof google.maps === 'undefined' || typeof google.maps.places === 'undefined') {
            this.retryTimes += 1;

            setTimeout(this.waitForLoad.bind(this), this.waitTimeout);
        } else {
            this.initAutocomplete();
        }
    }

    initAutocomplete() {
        const options = { types: ['geocode'], ...this.options };

        const autocomplete = new google.maps.places.PlaceAutocompleteElement(options);
        const inputHeight = window.getComputedStyle(this.$input).height;

        autocomplete.style.height = inputHeight;
        autocomplete.style.boxSizing = 'border-box';

        // Find or create a wrapper div around the input
        let wrapper = this.$input.parentElement;

        if (!wrapper || !wrapper.classList.contains('fui-autocomplete-wrapper')) {
            wrapper = document.createElement('div');
            wrapper.classList.add('fui-autocomplete-wrapper');

            this.$input.parentNode.insertBefore(wrapper, this.$input);
            wrapper.appendChild(this.$input);
        }

        // Create and insert the fake placeholder overlay
        const hiddenInput = this.$input;
        const savedValue = hiddenInput.value;

        if (savedValue) {
            const overlay = document.createElement('div');
            overlay.classList.add('fui-autocomplete-placeholder');
            overlay.innerText = savedValue;

            wrapper.style.position = 'relative';
            overlay.style.position = 'absolute';
            overlay.style.left = '0';
            overlay.style.top = '0';
            overlay.style.height = inputHeight;
            overlay.style.lineHeight = inputHeight;
            overlay.style.width = '100%';
            overlay.style.padding = '0 2.5rem';
            overlay.style.pointerEvents = 'none';
            overlay.style.color = '#6B7280';
            overlay.style.fontSize = '14px';
            overlay.style.zIndex = '1';

            wrapper.appendChild(overlay);

            // Toggle overlay on focus/blur
            autocomplete.addEventListener('focusin', () => {
                overlay.style.display = 'none';
            });

            autocomplete.addEventListener('focusout', () => {
                if (hiddenInput.value) {
                    overlay.style.display = '';
                }
            });
        }

        // Replace input with autocomplete element
        wrapper.replaceChild(autocomplete, hiddenInput);

        // Reinsert hidden input for actual form value
        hiddenInput.type = 'hidden';
        hiddenInput.name = this.$input.name;
        wrapper.appendChild(hiddenInput);

        autocomplete.addEventListener('gmp-select', async({ placePrediction }) => {
            const place = placePrediction.toPlace();
            await place.fetchFields({ fields: ['addressComponents', 'formattedAddress'] });

            if (!place.addressComponents) { return; }

            this.setAddressValues(place.addressComponents, place.formattedAddress);

            const populateAddressEvent = new CustomEvent('populateAddress', {
                bubbles: true,
                detail: {
                    addressProvider: this,
                    place,
                    formattedAddress: place.formattedAddress,
                    addressComponents: place.addressComponents,
                },
            });

            this.$field.dispatchEvent(populateAddressEvent);
        });
    }

    setAddressValues(address, formattedAddress) {
        const formData = {};
        const componentMap = this.componentMap();

        // Sort out the data from Google so its easier to manage
        for (let i = 0; i < address.length; i++) {
            const [addressType] = address[i].types;

            if (componentMap[addressType]) {
                formData[addressType] = address[i][componentMap[addressType]];
            }
        }

        if (formData.street_number && formData.route) {
            let street = `${formData.street_number} ${formData.route}`;

            if (formData.subpremise) {
                street = `${formData.subpremise}/${street}`;
            }

            this.setFieldValue('[data-address1]', street);
        }

        this.setFieldValue('[data-autocomplete]', formattedAddress);
        this.setFieldValue('[data-city]', formData.locality, formData.postal_town);
        this.setFieldValue('[data-zip]', formData.postal_code);
        this.setFieldValue('[data-state]', formData.administrative_area_level_1);
        this.setFieldValue('[data-country]', formData.country);
    }

    onCurrentLocation(position) {
        const { latitude, longitude } = position.coords;

        const xhr = getAjaxClient(this.$form, 'POST', window.location.href, true);
        xhr.timeout = 10 * 1000;

        xhr.ontimeout = () => {
            console.log('The request timed out.');
        };

        xhr.onerror = (e) => {
            console.log('The request encountered a network error. Please try again.');
        };

        xhr.onload = () => {
            this.onEndFetchLocation();

            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const response = JSON.parse(xhr.responseText);

                    if (response && response.results && response.results[0] && response.results[0].address_components) {
                        this.setAddressValues(response.results[0].address_components);
                    }

                    if (response.error_message || response.error) {
                        console.log(response);
                    }
                } catch (e) {
                    console.log(e);
                }
            } else {
                console.log(`${xhr.status}: ${xhr.statusText}`);
            }
        };

        // Use our own proxy to get around lack of support from Google Places and restricted API keys
        const formData = new FormData();
        formData.append('action', 'formie/address/google-places-geocode');
        formData.append('latlng', `${latitude},${longitude}`);
        formData.append('key', this.geocodingApiKey);

        xhr.send(formData);
    }

    componentMap() {
        /* eslint-disable camelcase */
        return {
            subpremise: 'shortText',
            street_number: 'shortText',
            route: 'longText',
            postal_town: 'longText',
            locality: 'longText',
            administrative_area_level_1: 'shortText',
            country: 'shortText',
            postal_code: 'shortText',
        };
        /* eslint-enable camelcase */
    }

    setFieldValue(selector, value, fallback) {
        if (this.$field.querySelector(selector)) {
            this.$field.querySelector(selector).value = value || fallback || '';
        }
    }
}

window.FormieGoogleAddress = FormieGoogleAddress;
