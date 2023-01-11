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
            this.form.addEventListener(this.$input, eventKey('paste'), this.characterCheck.bind(this), false);
            this.form.addEventListener(this.$input, eventKey('keydown'), this.characterCheck.bind(this), false);
            this.form.addEventListener(this.$input, eventKey('populate'), this.characterCheck.bind(this), false);

            // Fire immediately
            this.$input.dispatchEvent(new Event('keydown', { bubbles: true }));
        }

        if (this.maxWords) {
            this.form.addEventListener(this.$input, eventKey('paste'), this.wordCheck.bind(this), false);
            this.form.addEventListener(this.$input, eventKey('keydown'), this.wordCheck.bind(this), false);
            this.form.addEventListener(this.$input, eventKey('populate'), this.wordCheck.bind(this), false);

            // Fire immediately
            this.$input.dispatchEvent(new Event('keydown', { bubbles: true }));
        }
    }

    characterCheck(e) {
        setTimeout(() => {
            // Strip HTML tags
            var value = this.stripTags(e.target.value);
            var charactersLeft = this.maxChars - this.count(value);
            var extraClasses = ['fui-limit-number'];
            var type = charactersLeft == 1 || charactersLeft == -1 ? 'character' : 'characters';

            if (charactersLeft < 0) {
                extraClasses.push('fui-limit-number-error');
            }

            this.$text.innerHTML = t('{startTag}{num}{endTag} ' + type + ' left', {
                num: String(charactersLeft),
                startTag: '<span class="' + extraClasses.join(' ') + '">',
                endTag: '</span>',
            });
        }, 1);
    }

    wordCheck(e) {
        setTimeout(() => {
            // Strip HTML tags
            var value = this.stripTags(e.target.value);
            var wordCount = value.split(/\S+/).length - 1;
            var wordsLeft = this.maxWords - wordCount;
            var extraClasses = ['fui-limit-number'];
            var type = wordsLeft == 1 || wordsLeft == -1 ? 'word' : 'words';

            if (wordsLeft < 0) {
                extraClasses.push('fui-limit-number-error');
            }

            this.$text.innerHTML = t('{startTag}{num}{endTag} ' + type + ' left', {
                num: String(wordsLeft),
                startTag: '<span class="' + extraClasses.join(' ') + '">',
                endTag: '</span>',
            });
        }, 1);
    }

    count(value) {
        // Convert any multibyte characters to their HTML entity equivalent to match server-side processing
        // https://dev.to/nikkimk/converting-utf-including-emoji-to-html-x1f92f-4951
        return [...value].map(char => {
            // Check for space characters to exclude
            return char.codePointAt() > 127 && !/\s/.test(char) ? `&#${char.codePointAt()};` : char;
        }).join('').length;
    }

    stripTags(string) {
        const doc = new DOMParser().parseFromString(string, 'text/html');
        
        return doc.body.textContent || '';
    }
}

window.FormieTextLimit = FormieTextLimit;
