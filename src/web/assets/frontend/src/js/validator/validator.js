import { t } from '../utils/utils';
import rules from './rules';

class FormieValidator {
    constructor(form, config) {
        this.form = form;
        this.errors = [];
        this.validators = {};
        this.boundListeners = false;

        this.config = {
            live: false,
            fieldContainerErrorClass: 'fui-error',
            inputErrorClass: 'fui-error',
            messagesClass: 'fui-errors',
            messageClass: 'fui-error-message',
            fieldsSelector: 'input:not([type="hidden"]):not([type="submit"]):not([type="button"]), select, textarea',

            patterns: {
                // eslint-disable-next-line
                email: /^([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22))*\x40([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d))*(\.\w{2,})+$/,
                url: /^(?:(?:https?|HTTPS?|ftp|FTP):\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)(?:\.(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)*(?:\.(?:[a-zA-Z\u00a1-\uffff]{2,}))\.?)(?::\d{2,5})?(?:[/?#]\S*)?$/,
                number: /^(?:[-+]?[0-9]*[.,]?[0-9]+)$/,
                color: /^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/,
                date: /(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-9])|(?:(?!02)(?:0[1-9]|1[0-2])-(?:30))|(?:(?:0[13578]|1[02])-31))/,
                time: /^(?:(0[0-9]|1[0-9]|2[0-3])(:[0-5][0-9]))$/,
                month: /^(?:(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])))$/,
            },

