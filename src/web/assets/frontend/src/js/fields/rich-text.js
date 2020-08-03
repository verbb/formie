import pell from 'pell';

class FormieRichText {
    constructor(settings = {}) {
        this.formId = '#formie-form-' + settings.formId;
        this.fieldId = '#fields-' + settings.fieldId;
        this.containerId = '#fields-' + settings.containerId;
        this.buttons = settings.buttons;

        this.$form = document.querySelector(this.formId);
        this.$field = document.querySelector(this.fieldId);
        this.$container = document.querySelector(this.containerId);

        if (this.$form && this.$field && this.$container) {
            this.initEditor();
        }
    }

    initEditor() {
        if (!this.buttons) {
            this.buttons = ['bold', 'italic'];
        }

        pell.init({
            element: this.$container,
            defaultParagraphSeparator: 'p',
            styleWithCSS: true,
            actions: this.buttons,
            onChange: html => {
                this.$field.textContent = html;
            },
            classes: {
                actionbar: 'fui-rich-text-toolbar',
                button: 'fui-rich-text-button',
                content: 'fui-input fui-rich-text-content',
                selected: 'fui-rich-text-selected',
            },
        });
    }
}

window.FormieRichText = FormieRichText;
