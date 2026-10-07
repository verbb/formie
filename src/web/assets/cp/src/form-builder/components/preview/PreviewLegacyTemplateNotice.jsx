import React from 'react';
import { Alert } from '@verbb/plugin-kit-react/components';

export const PreviewLegacyTemplateNotice = ({
    title = Craft.t('formie', 'Legacy field preview requires migration.'),
    message = Craft.t('formie', 'Replace `getFormBuilderPreviewHtml()` with `defineFormBuilderPreviewSchema()`.'),
}) => {
    return (
        <Alert variant="warning" size="sm" heading={title} className="mt-2">
            {message}
        </Alert>
    );
};
