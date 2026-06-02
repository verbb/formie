import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faTriangleExclamation } from '@fortawesome/pro-solid-svg-icons';

import { cn } from '@verbb/plugin-kit-react/utils';
import { useMemo } from 'react';
import { useFormBuilderForm } from '@form-builder/contexts/FormBuilderFormContext';

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

    const errorMessage = useMemo(() => {
        if (errorList.length === 1) {
            return Craft.t('formie', 'Found {num} error', { num: errorList.length });
        }

        return Craft.t('formie', 'Found {num} errors', { num: errorList.length });
    }, [errorList.length]);

    if (errorList.length === 0) {
        return null;
    }

    return (
        <div
            className={cn(
                'rounded-lg shadow-[0_0_0_1px_var(--color-gray-200),_0_2px_12px_rgb(205_216_228_/_50%)] bg-white/50 py-4 px-6 mb-4',
            )}
            tabIndex={-1}
            role="alert"
            aria-live="assertive"
            aria-atomic="true"
            aria-labelledby="form-builder-errors-heading"
        >
            <div className="flex items-center gap-1.5">
                <FontAwesomeIcon icon={faTriangleExclamation} className="size-3 text-rose-600" />

                <h2 id="form-builder-errors-heading" className="text-base font-bold">
                    {errorMessage}
                </h2>
            </div>

            <ul className="mt-1 space-y-1 pl-4.5 text-sm text-gray-700">
                {errorList.map((error, index) => {
                    return (
                        <li key={`${index}-${error}`} className="list-disc" dangerouslySetInnerHTML={{ __html: error }} />
                    );
                })}
            </ul>
        </div>
    );
}

export { FormBuilderErrorsPane };
