import { FormieAddressProvider } from './address-provider';

export class FormieAddressFinder extends FormieAddressProvider {
    constructor(settings = {}) {
        super(settings);

        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;
        this.$input = this.$field.querySelector('[data-autocomplete]');
        this.scriptId = 'FORMIE_ADDRESS_FINDER_SCRIPT';

        this.apiKey = settings.apiKey;
        this.countryCode = settings.countryCode;
        this.widgetOptions = settings.widgetOptions;

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
            script.src = 'https://api.addressfinder.io/assets/v3/widget.js';
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
            console.error(`Unable to load AddressFinder API after ${this.retryTimes} times.`);
            return;
        }

        if (typeof AddressFinder === 'undefined') {
            this.retryTimes += 1;

            setTimeout(this.waitForLoad.bind(this), this.waitTimeout);
        } else {
            this.initAutocomplete();
        }
    }

    initAutocomplete() {
        const widget = new AddressFinder.Widget(this.$input, this.apiKey, this.countryCode, this.widgetOptions);

        widget.on('result:select', (fullAddress, metaData) => {
            // We want to reverse if there's a unit number
            if (metaData.address_line_2) {
                this.setFieldValue('[data-address1]', metaData.address_line_2);
                this.setFieldValue('[data-address2]', metaData.address_line_1);
            } else {
                this.setFieldValue('[data-address1]', metaData.address_line_1);
                this.setFieldValue('[data-address2]', '');
            }

            this.setFieldValue('[data-city]', metaData.locality_name);
            this.setFieldValue('[data-zip]', metaData.postcode);
            this.setFieldValue('[data-state]', metaData.state_territory);
            this.setFieldValue('[data-country]', this.countryCode);
        });
    }

    setFieldValue(selector, value) {
        if (this.$field.querySelector(selector)) {
            this.$field.querySelector(selector).value = value || '';
        }
    }
}

window.FormieAddressFinder = FormieAddressFinder;
