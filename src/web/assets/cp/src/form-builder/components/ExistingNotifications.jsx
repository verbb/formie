import { takeAtLeast, createItem, generateHandle, findUniqueHandle } from '@verbb/plugin-kit-core';
import { getRichTextText } from '@utils/tiptapUtils';
import React, {
    useEffect, useRef, useState,
} from 'react';

import { Button, Dialog, Icon, Input, Spinner, TiptapInput } from '@verbb/plugin-kit-react/components';

import { cn } from '@verbb/plugin-kit-react/utils';
import { useFormValues } from '@form-builder/hooks/useFormTools';
import { useBuilderActions } from '@form-builder/builder/useBuilderActions';
import { useVariableCategories } from '@form-builder/hooks/useVariableCategories';
import { LargeErrorState, StatePanel } from '@utils';

const getHandleSourceText = (value) => {
    return getRichTextText(value)
        .replace(/\{[^}]*\}/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
};

const META_KEYS_TO_STRIP = new Set([
    'id',
    'formId',
    'uid',
    'errors',
    '_id',
    '_selectionKey',
]);

const getNotificationSelectionKey = (notification = {}) => {
    if (notification?._selectionKey) {
        return notification._selectionKey;
    }

    return `${notification.id || notification.uid || notification.handle || 'notification'}`;
};

const getNotificationIdentityKey = (notification = {}) => {
    if (notification?.id) {
        return `id:${notification.id}`;
    }

    if (notification?.uid) {
        return `uid:${notification.uid}`;
    }

    if (notification?.formId && notification?.handle) {
        return `form:${notification.formId}:handle:${notification.handle}`;
    }

    if (notification?.handle) {
        return `handle:${notification.handle}`;
    }

    return getNotificationSelectionKey(notification);
};

const normalizeNotification = (notification = {}, index = 0, scope = '') => {
    return {
        ...notification,
        _selectionKey: `${scope}:${notification.id || notification.uid || notification.handle || 'notification'}:${index}`,
    };
};

const EXISTING_NOTIFICATIONS_SEARCH_DEBOUNCE_MS = 600;
const ALL_NOTIFICATIONS_SEARCH_MINIMUM = 3;

