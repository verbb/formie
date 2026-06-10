import React, { useEffect, useRef } from 'react';

const IFRAME_BASE_STYLES = `
*, *::before, *::after { box-sizing: border-box; }
html, body { margin: 0; padding: 0; }
body {
    padding: 0.625rem 0.75rem;
    font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    font-size: 13px;
    line-height: 1.5;
    color: rgb(31, 41, 51);
    word-wrap: break-word;
}
p, h1, h2, h3, h4, h5, h6 { margin: 0; }
p + p { margin-top: 0.5rem; }
img, video, svg { max-width: 100%; height: auto; }
`;

function writeIframeDocument(iframe, html) {
    const doc = iframe?.contentDocument;

    if (!doc) {
        return;
    }

    doc.open();
    doc.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><style>${IFRAME_BASE_STYLES}</style></head><body>${html}</body></html>`);
    doc.close();
}

function resizeIframe(iframe) {
    const doc = iframe?.contentDocument;

    if (!doc?.body) {
        return;
    }

    const height = Math.max(
        doc.documentElement.scrollHeight,
        doc.body.scrollHeight,
        1,
    );

    iframe.style.height = `${height}px`;
}

export const PreviewHtml = ({ html = '', fallbackHtml = '' }) => {
    const content = html || fallbackHtml;
    const iframeRef = useRef(null);
    const resizeObserverRef = useRef(null);

    useEffect(() => {
        const iframe = iframeRef.current;

        if (!iframe || !content) {
            return undefined;
        }

        writeIframeDocument(iframe, content);

        const doc = iframe.contentDocument;

        if (!doc?.body) {
            return undefined;
        }

        resizeIframe(iframe);

        resizeObserverRef.current?.disconnect();
        resizeObserverRef.current = new ResizeObserver(() => {
            resizeIframe(iframe);
        });
        resizeObserverRef.current.observe(doc.body);

        return () => {
            resizeObserverRef.current?.disconnect();
            resizeObserverRef.current = null;
        };
    }, [content]);

    if (!content) {
        return (
            <p className="formie-field-preview-static-content light">
                {Craft.t('formie', 'No HTML content configured yet.')}
            </p>
        );
    }

    return (
        <div className="formie-field-preview-static-content formie-field-preview-html">
            <iframe
                ref={iframeRef}
                className="formie-field-preview-html-iframe"
                src="about:blank"
                sandbox="allow-same-origin"
                title={Craft.t('formie', 'HTML content preview')}
                tabIndex={-1}
            />
        </div>
    );
};
