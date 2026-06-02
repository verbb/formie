import { useState, useEffect } from 'react';
import { Spinner } from '@verbb/plugin-kit-react/components';
import { takeAtLeast } from '@verbb/plugin-kit-react/utils';
import { useFormValues } from '@form-builder/hooks/useFormTools';
import { LargeErrorState, StatePanel } from '@utils';

const FormUsage = () => {
    const formValues = useFormValues();

    const [usageData, setUsageData] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    const loadUsageData = async () => {
        if (!formValues.id) { return; }

        setLoading(true);
        setError(null);

        const data = {
            formId: formValues.id,
        };

        try {
            const response = await takeAtLeast(500)(
                Craft.sendActionRequest('POST', 'formie/forms/get-form-usage', { data }),
            );

            if (response.data.error) {
                throw response.data.error;
            }

            setUsageData(response.data);
        } catch (error) {
            setError(error);
        }

        setLoading(false);
    };

    useEffect(() => {
        loadUsageData();
    }, []);

    if (loading) {
        return (
            <div className="p-20 text-center">
                <Spinner size="lg" />
            </div>
        );
    }

    if (error) {
        return (
            <LargeErrorState
                error={error}
                message={Craft.t('formie', 'Unable to load usage for this form.')}
                detailsLabel={Craft.t('formie', 'Show error details')}
                actionLabel={Craft.t('formie', 'Try again')}
                onAction={loadUsageData}
            />
        );
    }

    if (!usageData || usageData.length === 0) {
        return (
            <StatePanel
                variant="empty"
                title={Craft.t('formie', 'No usage found')}
                message={Craft.t('formie', 'This form is not currently being used by any entries, users, or other elements.')}
                containerClassName="p-8 text-center"
                contentClassName="flex w-[90%] max-w-[560px] flex-col items-center text-center mx-auto"
            />
        );
    }

    return (
        <div className="border border-gray-200 rounded-lg overflow-hidden">
            <table className="w-full">
                <thead className="bg-gray-50">
                    <tr>
                        <th className="px-4 py-3 text-left text-sm font-bold text-gray-700">
                            {Craft.t('formie', 'Element')}
                        </th>

                        <th className="px-4 py-3 text-left text-sm font-bold text-gray-700">
                            {Craft.t('formie', 'Type')}
                        </th>

                        <th className="px-4 py-3 text-left text-sm font-bold text-gray-700">
                            {Craft.t('formie', 'Site')}
                        </th>

                        <th className="px-4 py-3 text-left text-sm font-bold text-gray-700">
                            {Craft.t('formie', 'Status')}
                        </th>

                        <th className="px-4 py-3 text-left text-sm font-bold text-gray-700">
                            {Craft.t('formie', 'Field')}
                        </th>
                    </tr>
                </thead>

                <tbody className="bg-white divide-y divide-gray-200">
                    {usageData.map((item, index) => {
                        return (
                            <tr key={index} className="hover:bg-gray-50">
                                <td className="px-4 py-3 text-sm text-gray-900">
                                    <div className="flex items-center" style={{ paddingLeft: `${(item.level - 1) * 12}px` }}>
                                        <a className="text-blue-600 font-bold hover:underline" href={item.element.cpEditUrl} target="_blank" rel="noopener noreferrer">
                                            {item.element.title}
                                        </a>
                                    </div>
                                </td>

                                <td className="px-4 py-3 text-sm text-gray-900">
                                    {item.elementType}
                                </td>

                                <td className="px-4 py-3 text-sm text-gray-900">
                                    {item.site.name}
                                </td>

                                <td className="px-4 py-3 text-sm text-gray-900">
                                    {item.isDraft ? (
                                        Craft.t('formie', 'Draft')
                                    ) : item.isRevision ? (
                                        Craft.t('formie', 'Revision')
                                    ) : (
                                        item.status
                                    )}
                                </td>

                                <td className="px-4 py-3 text-sm text-gray-900">
                                    {item.field && (
                                        <a className="font-bold" href={item.field.cpEditUrl} target="_blank" rel="noopener noreferrer">
                                            {item.field.name} <code>({item.field.handle})</code>
                                        </a>
                                    )}

                                    {!item.field && Craft.t('formie', 'Unknown field')}
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
};

export { FormUsage };
