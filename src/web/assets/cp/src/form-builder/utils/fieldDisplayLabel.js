import { getRichTextText } from '@utils/tiptapUtils';

import { hasRichTextValue } from '@form-builder/utils/richTextValue';

export const usesQuestionFieldLabel = (fieldType) => {
    return fieldType?.labelSource === 'question';
};

export const getFieldDisplayLabel = (field, fieldType, fallback) => {
    const resolvedFallback = fallback || Craft.t('formie', 'Field');

    if (usesQuestionFieldLabel(fieldType)) {
        const questionText = getRichTextText(field?.question).trim();

        if (questionText) {
            return questionText;
        }
    }

    const shouldUseFieldLabel = fieldType?.hasLabel !== false;

    if (shouldUseFieldLabel) {
        return field?.label || fieldType?.label || resolvedFallback;
    }

    return fieldType?.label || resolvedFallback;
};

export const shouldShowFieldDisplayLabel = (field, fieldType) => {
    if (fieldType?.hasLabel !== false) {
        return true;
    }

    if (usesQuestionFieldLabel(fieldType)) {
        return hasRichTextValue(field?.question) || Boolean(getRichTextText(field?.question).trim());
    }

    return false;
};

export const hasQuestionFieldLabelContent = (field, fieldType) => {
    return usesQuestionFieldLabel(fieldType) && hasRichTextValue(field?.question);
};
