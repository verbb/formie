<?php
namespace verbb\formie\enums;

enum CompletionBehavior: string
{
    // Cases
    // =========================================================================

    case Message = 'message';
    case Redirect = 'redirect';
    case Reload = 'reload';
    case Reset = 'reset';
}
