import { Bouncer } from './utils/bouncer';

class FormieForm {
    constructor(settings = {}) {
        this.formId = `#formie-form-${settings.formId}`;
        this.$form = document.querySelector(this.formId);
        this.settings = settings.settings;
        this.validationOnSubmit = !!this.settings.validationOnSubmit;
        this.validationOnFocus = !!this.settings.validationOnFocus;

        this.setCurrentPage(this.settings.currentPageId);

        if (this.$form) {
            this.initValidator();

            // Check if this is a success page and if we need to hide the notice
            // This is for non-ajax forms, where the page has reloaded
            this.hideSuccess();

            // Hijack the form's submit handler, in case we need to do something
            this.addSubmitEventListener();

            // Save the form's current state so we can tell if its changed later on
            this.savedFormHash = this.hashForm();

            // Listen to form changes if the user tries to reload
            this.addFormUnloadEventListener();
        }
    }

    initValidator() {
        // Kick off validation - use this even if disabling client-side validation
        // so we can use a nice API handle server-side errprs
        var validatorSettings = {
            fieldClass: 'fui-error',
            errorClass: 'fui-error-message',
            fieldPrefix: 'fui-field-',
            errorPrefix: 'fui-error-',
            messageAfterField: true,
            messageCustom: 'data-fui-message',
            messageTarget: 'data-fui-target',
            validateOnBlur: this.validationOnFocus,

            // Call validation on-demand
            validateOnSubmit: false,
            disableSubmit: false,

            customValidations: {},

            messages: {
                missingValue: {
                    checkbox: t('This field is required.'),
                    radio: t('Please select a value.'),
                    select: t('Please select a value.'),
                    'select-multiple': t('Please select at least one value.'),
                    default: t('Please fill out this field.'),
                },

                patternMismatch: {
                    email: t('Please enter a valid email address.'),
                    url: t('Please enter a URL.'),
                    number: t('Please enter a number'),
                    color: t('Please match the following format: #rrggbb'),
                    date: t('Please use the YYYY-MM-DD format'),
                    time: t('Please use the 24-hour time format. Ex. 23:00'),
                    month: t('Please use the YYYY-MM format'),
                    default: t('Please match the requested format.'),
                },

                outOfRange: {
                    over: t('Please select a value that is no more than {max}.'),
                    under: t('Please select a value that is no less than {min}.'),
                },

                wrongLength: {
                    over: t('Please shorten this text to no more than {maxLength} characters. You are currently using {length} characters.'),
                    under: t('Please lengthen this text to {minLength} characters or more. You are currently using {length} characters.'),
                },

                fallback: t('There was an error with this field.'),
            },
        };

        // Allow other modules to modify our validator settings (for custom rules and messages)
        const registerFormieValidation = new CustomEvent('registerFormieValidation', {
            bubbles: true,
            detail: {
                validatorSettings,
            },
        });

        this.$form.dispatchEvent(registerFormieValidation);

        this.validator = new Bouncer(this.formId, registerFormieValidation.detail.validatorSettings);

        // Override error messages defined in DOM - Bouncer only uses these as a last resort
        // In future updates, we can probably remove this
        document.addEventListener('bouncerShowError', (e) => {
            var $field = e.target;
            var $fieldContainer = $field.closest('.fui-field-container');
            var message = $field.getAttribute('data-fui-message');

            // Check if we need to move the error out of the .fui-input-container node.
            // Only the input itself should be in here.
            var $errorToMove = $field.parentNode.querySelector('.fui-error-message');

            if ($errorToMove && $errorToMove.parentNode.parentNode) {
                $errorToMove.parentNode.parentNode.appendChild($errorToMove);
            }

            // The error has been moved, find it again
            if ($fieldContainer) {
                var $error = $fieldContainer.querySelector('.fui-error-message');

                if ($error && message) {
                    $error.textContent = message;
                }
            }
        }, false);
    }

    addSubmitEventListener() {
        var $submitBtns = this.$form.querySelectorAll('[type="submit"]');

        // Forms can have multiple submit buttons, and its easier to assign the currently clicked one
        // than tracking it through the submit handler.
        $submitBtns.forEach(($submitBtn) => {
            $submitBtn.addEventListener('click', (e) => {
                this.$submitBtn = e.target;

                // Store for later if we're using text spinner
                this.originalButtonText = e.target.textContent.trim();
            });
        });

        this.$form.addEventListener('onBeforeFormieSubmit', this.onBeforeSubmit.bind(this));
        this.$form.addEventListener('onFormieValidate', this.onValidate.bind(this));
        this.$form.addEventListener('onFormieSubmit', this.onSubmit.bind(this));
        this.$form.addEventListener('onFormieSubmitError', this.onSubmitError.bind(this));
    }

    onBeforeSubmit(e) {
        this.beforeSubmit();

        // Save for later to trigger real submit
        this.submitHandler = e.detail.submitHandler;
    }

    onValidate(e) {
        // Bypass validation and custom event handling if going back
        if (!this.$form.goToPage && !this.validate()) {
            this.onFormError();

            // Set a flag on the event, so other listeners can potentially do something
            e.detail.invalid = true;

            e.preventDefault();
        }
    }

