<?php
namespace verbb\formie\enums;

enum SubmissionOperation: string
{
    // Cases
    // =========================================================================

    case SUBMIT = 'submit';
    case SAVE_DRAFT = 'saveDraft';
    case REVISE = 'revise';
    case PAYMENT_REPLAY = 'paymentReplay';
}
