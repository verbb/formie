import { eventKey } from '../utils/utils';

import flatpickr from 'flatpickr';

require('flatpickr/dist/flatpickr.min.css');

export class FormieDatePicker {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field.querySelector('input');

        this.locales = [];
        this.dateFormat = settings.dateFormat;
        this.includeTime = settings.includeTime;
        this.locale = settings.locale;
        this.minDate = settings.minDate;
        this.maxDate = settings.maxDate;

        this.initDatePicker();
    }

    initDatePicker() {
        const defaultOptions = {
            disableMobile: true,
            allowInput: true,
            dateFormat: this.dateFormat,
            enableTime: this.includeTime,
            hourIncrement: 1,
            minuteIncrement: 1,
            minDate: this.minDate,
            maxDate: this.maxDate,
        };

        // Emit an "beforeInit" event
        const beforeInitEvent = this.$field.dispatchEvent(new CustomEvent('beforeInit', {
            bubbles: true,
            detail: {
                datepicker: this,
                options: defaultOptions,
            },
        }));

        const options = {
            ...defaultOptions,
            ...beforeInitEvent.options,
        };

        this.datepicker = flatpickr(this.$field, options);

        // Emit an "afterInit" event
        this.$field.dispatchEvent(new CustomEvent('afterInit', {
            bubbles: true,
            detail: {
                datepicker: this,
                options,
            },
        }));

        // Load in the locale as required
        this.loadLocale();
    }

    loadLocale() {
        if (this.locale === 'en') {
            return;
        }

        if (!this.locales.includes(this.locale)) {
            var $script = document.createElement('script');
            $script.src = `https://npmcdn.com/flatpickr@4.6.9/dist/l10n/${this.locale}.js`;
            $script.defer = false;
            $script.async = false;
            $script.onload = () => {
                this.datepicker.set('locale', this.locale);
            };
            
            document.body.appendChild($script);

            this.locales.push(this.locale);
        }
    }
}

window.FormieDatePicker = FormieDatePicker;
