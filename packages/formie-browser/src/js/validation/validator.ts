import { syncPageTabErrors } from '#core/page-tab-errors';
import rules from '#validation/rules';
import type {
    ValidationConfig,
    ValidationContext,
    ValidationError,
    ValidationInput,
    ValidationRules,
} from '#validation/types';
import { t } from '#utils/i18n';
import { getValidatorEventName } from '#utils/event-names';
import { createDebug } from '#utils/debug';

type ValidatorDefinition = {
    validate: (ctx: ValidationContext) => boolean;
    errorMessage?: (ctx: ValidationContext) => string;
};

type ValidateOptions = {
    includeHiddenPages?: boolean;
};

const DEFAULT_PATTERNS: Record<string, RegExp> = {
    // eslint-disable-next-line
    email: /^([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22))*\x40([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d))*(\.\w{2,})+$/,
    url: /^(?:(?:https?|HTTPS?|ftp|FTP):\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)(?:\.(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)*(?:\.(?:[a-zA-Z\u00a1-\uffff]{2,}))\.?)(?::\d{2,5})?(?:[/?#]\S*)?$/,
    number: /^(?:[-+]?[0-9]*[.,]?[0-9]+)$/,
    color: /^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/,
    date: /(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-9])|(?:(?!02)(?:0[1-9]|1[0-2])-(?:30))|(?:(?:0[13578]|1[02])-31))/,
    time: /^(?:(0[0-9]|1[0-9]|2[0-3])(:[0-5][0-9]))$/,
    month: /^(?:(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])))$/,
};
const debug = createDebug('general', 'validator');

function isValidationInput(node: Element | null): node is ValidationInput {
    return !!node && (
        node instanceof HTMLInputElement ||
        node instanceof HTMLSelectElement ||
        node instanceof HTMLTextAreaElement
    );
}

function removeDescribedBy(input: HTMLElement, describedById: string): void {
    const current = (input.getAttribute('aria-describedby') || '').trim();

    if (!current) {
        return;
    }

    const filtered = current.split(/\s+/).filter((item) => {
        return item !== describedById;
    });

    if (filtered.length) {
        input.setAttribute('aria-describedby', filtered.join(' '));
        return;
    }

    input.removeAttribute('aria-describedby');
}

function appendDescribedBy(input: HTMLElement, describedById: string): void {
    const current = (input.getAttribute('aria-describedby') || '').trim();
    const items = current ? current.split(/\s+/) : [];

    if (!items.includes(describedById)) {
        items.push(describedById);
    }

    input.setAttribute('aria-describedby', items.join(' ').trim());
}

function setErrorMessageReference(input: HTMLElement, errorMessageId: string): void {
    input.setAttribute('aria-errormessage', errorMessageId);
}

function clearErrorMessageReference(input: HTMLElement, errorMessageId: string): void {
    if (input.getAttribute('aria-errormessage') === errorMessageId) {
        input.removeAttribute('aria-errormessage');
    }
}

export class FormieValidator {
    form: HTMLFormElement;
    errors: ValidationError[] = [];
    validators: Record<string, ValidatorDefinition> = {};
    boundListeners = false;
    activated = new WeakSet<ValidationInput>();
    submitted = false;
    initialValues = new WeakMap<ValidationInput, string | boolean>();
    onBlur: EventListener;
    onChange: EventListener;
    onInput: EventListener;
    config: ValidationConfig;

    constructor(form: HTMLFormElement, config: Partial<ValidationConfig> = {}) {
        this.form = form;
        this.onBlur = this.blurHandler.bind(this);
        this.onChange = this.changeHandler.bind(this);
        this.onInput = this.inputHandler.bind(this);
        this.config = {
            live: false,
            errorMessage: '',
            fieldContainerErrorClass: [],
            inputErrorClass: [],
            messagesClass: [],
            messageClass: [],
            fieldsSelector: 'input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([disabled]), select:not([disabled]), textarea:not([disabled])',
            patterns: DEFAULT_PATTERNS,
            ...config,
        };

        Object.entries(rules).forEach(([validatorName, validator]) => {
            this.addValidator(validatorName, validator.rule, validator.message);
        });

        this.init();
    }

