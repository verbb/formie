<?php
namespace verbb\formie\references;

enum ReferenceSlotKind: string
{
    // Cases
    // =========================================================================

    case Exact = 'reference';
    case Text = 'text';
    case Literal = 'literal';
}
