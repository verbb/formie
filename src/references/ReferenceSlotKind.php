<?php
namespace verbb\formie\references;

enum ReferenceSlotKind: string
{
    case Exact = 'reference';
    case Text = 'text';
    case Literal = 'literal';
}
