<?php
namespace verbb\formie\enums;

enum SubmissionOutcomeType: string
{
    // Cases
    // =========================================================================

    case PAGE_CHANGED = 'pageChanged';
    case DRAFT_SAVED = 'draftSaved';
    case COMPLETED = 'completed';
    case REVISED = 'revised';
    case PAYMENT_ACTION_REQUIRED = 'paymentActionRequired';
    case PAYMENT_PENDING = 'paymentPending';
    case VALIDATION_FAILED = 'validationFailed';
    case PAYMENT_FAILED = 'paymentFailed';
    case REJECTED = 'rejected';
    case STATE_CONFLICT = 'stateConflict';
}
