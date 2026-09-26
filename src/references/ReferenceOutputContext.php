<?php
namespace verbb\formie\references;

enum ReferenceOutputContext: string
{
    case PlainText = 'plainText';
    case Html = 'html';
    case EmailHeader = 'emailHeader';
    case UrlComponent = 'urlComponent';
    case StructuredData = 'structuredData';
}
