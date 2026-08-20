import { findUniqueHandle, generateHandle } from '@verbb/plugin-kit-core';
import { getRichTextText } from '@utils/tiptapUtils';
import {
    useState, useEffect, useMemo, useRef,
} from 'react';

import {
    Button, ButtonGroup, DropdownItem, DropdownMenu, Icon, Status, TiptapInput,
} from '@verbb/plugin-kit-react/components';
import { useFormValues } from '@form-builder/hooks/useFormTools';
import useAppStore from '@form-builder/hooks/useAppStore';
import { useBuilderActions } from '@form-builder/builder/useBuilderActions';
import { NotificationEdit } from './NotificationEdit';
import { ExistingNotifications } from './ExistingNotifications';
import { getDevToolsConfig } from '@form-builder/dev/config';
import { collectNotificationReservedHandles } from '@form-builder/utils/handleValidation';
import { cn } from '@verbb/plugin-kit-react/utils';
import { useVariableCategories } from '@form-builder/hooks/useVariableCategories';
import { StatePanel } from '@utils';
import { announceFormBuilderStatus } from '@form-builder/utils/accessibility';

const getHandleSourceText = (value) => {
    return getRichTextText(value)
        .replace(/\{[^}]*\}/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
};

const NOTIFICATION_META_KEYS_TO_RESET = new Set([
    'id',
    'uid',
    'formId',
    'dateCreated',
    'dateUpdated',
    'errors',
]);

const stripPersistedNotificationMeta = (notification = {}) => {
    return Object.fromEntries(Object.entries(notification).filter(([key]) => {
        return !NOTIFICATION_META_KEYS_TO_RESET.has(key);
    }));
};