    onSubmit(e) {
        // Stop base behaviour of just submitting the form
        e.preventDefault();

        // Either staight submit, or use Ajax
        if (this.settings.submitMethod === 'ajax') {
            this.ajaxSubmit();
        } else {
            // Before a server-side submit, refresh the saved hash immediately. Otherwise, the native submit
            // handler - which technically unloads the page - will trigger the changed alert.
            this.savedFormHash = this.hashForm();

            this.$form.submit();
        }
    }

    onSubmitError(e) {
        this.onFormError();
    }

    addFormUnloadEventListener() {
        window.addEventListener('beforeunload', (e) => {
            if (this.savedFormHash !== this.hashForm()) {
                e.returnValue = t('Are you sure you want to leave?');
            }
        });
    }

    hashForm() {
        var hash = {};

        var formData = new FormData(this.$form);
        var excludedItems = ['g-recaptcha-response'];

        formData.forEach((value, key) => {
            if (!excludedItems.includes(key)) {
                hash[key] = value;
            }
        });

        return JSON.stringify(hash);
    }

    validate() {
        if (!this.validationOnSubmit) {
            return true;
        }

        var $fieldset = this.$form;

        if (this.$currentPage) {
            $fieldset = this.$currentPage;
        }

        var invalidFields = this.validator.validateAll($fieldset);

        // If there are errors, focus on the first one
        if (invalidFields.length > 0) {
            invalidFields[0].focus();
        }

        return !invalidFields.length;
    }

    hideSuccess() {
        var $successMessage = this.$form.parentNode.querySelector('.fui-alert-success');

        if ($successMessage && this.settings.submitActionMessageTimeout) {
            var timeout = parseInt(this.settings.submitActionMessageTimeout, 10) * 1000;

            setTimeout(() => {
                $successMessage.remove();
            }, timeout);
        }
    }

    addLoading() {
        if (this.$submitBtn) {
            // Always disable the button
            this.$submitBtn.setAttribute('disabled', true);

            if (this.settings.loadingIndicator === 'spinner') {
                this.$submitBtn.classList.add('fui-loading');
            }

            if (this.settings.loadingIndicator === 'text') {
                this.$submitBtn.textContent = this.settings.loadingIndicatorText;
            }
        }
    }

    removeLoading() {
        if (this.$submitBtn) {
            // Always enable the button
            this.$submitBtn.removeAttribute('disabled');

            if (this.settings.loadingIndicator === 'spinner') {
                this.$submitBtn.classList.remove('fui-loading');
            }

            if (this.settings.loadingIndicator === 'text') {
                this.$submitBtn.textContent = this.originalButtonText;
            }
        }
    }

    onFormError(errorMessage) {
        if (errorMessage) {
            this.showFormAlert(errorMessage, 'error');
        } else {
            this.showFormAlert(this.settings.errorMessage, 'error');
        }

        this.removeLoading();
    }

    showFormAlert(text, type) {
        var $alert = this.$form.parentNode.querySelector('.fui-alert');

        // Strip <p> tags
        text = text.replace(/<p[^>]*>/g, '').replace(/<\/p>/g, '');

        if ($alert) {
            // We have to cater for HTML entities - quick-n-dirty
            if ($alert.innerHTML !== this.decodeHtml(text)) {
                $alert.innerHTML = $alert.innerHTML + '<br>' + text;
            }
        } else {
            $alert = document.createElement('div');
            $alert.className = 'fui-alert fui-alert-' + type;
            $alert.setAttribute('role' , 'alert');
            $alert.innerHTML = text;

            this.$form.parentNode.insertBefore($alert, this.$form);
        }
    }

    decodeHtml(html) {
        var txt = document.createElement('textarea');
        txt.innerHTML = html;
        return txt.value;
    }

    removeFormAlert() {
        var $alert = this.$form.parentNode.querySelector('.fui-alert');

        if ($alert) {
            $alert.remove();
        }
    }

    removeBackInput() {
        // Remove the hidden back input sent in any previous step
        var $backButtonInput = this.$form.querySelector('[name="goToPageId"][type="hidden"]');

        if ($backButtonInput) {
            $backButtonInput.remove();
        }

        // Reset the chosen page
        this.$form.goToPage = null;
    }

    beforeSubmit() {
        // Remove all validation errors
        Array.prototype.filter.call(this.$form.querySelectorAll('input, select, textarea'), (($field) => {
            this.validator.removeError($field);
        }));

        this.removeFormAlert();
        this.addLoading();
    }

