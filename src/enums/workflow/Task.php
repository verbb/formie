<?php
namespace verbb\formie\enums\workflow;

enum Task: string
{
    // Cases
    // =========================================================================
    
    case PREPARE_APPLY_DRAFT_CONTEXT = 'prepare.applyDraftContext';
    case PREPARE_INITIALIZE_SUBMIT_REQUEST = 'prepare.initializeSubmitRequest';

    case NORMALIZE_HANDLE_BACK_NAVIGATION = 'normalize.handleBackNavigation';
    case NORMALIZE_RESOLVE_PAGE_FLOW = 'normalize.resolvePageFlow';
    case NORMALIZE_CLEAR_CONDITIONALLY_HIDDEN_FIELDS = 'normalize.clearConditionallyHiddenFields';
    case NORMALIZE_ENSURE_SUBMISSION_DEFAULTS = 'normalize.ensureSubmissionDefaults';
    case NORMALIZE_APPLY_STATUS_RULES = 'normalize.applyStatusRules';

    case VALIDATE_SUBMISSION = 'validate.validateSubmission';

    case SCREEN_RUN_CAPTCHA_CHECKS = 'screen.runCaptchaChecks';
    case SCREEN_RUN_SPAM_CHECKS = 'screen.runSpamChecks';

    case AUTHORIZE_HALT_ON_SUBMISSION_ERRORS = 'authorize.haltOnSubmissionErrors';
    case AUTHORIZE_RESOLVE_PAYMENT_STATE = 'authorize.resolvePaymentState';

    case SAVE_PROCESS_PAYMENTS = 'save.processPayments';
    case SAVE_APPLY_COMPLETION_FROM_PAYMENT_STATE = 'save.applyCompletionFromPaymentState';
    case SAVE_PERSIST_SUBMISSION_WORKFLOW = 'save.persistSubmissionWorkflow';
    case SAVE_PERSIST_SUBMISSION_DIRECT = 'save.persistSubmissionDirect';
    case SAVE_SET_PROCESSING_SUCCESS = 'save.setProcessingSuccess';

    case DISPATCH_GUARD_DISPATCH_ELIGIBILITY = 'dispatch.guardDispatchEligibility';
    case DISPATCH_SEND_NOTIFICATIONS = 'dispatch.sendNotifications';
    case DISPATCH_TRIGGER_INTEGRATIONS = 'dispatch.triggerIntegrations';
    case DISPATCH_SEND_SPAM_NOTIFICATIONS = 'dispatch.sendSpamNotifications';
    case DISPATCH_MARK_DISPATCH_FINALIZED = 'dispatch.markDispatchFinalized';

    case FINALIZE_APPLY_SPAM_BEHAVIOUR = 'finalize.applySpamBehaviour';
    case FINALIZE_APPLY_PROGRESSION_STATE = 'finalize.applyProgressionState';
    case FINALIZE_HYDRATE_RESPONSE = 'finalize.hydrateResponse';
}
