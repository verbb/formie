import {
    useEffect, useMemo, useRef, useState,
} from 'react';
import { isEqual } from 'lodash-es';

import { SchemaFormEngine, useSchemaFormEngine, useEngineField, FieldLayout } from '@verbb/plugin-kit-react/forms';
import { Spinner } from '@verbb/plugin-kit-react/components';
import { fetchPaymentProviderSettingsSchema } from '@form-builder/hooks/useFormTools';

const paymentProviderSchemaCache = {};

const mergeProviderSettingsWithDefaults = (defaults, currentValues) => {
    const merged = {
        ...(defaults && typeof defaults === 'object' ? defaults : {}),
    };

    Object.entries(currentValues || {}).forEach(([key, value]) => {
        if (value === '' || value === null || value === undefined) {
            return;
        }

        merged[key] = value;
    });

    return merged;
};

const getNestedErrors = (errors, prefix) => {
    if (!errors || typeof errors !== 'object' || !prefix) {
        return {};
    }

    const nestedErrors = {};

    Object.entries(errors).forEach(([key, value]) => {
        if (!key.startsWith(`${prefix}.`)) {
            return;
        }

        const nestedKey = key.slice(prefix.length + 1);
        if (!nestedKey) {
            return;
        }

        nestedErrors[nestedKey] = value;
    });

    return nestedErrors;
};

