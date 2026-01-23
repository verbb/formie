import {
    t, getAjaxClient, addClasses, removeClasses,
} from './utils/utils';
import FormieValidator from './validator/validator';

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
        this.tabCompleteClass = this.form.getClasses('tabComplete');
        this.errorMessageClass = this.form.getClasses('errorMessage');
        this.successMessageClass = this.form.getClasses('successMessage');
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

        // Emit a custom event to let scripts know the Formie class is ready
        this.$form.dispatchEvent(new CustomEvent('onFormieThemeReady', {
            bubbles: true,
            detail: {
                theme: this,
                addValidator: this.addValidator.bind(this),
            },
        }));
    }

    initValidator() {
        const validatorSettings = {
            live: this.validationOnFocus,

            fieldContainerErrorClass: this.form.getClasses('fieldContainerError'),
            inputErrorClass: this.form.getClasses('fieldInputError'),
            messagesClass: this.form.getClasses('fieldErrors'),
            messageClass: this.form.getClasses('fieldError'),
        };

        this.validator = new FormieValidator(this.$form, validatorSettings);

        // Allow other modules to modify our validator. Use `triggerEvent` to support calling `registerEvent` as different
        // times during the app, as some fields or custom validators can be registered after this call.
        this.form.triggerEvent('registerFormieValidation', {
            validator: this.validator,
        });
    }

    addValidator() {
        this.form.registerEvent('registerFormieValidation', (e) => {
            e.validator.addValidator(...arguments);
        });
    }

    addSubmitEventListener() {
        const $submitBtns = this.$form.querySelectorAll('[type="submit"]');

        // Forms can have multiple submit buttons, and its easier to assign the currently clicked one
        // than tracking it through the submit handler.
        $submitBtns.forEach(($submitBtn) => {
            this.form.addEventListener($submitBtn, 'click', (e) => {
                this.$submitBtn = e.target;

                // Store for later if we're using text spinner
                this.originalButtonText = this.$submitBtn.textContent.trim();

                const submitAction = this.$submitBtn.getAttribute('data-submit-action') || 'submit';

                // Each submit button can do different things, to store that
                this.updateSubmitAction(submitAction);
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

                this.setPage({
                    pageIndex,
                    pageId,
                    action: e.target.getAttribute('href'),
                });
            });
        });
    }

    setPage(options) {
        this.togglePage({
            nextPageIndex: options.pageIndex,
            nextPageId: options.pageId,
            totalPages: this.settings.pages.length,
        });

        // Ensure we still update the current page server-side
        const xhr = getAjaxClient(this.$form, 'GET', options.action, true);
        xhr.send();
    }

    hashForm() {
        const hash = {};
        const formData = new FormData(this.$form);

        // Exlcude some params from the hash, that are programatically changed
        // TODO, allow some form of registration for captchas.
        const excludedItems = [
            'g-recaptcha-response',
            'h-captcha-response',
            'CRAFT_CSRF_TOKEN',
            '__JSCHK',
            '__DUP',
            'beesknees',
            'cf-turnstile-response',
            'frc-captcha-solution',
            'snaptcha',
            'submitAction',
        ];

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

        // Validate just the current page (if there is one) or the entire form
        const errors = this.validator.submit($fieldset);

        // // If there are errors, focus on the first one
        if (errors.length > 0 && focus) {
            errors[0].input.focus();
        }

        // Remove any global errors if none - just in case
        if (errors.length === 0) {
            this.removeFormAlert();
        }

        // Raise a client-side event for failed errors. Set a tiny delay to ensure the event fires correctly-timed
        if (errors.length) {
            setTimeout(() => {
                this.form.validateError({ errors });
            }, 10);
        }

        return !errors.length;
    }

    hideSuccess() {
        const $successMessage = this.$form.parentNode.querySelector('[data-fui-alert-success]');

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

            // Update the attribute, to not be fully reliant on theme
            this.$submitBtn.setAttribute('data-loading', true);

            if (this.settings.loadingIndicator === 'spinner') {
                addClasses(this.$submitBtn, this.loadingClass);
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

            // Update the attribute, to not be fully reliant on theme
            this.$submitBtn.removeAttribute('data-loading');

            if (this.settings.loadingIndicator === 'spinner') {
                removeClasses(this.$submitBtn, this.loadingClass);
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
        let $alert = this.$form.parentNode.querySelector('[data-fui-alert]');

        if ($alert) {
            // We have to cater for HTML entities - quick-n-dirty
            if ($alert.innerHTML !== this.decodeHtml(text)) {
                $alert.innerHTML = `${$alert.innerHTML}<br>${text}`;
            }
        } else {
            $alert = document.createElement('div');
            $alert.innerHTML = text;

            // Set attributes on the alert according to theme config
            this.form.applyThemeConfig($alert, 'alert');

            // For error notices, we have potential special handling on position
            if (type == 'error') {
                this.form.applyThemeConfig($alert, 'alertError');

                if (this.settings.errorMessagePosition == 'bottom-form') {
                    this.$submitBtn.parentNode.parentNode.insertBefore($alert, this.$submitBtn.parentNode);
                } else if (this.settings.errorMessagePosition == 'top-form') {
                    this.$form.parentNode.insertBefore($alert, this.$form);
                }
            } else {
                this.form.applyThemeConfig($alert, 'alertSuccess');

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
                addClasses($tab.parentNode, this.tabErrorClass);
            }
        });
    }

    decodeHtml(html) {
        const txt = document.createElement('textarea');
        txt.innerHTML = html;
        return txt.value;
    }

    removeFormAlert() {
        const $alert = this.$form.parentNode.querySelector('[data-fui-alert]');

        if ($alert) {
            $alert.remove();
        }
    }

    removeTabErrors() {
        const $tabs = this.$form.parentNode.querySelectorAll('[data-fui-page-tab]');

        $tabs.forEach(($tab) => {
            removeClasses($tab, this.tabErrorClass);
        });
    }

    beforeSubmit() {
        this.validator?.removeAllErrors();

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

        const xhr = getAjaxClient(this.$form, method ? method : 'POST', action ? action : window.location.href, true);
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

        // Check if there's any events in the `submitData` response back, and fire them
        if (data.submitData && Array.isArray(data.submitData) && data.submitData.length) {
            // Get just event data from the submitData
            const events = data.submitData.filter((item) => {
                return 'event' in item;
            });

            if (events.length) {
                // An error message may be shown in some cases (for 3D secure) so remove the form-global level error notice.
                this.removeFormAlert();

                events.forEach((eventData) => {
                    this.$form.dispatchEvent(new CustomEvent(eventData.event, {
                        bubbles: true,
                        detail: {
                            data: eventData.data,
                        },
                    }));
                });
            }
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
        this.submitHandler.formSubmitError(data);

        // Fire cleanup methods after _any_ ajax call
        this.afterAjaxSubmit(data);

        // Show server-side errors for each field
        Object.keys(errors).forEach((handle, index) => {
            const [error] = errors[handle];
            let selector = handle.split('.');
            selector = selector.join('][');

            let $field = this.$form.querySelector(`[name="fields[${selector}]"]`);

            // Check for multiple fields
            if (!$field) {
                $field = this.$form.querySelector(`[name="fields[${selector}][]"]`);
            }

            // Handle Repeater/Groups - a little more complicated to translate `group[0].field.handle`
            if (!$field && handle.includes('[')) {
                const blockIndex = handle.match(/\[(.*?)\]/)[1] || null;
                let regexString = `fields[${handle.replace(/\./g, '][').replace(']]', ']').replace(/\[.*?\]/, '][rows][.*][fields]')}]`;
                regexString = regexString.replace(/\[/g, '\\[').replace(/\]/g, '\\]');

                const $targets = this.querySelectorAllRegex(new RegExp(regexString), 'name');

                if ($targets.length && $targets[blockIndex]) {
                    $field = $targets[blockIndex];
                }
            }

            // Handle Date fields, which probably need better handling. Only for Ajax-based forms though.
            if (!$field) {
                $field = this.$form.querySelector(`[name="fields[${selector}][date]"]`);

                if (!$field) {
                    $field = this.$form.querySelector(`[name="fields[${selector}][time]"]`);
                }
            }

            // Handle Question Captcha
            if (!$field && selector.includes('formieCaptchaQuestion')) {
                selector = selector.replace('formieCaptchaQuestion', '[formieCaptchaQuestion]');

                $field = this.$form.querySelector(`[name="fields${selector}"]`);
            }

            if ($field) {
                if (error) {
                    this.validator?.showError($field, 'server', error);
                }

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

        // If people have provided a redirect behaviour to handle their own redirecting
        if (data.redirectCallback) {
            data.redirectCallback();

            return;
        }

        // If we're redirecting away, do it immediately for nicer UX
        if (data.redirectUrl) {
            if (this.settings.submitActionTab === 'new-tab') {
                // Reset values if in a new tab. No need when in the same tab.
                this.resetForm();

                // Allow people to modify the target from `window` with `redirectTarget`
                data.redirectTarget.open(data.redirectUrl, '_blank');
            } else {
                data.redirectTarget.location.href = data.redirectUrl;
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
        this.resetForm();

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

    resetForm() {
        // `$form.reset()` will do most, but programatically setting `checked` for checkboxes won't be cleared
        this.$form.reset();

        this.$form.querySelectorAll('[type="checkbox"]').forEach(($checkbox) => {
            $checkbox.removeAttribute('checked');
        });
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
        const $progressValue = this.$form.querySelector('[data-fui-progress-value]');

        if ($progress && data.nextPageIndex >= 0) {
            const pageIndex = parseInt(data.nextPageIndex, 10) + 1;
            const progress = Math.round((pageIndex / data.totalPages) * 100);

            $progress.style.width = `${progress}%`;
            $progress.setAttribute('aria-valuenow', progress);

            $progressValue.setAttribute('data-fui-progress-value', progress);
            $progressValue.textContent = `${progress}%`;
        }

        const $tabs = this.$form.querySelectorAll('[data-fui-page-tab]');

        if (data.nextPageId) {
            $tabs.forEach(($tab) => {
                // Show the current page
                if ($tab.id === `${this.tabClass}-${data.nextPageId}`) {
                    addClasses($tab, this.tabActiveClass);
                } else {
                    removeClasses($tab, this.tabActiveClass);
                }
            });

            let isComplete = true;

            $tabs.forEach(($tab) => {
                if ($tab.classList.contains(this.tabActiveClass)) {
                    isComplete = false;
                }

                if (isComplete) {
                    addClasses($tab, this.tabCompleteClass);
                } else {
                    removeClasses($tab, this.tabCompleteClass);
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

        // Update the input, if we're client-driven
        const $input = this.$form.querySelector('input[type="hidden"][name="pageIndex"]');

        if ($input) {
            $input.value = this.getCurrentPageIndex();
        }
    }

    getCurrentPage() {
        return this.settings.pages.find((page) => {
            return page.id == this.settings.currentPageId;
        });
    }

    getCurrentPageIndex() {
        const currentPage = this.getCurrentPage();

        if (currentPage) {
            return this.settings.pages.indexOf(currentPage);
        }

        return 0;
    }

    getPageId(pageId) {
        return `${this.config.formHashId}-p-${pageId}`;
    }

    scrollToForm() {
        this.$form.parentNode.scrollIntoView({
            behavior: 'smooth',
            block: 'start', // Align to the top of the viewport
        });
    }

    triggerJsEvents() {
        const currentPage = this.getCurrentPage();

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

    querySelectorAllRegex(regex, attributeToSearch) {
        const output = [];

        for (const element of this.$form.querySelectorAll(`[${attributeToSearch}]`)) {
            if (regex.test(element.getAttribute(attributeToSearch))) {
                output.push(element);
            }
        }

        return output;
    }
}
