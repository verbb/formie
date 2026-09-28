<?php
namespace verbb\formie\enums;

enum SubscriptionCancellationMode: string
{
    // Cases
    // =========================================================================

    case AT_PERIOD_END = 'atPeriodEnd';
    case IMMEDIATE = 'immediate';
}
