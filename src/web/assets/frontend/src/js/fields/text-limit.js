import { eventKey } from '../utils/utils';

export class FormieTextLimit {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;
        this.$text = this.$field.querySelector('[data-max-limit]');
        this.$input = this.$field.querySelector('input, textarea');

        if (this.$text) {
            this.initTextMax();
        } else {
            console.error('Unable to find rich text field “[data-max-limit]”');
        }
    }

    initTextMax() {
        this.maxChars = this.$text.getAttribute('data-max-chars');
        this.maxWords = this.$text.getAttribute('data-max-words');

        if (this.maxChars) {
            this.form.addEventListener(this.$input, eventKey('keydown'), this.characterCheck.bind(this), false);
        }

        if (this.maxWords) {
            this.form.addEventListener(this.$input, eventKey('keydown'), this.wordCheck.bind(this), false);
        }
    }

    characterCheck(e) {
        setTimeout(() => {
            // If we're using a rich text editor, treat it a little differently
            const isRichText = e.target.hasAttribute('contenteditable');

            const value = isRichText ? e.target.innerHTML : e.target.value;

            let charactersLeft = this.maxChars - value.length;

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
            // If we're using a rich text editor, treat it a little differently
            const isRichText = e.target.hasAttribute('contenteditable');

            const value = isRichText ? e.target.innerHTML : e.target.value;

            const wordCount = value.split(/\S+/).length - 1;
            const regex = new RegExp(`^\\s*\\S+(?:\\s+\\S+){0,${this.maxWords - 1}}`);

            if (wordCount >= this.maxWords) {
                e.target.value = value.match(regex);
            }

            let wordsLeft = this.maxWords - wordCount;

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
