import { useEffect, useMemo, useState } from 'react';

import {
    Button,
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    EditableTable,
} from '@verbb/plugin-kit-react/components';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';
import { cn } from '@verbb/plugin-kit-react/utils';

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
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className={cn(
                'w-[35%] h-[35%]',
                'max-w-auto',
                'min-w-[200px]',
                'min-h-[200px]',
            )}>
                <DialogHeader>
                    <DialogTitle>{t('Dropdown Options')}</DialogTitle>
                    <DialogDescription>
                        {columnHeading
                            ? t('Define the available options for “{heading}”.', { heading: columnHeading })
                            : t('Define the available options.')}
                    </DialogDescription>
                </DialogHeader>

                <div className="h-full overflow-y-auto">
                    <div className="grid grid-cols-1 gap-4 p-4">
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
                </div>

                <DialogFooter className="gap-2">
                    <Button type="button" onClick={() => { onOpenChange(false); }}>
                        {t('Cancel')}
                    </Button>

                    <Button type="button" variant="primary" onClick={handleSave}>
                        {t('Done')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export { FormieTableColumnOptionsDialog };
