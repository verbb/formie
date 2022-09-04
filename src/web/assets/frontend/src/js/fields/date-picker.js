import { eventKey } from '../utils/utils';

import flatpickr from 'flatpickr';

require('flatpickr/dist/flatpickr.min.css');

export class FormieDatePicker {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field.querySelector('input');

        this.locales = [];
        this.datePickerOptions = settings.datePickerOptions || [];
        this.dateFormat = settings.dateFormat;
        this.timeFormat = settings.timeFormat;
        this.includeTime = settings.includeTime;
        this.includeDate = settings.includeDate;
        this.getIsDate = settings.getIsDate;
        this.getIsTime = settings.getIsTime;
        this.getIsDateTime = settings.getIsDateTime;
        this.locale = settings.locale;
        this.minDate = settings.minDate;
        this.maxDate = settings.maxDate;
        this.availableDaysOfWeek = settings.availableDaysOfWeek;

        // Resolve the correct, full date-time format
        if (this.getIsDate) {
            this.dateTimeFormat = this.dateFormat;
        } else if (this.getIsTime) {
            this.dateTimeFormat = this.timeFormat;
        } else if (this.getIsDateTime) {
            this.dateTimeFormat = `${this.dateFormat} ${this.timeFormat}`;
        }

        this.initDatePicker();
    }

    initDatePicker() {
        const defaultOptions = {
            disableMobile: true,
            allowInput: true,
            altInput: true,
            altFormat: this.prepareFormat(),
            dateFormat: 'Y-m-d H:i:S',
            hourIncrement: 1,
            minuteIncrement: 1,
            minDate: this.minDate,
            maxDate: this.maxDate,
            disable: [this.getDisabledWeekdays.bind(this)],
        };

        // Include time for time-only and datetime
        if (this.getIsTime || this.getIsDateTime) {
            defaultOptions.enableTime = true;
        }

        // Exclude date for time-only
        if (this.getIsTime) {
            defaultOptions.noCalendar = true;
        }

        // We have options defined by default, which are overridden by any defined in the CP for the field
        // which are then overridden by any defined in the JS event. So combine the default + field options first.
        const options = {
            ...defaultOptions,
            ...this.getDatePickerOptions(),
        };

        // Emit an "beforeInit" event. This can directly modify the `options` param
        const beforeInitEvent = new CustomEvent('beforeInit', {
            bubbles: true,
            detail: {
                datepicker: this,
                options,
            },
        });

        this.$field.dispatchEvent(beforeInitEvent);

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

        // Update the form hash, so we don't get change warnings
        if (this.form.formTheme) {
            this.form.formTheme.updateFormHash();
        }
    }

    loadLocale() {
        if (this.locale === 'en') {
            return;
        }

        if (!this.locales.includes(this.locale)) {
            const $script = document.createElement('script');
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

    getDatePickerOptions() {
        const opts = {};

        // Format options stored in Formie, ready for JS
        this.datePickerOptions.forEach((object) => {
            // Handle parsing boolean, ugh
            if (object.value === 'true') {
                object.value = true;
            } else if (object.value === 'false') {
                object.value = false;
            }

            opts[object.label] = object.value;
        });

        return opts;
    }

    prepareFormat() {
        // Convert date format from PHP to Flatpickr
        // https://flatpickr.js.org/formatting/
        return this.dateTimeFormat.replace('A', 'K').replace('a', 'K').replace('s', 'S').replace('g', 'h').replace('h', 'G');
    }

    getDisabledWeekdays(date) {
        if (this.availableDaysOfWeek !== '*') {
            return (this.availableDaysOfWeek.includes(date.getDay().toString())) ? false : true;
        }

        return false;
    }
}

window.FormieDatePicker = FormieDatePicker;
