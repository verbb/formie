import { eventKey } from '../utils/utils';
import intlTelInput from 'intl-tel-input';

export class FormiePhoneCountry {
    constructor(settings = {}) {
        this.formId = '#formie-form-' + settings.formId;
        this.fieldId = '#fields-' + settings.fieldId;
        this.countryFieldId = '#fields-' + settings.countryFieldId;
        this.countryShowDialCode = settings.countryShowDialCode;
        this.countryDefaultValue = settings.countryDefaultValue;
        this.countryAllowed = settings.countryAllowed;

        this.$form = document.querySelector(this.formId);
        this.$field = document.querySelector(this.formId + ' ' + this.fieldId);
        this.$countryInput = document.querySelector(this.formId + ' ' + this.countryFieldId);

        if (this.$form && this.$field) {
            this.form = this.$form.form;

            this.initValidator();
        } else {
            console.error('Unable to find ' + this.formId + ' ' + this.fieldId);
        }
    }

    initValidator() {
        var options = {
            allowDropdown: true,
            autoHideDialCode: true,
            nationalMode: false,
            preferredCountries: [],
            separateDialCode: false,
            initialCountry: 'auto',
            autoPlaceholder: 'off',
            utilsScript: 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.min.js',
        };

        if (this.countryAllowed && this.countryAllowed.length) {
            options.onlyCountries = this.countryAllowed.map(item => {
                return item.value.toLowerCase();
            });

            // Set the initial country to the first in collection
            [options.initialCountry] = options.onlyCountries;

            // Not much point showing a dropdown if a single option.
            // Also put it into national mode for input-ease
            if (options.onlyCountries.length === 1) {
                options.allowDropdown = false;
                options.nationalMode = true;
            }

            // Save this on the field so we can check during validation
            this.$field.restrictedCountries = true;
        }

        if (this.countryShowDialCode) {
            options.separateDialCode = true;
        }

        if (this.countryDefaultValue) {
            options.initialCountry = this.countryDefaultValue;
        }

        this.validator = intlTelInput(this.$field, options);

        // Attach the validator to the field so we can access later
        this.$field.validator = this.validator;

        // Also add the hidden input for the country code
        this.$field.$countryInput = this.$countryInput;

        // Emit an "init" event
        this.$field.dispatchEvent(new CustomEvent('init', {
            bubbles: true,
            detail: {
                phoneCountry: this,
                validator: this.validator,
                validatorOptions: options,
            },
        }));

        // Attach custom validation
        this.form.addEventListener(this.$form, eventKey('registerFormieValidation'), this.registerValidation.bind(this));
    }

    registerValidation(e) {
        // Add our custom validations logic and methods
        e.detail.validatorSettings.customValidations = {
            ...e.detail.validatorSettings.customValidations,
            ...this.getPhoneRule(),
        };

        // Add our custom messages
        e.detail.validatorSettings.messages = {
            ...e.detail.validatorSettings.messages,
            ...this.getPhoneMessage(),
        };
    }

    getPhoneRule() {
        return {
            phoneCountry(field) {
                if (field.value.trim() && field.validator) {
                    if (field.validator.isValidNumber()) {
                        var countryData = field.validator.getSelectedCountryData();
                        var selectedCountryCode = countryData.iso2;

                        // The library doesn't provide a method to check if it's a valid number against restricted countries
                        // so we need to do that ourselves.
                        if (field.restrictedCountries) {
                            // Check if this country code is in our allowed codes. Note `selectedCountryCode` will
                            // be empty if it matches a valid phone for a non-allowed country.
                            if (!field.validator.options.onlyCountries.includes(selectedCountryCode)) {
                                return true;
                            }
                        }

                        // Save the country code to the hidden input
                        if (field.$countryInput) {
                            field.$countryInput.value = selectedCountryCode.toUpperCase();
                        }
                    } else {
                        return true;
                    }
                }
            },
        };
    }

    getPhoneMessage() {
        return {
            phoneCountry(field) {
                var errorMap = ['Invalid number', 'Invalid country code', 'Too short', 'Too long', 'Invalid number'];
                var errorCode = field.validator.getValidationError();

                return t(errorMap[errorCode]);
            },
        };
    }
}

window.FormiePhoneCountry = FormiePhoneCountry;
