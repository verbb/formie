<?php
namespace verbb\formie\base;

/**
 * Formie 3 compatibility marker for rich field values.
 *
 * Formie 4's canonical value contract lives under `fields\values`; this empty
 * marker remains so existing third-party value classes can still be loaded.
 */
interface FieldValueInterface
{
}
