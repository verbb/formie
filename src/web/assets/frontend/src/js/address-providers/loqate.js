import { FormieAddressProvider } from './address-provider';

export class FormieLoqate extends FormieAddressProvider {
    constructor(settings = {}) {
        super(settings);

        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;
        this.$input = this.$field.querySelector('[data-autocomplete]');
        this.scriptId = 'FORMIE_LOQATE_SCRIPT';

        this.apiKey = settings.apiKey;
        this.namespace = settings.namespace;
        this.reconfigurableOptions = settings.reconfigurableOptions;

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
            script.src = 'https://services.pcapredict.com/js/address-3.91.min.js?ver=4.7.3';
            script.defer = true;
            script.async = true;
            script.id = this.scriptId;
            script.onload = () => {
                this.initAutocomplete();
            };

            document.body.appendChild(script);

            const css = document.createElement('link');
            css.href = 'https://services.pcapredict.com/css/address-3.91.min.css';
            css.rel = 'stylesheet';
            css.type = 'text/css';

            document.body.appendChild(css);
        } else {
            // Script already present, but might not be loaded yet...
            this.waitForLoad();
        }
    }

    waitForLoad() {
        // Prevent running forever
        if (this.retryTimes > this.maxRetryTimes) {
            console.error(`Unable to load Loqate API after ${this.retryTimes} times.`);
            return;
        }

        if (typeof pca === 'undefined') {
            this.retryTimes += 1;

            setTimeout(this.waitForLoad.bind(this), this.waitTimeout);
        } else {
            this.initAutocomplete();
        }
    }

    initAutocomplete() {
        const fields = [
            { element: `${this.namespace}[autocomplete]`, field: 'Line1', mode: pca.fieldMode.SEARCH },
            { element: `${this.namespace}[address1]`, field: 'Line1', mode: pca.fieldMode.POPULATE },
            { element: `${this.namespace}[address2]`, field: 'Line2', mode: pca.fieldMode.POPULATE },
            { element: `${this.namespace}[address3]`, field: 'Line3', mode: pca.fieldMode.POPULATE },
            { element: `${this.namespace}[city]`, field: 'City', mode: pca.fieldMode.POPULATE },
            { element: `${this.namespace}[state]`, field: 'Province', mode: pca.fieldMode.POPULATE },
            { element: `${this.namespace}[zip]`, field: 'PostalCode' },
            { element: `${this.namespace}[country]`, field: 'CountryName', mode: pca.fieldMode.COUNTRY },
        ];

        const options = {
            key: this.apiKey,
            ...this.reconfigurableOptions,
        };

        const control = new pca.Address(fields, options);
    }
}

window.FormieLoqate = FormieLoqate;
