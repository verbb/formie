export const rule = ({ input, config }) => {
    const pattern = input.getAttribute('pattern');
    const patternToMatch = pattern ? new RegExp(`^(?:${pattern})$`) : config.patterns[input.type];

    if (!patternToMatch || !input.value || input.value.length < 1) {
        return true;
    }

    return input.value.match(patternToMatch) ? true : false;
};

export const message = ({ input, label, t }) => {
    const messages = {
        email: t('{attribute} is not a valid email address.', { attribute: label }),
        url: t('{attribute} is not a valid URL.', { attribute: label }),
        number: t('{attribute} is not a valid number.', { attribute: label }),
        default: t('{attribute} is not a valid format.', { attribute: label }),
    };

    return input.getAttribute(`data-pattern-${input.type}-message`) ?? messages[input.type] ?? messages.default;
};

export default {
    rule,
    message,
};
