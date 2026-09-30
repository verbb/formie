import { t, eventKey } from '../utils/utils';

const WORD_PATTERN = /[^\s\u0085]+/gu;
const GRAPHEME_PATTERN = (() => {
    try {
        return new RegExp('(?:\\p{Regional_Indicator}{2}|\\p{Extended_Pictographic}[\\p{Emoji_Modifier}\\p{M}\\u{E0020}-\\u{E007F}]*(?:\\u{200D}\\p{Extended_Pictographic}[\\p{Emoji_Modifier}\\p{M}\\u{E0020}-\\u{E007F}]*)*|[\\s\\S])\\p{M}*', 'gu');
    } catch {
        return null;
    }
})();
const graphemeSegmenter = (() => {
    if (typeof Intl === 'undefined' || typeof Intl.Segmenter !== 'function') {
        return null;
    }

    try {
        return new Intl.Segmenter(undefined, { granularity: 'grapheme' });
    } catch {
        return null;
    }
})();

export class FormieTextLimit {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;
        this.$text = this.$field.querySelector('[data-limit]');
        this.$input = this.$field.querySelector('input, textarea');

        this.initTextLimits();
    }

    initTextLimits() {
        this.minChars = this.$input.getAttribute('data-min-chars');
        this.maxChars = this.$input.getAttribute('data-max-chars');
        this.minWords = this.$input.getAttribute('data-min-words');
        this.maxWords = this.$input.getAttribute('data-max-words');

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

        this.form.registerEvent('registerFormieValidation', this.registerValidation.bind(this));
    }

    registerValidation(e) {
        e.validator.addValidator('textMinCharacterLimit', ({ input }) => {
            const limit = input.getAttribute('data-min-chars');

            if (!limit) {
                return true;
            }

            const value = this.stripTags(input.value);
            const charactersLeft = limit - this.count(value);

            if (charactersLeft > 0) {
                return false;
            }

            return true;
        }, ({ label, input }) => {
            return t('{attribute} must be no less than {min} characters.', {
                attribute: label,
                min: input.getAttribute('data-min-chars'),
            });
        });

        e.validator.addValidator('textMaxCharacterLimit', ({ input }) => {
            const limit = input.getAttribute('data-max-chars');

            if (!limit) {
                return true;
            }

            const value = this.stripTags(input.value);
            const charactersLeft = limit - this.count(value);

            if (charactersLeft < 0) {
                return false;
            }

            return true;
        }, ({ label, input }) => {
            return t('{attribute} must be no greater than {max} characters.', {
                attribute: label,
                max: input.getAttribute('data-max-chars'),
            });
        });

        e.validator.addValidator('textMinWordLimit', ({ input }) => {
            const limit = input.getAttribute('data-min-words');

            if (!limit) {
                return true;
            }

            const value = this.stripTags(input.value);
            const wordCount = this.getWordCount(value);
            const wordsLeft = limit - wordCount;

            if (wordsLeft > 0) {
                return false;
            }

            return true;
        }, ({ label, input }) => {
            return t('{attribute} must be no less than {min} words.', {
                attribute: label,
                min: input.getAttribute('data-min-words'),
            });
        });

        e.validator.addValidator('textMaxWordLimit', ({ input }) => {
            const limit = input.getAttribute('data-max-words');

            if (!limit) {
                return true;
            }

            const value = this.stripTags(input.value);
            const wordCount = this.getWordCount(value);
            const wordsLeft = limit - wordCount;

            if (wordsLeft < 0) {
                return false;
            }

            return true;
        }, ({ label, input }) => {
            return t('{attribute} must be no greater than {max} words.', {
                attribute: label,
                max: input.getAttribute('data-max-words'),
            });
        });
    }

    characterCheck(e) {
        setTimeout(() => {
            // Strip HTML tags
            const value = this.stripTags(e.target.value);
            const charactersLeft = this.maxChars - this.count(value);
            const extraClasses = ['fui-limit-number'];
            const type = charactersLeft == 1 || charactersLeft == -1 ? 'character' : 'characters';

            if (charactersLeft < 0) {
                extraClasses.push('fui-limit-number-error');
            }

            if (this.$text) {
                this.$text.innerHTML = t(`{startTag}{num}{endTag} ${type} left`, {
                    num: String(charactersLeft),
                    startTag: `<span class="${extraClasses.join(' ')}">`,
                    endTag: '</span>',
                });

            }
        }, 1);
    }

    wordCheck(e) {
        setTimeout(() => {
            // Strip HTML tags
            const value = this.stripTags(e.target.value);
            const wordCount = this.getWordCount(value);
            const wordsLeft = this.maxWords - wordCount;
            const extraClasses = ['fui-limit-number'];
            const type = wordsLeft == 1 || wordsLeft == -1 ? 'word' : 'words';

            if (wordsLeft < 0) {
                extraClasses.push('fui-limit-number-error');
            }

            if (this.$text) {
                this.$text.innerHTML = t(`{startTag}{num}{endTag} ${type} left`, {
                    num: String(wordsLeft),
                    startTag: `<span class="${extraClasses.join(' ')}">`,
                    endTag: '</span>',
                });
            }
        }, 1);
    }

    count(value) {
        if (graphemeSegmenter) {
            return Array.from(graphemeSegmenter.segment(value)).length;
        }

        return GRAPHEME_PATTERN ? value.match(GRAPHEME_PATTERN)?.length || 0 : Array.from(value).length;
    }

    getWordCount(value) {
        return value.match(WORD_PATTERN)?.length || 0;
    }

    stripTags(string) {
        const doc = new DOMParser().parseFromString(string, 'text/html');

        return doc.body.textContent || '';
    }
}

window.FormieTextLimit = FormieTextLimit;
