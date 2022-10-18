import { Bouncer } from './utils/bouncer';

export class FormieFormTheme {
    constructor($form, config = {}) {
        this.$form = $form;
        this.config = config;
        this.settings = config.settings;
        this.validationOnSubmit = !!this.settings.validationOnSubmit;
        this.validationOnFocus = !!this.settings.validationOnFocus;

        this.setCurrentPage(this.settings.currentPageId);

        if (!this.$form) {
            return;
        }

        this.$form.formTheme = this;
        this.form = this.$form.form;

        // Setup classes according to theme config
        this.loadingClass = this.form.getClasses('loading');
        this.tabErrorClass = this.form.getClasses('tabError');
        this.tabActiveClass = this.form.getClasses('tabActive');
        this.errorMessageClass = this.form.getClasses('errorMessage');
        this.successMessageClass = this.form.getClasses('successMessage');
        this.alertClass = this.form.getClasses('alert');
        this.alertErrorClass = this.form.getClasses('alertError');
        this.alertSuccessClass = this.form.getClasses('alertSuccess');
        this.tabClass = this.form.getClasses('tab');

        this.initValidator();

        // Check if this is a success page and if we need to hide the notice
        // This is for non-ajax forms, where the page has reloaded
        this.hideSuccess();

        // Hijack the form's submit handler, in case we need to do something
        this.addSubmitEventListener();

        // Save the form's current state so we can tell if its changed later on
        this.updateFormHash();

        // Listen to form changes if the user tries to reload
        if (this.settings.enableUnloadWarning) {
            this.addFormUnloadEventListener();
        }

        // Listen to tabs being clicked for ajax-enabled forms
        if (this.settings.submitMethod === 'ajax') {
            this.formTabEventListener();
        }
    }

