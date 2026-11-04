import { useEffect, useMemo, useRef, useState } from 'react';

import { buildUniqueHandleFromSource, getErrorMessage } from '@verbb/plugin-kit-core';
import { Alert, Button, Dialog, Icon, Input } from '@verbb/plugin-kit-react/components';
import { FieldLayout } from '@verbb/plugin-kit-react/forms';
import { cn } from '@verbb/plugin-kit-react/utils';
import { ReportFormsSelect } from '@reports/components/ReportFormsSelect';

/** pk-input Enter → CustomEvent on the nearest schema form (not Craft #main). */
const PK_IMPLICIT_SUBMIT_EVENT = 'pk-implicit-submit';

const buildHandle = (sourceValue, reservedHandles) => {
    return buildUniqueHandleFromSource({
        sourceValue,
        reservedHandles,
    });
};

export const CreateReportModal = ({
    open,
    onOpenChange,
    createActionUrl,
    csrfTokenName,
    csrfTokenValue,
    reservedHandles = [],
    reportHandles = [],
    formOptions = null,
    formOptionsUrl,
    onShown,
    onCreated,
}) => {
    const [name, setName] = useState('');
    const [handle, setHandle] = useState('');
    const [formIds, setFormIds] = useState([]);
    const [nameErrors, setNameErrors] = useState([]);
    const [handleErrors, setHandleErrors] = useState([]);
    const [formIdsErrors, setFormIdsErrors] = useState([]);
    const [formError, setFormError] = useState(null);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [advancedVisible, setAdvancedVisible] = useState(false);
    const [shown, setShown] = useState(false);
    const [loadedFormOptions, setLoadedFormOptions] = useState(formOptions);
    const [formOptionsError, setFormOptionsError] = useState(null);
    const [formOptionsAttempt, setFormOptionsAttempt] = useState(0);
    const lastGeneratedHandleRef = useRef('');
    const formRef = useRef(null);
    // Guard overlapping Enter / footer submit / requestSubmit races.
    const submitInFlightRef = useRef(false);

    const toGeneralError = (source, fallbackText = null) => {
        const errorMessage = getErrorMessage(source);

        return errorMessage?.text || fallbackText || Craft.t('formie', 'Couldn’t create report.');
    };

    const uniqueHandles = useMemo(() => {
        return [...new Set([
            ...reservedHandles,
            ...reportHandles,
        ].filter(Boolean).map((value) => { return String(value).toLowerCase(); }))];
    }, [reportHandles, reservedHandles]);

    const selectableFormOptions = useMemo(() => {
        return (loadedFormOptions || []).filter((option) => { return option.value && option.value !== '*'; });
    }, [loadedFormOptions]);

    useEffect(() => {
        if (!open || !shown || loadedFormOptions !== null) {
            return undefined;
        }

        // Wait for the dialog to be visible, and discard responses after dismissal.
        const controller = new AbortController();
        setFormOptionsError(null);

        const loadFormOptions = async () => {
            try {
                const response = await fetch(formOptionsUrl, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: controller.signal,
                });
                const payload = await response.json();

                if (!response.ok || !Array.isArray(payload.formOptions)) {
                    throw new Error(Craft.t('formie', 'Unable to load forms.'));
                }

                if (!controller.signal.aborted) {
                    setLoadedFormOptions(payload.formOptions);
                }
            } catch {
                if (!controller.signal.aborted) {
                    setFormOptionsError(Craft.t('formie', 'Unable to load forms.'));
                }
            }
        };

        loadFormOptions();

        return () => { controller.abort(); };
    }, [open, shown, loadedFormOptions, formOptionsUrl, formOptionsAttempt]);

    useEffect(() => {
        if (!open) {
            setShown(false);
            setFormOptionsError(null);
            setName('');
            setHandle('');
            setFormIds([]);
            setNameErrors([]);
            setHandleErrors([]);
            setFormIdsErrors([]);
            setFormError(null);
            setIsSubmitting(false);
            setAdvancedVisible(false);
            submitInFlightRef.current = false;
            lastGeneratedHandleRef.current = '';
            return;
        }

        if (selectableFormOptions.length === 1) {
            setFormIds([String(selectableFormOptions[0].value)]);
        }
    }, [open, selectableFormOptions]);

    // Server/client handle errors live under Advanced — keep that section open.
    useEffect(() => {
        if (handleErrors.length > 0) {
            setAdvancedVisible(true);
        }
    }, [handleErrors]);

    const validateClient = () => {
        const nextNameErrors = [];
        const nextHandleErrors = [];
        const nextFormIdsErrors = [];

        if (!name.trim()) {
            nextNameErrors.push(Craft.t('app', 'Name cannot be blank.'));
        }

        const normalizedHandle = handle.trim();

        if (!normalizedHandle) {
            nextHandleErrors.push(Craft.t('app', 'Handle cannot be blank.'));
        } else if (uniqueHandles.includes(normalizedHandle.toLowerCase())) {
            nextHandleErrors.push(Craft.t('app', 'Handle has already been taken.'));
        }

        const normalizedFormIds = (formIds || []).map((value) => { return parseInt(String(value), 10); }).filter(Boolean);

        if (!normalizedFormIds.length) {
            nextFormIdsErrors.push(Craft.t('formie', 'Choose at least one form.'));
        }

        setNameErrors(nextNameErrors);
        setHandleErrors(nextHandleErrors);
        setFormIdsErrors(nextFormIdsErrors);

        return nextNameErrors.length === 0
            && nextHandleErrors.length === 0
            && nextFormIdsErrors.length === 0;
    };

    const onNameChange = (event) => {
        const nextName = event.target.value;
        setName(nextName);
        setNameErrors([]);
        setFormError(null);

        // Keep the handle synced until a user manually edits it (New Form pattern).
        const shouldSyncHandle = !handle || handle === lastGeneratedHandleRef.current;

        if (!shouldSyncHandle) {
            return;
        }

        const nextHandle = buildHandle(nextName, uniqueHandles);
        lastGeneratedHandleRef.current = nextHandle;
        setHandle(nextHandle);
        setHandleErrors([]);
    };

    const onHandleChange = (event) => {
        setHandle(event.target.value);
        setHandleErrors([]);
        setFormError(null);
    };

    const submitCreate = async () => {
        if (submitInFlightRef.current || !selectableFormOptions.length) {
            return;
        }

        if (!validateClient()) {
            // handleErrors effect opens Advanced when the handle itself is invalid.
            return;
        }

        submitInFlightRef.current = true;
        setIsSubmitting(true);
        setFormError(null);
        setNameErrors([]);
        setHandleErrors([]);
        setFormIdsErrors([]);

        const normalizedFormIds = (formIds || []).map((value) => { return parseInt(String(value), 10); }).filter(Boolean);

        try {
            const body = new FormData();
            body.append(csrfTokenName, csrfTokenValue);
            body.append('name', name.trim());
            body.append('handle', handle.trim());
            normalizedFormIds.forEach((formId) => {
                body.append('formIds[]', String(formId));
            });

            const response = await fetch(createActionUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                },
                body,
            });

            const payload = await response.json();

            if (!response.ok || !payload.success) {
                setNameErrors((payload.errors?.name || []).map(String));
                setHandleErrors((payload.errors?.handle || []).map(String));
                setFormIdsErrors((payload.errors?.formIds || []).map(String));

                if (
                    !payload.errors?.name?.length
                    && !payload.errors?.handle?.length
                    && !payload.errors?.formIds?.length
                ) {
                    setFormError(toGeneralError(payload));
                }

                submitInFlightRef.current = false;
                setIsSubmitting(false);
                return;
            }

            onCreated(payload);
            submitInFlightRef.current = false;
            setIsSubmitting(false);
        } catch (error) {
            console.error(error);
            setFormError(toGeneralError(error));
            submitInFlightRef.current = false;
            setIsSubmitting(false);
        }
    };

    const handleSubmit = (event) => {
        event.preventDefault();
        submitCreate();
    };

    // pk-input Enter → pk-implicit-submit (no SchemaFormEngine in this modal).
    useEffect(() => {
        const form = formRef.current;

        if (!form || !open) {
            return undefined;
        }

        const onImplicitSubmit = (event) => {
            event.preventDefault();
            submitCreate();
        };

        form.addEventListener(PK_IMPLICIT_SUBMIT_EVENT, onImplicitSubmit);

        return () => {
            form.removeEventListener(PK_IMPLICIT_SUBMIT_EVENT, onImplicitSubmit);
        };
    });

    return (
        <Dialog
            open={open}
            label={Craft.t('formie', 'New report')}
            onPkHide={() => { onOpenChange(false); }}
            onPkAfterShow={() => {
                if (open) {
                    setShown(true);
                    onShown?.();
                }
            }}
        >
            <form
                ref={formRef}
                id="formie-create-report-form"
                className="flex flex-col gap-5"
                onSubmit={handleSubmit}
            >
                <FieldLayout
                    name="name"
                    label={Craft.t('app', 'Name')}
                    instructions={Craft.t('formie', 'What this report will be called in the control panel.')}
                    required
                    errors={nameErrors}
                >
                    <Input value={name} onChange={onNameChange} autoFocus autoComplete="off" />
                </FieldLayout>

                <FieldLayout
                    name="formIds"
                    label={Craft.t('formie', 'Forms')}
                    instructions={Craft.t('formie', 'Search and choose which forms this report should include. You can add or remove forms later in report settings.')}
                    required
                    errors={formIdsErrors}
                >
                    <ReportFormsSelect
                        formIds={formIds}
                        options={selectableFormOptions}
                        loading={loadedFormOptions === null && !formOptionsError}
                        emptyMessage={formOptionsError || undefined}
                        onChange={(nextFormIds) => {
                            setFormIds(Array.isArray(nextFormIds) ? nextFormIds.map(String) : []);
                            setFormIdsErrors([]);
                        }}
                    />
                    {formOptionsError && (
                        <Alert variant="error" announce="assertive" size="sm">
                            <p>{formOptionsError}</p>
                            <Button type="button" size="xs" onClick={() => { setFormOptionsAttempt((attempt) => { return attempt + 1; }); }}>
                                {Craft.t('formie', 'Try Again')}
                            </Button>
                        </Alert>
                    )}
                    {loadedFormOptions !== null && !selectableFormOptions.length && (
                        <p>{Craft.t('formie', 'No forms available.')}</p>
                    )}
                </FieldLayout>

                <div>
                    <button
                        type="button"
                        className={cn([
                            'cursor-pointer',
                            'flex items-center gap-1',
                        ])}
                        onClick={() => { setAdvancedVisible((visible) => { return !visible; }); }}
                        aria-expanded={advancedVisible}
                        aria-controls="create-report-advanced"
                    >
                        <Icon icon={advancedVisible ? 'chevron-up' : 'chevron-down'} className="size-3" />
                        {Craft.t('app', 'Advanced')}
                    </button>

                    <div id="create-report-advanced" className={advancedVisible ? 'mt-4' : 'hidden'}>
                        <FieldLayout
                            name="handle"
                            label={Craft.t('app', 'Handle')}
                            instructions={Craft.t('formie', 'How you’ll refer to this report in templates and URLs.')}
                            required
                            errors={handleErrors}
                        >
                            <Input className="code" value={handle} onChange={onHandleChange} autoComplete="off" />
                        </FieldLayout>
                    </div>
                </div>

                {formError ? (
                    <Alert variant="error" announce="assertive" size="sm">
                        {formError}
                    </Alert>
                ) : null}
            </form>

            <Button slot="footer" type="button" onClick={() => { onOpenChange(false); }}>
                {Craft.t('app', 'Cancel')}
            </Button>
            <Button
                slot="footer"
                type="submit"
                form="formie-create-report-form"
                variant="primary"
                loading={isSubmitting}
                disabled={!selectableFormOptions.length}
                onClick={handleSubmit}
            >
                {Craft.t('formie', 'Create report')}
            </Button>
        </Dialog>
    );
};
