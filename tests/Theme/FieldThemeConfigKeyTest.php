<?php

declare(strict_types=1);

use verbb\formie\fields\Date;
use verbb\formie\fields\Email;
use verbb\formie\fields\Hidden;
use verbb\formie\fields\Phone;
use verbb\formie\fields\Radio;
use verbb\formie\fields\SingleLineText;

it('resolves legacy theme config keys for aliased core field types', function (): void {
    expect((new Email())->themeConfigKey())->toBe('emailAddress')
        ->and((new Radio())->themeConfigKey())->toBe('radioButtons')
        ->and((new Date())->themeConfigKey())->toBe('dateTime')
        ->and((new Hidden())->themeConfigKey())->toBe('hiddenField')
        ->and((new Phone())->themeConfigKey())->toBe('phoneNumber');
});

it('derives theme config key from short class name for unaliased fields', function (): void {
    expect((new SingleLineText())->themeConfigKey())->toBe('singleLineText');
});
