/** Copy plain text to the clipboard. */
export async function copyToClipboardWithMeta(value) {
    const text = String(value ?? '');

    if (navigator.clipboard?.writeText) {
        try {
            await navigator.clipboard.writeText(text);
            return;
        } catch {
            // Fall through for browsers that expose the API but deny it in the CP.
        }
    }

    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();

    try {
        if (!document.execCommand('copy')) {
            throw new Error('The browser did not allow clipboard access.');
        }
    } finally {
        textarea.remove();
    }
}
