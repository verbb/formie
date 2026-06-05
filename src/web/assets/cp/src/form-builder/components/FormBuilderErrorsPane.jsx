import { useMemo } from 'react';

import { useFormBuilderForm } from '@form-builder/contexts/FormBuilderFormContext';
import { FormieErrorsPane } from '@utils/FormieErrorsPane';

function FormBuilderErrorsPane() {
    const { values: formValues, errors: errorMap } = useFormBuilderForm();

    const errorList = useMemo(() => {
        const collected = [];
        const pages = formValues?.pages || [];
        const integrationGroups = formValues?.integrations || {};
        const integrationLabelsByHandle = {};

        Object.values(integrationGroups).forEach((groupIntegrations) => {
            if (!Array.isArray(groupIntegrations)) {
                return;
            }

            groupIntegrations.forEach((integration) => {
                const handle = integration?.handle;
                if (!handle) {
                    return;
                }

                const integrationName = typeof integration?.name === 'string' ? integration.name.trim() : '';
                if (integrationName) {
                    integrationLabelsByHandle[handle] = integrationName;
                }
            });
        });

        const fieldErrors = errorMap || {};

        Object.entries(fieldErrors).forEach(([path, messages]) => {
            if (!Array.isArray(messages) || messages.length === 0) {
                return;
            }

            const integrationMatch = path.match(/^settings\.integrations\.([^.]+)\./);
            if (integrationMatch) {
                const integrationHandle = integrationMatch[1];
                const integrationLabel = integrationLabelsByHandle[integrationHandle] || Craft.t('formie', 'Unknown Integration');
                const prefix = `${Craft.t('formie', 'Integrations')} > ${integrationLabel} >`;

                messages.forEach((message) => {
                    collected.push(`${prefix} ${message}`);
                });

                return;
            }

            const parts = path.split('.');
            const pagesIndex = parts.indexOf('pages');
            const rowsIndex = parts.indexOf('rows');
            const fieldsIndex = parts.indexOf('fields');

            const pageIndex = pagesIndex >= 0 ? Number(parts[pagesIndex + 1]) : null;
            const rowIndex = rowsIndex >= 0 ? Number(parts[rowsIndex + 1]) : null;
            const fieldIndex = fieldsIndex >= 0 ? Number(parts[fieldsIndex + 1]) : null;

            const page = Number.isInteger(pageIndex) ? pages[pageIndex] : null;
            const row = Number.isInteger(rowIndex) ? page?.rows?.[rowIndex] : null;
            const field = Number.isInteger(fieldIndex) ? row?.fields?.[fieldIndex] : null;

            const pageLabel = page?.label;
            const fieldLabel = field?.settings?.label || field?.label || field?.settings?.handle || field?.handle;
            const prefixParts = [];

            if (pageLabel) {
                prefixParts.push(`${pageLabel}:`);
            }

            if (fieldLabel) {
                prefixParts.push(`${fieldLabel} -`);
            }

            const prefix = prefixParts.join(' ');

            messages.forEach((message) => {
                collected.push(prefix ? `${prefix} ${message}` : message);
            });
        });

        return collected;
    }, [formValues, errorMap]);

    return (
        <FormieErrorsPane
            errors={errorList}
            className="mb-4"
            headingId="form-builder-errors-heading"
        />
    );
}

export { FormBuilderErrorsPane };