function Notifications({ schema, schemaIndex }) {
    const formValues = useFormValues();
    const globalReservedHandles = useAppStore((state) => { return state.reservedHandles || []; });
    const {
        addNotification,
        updateNotification,
        deleteNotification,
        duplicateNotification,
    } = useBuilderActions();
    const [editingNotification, setEditingNotification] = useState(null);
    const [isExistingNotificationsOpen, setIsExistingNotificationsOpen] = useState(false);
    const hasInitialAutoOpenRunRef = useRef(false);
    const builderDevSettings = useMemo(() => {
        if (!import.meta.env.DEV) {
            return null;
        }

        return getDevToolsConfig();
    }, []);
    const shouldAutoOpenInitialNotificationEditor = Boolean(builderDevSettings?.enabled && builderDevSettings?.autoOpenFirstNotification);

    const notifications = formValues.notifications || [];
    const notificationReservedHandles = useMemo(() => {
        const siblingHandles = collectNotificationReservedHandles(notifications, editingNotification?._id);
        return [...new Set([...(globalReservedHandles || []), ...siblingHandles])];
    }, [notifications, editingNotification?._id, globalReservedHandles]);
    const notificationVariableCategories = useVariableCategories({
        groups: ['fieldsVariables', 'staticFormVariables', 'staticGeneralVariables', 'staticSiteVariables'],
        content: 'singleLine',
    });

    useEffect(() => {
        if (!shouldAutoOpenInitialNotificationEditor || hasInitialAutoOpenRunRef.current) {
            return;
        }

        if (notifications.length > 0) {
            setEditingNotification(notifications[0]);
            hasInitialAutoOpenRunRef.current = true;
        }
    }, [shouldAutoOpenInitialNotificationEditor, notifications]);

    const handleEdit = (e, notification) => {
        e.preventDefault();

        setEditingNotification(notification);
    };

    const handleDuplicate = (e, notification) => {
        e.preventDefault();

        // Get all existing handles to check for uniqueness
        const existingHandles = notifications.map((n) => { return n.handle; });

        // Get the name of the notification as a string from the RichText field
        const name = getHandleSourceText(notification.name);

        // Generate a new handle from the notification name
        const baseHandle = generateHandle(name);

        // Find a unique handle based on the generated handle
        const uniqueHandle = findUniqueHandle(baseHandle, existingHandles);

        // Transform callback to update the handle
        const transformCallback = (duplicatedNotification) => {
            const sanitizedNotification = stripPersistedNotificationMeta(duplicatedNotification);

            return {
                ...sanitizedNotification,
                handle: uniqueHandle,
            };
        };

        duplicateNotification(notification, transformCallback);
        announceFormBuilderStatus(Craft.t('formie', '{label} notification duplicated.', {
            label: name || Craft.t('formie', 'Notification'),
        }));
    };

    const handleDelete = (e, notification) => {
        e.preventDefault();

        // Get the name of the notification as a string from the RichText field
        const name = getRichTextText(notification.name);

        // Add confirmation dialog
        const confirmationMessage = Craft.t('formie', 'Are you sure you want to delete "{name}"?', { name });
        const isConfirmed = window.confirm(confirmationMessage);

        if (!isConfirmed) {
            return;
        }

        deleteNotification(notification);
        announceFormBuilderStatus(Craft.t('formie', '{label} notification deleted.', {
            label: name || Craft.t('formie', 'Notification'),
        }));
    };

    const handleCreateNew = (e) => {
        e?.preventDefault?.();

        setEditingNotification({
            enabled: true,
            recipients: 'email',
            // Matches Notification::DISPATCH_TIMING_DEFAULT / schema defaultValue.
            dispatchTiming: 'default',
        });
    };

    const openExistingNotificationsModal = (e) => {
        e?.preventDefault?.();
        setIsExistingNotificationsOpen(true);
    };

    const closeExistingNotificationsModal = () => {
        setIsExistingNotificationsOpen(false);
    };

    const handleSaveNotification = (notification) => {
        const notificationName = getRichTextText(notification?.name) || Craft.t('formie', 'Notification');

        if (editingNotification && editingNotification._id) {
            // Update existing notification
            updateNotification(editingNotification, notification);
            announceFormBuilderStatus(Craft.t('formie', '{label} notification updated.', {
                label: notificationName,
            }));
        } else {
            // Add new notification
            addNotification(notification);
            announceFormBuilderStatus(Craft.t('formie', '{label} notification added.', {
                label: notificationName,
            }));
        }

        setEditingNotification(null);
    };

    const newNotificationActions = (
        <ButtonGroup>
            <Button type="button" variant="primary" onClick={handleCreateNew}>
                <Icon slot="start" icon="plus" className="size-3" />
                {Craft.t('formie', 'New Notification')}
            </Button>
            <DropdownMenu placement="bottom-end">
                <Button
                    slot="trigger"
                    type="button"
                    variant="primary"
                    groupTrigger
                    aria-label={Craft.t('app', 'Open menu')}
                />
                <DropdownItem
                    value="existing"
                    onPkSelect={() => { openExistingNotificationsModal(); }}
                >
                    {Craft.t('formie', 'Select existing notification')}
                </DropdownItem>
            </DropdownMenu>
        </ButtonGroup>
    );

    return (
        <>
            {/*
             * Prefer flex+gap over space-y when this tree also mounts pk-dialog.
             * space-y uses :not(:last-child) on DOM siblings — controlled dialogs stay
             * in-tree (even with a zero layout box) and steal last-child, adding margin
             * under the trigger/content when the modal opens. gap ignores out-of-flow hosts.
             */}
            <div className="flex flex-col gap-4">
            {notifications.length === 0 ? (
                <StatePanel
                    variant="empty"
                    title={Craft.t('formie', 'No notifications created')}
                    message={Craft.t('formie', 'Create a notification to email users when this form is submitted.')}
                    containerClassName="py-20"
                    contentClassName="flex w-[90%] max-w-[560px] flex-col items-center text-center mx-auto"
                    messageClassName="mb-5 text-sm text-gray-500"
                >
                    {newNotificationActions}
                </StatePanel>
            ) : (
                <>
                    <div className="flex justify-between items-center">
                        <h3 className="text-lg font-semibold">{Craft.t('formie', 'Email Notifications')}</h3>

                        {newNotificationActions}
                    </div>

                    <div className="border border-gray-200 rounded-lg overflow-hidden">
                        <table className="w-full">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-sm font-bold text-gray-700">{Craft.t('formie', 'Name')}</th>
                                    <th className="px-4 py-3 text-left text-sm font-bold text-gray-700">{Craft.t('formie', 'Subject')}</th>
                                    <th className="px-4 py-3 text-left text-sm font-bold text-gray-700">{Craft.t('formie', 'Recipients')}</th>
                                    <th className="px-4 py-3 text-left text-sm font-bold text-gray-700 w-0"></th>
                                </tr>
                            </thead>

                            <tbody className="divide-y divide-gray-200">
                                {notifications.map((notification) => {
                                    return (
                                        <tr key={notification._id} className="hover:bg-gray-50">
                                            <td className="px-4 py-3 text-sm text-gray-900">
                                                <div className="flex items-start gap-2">
                                                    <Status
                                                        status={notification.enabled ? 'enabled' : 'disabled'}
                                                        className="mt-[4px]"
                                                    />

                                                    <button
                                                        onClick={(e) => { return handleEdit(e, notification); }}
                                                        className="min-w-0 flex-1 bg-transparent border-none p-0 cursor-pointer font-bold text-blue-600 hover:underline text-left"
                                                    >
                                                        <TiptapInput
                                                            value={notification.name}
                                                            readOnly
                                                            variableCategories={notificationVariableCategories}
                                                            className="[&_.ProseMirror]:text-left"
                                                        />
                                                    </button>
                                                </div>
                                            </td>

                                            <td className="px-4 py-3 text-sm text-gray-900">
                                                <TiptapInput value={notification.subject} readOnly variableCategories={notificationVariableCategories} />
                                            </td>

                                            <td className="px-4 py-3 text-sm text-gray-900">
                                                {notification.recipients === 'conditions' ? (
                                                    <span className="text-gray-500">
                                                        {Craft.t('formie', 'Conditional')}
                                                    </span>
                                                ) : (
                                                    <TiptapInput value={notification.to} readOnly variableCategories={notificationVariableCategories} />
                                                )}
                                            </td>

                                            <td className="px-2 py-0">
                                                {/*
                                                  Icon-only pk-button (no `icon` compact flag): kit makes a
                                                  square hit box from size. Glyph from --pk-btn-icon-size —
                                                  do not Tailwind-size the Icon. sm ≈ 30×30 like v1.
                                                */}
                                                <div className="flex items-center justify-end">
                                                    <Button
                                                        variant="none"
                                                        size="sm"
                                                        aria-label={Craft.t('formie', 'Edit')}
                                                        onClick={(e) => { return handleEdit(e, notification); }}
                                                        className={cn(
                                                            'text-gray-500',
                                                            'hover:text-orange-400',
                                                        )}
                                                    >
                                                        <Icon slot="start" icon="pen-to-square" />
                                                    </Button>
                                                    <Button
                                                        variant="none"
                                                        size="sm"
                                                        aria-label={Craft.t('formie', 'Duplicate')}
                                                        onClick={(e) => { return handleDuplicate(e, notification); }}
                                                        className={cn(
                                                            'text-gray-500',
                                                            'hover:text-blue-500',
                                                        )}
                                                    >
                                                        <Icon slot="start" icon="clone" />
                                                    </Button>
                                                    <Button
                                                        variant="none"
                                                        size="sm"
                                                        aria-label={Craft.t('formie', 'Delete')}
                                                        onClick={(e) => { return handleDelete(e, notification); }}
                                                        className={cn(
                                                            'text-gray-500',
                                                            'hover:text-red-500',
                                                        )}
                                                    >
                                                        <Icon slot="start" icon="xmark" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </>
            )}
            </div>

            {/* Overlay hosts sit outside the spaced stack so they never participate in sibling CSS. */}
            {editingNotification !== null && (
                <NotificationEdit
                    notification={editingNotification}
                    schema={schema}
                    schemaIndex={schemaIndex}
                    reservedHandles={notificationReservedHandles}
                    onSave={handleSaveNotification}
                    onCancel={() => {
                        setEditingNotification(null);
                    }}
                    onDelete={() => {
                        const notificationName = getRichTextText(editingNotification?.name) || Craft.t('formie', 'Notification');
                        deleteNotification(editingNotification);
                        announceFormBuilderStatus(Craft.t('formie', '{label} notification deleted.', {
                            label: notificationName,
                        }));
                        setEditingNotification(null);
                    }}
                />
            )}

            {isExistingNotificationsOpen && (
                <ExistingNotifications onClose={closeExistingNotificationsModal} />
            )}
        </>
    );
}

export { Notifications };
