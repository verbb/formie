/** Copy plain text to the clipboard. */
export async function copyToClipboardWithMeta(value) {
    await navigator.clipboard.writeText(String(value ?? ''));
}
