import React from 'react';
import { ContainerFieldPreview } from './preview/ContainerFieldPreview';
import { renderFieldPreviewSchema } from './preview/renderFieldPreviewTemplate';

const getMissingFieldPreviewSchema = () => {
    return [
        {
            $cmp: 'PreviewMessage',
            message: Craft.t('formie', 'Unable to find component class'),
            className: 'formie-field-preview-input',
        },
    ];
};

const isMissingField = (field) => {
    return field?.isMissing === true ||
        field?.type === 'verbb\\formie\\fields\\MissingField' ||
        Boolean(field?.expectedType);
};

const FieldPreview = ({
    field, fieldType, pageIndex, rowIndex, fieldIndex, canAddExistingFields = false, onOpenExistingFields = null,
}) => {
    try {
        if (isMissingField(field)) {
            return renderFieldPreviewSchema(getMissingFieldPreviewSchema(), field, fieldType);
        }

        if (!fieldType) {
            return renderFieldPreviewSchema(getMissingFieldPreviewSchema(), field, fieldType);
        }

        if (fieldType?.isContainerParentField || fieldType?.isRepeatableParentField) {
            return (
                <ContainerFieldPreview
                    field={field}
                    fieldType={fieldType}
                    pageIndex={pageIndex}
                    rowIndex={rowIndex}
                    fieldIndex={fieldIndex}
                    canAddExistingFields={canAddExistingFields}
                    onOpenExistingFields={onOpenExistingFields}
                />
            );
        }

        return renderFieldPreviewSchema(fieldType.preview, field, fieldType);
    } catch (error) {
        console.error('Failed to render field preview:', error);

        return (
            <div className="text-error mt-2">
                <p>{Craft.t('formie', 'Unable to render field preview.')}</p>
            </div>
        );
    }
};

export { FieldPreview };
