import { exec, init } from 'pell';

export class FormieRichText {
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
        } else {
            console.error('Unable to find ' + this.formId + ' ' + this.fieldId + ' ' + this.containerId);
        }
    }

    getButtons() {
        let buttonDefinitions = [
            {
                name: 'bold',
                icon: '<i class="far fa-bold"></i>',
            },
            {
                name: 'italic',
                icon: '<i class="far fa-italic"></i>',
            },
            {
                name: 'underline',
                icon: '<i class="far fa-underline"></i>',
            },
            {
                name: 'strikethrough',
                icon: '<i class="far fa-strikethrough"></i>',
            },
            {
                name: 'heading1',
                icon: '<i class="far fa-h1"></i>',
            },
            {
                name: 'heading2',
                icon: '<i class="far fa-h2"></i>',
            },
            {
                name: 'paragraph',
                icon: '<i class="far fa-paragraph"></i>',
            },
            {
                name: 'quote',
                icon: '<i class="far fa-quote-right"></i>',
            },
            {
                name: 'olist',
                icon: '<i class="far fa-list-ol"></i>',
            },
            {
                name: 'ulist',
                icon: '<i class="far fa-list-ul"></i>',
            },
            {
                name: 'code',
                icon: '<i class="far fa-code"></i>',
            },
            {
                name: 'line',
                icon: '<i class="far fa-horizontal-rule"></i>',
            },
            {
                name: 'link',
                icon: '<i class="far fa-link"></i>',
            },
            {
                name: 'image',
                icon: '<i class="far fa-image"></i>',
            },
        ];

        if (!this.buttons) {
            this.buttons = ['bold', 'italic'];
        }

        let buttons = [];

        this.buttons.forEach(button => {
            let found = buttonDefinitions.find(element => element.name === button);

            if (found) {
                buttons.push(found);
            }
        });

        return buttons;
    }

    initEditor() {
        // Load in FontAwesome, for better icons
        var $script = document.createElement('script');
        $script.src = 'https://kit.fontawesome.com/bfee7f35b7.js';
        $script.setAttribute('crossorigin', 'anonymous');
        document.body.appendChild($script);

        console.log(this.getButtons());

        this.editor = init({
            element: this.$container,
            defaultParagraphSeparator: 'p',
            styleWithCSS: true,
            actions: this.getButtons(),
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

        // Populate any values initially set
        this.editor.content.innerHTML = this.$field.textContent;
    }
}

window.FormieRichText = FormieRichText;