    ajaxSubmit() {
        const formData = new FormData(this.$form);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');

        this.beforeSubmit();

        xhr.onreadystatechange = () => {
            if (xhr.readyState !== 4) {
                return;
            }

            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const response = JSON.parse(xhr.responseText);

                    if (response.errors) {
                        this.onAjaxError(response.errors, response.errorMessage);
                    } else {
                        this.onAjaxSuccess(response);
                    }
                } catch(e) {
                    this.onAjaxError(t('Unable to parse response `{e}`.', { e }));
                }
            } else {
                this.onAjaxError(xhr.status + ': ' + xhr.statusText);
            }
        };

        xhr.send(formData);
    }

    afterAjaxSubmit(response) {
        // This will be called regardless of success or error
        this.removeBackInput();

        this.updateSubmissionInput(response);
    }

    onAjaxError(response, errorMessage = '') {
        this.onFormError(errorMessage);

        this.afterAjaxSubmit(response);

        if (typeof response === 'string') {
            this.showFormAlert(response, 'error');
        }

        if (typeof response === 'object') {
            Object.keys(response).forEach((handle, index) => {
                const [ error ] = response[handle];
                const $field = document.querySelector(`[name="fields[${handle}]"]`);

                if ($field) {
                    this.validator.showError($field, { customMessage: error });

                    // Focus on the first error
                    if (index === 0) {
                        $field.focus();
                    }
                }
            });
        }
    }

    onAjaxSuccess(data) {
        // Fire the event, because we've overridden the handler
        this.submitHandler.formAfterSubmit();

        this.afterAjaxSubmit(data);

        // Reset the form hash, as all has been saved
        this.savedFormHash = this.hashForm();

        // Check if we need to proceed to the next page
        if (data.nextPageId) {
            this.removeLoading();

            this.togglePage(data);

            return;
        }

        // If we're redirecting away, do it immediately for nicer UX
        if (this.settings.submitAction === 'entry') {
            if (this.settings.submitActionTab === 'same-tab') {
                window.location.href = this.settings.redirectEntry;
            } else if (this.settings.submitActionTab === 'new-tab') {
                window.open(this.settings.redirectEntry, '_blank');
            }

            return;
        }

        if (this.settings.submitAction === 'url') {
            if (this.settings.submitActionTab === 'same-tab') {
                window.location.href = this.settings.submitActionUrl;
            } else if (this.settings.submitActionTab === 'new-tab') {
                window.open(this.settings.submitActionUrl, '_blank');
            }

            return;
        }

        // Delay this a little, in case we're redirecting away - better UX to just keep it loading
        this.removeLoading();

        // Remove the back button - not great UX to go back to a finished form
        // Remember, its the button and the hidden input
        var $backButtonInputs = this.$form.querySelectorAll('[name="goToPageId"]');

        $backButtonInputs.forEach($backButtonInput => {
            $backButtonInput.remove();
        });

        // Also remove the submit button for a multi-page form. Its bad UX to show you can
        // submit a multi-page form again, at the end. In fact, we'll probably get errors -
        // but this is totally fine for a single-page ajax form.
        if (data.totalPages > 1) {
            if (this.$submitBtn) {
                this.$submitBtn.remove();
            }
        }

        if (this.settings.submitAction === 'message') {
            this.showFormAlert(this.settings.submitActionMessage, 'success');

            // Check if we need to remove the success message
            this.hideSuccess();

            if (this.settings.submitActionFormHide) {
                this.$form.style.display = 'none';
            }
        }

        // Reset values regardless, for the moment
        this.$form.reset();

        // Reset the form hash, as all has been saved
        this.savedFormHash = this.hashForm();
    }

    updateSubmissionInput(data) {
        if (!data.submissionId || !data.nextPageId) {
            return;
        }

        // Add the hidden submission input, if it doesn't exist
        var $input = this.$form.querySelector('[name="submissionId"][type="hidden"]');

        if (!$input) {
            $input = document.createElement('input');
            $input.setAttribute('type', 'hidden');
            $input.setAttribute('name', 'submissionId');
            this.$form.appendChild($input);
        }

        $input.setAttribute('value', data.submissionId);
    }

    togglePage(data) {
        // Hide all pages
        var $allPages = this.$form.querySelectorAll('.fui-page');

        $allPages.forEach($page => {
            // Show the current page
            if ($page.id === 'formie-p-' + data.nextPageId) {
                $page.classList.remove('fui-hidden');
            } else {
                $page.classList.add('fui-hidden');
            }
        });

        // Update tabs and progress bar if we're using them
        var $progress = this.$form.querySelector('.fui-progress-bar');

        if ($progress) {
            var pageIndex = data.nextPageIndex + 1;
            var progress = Math.round((pageIndex / data.totalPages) * 100);

            $progress.style.width = progress + '%';
            $progress.setAttribute('aria-valuenow', progress);
            $progress.textContent = progress + '%';
        }

        var $tabs = this.$form.querySelectorAll('.fui-tab');

        $tabs.forEach($tab => {
            // Show the current page
            if ($tab.id === 'fui-tab-' + data.nextPageId) {
                $tab.classList.add('fui-tab-active');
            } else {
                $tab.classList.remove('fui-tab-active');
            }
        });

        // Update the current page
        this.setCurrentPage(data.nextPageId);
    }

    setCurrentPage(pageId) {
        this.currentPageId = `#formie-p-${pageId}`;
        this.$currentPage = document.querySelector(this.currentPageId);
    }
}

window.FormieForm = FormieForm;

