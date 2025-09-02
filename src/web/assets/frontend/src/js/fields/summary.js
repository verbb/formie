import {
    t, eventKey, getAjaxClient, addClasses, removeClasses,
} from '../utils/utils';

export class FormieSummary {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;
        this.fieldId = settings.fieldId;
        this.loadingClass = this.form.getClasses('loading');

        this.submissionId = null;
        this.debouncedFetch = this.debounce(this.fetchSummary.bind(this), 300);

        // For ajax forms, we want to refresh the field when this field is visible
        if (this.form.settings.submitMethod === 'ajax') {
            this.initVisibilityObserver();
        }
    }

    initVisibilityObserver() {
        const observer = new IntersectionObserver((entries) => {
            if (entries[0].intersectionRatio > 0) {
                this.onFieldVisible();
            }
        }, {
            root: this.$form,

            // Include a large margin to cater for when other elements might cover the placeholder
            // as `IntersectionObserver` won't deem the placeholder in view if under something else
            rootMargin: '50px',
        });

        observer.observe(this.$field);
    }

    onFieldVisible() {
        const $submission = this.$form.querySelector('[name="submissionId"]');

        if ($submission) {
            this.submissionId = $submission.value;
        }

        if (!this.submissionId) {
            console.error('Summary field: Unable to find `submissionId`');
            return;
        }

        // Emit an "onFieldVisible" event
        this.$field.dispatchEvent(new CustomEvent('onFieldVisible', {
            bubbles: true,
            detail: {
                summary: this,
            },
        }));

        this.debouncedFetch();
    }

    fetchSummary() {
        const $container = this.$field.querySelector('[data-summary-blocks]');

        if (!$container) {
            console.error('Summary field: Unable to find `container`');
            return;
        }

        if (this.loadingClass) {
            addClasses($container, this.loadingClass);
        }

        const xhr = getAjaxClient(this.$form, 'POST', window.location.href, true);

        xhr.onload = () => {
            if (this.loadingClass) {
                removeClasses($container, this.loadingClass);
            }

            if (xhr.status >= 200 && xhr.status < 300) {
                $container.parentNode.innerHTML = xhr.responseText;
            }

            // Emit an "onFetchSummary" event
            this.$field.dispatchEvent(new CustomEvent('onFetchSummary', {
                bubbles: true,
                detail: {
                    summary: this,
                    data: xhr,
                },
            }));
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
    }

    debounce(func, delay) {
        let timeoutId;

        return (...args) => {
            if (timeoutId) {
                clearTimeout(timeoutId);
            }

            timeoutId = setTimeout(() => {
                func.apply(this, args);
            }, delay);
        };
    }
}

window.FormieSummary = FormieSummary;
