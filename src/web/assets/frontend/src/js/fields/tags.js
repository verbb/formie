import Tagify from '@yaireo/tagify';

export class FormieTags {
    constructor(settings = {}) {
        this.formId = '#formie-form-' + settings.formId;
        this.$form = document.querySelector(this.formId);

        if (this.$form) {
            this.initTags();
        }
    }

    initTags() {
        const $inputs = this.$form.querySelectorAll('[data-formie-tags]');

        $inputs.forEach(($input) => {
            $input.setAttribute('type', 'hidden');

            // Maximum compatibility.
            const tags = JSON.parse($input.getAttribute('data-formie-tags'));

            $input.tagify = new Tagify($input, {
                whitelist: tags,
            });
        });
    }
}

window.FormieTags = FormieTags;
