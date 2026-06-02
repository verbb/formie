import {
    createContext, useCallback, useContext, useEffect, useMemo, useState,
} from 'react';
import { get as getValue } from 'lodash-es';
import { hasSchemaErrorsCached } from '@verbb/plugin-kit-react/utils/schemaIndexCache';

const FormBuilderFormContext = createContext(null);

const normalizeErrors = (errors) => {
    return errors && typeof errors === 'object' ? errors : {};
};

const hasErrorValue = (value) => {
    if (Array.isArray(value)) {
        return value.length > 0;
    }
    if (!value) {
        return false;
    }
    if (typeof value === 'object') {
        return Array.isArray(value.errors) ? value.errors.length > 0 : Boolean(value.errors);
    }
    return Boolean(value);
};

export const FormBuilderFormProvider = ({ form, children }) => {
    const [values, setValues] = useState(() => {
        return form?.store?.state?.values ?? {};
    });
    const [errors, setErrors] = useState(() => {
        return normalizeErrors(form?.getErrorMapFields?.());
    });

    useEffect(() => {
        if (!form?.store?.subscribe) {
            setValues(form?.store?.state?.values ?? {});
            setErrors(normalizeErrors(form?.getErrorMapFields?.()));
            return undefined;
        }

        const update = () => {
            setValues(form.store.state.values);
            setErrors(normalizeErrors(form.getErrorMapFields?.()));
        };

        update();
        return form.store.subscribe(update);
    }, [form]);

    const hasErrorsForPrefix = useCallback((prefix) => {
        if (!prefix) {
            return false;
        }
        return Object.entries(errors).some(([key, value]) => {
            if (!key.startsWith(prefix)) {
                return false;
            }
            return hasErrorValue(value);
        });
    }, [errors]);

    const hasErrorsForFieldNames = useCallback((fieldNames) => {
        if (!Array.isArray(fieldNames) || fieldNames.length === 0) {
            return false;
        }
        return fieldNames.some((fieldName) => {
            const fieldError = errors?.[fieldName];
            return hasErrorValue(fieldError);
        });
    }, [errors]);

    const hasErrorsForSchema = useCallback((schemaNode) => {
        return hasSchemaErrorsCached(errors, schemaNode);
    }, [errors]);

    const getValueAtPath = useCallback((path, fallback = undefined) => {
        return getValue(values, path, fallback);
    }, [values]);

    const contextValue = useMemo(() => {
        return {
            form,
            values,
            errors,
            hasErrorsForPrefix,
            hasErrorsForFieldNames,
            hasErrorsForSchema,
            getValueAtPath,
        };
    }, [form, values, errors, hasErrorsForPrefix, hasErrorsForFieldNames, hasErrorsForSchema, getValueAtPath]);

    return (
        <FormBuilderFormContext.Provider value={contextValue}>
            {children}
        </FormBuilderFormContext.Provider>
    );
};

export const useFormBuilderForm = () => {
    const context = useContext(FormBuilderFormContext);
    if (!context) {
        throw new Error('useFormBuilderForm must be used within FormBuilderFormProvider');
    }
    return context;
};
