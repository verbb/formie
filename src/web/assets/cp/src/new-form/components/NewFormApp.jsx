import { buildUniqueHandleFromSource, getErrorMessage } from '@verbb/plugin-kit-core';
import {
    useEffect, useMemo, useRef, useState,
} from 'react';

import { Button, Icon, Input, SelectInput, Separator } from '@verbb/plugin-kit-react/components';

import { FieldLayout } from '@verbb/plugin-kit-react/forms';
import { cn } from '@verbb/plugin-kit-react/utils';

/** Kit inputs dispatch this on Enter; SchemaFormEngine is not used on New Form. */
const PK_IMPLICIT_SUBMIT_EVENT = 'pk-implicit-submit';

const uniqueHandles = (settings) => {
    const handles = [
        ...(settings.formHandles || []),
        ...(settings.reservedHandles || []),
    ];

    return [...new Set(handles.filter(Boolean))];
};

const withMaxLength = (handle, maxLength) => {
    if (!maxLength || !Number.isFinite(maxLength)) {
        return handle;
    }

    return (handle || '').slice(0, Math.max(maxLength, 0));
};

const buildHandle = (sourceValue, settings) => {
    const nextHandle = buildUniqueHandleFromSource({
        sourceValue,
        reservedHandles: uniqueHandles(settings),
    });

    return withMaxLength(nextHandle, settings.maxFormHandleLength);
};

