import React from 'react';

export const PreviewLegacyTemplateNotice = ({
    title = Craft.t('formie', 'Legacy field preview requires migration.'),
    message = Craft.t('formie', 'Replace `getFormBuilderPreviewHtml()` with `defineFormBuilderPreviewSchema()`.'),
}) => {
    return (
        <div className="text-error mt-2">
            <p className="font-medium">{title}</p>
            <p>{message}</p>
        </div>
    );
};
