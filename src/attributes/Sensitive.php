<?php
namespace verbb\formie\attributes;

use Attribute;

/** Marks a setting for encryption and diagnostic redaction, not form assignability. */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Sensitive
{
}
