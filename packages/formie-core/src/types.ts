import type { BrowserModuleManifest } from './browser-modules';
export type KnownClientFieldType =
    | 'single-line-text'
    | 'multi-line-text'
    | 'number'
    | 'email'
    | 'phone'
    | 'dropdown'
    | 'radio'
    | 'checkboxes'
    | 'agree'
    | 'date'
    | 'name'
    | 'address'
    | 'repeater'
    | 'signature'
    | 'file';

export type ClientFieldType = KnownClientFieldType | (string & {});

export type ClientFieldValueStructure =
    | 'scalar'
    | 'fixed-parent'
    | 'container-parent'
    | 'repeatable-parent';

export type ClientFieldValueType = {
    kind: 'string' | 'boolean' | 'number' | 'object' | 'array' | 'relationQuery' | 'none' | 'storageSafe';
    representation?: 'decimal-string';
    items?: ClientFieldValueType;
    class?: string | null;
};

export type ClientFieldValueContract = {
    structure: ClientFieldValueStructure;
    valueType?: ClientFieldValueType;
};

export type ClientValidationRule = {
    message?: string;
    messages?: Record<string, string>;
    type: string;
    fieldId?: string | null;
    fieldHandle?: string | null;
    min?: number | null;
    max?: number | null;
    minDate?: string | null;
    maxDate?: string | null;
};

export type ClientFieldDefinition = {
    uid: string;
    id: string;
    key: string;
    handle: string;
    label?: string | null;
    instructions?: string | null;
    type: ClientFieldType;
    required: boolean;
    condition?: {
        version?: number;
        mode: 'all' | 'any';
        effect: 'show' | 'hide' | 'enable' | 'disable';
        clearOnHide?: boolean;
        rules: Array<{
            fieldId: string;
            operator: string;
            value: unknown;
            field?: string;
            source?: { selector?: string; handle?: string; target?: string; defaultValue?: string; transformerId?: string; transformerParams?: Record<string, string>; isValid?: boolean };
            valueType?: import('./conditions').ConditionValueType;
            browserSafe?: boolean;
        }>;
    } | null;
    validation: ClientValidationRule[];
    client?: {
        children: { model: ClientFieldValueStructure; mode?: 'parts' | 'rows' };
        valueType?: ClientFieldValueType;
    };
    /** Older bootstrap payloads used runtime.structure. */
    runtime?: ClientFieldValueContract;
    input: Record<string, unknown>;
    moduleRefs?: string[];
    meta?: Record<string, unknown>;
};

export type ClientRowDefinition = {
    fields: ClientFieldDefinition[];
};

export type ClientPageDefinition = {
    id: string;
    key: string;
    label?: string | null;
    condition?: ClientFieldDefinition['condition'];
    rows: ClientRowDefinition[];
    actions: {
        primary: {
            condition?: ClientFieldDefinition['condition'];
            type: 'next' | 'submit';
            label: string;
        };
        secondary: Array<{
            type: 'back' | 'save';
            label: string;
        }>;
    };
};

export type ClientFormDefinition = {
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
            errorAriaLive?: 'polite' | 'assertive' | 'off';
        };
        progress?: {
            enabled: boolean;
            calculation: 'completion' | 'page-position' | string;
        };
    };
    pages: ClientPageDefinition[];
    modules: BrowserModuleManifest;
    submission: {
        endpoint: string;
        uploadEndpoint?: string;
        method: 'POST';
        encoding: string;
        actions: Array<'back' | 'save' | 'submit'>;
        response: {
            successMessageMode: 'inline' | 'none';
            redirectMode: 'same-tab' | 'new-tab';
        };
    };
};

export type ClientFormSession = {
    version: number;
    id: string;
    currentPageId: string;
    tokens: {
        csrf?: {
            name: string;
            value: string;
        };
        request?: string;
        render?: string;
        uploadCreate?: string;
        captchas?: Record<string, unknown>;
    };
    continuation?: {
        submissionUid?: string;
        progressId?: string;
        grantToken?: string;
        purpose?: 'continue-incomplete' | 'revise-complete';
        submissionId?: number;
        draftContext?: string;
        draftContextToken?: string;
        resumeUrl?: string;
        [key: string]: unknown;
    } | null;
};

