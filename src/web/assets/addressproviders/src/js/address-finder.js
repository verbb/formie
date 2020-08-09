export class FormieAddressFinder {
    constructor(settings = {}) {
        this.apiKey = settings.apiKey;
        this.countryCode = settings.countryCode;
        this.container = settings.container;
        this.formId = settings.formId;
        this.fieldContainer = settings.fieldContainer;
        this.widgetOptions = settings.widgetOptions;

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

        this.$input = document.querySelector('[data-' + this.container + ']');

        if (!this.$input) {
            console.error('Unable to find input [data-' + this.container + ']');

            return;
        }

        this.downloadAF();
    }

    downloadAF() {
        var initAF = () => {
            var widget = new AddressFinder.Widget(this.$input, this.apiKey, this.countryCode, this.widgetOptions);
        
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
        };

        var script = document.createElement('script');
        script.src = 'https://api.addressfinder.io/assets/v3/widget.js';
        script.async = true;
        script.onload = initAF;
        document.body.appendChild(script);
    }

    setFieldValue(selector, value) {
        if (this.$field.querySelector(selector)) {
            this.$field.querySelector(selector).value = value || '';
        }
    }
}

window.FormieAddressFinder = FormieAddressFinder;
