import { useMemo } from 'react';
import { useFormBuilderForm } from '@form-builder/contexts/FormBuilderFormContext';

const useFormBuilderTabErrors = (schemaNode) => {
    const { hasErrorsForPrefix, hasErrorsForSchema } = useFormBuilderForm();

    return useMemo(() => {
        const errors = {};

        if (!schemaNode?.children) {
            return errors;
        }

        const fieldsTabHasErrors = hasErrorsForPrefix('pages.');

        Object.values(schemaNode.children).forEach((item) => {
            if (item.$cmp !== 'FormBuilderTabContent') {
                return;
            }

            if (item.props?.value === 'fields') {
                errors[item.props.value] = fieldsTabHasErrors;
                return;
            }

            if (item.props?.value === 'notifications') {
                errors[item.props.value] = hasErrorsForPrefix('notifications.');
                return;
            }

            if (item.props?.value === 'integrations') {
                errors[item.props.value] = hasErrorsForPrefix('integrations.');
                return;
            }

            errors[item.props.value] = hasErrorsForSchema(item.children || []);
        });

        return errors;
    }, [schemaNode, hasErrorsForPrefix, hasErrorsForSchema]);
};

export { useFormBuilderTabErrors };
