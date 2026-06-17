import { Fragment, useMemo, useState } from 'react';

import {
    Combobox,
    ComboboxChip,
    ComboboxChips,
    ComboboxChipsInput,
    ComboboxCollection,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxHighlightedText,
    ComboboxItem,
    ComboboxLabel,
    ComboboxList,
    ComboboxValue,
    useComboboxAnchor,
} from '@verbb/plugin-kit-react/components';

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
    const [searchValue, setSearchValue] = useState('');
    const anchor = useComboboxAnchor();

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

    const getChipLabel = (item) => getFormOptionLabel(item, duplicateTitles);

    const selectedItems = useMemo(() => {
        if (formIds === ALL_FORMS_VALUE || formIds === ['*']) {
            return includeAllOption
                ? allItems.filter((item) => item.value === ALL_FORMS_VALUE)
                : [];
        }

        if (!Array.isArray(formIds) || formIds.length === 0) {
            return [];
        }

        const selectedValues = new Set(formIds.map(String));

        return allItems.filter((item) => selectedValues.has(item.value));
    }, [allItems, formIds, includeAllOption]);

    const handleChange = (nextValue) => {
        const selected = Array.isArray(nextValue) ? nextValue : [];
        const values = selected.map((item) => item.value);

        onChange?.(normalizeFormIds(values, includeAllOption));
    };

    return (
        <Combobox
            multiple
            items={groupedItems}
            value={selectedItems}
            disabled={disabled}
            onValueChange={handleChange}
            onInputValueChange={setSearchValue}
            onOpenChange={(open) => {
                if (!open) {
                    setSearchValue('');
                }
            }}
            itemToStringLabel={(item) => {
                if (!item || item.isAllOption) {
                    return item?.label ?? '';
                }

                return `${item.label} ${item.handle ?? ''}`.trim();
            }}
            itemToStringValue={(item) => toStringValue(item?.value)}
        >
            <ComboboxChips ref={anchor}>
                <ComboboxValue>
                    {(items) => (
                        <Fragment>
                            {items.map((item) => (
                                <ComboboxChip key={toStringValue(item.value)}>
                                    {getChipLabel(item)}
                                </ComboboxChip>
                            ))}
                            <ComboboxChipsInput placeholder={placeholder || Craft.t('formie', 'Search forms…')} />
                        </Fragment>
                    )}
                </ComboboxValue>
            </ComboboxChips>

            <ComboboxContent anchor={anchor}>
                <ComboboxEmpty>{emptyMessage || Craft.t('formie', 'No forms found.')}</ComboboxEmpty>

                <ComboboxList>
                    <ComboboxCollection>
                        {(group) => (
                            <ComboboxGroup key={group.value}>
                                {group.label ? (
                                    <ComboboxLabel>{group.label}</ComboboxLabel>
                                ) : null}

                                {group.items.map((item) => (
                                    <ComboboxItem key={toStringValue(item.value)} value={item}>
                                        <ComboboxHighlightedText
                                            text={getFormOptionLabel(item, duplicateTitles)}
                                            search={searchValue}
                                        />
                                    </ComboboxItem>
                                ))}
                            </ComboboxGroup>
                        )}
                    </ComboboxCollection>
                </ComboboxList>
            </ComboboxContent>
        </Combobox>
    );
};
