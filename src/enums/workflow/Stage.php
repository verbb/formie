<?php
namespace verbb\formie\enums\workflow;

enum Stage: string
{
    // Cases
    // =========================================================================
    
    case PREPARE = 'prepare';
    case NORMALIZE = 'normalize';
    case VALIDATE = 'validate';
    case SCREEN = 'screen';
    case AUTHORIZE = 'authorize';
    case SAVE = 'save';
    case DISPATCH = 'dispatch';
    case FINALIZE = 'finalize';
}