function PaymentProviderSettingsField({ field, form }) {
    const { value: selectedProviderHandle } = useEngineField(form, 'paymentIntegration');
    const { value: providerSettingsRootValue } = useEngineField(form, 'providerSettings');
    const providerHandle = String(selectedProviderHandle || '').trim();
    const schemaGroup = String(field.schemaGroup || 'defineFormBuilderGeneralSchema').trim() || 'defineFormBuilderGeneralSchema';
    const fieldType = String(field.fieldType || 'verbb\\formie\\fields\\Payment').trim() || 'verbb\\formie\\fields\\Payment';
    const providerSettingsPath = providerHandle ? `providerSettings.${providerHandle}` : '';
    const providerConfigKey = `${fieldType}::${schemaGroup}::${providerHandle}`;
    const appliedProviderConfigKeyRef = useRef(null);
    const [configLoading, setConfigLoading] = useState(false);
    const [configError, setConfigError] = useState(null);
    const [schemaConfig, setSchemaConfig] = useState(null);
    const { value: providerSettingsValue } = useEngineField(
        form,
        providerSettingsPath || '__providerSettingsFallback__',
    );

    useEffect(() => {
        if (!providerHandle || typeof form?.setFieldValue !== 'function') {
            return;
        }

        const rootValue = (typeof form?.getValueAtPath === 'function')
            ? form.getValueAtPath('providerSettings', providerSettingsRootValue)
            : providerSettingsRootValue;
        const hasObjectRoot = rootValue && typeof rootValue === 'object' && !Array.isArray(rootValue);

        // New fields can start with providerSettings as [].
        // Writing providerSettings.stripe onto an array creates non-index keys that JSON serialization drops.
        if (!hasObjectRoot) {
            form.setFieldValue('providerSettings', {});
        }
    }, [providerHandle, form, providerSettingsRootValue]);

    useEffect(() => {
        if (!providerHandle) {
            setSchemaConfig(null);
            setConfigError(null);
            setConfigLoading(false);
            return;
        }

        const cacheKey = providerConfigKey;
        const cached = paymentProviderSchemaCache[cacheKey];
        if (cached) {
            setSchemaConfig(cached);
            setConfigError(null);
            return;
        }

        let cancelled = false;
        setConfigLoading(true);
        setConfigError(null);

        fetchPaymentProviderSettingsSchema(providerHandle, {
            schemaGroup,
            fieldType,
        }).then((result) => {
            if (cancelled) {
                return;
            }

            if (result?.ok) {
                const nextConfig = {
                    schema: result?.data?.schema || [],
                    schemaIndex: result?.data?.schemaIndex || null,
                    defaultValues: result?.data?.defaultValues || {},
                };
                paymentProviderSchemaCache[cacheKey] = nextConfig;
                setSchemaConfig(nextConfig);
                setConfigError(null);
            } else {
                setSchemaConfig(null);
                setConfigError(result?.errors?.provider?.[0] || Craft.t('formie', 'Failed to load provider settings.'));
            }

            setConfigLoading(false);
        });

        return () => {
            cancelled = true;
        };
    }, [providerHandle, schemaGroup, fieldType, providerConfigKey]);

    const initialValues = useMemo(() => {
        if (!providerHandle) {
            return {};
        }

        const currentValues = (providerSettingsValue && typeof providerSettingsValue === 'object')
            ? providerSettingsValue
            : {};
        const defaults = schemaConfig?.defaultValues || {};

        return mergeProviderSettingsWithDefaults(defaults, currentValues);
    }, [providerHandle, schemaConfig, providerSettingsValue]);

    const nestedErrors = useMemo(() => {
        if (!providerSettingsPath) {
            return {};
        }

        return getNestedErrors(form?.errors, providerSettingsPath);
    }, [form, providerSettingsPath]);
    const fallbackSchemaIndex = useMemo(() => {
        return {
            schema: [],
            fieldEntries: [],
        };
    }, []);

    const nestedForm = useSchemaFormEngine({
        schema: schemaConfig?.schema || [],
        schemaIndex: schemaConfig?.schemaIndex || fallbackSchemaIndex,
        defaultValues: initialValues,
        errors: nestedErrors,
        parentForm: form,
        parentPath: providerSettingsPath || undefined,
        onChange: (values) => {
            if (!providerSettingsPath || typeof form?.setFieldValue !== 'function') {
                return;
            }

            const existing = typeof form?.getValueAtPath === 'function'
                ? (form.getValueAtPath(providerSettingsPath, {}) || {})
                : {};
            const mergedValues = {
                ...(existing && typeof existing === 'object' ? existing : {}),
                ...(values && typeof values === 'object' ? values : {}),
            };

            if (isEqual(existing, mergedValues)) {
                return;
            }

            form.setFieldValue(providerSettingsPath, mergedValues);
        },
    });

    useEffect(() => {
        if (!providerHandle) {
            appliedProviderConfigKeyRef.current = null;
            return;
        }

        if (!providerHandle || !schemaConfig) {
            return;
        }

        if (appliedProviderConfigKeyRef.current === providerConfigKey) {
            return;
        }

        appliedProviderConfigKeyRef.current = providerConfigKey;

        if (typeof nestedForm?.store?.reset === 'function') {
            nestedForm.store.reset(initialValues);
        }

        if (!providerSettingsPath || typeof form?.setFieldValue !== 'function') {
            return;
        }

        const existing = typeof form?.getValueAtPath === 'function'
            ? (form.getValueAtPath(providerSettingsPath, {}) || {})
            : {};

        if (!isEqual(existing, initialValues)) {
            form.setFieldValue(providerSettingsPath, initialValues);
        }
    }, [providerHandle, providerConfigKey, schemaConfig, nestedForm, initialValues, providerSettingsPath, form]);

    const content = (
        <div>
            {!providerHandle && (
                <p className="text-sm text-gray-500">
                    {Craft.t('formie', 'Select a payment provider to configure provider settings.')}
                </p>
            )}

            {providerHandle && configLoading && (
                <div className="flex items-center gap-2 text-gray-500">
                    <div className="size-4"><Spinner size="xs" /></div>
                    <span className="text-sm">{Craft.t('formie', 'Loading provider settings…')}</span>
                </div>
            )}

            {providerHandle && !configLoading && configError && (
                <p className="text-sm text-red-600">{configError}</p>
            )}

            {providerHandle && !configLoading && !configError && (schemaConfig?.schema || []).length > 0 && (
                <SchemaFormEngine
                    key={providerConfigKey}
                    form={nestedForm}
                    withoutForm
                    className="space-y-4"
                />
            )}
        </div>
    );

    if (!field.label && !field.instructions) {
        return content;
    }

    return (
        <FieldLayout
            name={field.name}
            label={field.label}
            instructions={field.instructions}
            required={field.required}
        >
            {content}
        </FieldLayout>
    );
}

export { PaymentProviderSettingsField };
