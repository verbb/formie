import React from 'react';
import { TiptapContent } from '@verbb/plugin-kit-react/components';

export const PreviewAgree = ({ checked = false, description = '' }) => {
    return (
        <div className="formie-field-preview-checkbox">
            <input type="checkbox" defaultChecked={Boolean(checked)} />
            <label>
                <TiptapContent value={description || ''} />
            </label>
        </div>
    );
};
