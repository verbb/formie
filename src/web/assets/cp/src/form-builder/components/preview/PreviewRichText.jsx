import React from 'react';
import { TiptapContent } from '@verbb/plugin-kit-react/components';
import { normalizeRichTextValue } from '@form-builder/utils/richTextValue';

export const PreviewRichText = ({ value = '' }) => {
    return (
        <div className="formie-field-preview-static-content formie-field-preview-rich-text">
            <TiptapContent value={normalizeRichTextValue(value)} />
        </div>
    );
};