            ...config,
        };

        // Register core validators
        Object.entries(rules).forEach(([validatorName, validator]) => {
            this.addValidator(validatorName, validator.rule, validator.message);
        });

        this.init();
    }

    init() {
        this.form.setAttribute('novalidate', true);

        if (this.config.live) {
            this.addEventListeners();
        }

        this.emitEvent(document, 'formieValidatorInitialized');
    }

    inputs(inputOrSelector = null) {
        // If this was a single form input, return straight away
        if (inputOrSelector instanceof HTMLElement && inputOrSelector.getAttribute('type')) {
            return [inputOrSelector];
        }

        // Otherwise, it's a selector to a regular DOM element. Find all inputs within that.
        if (!inputOrSelector) {
            inputOrSelector = this.form;
        }

        return inputOrSelector.querySelectorAll(this.config.fieldsSelector);
    }

    validate(inputOrSelector = null) {
        this.errors = [];

        this.inputs(inputOrSelector).forEach((input) => {
            let errorShown = false;

            if (!this.isVisible(input)) {
                return;
            }

            this.removeError(input);

            Object.entries(this.validators).forEach(([validatorName, validatorConfig]) => {
                const opts = this.getValidatorCallbackOptions(input);
                const isValid = validatorConfig.validate(opts);

                if (!isValid) {
                    // Don't show multiple errors, but record them
                    if (!errorShown) {
                        const errorMessage = this.getErrorMessage(input, validatorName, validatorConfig);

                        this.showError(input, validatorName, errorMessage);
                    }

                    this.errors.push({ input, validator: validatorName, result: isValid });

                    errorShown = true;
                }
            });
        });

        // Even if set to non-live, add event listeners to make the form have live validation, so that errors
        // are updated in real-time after the user hits submit. This is just good UX.
        if (!this.config.live) {
            this.addEventListeners();
        }
    }

    removeAllErrors() {
        this.inputs().forEach((input) => {
            this.removeError(input);
        });
    }

    removeError(input) {
        const fieldContainer = input.closest('[data-field-handle]');

        if (!fieldContainer) {
            return;
        }

        const errorMessages = fieldContainer.querySelector('[data-field-error-messages]');

        if (errorMessages) {
            errorMessages.remove();
        }

        if (this.config.fieldContainerErrorClass) {
            fieldContainer.classList.remove(this.config.fieldContainerErrorClass);
        }

        if (this.config.inputErrorClass) {
            input.classList.remove(this.config.inputErrorClass);
        }

        this.emitEvent(input, 'formieValidatorClearError');
    }

    showError(input, validatorName, errorMessage) {
        const fieldContainer = input.closest('[data-field-handle]');

        if (!fieldContainer) {
            return;
        }

        let errorMessages = fieldContainer.querySelector('[data-field-error-messages]');

        if (!errorMessages) {
            errorMessages = document.createElement('div');
            errorMessages.setAttribute('data-field-error-messages', '');

            if (this.config.messagesClass) {
                errorMessages.classList.add(this.config.messagesClass);
            }

            fieldContainer.appendChild(errorMessages);
        }

        // Find or create error element
        const errorElement = fieldContainer.querySelector(`[data-field-error-message-${validatorName}]`);

        if (!errorElement) {
            const errorElement = document.createElement('div');
            errorElement.setAttribute('data-field-error-message', '');
            errorElement.setAttribute(`data-field-error-message-${validatorName}`, '');

            if (this.config.messageClass) {
                errorElement.classList.add(this.config.messageClass);
            }

            errorElement.textContent = errorMessage;

            // Append error element to errorMessages div
            errorMessages.appendChild(errorElement);
        }

        // Add error classes to field and field container
        if (this.config.fieldContainerErrorClass) {
            fieldContainer.classList.add(this.config.fieldContainerErrorClass);
        }

        if (this.config.inputErrorClass) {
            input.classList.add(this.config.inputErrorClass);
        }

        this.emitEvent(input, 'formieValidatorShowError', {
            validatorName,
            errorMessage,
        });
    }

    getValidatorCallbackOptions(input) {
        const fieldContainer = input.closest('[data-field-handle]');

        // The label is pretty common, so add that in
        const label = fieldContainer?.querySelector('[data-field-label]')?.childNodes[0].textContent?.trim() ?? '';

        return {
            input,
            label,
            field: fieldContainer,
            t,
            config: this.config,
        };
    }

    getErrorMessage(input, validatorName, validator) {
        const opts = this.getValidatorCallbackOptions(input);

        const errorMessage = typeof validator.errorMessage === 'function' ? validator.errorMessage(opts) : validator.errorMessage;

        return errorMessage ?? t('{attribute} is invalid.', { attribute: opts.label });
    }

    getErrors() {
        return this.errors;
    }

    destroy() {
        this.removeEventListeners();

        // Remove novalidate attribute
        this.form.removeAttribute('novalidate');

        this.emitEvent(document, 'formieValidatorDestroyed');
    }

    isVisible(element) {
        return !!(element.offsetWidth || element.offsetHeight || element.getClientRects().length);
    }

    blurHandler(e) {
        // Formie will have it's own events, so ignore those
        // Only run if the field is in a form to be validated
        if (e instanceof CustomEvent || !e.target.form || !e.target.form.isSameNode(this.form)) {
            return;
        }

        // Special-case for file field, blurs as soon as the selector kicks in
        if (e.target.type === 'file') {
            return;
        }

        // Don't trigger click event handling for checkbox/radio. We should use the change.
        if (e.target.type === 'checkbox' || e.target.type === 'radio') {
            return;
        }

        // Validate the field
        this.validate(e.target);
    }

    changeHandler(e) {
        // Formie will have it's own events, so ignore those
        // Only run if the field is in a form to be validated
        if (e instanceof CustomEvent || !e.target.form || !e.target.form.isSameNode(this.form)) {
            return;
        }

        // Only handle change events for some fields
        if (e.target.type !== 'file' && e.target.type !== 'checkbox' && e.target.type !== 'radio') {
            return;
        }

        // Validate the field
        this.validate(e.target);
    }

    inputHandler(e) {
        // Formie will have it's own events, so ignore those
        // Only run if the field is in a form to be validated
        if (e instanceof CustomEvent || !e.target.form || !e.target.form.isSameNode(this.form)) {
            return;
        }

        // Only run on fields with errors
        if (!e.target.classList.contains(this.config.inputErrorClass)) {
            return;
        }

        // // Don't trigger click event handling for checkbox/radio. We should use the change.
        if (e.target.type === 'checkbox' || e.target.type === 'radio') {
            return;
        }

        // Validate the field
        this.validate(e.target);
    }

    clickHandler(e) {
        // Formie will have it's own events, so ignore those
        // Only run if the field is in a form to be validated
        if (e instanceof CustomEvent || !e.target.form || !e.target.form.isSameNode(this.form)) {
            return;
        }

        // Only run on fields with errors
        if (!e.target.classList.contains(this.config.inputErrorClass)) {
            return;
        }

        // Don't trigger click event handling for checkbox/radio. We should use the change.
        if (e.target.type === 'checkbox' || e.target.type === 'radio') {
            return;
        }

        // Validate the field
        this.validate(e.target);
    }

    addEventListeners() {
        if (!this.boundListeners) {
            this.form.addEventListener('blur', this.blurHandler.bind(this), true);
            this.form.addEventListener('change', this.changeHandler.bind(this), false);
            this.form.addEventListener('input', this.inputHandler.bind(this), false);
            this.form.addEventListener('click', this.clickHandler.bind(this), false);

            this.boundListeners = true;
        }
    }

    removeEventListeners() {
        this.form.removeEventListener('blur', this.blurHandler, true);
        this.form.removeEventListener('change', this.changeHandler, false);
        this.form.removeEventListener('input', this.inputHandler, false);
        this.form.removeEventListener('click', this.clickHandler, false);
    }

    emitEvent(el, type, details) {
        const event = new CustomEvent(type, {
            bubbles: true,
            detail: details || {},
        });

        el.dispatchEvent(event);
    }

    addValidator(name, validatorFunction, errorMessage) {
        this.validators[name] = {
            validate: validatorFunction,
            errorMessage,
        };
    }
}

export default FormieValidator;
