<?php
namespace verbb\formie\enums;

enum NavigationIntent: string
{
    // Cases
    // =========================================================================

    case ADVANCE = 'advance';
    case BACK = 'back';
    case STAY = 'stay';
    case TARGET = 'target';
}
