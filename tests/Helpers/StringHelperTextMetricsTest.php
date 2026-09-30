<?php

declare(strict_types=1);

use verbb\formie\helpers\StringHelper;

it('counts words as non-whitespace runs', function (): void {
    $value = 'H₂O₂, 1,023 (the comma splits it) and factor–circadian splits it.';

    expect(StringHelper::getWordCount($value))->toBe(10)
        ->and(StringHelper::getWordCount("one\u{00A0}two\u{0085}three"))->toBe(3)
        ->and(StringHelper::normalizeText(" one\u{00A0}\ttwo\u{0085}three "))->toBe('one two three');
});

it('counts extended grapheme clusters', function (): void {
    expect(StringHelper::getCharacterCount("e\u{0301}"))->toBe(1)
        ->and(StringHelper::getCharacterCount('👨‍👩‍👧‍👦'))->toBe(1)
        ->and(StringHelper::getCharacterCount('🇦🇺'))->toBe(1)
        ->and(StringHelper::getCharacterCount('❤️‍🔥'))->toBe(1)
        ->and(StringHelper::getCharacterCount('🙂‍↔️'))->toBe(1);
});

it('uses decoded plain text for character limits', function (): void {
    expect(StringHelper::getCharacterCount('<strong>one</strong> two &#x1F389;'))->toBe(9);
});