export const NewFormApp = ({ settings }) => {
    const isStencilMode = settings.showStencilSelector === false;
    const stencilOptions = useMemo(() => {
        return (settings.stencilOptions || []).map((option) => {
            return {
                ...option,
                value: String(option?.value ?? ''),
            };
        });
    }, [settings.stencilOptions]);

    const initialValues = useMemo(() => {
        return {
            name: settings.name || '',
            handle: settings.handle || '',
            applyStencilId: String(settings.applyStencilId ?? ''),
        };
    }, [settings.applyStencilId, settings.handle, settings.name]);

    const [name, setName] = useState(initialValues.name);
    const [handle, setHandle] = useState(initialValues.handle);
    const [applyStencilId, setApplyStencilId] = useState(initialValues.applyStencilId);
    const [advancedVisible, setAdvancedVisible] = useState(Boolean(settings.handleErrors?.length));
    const [nameErrors, setNameErrors] = useState(settings.nameErrors || []);
    const [handleErrors, setHandleErrors] = useState(settings.handleErrors || []);
    const [formError, setFormError] = useState(null);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isVisible, setIsVisible] = useState(false);

    const formRef = useRef(null);
    const submitInFlightRef = useRef(false);
    const lastGeneratedHandleRef = useRef(initialValues.handle || buildHandle(initialValues.name, settings));
    const normalizedReservedHandles = useMemo(() => {
        return uniqueHandles(settings).map((value) => { return String(value).toLowerCase(); });
    }, [settings]);

    useEffect(() => {
        if (handleErrors.length > 0) {
            setAdvancedVisible(true);
        }
    }, [handleErrors]);

    useEffect(() => {
        const frame = requestAnimationFrame(() => {
            setIsVisible(true);
        });

        return () => {
            cancelAnimationFrame(frame);
        };
    }, []);

    const toGeneralError = (source, fallbackText = null) => {
        const errorMessage = getErrorMessage(source);

        return {
            heading: errorMessage?.heading || Craft.t('formie', 'Something went wrong'),
            text: errorMessage?.text || fallbackText || Craft.t('formie', 'Couldn’t save form.'),
            traceAsString: errorMessage?.traceAsString || '',
        };
    };

    const validateClient = () => {
        const nextNameErrors = [];
        const nextHandleErrors = [];

        if (!name.trim()) {
            nextNameErrors.push(Craft.t('app', 'Name cannot be blank.'));
        }

        const normalizedHandle = handle.trim();
        if (!normalizedHandle) {
            nextHandleErrors.push(Craft.t('app', 'Handle cannot be blank.'));
        } else {
            if (settings.maxFormHandleLength && normalizedHandle.length > settings.maxFormHandleLength) {
                nextHandleErrors.push(Craft.t('formie', 'Handle is too long.'));
            }

            if (normalizedReservedHandles.includes(normalizedHandle.toLowerCase())) {
                nextHandleErrors.push(Craft.t('app', 'Handle has already been taken.'));
            }
        }

        setNameErrors(nextNameErrors);
        setHandleErrors(nextHandleErrors);

        return nextNameErrors.length === 0 && nextHandleErrors.length === 0;
    };

    const onNameChange = (event) => {
        const nextName = event.target.value;
        setName(nextName);
        setNameErrors([]);
        setFormError(null);

        // Keep the handle synced until a user manually edits it.
        const shouldSyncHandle = !handle || handle === lastGeneratedHandleRef.current;

        if (!shouldSyncHandle) {
            return;
        }

        const nextHandle = buildHandle(nextName, settings);
        lastGeneratedHandleRef.current = nextHandle;
        setHandle(nextHandle);
        setHandleErrors([]);
    };

    const onHandleChange = (event) => {
        setHandle(event.target.value);
        setHandleErrors([]);
        setFormError(null);
    };

    const submitAjax = async() => {
        // Sync guard — React state alone can let click + requestSubmit both enter.
        if (submitInFlightRef.current || isSubmitting) {
            return;
        }

        if (!validateClient()) {
            // Handle errors live under Advanced — open it when client validation fails there.
            setAdvancedVisible(true);
            return;
        }

        submitInFlightRef.current = true;
        setIsSubmitting(true);
        setFormError(null);
        setNameErrors([]);
        setHandleErrors([]);

        const data = {
            title: name,
            handle,
            applyStencilId: applyStencilId || '',
        };

        if (settings.scope) {
            data.scope = settings.scope;
        }

        if (settings.groupId) {
            data.groupId = settings.groupId;
        }

        if (settings.siteId) {
            data.siteId = settings.siteId;
        }

        try {
            const response = await Craft.sendActionRequest('POST', settings.submitAction || 'formie/forms/save', { data });
            const payload = response?.data || {};

            if (payload?.errors || payload?.success === false) {
                const serverErrors = payload.errors || {};
                setNameErrors(serverErrors.title || []);
                setHandleErrors(serverErrors.handle || []);

                if (serverErrors.form?.length) {
                    setFormError({
                        heading: Craft.t('formie', 'Validation error'),
                        text: serverErrors.form[0],
                        traceAsString: '',
                    });
                } else if (!serverErrors.title && !serverErrors.handle) {
                    setFormError(toGeneralError(payload, settings.saveErrorText));
                }

                submitInFlightRef.current = false;
                setIsSubmitting(false);
                return;
            }

            if (typeof payload.redirect !== 'string' || payload.redirect.trim() === '') {
                setFormError(toGeneralError(payload, settings.saveErrorText));
                submitInFlightRef.current = false;
                setIsSubmitting(false);
                return;
            }

            window.location.assign(payload.redirect);
        } catch (error) {
            console.error('Failed to save item.', error);
            setFormError(toGeneralError(error, settings.saveErrorText));
            submitInFlightRef.current = false;
            setIsSubmitting(false);
        }
    };

    const handleSubmit = (event) => {
        event.preventDefault();
        submitAjax();
    };

    // pk-input Enter → pk-implicit-submit (SchemaFormEngine is not used here).
    useEffect(() => {
        const form = formRef.current;

        if (!form) {
            return undefined;
        }

        const onImplicitSubmit = (event) => {
            event.preventDefault();
            submitAjax();
        };

        form.addEventListener(PK_IMPLICIT_SUBMIT_EVENT, onImplicitSubmit);

        return () => {
            form.removeEventListener(PK_IMPLICIT_SUBMIT_EVENT, onImplicitSubmit);
        };
    });

    const resolvedTitleText = settings.titleText || (isStencilMode
        ? Craft.t('formie', 'Create your stencil')
        : Craft.t('formie', 'Create your form'));
    const resolvedIntroText = settings.introText || (isStencilMode
        ? Craft.t('formie', 'Before you get started, you’ll need a name for your stencil.')
        : Craft.t('formie', 'Before you get started, you’ll need a name for your form.'));
    const resolvedNameInstructions = settings.nameInstructions || (isStencilMode
        ? Craft.t('formie', 'What this stencil will be called in the control panel.')
        : Craft.t('formie', 'What this form will be called in the control panel.'));
    const resolvedHandleInstructions = settings.handleInstructions || (isStencilMode
        ? Craft.t('formie', 'How you’ll refer to this stencil in the templates.')
        : Craft.t('formie', 'How you’ll refer to this form in the templates.'));

    return (
        <div className={cn([
            'flex items-center justify-center transition-opacity duration-300 ease-in-out',
            isVisible ? 'opacity-100' : 'opacity-0',
        ])}>
            <div className={cn([
                'mx-auto min-w-[550px] max-w-[760px]',
                'rounded-lg border border-[#d6dde5] bg-white p-[40px]',
                'shadow-[0_1px_0_rgba(15,23,42,0.04)]',
            ])}>
                <div className={cn([
                    'mb-12 text-center',
                ])}>
                    <h1 className={cn([
                        'text-[28px] mb-1 font-light text-[#fb6853]',
                    ])}>{resolvedTitleText}</h1>
                    <p className="text-gray-400">{resolvedIntroText}</p>
                </div>

                <form ref={formRef} className="space-y-6" onSubmit={handleSubmit}>
                    <FieldLayout
                        name="title"
                        label={Craft.t('app', 'Name')}
                        instructions={resolvedNameInstructions}
                        required
                        errors={nameErrors}
                    >
                        <Input value={name} onChange={onNameChange} autoFocus autoComplete="off" data-1p-ignore />
                    </FieldLayout>

                    {settings.showStencilSelector !== false && stencilOptions.length > 1 && (
                        <FieldLayout
                            name="applyStencilId"
                            label={Craft.t('formie', 'Stencil')}
                            instructions={Craft.t('formie', 'Select a stencil to kick-start your form with fields and settings.')}
                        >
                            <SelectInput
                                value={applyStencilId}
                                options={stencilOptions}
                                onChange={(value) => { return setApplyStencilId(String(value ?? '')); }}
                                triggerClassName="w-full"
                            />
                        </FieldLayout>
                    )}

                    <div>
                        <button
                            type="button"
                            className={cn([
                                'cursor-pointer',
                                'flex items-center gap-1',
                            ])}
                            onClick={() => { return setAdvancedVisible((visible) => { return !visible; }); }}
                            aria-expanded={advancedVisible}
                            aria-controls="new-form-advanced"
                        >
                            <Icon icon={advancedVisible ? 'chevron-up' : 'chevron-down'} className="size-3" />
                            {Craft.t('app', 'Advanced')}
                        </button>

                        <div id="new-form-advanced" className={advancedVisible ? 'mt-4' : 'hidden'}>
                            <FieldLayout
                                name="handle"
                                label={Craft.t('app', 'Handle')}
                                instructions={resolvedHandleInstructions}
                                required
                                errors={handleErrors}
                            >
                                <Input value={handle} onChange={onHandleChange} />
                            </FieldLayout>
                        </div>
                    </div>

                    {formError ? (
                        <div className="mt-4 rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                            <p className="m-0">
                                <span className="font-semibold">{formError.heading}</span>
                                {': '}
                                {formError.text}
                            </p>

                            {formError.traceAsString ? (
                                <details className="mt-2 text-xs">
                                    <summary className="cursor-pointer">{Craft.t('formie', 'Show error details')}</summary>
                                    <div className="mt-2 whitespace-pre-wrap" dangerouslySetInnerHTML={{ __html: formError.traceAsString }} />
                                </details>
                            ) : null}
                        </div>
                    ) : null}

                    <Separator className="my-5" />

                    <div className="buttons flex items-center justify-between">
                        <Button
                            href={settings.cancelUrl}
                            size="lg"
                        >
                            {Craft.t('formie', 'Cancel')}
                        </Button>

                        <Button
                            type="submit"
                            variant="primary"
                            size="lg"
                            spinnerSize="xs"
                            loading={isSubmitting}
                            onClick={handleSubmit}
                        >
                            {settings.submitLabel || Craft.t('formie', 'Next')}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    );
};