const ExistingNotifications = ({ onClose }) => {
    const formValues = useFormValues();
    const {
        getNotifications,
        initNotifications,
    } = useBuilderActions();

    const [existingNotifications, setExistingNotifications] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [search, setSearch] = useState('');
    const [selectedForm, setSelectedForm] = useState(null);
    const [selectedNotifications, setSelectedNotifications] = useState([]);
    const [mounted, setMounted] = useState(false);
    const [isLoadingResults, setIsLoadingResults] = useState(false);
    const resultsRequestIdRef = useRef(0);
    const previousSelectedFormKeyRef = useRef(null);
    const searchInputRef = useRef(null);
    const notificationVariableCategories = useVariableCategories({
        groups: ['fieldsVariables', 'staticFormVariables', 'staticGeneralVariables', 'staticSiteVariables'],
        content: 'singleLine',
    });
    const trimmedSearch = search.trim();
    const hasSearch = Boolean(trimmedSearch);
    const meetsSearchMinimum = trimmedSearch.length >= ALL_NOTIFICATIONS_SEARCH_MINIMUM;

    const normalizeExistingNotifications = (items = []) => {
        return (items || []).map((item, itemIndex) => {
            if (item.heading) {
                return item;
            }

            return {
                ...item,
                notifications: (item.notifications || []).map((notification, notificationIndex) => {
                    return normalizeNotification(notification, notificationIndex, item.key || itemIndex);
                }),
            };
        });
    };

    const totalSelected = selectedNotifications.length;
    const submitText = totalSelected > 1
        ? Craft.t('formie', 'Add {num} notifications', { num: totalSelected })
        : totalSelected > 0
            ? Craft.t('formie', 'Add {num} notification', { num: totalSelected })
            : Craft.t('formie', 'Add notification');

    useEffect(() => {
        if (!existingNotifications.length) {
            return;
        }

        const firstTab = existingNotifications.find((item) => {
            return !item.heading;
        });
        const fallbackTab = firstTab || existingNotifications[0];

        setSelectedForm((prev) => {
            if (!prev?.key) {
                return fallbackTab;
            }

            const matchingTab = existingNotifications.find((item) => {
                return !item.heading && item.key === prev.key;
            });

            return matchingTab || fallbackTab;
        });
    }, [existingNotifications]);

    useEffect(() => {
        handleOpen();
    }, []);

    // Content mounts after the dialog's open focus pass (spinner first). Focus search once ready.
    useEffect(() => {
        if (loading || error || !mounted || !existingNotifications.length) {
            return;
        }

        const frameId = window.requestAnimationFrame(() => {
            searchInputRef.current?.focus?.({ preventScroll: true });
        });

        return () => {
            window.cancelAnimationFrame(frameId);
        };
    }, [loading, error, mounted, existingNotifications.length]);

    const fetchExistingNotifications = async() => {
        setError(null);
        setLoading(true);

        const data = {
            formId: formValues.id,
            compact: true,
            includeNotifications: false,
        };

        try {
            const response = await takeAtLeast(500)(
                Craft.sendActionRequest('POST', 'formie/forms/get-existing-notifications', { data }),
            );

            if (response.data?.error) {
                throw new Error(response.data.error);
            }

            const normalized = normalizeExistingNotifications(response.data || []);

            setExistingNotifications(normalized);
            setMounted(true);
        } catch (error) {
            setError(error);
        }

        setLoading(false);
    };

    const fetchExistingNotificationsForSelectedForm = async(formKey, searchTerm = '') => {
        if (!formKey) {
            return;
        }

        if (formKey === '*' && !searchTerm.trim()) {
            setIsLoadingResults(false);

            setExistingNotifications((prev) => {
                return prev.map((form) => {
                    if (form.key !== formKey) {
                        return form;
                    }

                    return {
                        ...form,
                        notifications: [],
                    };
                });
            });

            setSelectedForm((prev) => {
                if (!prev || prev.key !== formKey) {
                    return prev;
                }

                return {
                    ...prev,
                    notifications: [],
                };
            });

            return;
        }

        const requestId = ++resultsRequestIdRef.current;
        setIsLoadingResults(true);

        try {
            const response = await Craft.sendActionRequest('POST', 'formie/forms/get-existing-notifications', {
                data: {
                    formId: formValues.id,
                    compact: true,
                    includeNotifications: true,
                    formKey,
                    search: searchTerm,
                },
            });

            if (requestId !== resultsRequestIdRef.current) {
                return;
            }

            if (response.data?.error) {
                throw new Error(response.data.error);
            }

            const normalizedResults = normalizeExistingNotifications(response.data || []);
            const resolvedResult = normalizedResults.find((form) => {
                return form.key === formKey;
            }) || {
                key: formKey,
                notifications: [],
            };

            setExistingNotifications((prev) => {
                return prev.map((form) => {
                    if (form.key !== formKey) {
                        return form;
                    }

                    return {
                        ...form,
                        notifications: resolvedResult.notifications || [],
                    };
                });
            });

            setSelectedForm((prev) => {
                if (!prev || prev.key !== formKey) {
                    return prev;
                }

                return {
                    ...prev,
                    notifications: resolvedResult.notifications || [],
                };
            });
        } catch (error) {
            if (requestId === resultsRequestIdRef.current) {
                setError(error);
            }
        } finally {
            if (requestId === resultsRequestIdRef.current) {
                setIsLoadingResults(false);
            }
        }
    };

    const handleOpen = () => {
        setLoading(true);
        setIsLoadingResults(false);
        setSelectedNotifications([]);
        setSearch('');
        setError(null);

        if (!existingNotifications.length) {
            fetchExistingNotifications();
        } else {
            setTimeout(() => {
                setMounted(true);
                setLoading(false);
            }, 100);
        }
    };

    const handleClose = () => {
        resultsRequestIdRef.current += 1;
        setIsLoadingResults(false);
        setSelectedNotifications([]);
        setSearch('');
        setError(null);
        setMounted(false);
        onClose();
    };

    const selectTab = (item) => {
        const nextFormKey = item?.key;
        const willFetchResults = Boolean(nextFormKey) && (
            nextFormKey === '*'
                ? meetsSearchMinimum
                : (!hasSearch || meetsSearchMinimum)
        );

        // Set loading before switching tabs to avoid any intermediate
        // empty-state render while transitioning between tab datasets.
        setIsLoadingResults(willFetchResults);
        setSelectedForm(item);
    };

    useEffect(() => {
        const selectedFormKey = selectedForm?.key;

        if (!mounted || !selectedFormKey) {
            return;
        }

        const keyChanged = previousSelectedFormKeyRef.current !== selectedFormKey;
        previousSelectedFormKeyRef.current = selectedFormKey;
        const willFetchResults = selectedFormKey === '*'
            ? meetsSearchMinimum
            : (!hasSearch || meetsSearchMinimum);

        // Show loading for the whole debounce + request window so search feels live
        // (v1 feedback); do not wait until fetchExistingNotificationsForSelectedForm runs.
        setIsLoadingResults(willFetchResults);

        const timeoutId = window.setTimeout(() => {
            if (selectedFormKey === '*') {
                if (!meetsSearchMinimum) {
                    fetchExistingNotificationsForSelectedForm(selectedFormKey, '');
                    return;
                }

                fetchExistingNotificationsForSelectedForm(selectedFormKey, trimmedSearch);
                return;
            }

            if (hasSearch && !meetsSearchMinimum) {
                return;
            }

            fetchExistingNotificationsForSelectedForm(selectedFormKey, trimmedSearch);
        }, keyChanged ? 0 : EXISTING_NOTIFICATIONS_SEARCH_DEBOUNCE_MS);

        return () => {
            window.clearTimeout(timeoutId);
        };
    }, [mounted, selectedForm?.key, trimmedSearch, hasSearch, meetsSearchMinimum]);

    const isNotificationSelected = (notification) => {
        const identityKey = getNotificationIdentityKey(notification);

        return selectedNotifications.some((selectedNotification) => {
            return getNotificationIdentityKey(selectedNotification) === identityKey;
        });
    };

    const notificationSelected = (notification, selected) => {
        const identityKey = getNotificationIdentityKey(notification);

        if (selected) {
            setSelectedNotifications((prev) => {
                const exists = prev.some((selectedNotification) => {
                    return getNotificationIdentityKey(selectedNotification) === identityKey;
                });

                if (exists) {
                    return prev;
                }

                return [...prev, notification];
            });
        } else {
            setSelectedNotifications((prev) => {
                return prev.filter((selectedNotification) => {
                    return getNotificationIdentityKey(selectedNotification) !== identityKey;
                });
            });
        }
    };

    const buildImportedNotification = (notification, existingHandles) => {
        const source = JSON.parse(JSON.stringify(notification || {}));
        const data = Object.fromEntries(Object.entries(source).filter(([key]) => {
            return !META_KEYS_TO_STRIP.has(key);
        }));

        const baseHandle = data.handle || generateHandle(getHandleSourceText(data.name || '') || 'notification');
        const nextHandle = findUniqueHandle(baseHandle, existingHandles);

        existingHandles.push(nextHandle);

        return createItem({
            ...data,
            handle: nextHandle,
            enabled: data.enabled ?? true,
        });
    };

    const addNotifications = () => {
        const existingHandles = (getNotifications() || [])
            .map((notification) => {
                return notification?.handle;
            })
            .filter(Boolean);
        const seenIdentityKeys = new Set();
        const uniqueSelectedNotifications = selectedNotifications.filter((notification) => {
            const identityKey = getNotificationIdentityKey(notification);

            if (seenIdentityKeys.has(identityKey)) {
                return false;
            }

            seenIdentityKeys.add(identityKey);
            return true;
        });

        const nextNotifications = [
            ...(getNotifications() || []),
            ...uniqueSelectedNotifications.map((notification) => {
                return buildImportedNotification(notification, existingHandles);
            }),
        ];

        initNotifications(nextNotifications);
        handleClose();
    };

    return (
        <Dialog
            open
            label={Craft.t('formie', 'Add Existing Notification')}
            withoutBodyPadding
            className="formie-existing-notifications-dialog"
            onPkOpenChange={(event) => {
                if (!(event.detail?.open ?? event.target?.open ?? false)) {
                    handleClose();
                }
            }}
        >
            <div className="flex h-full min-h-0 flex-col overflow-hidden">
                {error && (
                    <LargeErrorState
                        error={error}
                        message={Craft.t('formie', 'Unable to load existing notifications.')}
                        detailsLabel={Craft.t('formie', 'Show error details')}
                        actionLabel={Craft.t('formie', 'Try Again')}
                        onAction={handleOpen}
                        containerClassName="absolute inset-0 z-10 flex items-center justify-center bg-white"
                    />
                )}

                {loading && (
                    <div className="flex h-full min-h-0 items-center justify-center">
                        <Spinner size="lg" />
                    </div>
                )}

                {!loading && !error && mounted && existingNotifications.length && (
                    <div className="flex h-full min-h-0 flex-col md:flex-row">
                        <div className={cn(
                            'relative',
                            'bg-[#f3f7fc]',
                            'border-b md:border-b-0 md:border-r',
                            'border-[rgba(51,64,77,.1)]',
                            'overflow-auto',
                            'w-full max-h-[180px] md:max-h-none md:w-[240px] md:shrink-0',
                            'px-2 pt-3',
                        )}>
                            <div className="space-y-1">
                                {existingNotifications.map((item, index) => {
                                    if (item.heading) {
                                        return (
                                            <div key={`heading-${String(item.heading)}-${index}`} className="mt-4 ml-[10px]">
                                                <h3 className="text-[11px] font-bold text-gray-500 uppercase">
                                                    {item.heading}
                                                </h3>
                                            </div>
                                        );
                                    }

                                    return (
                                        <Button
                                            key={item.key ?? `notification-source-${index}`}
                                            type="button"
                                            size="none"
                                            variant="transparent"
                                            onClick={() => { return selectTab(item); }}
                                            className={cn(
                                                'formie-existing-picker-nav-item',
                                                selectedForm?.key === item.key && 'is-selected',
                                            )}
                                        >
                                            {item.label}
                                        </Button>
                                    );
                                })}
                            </div>
                        </div>

                        <div className="flex min-h-0 flex-1 flex-col">
                            <div className="border-b border-gray-100 p-3 md:p-4">
                                <Input
                                    ref={searchInputRef}
                                    autofocus
                                    value={search}
                                    onChange={(e) => { return setSearch(e.target.value); }}
                                    placeholder={Craft.t('formie', 'Search')}
                                >
                                    <Icon slot="start" icon="magnifying-glass" className="size-4" />
                                </Input>
                            </div>

                            <div className="flex-1 overflow-y-auto p-3 md:p-4">
                                {isLoadingResults ? (
                                    <div className="h-full flex items-center justify-center">
                                        <Spinner size="lg" />
                                    </div>
                                ) : (selectedForm?.key === '*' && !hasSearch) ? (
                                    <StatePanel
                                        variant="info"
                                        showIcon={false}
                                        message={Craft.t('formie', 'Search to browse notifications across all forms and stencils.')}
                                        containerClassName="py-4"
                                        contentClassName="flex flex-col items-center text-center"
                                        messageClassName="mb-0 text-sm text-gray-500"
                                    />
                                ) : (selectedForm?.key === '*' && hasSearch && !meetsSearchMinimum) ? (
                                    <StatePanel
                                        variant="info"
                                        showIcon={false}
                                        message={Craft.t('formie', 'Type at least 3 characters to search all notifications.')}
                                        containerClassName="py-4"
                                        contentClassName="flex flex-col items-center text-center"
                                        messageClassName="mb-0 text-sm text-gray-500"
                                    />
                                ) : (selectedForm && (selectedForm.notifications || []).length > 0) ? (
                                    <div className="grid grid-cols-1 gap-2 md:grid-cols-2">
                                        {(selectedForm.notifications || []).map((notification, notificationIndex) => {
                                            return (
                                                <ExistingNotificationItem
                                                    key={getNotificationSelectionKey(notification) || notificationIndex}
                                                    notification={notification}
                                                    variableCategories={notificationVariableCategories}
                                                    selected={isNotificationSelected(notification)}
                                                    onSelected={(selected) => { return notificationSelected(notification, selected); }}
                                                />
                                            );
                                        })}
                                    </div>
                                ) : (
                                    <StatePanel
                                        variant="empty"
                                        showIcon={false}
                                        message={Craft.t('formie', 'No notifications found.')}
                                        containerClassName="py-4"
                                        contentClassName="flex flex-col items-center text-center"
                                        messageClassName="mb-0 text-sm text-gray-500"
                                    />
                                )}
                            </div>
                        </div>
                    </div>
                )}

                {!loading && !error && !mounted && (
                    <StatePanel
                        variant="empty"
                        showIcon={false}
                        message={Craft.t('formie', 'No existing notifications to select.')}
                        containerClassName="h-full flex items-center justify-center"
                        contentClassName="flex flex-col items-center text-center"
                        messageClassName="mb-0 text-sm text-gray-500"
                    />
                )}
            </div>

            {/* justify-between: Cancel left, primary submit right */}
            <div slot="footer" className="flex w-full justify-between gap-2">
                <Button type="button" onClick={handleClose}>
                    {Craft.t('app', 'Cancel')}
                </Button>

                <Button
                    type="button"
                    variant="primary"
                    disabled={totalSelected === 0}
                    onClick={addNotifications}
                >
                    {submitText}
                </Button>
            </div>
        </Dialog>
    );
};

const ExistingNotificationItem = ({
    notification, variableCategories, selected, onSelected,
}) => {
    return (
        <div
            className={cn(
                'p-3 border border-[2px] rounded-lg cursor-pointer transition-colors',
                selected
                    ? 'border-blue-500 bg-blue-50'
                    : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50',
            )}
            onClick={() => { return onSelected(!selected); }}
        >
            <div className="space-y-1">
                {/* readonly TiptapInput: parses {tokens} into chips; chrome stripped via [readonly]. */}
                <div className="font-medium text-sm">
                    <TiptapInput value={notification.name} readOnly variableCategories={variableCategories} />
                </div>

                <div className="text-xs text-gray-500">
                    <TiptapInput value={notification.subject} readOnly variableCategories={variableCategories} />
                </div>
            </div>
        </div>
    );
};

export { ExistingNotifications };
