import { useEffect, useMemo, useRef, useState } from 'react';

import {
    Button,
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    Input,
} from '@verbb/plugin-kit-react/components';
import { FieldLayout } from '@verbb/plugin-kit-react/forms';
import { buildUniqueHandleFromSource, getErrorMessage } from '@verbb/plugin-kit-react/utils';
import { ReportFormsSelect } from '@reports/components/ReportFormsSelect';

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
    formOptions = [],
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
    const lastGeneratedHandleRef = useRef('');

    const toGeneralError = (source, fallbackText = null) => {
        const errorMessage = getErrorMessage(source);

        return errorMessage?.text || fallbackText || Craft.t('formie', 'Couldn’t create report.');
    };

    const uniqueHandles = useMemo(() => {
        return [...new Set([
            ...reservedHandles,
            ...reportHandles,
        ].filter(Boolean).map((value) => String(value).toLowerCase()))];
    }, [reportHandles, reservedHandles]);

    const selectableFormOptions = useMemo(() => {
        return (formOptions || []).filter((option) => option.value && option.value !== '*');
    }, [formOptions]);

    useEffect(() => {
        if (!open) {
            setName('');
            setHandle('');
            setFormIds([]);
            setNameErrors([]);
            setHandleErrors([]);
            setFormIdsErrors([]);
            setFormError(null);
            setIsSubmitting(false);
            lastGeneratedHandleRef.current = '';
            return;
        }

        if (selectableFormOptions.length === 1) {
            setFormIds([String(selectableFormOptions[0].value)]);
        }
    }, [open, selectableFormOptions]);

    const onNameChange = (event) => {
        const nextName = event.target.value;
        setName(nextName);
        setNameErrors([]);

        const nextHandle = buildHandle(nextName, uniqueHandles);
        lastGeneratedHandleRef.current = nextHandle;
        setHandle(nextHandle);
        setHandleErrors([]);
    };

    const onHandleChange = (event) => {
        setHandle(event.target.value);
        setHandleErrors([]);
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        setIsSubmitting(true);
        setFormError(null);
        setNameErrors([]);
        setHandleErrors([]);
        setFormIdsErrors([]);

        const normalizedFormIds = (formIds || []).map((value) => parseInt(String(value), 10)).filter(Boolean);

        if (!normalizedFormIds.length) {
            setFormIdsErrors([Craft.t('formie', 'Choose at least one form.')]);
            setIsSubmitting(false);
            return;
        }

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

                setIsSubmitting(false);
                return;
            }

            onCreated(payload);
            setIsSubmitting(false);
        } catch (error) {
            console.error(error);
            setFormError(toGeneralError(error));
            setIsSubmitting(false);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-lg">
                <DialogHeader>
                    <DialogTitle>{Craft.t('formie', 'New report')}</DialogTitle>
                </DialogHeader>

                <form className="flex flex-col" onSubmit={handleSubmit}>
                    <div className="flex flex-col gap-5 px-6 py-5">
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
                            name="handle"
                            label={Craft.t('app', 'Handle')}
                            instructions={Craft.t('formie', 'How you’ll refer to this report in templates and URLs.')}
                            required
                            errors={handleErrors}
                        >
                            <Input className="code" value={handle} onChange={onHandleChange} autoComplete="off" />
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
                                disabled={!selectableFormOptions.length}
                                onChange={(nextFormIds) => {
                                    setFormIds(Array.isArray(nextFormIds) ? nextFormIds.map(String) : []);
                                    setFormIdsErrors([]);
                                }}
                            />
                        </FieldLayout>

                        {formError ? (
                            <p className="text-sm text-rose-600">{formError}</p>
                        ) : null}
                    </div>

                    <DialogFooter className="gap-2">
                        <Button type="button" onClick={() => { onOpenChange(false); }}>
                            {Craft.t('app', 'Cancel')}
                        </Button>
                        <Button
                            type="submit"
                            variant="primary"
                            loading={isSubmitting}
                            disabled={!selectableFormOptions.length}
                        >
                            {Craft.t('formie', 'Create report')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
};
