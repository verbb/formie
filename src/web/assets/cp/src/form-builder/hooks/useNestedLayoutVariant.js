import { useEffect, useMemo, useState } from 'react';
import { evaluateCondition } from '@verbb/plugin-kit-forms';

const toKeyedMap = (value) => {
    return value && typeof value === 'object' ? value : {};
};

const getNestedLayoutChildProps = (children, values) => {
    const childNodes = Array.isArray(children) ? children : [];
    const nestedLayoutChildren = childNodes.filter((child) => {
        return child?.$cmp === 'NestedLayout';
    });

    if (!nestedLayoutChildren.length) {
        return {};
    }

    const activeChild = nestedLayoutChildren.find((child) => {
        return evaluateCondition(child.if, values || {});
    }) || nestedLayoutChildren[0];

    return toKeyedMap(activeChild?.props);
};

export const useNestedLayoutVariant = ({ form, field }) => {
    const [formValues, setFormValues] = useState(() => {
        return form?.store?.state?.values || {};
    });

    useEffect(() => {
        if (!form?.store?.subscribe) {
            setFormValues(form?.store?.state?.values || {});
            return undefined;
        }

        const updateValues = () => {
            setFormValues(form.store.state.values || {});
        };

        updateValues();
        return form.store.subscribe(updateValues);
    }, [form]);

    const childProps = useMemo(() => {
        return getNestedLayoutChildProps(field?.children, formValues || {});
    }, [field?.children, formValues]);

    const parentType = field?.parentType || childProps.parentType || formValues?.type || form?.getFieldValue?.('type') || null;
    const layoutKey = childProps.layoutKey || field?.layoutKey || 'rows';

    return {
        childProps,
        parentType,
        layoutKey,
    };
};