export type ClientFormBootstrap = {
    contractVersion: 1;
    definition: ClientFormDefinition;
    session: ClientFormSession;
};

export type ClientSubmitResult = {
    outcome?: string;
    version?: number | null;
    success: boolean;
    submissionUid?: string | null;
    resumeToken?: string | null;
    resumeUrl?: string | null;
    resumeTokenExpiresAt?: number | null;
    currentPageId?: string | null;
    nextPageId?: string | null;
    previousPageId?: string | null;
    isFinalPage: boolean;
    errors: {
        form: string[];
        fields: Record<string, string[]>;
    };
    messages: {
        notice?: string | null;
        error?: string | null;
    };
    session?: ClientFormSession | null;
    completion?: { behavior: 'message' | 'redirect' | 'reload' | 'reset'; url: string | null; target: 'same-tab' | 'new-tab'; message: string | null; hideForm: boolean } | null;
    redirect?: { url: string; target?: string } | null;
    quizResult?: Record<string, unknown> | null;
    clientEvents?: Array<Record<string, unknown>>;
    paymentStatus?: string | null;
    paymentMessage?: string | null;
    paymentRedirectUrl?: string | null;
    paymentAction?: Record<string, unknown> | null;
    paymentDecision?: Record<string, unknown> | null;
    keepSubmitLoading?: boolean;
};

export type ClientFormFieldState = {
    hidden: boolean;
    disabled: boolean;
};

export type ClientFormPageState = {
    hidden: boolean;
};

export type ClientFormState = {
    status: 'idle' | 'loading' | 'ready' | 'submitting' | 'refreshing' | 'destroyed';
    definition: ClientFormDefinition;
    session: ClientFormSession;
    values: Record<string, unknown>;
    errors: ClientSubmitResult['errors'];
    fieldStates: Record<string, ClientFormFieldState>;
    pageStates: Record<string, ClientFormPageState>;
    currentPageId: string;
    lastSubmitResult?: ClientSubmitResult | null;
};

export type ClientSubmitAction = 'back' | 'save' | 'next' | 'submit' | 'revise';

export type ClientFormEventName =
    | 'formie:client:ready'
    | 'formie:submit:result'
    | 'formie:page:navigate'
    | 'formie:page:navigate:error'
    | 'formie:session:refreshed'
    | 'formie:session:refresh:error'
    | 'formie:state:reset';

export type ClientTransport = {
    browserRequestOptions?: import('./request-profile').BrowserRequestOptions;
    submit(input: {
        definition: ClientFormDefinition;
        session: ClientFormSession;
        values: Record<string, unknown>;
        action: 'back' | 'save' | 'submit' | 'revise';
        browserData?: Record<string, unknown>;
    }): Promise<ClientSubmitResult>;
    refreshSession(input: {
        formHandle: string;
        siteId?: number;
        session: ClientFormSession;
    }): Promise<ClientFormSession>;
    setPage?(input: {
        definition: ClientFormDefinition;
        session: ClientFormSession;
        values: Record<string, unknown>;
        currentPageId?: string;
        targetPageId: string;
    }): Promise<ClientSubmitResult>;
};

export type ClientFormInstance = {
    getBrowserRequestOptions(): import('./request-profile').BrowserRequestOptions;
    setBrowserModuleGuard(guard: () => void): void;
    setBrowserModulePreparation(prepare: (action: ClientSubmitAction) => Promise<Record<string, unknown>>): void;
    id: string;
    getState(): ClientFormState;
    subscribe(listener: (state: ClientFormState) => void): () => void;
    setValue(fieldId: string, value: unknown): void;
    patchValues(values: Record<string, unknown>): void;
    submit(action?: ClientSubmitAction): Promise<ClientSubmitResult>;
    setPage(pageId: string): Promise<void>;
    refreshSession(): Promise<void>;
    reset(): void;
    destroy(): Promise<void>;
    on(eventName: ClientFormEventName | (string & {}), callback: (payload: unknown) => void): () => void;
};
