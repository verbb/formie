<?php
namespace verbb\formie\references;

enum ReferenceDiagnostic: string
{
    // Cases
    // =========================================================================

    case InvalidExpression = 'invalidExpression';
    case UnknownSource = 'unknownSource';
    case UnknownTransform = 'unknownTransform';
    case MissingField = 'missingField';
    case AmbiguousField = 'ambiguousField';
    case InvalidSelector = 'invalidSelector';
    case MissingRowScope = 'missingRowScope';
    case InvalidRowScope = 'invalidRowScope';
    case ForbiddenSource = 'forbiddenSource';
    case InvalidType = 'invalidType';
    case InvalidOutput = 'invalidOutput';
}
