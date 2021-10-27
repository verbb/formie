import { eventKey } from '../utils/utils';

export class FormieSummary {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;
        this.$container = this.$field.querySelector('.fui-summary-blocks');
        this.fieldId = settings.fieldId;
        this.submissionId = null;

        var $submission = this.$form.querySelector('[name="submissionId"]');

        if ($submission) {
            this.submissionId = $submission.value;
        }

        // For ajax forms, we want to refresh the field when the page is toggled
        if (this.$container && this.submissionId && this.form.settings.submitMethod === 'ajax') {
            this.form.addEventListener(this.$form, 'onFormiePageToggle', this.onPageToggle.bind(this));
        }
    }

    onPageToggle(e) {
        // Wait a little for the page to update in the DOM
        setTimeout(() => {
            // Does this page contain a summary field? No need to fetch if we aren't seeing the field
            var summaryField = null;

            if (this.form.formTheme && this.form.formTheme.$currentPage) {
                summaryField = this.form.formTheme.$currentPage.querySelector('.fui-type-summary');
            }

            if (!summaryField) {
                return;
            }

            var $container = summaryField.querySelector('.fui-summary-blocks');

            $container.classList.add('fui-loading');

            const xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('Cache-Control', 'no-cache');

            xhr.onload = () => {
                $container.classList.remove('fui-loading');

                if (xhr.status >= 200 && xhr.status < 300) {
                    // Replace the HTML for the field
                    $container.parentNode.innerHTML = xhr.responseText;
                }
            };

            var params = {
                action: 'formie/fields/get-summary-html',
                submissionId: this.submissionId,
                fieldId: this.fieldId,
            };

            var formData = new FormData();

            for (var key in params) {
                formData.append(key, params[key]);
            }

            xhr.send(formData);
        }, 50);
    }
}

window.FormieSummary = FormieSummary;
