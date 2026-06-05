export const normalizeErrorText = (value) => {
    if (value == null) {
        return '';
    }

    if (typeof value === 'string') {
        return value.trim();
    }

    if (typeof value === 'object') {
        try {
            return JSON.stringify(value);
        } catch {
            return String(value).trim();
        }
    }

    return String(value).trim();
};

export const getRequestErrorMessage = (error, fallbackMessage = 'Request failed.') => {
    const messages = [];
    const payload = error?.response?.data;

    const appendMessage = (value) => {
        if (value == null) {
            return;
        }

        const message = normalizeErrorText(value);

        if (message) {
            messages.push(message);
        }
    };

    const payloadMessage = normalizeErrorText(payload?.message);
    const payloadException = normalizeErrorText(payload?.exception);
    const payloadFile = normalizeErrorText(payload?.file);
    const payloadLine = normalizeErrorText(payload?.line);

    if (payloadMessage && (payloadException || payloadFile || payloadLine)) {
        const location = payloadFile ? `${payloadFile}${payloadLine ? `:${payloadLine}` : ''}` : '';
        const detailPrefix = [payloadException, location].filter(Boolean).join(' @ ');
        appendMessage(detailPrefix ? `${detailPrefix} - ${payloadMessage}` : payloadMessage);
    }

    appendMessage(payload?.message);
    appendMessage(payload?.error);

    const payloadErrors = payload?.errors;

    if (Array.isArray(payloadErrors)) {
        payloadErrors.forEach((item) => {
            appendMessage(item);
        });
    } else if (payloadErrors && typeof payloadErrors === 'object') {
        Object.values(payloadErrors).forEach((value) => {
            if (Array.isArray(value)) {
                value.forEach((item) => {
                    appendMessage(item);
                });
                return;
            }

            appendMessage(value);
        });
    }

    appendMessage(error?.message);

    const uniqueMessages = [...new Set(messages)];

    return uniqueMessages[0] || fallbackMessage;
};
