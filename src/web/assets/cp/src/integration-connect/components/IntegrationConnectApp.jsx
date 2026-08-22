import { getErrorMessage } from '@verbb/plugin-kit-core';
import {
    useEffect, useRef, useState,
} from 'react';

import { Button, Dialog, Icon, Status } from '@verbb/plugin-kit-react/components';

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

    // Honour custom Craft csrfTokenName values from the CP form.
    const csrfTokenName = typeof Craft !== 'undefined' && Craft.csrfTokenName
        ? Craft.csrfTokenName
        : null;

    if (csrfTokenName && values[csrfTokenName]) {
        payload[csrfTokenName] = values[csrfTokenName];
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

    const statusType = statusText === 'Error'
        ? 'off'
        : (statusText === 'Connected' ? 'on' : 'disabled');
    const statusLabelMuted = statusText !== 'Connected' && statusText !== 'Error';
    // OAuth meta row uses “Connect”; API check-connection uses “Refresh” once linked.
    const actionLabel = statusText === 'Connected'
        ? Craft.t('formie', 'Refresh')
        : Craft.t('formie', 'Connect');

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

    // Host is Craft `.field.lightswitch-field` — use `.heading` / `.input` so meta padding matches OAuth Connect.
    if (isDirty) {
        return (
            <div className="heading">
                <span className="warning with-icon">
                    {Craft.t('formie', 'Save this integration to connect.')}
                </span>
            </div>
        );
    }

    return (
        <>
            <div className="heading">
                <Status
                    status={statusType}
                    className="formie-integration-connect__status-icon"
                /><span className={statusLabelMuted ? 'light' : undefined}>{Craft.t('formie', statusText)}</span>
            </div>

            <div className="input ltr">
                {/* default = Craft .btn fill (slate); secondary is dark gray-on-white. */}
                <Button
                    type="button"
                    size="xs"
                    variant="default"
                    loading={loading}
                    spinnerSize="xs"
                    onClick={refresh}
                    disabled={loading || isDirty}
                >
                    {actionLabel}
                </Button>
            </div>

            {/*
             * Match formie-plugin-repo-alt DialogContent error panel:
             * 66% × 66% (600×400 floor), no title chrome, centered icon/heading/mono + Show details.
             */}
            <Dialog
                open={showModal}
                withoutHeader
                className="formie-integration-connect-dialog"
                onPkOpenChange={(event) => {
                    const open = Boolean(event.detail?.open);
                    setShowModal(open);

                    if (!open) {
                        setShowDetails(false);
                    }
                }}
            >
                <Button
                    type="button"
                    variant="none"
                    size="none"
                    icon
                    data-dialog="close"
                    className="formie-integration-connect-dialog-close"
                    aria-label={Craft.t('app', 'Close')}
                >
                    <Icon icon="xmark" />
                </Button>

                <div className="formie-integration-connect-error">
                    <div className="formie-integration-connect-error__stack">
                        <div className="formie-integration-connect-error__icon">
                            <Icon icon="triangle-exclamation" />
                        </div>

                        <h3 className="formie-integration-connect-error__heading">
                            {errorMessage?.heading || Craft.t('formie', 'Connection error')}
                        </h3>

                        <p className="formie-integration-connect-error__message">
                            {errorMessage?.text || Craft.t('formie', 'An unexpected error occurred.')}
                        </p>

                        {errorMessage?.traceAsString ? (
                            <div className="formie-integration-connect-error__details">
                                <button
                                    type="button"
                                    className="formie-integration-connect-error__details-toggle"
                                    onClick={() => {
                                        setShowDetails((value) => {
                                            return !value;
                                        });
                                    }}
                                >
                                    <Icon
                                        icon="chevron-right"
                                        className={showDetails ? 'is-open' : undefined}
                                    />
                                    {Craft.t('formie', showDetails ? 'Hide details' : 'Show details')}
                                </button>

                                {showDetails ? (
                                    <div
                                        className="formie-integration-connect-error__trace"
                                        dangerouslySetInnerHTML={{ __html: errorMessage.traceAsString }}
                                    />
                                ) : null}
                            </div>
                        ) : null}
                    </div>
                </div>
            </Dialog>
        </>
    );
};
