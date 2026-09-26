<?php
namespace verbb\formie\attributes;

use Attribute;

/** Grants form bindings access to an existing public integration property. */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class FormIntegrationSetting
{
}
