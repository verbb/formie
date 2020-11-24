import { eventKey } from '../utils/utils';

export class FormieTextLimit {
    constructor(settings = {}) {
        this.formId = '#formie-form-' + settings.formId;
        this.fieldId = '#fields-' + settings.fieldId;

        this.$form = document.querySelector(this.formId);
        this.$field = document.querySelector(this.fieldId);
        this.$text = document.querySelector(this.fieldId + '-max');

        if (this.$form && this.$field && this.$text) {
            this.form = this.$form.form;

            this.initTextMax();
        } else {
            // Only an error if a single-page form (any submit method), or a multi-page ajax form.
            if (!settings.formSettings.hasMultiplePages || (settings.formSettings.submitMethod === 'ajax' && settings.formSettings.hasMultiplePages)) {
                console.error('Unable to find text limit ' + this.formId + ' ' + this.fieldId);
            }
        }
    }

    initTextMax() {
        this.maxChars = this.$text.getAttribute('data-max-chars');
        this.maxWords = this.$text.getAttribute('data-max-words');

        if (this.maxChars) {
            this.form.addEventListener(this.$field, eventKey('keydown'), this.characterCheck.bind(this), false);
        }

        if (this.maxWords) {
            this.form.addEventListener(this.$field, eventKey('keydown'), this.wordCheck.bind(this), false);
        }
    }

    characterCheck(e) {
        setTimeout(() => {
            var charactersLeft = this.maxChars - e.target.value.length;

            if (charactersLeft <= 0) {
                charactersLeft = '0';
            }

            this.$text.innerHTML = t('{num} characters left', {
                num: charactersLeft,
            });
        }, 1);
    }

    wordCheck(e) {
        setTimeout(() => {
            var wordCount = e.target.value.split(/\S+/).length - 1;
            var regex = new RegExp('^\\s*\\S+(?:\\s+\\S+){0,' + (this.maxWords - 1) + '}');
            
            if (wordCount >= this.maxWords) {
                this.$field.value = e.target.value.match(regex);
            }

            var wordsLeft = this.maxWords - wordCount;

            if (wordsLeft <= 0) {
                wordsLeft = '0';
            }

            this.$text.innerHTML = t('{num} words left', {
                num: wordsLeft,
            });
        }, 1);
    }
}

window.FormieTextLimit = FormieTextLimit;