    init(): void {
        debug.log('Initializing validator.', {
            formId: this.form.id || null,
            live: this.config.live,
        });
        this.form.setAttribute('novalidate', 'true');

        this.inputs().forEach((input) => {
            this.initialValues.set(input, this.getInputValue(input));
        });

        if (this.config.live) {
            this.addEventListeners();
        }

        this.emitEvent(document, getValidatorEventName('ready'), {
            validator: this,
        });
    }

    inputs(inputOrSelector: Element | null = null): ValidationInput[] {
        if (isValidationInput(inputOrSelector)) {
            return [inputOrSelector];
        }

        const root = inputOrSelector || this.form;

        return Array.from(root.querySelectorAll(this.config.fieldsSelector)).filter((input): input is ValidationInput => {
            return isValidationInput(input);
        });
    }

    getInputValue(input: ValidationInput): string | boolean {
        if (input instanceof HTMLInputElement && (input.type === 'checkbox' || input.type === 'radio')) {
            return input.checked;
        }

        if (input instanceof HTMLInputElement && input.type === 'file') {
            return input.files?.length ? Array.from(input.files).map((file) => {
                return file.name;
            }).join('|') : '';
        }

        return input.value ?? '';
    }

    isDirty(input: ValidationInput): boolean {
        if (!this.initialValues.has(input)) {
            this.initialValues.set(input, this.getInputValue(input));
            return false;
        }

        return this.getInputValue(input) !== this.initialValues.get(input);
    }

    shouldShowError(input: ValidationInput): boolean {
        return this.submitted || this.activated.has(input);
    }

    validate(inputOrSelector: Element | null = null, options: ValidateOptions = {}): ValidationError[] {
        this.errors = [];

        // Checkbox/radio fields often render as many inputs for one logical field.
        // We validate and render errors once per group to avoid duplicate messages.
        const seenGroups = new Set<string>();

        this.inputs(inputOrSelector).forEach((input) => {
            let errorShown = false;

            if (!this.isVisible(input, options)) {
                return;
            }

            const field = input.closest('[data-formie-field-handle]') as HTMLElement | null;
            const groupKey = (input instanceof HTMLInputElement && (input.type === 'checkbox' || input.type === 'radio'))
                ? `${field?.getAttribute('data-formie-field-handle') || ''}:${input.name}`
                : null;

            if (groupKey) {
                if (seenGroups.has(groupKey)) {
                    return;
                }

                seenGroups.add(groupKey);
            }

            if (this.shouldShowError(input)) {
                this.removeError(input);
            }

            const opts = this.getValidatorCallbackOptions(input);

            Object.entries(this.validators).forEach(([validatorName, validatorConfig]) => {
                const isValid = validatorConfig.validate(opts);

                if (!isValid) {
                    const errorMessage = this.getErrorMessage(input, validatorName, validatorConfig, opts);

                    if (this.shouldShowError(input) && !errorShown) {
                        this.showError(input, validatorName, errorMessage);
                    }

                    this.errors.push({
                        input,
                        field: opts.field,
                        validator: validatorName,
                        message: errorMessage,
                        handle: opts.field?.getAttribute('data-formie-field-handle') || null,
                        result: false,
                    });

                    errorShown = true;
                }
            });

            if (!errorShown && this.shouldShowError(input)) {
                this.removeError(input);
            }
        });

        debug.log('Validation pass complete.', {
            errorCount: this.errors.length,
            includeHiddenPages: options.includeHiddenPages === true,
        });
        return this.errors;
    }

    removeAllErrors(): void {
        this.inputs().forEach((input) => {
            this.removeError(input);
        });
    }

