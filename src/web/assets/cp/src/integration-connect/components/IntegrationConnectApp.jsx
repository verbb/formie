import {
    useEffect, useRef, useState,
} from 'react';

import {
    Button,
    Dialog,
    DialogContent,
    Status,
} from '@verbb/plugin-kit-react/components';

import { cn, getErrorMessage } from '@verbb/plugin-kit-react/utils';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faChevronRight, faExclamationTriangle } from '@fortawesome/pro-solid-svg-icons';

const getFormInputs = () => {
    let form = document.getElementById('main-form');

    // allowAdminChanges=false can remove the dedicated form wrapper.
    if (!form) {
        form = document.getElementById('main');
    }

    if (!form) {
        return [];
    }

    return Array.from(form.querySelectorAll('input, select, textarea'));
};

const serializeForm = () => {
    const values = {};

    getFormInputs().forEach((inputElement) => {
        const attribute = inputElement.getAttribute('name');

        if (!attribute) {
            return;
        }

        values[attribute] = inputElement.value;
    });

    return values;
};

const buildConnectionPayload = (settings) => {
    const values = serializeForm();
    const type = String(values.type || settings.integrationType || '');
    const payload = {
        id: values.id || settings.integrationId || '',
        type,
    };

    // Keep CSRF token if present in serialized form.
    if (values.CRAFT_CSRF_TOKEN) {
        payload.CRAFT_CSRF_TOKEN = values.CRAFT_CSRF_TOKEN;
    }

    // Include only the selected integration type settings.
    Object.keys(values).forEach((key) => {
        if (!type || !key.startsWith(`types[${type}]`)) {
            return;
        }

        payload[key] = values[key];
    });

    return payload;
};

export const IntegrationConnectApp = ({ settings }) => {
    const [statusText, setStatusText] = useState(settings.connected ? 'Connected' : 'Not connected');
    const [showModal, setShowModal] = useState(false);
    const [errorMessage, setErrorMessage] = useState(null);
    const [loading, setLoading] = useState(false);
    const [isDirty, setIsDirty] = useState(false);
    const [showDetails, setShowDetails] = useState(false);
    const initialSnapshotRef = useRef('');

    const statusType = statusText === 'Error' ? 'off' : (statusText === 'Connected' ? 'on' : 'gray');

    const closeModal = () => {
        setShowModal(false);
        setShowDetails(false);
    };

    const resolveModalError = (sourceError) => {
        const parsedError = getErrorMessage(sourceError);

        if (parsedError?.text) {
            return parsedError;
        }

        if (typeof sourceError === 'string') {
            return {
                heading: Craft.t('formie', 'Connection error'),
                text: sourceError,
                traceAsString: '',
            };
        }

        return {
            heading: Craft.t('formie', 'Connection error'),
            text: Craft.t('formie', 'An error occurred while checking the integration connection.'),
            traceAsString: '',
        };
    };

    useEffect(() => {
        const getSnapshot = () => {
            return JSON.stringify(serializeForm());
        };

        const syncDirtyState = () => {
            setIsDirty(getSnapshot() !== initialSnapshotRef.current);
        };

        initialSnapshotRef.current = getSnapshot();

        const inputs = getFormInputs();
        inputs.forEach((inputElement) => {
            inputElement.addEventListener('input', syncDirtyState);
            inputElement.addEventListener('change', syncDirtyState);
        });

        return () => {
            inputs.forEach((inputElement) => {
                inputElement.removeEventListener('input', syncDirtyState);
                inputElement.removeEventListener('change', syncDirtyState);
            });
        };
    }, []);

    useEffect(() => {
        const host = document.querySelector('.formie-integration-connect');
        if (!host) {
            return;
        }

        host.classList.toggle('integration-connect-modal-open', showModal);

        return () => {
            host.classList.remove('integration-connect-modal-open');
        };
    }, [showModal]);

    const refresh = async(event) => {
        event.preventDefault();

        if (loading || isDirty) {
            return;
        }

        setShowModal(false);
        setErrorMessage(null);
        setShowDetails(false);
        setLoading(true);
        setStatusText('Connecting...');

        const data = buildConnectionPayload(settings);

        try {
            const response = await Craft.sendActionRequest('POST', 'formie/integrations/check-connection', { data });
            setLoading(false);

            if (response?.data?.message || response?.data?.success === false) {
                setErrorMessage(resolveModalError(response?.data?.message || null));
                setShowModal(true);
                setStatusText('Error');

                return;
            }

            setStatusText('Connected');
        } catch (sourceError) {
            setLoading(false);
            setErrorMessage(resolveModalError(sourceError));
            setShowModal(true);
            setStatusText('Error');
        }
    };

    if (isDirty) {
        return (
            <div className="flex items-center justify-between -mx-[14px] py-2 gap-2 h-[46px]">
                <span className="flex items-center gap-2 text-warning">
                    <FontAwesomeIcon icon={faExclamationTriangle} className="size-3" />
                    {Craft.t('formie', 'Save this integration to connect.')}
                </span>
            </div>
        );
    }

    return (
        <div className="flex items-center justify-between -mx-[14px] py-2 gap-2 h-[46px]">
            <div className="flex items-center gap-2">
                <Status status={statusType} className="mx-1" />
                <span>{Craft.t('formie', statusText)}</span>
            </div>

            <div className="">
                <Button
                    type="button"
                    size="sm"
                    loading={loading}
                    spinnerSize="xs"
                    onClick={refresh}
                    disabled={loading || isDirty}
                >
                    {Craft.t('formie', 'Refresh')}
                </Button>

                <Dialog
                    open={showModal}
                    onOpenChange={(open) => {
                        setShowModal(open);

                        if (!open) {
                            setShowDetails(false);
                        }
                    }}
                >
                    <DialogContent className={cn(
                        'w-[66%] h-[66%]',
                        'min-w-[600px]',
                        'min-h-[400px]',
                    )}
                    showCloseButton={true}
                    autoFocusFirstInput={false}
                    >
                        <div className="flex h-full w-full items-center justify-center p-8 text-center">
                            <div className="flex max-w-[680px] flex-col items-center gap-2 text-sm text-rose-600">
                                <div className="flex items-center justify-center">
                                    <FontAwesomeIcon icon={faExclamationTriangle} className="size-10" />
                                </div>

                                <h3 className="m-0 text-base font-semibold">
                                    {errorMessage?.heading || Craft.t('formie', 'Connection error')}
                                </h3>

                                <p className="m-0 text-xs font-mono">
                                    {errorMessage?.text || Craft.t('formie', 'An unexpected error occurred.')}
                                </p>

                                {errorMessage?.traceAsString ? (
                                    <div className="w-full">
                                        <button
                                            type="button"
                                            className="mx-auto mt-1 flex cursor-pointer items-center gap-1 text-xs"
                                            onClick={() => {
                                                setShowDetails((value) => {
                                                    return !value;
                                                });
                                            }}
                                        >
                                            <FontAwesomeIcon
                                                icon={faChevronRight}
                                                className={cn('size-3 transition-transform', showDetails && 'rotate-90')}
                                            />
                                            {Craft.t('formie', showDetails ? 'Hide details' : 'Show details')}
                                        </button>

                                        {showDetails ? (
                                            <div
                                                className="mt-3 max-h-[180px] overflow-auto rounded-md bg-slate-50 p-3 text-left text-xs"
                                                dangerouslySetInnerHTML={{ __html: errorMessage.traceAsString }}
                                            />
                                        ) : null}
                                    </div>
                                ) : null}
                            </div>
                        </div>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    );
};
