import { eventKey } from '../utils/utils';

export class FormieSummary {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;
        this.fieldId = settings.fieldId;
        this.loadingClass = this.form.getClasses('loading');

        // For ajax forms, we want to refresh the field when the page is toggled
        if (this.form.settings.submitMethod === 'ajax') {
            this.form.addEventListener(this.$form, 'onFormiePageToggle', this.onPageToggle.bind(this));
        }
    }

    onPageToggle(e) {
        // Wait a little for the page to update in the DOM
        setTimeout(() => {
            this.submissionId = null;

            const $submission = this.$form.querySelector('[name="submissionId"]');

            if ($submission) {
                this.submissionId = $submission.value;
            }

            if (!this.submissionId) {
                console.error('Summary field: Unable to find `submissionId`');

                return;
            }

            // Does this page contain a summary field? No need to fetch if we aren't seeing the field
            let $summaryField = null;

            if (this.form.formTheme && this.form.formTheme.$currentPage) {
                $summaryField = this.form.formTheme.$currentPage.querySelector('[data-field-type="summary"]');
            }

            if (!$summaryField) {
                console.log('Summary field: Unable to find `summaryField`');

                return;
            }

            const $container = $summaryField.querySelector('[data-summary-blocks]');

            if (!$container) {
                console.error('Summary field: Unable to find `container`');

                return;
            }

            $container.classList.add(this.loadingClass);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('Cache-Control', 'no-cache');

            xhr.onload = () => {
                $container.classList.remove(this.loadingClass);

                if (xhr.status >= 200 && xhr.status < 300) {
                    // Replace the HTML for the field
                    $container.parentNode.innerHTML = xhr.responseText;
                }
            };

            const params = {
                action: 'formie/fields/get-summary-html',
                submissionId: this.submissionId,
                fieldId: this.fieldId,
            };

            const formData = new FormData();

            for (const key in params) {
                formData.append(key, params[key]);
            }

            xhr.send(formData);
        }, 50);
    }
}

window.FormieSummary = FormieSummary;