    removeError(input: ValidationInput): void {
        const fieldContainer = input.closest('[data-formie-field-handle]') as HTMLElement | null;

        if (!fieldContainer) {
            input.removeAttribute('aria-invalid');
            return;
        }

        const errorMessages = fieldContainer.querySelector('[data-formie-field-errors]') as HTMLElement | null;
        const errorContainerId = errorMessages?.id || '';

        fieldContainer.querySelectorAll('[data-formie-field-error]').forEach((node) => {
            node.remove();
        });

        if (errorMessages) {
            errorMessages.innerHTML = '';
        }

        fieldContainer.querySelectorAll('input, select, textarea').forEach((fieldInput) => {
            const element = fieldInput as HTMLElement;
            element.removeAttribute('aria-invalid');
            if (this.config.inputErrorClass.length) {
                element.classList.remove(...this.config.inputErrorClass);
            }
            element.removeAttribute('data-formie-input-has-error');

            if (errorContainerId) {
                removeDescribedBy(element, errorContainerId);
            }

            fieldContainer.querySelectorAll('[data-formie-field-error]').forEach((errorNode) => {
                const errorMessageId = (errorNode as HTMLElement).id;

                if (errorMessageId) {
                    clearErrorMessageReference(element, errorMessageId);
                }
            });
        });

        // Nested field wrappers inherit error state so repeaters/groups can reflect
        // child validation failures without each feature reimplementing traversal.
        for (let element = fieldContainer as HTMLElement | null; element; element = element.parentElement?.closest('[data-formie-field-handle]') as HTMLElement | null) {
            if (this.config.fieldContainerErrorClass.length) {
                element.classList.remove(...this.config.fieldContainerErrorClass);
            }
            element.removeAttribute('data-formie-field-has-error');
        }

        this.emitEvent(input, getValidatorEventName('clear-error'), {
            validator: this,
        });

        syncPageTabErrors(this.form);
    }

    showError(input: ValidationInput, validatorName: string, errorMessage: string): void {
        const fieldContainer = input.closest('[data-formie-field-handle]') as HTMLElement | null;

        if (!fieldContainer) {
            return;
        }

        let errorMessages = fieldContainer.querySelector('[data-formie-field-errors]') as HTMLElement | null;

        if (!errorMessages) {
            errorMessages = document.createElement('div');
            errorMessages.setAttribute('data-formie-field-errors', 'true');
            if (this.config.messagesClass.length) {
                errorMessages.classList.add(...this.config.messagesClass);
            }
            fieldContainer.appendChild(errorMessages);
        }

        if (this.config.messagesClass.length) {
            errorMessages.classList.add(...this.config.messagesClass);
        }
        errorMessages.innerHTML = '';

        const handle = fieldContainer.getAttribute('data-formie-field-handle') || 'field';
        const errorId = `${handle}-error`;
        errorMessages.id = errorMessages.id || `${handle}-errors`;
        errorMessages.setAttribute('aria-live', 'polite');
        errorMessages.setAttribute('aria-atomic', 'true');

        const errorElement = document.createElement('div');
        errorElement.setAttribute('data-formie-field-error', 'true');
        errorElement.setAttribute(`data-formie-field-error-${validatorName}`, 'true');
        errorElement.setAttribute('id', errorId);
        errorElement.setAttribute('role', 'alert');
        if (this.config.messageClass.length) {
            errorElement.classList.add(...this.config.messageClass);
        }
        errorElement.textContent = errorMessage;
        errorMessages.appendChild(errorElement);

        fieldContainer.setAttribute('data-formie-field-has-error', 'true');

        fieldContainer.querySelectorAll('input, select, textarea').forEach((fieldInput) => {
            const element = fieldInput as HTMLElement;
            element.setAttribute('aria-invalid', 'true');
            if (this.config.inputErrorClass.length) {
                element.classList.add(...this.config.inputErrorClass);
            }
            element.setAttribute('data-formie-input-has-error', 'true');
            appendDescribedBy(element, errorMessages.id);
            setErrorMessageReference(element, errorId);
        });

        // Mirror the same nested traversal on add so parent wrappers stay visually
        // in sync with the actual field that failed validation.
        for (let element = fieldContainer as HTMLElement | null; element; element = element.parentElement?.closest('[data-formie-field-handle]') as HTMLElement | null) {
            if (this.config.fieldContainerErrorClass.length) {
                element.classList.add(...this.config.fieldContainerErrorClass);
            }
            element.setAttribute('data-formie-field-has-error', 'true');
        }

        this.emitEvent(input, getValidatorEventName('show-error'), {
            validator: this,
            validatorName,
            errorMessage,
        });

        syncPageTabErrors(this.form);
    }

