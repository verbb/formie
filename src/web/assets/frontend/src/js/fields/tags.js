import Tagify from '@yaireo/tagify';

export class FormieTags {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;

        this.initTags();
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
