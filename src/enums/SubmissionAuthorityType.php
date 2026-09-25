<?php
namespace verbb\formie\enums;

enum SubmissionAuthorityType: string
{
    // Cases
    // =========================================================================

    case VISITOR = 'visitor';
    case CONTROL_PANEL = 'controlPanel';
    case GRAPHQL_ADMIN = 'graphqlAdmin';
    case PAYMENT_REPLAY = 'paymentReplay';
    case TRUSTED_INTERNAL = 'trustedInternal';
}