    getValidatorCallbackOptions(input: ValidationInput): ValidationContext {
        const fieldContainer = input.closest('[data-formie-field-handle]') as HTMLElement | null;
        const label = fieldContainer?.querySelector('[data-formie-field-label]')?.childNodes[0]?.textContent?.trim() ?? '';
        const rules = this.parseValidationRules(fieldContainer?.getAttribute('data-formie-validation'));

        return {
            t,
            input,
            label,
            field: fieldContainer,
            form: this.form,
            config: this.config,
            rules,
            getRule: (rule) => {
                return this.getRule(fieldContainer, rule);
            },
        };
    }

    getErrorMessage(input: ValidationInput, validatorName: string, validator: ValidatorDefinition, opts: ValidationContext): string {
        const errorMessage = typeof validator.errorMessage === 'function' ? validator.errorMessage(opts) : validator.errorMessage;
        return errorMessage ?? t('{attribute} is invalid.', { attribute: opts.label });
    }

    getErrors(): ValidationError[] {
        return this.errors;
    }

    getFieldErrors(errors: ValidationError[] = this.errors): Record<string, string[]> {
        const fieldErrors: Record<string, string[]> = {};

        errors.forEach((error) => {
            if (!error.handle || fieldErrors[error.handle]?.length) {
                return;
            }

            fieldErrors[error.handle] = [error.message];
        });

        return fieldErrors;
    }

    getRule(field: HTMLElement | null, rule: string): ValidationRules[string] | false {
        if (!field) {
            return false;
        }

        const rules = this.parseValidationRules(field.getAttribute('data-formie-validation'));

        if (Object.prototype.hasOwnProperty.call(rules, rule)) {
            return rules[rule];
        }

        return false;
    }

    parseValidationRules(ruleString: string | null | undefined): ValidationRules {
        const rules: ValidationRules = {};

        if (!ruleString) {
            return rules;
        }

        let parsedRules: unknown = null;

        try {
            parsedRules = JSON.parse(ruleString);
        } catch {
            debug.warn('Invalid validation rules payload.', {
                formId: this.form.id || null,
            });
            return rules;
        }

        if (!Array.isArray(parsedRules)) {
            return rules;
        }

        parsedRules.forEach((part) => {
            if (!part || typeof part !== 'object' || Array.isArray(part)) {
                return;
            }

            const candidate = part as Record<string, unknown>;
            const type = typeof candidate.type === 'string' ? candidate.type.trim() : '';

            if (!type) {
                return;
            }

            rules[type] = candidate as ValidationRules[string];
        });

        return rules;
    }

    destroy(): void {
        debug.log('Destroying validator.', {
            formId: this.form.id || null,
        });
        this.removeEventListeners();
        this.form.removeAttribute('novalidate');

        this.emitEvent(document, getValidatorEventName('destroy'), {
            validator: this,
        });
    }

