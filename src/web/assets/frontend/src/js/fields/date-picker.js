import { t, eventKey, getScriptUrl } from '../utils/utils';

import flatpickr from 'flatpickr';

require('flatpickr/dist/flatpickr.min.css');

// A Flatpickr plugin to copy all attributes on the input to the `altInput` for accessibility
function attributesPlugin() {
    return function(fp) {
        return {
            onReady() {
                const excludedAttributes = ['type', 'name', 'value'];

                // Copy all attributes from normal input to `altInput` - except some
                fp.input.getAttributeNames().forEach((attribute) => {
                    if (!excludedAttributes.includes(attribute)) {
                        fp.altInput.setAttribute(attribute, fp.input.getAttribute(attribute));

                        fp.input.removeAttribute(attribute);
                    }
                });

                fp.loadedPlugins.push('labelPlugin');
            },
        };
    };
}

export class FormieDatePicker {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field.querySelector('input');

        this.locales = [];
        this.datePickerOptions = settings.datePickerOptions || [];
        this.dateFormat = settings.dateFormat;
        this.timeFormat = settings.timeFormat;
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
            minDate: this.getMinDate(),
            maxDate: this.getMaxDate(),
            disable: [this.getDisabledWeekdays.bind(this)],
            plugins: [new attributesPlugin({})],
            onReady: (dateObj, dateStr, instance) => {
                // Update the form hash, so we don't get change warnings
                if (this.form.formTheme) {
                    this.form.formTheme.updateFormHash();
                }
            },
            onChange: (selectedDates, dateStr, instance) => {
                // Trigger an input event on the field, otherwise will throw validation errors when on-focus
                // https://github.com/verbb/formie/issues/1460
                instance.altInput.dispatchEvent(new Event('input', {
                    bubbles: true,
                    cancelable: true,
                }));
            },
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
    }

    loadLocale() {
        if (this.locale === 'en') {
            return;
        }

        if (!this.locales.includes(this.locale)) {
            const $script = document.createElement('script');
            $script.src = getScriptUrl(this.$form, `https://npmcdn.com/flatpickr@4.6.9/dist/l10n/${this.locale}.js`);
            $script.defer = false;
            $script.async = false;
            $script.onload = () => {
                this.datepicker.set('locale', this.locale);

                // Update the form hash, so we don't get change warnings
                if (this.form.formTheme) {
                    this.form.formTheme.updateFormHash();
                }
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
            return (this.availableDaysOfWeek.includes(date.getDay())) ? false : true;
        }

        return false;
    }

    getMinDate() {
        return this._normalizeDate(this.minDate, 'min');
    }

    getMaxDate() {
        return this._normalizeDate(this.maxDate, 'max');
    }

    _normalizeDate(input, type = 'min') {
        if (!input) {
            return null;
        }

        // Absolute ISO date string
        if (!isNaN(Date.parse(input))) {
            return new Date(input);
        }

        // Relative offset string
        return this._parseOffsetString(input, type);
    }

    _parseOffsetString(offset, type) {
        const match = offset.trim().match(/^([+-]?\d+)\s*(day|days|week|weeks|month|months|year|years)$/i);

        if (!match) {
            return null;
        }

        const [, amountStr, unitRaw] = match;
        const amount = parseInt(amountStr, 10);
        const unit = unitRaw.toLowerCase();
        const date = new Date();

        switch (unit) {
            case 'day':
            case 'days':
                date.setDate(date.getDate() + amount);
                break;
            case 'week':
            case 'weeks':
                date.setDate(date.getDate() + (amount * 7));
                break;
            case 'month':
            case 'months':
                date.setMonth(date.getMonth() + amount);
                break;
            case 'year':
            case 'years':
                date.setFullYear(date.getFullYear() + amount);
                break;
        }

        // Set time for boundaries
        if (type === 'min') {
            date.setHours(0, 0, 0, 0); // 00:00:00.000
        } else if (type === 'max') {
            date.setHours(23, 59, 59, 999); // 23:59:59.999
        }

        return date;
    }
}

window.FormieDatePicker = FormieDatePicker;
