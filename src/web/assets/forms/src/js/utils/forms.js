import { get } from 'lodash-es';

const nl2br = (str) => {
    return str.replace(/\n/g, '<br>');
};

export const getErrorMessage = function(error) {
    const content = {
        heading: '',
        text: '',
        trace: '',
    };

    // The category of error - should be `Internal Server Error` or `Bad Request`.
    // Fallback on generic message - likely JS-side which doesn't have a category.
    content.heading = get(error, 'response.statusText', 'An error has occurred');

    // Check for application errors returning via `asFailure()` from controllers, handle generic `error`,
    // or fallback on a string-cast of the error (likely JS-side error)
    content.text = get(error, 'response.data.message', get(error, 'response.data.error', error));

    // Check if there's trace info from the server. We often need two lines of trace too for context.
    const file1 = get(error, 'response.data.file', '');
    const line1 = get(error, 'response.data.line', '');

    if (file1 && line1) {
        content.trace = nl2br(`${file1}:${line1}`);
    }

    const file2 = get(error, 'response.data.trace.0.file', '');
    const line2 = get(error, 'response.data.trace.0.line', '');

    if (file2 && line2) {
        content.trace += nl2br(`<br>${file2}:${line2}`);
    }

    // Check for JS-side stack trace
    const jsStack = get(error, 'stack', '');

    if (jsStack) {
        content.trace += nl2br(jsStack);
    }

    return content;
};
