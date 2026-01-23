import { t, addClasses, removeClasses } from '../utils/utils';
import rules from './rules';

class FormieValidator {
    constructor(form, config) {
        this.form = form;
        this.errors = [];
        this.errorIds = {};
        this.validators = {};
        this.boundListeners = false;
        this.activated = new WeakSet();
        this.submitted = false;
        this.initialValues = new WeakMap();

        this.onBlur = this.blurHandler.bind(this);
        this.onChange = this.changeHandler.bind(this);
        this.onInput = this.inputHandler.bind(this);

        this.config = {
            live: false,
            inputErrorIndicatorAttribute: 'data-field-has-error',
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

        // snapshot initial values
        this.inputs().forEach((input) => {
            this.initialValues.set(input, this.getInputValue(input));
        });

        if (this.config.live) {
            this.addEventListeners();
        }

        this.emitEvent(document, 'formieValidatorInitialized');
    }

    inputs(inputOrSelector = null) {
        // If this was a single form input, return straight away
        if (inputOrSelector instanceof HTMLElement) {
            const validFormTags = ['INPUT', 'TEXTAREA', 'SELECT', 'BUTTON'];

            if (validFormTags.includes(inputOrSelector.tagName.toUpperCase())) {
                return [inputOrSelector];
            }
        }

        // Otherwise, it's a selector to a regular DOM element. Find all inputs within that.
        if (!inputOrSelector) {
            inputOrSelector = this.form;
        }

        return inputOrSelector.querySelectorAll(this.config.fieldsSelector);
    }

    getInputValue(input) {
        if (input.type === 'checkbox' || input.type === 'radio') {
            return input.checked;
        }

        if (input.type === 'file') {
            return input.files?.length ? Array.from(input.files).map((f) => { return f.name; }).join('|') : '';
        }

        return input.value ?? '';
    }

    isDirty(input) {
        if (!this.initialValues.has(input)) {
            this.initialValues.set(input, this.getInputValue(input));
            return false;
        }

        return this.getInputValue(input) !== this.initialValues.get(input);
    }

    shouldShowError(input) {
        return this.submitted || this.activated.has(input);
    }

    validate(inputOrSelector = null) {
        this.errors = [];

        this.inputs(inputOrSelector).forEach((input) => {
            let errorShown = false;

            if (!this.isVisible(input)) {
                return;
            }

            // Always clear existing error if we’re going to revalidate this field
            // (so errors can disappear as the user fixes)
            if (this.shouldShowError(input)) {
                this.removeError(input);
            }

            const opts = this.getValidatorCallbackOptions(input);

            Object.entries(this.validators).forEach(([validatorName, validatorConfig]) => {
                const isValid = validatorConfig.validate(opts);

                if (!isValid) {
                    if (this.shouldShowError(input)) {
                        // Don't show multiple errors, but record them
                        if (!errorShown) {
                            const errorMessage = this.getErrorMessage(input, validatorName, validatorConfig, opts);

                            this.showError(input, validatorName, errorMessage);
                        }
                    }

                    this.errors.push({ input, validator: validatorName, result: isValid });

                    errorShown = true;
                }
            });

            // If invalid but we’re not allowed to show yet, don’t leave stale errors around
            if (!errorShown && this.shouldShowError(input)) {
                this.removeError(input);
            }
        });

        return this.errors;
    }

    removeAllErrors() {
        this.inputs().forEach((input) => {
            this.removeError(input);
        });
    }

    removeError(input) {
        input.removeAttribute('aria-errormessage');
        input.removeAttribute('aria-invalid');

        const fieldContainer = input.closest('[data-field-handle]');

        if (!fieldContainer) {
            return;
        }

        const errorMessages = fieldContainer.querySelector('[data-field-error-messages]');

        if (errorMessages) {
            errorMessages.remove();
        }

        removeClasses(input, this.config.inputErrorClass);
        input.removeAttribute(this.config.inputErrorIndicatorAttribute);

        // Bubble up the tree to find all field containers (think sub-fields)
        for (let el = input.parentElement; el; el = el.parentElement) {
            if (el.hasAttribute('data-field-handle')) {
                removeClasses(el, this.config.fieldContainerErrorClass);
            }
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

            addClasses(errorMessages, this.config.messagesClass);

            fieldContainer.appendChild(errorMessages);
        }

        // Find or create error element
        let errorElement = fieldContainer.querySelector(`[data-field-error-message-${validatorName}]`);

        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.setAttribute('data-field-error-message', '');
            errorElement.setAttribute(`data-field-error-message-${validatorName}`, '');

            addClasses(errorElement, this.config.messageClass);

            errorElement.textContent = errorMessage;

            // Append error element to errorMessages div
            errorMessages.appendChild(errorElement);

            // Create an error ID for this element, but saved to a cache, to ensure it's not generated every time
            const errorKey = `error-${fieldContainer.getAttribute('data-field-handle')}`;

            if (!this.errorIds[errorKey]) {
                this.errorIds[errorKey] = `${errorKey}-${Math.random().toString(20).substr(2, 5)}`;
            }

            const errorId = this.errorIds[errorKey];

            // Add aria attributes to the input, referencing the error element
            input.setAttribute('aria-errormessage', errorId);
            input.setAttribute('aria-invalid', true);

            // Set an ID for the error element for accessibility
            errorElement.setAttribute('id', errorId);
            errorElement.setAttribute('aria-live', 'polite');
            errorElement.setAttribute('aria-atomic', true);
        }

        // Add error classes to field
        addClasses(input, this.config.inputErrorClass);
        input.setAttribute(this.config.inputErrorIndicatorAttribute, true);

        // Bubble up the tree to find all field containers (think sub-fields)
        for (let el = input.parentElement; el; el = el.parentElement) {
            if (el.hasAttribute('data-field-handle')) {
                addClasses(el, this.config.fieldContainerErrorClass);
            }
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
            t,
            input,
            label,
            field: fieldContainer,
            config: this.config,
            getRule: (rule) => {
                return this.getRule(fieldContainer, rule);
            },
        };
    }

