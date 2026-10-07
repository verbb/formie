<?php
namespace verbb\formie\content;

/** @internal Typed projection discriminator for Formie's content pipeline. */
enum FieldValueProjection
{
    // Cases
    // =========================================================================

    case String;
    case Data;
    case Export;
    case Reference;
    case ReferenceBlock;
    case Summary;
    case Condition;
    case Integration;
}
