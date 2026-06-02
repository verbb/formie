import React, { useMemo } from 'react';
import useAppStore from '@form-builder/hooks/useAppStore';
import { renderFieldPreviewSchema } from './renderFieldPreviewTemplate';
import { SubFieldPreviewControl } from './SubFieldPreviewControl';

const ChildFieldPreview = ({ field }) => {
    const getFieldTypeByType = useAppStore((state) => { return state.getFieldTypeByType; });
    const fieldType = useMemo(() => {
        if (!field?.type || typeof getFieldTypeByType !== 'function') {
            return null;
        }

        return getFieldTypeByType(field.type);
    }, [field?.type, getFieldTypeByType]);

    if (!fieldType?.preview) {
        return <SubFieldPreviewControl field={field} />;
    }

    // Avoid deep/nested parent previews in child slots.
    if (fieldType.isContainerParentField || fieldType.isRepeatableParentField) {
        return <SubFieldPreviewControl field={field} />;
    }

    return renderFieldPreviewSchema(fieldType.preview, field, fieldType);
};

export { ChildFieldPreview };
