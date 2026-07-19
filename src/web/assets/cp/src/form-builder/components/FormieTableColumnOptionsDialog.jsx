import { useEffect, useMemo, useState } from 'react';

import { useTranslation } from '@verbb/plugin-kit-react/hooks';
import { Button, Dialog, EditableTable } from '@verbb/plugin-kit-react/components';

const normalizeOptionRows = (options = []) => {
    if (!Array.isArray(options)) {
        return [];
    }

    return options.map((option) => {
        return {
            label: String(option?.label ?? ''),
            value: String(option?.value ?? ''),
            isDefault: Boolean(option?.default ?? option?.isDefault),
        };
    });
};

function FormieTableColumnOptionsDialog({
    open,
    onOpenChange,
    columnHeading,
    columnOptions = [],
    onSave,
}) {
    const t = useTranslation();
    const [rows, setRows] = useState(() => { return normalizeOptionRows(columnOptions); });

    useEffect(() => {
        if (!open) {
            return;
        }

        setRows(normalizeOptionRows(columnOptions));
    }, [columnOptions, open]);

    const tableColumns = useMemo(() => {
        return [
            {
                name: 'label',
                label: t('Option Label'),
                type: 'text',
                required: true,
            },
            {
                name: 'value',
                label: t('Value'),
                type: 'value',
                source: 'label',
            },
            {
                name: 'isDefault',
                label: t('Default?'),
                type: 'checkbox',
                thin: true,
            },
        ];
    }, [t]);

    const handleSave = () => {
        const nextOptions = rows
            .map((row) => {
                return {
                    label: String(row?.label ?? '').trim(),
                    value: String(row?.value ?? '').trim(),
                    default: Boolean(row?.isDefault),
                };
            })
            .filter((row) => { return row.label !== '' || row.value !== ''; });

        onSave(nextOptions);
        onOpenChange(false);
    };

    return (
        <Dialog
            open={open}
            label={t('Dropdown Options')}
            onPkOpenChange={(event) => { onOpenChange(event.detail?.open ?? event.target?.open ?? false); }}
        >
            <div className="grid grid-cols-1 gap-4">
                <p className="text-sm text-slate-600">
                    {columnHeading
                        ? t('Define the available options for “{heading}”.', { heading: columnHeading })
                        : t('Define the available options.')}
                </p>

                <div className="space-y-4">
                    <EditableTable
                        columns={tableColumns}
                        rows={rows}
                        onChange={setRows}
                        addRowLabel={t('Add an option')}
                        allowReorder={true}
                        allowAdd={true}
                        allowDelete={true}
                    />
                </div>
            </div>

            <Button slot="footer" type="button" onClick={() => { onOpenChange(false); }}>
                {t('Cancel')}
            </Button>

            <Button slot="footer" type="button" variant="primary" onClick={handleSave}>
                {t('Done')}
            </Button>
        </Dialog>
    );
}

export { FormieTableColumnOptionsDialog };
