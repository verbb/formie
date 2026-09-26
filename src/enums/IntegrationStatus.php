<?php
namespace verbb\formie\enums;

enum IntegrationStatus: string
{
    case Succeeded = 'succeeded';
    case Skipped = 'skipped';
    case Rejected = 'rejected';
    case Failed = 'failed';
    case Unknown = 'unknown';
}
