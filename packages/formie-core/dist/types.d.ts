export type KnownFrontendFieldType = 'single-line-text' | 'multi-line-text' | 'number' | 'email' | 'phone' | 'dropdown' | 'radio' | 'checkboxes' | 'agree' | 'date' | 'name' | 'address' | 'repeater' | 'signature' | 'file';
export type FrontendFieldType = KnownFrontendFieldType | (string & {});
export type FrontendFieldValueStructure = 'scalar' | 'fixed-parent' | 'container-parent' | 'repeatable-parent';
export type FrontendFieldValueClass = {
    class?: string | null;
};
export type FrontendFieldValueContract = {
    structure: FrontendFieldValueStructure;
    valueClass?: FrontendFieldValueClass;
};
export type FrontendValidationRule = {
    type: string;
    fieldId?: string | null;
    fieldHandle?: string | null;
    min?: number | null;
    max?: number | null;
    minDate?: string | null;
    maxDate?: string | null;
};
export type FrontendModuleTarget = {
    targetType: 'form' | 'page' | 'field' | 'slot';
    targetId: string;
};
export type FrontendModuleManifest = {
    id: string;
    type: 'field' | 'captcha' | 'address' | 'payment' | 'custom';
    capability: string;
    targets: FrontendModuleTarget[];
    config?: Record<string, unknown>;
};
export type FrontendFieldDefinition = {
    id: string;
    key: string;
    handle: string;
    label?: string | null;
    instructions?: string | null;
    type: FrontendFieldType;
    required: boolean;
    condition?: {
        mode: 'all' | 'any';
        effect: 'show' | 'hide' | 'enable' | 'disable';
        clearOnHide?: boolean;
        rules: Array<{
            fieldId: string;
            operator: string;
            value: unknown;
        }>;
    } | null;
    validation: FrontendValidationRule[];
    runtime?: FrontendFieldValueContract;
    input: Record<string, unknown>;
    moduleRefs?: string[];
    meta?: Record<string, unknown>;
};
export type FrontendRowDefinition = {
    fields: FrontendFieldDefinition[];
};
export type FrontendPageDefinition = {
    id: string;
    key: string;
    label?: string | null;
    condition?: FrontendFieldDefinition['condition'];
    rows: FrontendRowDefinition[];
    actions: {
        primary: {
            type: 'next' | 'submit';
            label: string;
        };
        secondary: Array<{
            type: 'back' | 'save';
            label: string;
        }>;
    };
};
export type FrontendFormDefinition = {
    id: string;
    handle: string;
    title?: string | null;
    locale?: string | null;
    siteId?: number | null;
    settings: {
        initialPageId: string;
        submitMethod: 'ajax';
        validation: {
            onBlur: boolean;
            onSubmit: boolean;
            formErrorMessage?: string;
        };
        progress?: {
            enabled: boolean;
            calculation: 'completion' | 'page-position' | string;
        };
    };
    pages: FrontendPageDefinition[];
    modules: FrontendModuleManifest[];
    submission: {
        endpoint: string;
        method: 'POST';
        encoding: string;
        actions: Array<'back' | 'save' | 'submit'>;
        response: {
            successMessageMode: 'inline' | 'none';
            redirectMode: 'same-tab' | 'new-tab';
        };
    };
};
export type FrontendFormSession = {
    id: string;
    currentPageId: string;
    tokens: {
        csrf?: {
            name: string;
            value: string;
        };
        request?: string;
        render?: string;
        captchas?: Record<string, unknown>;
    };
    continuation?: {
        submissionUid?: string;
        continuationToken?: string;
        draftContext?: string;
        draftContextToken?: string;
        resumeUrl?: string;
        [key: string]: unknown;
    } | null;
};
export type FrontendFormEnvelope = {
    schemaVersion: 1;
    definition: FrontendFormDefinition;
    session: FrontendFormSession;
};
export type FrontendSubmitResult = {
    success: boolean;
    submissionUid?: string | null;
    currentPageId?: string | null;
    nextPageId?: string | null;
    previousPageId?: string | null;
    isFinalPage: boolean;
    errors: {
        form: string[];
        fields: Record<string, string[]>;
        pages: Record<string, string[]>;
    };
    messages: {
        notice?: string | null;
        error?: string | null;
    };
    session?: FrontendFormSession | null;
    quizResult?: Record<string, unknown> | null;
    clientEvents?: Array<Record<string, unknown>>;
    paymentStatus?: string | null;
    paymentMessage?: string | null;
    paymentRedirectUrl?: string | null;
    paymentAction?: Record<string, unknown> | null;
    paymentDecision?: Record<string, unknown> | null;
    keepSubmitLoading?: boolean;
};
export type FrontendFormFieldState = {
    hidden: boolean;
    disabled: boolean;
};
export type FrontendFormPageState = {
    hidden: boolean;
};
export type FrontendFormState = {
    status: 'idle' | 'loading' | 'ready' | 'submitting' | 'refreshing' | 'destroyed';
    definition: FrontendFormDefinition;
    session: FrontendFormSession;
    values: Record<string, unknown>;
    errors: FrontendSubmitResult['errors'];
    fieldStates: Record<string, FrontendFormFieldState>;
    pageStates: Record<string, FrontendFormPageState>;
    currentPageId: string;
    lastSubmitResult?: FrontendSubmitResult | null;
};
export type FrontendSubmitAction = 'back' | 'save' | 'next' | 'submit';
export type FrontendFormEventName = 'formie:client:ready' | 'formie:submit:result' | 'formie:page:navigate' | 'formie:page:navigate:error' | 'formie:session:refreshed' | 'formie:session:refresh:error' | 'formie:state:reset';
export type FrontendTransport = {
    submit(input: {
        definition: FrontendFormDefinition;
        session: FrontendFormSession;
        values: Record<string, unknown>;
        action: 'back' | 'save' | 'submit';
    }): Promise<FrontendSubmitResult>;
    refreshSession(input: {
        formHandle: string;
        siteId?: number;
        session: FrontendFormSession;
    }): Promise<FrontendFormSession>;
    setPage?(input: {
        definition: FrontendFormDefinition;
        session: FrontendFormSession;
        values: Record<string, unknown>;
        currentPageId?: string;
        targetPageId: string;
    }): Promise<FrontendFormSession>;
};
export type FrontendFormInstance = {
    id: string;
    getState(): FrontendFormState;
    subscribe(listener: (state: FrontendFormState) => void): () => void;
    setValue(fieldId: string, value: unknown): void;
    patchValues(values: Record<string, unknown>): void;
    submit(action?: FrontendSubmitAction): Promise<FrontendSubmitResult>;
    setPage(pageId: string): Promise<void>;
    refreshSession(): Promise<void>;
    reset(): void;
    destroy(): Promise<void>;
    on(eventName: FrontendFormEventName | (string & {}), callback: (payload: unknown) => void): () => void;
};
//# sourceMappingURL=types.d.ts.map