    getErrorMessage(input, validatorName, validator, opts) {
        const errorMessage = typeof validator.errorMessage === 'function' ? validator.errorMessage(opts) : validator.errorMessage;

        return errorMessage ?? t('{attribute} is invalid.', { attribute: opts.label });
    }

    getErrors() {
        return this.errors;
    }

    getRule(field, rule) {
        if (field) {
            const ruleString = field.getAttribute('data-validation');

            if (ruleString) {
                const rules = this.parseValidationRules(ruleString);

                if (rules[rule]) {
                    return rules[rule];
                }
            }
        }

        return false;
    }

    parseValidationRules(ruleString) {
        const rules = {};
        const parts = ruleString.split('|');

        parts.forEach((part) => {
            const [key, value] = part.split(':');
            rules[key] = value !== undefined ? value : true;
        });

        return rules;
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

        // Only activate if they actually changed the value
        if (this.isDirty(e.target)) {
            this.activated.add(e.target);
        }

        if (this.shouldShowError(e.target)) {
            this.validate(e.target);
        }
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

        // For discrete-choice fields, a change is inherently “dirty”
        this.activated.add(e.target);
        this.validate(e.target);
    }

    inputHandler(e) {
        // Formie will have it's own events, so ignore those
        // Only run if the field is in a form to be validated
        if (e instanceof CustomEvent || !e.target.form || !e.target.form.isSameNode(this.form)) {
            return;
        }

        // // Don't trigger click event handling for checkbox/radio. We should use the change.
        if (e.target.type === 'checkbox' || e.target.type === 'radio') {
            return;
        }

        // Only show errors while typing after first blur (or submit)
        if (this.shouldShowError(e.target)) {
            this.validate(e.target);
        }
    }

    submit(inputOrSelector = null) {
        this.submitted = true;

        // After first submit attempt, become “live” for fixing errors
        // In non-live mode, we bind listeners only now.
        if (!this.config.live) {
            this.addEventListeners();
        }

        return this.validate(inputOrSelector);
    }

    addEventListeners() {
        if (!this.boundListeners) {
            this.form.addEventListener('blur', this.onBlur, true);
            this.form.addEventListener('change', this.onChange, false);
            this.form.addEventListener('input', this.onInput, false);

            this.boundListeners = true;
        }
    }

    removeEventListeners() {
        this.form.removeEventListener('blur', this.onBlur, true);
        this.form.removeEventListener('change', this.onChange, false);
        this.form.removeEventListener('input', this.onInput, false);

        this.boundListeners = false;
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