    isVisible(element: ValidationInput, options: ValidateOptions = {}): boolean {
        // Conditionally hidden fields are treated as inactive inputs, and hidden
        // pages are only re-included on the final page submit pass.
        if (element.closest('[data-formie-conditionally-hidden]')) {
            return false;
        }

        if (element.closest('[data-formie-page-hidden]')) {
            return !!options.includeHiddenPages;
        }

        return !!(element.offsetWidth || element.offsetHeight || element.getClientRects().length);
    }

    blurHandler(event: Event): void {
        if (!(event.target instanceof HTMLElement) || !isValidationInput(event.target) || !event.target.form?.isSameNode(this.form)) {
            return;
        }

        if (event instanceof CustomEvent) {
            return;
        }

        if (event.target instanceof HTMLInputElement && event.target.type === 'file') {
            return;
        }

        if (event.target instanceof HTMLInputElement && (event.target.type === 'checkbox' || event.target.type === 'radio')) {
            return;
        }

        if (this.isDirty(event.target)) {
            this.activated.add(event.target);
        }

        if (this.shouldShowError(event.target)) {
            this.validate(event.target);
        }
    }

    changeHandler(event: Event): void {
        if (!(event.target instanceof HTMLElement) || !isValidationInput(event.target) || !event.target.form?.isSameNode(this.form)) {
            return;
        }

        if (event instanceof CustomEvent) {
            return;
        }

        if (event.target instanceof HTMLSelectElement) {
            this.activated.add(event.target);
            this.validate(event.target);
            return;
        }

        if (!(event.target instanceof HTMLInputElement)) {
            return;
        }

        if (
            event.target.type !== 'file' &&
            event.target.type !== 'checkbox' &&
            event.target.type !== 'radio'
        ) {
            return;
        }

        this.activated.add(event.target);
        this.validate(event.target);
    }

    inputHandler(event: Event): void {
        if (!(event.target instanceof HTMLElement) || !isValidationInput(event.target) || !event.target.form?.isSameNode(this.form)) {
            return;
        }

        if (event instanceof CustomEvent) {
            return;
        }

        if (event.target instanceof HTMLInputElement && (event.target.type === 'checkbox' || event.target.type === 'radio')) {
            return;
        }

        if (this.shouldShowError(event.target)) {
            this.validate(event.target);
        }
    }

    submit(inputOrSelector: Element | null = null, { final = false }: { final?: boolean } = {}): ValidationError[] {
        this.submitted = true;
        debug.log('Submit validation requested.', {
            final,
        });

        // After the first submit attempt, validation should respond live while the user corrects errors.
        if (!this.boundListeners) {
            this.addEventListeners();
        }

        this.removeAllErrors();

        return this.validate(inputOrSelector, {
            includeHiddenPages: final,
        });
    }

    resetLiveState(): void {
        this.submitted = false;
        this.activated = new WeakSet<ValidationInput>();
        this.errors = [];
        this.removeAllErrors();
    }

    addEventListeners(): void {
        if (this.boundListeners) {
            return;
        }

        this.form.addEventListener('blur', this.onBlur, true);
        this.form.addEventListener('change', this.onChange, false);
        this.form.addEventListener('input', this.onInput, false);
        this.boundListeners = true;
        debug.log('Event listeners attached.');
    }

    removeEventListeners(): void {
        this.form.removeEventListener('blur', this.onBlur, true);
        this.form.removeEventListener('change', this.onChange, false);
        this.form.removeEventListener('input', this.onInput, false);
        this.boundListeners = false;
        debug.log('Event listeners removed.');
    }

    emitEvent(target: Document | Element, type: string, detail: Record<string, unknown> = {}): void {
        target.dispatchEvent(new CustomEvent(type, {
            bubbles: true,
            detail,
        }));
    }

    addValidator(
        name: string,
        validatorFunction: (ctx: ValidationContext) => boolean,
        errorMessage?: (ctx: ValidationContext) => string,
    ): void {
        this.validators[name] = {
            validate: validatorFunction,
            errorMessage,
        };
    }

    removeValidator(name: string): void {
        delete this.validators[name];
    }
}
