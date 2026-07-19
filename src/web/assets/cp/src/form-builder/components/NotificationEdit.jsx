import { useMemo, useRef, useState } from 'react';

import { cn } from '@verbb/plugin-kit-react/utils';
import { SchemaFormEngine, useSchemaFormEngine } from '@verbb/plugin-kit-react/forms';
import { Button, Dialog } from '@verbb/plugin-kit-react/components';
import { useHandleSyncOnChange } from '@form-builder/hooks/useHandleSyncOnChange';
import { collectSchemaDefaultValues } from '@form-builder/utils/collectSchemaDefaultValues';

import {
    injectReservedHandlesIntoSchema,
    injectReservedHandlesIntoSchemaIndex,
} from '@form-builder/utils/handleValidation';

function NotificationEdit({
    notification, schema, schemaIndex, reservedHandles = [], onSave, onCancel, onDelete,
}) {
    const isNew = !notification?.id;
    const [open, setOpen] = useState(true);
    const pendingCloseRef = useRef(null);

    // Prefer schemaIndex.schema — top-level `schema` on `$cmp: Notifications` is stripped
    // by SchemaFormEngine bookkeeping and never reaches this component as a prop.
    const resolvedSchema = schemaIndex?.schema ?? schema ?? [];

    const schemaWithReservedHandles = useMemo(() => {
        return injectReservedHandlesIntoSchema(resolvedSchema, reservedHandles);
    }, [resolvedSchema, reservedHandles]);
    const schemaIndexWithReservedHandles = useMemo(() => {
        return injectReservedHandlesIntoSchemaIndex(schemaIndex, reservedHandles);
    }, [schemaIndex, reservedHandles]);
    const handleSyncOnChange = useHandleSyncOnChange(schemaWithReservedHandles);

    // Schema field defaults (e.g. dispatchTiming → "default") must seed the store for
    // new notifications — Advanced may never mount before Apply if the user stays on Content.
    const initialValues = useMemo(() => {
        const schemaDefaults = collectSchemaDefaultValues(schemaWithReservedHandles || []);
        return {
            ...schemaDefaults,
            ...(notification || {}),
        };
    }, [notification, schemaWithReservedHandles]);

    const form = useSchemaFormEngine({
        schema: schemaWithReservedHandles,
        schemaIndex: schemaIndexWithReservedHandles,
        defaultValues: initialValues,
        onChange: handleSyncOnChange,
    });

    form.onSuccess((data) => {
        pendingCloseRef.current = { type: 'save', data };
        setOpen(false);
    });

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

        pendingCloseRef.current = { type: 'delete' };
        setOpen(false);
    };

    const handleAfterHide = () => {
        const pending = pendingCloseRef.current;
        pendingCloseRef.current = null;

        if (pending?.type === 'save') {
            onSave(pending.data);
            return;
        }

        if (pending?.type === 'delete') {
            onDelete();
            return;
        }

        onCancel();
    };

    return (
        <Dialog
            open={open}
            withoutBodyPadding
            label={isNew ? Craft.t('formie', 'New Notification') : Craft.t('formie', 'Edit Notification')}
            // Match v1 DialogContent sizing:
            // mobile: calc(100vw/dvh - 24px); md+: 66% with min 600×400.
            className="formie-notification-edit-dialog"
            onPkOpenChange={(event) => {
                setOpen(Boolean(event.detail?.open ?? event.target?.open));
            }}
            onPkAfterHide={handleAfterHide}
        >
            <div className={cn(
                'flex h-full min-h-0 flex-col overflow-hidden',
            )}
            >
                <SchemaFormEngine
                    form={form}
                    className="h-full min-h-0"
                />
            </div>

            {!isNew && (
                <Button
                    slot="footer"
                    type="button"
                    style={{ marginInlineEnd: 'auto' }}
                    onClick={handleDelete}
                >
                    {Craft.t('formie', 'Delete')}
                </Button>
            )}

            <Button
                slot="footer"
                type="button"
                data-dialog-close
            >
                {Craft.t('app', 'Cancel')}
            </Button>

            <Button
                slot="footer"
                type="button"
                variant="primary"
                onClick={handleSave}
            >
                {Craft.t('formie', 'Apply')}
            </Button>
        </Dialog>
    );
}

export { NotificationEdit };
