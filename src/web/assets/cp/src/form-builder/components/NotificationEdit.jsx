import { useMemo } from 'react';

import { Button } from '@verbb/plugin-kit-react/components';
import { SchemaFormEngine, useSchemaFormEngine } from '@verbb/plugin-kit-react/forms';
import { useHandleSyncOnChange } from '@form-builder/hooks/useHandleSyncOnChange';

import {
    injectReservedHandlesIntoSchema,
    injectReservedHandlesIntoSchemaIndex,
} from '@form-builder/utils/handleValidation';

import {
    DialogFooter,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@verbb/plugin-kit-react/components';

import { cn } from '@verbb/plugin-kit-react/utils';

function NotificationEdit({
    notification, schema, schemaIndex, reservedHandles = [], onSave, onCancel, onDelete,
}) {
    // const formRef = useRef(null);
    const isNew = !notification?.id;

    const schemaWithReservedHandles = useMemo(() => {
        return injectReservedHandlesIntoSchema(schema, reservedHandles);
    }, [schema, reservedHandles]);
    const schemaIndexWithReservedHandles = useMemo(() => {
        return injectReservedHandlesIntoSchemaIndex(schemaIndex, reservedHandles);
    }, [schemaIndex, reservedHandles]);
    const handleSyncOnChange = useHandleSyncOnChange(schemaWithReservedHandles);

    const form = useSchemaFormEngine({
        schema: schemaWithReservedHandles,
        schemaIndex: schemaIndexWithReservedHandles,
        defaultValues: notification,
        onChange: handleSyncOnChange,
    });

    form.onSuccess((data) => {
        onSave(data);
    });

    const handleCancel = () => {
        onCancel();
    };

    const handleSave = (e) => {
        e.preventDefault();

        form.handleSubmit();
    };

    const handleDelete = () => {
        const confirmationMessage = Craft.t('formie', 'Are you sure you want to delete "{name}"?', {
            name: notification.name,
        });

        const isConfirmed = window.confirm(confirmationMessage);

        if (!isConfirmed) {
            return;
        }

        onDelete();
    };

    return (
        <DialogContent className={cn(
            'w-[calc(100vw-24px)] h-[calc(100dvh-24px)]',
            'min-w-0 min-h-0 max-w-none',
            'md:w-[66%] md:h-[66%]',
            'md:min-w-[600px] md:min-h-[400px]',
        )}
        >
            <DialogHeader>
                <DialogTitle>
                    {isNew ? Craft.t('formie', 'New Notification') : Craft.t('formie', 'Edit Notification')}
                </DialogTitle>

                <DialogDescription className="hidden">
                    {Craft.t('formie', 'Edit the notification for this form.')}
                </DialogDescription>
            </DialogHeader>

            <div className="flex-1 min-h-0 overflow-hidden">
                <SchemaFormEngine
                    form={form}
                    className="h-full"
                />
            </div>

            <DialogFooter className={cn(
                'flex flex-row gap-2',
                isNew ? 'justify-end' : 'justify-between',
            )}
            >
                {!isNew && (
                    <Button
                        type="button"
                        onClick={handleDelete}
                    >
                        {Craft.t('formie', 'Delete')}
                    </Button>
                )}

                <div className="flex flex-row justify-end gap-2">
                    <Button
                        type="button"
                        onClick={handleCancel}
                    >
                        {Craft.t('formie', 'Cancel')}
                    </Button>

                    <Button
                        type="button"
                        variant="primary"
                        onClick={handleSave}
                    >
                        {Craft.t('formie', 'Apply')}
                    </Button>
                </div>
            </DialogFooter>
        </DialogContent>
    );
}

export { NotificationEdit };
