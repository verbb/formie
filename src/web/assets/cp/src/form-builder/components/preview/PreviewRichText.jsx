import React from 'react';
import { TiptapContent } from '@verbb/plugin-kit-react/components';

export const PreviewRichText = ({ value = '' }) => {
    return <TiptapContent value={value || ''} />;
};