    initValidator() {
        // Kick off validation - use this even if disabling client-side validation
        // so we can use a nice API handle server-side errprs
        const validatorSettings = {
            fieldClass: 'fui-error',
            errorClass: this.form.getClasses('fieldError'),
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

        // Give a small amount of time for other JS scripts to register validations. These are lazy-loaded.
        // Maybe re-think this so we don't have to deal with event listener registration before/after dispatch?
        setTimeout(() => {
            this.$form.dispatchEvent(registerFormieValidation);

            this.validator = new Bouncer(this.$form, registerFormieValidation.detail.validatorSettings);
        }, 500);

        // After we clear any error, validate the fielset again. Mostly so we can remove global errors
        this.form.addEventListener(this.$form, 'bouncerRemoveError', (e) => {
            // Prevent an infinite loop (check behaviour with an Agree field)
            // https://github.com/verbb/formie/issues/905
            if (!this.submitDebounce) {
                this.validate(false);
            }
        });

        // Override error messages defined in DOM - Bouncer only uses these as a last resort
        // In future updates, we can probably remove this
        this.form.addEventListener(this.$form, 'bouncerShowError', (e) => {
            let message = null;
            const $field = e.target;
            const $fieldContainer = $field.closest('[data-field-type]');

            // Get the error message as defined on the input element. Use the parent to find the element
            // just to cater for some edge-cases where there might be multiple inputs (Datepicker).
            const $message = $field.parentNode.querySelector('[data-fui-message]');

            if ($message) {
                message = $message.getAttribute('data-fui-message');
            }

            // If there's a server error, it takes priority.
            if (e.detail && e.detail.errors && e.detail.errors.serverMessage) {
                message = e.detail.errors.serverMessage;
            }

            // Check if we need to move the error out of the .fui-input-container node.
            // Only the input itself should be in here.
            const $errorToMove = $field.parentNode.querySelector(`.${this.errorMessageClass}`);

            if ($errorToMove && $errorToMove.parentNode.parentNode) {
                $errorToMove.parentNode.parentNode.appendChild($errorToMove);
            }

            // The error has been moved, find it again
            if ($fieldContainer) {
                const $error = $fieldContainer.querySelector(`.${this.errorMessageClass}`);

                if ($error && message) {
                    $error.textContent = message;
                }
            }
        }, false);
    }

    addSubmitEventListener() {
        const $submitBtns = this.$form.querySelectorAll('[type="submit"]');

        // Forms can have multiple submit buttons, and its easier to assign the currently clicked one
        // than tracking it through the submit handler.
        $submitBtns.forEach(($submitBtn) => {
            this.form.addEventListener($submitBtn, 'click', (e) => {
                this.$submitBtn = e.target;

                // Store for later if we're using text spinner
                this.originalButtonText = e.target.textContent.trim();

                // Each submit button can do different things, to store that
                this.updateSubmitAction(this.$submitBtn.getAttribute('data-submit-action'));
            });
        });

        this.form.addEventListener(this.$form, 'onBeforeFormieSubmit', this.onBeforeSubmit.bind(this));
        this.form.addEventListener(this.$form, 'onFormieValidate', this.onValidate.bind(this));
        this.form.addEventListener(this.$form, 'onFormieSubmit', this.onSubmit.bind(this));
        this.form.addEventListener(this.$form, 'onFormieSubmitError', this.onSubmitError.bind(this));
    }

    onBeforeSubmit(e) {
        this.beforeSubmit();

        // Save for later to trigger real submit
        this.submitHandler = e.detail.submitHandler;
    }

    onValidate(e) {
        // If invalid, we only want to stop if we're submitting.
        if (!this.validate()) {
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
            // But trigger an alert if we're going back, and back-submission data isn't set
            if (!this.settings.enableBackSubmission && this.form.submitAction === 'back') {
                // Don't reset the hash, trigger a warning if content has changed, because we're not submitting
            } else {
                this.updateFormHash();
            }

            // Triger any JS events for this page, only if submitting (not going back/saving)
            if (this.form.submitAction === 'submit') {
                this.triggerJsEvents();
            }

            this.$form.submit();
        }
    }

    onSubmitError(e) {
        this.onFormError();
    }

    addFormUnloadEventListener() {
        this.form.addEventListener(window, 'beforeunload', (e) => {
            if (this.savedFormHash !== this.hashForm()) {
                e.preventDefault();

                return e.returnValue = t('Are you sure you want to leave?');
            }
        });
    }

    formTabEventListener() {
        const $tabs = this.$form.querySelectorAll('[data-fui-page-tab-anchor]');

        $tabs.forEach(($tab) => {
            this.form.addEventListener($tab, 'click', (e) => {
                e.preventDefault();

                const pageIndex = e.target.getAttribute('data-fui-page-index');
                const pageId = e.target.getAttribute('data-fui-page-id');

                this.togglePage({
                    nextPageIndex: pageIndex,
                    nextPageId: pageId,
                    totalPages: this.settings.pages.length,
                });

                // Ensure we still update the current page server-side
                const xhr = new XMLHttpRequest();
                xhr.open('GET', e.target.getAttribute('href'), true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.setRequestHeader('Cache-Control', 'no-cache');
                xhr.send();
            });
        });
    }

    hashForm() {
        const hash = {};
        const formData = new FormData(this.$form);

        // Exlcude some params from the hash, that are programatically changed
        const excludedItems = ['g-recaptcha-response', 'CRAFT_CSRF_TOKEN', '__JSCHK', 'submitAction'];

        for (const pair of formData.entries()) {
            const isExcluded = excludedItems.filter((item) => { return pair[0].startsWith(item); });

            if (!isExcluded.length) {
                // eslint-disable-next-line
                hash[pair[0]] = pair[1];
            }
        }

        return JSON.stringify(hash);
    }

    updateFormHash() {
        this.savedFormHash = this.hashForm();
    }

    validate(focus = true) {
        if (!this.validationOnSubmit) {
            return true;
        }

        // Only validate on submit actions
        if (this.form.submitAction !== 'submit') {
            return true;
        }

        let $fieldset = this.$form;

        if (this.$currentPage) {
            $fieldset = this.$currentPage;
        }

        const invalidFields = this.validator.validateAll($fieldset);

        // If there are errors, focus on the first one
        if (invalidFields.length > 0 && focus) {
            invalidFields[0].focus();
        }

        // Remove any global errors if none - just in case
        if (invalidFields.length === 0) {
            this.removeFormAlert();
        }

        // Set the debounce after a little bit, to prevent an infinite loop, as this method
        // is called on `bouncerRemoveError`.
        this.submitDebounce = true;

        setTimeout(() => {
            this.submitDebounce = false;
        }, 500);

        return !invalidFields.length;
    }

    hideSuccess() {
        const $successMessage = this.$form.parentNode.querySelector(`.${this.successMessageClass}`);

        if ($successMessage && this.settings.submitActionMessageTimeout) {
            const timeout = parseInt(this.settings.submitActionMessageTimeout, 10) * 1000;

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
                this.$submitBtn.classList.add(this.loadingClass);
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
                this.$submitBtn.classList.remove(this.loadingClass);
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
        let $alert = this.$form.parentNode.querySelector('[role="alert"]');

        // Strip <p> tags
        text = text.replace(/<p[^>]*>/g, '').replace(/<\/p>/g, '');

        if ($alert) {
            // We have to cater for HTML entities - quick-n-dirty
            if ($alert.innerHTML !== this.decodeHtml(text)) {
                $alert.innerHTML = `${$alert.innerHTML}<br>${text}`;
            }
        } else {
            $alert = document.createElement('div');
            $alert.className = `${this.alertClass}`;
            $alert.setAttribute('role', 'alert');
            $alert.innerHTML = text;

            // For error notices, we have potential special handling on position
            if (type == 'error') {
                $alert.className += ` ${this.alertErrorClass} ${this.alertClass}-${this.settings.errorMessagePosition}`;

                if (this.settings.errorMessagePosition == 'bottom-form') {
                    this.$submitBtn.parentNode.parentNode.insertBefore($alert, this.$submitBtn.parentNode);
                } else if (this.settings.errorMessagePosition == 'top-form') {
                    this.$form.parentNode.insertBefore($alert, this.$form);
                }
            } else {
                $alert.className += ` ${this.alertSuccessClass} ${this.alertClass}-${this.settings.submitActionMessagePosition}`;

                if (this.settings.submitActionMessagePosition == 'bottom-form') {
                    // An even further special case when hiding the form!
                    if (this.settings.submitActionFormHide) {
                        this.$form.parentNode.insertBefore($alert, this.$form);
                    } else if (this.$submitBtn.parentNode) {
                        // Check if there's a submit button still. Might've been removed for multi-page, ajax.
                        this.$submitBtn.parentNode.parentNode.insertBefore($alert, this.$submitBtn.parentNode);
                    } else {
                        this.$form.parentNode.insertBefore($alert, this.$form.nextSibling);
                    }
                } else if (this.settings.submitActionMessagePosition == 'top-form') {
                    this.$form.parentNode.insertBefore($alert, this.$form);
                }
            }
        }
    }

    showTabErrors(errors) {
        Object.keys(errors).forEach((pageId, index) => {
            const $tab = this.$form.parentNode.querySelector(`[data-fui-page-id="${pageId}"]`);

            if ($tab) {
                $tab.parentNode.classList.add(this.tabErrorClass);
            }
        });
    }

    decodeHtml(html) {
        const txt = document.createElement('textarea');
        txt.innerHTML = html;
        return txt.value;
    }

    removeFormAlert() {
        const $alert = this.$form.parentNode.querySelector(`.${this.alertClass}`);

        if ($alert) {
            $alert.remove();
        }
    }

    removeTabErrors() {
        const $tabs = this.$form.parentNode.querySelectorAll('[data-fui-page-tab]');

        $tabs.forEach(($tab) => {
            $tab.classList.remove(this.tabErrorClass);
        });
    }

    beforeSubmit() {
        // Remove all validation errors
        Array.prototype.filter.call(this.$form.querySelectorAll('input, select, textarea'), (($field) => {
            this.validator.removeError($field);
        }));

        this.removeFormAlert();
        this.removeTabErrors();

        // Don't set a loading if we're going back and the unload warning appears, because there's no way to re-enable
        // the button after the user cancels the unload event
        if (!this.settings.enableBackSubmission && this.form.submitAction === 'back') {
            // Do nothing
        } else {
            this.addLoading();
        }
    }

    ajaxSubmit() {
        const formData = new FormData(this.$form);
        const method = this.$form.getAttribute('method');
        const action = this.$form.getAttribute('action');

        const xhr = new XMLHttpRequest();
        xhr.open(method ? method : 'POST', action ? action : window.location.href, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('Cache-Control', 'no-cache');
        xhr.timeout = (this.settings.ajaxTimeout || 10) * 1000;

        this.beforeSubmit();

        xhr.ontimeout = () => {
            this.onAjaxError(t('The request timed out.'));
        };

        xhr.onerror = (e) => {
            this.onAjaxError(t('The request encountered a network error. Please try again.'));
        };

        xhr.onload = () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const response = JSON.parse(xhr.responseText);

                    if (response.errors) {
                        this.onAjaxError(response.errorMessage, response);
                    } else {
                        this.onAjaxSuccess(response);
                    }
                } catch (e) {
                    this.onAjaxError(t('Unable to parse response `{e}`.', { e }));
                }
            } else {
                this.onAjaxError(`${xhr.status}: ${xhr.statusText}`);
            }
        };

        xhr.send(formData);
    }

    afterAjaxSubmit(data) {
        // Reset the submit action, immediately, whether fail or success
        this.updateSubmitAction('submit');

        this.updateSubmissionInput(data);

        // Check if there's any events in the response back, and fire them
        if (data.events && Array.isArray(data.events)) {
            data.events.forEach((eventData) => {
                this.$form.dispatchEvent(new CustomEvent(eventData.event, {
                    bubbles: true,
                    detail: {
                        data: eventData.data,
                    },
                }));
            });
        }
    }

    onAjaxError(errorMessage, data = {}) {
        const errors = data.errors || {};
        const pageFieldErrors = data.pageFieldErrors || {};

        // Show an error message at the top of the form
        this.onFormError(errorMessage);

        // Update the page tabs (if any) to show error state
        this.showTabErrors(pageFieldErrors);

        // Fire a fail event
        this.submitHandler.formSubmitError();

        // Fire cleanup methods after _any_ ajax call
        this.afterAjaxSubmit(data);

        // Show server-side errors for each field
        Object.keys(errors).forEach((handle, index) => {
            const [error] = errors[handle];
            let $field = this.$form.querySelector(`[name="fields[${handle}]"]`);

            // Check for multiple fields
            if (!$field) {
                $field = this.$form.querySelector(`[name="fields[${handle}][]"]`);
            }

            if ($field) {
                this.validator.showError($field, { serverMessage: error });

                // Focus on the first error
                if (index === 0) {
                    $field.focus();
                }
            }
        });

        // Go to the first page with an error, for good UX
        this.togglePage(data, false);
    }

    onAjaxSuccess(data) {
        // Fire the event, because we've overridden the handler
        this.submitHandler.formAfterSubmit(data);

        // Fire cleanup methods after _any_ ajax call
        this.afterAjaxSubmit(data);

        // Reset the form hash, as all has been saved
        this.updateFormHash();

        // Triger any JS events for this page, right away before navigating away
        if (this.form.submitAction === 'submit') {
            this.triggerJsEvents();
        }

        // Check if we need to proceed to the next page
        if (data.nextPageId) {
            this.removeLoading();

            this.togglePage(data);

            return;
        }

        // If we're redirecting away, do it immediately for nicer UX
        if (data.redirectUrl) {
            if (this.settings.submitActionTab === 'new-tab') {
                // Reset values if in a new tab. No need when in the same tab.
                this.$form.reset();

                window.open(data.redirectUrl, '_blank');
            } else {
                window.location.href = data.redirectUrl;
            }

            return;
        }

        // Delay this a little, in case we're redirecting away - better UX to just keep it loading
        this.removeLoading();

        // For multi-page ajax forms, deal with them a little differently.
        if (data.totalPages > 1) {
            // If we have a success message at the top, go to the first page
            if (this.settings.submitActionMessagePosition == 'top-form') {
                this.togglePage({
                    nextPageIndex: 0,
                    nextPageId: this.settings.pages[0].id,
                    totalPages: this.settings.pages.length,
                });
            } else {
                // Otherwise, we want to hide the buttons because we have to stay on the last page
                // to show the success message at the bottom of the form. Otherwise, showing it on the
                // first page of an empty form is just plain weird UX.
                if (this.$submitBtn) {
                    this.$submitBtn.remove();
                }

                // Remove the back button - not great UX to go back to a finished form
                // Remember, its the button and the hidden input
                const $backButtonInputs = this.$form.querySelectorAll('[data-submit-action="back"]');

                $backButtonInputs.forEach(($backButtonInput) => {
                    $backButtonInput.remove();
                });
            }
        }

        if (this.settings.submitAction === 'message') {
            // Allow the submit action message to be sent from the response, or fallback to static.
            const submitActionMessage = data.submitActionMessage || this.settings.submitActionMessage;

            this.showFormAlert(submitActionMessage, 'success');

            // Check if we need to remove the success message
            this.hideSuccess();

            if (this.settings.submitActionFormHide) {
                this.$form.style.display = 'none';
            }

            // Smooth-scroll to the top of the form.
            if (this.settings.scrollToTop) {
                this.scrollToForm();
            }
        }

        // Reset values regardless, for the moment
        this.$form.reset();

        // Remove the submission ID input in case we want to go again
        this.removeHiddenInput('submissionId');

        // Reset the form hash, as all has been saved
        this.updateFormHash();
    }

    updateSubmitAction(action) {
        // All buttons should have a `[data-submit-action]` but just for backward-compatibility
        // assume when not present, we're submitting
        if (!action) {
            action = 'submit';
        }

        // Update the submit action on the form while we're at it. Store on the `$form`
        // for each of lookup on event hooks like captchas.
        this.form.submitAction = action;

        this.updateOrCreateHiddenInput('submitAction', action);
    }

    updateSubmissionInput(data) {
        if (!data.submissionId || !data.nextPageId) {
            return;
        }

        // Add the hidden submission input, if it doesn't exist
        this.updateOrCreateHiddenInput('submissionId', data.submissionId);
    }

    updateOrCreateHiddenInput(name, value) {
        let $input = this.$form.querySelector(`[name="${name}"][type="hidden"]`);

        if (!$input) {
            $input = document.createElement('input');
            $input.setAttribute('type', 'hidden');
            $input.setAttribute('name', name);
            this.$form.appendChild($input);
        }

        $input.setAttribute('value', value);
    }

    removeHiddenInput(name) {
        const $input = this.$form.querySelector(`[name="${name}"][type="hidden"]`);

        if ($input) {
            $input.parentNode.removeChild($input);
        }
    }

    togglePage(data, scrollToTop = true) {
        // Trigger an event when a page is toggled
        this.$form.dispatchEvent(new CustomEvent('onFormiePageToggle', {
            bubbles: true,
            detail: {
                data,
            },
        }));

        // Hide all pages
        const $allPages = this.$form.querySelectorAll('[data-fui-page]');

        if (data.nextPageId) {
            $allPages.forEach(($page) => {
                // Show the current page
                if ($page.id === `${this.getPageId(data.nextPageId)}`) {
                    $page.removeAttribute('data-fui-page-hidden');
                } else {
                    $page.setAttribute('data-fui-page-hidden', true);
                }
            });
        }

        // Update tabs and progress bar if we're using them
        const $progress = this.$form.querySelector('[data-fui-progress-bar]');

        if ($progress && data.nextPageIndex) {
            const pageIndex = parseInt(data.nextPageIndex, 10) + 1;
            const progress = Math.round((pageIndex / data.totalPages) * 100);

            $progress.style.width = `${progress}%`;
            $progress.setAttribute('aria-valuenow', progress);
            $progress.textContent = `${progress}%`;
        }

        const $tabs = this.$form.querySelectorAll('[data-fui-page-tab]');

        if (data.nextPageId) {
            $tabs.forEach(($tab) => {
                // Show the current page
                if ($tab.id === `${this.tabClass}-${data.nextPageId}`) {
                    $tab.classList.add(this.tabActiveClass);
                } else {
                    $tab.classList.remove(this.tabActiveClass);
                }
            });

            // Update the current page
            this.setCurrentPage(data.nextPageId);
        }

        // Smooth-scroll to the top of the form.
        if (this.settings.scrollToTop) {
            this.scrollToForm();
        }
    }

    setCurrentPage(pageId) {
        this.settings.currentPageId = pageId;
        this.$currentPage = this.$form.querySelector(`#${this.getPageId(pageId)}`);
    }

    getPageId(pageId) {
        return `${this.config.formHashId}-p-${pageId}`;
    }

    scrollToForm() {
        // Check for scroll-padding-top or `scroll-margin-top`
        const extraPadding = (document.documentElement.style['scroll-padding-top'] || '0px').replace('px', '');
        const extraMargin = (document.documentElement.style['scroll-margin-top'] || '0px').replace('px', '');

        // Because the form can be hidden, use the parent wrapper
        window.scrollTo({
            top: this.$form.parentNode.getBoundingClientRect().top + window.pageYOffset - 100 - extraPadding - extraMargin,
            behavior: 'smooth',
        });
    }

    triggerJsEvents() {
        const currentPage = this.settings.pages.find((page) => {
            return page.id == this.settings.currentPageId;
        });

        // Find any JS events for the current page and fire
        if (currentPage && currentPage.settings.enableJsEvents) {
            const payload = {};

            currentPage.settings.jsGtmEventOptions.forEach((option) => {
                payload[option.label] = option.value;
            });

            // Push to the datalayer
            window.dataLayer = window.dataLayer || [];
            window.dataLayer.push(payload);
        }

    }
}
