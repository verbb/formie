<?php
namespace verbb\formie\references;

enum ReferenceOutputContext: string
{
    // Cases
    // =========================================================================

    case PlainText = 'plainText';
    case Html = 'html';
    case EmailHeader = 'emailHeader';
    case UrlComponent = 'urlComponent';
    case StructuredData = 'structuredData';
}
