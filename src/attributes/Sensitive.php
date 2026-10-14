<?php
namespace verbb\formie\attributes;

use Attribute;

/** Marks sensitive setting values for redaction, not form assignability. */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Sensitive
{
}
