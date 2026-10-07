<?php
namespace verbb\formie\references;

enum ReferenceUsage: string
{
    // Cases
    // =========================================================================

    case Text = 'text';
    case RichText = 'richText';
    case EmailHeader = 'emailHeader';
    case Integration = 'integration';
    case Url = 'url';
    case Condition = 'condition';


    // Static Methods
    // =========================================================================

    public static function forOutput(ReferenceOutputContext $output): self
    {
        return match ($output) {
            ReferenceOutputContext::Html => self::RichText,
            ReferenceOutputContext::EmailHeader => self::EmailHeader,
            ReferenceOutputContext::UrlComponent => self::Url,
            default => self::Text,
        };
    }
}
