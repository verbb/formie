<?php
namespace verbb\formie\enums\workflow;

enum Stage: string
{
    // Cases
    // =========================================================================

    case PREFLIGHT = 'preflight';
    case VALIDATE = 'validate';
    case SCREEN = 'screen';
    case PERSIST = 'persist';
    case DISPATCH = 'dispatch';
    case FINALIZE = 'finalize';
}
