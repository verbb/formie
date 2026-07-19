import { Fragment, useMemo } from 'react';

import { Combobox, Option, OptionGroup } from '@verbb/plugin-kit-react/components';

export const ALL_FORMS_VALUE = '*';

const toStringValue = (value) => String(value ?? '');

const normalizeFormOption = (option) => ({
    label: option.label,
    value: toStringValue(option.value),
    handle: option.handle ? String(option.handle) : null,
    groupId: option.groupId ?? null,
    groupName: option.groupName ? String(option.groupName) : null,
    isAllOption: false,
});

const normalizeFormIds = (selectedValues, includeAllOption) => {
    const selected = Array.isArray(selectedValues) ? selectedValues.map(String) : [];

    if (includeAllOption && selected.includes(ALL_FORMS_VALUE)) {
        return ALL_FORMS_VALUE;
    }

    if (!selected.length) {
        return [];
    }

    return selected
        .map((value) => parseInt(value, 10))
        .filter(Boolean);
};

const getGroupKey = (option) => {
    if (option.groupId) {
        return `group:${option.groupId}`;
    }

    return 'ungrouped';
};

const getGroupLabel = (option, key) => {
    if (key === 'ungrouped') {
        return Craft.t('formie', 'Ungrouped');
    }

    return option.groupName || Craft.t('formie', 'Group');
};

const getFormOptionLabel = (item, duplicateTitles) => {
    if (item.isAllOption) {
        return item.label;
    }

    if (duplicateTitles.has(item.label) && item.handle) {
        return `${item.label} (${item.handle})`;
    }

    return item.label;
};

export const ReportFormsSelect = ({
    formIds = [],
    options = [],
    includeAllOption = false,
    disabled = false,
    placeholder,
    emptyMessage,
    onChange,
}) => {
    const groupedItems = useMemo(() => {
        const formOptions = (options || []).filter((option) => option.value && option.value !== ALL_FORMS_VALUE);
        const groups = [];
        const groupOrder = [];
        const groupMap = new Map();

        formOptions.forEach((option) => {
            const item = normalizeFormOption(option);
            const key = getGroupKey(option);

            if (!groupMap.has(key)) {
                groupMap.set(key, {
                    value: key,
                    label: getGroupLabel(option, key),
                    items: [],
                });
                groupOrder.push(key);
            }

            groupMap.get(key).items.push(item);
        });

        if (includeAllOption) {
            groups.push({
                value: '__all__',
                label: '',
                items: [{
                    label: Craft.t('formie', 'All Forms'),
                    value: ALL_FORMS_VALUE,
                    handle: null,
                    isAllOption: true,
                }],
            });
        }

        groupOrder.forEach((key) => {
            const group = groupMap.get(key);

            if (group?.items.length) {
                groups.push(group);
            }
        });

        return groups;
    }, [includeAllOption, options]);

    const allItems = useMemo(() => {
        return groupedItems.flatMap((group) => group.items);
    }, [groupedItems]);

    const duplicateTitles = useMemo(() => {
        const counts = new Map();

        allItems.forEach((item) => {
            if (item.isAllOption) {
                return;
            }

            counts.set(item.label, (counts.get(item.label) || 0) + 1);
        });

        return new Set(
            [...counts.entries()]
                .filter(([, count]) => count > 1)
                .map(([label]) => label),
        );
    }, [allItems]);

    // pk-combobox is string-valued in multiple mode; feed it the selected string values.
    const selectedValues = useMemo(() => {
        const isAllSelected = formIds === ALL_FORMS_VALUE
            || (Array.isArray(formIds) && formIds.length === 1 && String(formIds[0]) === ALL_FORMS_VALUE);

        if (isAllSelected) {
            return includeAllOption ? [ALL_FORMS_VALUE] : [];
        }

        if (!Array.isArray(formIds) || formIds.length === 0) {
            return [];
        }

        const knownValues = new Set(allItems.map((item) => item.value));

        return formIds.map(String).filter((value) => knownValues.has(value));
    }, [allItems, formIds, includeAllOption]);

    const handleChange = (event) => {
        const raw = event.detail?.value;
        const values = Array.isArray(raw) ? raw : (raw ? [raw] : []);

        onChange?.(normalizeFormIds(values, includeAllOption));
    };

    return (
        <Combobox
            multiple
            width="full"
            className="w-full"
            disabled={disabled}
            values={selectedValues}
            placeholder={placeholder || Craft.t('formie', 'Search forms…')}
            emptyMessage={emptyMessage || Craft.t('formie', 'No forms found.')}
            onPkChange={handleChange}
        >
            {groupedItems.map((group) => {
                // Chip labels come from each option's rendered label, so bake the
                // disambiguating handle into it here (matching the old chip/highlight text).
                const optionNodes = group.items.map((item) => (
                    <Option key={toStringValue(item.value)} value={toStringValue(item.value)}>
                        {getFormOptionLabel(item, duplicateTitles)}
                    </Option>
                ));

                // The "All Forms" pseudo-group has no label — render it flat.
                if (!group.label) {
                    return <Fragment key={group.value}>{optionNodes}</Fragment>;
                }

                return (
                    <OptionGroup key={group.value} label={group.label}>
                        {optionNodes}
                    </OptionGroup>
                );
            })}
        </Combobox>
    );
};
