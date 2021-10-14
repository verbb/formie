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

            const defaultOptions = {
                whitelist: tags,
            };

            // Emit an "beforeInit" event
            const beforeInitEvent = new CustomEvent('beforeInit', {
                bubbles: true,
                detail: {
                    tagField: this,
                    options: defaultOptions,
                },
            });

            $input.dispatchEvent(beforeInitEvent);

            const options = {
                ...defaultOptions,
                ...beforeInitEvent.detail.options,
            };

            $input.tagify = new Tagify($input, options);

            // Emit an "afterInit" event
            $input.dispatchEvent(new CustomEvent('afterInit', {
                bubbles: true,
                detail: {
                    tagField: this,
                    tagify: $input.tagify,
                    options,
                },
            }));
        });
    }
}

window.FormieTags = FormieTags;
