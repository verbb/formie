<?php
namespace verbb\formie\enums\workflow;

enum Task: string
{
    // Cases
    // =========================================================================

    case PREFLIGHT_RESOLVE_NAVIGATION_INTENT = 'preflight.resolveNavigationIntent';
    case PREFLIGHT_APPLY_SUBMISSION_DEFAULTS = 'preflight.applySubmissionDefaults';
    case PREFLIGHT_CLEAR_HIDDEN_VALUES = 'preflight.clearHiddenValues';
    case PREFLIGHT_RESOLVE_TRANSITION = 'preflight.resolveTransition';
    case PREFLIGHT_CAPTURE_METADATA = 'preflight.captureMetadata';
    case PREFLIGHT_APPLY_STATUS_RULES = 'preflight.applyStatusRules';
    case VALIDATE_SUBMISSION = 'validate.submission';
    case VALIDATE_ENFORCE_PROGRESSION = 'validate.enforceProgression';
    case VALIDATE_RESOLVE_TRANSITION = 'validate.resolveTransition';
    case SCREEN_EVALUATE_SPAM = 'screen.evaluateSpam';
    case SCREEN_VERIFY_CAPTCHA = 'screen.verifyCaptcha';
    case PERSIST_SUBMISSION = 'persist.submission';
    case PERSIST_PROCESS_PAYMENT = 'persist.processPayment';
    case PERSIST_QUESTIONNAIRE_RESULT = 'persist.questionnaireResult';
    case DISPATCH_SEND_NOTIFICATIONS = 'dispatch.sendNotifications';
    case DISPATCH_TRIGGER_INTEGRATIONS = 'dispatch.triggerIntegrations';
    case DISPATCH_SEND_SPAM_NOTIFICATIONS = 'dispatch.sendSpamNotifications';
}
