import React from 'react';
import { FormieElementFieldPreview } from './ElementFieldPreview';
import { usePreviewSchemaContext } from './PreviewSchemaContext';

export const PreviewElementField = () => {
    const { field } = usePreviewSchemaContext();

    return <FormieElementFieldPreview field={field} />;
};
