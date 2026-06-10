import React from 'react';
import { TiptapContent } from '@verbb/plugin-kit-react/components';

export const PreviewRichText = ({ value = '' }) => {
    return (
        <div className="formie-field-preview-static-content formie-field-preview-rich-text">
            <TiptapContent value={value || ''} />
        </div>
    );
};
